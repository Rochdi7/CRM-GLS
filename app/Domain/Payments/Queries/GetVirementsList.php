<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Domain\Payments\Actions\ValiderVirement;
use App\Models\User;
use App\Models\Virement;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model de la page « Virements » (07/10/2026) — mêmes conventions de
 * portée (centres affectés + centre actif) que les autres listes finance.
 *
 * Deux totaux, deux périmètres :
 *  - `montantTotal` chapeaute les lignes FILTRÉES (même règle que partout :
 *    un total se calcule sur les mêmes lignes que celles qu'il chapeaute) ;
 *  - `enAttente` (nombre + montant) est la boîte de réception du comptable
 *    sur le centre actif, SANS les filtres — il dit ce qu'il reste à
 *    traiter, pas ce que la recherche courante montre. La page des
 *    paiements l'affiche à côté de « Montant total » : de l'argent déclaré
 *    mais pas encore vérifié n'est PAS encaissé, il est compté à part.
 */
final class GetVirementsList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return array{data: LengthAwarePaginator, montantTotal: string, enAttente: array{count:int, montant:string}}
     */
    public function __invoke(
        User $user,
        string $search = '',
        string $statutFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        bool $dateFilterEngaged = false,
    ): array {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $base = $this->scoped($user)
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('reference', 'ilike', "%{$search}%")
                ->orWhere('reference_virement', 'ilike', "%{$search}%")
                ->orWhere('nom_payeur', 'ilike', "%{$search}%")
                ->orWhereHas('student', fn ($s) => $s
                    ->where('nom', 'ilike', "%{$search}%")
                    ->orWhere('prenom', 'ilike', "%{$search}%")
                    ->orWhere('reference', 'ilike', "%{$search}%"))))
            ->when($statutFilter !== '', fn (Builder $q) => $q->where('statut', $statutFilter))
            ->when($dateFrom !== '', fn (Builder $q) => $q->whereDate('date_operation', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $q) => $q->whereDate('date_operation', '<=', $dateTo))
            // Fenêtre de l'année active par DÉFAUT sur la date d'opération
            // (un virement n'a pas de FK d'année, §11), qu'un filtre de date
            // explicite remplace. ⚠ Une demande EN ATTENTE ne se cache
            // jamais derrière la fenêtre de l'année : c'est la boîte de
            // réception du comptable (même exception que les transferts).
            ->when(
                ! $dateFilterEngaged
                    && $statutFilter !== Virement::STATUT_EN_ATTENTE
                    && $this->context->anneeDateRange() !== null,
                fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereBetween('date_operation', $this->context->anneeDateRange())
                    ->orWhere('statut', Virement::STATUT_EN_ATTENTE)),
            )
            ->latest('id');

        $montantTotal = (clone $base)->sum('montant');

        $virements = (clone $base)
            ->with([
                'student', 'inscription.group', 'fee.inscription', 'encaissement',
                'demandePar', 'decidePar', 'media',
            ])
            ->paginate($perPage)
            ->withQueryString();

        $currentEmployeeId = $user->employee?->id;

        $virements->through(fn (Virement $v): array => $this->row($v, $currentEmployeeId));

        return [
            'data' => $virements,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
            'enAttente' => $this->enAttente($user),
        ];
    }

    /**
     * Boîte de réception : virements « En attente de vérification » dans la
     * portée centre de l'utilisateur, sans aucun filtre de page.
     *
     * @return array{count:int, montant:string}
     */
    public function enAttente(User $user): array
    {
        $row = $this->scoped($user)
            ->enAttente()
            ->selectRaw('count(*) as nb, coalesce(sum(montant), 0) as total')
            ->first();

        return [
            'count' => (int) ($row?->nb ?? 0),
            'montant' => number_format((float) ($row?->total ?? 0), 2, '.', ''),
        ];
    }

    private function scoped(User $user): Builder
    {
        return Virement::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                $id = $this->context->etablissementId();
                if ($id !== null) {
                    $q->where('etablissement_id', $id);
                }
            });
    }

    /** @return array<string, mixed> */
    private function row(Virement $v, ?int $currentEmployeeId): array
    {
        $fee = $v->fee;

        return [
            'id' => $v->id,
            'reference' => $v->reference,
            'dateOperation' => $v->date_operation?->toDateString(),
            'demandeLe' => $v->created_at?->toDateTimeString(),
            'studentId' => $v->student_id,
            'studentNom' => $v->student?->nomComplet(),
            'studentReference' => $v->student?->reference,
            'telephone' => $v->student?->telephone,
            'inscriptionId' => $v->inscription_id,
            'inscriptionReference' => $v->inscription?->reference,
            'groupeNom' => $v->inscription?->group?->nom,
            'feeNom' => $fee?->nom,
            'feeEcheance' => $fee?->date_echeance?->toDateString(),
            'montant' => number_format((float) $v->montant, 2, '.', ''),
            'nomPayeur' => $v->nom_payeur,
            'referenceVirement' => $v->reference_virement,
            'justificatifUrl' => $v->getFirstMediaUrl(Virement::MEDIA_JUSTIFICATIF) ?: null,
            'note' => $v->note ?? '',
            'statut' => $v->statut,
            'motifRefus' => $v->motif_refus,
            'demandeParNom' => $v->demandePar?->nomComplet(),
            'decideParNom' => $v->decidePar?->nomComplet(),
            'decideLe' => $v->decide_le?->toDateTimeString(),
            'encaissementId' => $v->encaissement_id,
            'encaissementReference' => $v->encaissement?->reference,
            // Celui qui a déclaré le virement ne le valide pas (contrôle à
            // deux personnes, ValiderVirement) — porté à l'écran, jamais
            // redérivé par le composant (§5).
            'autoValidation' => $currentEmployeeId !== null && $v->demande_par_id === $currentEmployeeId,
            // Pourquoi la validation serait refusée (frais masqué, dossier
            // clos, reste insuffisant) — la MÊME règle que l'action, pour que
            // le comptable la lise AVANT de cliquer. Null = validable.
            'validationBlocker' => $v->isEnAttente() ? ValiderVirement::blocage($v, $fee) : null,
        ];
    }
}
