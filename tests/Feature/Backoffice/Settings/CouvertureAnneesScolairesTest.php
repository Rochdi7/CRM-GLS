<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Settings;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une année scolaire est une FENÊTRE DE LECTURE (§11 « Context scoping ») :
 * une dépense, un remboursement et un chèque n'ont pas de FK d'année, ils
 * sont rattachés à celle dont l'intervalle CONTIENT leur date. Déplacer une
 * borne ne déplace donc pas ces lignes — elle décide si un écran les voit
 * encore.
 *
 * Signalé le 11/09/2026 : en clôturant 2025/2026 au 26/08/2026 pendant que
 * 2026/2027 ouvrait le 27/09/2026, 32 jours n'appartenaient plus à aucune
 * année. Une dépense du 31/08/2026 (140,00 DH « En attente ») restait en
 * base, caisse engagée, mais n'apparaissait dans AUCUN contexte : ni
 * approuvable, ni refusable, ni visible.
 */
final class CouvertureAnneesScolairesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);
    }

    private function annee(string $nom, string $debut, string $fin): AnneeScolaire
    {
        return AnneeScolaire::create([
            'nom' => $nom, 'date_debut' => $debut, 'date_fin' => $fin,
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
    }

    private function depenseLe(string $date): Depense
    {
        $centre = Etablissement::factory()->create();
        $employee = Employee::factory()->create(['etablissement_id' => $centre->id]);
        // §11 : un employé n'a qu'UNE caisse « Caissière », provisionnée par
        // l'observer — on réutilise la sienne au lieu d'en créer une seconde.
        $caisse = $employee->till()->first() ?? Caisse::factory()->create([
            'responsable_employee_id' => $employee->id,
            'etablissement_id' => $centre->id,
            'type' => Caisse::TYPE_CAISSIERE,
        ]);

        return Depense::create([
            'reference' => 'DEP-'.str_pad((string) (Depense::count() + 1), 3, '0', STR_PAD_LEFT),
            'type_depense_id' => TypeDepense::create(['nom' => 'Logistiques '.uniqid()])->id,
            'caisse_id' => $caisse->id,
            'agent_id' => $employee->id,
            'montant' => 140,
            'methode_paiement' => 'Espèces',
            'date_depense' => $date,
            'description' => 'Gaz',
            'statut' => Depense::STATUT_EN_ATTENTE,
        ]);
    }

    /**
     * ⚠ Le geste central (21/09/2026) : DÉPLACER LA FRONTIÈRE entre deux
     * années adjacentes. Les deux ordres possibles étaient refusés —
     * raccourcir l'ancienne ouvre un trou, avancer la suivante chevauche
     * l'ancienne — et aucun écran n'édite les deux bornes ensemble. Le
     * garde-fou interdisait donc exactement le geste légitime qu'il protège.
     *
     * La voisine qui BORDE le trou l'absorbe : elle ne fait que grandir
     * dans un intervalle que personne ne couvre.
     */
    public function test_shrinking_a_year_slides_the_neighbours_boundary_instead_of_refusing(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $suivante = $this->annee('2026/2027', '2026-09-27', '2027-08-31');

        $this->depenseLe('2026-08-31');

        $this->put(route('backoffice.annees-scolaires.update', $ancienne), [
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
        ])->assertSessionHasNoErrors();

        // L'année soumise a bien été raccourcie...
        $this->assertSame('2026-08-26', $ancienne->fresh()->date_fin->toDateString());
        // ...et la voisine a glissé pour reprendre le lendemain : plus un
        // seul jour à découvert, et la dépense du 31/08 reste visible.
        $this->assertSame('2026-08-27', $suivante->fresh()->date_debut->toDateString());
    }

    /**
     * Une année que l'utilisateur n'a PAS éditée vient de changer de dates.
     * Le taire ferait découvrir le décalage des mois plus tard, sur un
     * total qui ne tombe plus juste (§11 « signaler plutôt que masquer »).
     */
    public function test_the_slide_is_announced_and_names_the_year_and_its_new_boundary(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->annee('2026/2027', '2026-09-27', '2027-08-31');
        $this->depenseLe('2026-08-31');

        $response = $this->from(route('backoffice.settings'))
            ->put(route('backoffice.annees-scolaires.update', $ancienne), [
                'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
            ]);

        $response->assertSessionHasNoErrors();

        $message = (string) $response->baseResponse->getSession()->get('warning');

        $this->assertStringContainsString('2026/2027', $message);
        $this->assertStringContainsString('27/08/2026', $message);
    }

    /**
     * Rien n'a glissé ⇒ AUCUNE clé `warning`. Une valeur nulle flashée
     * allumerait quand même la bannière, vide.
     */
    public function test_nothing_is_announced_when_no_boundary_moved(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->annee('2026/2027', '2026-09-01', '2027-08-31');

        $this->put(route('backoffice.annees-scolaires.update', $ancienne), [
            'nom' => '2025/2026 bis', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
        ])->assertSessionHasNoErrors();

        $this->assertNull(session('warning'));
    }

    /**
     * Le glissement et l'année soumise sont écrits dans la MÊME
     * transaction : si l'écriture échoue, la base ne reste pas dans le trou
     * que la règle refuse. Ici l'échec vient du garde-fou « clôture »
     * (seul un super-admin y touche) — vérifié depuis l'autre bout : après
     * un refus, AUCUNE des deux années n'a bougé.
     */
    public function test_a_refused_write_slides_nothing(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $suivante = $this->annee('2026/2027', '2026-09-27', '2027-08-31');
        $this->depenseLe('2026-08-31');

        // Un nom déjà pris : la validation échoue APRÈS que la règle de
        // couverture a calculé son glissement.
        $this->put(route('backoffice.annees-scolaires.update', $ancienne), [
            'nom' => '2026/2027', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
        ])->assertSessionHasErrors('nom');

        $this->assertSame('2026-08-31', $ancienne->fresh()->date_fin->toDateString());
        $this->assertSame('2026-09-27', $suivante->fresh()->date_debut->toDateString());
    }

    /**
     * Le trou qu'AUCUNE voisine ne borde reste refusé : il n'y a rien à
     * faire glisser, et l'argent serait bel et bien perdu de vue.
     */
    public function test_a_gap_with_no_neighbour_to_absorb_it_is_still_refused(): void
    {
        $seule = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->depenseLe('2026-08-31');

        $this->put(route('backoffice.annees-scolaires.update', $seule), [
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
        ])->assertSessionHasErrors('date_fin');

        $this->assertSame('2026-08-31', $seule->fresh()->date_fin->toDateString());
    }

    /**
     * L'intention du 11/09/2026 : « 26 août = ancienne année, 27 août =
     * nouvelle ». Des années CONTIGUËS sont la configuration correcte et ne
     * doivent jamais être refusées — sinon le garde-fou empêcherait la
     * clôture qu'il est censé protéger.
     */
    public function test_contiguous_years_are_accepted(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->annee('2026/2027', '2026-08-27', '2027-08-31');
        $this->depenseLe('2026-08-26');

        $this->put(route('backoffice.annees-scolaires.update', $ancienne), [
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-08-26', $ancienne->fresh()->date_fin->toDateString());
    }

    /**
     * Un trou VIDE reste permis : une année future volontairement détachée
     * n'orpheline rien. Le garde-fou protège de l'argent perdu, il n'impose
     * pas un calendrier continu.
     */
    public function test_an_empty_gap_is_allowed(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->annee('2026/2027', '2026-09-27', '2027-08-31');

        $this->put(route('backoffice.annees-scolaires.update', $ancienne), [
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
        ])->assertSessionHasNoErrors();
    }

    public function test_overlapping_years_are_refused(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->annee('2026/2027', '2026-09-01', '2027-08-31');

        // Une date dans deux fenêtres serait comptée dans les totaux des
        // deux années.
        $this->put(route('backoffice.annees-scolaires.update', $ancienne), [
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-09-15',
        ])->assertSessionHasErrors('date_fin');
    }

    /**
     * À la CRÉATION, la voisine qui précède absorbe le trou par sa borne de
     * FIN — le glissement marche dans les deux sens, pas seulement vers
     * l'année suivante.
     */
    public function test_a_new_year_slides_the_previous_years_end_to_cover_the_gap(): void
    {
        $precedente = $this->annee('2025/2026', '2025-09-01', '2026-08-26');
        $this->depenseLe('2026-08-31');

        $this->post(route('backoffice.annees-scolaires.store'), [
            'nom' => '2026/2027', 'date_debut' => '2026-09-27', 'date_fin' => '2027-08-31',
        ])->assertSessionHasNoErrors();

        // La précédente s'étend jusqu'à la veille de la nouvelle : la
        // dépense du 31/08 reste rattachée à 2025/2026.
        $this->assertSame('2026-09-26', $precedente->fresh()->date_fin->toDateString());
    }
}
