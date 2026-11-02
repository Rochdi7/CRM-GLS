<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Domain\Payments\Support\ChequeOrigine;
use App\Models\Cheque;
use App\Models\DemandeRemboursement;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model des demandes de remboursement (02/11/2026), affichées dans
 * l'onglet Remboursements de « Gestion des dépenses ».
 *
 * Portée : centres affectés de l'utilisateur, resserrée au centre actif —
 * sur la colonne `etablissement_id` de la DEMANDE (le centre du
 * remboursement, §11). Aucune fenêtre d'année : une demande en attente est
 * une boîte de réception et ne se cache jamais derrière le sélecteur
 * d'année (même règle que les transferts et les virements en attente).
 *
 * Ce que le composant affiche sur la décision vient d'ICI, jamais redérivé :
 * `canDecide` (policy), `chequeRejete` (ChequeOrigine — la méthode est alors
 * verrouillée sur Espèces, l'approbation le refuserait sinon).
 */
final class GetDemandesRemboursementList
{
    public const DEFAULT_PER_PAGE = 10;

    public const PAGE_NAME = 'pageDemandes';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(User $user, string $search = '', string $statut = '', int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $demandes = $this->scoped($user)
            ->when(in_array($statut, DemandeRemboursement::STATUTS, true), fn ($q) => $q->where('statut', $statut))
            ->when($search !== '', function ($q) use ($search): void {
                $term = "%{$search}%";
                $q->where(fn ($sub) => $sub
                    ->where('reference', 'ilike', $term)
                    ->orWhere('encaissement_reference', 'ilike', $term)
                    ->orWhereHas('beneficiaire', fn ($s) => $s
                        ->where('nom', 'ilike', $term)
                        ->orWhere('prenom', 'ilike', $term)));
            })
            ->with([
                'beneficiaire',
                'encaissement.caisse',
                'encaissement.cheque',
                'demandePar' => fn ($q) => $q->withoutGlobalScopes(),
                'decidePar' => fn ($q) => $q->withoutGlobalScopes(),
                'etablissement',
                'remboursement',
            ])
            // Les demandes à décider d'abord, puis l'historique du plus
            // récent au plus ancien.
            ->orderByRaw('case when statut = ? then 0 else 1 end', [DemandeRemboursement::STATUT_EN_ATTENTE])
            ->latest()
            ->latest('id')
            ->paginate($perPage, ['*'], self::PAGE_NAME)
            ->withQueryString();

        // Chèque d'origine EN LOT, à travers la chaîne applied_from — une
        // requête par niveau, jamais une par ligne (§17).
        $cheques = ChequeOrigine::pour(
            $demandes->getCollection()->pluck('encaissement_id')->filter()->map(fn ($id) => (int) $id)->values()->all(),
        );

        $demandes->through(fn (DemandeRemboursement $d): array => [
            'id' => $d->id,
            'reference' => $d->reference,
            'beneficiaire' => $d->beneficiaire?->nomComplet(),
            'beneficiaireId' => $d->beneficiaire_id,
            'encaissementId' => $d->encaissement_id,
            'encaissementReference' => $d->encaissement_reference,
            'montant' => number_format((float) $d->montant, 2, '.', ''),
            'motif' => $d->motif,
            'note' => $d->note,
            'statut' => $d->statut,
            'methode' => $d->methode,
            'centre' => $d->etablissement?->nom_centre,
            'demandePar' => $d->demandePar?->nomComplet(),
            'demandeLe' => $d->created_at?->format('d/m/Y H:i'),
            'decidePar' => $d->decidePar?->nomComplet(),
            'decideLe' => $d->decide_le?->format('d/m/Y H:i'),
            'motifRefus' => $d->motif_refus,
            'remboursementReference' => $d->remboursement?->reference,
            'chequeRejete' => $d->encaissement_id !== null
                && ($cheques[(int) $d->encaissement_id] ?? null)?->statut === Cheque::STATUT_REJETE
                && $d->encaissement?->caisse?->isCompteMethode() === true,
            'canDecide' => $d->isEnAttente() && $user->can('approve', $d),
        ]);

        return $demandes;
    }

    /**
     * L'argent EN ATTENTE sur le périmètre (centres + centre actif), hors
     * recherche et hors filtre de statut — c'est le badge de l'onglet et le
     * chiffre « en attente » de l'en-tête, qui ne doivent pas varier quand on
     * cherche un nom. Calculé en SQL, jamais réadditionné côté client.
     *
     * @return array{count:int, montant:string}
     */
    public function enAttente(User $user): array
    {
        $base = $this->scoped($user)->enAttente();

        return [
            'count' => (clone $base)->count(),
            'montant' => number_format((float) $base->sum('montant'), 2, '.', ''),
        ];
    }

    /** @return Builder<DemandeRemboursement> */
    private function scoped(User $user): Builder
    {
        $centreId = $this->context->etablissementId();

        return DemandeRemboursement::query()
            ->where(fn (Builder $q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->when($centreId !== null, fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('etablissement_id')
                ->orWhere('etablissement_id', $centreId)));
    }
}
