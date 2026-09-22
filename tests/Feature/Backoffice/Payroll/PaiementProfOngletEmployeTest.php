<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Onglet « Paiement prof » de la fiche employé — la configuration de paie
 * d'un enseignant, et le tableau win-win mois par mois.
 */
final class PaiementProfOngletEmployeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Employee $prof;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->prof = Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        foreach (['employees.view', 'employees.update', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /** @return array<string, mixed> */
    private function payload(array $extra = []): array
    {
        return [
            'nom' => $this->prof->nom,
            'prenom' => $this->prof->prenom,
            'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => 'Actif',
            'etablissement_ids' => [$this->centre->id],
            ...$extra,
        ];
    }

    #[Test]
    public function the_gls_mode_stores_its_amount_per_student(): void
    {
        $this->actingAs($this->admin())
            ->put("/backoffice/employees/{$this->prof->id}", $this->payload([
                'mode_paiement_prof' => Employee::MODE_PAIEMENT_GLS,
                'montant_par_etudiant_prof' => '500',
            ]))
            ->assertRedirect();

        $this->prof->refresh();
        $this->assertSame(Employee::MODE_PAIEMENT_GLS, $this->prof->mode_paiement_prof);
        $this->assertSame('500.00', (string) $this->prof->montant_par_etudiant_prof);
        // Seul le taux du mode choisi est conservé.
        $this->assertNull($this->prof->taux_horaire_prof);
    }

    #[Test]
    public function a_mode_without_its_rate_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->put("/backoffice/employees/{$this->prof->id}", $this->payload([
                'mode_paiement_prof' => Employee::MODE_PAIEMENT_HORAIRE,
                // pas de taux_horaire_prof
            ]))
            ->assertSessionHasErrors('taux_horaire_prof');

        $this->assertNull($this->prof->fresh()->mode_paiement_prof);
    }

    #[Test]
    public function the_win_win_mode_stores_one_amount_per_month_and_syncs_on_edit(): void
    {
        $admin = $this->admin();

        // Première saisie : deux mois.
        $this->actingAs($admin)
            ->put("/backoffice/employees/{$this->prof->id}", $this->payload([
                'mode_paiement_prof' => Employee::MODE_PAIEMENT_WIN_WIN,
                'taux_mensuels' => [
                    ['mois' => '2025-09', 'montant_par_etudiant' => '400'],
                    ['mois' => '2025-10', 'montant_par_etudiant' => '420'],
                ],
            ]))
            ->assertRedirect();

        $this->assertSame(
            ['2025-09-01' => '400.00', '2025-10-01' => '420.00'],
            $this->prof->tauxMensuels()->get()->mapWithKeys(fn ($t) => [$t->mois->toDateString() => (string) $t->montant_par_etudiant])->all(),
        );

        // Édition : octobre corrigé, septembre retiré, novembre ajouté.
        $this->actingAs($admin)
            ->put("/backoffice/employees/{$this->prof->id}", $this->payload([
                'mode_paiement_prof' => Employee::MODE_PAIEMENT_WIN_WIN,
                'taux_mensuels' => [
                    ['mois' => '2025-10', 'montant_par_etudiant' => '450'],
                    ['mois' => '2025-11', 'montant_par_etudiant' => '500'],
                ],
            ]))
            ->assertRedirect();

        $this->assertSame(
            ['2025-10-01' => '450.00', '2025-11-01' => '500.00'],
            $this->prof->tauxMensuels()->get()->mapWithKeys(fn ($t) => [$t->mois->toDateString() => (string) $t->montant_par_etudiant])->all(),
        );
    }

    #[Test]
    public function the_same_month_twice_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->put("/backoffice/employees/{$this->prof->id}", $this->payload([
                'mode_paiement_prof' => Employee::MODE_PAIEMENT_WIN_WIN,
                'taux_mensuels' => [
                    ['mois' => '2025-09', 'montant_par_etudiant' => '400'],
                    ['mois' => '2025-09', 'montant_par_etudiant' => '420'],
                ],
            ]))
            ->assertSessionHasErrors('taux_mensuels.0.mois');
    }

    #[Test]
    public function leaving_win_win_clears_the_monthly_table(): void
    {
        $this->prof->update(['mode_paiement_prof' => Employee::MODE_PAIEMENT_WIN_WIN]);
        $this->prof->tauxMensuels()->create(['mois' => '2025-09-01', 'montant_par_etudiant' => 400]);

        $this->actingAs($this->admin())
            ->put("/backoffice/employees/{$this->prof->id}", $this->payload([
                'mode_paiement_prof' => Employee::MODE_PAIEMENT_GLS,
                'montant_par_etudiant_prof' => '500',
            ]))
            ->assertRedirect();

        // Des lignes orphelines ressurgiraient au prochain retour en win-win.
        $this->assertSame(0, $this->prof->tauxMensuels()->count());
    }

    #[Test]
    public function the_employee_list_exposes_the_pay_configuration(): void
    {
        $this->prof->update(['mode_paiement_prof' => Employee::MODE_PAIEMENT_WIN_WIN]);
        $this->prof->tauxMensuels()->create(['mois' => '2025-09-01', 'montant_par_etudiant' => 400]);

        $this->actingAs($this->admin())
            ->get('/backoffice/employees?search='.$this->prof->nom)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('employees.data.0.modePaiementProf', Employee::MODE_PAIEMENT_WIN_WIN)
                ->where('employees.data.0.tauxMensuels.0.mois', '2025-09')
                ->where('employees.data.0.tauxMensuels.0.montant_par_etudiant', '400.00'));
    }
}
