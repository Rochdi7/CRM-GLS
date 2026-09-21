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

    /**
     * Pseudo-statut d'AFFICHAGE : un chèque rendu à son propriétaire.
     * Il n'existe pas dans `cheques.statut` (qui décrit le parcours
     * BANCAIRE) — il est dérivé de retourne_le, et n'a donc coûté ni
     * colonne, ni migration, ni patch de production.
     */
    public const STATUT_RESTITUE = 'Restitué';

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
        bool $dateFilterEngaged = false,
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
            // « Restitué » n'est pas une valeur de la colonne : c'est le
            // statut AFFICHÉ, porté par retourne_le (voir statutAffiche
            // plus bas). Le filtre doit donc le comprendre, sinon l'écran
            // propose un badge qu'aucun filtre ne retrouve — et, dans
            // l'autre sens, « En possession » ne doit plus ramener les
            // chèques rendus, qui ne s'affichent plus ainsi.
            ->when($statutFilter === self::STATUT_RESTITUE, fn ($q) => $q->whereNotNull('retourne_le'))
            ->when(
                $statutFilter !== '' && $statutFilter !== self::STATUT_RESTITUE,
                fn ($q) => $q->where('statut', $statutFilter)->whereNull('retourne_le'),
            )
            ->when($dateEcheanceFrom !== '', fn ($q) => $q->whereDate('date_echeance', '>=', $dateEcheanceFrom))
            ->when($dateEcheanceTo !== '', fn ($q) => $q->whereDate('date_echeance', '<=', $dateEcheanceTo))
            // Year switcher: a cheque follows its échéance into the year it
            // falls in (the page's own date filters are échéance-based); the
            // active year is only the DEFAULT window — an explicit échéance
            // filter takes over. A cheque with no échéance stays visible.
            //
            // ⚠ Decided from $dateFilterEngaged, not from the two edges being
            // empty (audit 07/09/2026, H-1): keyed on the edges, the window
            // re-armed the moment the LAST échéance was cleared, so clearing
            // a filter REMOVED rows instead of widening (§5). Harmless today
            // — every chèque in production has a NULL échéance and is kept by
            // the orWhereNull below — but the shape is the bug, and the first
            // dated chèque would hit it.
            ->when(
                ! $dateFilterEngaged && $this->context->anneeDateRange() !== null,
                fn ($q) => $q->where(fn ($sub) => $sub
                    ->whereBetween('date_echeance', $this->context->anneeDateRange())
                    ->orWhereNull('date_echeance')),
            )
            ->latest();

        // Total over every chèque matching the current filters (not just the
        // page shown) — same convention as GetDepensesList/GetEncaissementsList.
        //
        // ⚠ Les chèques RESTITUÉS en sont exclus (19/09/2026). « Montant
        // total » chapeaute une liste de papiers que l'école DÉTIENT ; un
        // chèque rendu à son propriétaire n'est plus là, et le compter
        // gonfle ce que l'école croit avoir en garantie — l'écran se
        // contredirait lui-même, avec une ligne badgée « Restitué » qui pèse
        // quand même dans le total au-dessus (§11 : un total se calcule sur
        // les MÊMES lignes que celles qu'il chapeaute).
        //
        // EXCEPTION : quand l'utilisateur DEMANDE explicitement les
        // restitués (statutFilter = « Restitué »), le total porte sur eux,
        // sinon la page afficherait des lignes et un total à 0,00.
        $montantTotal = (clone $base)
            ->when(
                $statutFilter !== self::STATUT_RESTITUE,
                fn ($q) => $q->whereNull('retourne_le'),
            )
            ->sum('montant');

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
            // Computed from the ALREADY eager-loaded relation, not via
            // Cheque::montantRestant() → montantUtilise(), which fires its
            // own SUM per row — one extra query per line, up to 100 on a
            // full page (audit 07/09/2026, M-14; §17 forbids a per-row money
            // accessor in a read model). The accessor stays as-is: the money
            // ACTIONS need it to re-read a freshly locked row.
            'reste' => number_format(
                round(max(0.0, (float) $cheque->montant - (float) $cheque->encaissements->sum('montant')), 2),
                2, '.', ''
            ),
            'banque' => $cheque->banque,
            'dateReception' => $cheque->date_reception?->toDateString(),
            'type' => $cheque->type,
            'dateEcheance' => $cheque->date_echeance?->toDateString(),
            'statut' => $cheque->statut,
            // ⚠ Ce que l'utilisateur LIT dans la colonne Statut n'est pas
            // « où en est le parcours bancaire » mais « où est le chèque
            // maintenant ». Un chèque de garantie rendu au client n'est plus
            // « En possession » — l'afficher ainsi fait mentir l'écran
            // (signalé le 19/09/2026). La colonne `statut` garde sa valeur
            // stockée, qui décrit le parcours BANCAIRE et n'a pas de valeur
            // « Restitué » ; c'est l'AFFICHAGE qui dit la vérité physique,
            // dérivé de retourne_le. Aucune colonne, aucun statut ajouté.
            'statutAffiche' => $cheque->estRetourne()
                ? 'Restitué'
                : $cheque->statut,
            'note' => $cheque->note ?? '',
            'agentNom' => $cheque->agent?->nomComplet(),
            'retourneLe' => $cheque->retourne_le?->toDateTimeString(),
            'retourneParNom' => $cheque->retournePar?->nomComplet(),
            // Peut-on rendre CE chèque de garantie à son propriétaire ?
            // Les mêmes bornes que RestituerChequeGarantie vérifie sous
            // verrou — portées à l'écran, jamais redérivées par le composant
            // (§5) : une page qui recopie la règle finit par proposer un
            // bouton que le serveur refuse. Le « reste » est calculé sur la
            // relation DÉJÀ chargée, pas via montantUtilise(), qui tirerait
            // son propre SUM par ligne (§17).
            'restituable' => $cheque->type === Cheque::TYPE_GARANTIE
                && $cheque->statut === Cheque::STATUT_EN_POSSESSION
                && ! $cheque->estRetourne()
                && round((float) $cheque->encaissements->sum('montant'), 2) <= 0.0,
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
