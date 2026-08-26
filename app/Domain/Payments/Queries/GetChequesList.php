<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Cheque;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Chèques list page — same center/context scoping
 * conventions as every other finance list (GetEncaissementsList et al.).
 */
final class GetChequesList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return array{data: LengthAwarePaginator, montantTotal: string}
     */
    public function __invoke(
        User $user,
        string $numeroFilter = '',
        string $proprietaireFilter = '',
        string $banqueFilter = '',
        string $typeFilter = '',
        string $statutFilter = '',
        string $dateEcheanceFrom = '',
        string $dateEcheanceTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): array {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $base = Cheque::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                $id = $this->context->etablissementId();
                if ($id !== null) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
                }
            })
            ->when($numeroFilter !== '', fn ($q) => $q->where('numero_cheque', 'ilike', "%{$numeroFilter}%"))
            ->when($proprietaireFilter !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('proprietaire_nom', 'ilike', "%{$proprietaireFilter}%")
                ->orWhereHas('student', fn ($s) => $s
                    ->where('nom', 'ilike', "%{$proprietaireFilter}%")
                    ->orWhere('prenom', 'ilike', "%{$proprietaireFilter}%"))))
            ->when($banqueFilter !== '', fn ($q) => $q->where('banque', $banqueFilter))
            ->when($typeFilter !== '', fn ($q) => $q->where('type', $typeFilter))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($dateEcheanceFrom !== '', fn ($q) => $q->whereDate('date_echeance', '>=', $dateEcheanceFrom))
            ->when($dateEcheanceTo !== '', fn ($q) => $q->whereDate('date_echeance', '<=', $dateEcheanceTo))
            // Year switcher: a cheque follows its échéance into the year it
            // falls in (the page's own date filters are échéance-based); the
            // active year is only the DEFAULT window — an explicit échéance
            // filter takes over. A cheque with no échéance stays visible.
            ->when(
                $dateEcheanceFrom === '' && $dateEcheanceTo === '' && $this->context->anneeDateRange() !== null,
                fn ($q) => $q->where(fn ($sub) => $sub
                    ->whereBetween('date_echeance', $this->context->anneeDateRange())
                    ->orWhereNull('date_echeance')),
            )
            ->latest();

        // Total over every chèque matching the current filters (not just the
        // page shown) — same convention as GetDepensesList/GetEncaissementsList.
        $montantTotal = (clone $base)->sum('montant');

        $cheques = (clone $base)
            ->with(['student', 'agent', 'retournePar', 'encaissements' => fn ($q) => $q->with('student')])
            ->paginate($perPage)
            ->withQueryString();

        $cheques->through(fn (Cheque $cheque): array => [
            'id' => $cheque->id,
            'reference' => $cheque->reference,
            'source' => $cheque->source,
            'studentId' => $cheque->student_id,
            'proprietaire' => $cheque->proprietaireLabel(),
            'proprietaireNom' => $cheque->proprietaire_nom,
            'telephone' => $cheque->student?->telephone,
            'whatsapp' => $cheque->student?->whatsapp,
            'numeroCheque' => $cheque->numero_cheque,
            'montant' => number_format((float) $cheque->montant, 2, '.', ''),
            'reste' => number_format($cheque->montantRestant(), 2, '.', ''),
            'banque' => $cheque->banque,
            'dateReception' => $cheque->date_reception?->toDateString(),
            'type' => $cheque->type,
            'dateEcheance' => $cheque->date_echeance?->toDateString(),
            'statut' => $cheque->statut,
            'note' => $cheque->note ?? '',
            'agentNom' => $cheque->agent?->nomComplet(),
            'retourneLe' => $cheque->retourne_le?->toDateTimeString(),
            'retourneParNom' => $cheque->retournePar?->nomComplet(),
            'encaissements' => $cheque->encaissements->map(fn ($e): array => [
                'id' => $e->id,
                'reference' => $e->reference,
                'montant' => number_format((float) $e->montant, 2, '.', ''),
                'studentId' => $e->student_id,
                'studentNom' => $e->student?->nomComplet(),
            ])->values()->all(),
        ]);

        return [
            'data' => $cheques,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
        ];
    }

    /**
     * Students with a parent/guardian on file, for the "Source: Parents"
     * owner picker — selecting one fills `proprietaire_nom` from the
     * student's inline parent_nom (no separate parents table, see
     * Student::PARENT_RELATIONS). Excludes students with no parent name.
     *
     * @return Collection<int, array{id:int, studentNom:string, parentNom:string, parentRelation:?string}>
     */
    public function parentOptions(User $user): Collection
    {
        return Student::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            // Active-context centre, same narrowing the cheque list itself
            // applies above — without it a single-centre context still offered
            // every centre's parents in the "Source: Parents" picker.
            ->tap(function ($q): void {
                $id = $this->context->etablissementId();
                if ($id !== null) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
                }
            })
            ->whereNotNull('parent_nom')
            ->where('parent_nom', '!=', '')
            ->orderBy('parent_nom')
            ->get()
            ->map(fn (Student $s): array => [
                'id' => $s->id,
                'studentNom' => $s->nomComplet(),
                'parentNom' => $s->parent_nom,
                'parentRelation' => $s->parent_relation,
            ]);
    }
}
