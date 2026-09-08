<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Access;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Support\Access\HiddenAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Réconciliation des paiements importés » — l'accès est une IDENTITÉ.
 *
 * L'écran réécrit l'affectation d'argent déjà encaissé en lisant des
 * fichiers du serveur : il appartient au SEUL compte de maintenance
 * (`HiddenAccount::EMAIL`), et pas même un super-admin ne l'atteint. Même
 * raisonnement — et même mécanique — que `GroupPolicy@updateClosed` : la
 * décision est prise dans `Gate::before`, AU-DESSUS du bypass super-admin,
 * sans quoi `Gate::before` l'accorderait à tout le monde (§16).
 *
 * ⚠ `EMAIL` seul, jamais `emails()` : `STAFF_EMAIL` est un compte de staff
 * du même homme, pas l'identité de maintenance.
 */
final class LegacyReconciliationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userAvecEmail(string $email, string $role = 'super-admin'): User
    {
        $centre = Etablissement::factory()->create();
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    public function test_le_compte_de_maintenance_ouvre_la_page(): void
    {
        $user = $this->userAvecEmail(HiddenAccount::EMAIL);

        $this->actingAs($user)
            ->get('/backoffice/reconciliation-paiements')
            ->assertOk();
    }

    /** Le cœur de la règle : super-admin ne suffit PAS. */
    public function test_un_super_admin_ordinaire_est_refuse(): void
    {
        $user = $this->userAvecEmail('rafik@glszentrum.com');

        $this->assertTrue($user->hasRole('super-admin'), 'le test doit bien viser un super-admin');

        $this->actingAs($user)
            ->get('/backoffice/reconciliation-paiements')
            ->assertForbidden();
    }

    /** Le compte de STAFF du mainteneur n'est pas l'identité de maintenance. */
    public function test_le_compte_staff_du_mainteneur_est_refuse(): void
    {
        $user = $this->userAvecEmail(HiddenAccount::STAFF_EMAIL);

        $this->actingAs($user)
            ->get('/backoffice/reconciliation-paiements')
            ->assertForbidden();
    }

    public function test_un_directeur_est_refuse(): void
    {
        $user = $this->userAvecEmail('directeur@glszentrum.com', 'director');

        $this->actingAs($user)
            ->get('/backoffice/reconciliation-paiements')
            ->assertForbidden();
    }

    /** L'écriture est gardée aussi, pas seulement l'affichage. */
    public function test_un_super_admin_ne_peut_pas_lancer_la_commande(): void
    {
        $user = $this->userAvecEmail('rafik@glszentrum.com');

        $this->actingAs($user)
            ->post('/backoffice/reconciliation-paiements', [
                'dossier' => base_path('data'),
                'apply' => true,
            ])
            ->assertForbidden();
    }

    /** Un chemin hors du dossier configuré est refusé, même au mainteneur. */
    public function test_un_dossier_hors_perimetre_est_refuse(): void
    {
        $user = $this->userAvecEmail(HiddenAccount::EMAIL);

        $this->actingAs($user)
            ->post('/backoffice/reconciliation-paiements', [
                'dossier' => base_path('app'),
                'apply' => false,
            ])
            ->assertSessionHasErrors('dossier');
    }

    /** La page n'est PAS dans la barre latérale — mais ce n'est pas ce qui la protège. */
    public function test_la_page_nest_pas_dans_la_navigation(): void
    {
        $navigation = file_get_contents(base_path('resources/js/Config/backofficeNavigation.ts'));

        $this->assertStringNotContainsString(
            'reconciliation-paiements',
            (string) $navigation,
            "l'écran doit rester hors de la barre latérale (§16)"
        );
    }
}
