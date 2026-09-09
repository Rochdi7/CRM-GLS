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
 * « Gestion de la base de données » — l'accès est une IDENTITÉ.
 *
 * L'écran écrit directement dans n'importe quelle table, hors de toute
 * action Domain et de tout invariant monétaire : il appartient au SEUL
 * compte de maintenance (`HiddenAccount::EMAIL`), et pas même un
 * super-admin ne l'atteint. Même mécanique que « Réconciliation des
 * paiements importés » : décidée dans `Gate::before`, AU-DESSUS du bypass
 * super-admin (§16).
 */
final class DatabaseManagementAccessTest extends TestCase
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

    public function test_le_compte_de_maintenance_ouvre_la_liste_et_une_table(): void
    {
        $user = $this->userAvecEmail(HiddenAccount::EMAIL);

        $this->actingAs($user)->get('/backoffice/database-management')->assertOk();
        $this->actingAs($user)->get('/backoffice/database-management/banques')->assertOk();
    }

    /** Le cœur de la règle : super-admin ne suffit PAS. */
    public function test_un_super_admin_ordinaire_est_refuse(): void
    {
        $user = $this->userAvecEmail('rafik@glszentrum.com');

        $this->assertTrue($user->hasRole('super-admin'), 'le test doit bien viser un super-admin');

        $this->actingAs($user)->get('/backoffice/database-management')->assertForbidden();
        $this->actingAs($user)->get('/backoffice/database-management/banques')->assertForbidden();
        $this->actingAs($user)->get('/backoffice/database-management/banques/export')->assertForbidden();
    }

    /** Le compte de STAFF du mainteneur n'est pas l'identité de maintenance. */
    public function test_le_compte_staff_du_mainteneur_est_refuse(): void
    {
        $user = $this->userAvecEmail(HiddenAccount::STAFF_EMAIL);

        $this->actingAs($user)->get('/backoffice/database-management')->assertForbidden();
    }

    public function test_un_directeur_est_refuse(): void
    {
        $user = $this->userAvecEmail('directeur@glszentrum.com', 'director');

        $this->actingAs($user)->get('/backoffice/database-management')->assertForbidden();
    }

    public function test_un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->get('/backoffice/database-management')->assertRedirect('/backoffice/login');
    }

    /** L'écriture est gardée aussi, pas seulement l'affichage. */
    public function test_un_super_admin_ne_peut_rien_ecrire(): void
    {
        $user = $this->userAvecEmail('rafik@glszentrum.com');

        $this->actingAs($user)
            ->post('/backoffice/database-management/banques/rows', ['values' => ['nom' => 'X']])
            ->assertForbidden();
        $this->actingAs($user)
            ->put('/backoffice/database-management/banques/rows', ['key' => ['id' => 1], 'values' => ['nom' => 'X']])
            ->assertForbidden();
        $this->actingAs($user)
            ->delete('/backoffice/database-management/banques/rows', ['key' => ['id' => 1]])
            ->assertForbidden();
        $this->actingAs($user)
            ->post('/backoffice/database-management/banques/truncate', ['confirmation' => 'banques'])
            ->assertForbidden();
    }

    /** La page n'est PAS dans la barre latérale — mais ce n'est pas ce qui la protège. */
    public function test_la_page_nest_pas_dans_la_navigation(): void
    {
        $navigation = file_get_contents(base_path('resources/js/Config/backofficeNavigation.ts'));

        $this->assertStringNotContainsString(
            'database-management',
            (string) $navigation,
            "l'écran doit rester hors de la barre latérale (§16)"
        );
    }
}
