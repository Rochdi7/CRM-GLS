<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the "Validation de transfert" list — a personal inbox/
 * outbox: every transfer where one of the VIEWER's own tills is either end
 * (source or destination), not just transfers they sent. Each row's "Type
 * de transaction" is relative to the viewer (Réception when money is coming
 * into one of their tills, Transfert when it's leaving one), and
 * Expéditeur/Destinataire show the owning EMPLOYEE's name
 * (Caisse::responsable(), e.g. "Rochdi Karouali") rather than the raw
 * caisse label (which is the same employee name).
 */
final class GetCaisseTransfersList
{
    public const DEFAULT_PER_PAGE = 15;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public const TYPE_ENVOYE = 'envoye';

    public const TYPE_RECU = 'recu';

    /**
     * @return array{data: LengthAwarePaginator, montantTotal: string}
     */
    public function __invoke(
        User $user,
        string $search = '',
        string $statutFilter = CaisseTransfer::STATUT_EN_ATTENTE,
        string $typeFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): array {
        $myCaisseIds = $user->employee?->caisses()->pluck('id')->all() ?? [];

        $base = CaisseTransfer::query()
            ->where(function (Builder $q) use ($user): void {
                $q->whereHas('caisseSource', function (Builder $sq) use ($user): void {
                    $this->centerAccess->scopeAccessibleCenters($sq, $user);
                    $this->scopeToActiveCenter($sq);
                })->orWhereHas('caisseDestination', function (Builder $dq) use ($user): void {
                    $this->centerAccess->scopeAccessibleCenters($dq, $user);
                    $this->scopeToActiveCenter($dq);
                });
            })
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when(
                $typeFilter === self::TYPE_ENVOYE,
                fn ($q) => $q->whereIn('caisse_source_id', $myCaisseIds),
            )
            ->when(
                $typeFilter === self::TYPE_RECU,
                fn ($q) => $q->whereIn('caisse_destination_id', $myCaisseIds),
            )
            ->when($search !== '', function ($q) use ($search): void {
                $term = "%{$search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'ilike', $term)->orWhere('note', 'ilike', $term);
                });
            })
            ->latest();

        // Total over the WHOLE filtered set, before pagination. The page used
        // to sum `transfers.data` in React, so the figure was the visible
        // page's subtotal: it changed on every page click while the filters
        // were unchanged (27/08/2026). Mirrors the montantTotal its three
        // sibling finance queries already return (GetEncaissementsList,
        // GetChequesList, GetDepensesList).
        $montantTotal = (clone $base)->sum('montant');

        $transfers = $base
            ->with(['caisseSource.responsable', 'caisseDestination.responsable', 'requestedBy', 'validatedBy'])
            ->paginate($perPage)
            ->withQueryString();

        $myEmployeeId = $user->employee?->id;

        $transfers->through(function (CaisseTransfer $t) use ($myCaisseIds, $myEmployeeId): array {
            $isReception = in_array($t->caisse_destination_id, $myCaisseIds, true)
                && ! in_array($t->caisse_source_id, $myCaisseIds, true);

            return [
                'id' => $t->id,
                'reference' => $t->reference,
                'expediteur' => $t->caisseSource?->responsable?->nomComplet() ?? $t->caisseSource?->nom,
                'destinataire' => $t->caisseDestination?->responsable?->nomComplet() ?? $t->caisseDestination?->nom,
                'caisseSourceId' => $t->caisse_source_id,
                'caisseDestinationId' => $t->caisse_destination_id,
                'typeTransaction' => $isReception ? 'Réception' : 'Transfert',
                'montant' => number_format((float) $t->montant, 2, '.', ''),
                'dateTransfert' => $t->date_transfert?->toDateTimeString(),
                'statut' => $t->statut,
                'requestedBy' => $t->requestedBy?->nomComplet(),
                'requestedById' => $t->requested_by,
                'validatedBy' => $t->validatedBy?->nomComplet(),
                'note' => $t->note,
                'isPending' => $t->statut === CaisseTransfer::STATUT_EN_ATTENTE,
            // RECIPIENT-ONLY validation: the row is actionable only for the
            // employee whose OWN till is the destination (and never for the
            // requester). Computed here from the same facts the policy and
            // ValiderTransfertCaisse use, so the button and the server can
            // never disagree. UI convenience only - the policy still gates.
            'canValidate' => $t->statut === CaisseTransfer::STATUT_EN_ATTENTE
                && in_array($t->caisse_destination_id, $myCaisseIds, true)
                && $t->requested_by !== $myEmployeeId,
                'showUrl' => route('backoffice.caisse-transfers.show', $t),
            ];
        });

        return [
            'data' => $transfers,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
        ];
    }

    /**
     * @return Collection<string, int>
     */
    public function statutCounts(User $user): Collection
    {
        return CaisseTransfer::query()
            ->where(function (Builder $q) use ($user): void {
                $q->whereHas('caisseSource', function (Builder $sq) use ($user): void {
                    $this->centerAccess->scopeAccessibleCenters($sq, $user);
                    $this->scopeToActiveCenter($sq);
                })->orWhereHas('caisseDestination', function (Builder $dq) use ($user): void {
                    $this->centerAccess->scopeAccessibleCenters($dq, $user);
                    $this->scopeToActiveCenter($dq);
                });
            })
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');
    }

    /**
     * @return Collection<int, array{id:int, nom:string, solde:string}>
     */
    public function caisseOptions(User $user): Collection
    {
        return Caisse::query()
            // Cash accounts only — a transfer never targets a centre's
            // TPE/Chèque/Virement account (DemanderTransfertCaisse refuses
            // it server-side too).
            ->whereIn('type', Caisse::TYPES_ESPECES)
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get()
            ->map(fn (Caisse $c): array => [
                'id' => $c->id,
                'nom' => $c->nom,
                'solde' => number_format((float) $c->solde, 2, '.', ''),
            ]);
    }

    private function scopeToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
