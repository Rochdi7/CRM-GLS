<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payroll\Actions\CalculerPaiementProfParSeance;
use App\Domain\Payroll\Queries\GetPaiementProfCalcul;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Group;
use App\Models\TypeDepense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Calcul paiement prof » — dérive, depuis les appels DÉJÀ SAISIS, le
 * montant dû à UN enseignant pour UN groupe sur UN mois de groupe, selon le
 * mode de paie configuré sur sa fiche (onglet « Paiement prof »).
 *
 * ⚠ **Cet écran n'écrit RIEN et ne touche AUCUNE caisse.** Il propose un
 * montant ; le paiement reste une dépense « Paiement prof » ordinaire,
 * enregistrée depuis le modal habituel (`prefill_*`), avec tous ses
 * invariants (§11). Un calcul n'est pas un paiement.
 *
 * `groupOptions()` est un endpoint JSON appelé par le modal dès qu'un groupe
 * est choisi : il rend les mois, les enseignants (avec leur mode et un
 * éventuel problème de configuration NOMMÉ) et les séances sans prof à
 * corriger — de sorte que le modal ne propose jamais un calcul que le
 * serveur refuserait ensuite.
 */
final class PaiementProfController extends Controller
{
    use AssertsContextScope;

    public function index(Request $request, GetPaiementProfCalcul $query): Response
    {
        $user = $request->user();

        abort_unless($user->can('prof-payments.calculate'), 403);

        $groupFilter = (string) $request->string('groupFilter');
        $enseignantFilter = (string) $request->string('enseignantFilter');
        $mois = (string) $request->string('mois');
        $heuresFilter = (string) $request->string('heures');

        $group = null;
        $enseignant = null;
        $calcul = null;

        if ($groupFilter !== '') {
            $group = Group::query()->with('enseignant')->find((int) $groupFilter);

            if ($group !== null) {
                // L'id vient de la query string, donc du navigateur : la
                // portée se rejoue là où il entre (§11).
                $this->assertGroupInContext($request, $group, 'groupFilter');
            }
        }

        if ($group !== null && $enseignantFilter !== '') {
            $enseignant = Employee::query()->find((int) $enseignantFilter);
        }

        if ($group !== null && $enseignant !== null && $mois !== '') {
            $calcul = $query(
                group: $group,
                enseignant: $enseignant,
                mois: $mois,
                heures: $heuresFilter !== '' ? (float) $heuresFilter : null,
            );
        }

        return Inertia::render('Backoffice/PaiementProf/Index', [
            'calcul' => $calcul,
            'filters' => [
                'groupFilter' => $groupFilter,
                'enseignantFilter' => $enseignantFilter,
                'mois' => $mois,
                'heures' => $heuresFilter,
                // Aide de saisie côté écran uniquement (durée d'une séance,
                // « 2h30 ») : le serveur ne s'en sert pas, mais la page
                // initialise son état depuis `filters`.
                'dureeSeance' => '',
            ],
            'groupOptions' => fn (): array => $query->groupOptions($user),
            'seancesMaxParMois' => CalculerPaiementProfParSeance::SEANCES_MAX_PAR_MOIS,
            'modes' => Employee::MODES_PAIEMENT_PROF,
            'paiementProfTypeId' => fn (): ?int => TypeDepense::query()
                ->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)
                ->value('id'),
            'canCreateDepense' => $user->can('expenses.create'),
        ]);
    }

    /**
     * Options dépendant du groupe choisi — mois, enseignants, séances sans
     * prof. Appelé par le modal en JSON.
     */
    public function groupOptions(Request $request, Group $group, GetPaiementProfCalcul $query): JsonResponse
    {
        abort_unless($request->user()->can('prof-payments.calculate'), 403);

        $this->assertGroupInContext($request, $group, 'group');

        $mois = (string) $request->string('mois');

        return response()->json($query->optionsPourGroupe($group, $mois !== '' ? $mois : null));
    }
}
