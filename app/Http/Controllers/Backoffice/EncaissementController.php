<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Payments\Queries\GetEncaissementDetails;
use App\Domain\Payments\Queries\GetEncaissementsList;
use App\Domain\Payments\Queries\GetInscriptionUnpaidFees;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Encaissements\StoreEncaissementRequest;
use App\Http\Requests\Backoffice\Encaissements\UpdateEncaissementRequest;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Payments list + create/edit with the cascading multi-row payment form
 * (Phase 10, docs/phase-10-finance-audit.md §2.4) — mirrors
 * EncaissementsIndex one-for-one, including the multi-row single-submit
 * DB::transaction (an invalid row rolls back every row already processed in
 * this submit) and the per-row `fee->inscription_id === inscription_id`
 * ownership check. No destroy(): a recorded payment is never deleted —
 * corrections go through a remboursement + new encaissement.
 */
final class EncaissementController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Encaissement::class, 'encaissement');
    }

    public function index(
        Request $request,
        GetEncaissementsList $getEncaissementsList,
    ): Response {
        $search = (string) $request->string('search');
        $caisseFilter = (string) $request->string('caisseFilter');
        $methodeFilter = (string) $request->string('methodeFilter');
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $perPage = (int) $request->integer('perPage', GetEncaissementsList::DEFAULT_PER_PAGE);
        // Page view tabs: Paiements (all) / Avances / Chèques.
        $view = (string) $request->string('view');
        if (! in_array($view, ['', 'avance', 'cheque'], true)) {
            $view = '';
        }

        return Inertia::render('Backoffice/Encaissements/Index', [
            'encaissements' => $getEncaissementsList($request->user(), $search, $caisseFilter, $methodeFilter, $dateFrom, $dateTo, $perPage, $view),
            'caisses' => $getEncaissementsList->caisseOptions($request->user()),
            'students' => $getEncaissementsList->studentOptions($request->user()),
            'methodes' => Encaissement::METHODES,
            'filters' => [
                'search' => $search,
                'caisseFilter' => $caisseFilter,
                'methodeFilter' => $methodeFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'perPage' => $perPage,
                'view' => $view,
            ],
        ]);
    }

    /**
     * Cascade step 1→2 lookup: a student's registrations, then (via
     * inscriptionFees()) one row per unpaid fee — mirrors
     * EncaissementsIndex::updatedStudentId()/updatedInscriptionId().
     */
    public function studentInscriptions(Request $request, int $student, GetEncaissementsList $getEncaissementsList): JsonResponse
    {
        $this->authorize('create', Encaissement::class);

        return response()->json(['inscriptions' => $getEncaissementsList->studentInscriptions($student)]);
    }

    public function inscriptionFees(Inscription $inscription, GetInscriptionUnpaidFees $getInscriptionUnpaidFees): JsonResponse
    {
        $this->authorize('create', Encaissement::class);

        return response()->json(['fees' => $getInscriptionUnpaidFees($inscription)]);
    }

    public function store(StoreEncaissementRequest $request, EnregistrerEncaissement $action): RedirectResponse
    {
        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                'payment_lines' => __('Your account is not linked to any employee record.'),
            ]);
        }

        // The till is ALWAYS the acting employee's own caisse — never chosen
        // client-side, for any role (the modal shows no caisse field). Same
        // self-heal as the journal's "mine" scope for pre-provisioner
        // accounts (CaisseProvisioner is idempotent).
        $caisse = $agent->caisses()->first()
            ?? app(\App\Services\CaisseProvisioner::class)->provisionFor($agent);

        $data = $request->validated();
        $inscriptionId = (int) $data['inscription_id'];

        $touchedLines = collect($data['payment_lines'])->filter(fn ($l) => ($l['montant'] ?? '') !== '');

        DB::transaction(function () use ($touchedLines, $data, $inscriptionId, $agent, $caisse, $action): void {
            foreach ($touchedLines as $line) {
                $fee = InscriptionFee::findOrFail($line['fee_id']);

                // A tampered client could inject a fee id belonging to a
                // different registration — refused exactly like
                // EncaissementsIndex::save()'s own inline guard, rolling
                // back every row already processed in this submit.
                if ($fee->inscription_id !== $inscriptionId) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('One of the selected fees does not belong to this registration.'),
                    ]);
                }

                $action->handle([
                    'student_id' => $data['student_id'],
                    'inscription_fee_id' => $fee->id,
                    'montant' => $line['montant'],
                    'methode' => $line['methode'],
                    'date_paiement' => $line['date_paiement'],
                    'caisse_id' => $caisse->id,
                    'numero_cheque' => $line['methode'] === Encaissement::METHODE_CHEQUE ? ($data['numero_cheque'] ?? null) : null,
                    'banque' => $line['methode'] === Encaissement::METHODE_CHEQUE ? ($data['banque'] ?? null) : null,
                    'date_echeance_cheque' => $line['methode'] === Encaissement::METHODE_CHEQUE ? ($data['date_echeance_cheque'] ?? null) : null,
                    'note' => $data['note'] ?? null,
                ], $agent);
            }
        });

        return redirect()->route('backoffice.encaissements.index')
            ->with('success', __('Payment recorded.'));
    }

    public function show(Encaissement $encaissement, GetEncaissementDetails $getEncaissementDetails): Response
    {
        return Inertia::render('Backoffice/Encaissements/Show', [
            'encaissement' => $getEncaissementDetails($encaissement),
        ]);
    }

    public function update(UpdateEncaissementRequest $request, Encaissement $encaissement): RedirectResponse
    {
        // montant / caisse_id are not editable (see UpdateEncaissementRequest);
        // this edit is audit-logged by LogsActivity.
        $encaissement->update($request->validated());

        return redirect()->route('backoffice.encaissements.index')
            ->with('success', __('Payment updated.'));
    }
}
