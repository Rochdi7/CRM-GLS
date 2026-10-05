<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Role;
use App\Support\Authorization\PermissionRegistry;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit 05/10/2026: the CEO had widened « Comptable » to 61 permissions on
 * the Rôles screen; `RolesAndPermissionsSeeder` re-synced every preset role
 * to its code preset on each run, so the next `db:seed` (the runbook's step
 * 9, re-run whenever a module adds permissions) would have silently put the
 * role back to 41. A seeder garnit — it never undoes an admin's decision
 * (CLAUDE.md § « Seeders — production only »). The seeder now three-way
 * merges against the preset it recorded at the previous run
 * (`AppSettings::ROLE_PRESETS_APPLIED`).
 */
final class RolesSeederKeepsHandEditsTest extends TestCase
{
    use RefreshDatabase;

    private const ROLE = 'accountant';

    protected function setUp(): void
    {
        parent::setUp();
        RolesAndPermissionsSeeder::$resetPresets = false;
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        RolesAndPermissionsSeeder::$resetPresets = false;
        parent::tearDown();
    }

    private function role(): Role
    {
        return Role::findByName(self::ROLE)->fresh();
    }

    /** @return list<string> */
    private function preset(): array
    {
        return PermissionRegistry::matrix()[self::ROLE];
    }

    public function test_a_fresh_seed_applies_the_preset_and_records_it(): void
    {
        $this->assertEqualsCanonicalizing($this->preset(), $this->role()->permissions->pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            $this->preset(),
            AppSettings::options(AppSettings::ROLE_PRESETS_APPLIED)[self::ROLE],
        );
    }

    public function test_a_permission_granted_by_hand_survives_a_reseed(): void
    {
        $this->assertNotContains('employees.view', $this->preset());
        $this->role()->givePermissionTo('employees.view');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue($this->role()->hasPermissionTo('employees.view'));
        // …and the preset is still all there.
        foreach ($this->preset() as $permission) {
            $this->assertTrue($this->role()->hasPermissionTo($permission), $permission);
        }
    }

    public function test_a_permission_revoked_by_hand_stays_revoked_after_a_reseed(): void
    {
        $this->assertContains('payments.view', $this->preset());
        $this->role()->revokePermissionTo('payments.view');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse($this->role()->hasPermissionTo('payments.view'));
    }

    public function test_a_permission_the_code_adds_to_the_preset_reaches_an_edited_role(): void
    {
        // Simulate "the previous seed ran with a preset that lacked X": the
        // live role was hand-edited meanwhile (so it is not a fresh role).
        $this->role()->givePermissionTo('employees.view');
        $this->assertContains('reports.view', $this->preset());
        $this->role()->revokePermissionTo('reports.view');

        $applied = AppSettings::options(AppSettings::ROLE_PRESETS_APPLIED);
        $applied[self::ROLE] = array_values(array_diff($applied[self::ROLE], ['reports.view']));
        AppSettings::setOptions(AppSettings::ROLE_PRESETS_APPLIED, $applied);

        $this->seed(RolesAndPermissionsSeeder::class);

        // The code "added" reports.view since the last seed → granted…
        $this->assertTrue($this->role()->hasPermissionTo('reports.view'));
        // …without touching the hand grant.
        $this->assertTrue($this->role()->hasPermissionTo('employees.view'));
    }

    public function test_a_permission_the_code_removes_from_the_preset_is_revoked_on_an_edited_role(): void
    {
        $this->role()->givePermissionTo('employees.view');
        // Simulate "the previous seed's preset carried Y, the code dropped it".
        $this->role()->givePermissionTo('stock.create');
        $applied = AppSettings::options(AppSettings::ROLE_PRESETS_APPLIED);
        $applied[self::ROLE] = [...$applied[self::ROLE], 'stock.create'];
        AppSettings::setOptions(AppSettings::ROLE_PRESETS_APPLIED, $applied);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse($this->role()->hasPermissionTo('stock.create'));
        $this->assertTrue($this->role()->hasPermissionTo('employees.view'));
    }

    public function test_a_reserved_ability_granted_by_hand_is_stripped(): void
    {
        $this->role()->givePermissionTo('expenses.approve');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse($this->role()->hasPermissionTo('expenses.approve'));
    }

    public function test_a_database_seeded_before_tracking_keeps_its_hand_grants(): void
    {
        $this->role()->givePermissionTo('employees.view');
        AppSettings::setOptions(AppSettings::ROLE_PRESETS_APPLIED, []);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue($this->role()->hasPermissionTo('employees.view'));
        foreach ($this->preset() as $permission) {
            $this->assertTrue($this->role()->hasPermissionTo($permission), $permission);
        }
        $this->assertEqualsCanonicalizing(
            $this->preset(),
            AppSettings::options(AppSettings::ROLE_PRESETS_APPLIED)[self::ROLE],
        );
    }

    public function test_an_explicit_reset_puts_the_preset_back(): void
    {
        $this->role()->givePermissionTo('employees.view');
        $this->role()->revokePermissionTo('payments.view');

        RolesAndPermissionsSeeder::$resetPresets = true;
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertEqualsCanonicalizing($this->preset(), $this->role()->permissions->pluck('name')->all());
    }

    public function test_super_admin_keeps_an_empty_permission_set(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(0, Role::findByName(Role::SUPER_ADMIN)->permissions()->count());
    }
}
