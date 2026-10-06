<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Expenses\Queries\GetDepensesList;
use App\Domain\Payroll\Actions\CalculerPaiementProfParPaliers;
use App\Domain\Payroll\Queries\GetPaiementProfCalcul;
use App\Domain\Payroll\Support\CotisationCnss;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Backoffice\Concerns\ResolvesPaiementProfCalcul;
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
    use ResolvesPaiementProfCalcul;

    public function index(Request $request, GetPaiementProfCalcul $query, GetDepensesList $depenses): Response
    {
        $user = $request->user();

        abort_unless($user->can('prof-payments.calculate'), 403);

        ['calcul' => $calcul, 'filters' => $filters] = $this->resolvePaiementProfCalcul($request, $query);

        return Inertia::render('Backoffice/PaiementProf/Index', [
            'calcul' => $calcul,
            'filters' => $filters,
            'groupOptions' => fn (): array => $query->groupOptions($user),
            'paliersPaie' => CalculerPaiementProfParPaliers::PALIERS,
            // Cotisation CNSS retenue — la constante, jamais recopiée en React.
            'cnssMontant' => CotisationCnss::MONTANT,
            'modes' => Employee::MODES_PAIEMENT_PROF,
            'paiementProfTypeId' => fn (): ?int => TypeDepense::query()
                ->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)
                ->value('id'),
            'canCreateDepense' => $user->can('expenses.create'),
            // Les paiements DÉJÀ enregistrés — sans cette liste l'écran
            // s'ouvrait sur « Aucun calcul » alors que des « Paiement prof »
            // existaient. Même read-model que l'onglet Paiements prof des
            // Dépenses (portée centre/année, statuts, total Approuvée
            // seulement) : jamais une seconde requête qui finirait par
            // diverger. Ce sont des dépenses : `expenses.view` les ouvre.
            'paiementsProf' => fn (): ?array => $user->can('expenses.view')
                ? $depenses(user: $user, scope: GetDepensesList::SCOPE_PAIEMENT_PROF)
                : null,
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
        $debut = (string) $request->string('debut');
        $fin = (string) $request->string('fin');

        return response()->json($query->optionsPourGroupe(
            $group,
            $mois !== '' ? $mois : null,
            $debut !== '' ? $debut : null,
            $fin !== '' ? $fin : null,
        ));
    }
}
