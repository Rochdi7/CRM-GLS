<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Parfois, quand je change de page, la page se recharge entièrement. »
 *
 * HandleInertiaRequests ne redéfinit PAS version(), donc Inertia utilise le
 * hash du manifeste Vite. Après chaque `npm run build` ce hash change : la
 * visite SUIVANTE — y compris un simple clic sur « 2 » — reçoit un 409 avec
 * X-Inertia-Location, et le navigateur fait un rechargement COMPLET.
 *
 * C'est le comportement voulu d'Inertia (recharger le JS périmé), pas un
 * défaut de la pagination. Ce test le FIXE pour qu'on ne le « corrige » pas
 * un jour en désactivant le versionnement, ce qui laisserait les onglets
 * ouverts tourner sur un bundle mort après un déploiement.
 */
final class AssetVersionReloadTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $centre = Etablissement::factory()->create();
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    public function test_a_stale_asset_version_forces_a_full_reload_on_a_pager_click(): void
    {
        $this->actingAs($this->user())
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => 'une-version-perimee',
            ])
            ->get('/backoffice/students?page=2')
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location');
    }

    /** La version COURANTE ne provoque aucun rechargement complet. */
    public function test_the_current_asset_version_paginates_without_a_full_reload(): void
    {
        $version = app(\App\Http\Middleware\HandleInertiaRequests::class)
            ->version(request());

        $this->actingAs($this->user())
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => (string) $version,
            ])
            ->get('/backoffice/students?page=2')
            ->assertOk();
    }
}
