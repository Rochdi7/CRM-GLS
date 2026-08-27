<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Actions\ReaffecterEncaissements;
use App\Domain\Payments\Queries\GetReallocatablePayments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Encaissements\ReaffecterEncaissementsRequest;
use App\Models\AnneeScolaire;
use App\Models\Group;
use App\Models\InscriptionFee;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Déplacer des encaissements » — the bulk correction screen for money booked
 * against the wrong group/année (super-admin only, payments.reallocate).
 *
 * Its list is deliberately NOT year-scoped (GetReallocatablePayments): the
 * rows to fix are precisely the ones the active year hides. The centre still
 * is. The move itself never touches caisses.solde or date_paiement — see
 * ReaffecterEncaissements.
 */
final class EncaissementReallocationController extends Controller
{
    public function index(Request $request, GetReallocatablePayments $query, CurrentContext $context): Response
    {
        $groupId = $request->integer('group_id') ?: null;
        $anneeId = $request->integer('annee_id') ?: null;

        $result = $query(
            search: (string) $request->string('search'),
            groupId: $groupId,
            fraisFilter: (string) $request->string('frais'),
            anneeId: $anneeId,
        );

        return Inertia::render('Backoffice/Encaissements/Reallocate', [
            'paiements' => $result['data'],
            'montantTotal' => $result['montantTotal'],
            'filters' => [
                'search' => (string) $request->string('search'),
                'group_id' => $groupId,
                'frais' => (string) $request->string('frais'),
                'annee_id' => $anneeId,
            ],
            'groupes' => fn () => Group::query()
                ->when($context->etablissementId(), fn ($q, $id) => $q->where('etablissement_id', $id))
                ->orderBy('nom')
                ->get(['id', 'nom', 'annee_scolaire_id'])
                ->map(fn (Group $g): array => [
                    'value' => $g->id,
                    'label' => $g->nom,
                    'anneeId' => $g->annee_scolaire_id,
                ]),
            'annees' => fn () => AnneeScolaire::orderBy('date_debut')->get(['id', 'nom'])
                ->map(fn (AnneeScolaire $a): array => ['value' => $a->id, 'label' => $a->nom]),
            // Every fee name reachable under the CURRENT groupe/année filter —
            // not just the ones on the visible page, so « Frais de Juillet »
            // can be picked before its rows are on screen. That is the whole
            // point of the filter: select one fee across the group, then move
            // it in one go.
            'fraisOptions' => fn () => InscriptionFee::query()
                ->whereHas('encaissements')
                ->whereHas('inscription', function ($i) use ($context, $groupId, $anneeId): void {
                    $i->when($context->etablissementId(), fn ($q, $id) => $q->where('etablissement_id', $id))
                        ->when($groupId, fn ($q, $id) => $q->where('group_id', $id))
                        ->when($anneeId, fn ($q, $id) => $q->where('annee_scolaire_id', $id));
                })
                ->distinct()
                ->orderBy('nom')
                ->pluck('nom')
                ->map(fn (string $nom): array => ['value' => $nom, 'label' => $nom])
                ->values(),
        ]);
    }

    public function store(ReaffecterEncaissementsRequest $request, ReaffecterEncaissements $action): RedirectResponse
    {
        $data = $request->validated();
        $cible = Group::findOrFail((int) $data['group_id']);

        $resultat = $action->handle(array_map('intval', $data['encaissement_ids']), $cible);

        $message = __(':count payment(s) moved for :montant MAD.', [
            'count' => $resultat['deplaces'],
            'montant' => $resultat['montant'],
        ]);

        if ($resultat['fraisCrees'] > 0) {
            $message .= ' '.__(':count fee line(s) recreated on the target registration.', [
                'count' => $resultat['fraisCrees'],
            ]);
        }

        if ($resultat['avances'] > 0) {
            $message .= ' '.__(':count left as unallocated advances (no matching fee).', [
                'count' => $resultat['avances'],
            ]);
        }

        // Naming them matters: "3 avances" alone leaves the operator hunting.
        if ($resultat['sansInscription'] !== []) {
            $message .= ' '.__('Not enrolled in this group: :names.', [
                'names' => implode(', ', $resultat['sansInscription']),
            ]);
        }

        return back()->with('success', $message);
    }

}
