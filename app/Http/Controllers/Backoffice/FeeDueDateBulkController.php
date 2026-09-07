<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Registrations\Actions\ModifierEcheancesFraisEnMasse;
use App\Domain\Registrations\Queries\GetGroupFeeDueDates;
use App\Http\Controllers\Backoffice\Concerns\RedirectsPreservingFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Inscriptions\BulkUpdateFeeDueDatesRequest;
use App\Models\Inscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Échéances en masse » — outil de saisie : choisir un groupe, choisir un
 * frais, cocher les étudiants qui partagent la même date, l'appliquer d'un
 * coup.
 *
 * Volontairement ABSENT de la barre latérale (demande métier du
 * 07/09/2026) : c'est un outil ponctuel, atteint par son lien direct
 * `/backoffice/bulk-echeance`, pas une rubrique de navigation. Il n'est
 * donc pas déclaré dans `resources/js/Config/backofficeNavigation.ts` — ce
 * qui ne change rien à sa protection, l'accès étant décidé par la
 * permission côté serveur, jamais par la présence d'une entrée de menu
 * (§5 : le prop client n'est qu'un confort d'interface).
 *
 * Le droit `fee-due-dates.bulk-update` est accordé à TOUS les rôles
 * (`PermissionRegistry::defaultForEveryRole()`) : l'écran ne déplace aucun
 * argent, il ne réécrit qu'une date de rappel sur des frais que son porteur
 * voit déjà. La portée reste celle des « Centres affectés » + du contexte
 * actif, appliquée par le read-model ET revérifiée à l'écriture par
 * ModifierEcheancesFraisEnMasse.
 */
final class FeeDueDateBulkController extends Controller
{
    use RedirectsPreservingFilters;

    public function index(Request $request, GetGroupFeeDueDates $query): Response
    {
        abort_unless($request->user()->can('fee-due-dates.bulk-update'), 403);

        $groupFilter = (string) $request->string('groupFilter');
        $fraisFilter = (string) $request->string('fraisFilter');
        $statutFilter = (string) $request->string('statutFilter', Inscription::STATUT_ACTIVE);

        if ($statutFilter !== '' && ! in_array($statutFilter, Inscription::STATUTS, true)) {
            $statutFilter = Inscription::STATUT_ACTIVE;
        }

        $groupId = $groupFilter !== '' ? (int) $groupFilter : null;
        $fraisId = $fraisFilter !== '' ? (int) $fraisFilter : null;

        $user = $request->user();

        // Le catalogue de frais dépend du groupe choisi : tant qu'aucun
        // groupe n'est sélectionné, la liste est vide plutôt que remplie de
        // frais qu'aucun étudiant du groupe ne porte.
        $fraisOptions = $query->fraisOptionsForGroup($user, $groupId);

        // Un frais resté sélectionné après un changement de groupe ne
        // s'applique plus : on le laisse tomber au lieu d'afficher un
        // tableau vide sans explication.
        if ($fraisId !== null && ! in_array($fraisId, array_column($fraisOptions, 'value'), true)) {
            $fraisId = null;
            $fraisFilter = '';
        }

        return Inertia::render('Backoffice/EcheancesEnMasse/Index', [
            'lignes' => $query($user, $groupId, $fraisId, $statutFilter),
            'filters' => [
                'groupFilter' => $groupFilter,
                'fraisFilter' => $fraisFilter,
                'statutFilter' => $statutFilter,
            ],
            'groupOptions' => $query->groupOptions($user),
            'fraisOptions' => $fraisOptions,
            'statuts' => $query->statutOptions(),
        ]);
    }

    public function update(
        BulkUpdateFeeDueDatesRequest $request,
        ModifierEcheancesFraisEnMasse $action,
    ): RedirectResponse {
        abort_unless($request->user()->can('fee-due-dates.bulk-update'), 403);

        $modifiees = $action(
            $request->user(),
            $request->validated('fee_ids'),
            (string) $request->validated('date_echeance'),
        );

        // Les filtres survivent à l'écriture (§5) : l'opérateur enchaîne
        // plusieurs frais du MÊME groupe, il ne doit pas retrouver un écran
        // vide après chaque application.
        return $this->backToListPreservingFilters($request, 'backoffice.bulk-echeance.index')
            ->with('success', trans_choice(
                '{0}No due date needed changing.|{1}:count due date updated.|[2,*]:count due dates updated.',
                $modifiees,
                ['count' => $modifiees],
            ));
    }
}
