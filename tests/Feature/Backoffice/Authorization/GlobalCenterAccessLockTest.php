<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\ReferentialDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * « Tous les centres » is super-admin ONLY (24/08/2026). `centers.access-all`
 * is an ability name answered by Gate::before — it can never be granted to a
 * role (preset or custom) nor as a direct permission, and any stale grant is
 * stripped by RolesAndPermissionsSeeder. « Centres affectés » on the employee
 * form is the one authority on centre reach for everyone else.
 */
final class GlobalCenterAccessLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ReferentialDataSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user;
    }

    public function test_registry_never_offers_the_ability_as_grantable(): void
    {
        $this->assertNotContains(PermissionRegistry::GLOBAL_CENTER_ACCESS, PermissionRegistry::grantable());
        $this->assertFalse(PermissionRegistry::isGrantable(PermissionRegistry::GLOBAL_CENTER_ACCESS));
        $this->assertTrue(PermissionRegistry::exists(PermissionRegistry::GLOBAL_CENTER_ACCESS));

        foreach (PermissionRegistry::groupedGrantable() as $permissions) {
            $this->assertArrayNotHasKey(PermissionRegistry::GLOBAL_CENTER_ACCESS, $permissions);
        }
        // Still listed on the read-only catalogue (grouped()).
        $this->assertArrayHasKey(PermissionRegistry::GLOBAL_CENTER_ACCESS, PermissionRegistry::grouped()['Centres']);
    }

    public function test_a_new_role_cannot_carry_global_center_access(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('backoffice.roles.store'), [
            'label' => 'Coordinateur réseau',
            'name' => 'network-coordinator',
            'permissions' => ['dashboard.view', PermissionRegistry::GLOBAL_CENTER_ACCESS],
        ])->assertSessionHasErrors('permissions.1');

        $this->assertNull(Role::where('name', 'network-coordinator')->first());
    }

    public function test_an_existing_role_cannot_be_widened_to_every_center(): void
    {
        $this->actingAs($this->superAdmin());
        $role = Role::findByName('administrative-assistant');

        $this->put(route('backoffice.roles.update', $role), [
            'label' => $role->label,
            'permissions' => ['dashboard.view', PermissionRegistry::GLOBAL_CENTER_ACCESS],
        ])->assertSessionHasErrors('permissions.1');

        $this->assertFalse($role->fresh()->hasPermissionTo(PermissionRegistry::GLOBAL_CENTER_ACCESS));
    }

    public function test_the_ability_cannot_be_hand_granted_as_a_direct_permission(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => ['administrative-assistant'],
            'directPermissions' => [PermissionRegistry::GLOBAL_CENTER_ACCESS],
        ])->assertSessionHasErrors();

        $this->assertFalse($target->fresh()->hasDirectPermission(PermissionRegistry::GLOBAL_CENTER_ACCESS));
    }

    public function test_seeder_strips_stale_grants_from_custom_roles_and_users(): void
    {
        $permission = Permission::findByName(PermissionRegistry::GLOBAL_CENTER_ACCESS);

        // A custom role (never re-synced by the preset loop) and a direct grant,
        // both written at the model level as an older release allowed.
        $custom = Role::create(['name' => 'legacy-global', 'label' => 'Legacy', 'guard_name' => 'web']);
        $custom->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        $this->assertTrue($user->fresh()->can(PermissionRegistry::GLOBAL_CENTER_ACCESS));

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse($custom->fresh()->hasPermissionTo($permission));
        $this->assertFalse($user->fresh()->can(PermissionRegistry::GLOBAL_CENTER_ACCESS));
        // The permission row itself survives — it is still the ability name Gate::before answers.
        $this->assertNotNull(Permission::findByName(PermissionRegistry::GLOBAL_CENTER_ACCESS));
    }

    public function test_single_center_assistant_never_sees_tous_les_centres(): void
    {
        $centre = Etablissement::query()->orderBy('id')->first();
        $user = User::factory()->create();
        $user->assignRole('administrative-assistant');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        $this->actingAs($user->fresh());
        $context = app(CurrentContext::class);

        $this->assertFalse($context->canPickAllCenters());
        $this->assertFalse($context->canSwitchCenter());
        $this->assertSame([$centre->id], $context->availableCentres()->pluck('id')->all());
        $this->assertSame($centre->id, $context->etablissementId());

        // Posting « Tous les centres » is refused server-side as well.
        $context->setEtablissement(null);
        $this->assertSame($centre->id, $context->etablissementId());
    }

    public function test_only_a_super_admin_may_pick_tous_les_centres(): void
    {
        $this->actingAs($this->superAdmin());
        $context = app(CurrentContext::class);

        $this->assertTrue($context->canPickAllCenters());
        $this->assertSame(Etablissement::count(), $context->availableCentres()->count());
    }
}
