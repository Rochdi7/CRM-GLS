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

    public function test_a_gap_that_strands_money_is_refused(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->annee('2026/2027', '2026-09-27', '2027-08-31');

        $this->depenseLe('2026-08-31');

        // Rétrécir l'ancienne année au 26/08 ouvre un trou du 27/08 au 26/09
        // qui contient la dépense.
        $this->put(route('backoffice.annees-scolaires.update', $ancienne), [
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
        ])->assertSessionHasErrors('date_fin');

        $this->assertSame('2026-08-31', $ancienne->fresh()->date_fin->toDateString());
    }

    public function test_the_refusal_names_the_stranded_money(): void
    {
        $ancienne = $this->annee('2025/2026', '2025-09-01', '2026-08-31');
        $this->annee('2026/2027', '2026-09-27', '2027-08-31');
        $this->depenseLe('2026-08-31');

        $response = $this->from(route('backoffice.settings'))
            ->put(route('backoffice.annees-scolaires.update', $ancienne), [
                'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
            ]);

        $response->assertSessionHasErrors('date_fin');

        $message = $response->baseResponse->getSession()
            ->get('errors')->getBag('default')->first('date_fin');

        $this->assertStringContainsString('27/08/2026', $message);
        $this->assertStringContainsString('140', $message);
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

    public function test_a_new_year_cannot_open_a_gap_over_money(): void
    {
        $this->annee('2025/2026', '2025-09-01', '2026-08-26');
        $this->depenseLe('2026-08-31');

        $this->post(route('backoffice.annees-scolaires.store'), [
            'nom' => '2026/2027', 'date_debut' => '2026-09-27', 'date_fin' => '2027-08-31',
        ])->assertSessionHasErrors('date_fin');
    }
}
