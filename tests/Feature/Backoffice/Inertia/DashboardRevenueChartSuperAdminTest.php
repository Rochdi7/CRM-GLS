<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\AnneeScolaire;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Le graphique « Résumé des frais annuels » (chiffre d'affaire) du tableau
 * de bord est réservé au super-admin (21/10/2026, `dashboard.revenue-chart`
 * dans superAdminOnly()). Pour tout autre compte la donnée n'est pas
 * calculée : `annualFrais` arrive `null`, les cartes du haut restent.
 */
final class DashboardRevenueChartSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
    }

    public function test_the_revenue_chart_is_served_to_a_super_admin_only(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $this->actingAs($admin->fresh())
            ->get(route('backoffice.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('annualFrais')
                ->whereNot('annualFrais', null)
                ->whereNot('annualFraisPeriode', ''));

        // Un directeur (preset le plus large) ne reçoit pas le graphique, mais
        // garde les cartes.
        $director = User::factory()->create();
        $director->assignRole('director');

        $this->actingAs($director->fresh())
            ->get(route('backoffice.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('annualFrais', null)
                ->where('annualFraisPeriode', '')
                ->has('stats.studentsTotal'));
    }

    public function test_no_role_preset_can_carry_the_revenue_chart_permission(): void
    {
        $this->assertContains('dashboard.revenue-chart', PermissionRegistry::superAdminOnly());

        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains('dashboard.revenue-chart', $permissions, "[{$role}] must not hold the revenue chart");
        }

        $this->assertSame(0, Role::permission('dashboard.revenue-chart')->count());
    }
}
