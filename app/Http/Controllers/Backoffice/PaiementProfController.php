<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payroll\Actions\CalculerPaiementProfHebdomadaire;
use App\Domain\Payroll\Queries\GetPaiementProfCalcul;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\TypeDepense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Calcul paiement prof » — dérive, depuis les appels DÉJÀ SAISIS, le
 * montant dû à un enseignant pour un groupe sur une période.
 *
 * Portage de la logique du portail GLS (`ProfPaymentCalculationService`),
 * avec UNE différence de fond : le portail lisait un instantané importé
 * (Excel / API) et devait donc le stocker dans des tables d'import. Ici la
 * donnée nous appartient (`presences` → `seances`), donc **aucune table
 * d'import n'existe** : l'écran calcule à la lecture, à partir de la source
 * de vérité. Recalculer après un appel corrigé donne aussitôt le bon montant,
 * sans ré-importer quoi que ce soit.
 *
 * ⚠ **Cet écran n'écrit RIEN et ne touche AUCUNE caisse.** Il propose un
 * montant. Le paiement reste une dépense « Paiement prof » ordinaire,
 * enregistrée par l'utilisateur depuis le modal habituel — avec tous les
 * invariants monétaires que cela emporte (validation, garde de solde,
 * `CaisseLedger`, journal, §11). Le passage de l'un à l'autre se fait par la
 * query string (`prefill_*`), exactement comme le lien « rembourser » d'un
 * chèque rejeté : rien n'est créé automatiquement, l'opérateur relit et
 * soumet.
 *
 * Volontairement absent de la barre latérale, comme « Échéances en masse » :
 * c'est un outil, pas une rubrique — ce qui ne change rien à sa protection,
 * décidée par la permission côté serveur (§5, `prof-payments.calculate`).
 */
final class PaiementProfController extends Controller
{
    use AssertsContextScope;

    public function index(Request $request, GetPaiementProfCalcul $query): Response
    {
        $user = $request->user();

        abort_unless($user->can('prof-payments.calculate'), 403);

        $groupFilter = (string) $request->string('groupFilter');
        $dateDebut = (string) $request->string('dateDebut');
        $dateFin = (string) $request->string('dateFin');
        $montantFilter = (string) $request->string('montantParEtudiant');
        $seuilFilter = (string) $request->string('seuil');

        $groupId = $groupFilter !== '' ? (int) $groupFilter : null;

        $calcul = null;
        $group = null;

        if ($groupId !== null) {
            $group = Group::query()->with('enseignant')->find($groupId);

            if ($group !== null) {
                // Portée revérifiée ici et pas seulement dans le read-model :
                // l'id vient de la query string, donc du navigateur (§11 —
                // une garde de lecture se rejoue là où l'id entre).
                $this->assertGroupInContext($request, $group, 'groupFilter');
            }
        }

        if ($group !== null && $dateDebut !== '' && $dateFin !== '') {
            $calcul = $query(
                group: $group,
                dateDebut: $dateDebut,
                dateFin: $dateFin,
                montantParEtudiant: $montantFilter !== '' ? (float) $montantFilter : null,
                seuil: $seuilFilter !== '' ? (int) $seuilFilter : null,
            );
        }

        return Inertia::render('Backoffice/PaiementProf/Index', [
            'calcul' => $calcul,
            'filters' => [
                'groupFilter' => $groupFilter,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'montantParEtudiant' => $montantFilter,
                'seuil' => $seuilFilter,
            ],
            // Servi en CLOSURE : la liste des groupes n'a pas à être
            // recalculée par un rechargement partiel qui ne demande que le
            // calcul (§17 perf).
            'groupOptions' => fn (): array => $query->groupOptions($user),
            'seuilParDefaut' => CalculerPaiementProfHebdomadaire::SEUIL_PAR_DEFAUT,
            'pourcentageHebdo' => CalculerPaiementProfHebdomadaire::POURCENTAGE_HEBDO_PAR_DEFAUT,
            // Permet au bouton « Enregistrer la dépense » de pré-remplir le
            // bon type sans que la page ait à le deviner.
            'paiementProfTypeId' => fn (): ?int => TypeDepense::query()
                ->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)
                ->value('id'),
            // Le calcul propose un montant ; encore faut-il avoir le droit
            // d'enregistrer la dépense qui en découle. Confort d'interface
            // seulement — le vrai contrôle est dans DepenseController (§5).
            'canCreateDepense' => $user->can('expenses.create'),
        ]);
    }
}
