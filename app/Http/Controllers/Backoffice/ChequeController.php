<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Queries\GetChequesList;
use App\Domain\Payments\Queries\GetEncaissementsList;
use App\Domain\Settings\Queries\GetBanquesList;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Cheques\StoreChequeRequest;
use App\Http\Requests\Backoffice\Cheques\UpdateChequeRequest;
use App\Models\Cheque;
use App\Models\Student;
use App\Services\Context\CurrentContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Chèques in hand — off-ledger inventory of physical checks received.
 * A Cheque row never moves money by itself (caisses.solde is only touched
 * by EnregistrerEncaissement); "Remise à la banque" and the other statut
 * moves are pure lifecycle bookkeeping. No destroy route: like every
 * money-adjacent record, corrections go through edits/statut changes,
 * never deletion.
 *
 * A Rejeté chèque has two distinct, independent follow-ups (neither
 * automatic — always a separate reviewed user action, matching how every
 * other refund/money move in this app works):
 *   - Refunding the encaissement(s) it funded goes through the normal
 *     Remboursement flow (RemboursementController), pre-filled from the UI
 *     when the chèque funded exactly one encaissement.
 *   - Returning the physical chèque to its owner is recorded here via
 *     markRetourne() — off-ledger, records who returned it.
 */
final class ChequeController extends Controller
{
    public function index(
        Request $request,
        GetChequesList $getChequesList,
        GetEncaissementsList $getEncaissementsList,
        GetBanquesList $getBanquesList,
    ): Response {
        $this->authorize('viewAny', Cheque::class);

        $numeroFilter = (string) $request->string('numeroFilter');
        $proprietaireFilter = (string) $request->string('proprietaireFilter');
        $banqueFilter = (string) $request->string('banqueFilter');
        $typeFilter = (string) $request->string('typeFilter');
        $statutFilter = (string) $request->string('statutFilter');
        $dateEcheanceFrom = (string) $request->string('dateEcheanceFrom');
        $dateEcheanceTo = (string) $request->string('dateEcheanceTo');
        $perPage = (int) $request->integer('perPage', GetChequesList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Cheques/Index', [
            'cheques' => $getChequesList(
                $request->user(),
                $numeroFilter,
                $proprietaireFilter,
                $banqueFilter,
                $typeFilter,
                $statutFilter,
                $dateEcheanceFrom,
                $dateEcheanceTo,
                $perPage,
            ),
            'filters' => [
                'numeroFilter' => $numeroFilter,
                'proprietaireFilter' => $proprietaireFilter,
                'banqueFilter' => $banqueFilter,
                'typeFilter' => $typeFilter,
                'statutFilter' => $statutFilter,
                'dateEcheanceFrom' => $dateEcheanceFrom,
                'dateEcheanceTo' => $dateEcheanceTo,
                'perPage' => in_array($perPage, GetChequesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetChequesList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetChequesList::PER_PAGE_OPTIONS,
            'sources' => Cheque::SOURCES,
            'types' => Cheque::TYPES,
            'statuts' => Cheque::STATUTS,
            'banques' => $getBanquesList->activeNames(),
            'students' => $getEncaissementsList->studentOptions($request->user()),
            'parents' => $getChequesList->parentOptions($request->user()),
            'canCreate' => $request->user()->can('cheques.create'),
            'canUpdate' => $request->user()->can('cheques.update'),
        ]);
    }

    public function store(StoreChequeRequest $request): RedirectResponse
    {
        $this->authorize('create', Cheque::class);

        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                'montant' => __('Your account is not linked to any employee record.'),
            ]);
        }

        $data = $request->validated();

        Cheque::create([
            ...$this->normalizedPayload($data),
            'reference' => ReferenceGenerator::make('CHQ', 'cheques'),
            'statut' => Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => app(CurrentContext::class)->etablissementId(),
            'agent_id' => $agent->id,
        ]);

        return redirect()->route('backoffice.cheques.index')
            ->with('success', __('Cheque recorded.'));
    }

    public function update(UpdateChequeRequest $request, Cheque $cheque): RedirectResponse
    {
        $this->authorize('update', $cheque);

        $data = $request->validated();

        // The montant can never drop below what has already been used to
        // pay (linked Encaissements are append-only and never deleted).
        $utilise = $cheque->montantUtilise();
        if ((float) $data['montant'] < $utilise) {
            throw ValidationException::withMessages([
                'montant' => __('The amount cannot be lower than what has already been used from this cheque.'),
            ]);
        }

        $cheque->update($this->normalizedPayload($data));

        return redirect()->route('backoffice.cheques.index')
            ->with('success', __('Cheque updated.'));
    }

    /**
     * Lifecycle moves: En possession -> Déposé ("Remise à la banque"),
     * Déposé -> Encaissé | Rejeté. Any other transition is refused.
     */
    public function updateStatut(Request $request, Cheque $cheque): RedirectResponse
    {
        $this->authorize('update', $cheque);

        $statut = $request->string('statut')->toString();

        $allowed = match ($cheque->statut) {
            Cheque::STATUT_EN_POSSESSION => [Cheque::STATUT_DEPOSE],
            Cheque::STATUT_DEPOSE => [Cheque::STATUT_ENCAISSE, Cheque::STATUT_REJETE],
            default => [],
        };

        if (! in_array($statut, $allowed, true)) {
            throw ValidationException::withMessages([
                'statut' => __('This status change is not allowed from the current status.'),
            ]);
        }

        $cheque->update(['statut' => $statut]);

        return redirect()->route('backoffice.cheques.index')
            ->with('success', __('Cheque status updated.'));
    }

    /**
     * Records that a rejected chèque was physically handed back to its
     * owner — off-ledger bookkeeping only (never touches money; a refund
     * for the encaissement(s) it funded is recorded separately via
     * Remboursement). Only allowed once, only from Rejeté.
     */
    public function markRetourne(Request $request, Cheque $cheque): RedirectResponse
    {
        $this->authorize('update', $cheque);

        if ($cheque->statut !== Cheque::STATUT_REJETE) {
            throw ValidationException::withMessages([
                'statut' => __('Only a rejected cheque can be marked as returned.'),
            ]);
        }

        if ($cheque->estRetourne()) {
            throw ValidationException::withMessages([
                'statut' => __('This cheque has already been marked as returned.'),
            ]);
        }

        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                'statut' => __('Your account is not linked to any employee record.'),
            ]);
        }

        $cheque->update([
            'retourne_le' => now(),
            'retourne_par_id' => $agent->id,
        ]);

        return redirect()->route('backoffice.cheques.index')
            ->with('success', __('Cheque marked as returned to its owner.'));
    }

    /**
     * A student's chèques still holding value (Reste > 0) — feeds the
     * "Payer avec un chèque" dropdown in the Encaissements payment form.
     */
    public function studentCheques(Request $request, Student $student): JsonResponse
    {
        $this->authorize('viewAny', Cheque::class);

        $cheques = Cheque::query()
            ->where('student_id', $student->id)
            ->whereNotIn('statut', [Cheque::STATUT_REJETE])
            ->orderByDesc('date_reception')
            ->get()
            ->map(fn (Cheque $cheque): array => [
                'id' => $cheque->id,
                'numeroCheque' => $cheque->numero_cheque,
                'banque' => $cheque->banque,
                'montant' => number_format((float) $cheque->montant, 2, '.', ''),
                'reste' => number_format($cheque->montantRestant(), 2, '.', ''),
                'statut' => $cheque->statut,
            ])
            ->filter(fn (array $c): bool => (float) $c['reste'] > 0)
            ->values();

        return response()->json(['cheques' => $cheques]);
    }

    /**
     * Empty strings -> null for optional fields; the owner fields are
     * mutually exclusive by source (student_id only for Étudiant,
     * proprietaire_nom only for Parents).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedPayload(array $data): array
    {
        $isEtudiant = $data['source'] === Cheque::SOURCE_ETUDIANT;

        return [
            'source' => $data['source'],
            'student_id' => $isEtudiant ? ($data['student_id'] ?? null) : null,
            'proprietaire_nom' => $isEtudiant ? null : ($data['proprietaire_nom'] ?? null),
            'numero_cheque' => $data['numero_cheque'],
            'montant' => $data['montant'],
            'banque' => ($data['banque'] ?? '') !== '' ? $data['banque'] : null,
            'date_reception' => $data['date_reception'],
            'type' => $data['type'],
            'date_echeance' => ($data['date_echeance'] ?? '') !== '' ? $data['date_echeance'] : null,
            'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
        ];
    }
}
