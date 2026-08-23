<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncDefaultRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_roleless_logins_get_the_default_role_for_their_category(): void
    {
        $roleless = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $roleless->id,
            'categorie' => Employee::CATEGORIE_CONSULTANT,
        ]);

        $this->artisan('auth:sync-default-roles')->assertSuccessful();

        $this->assertTrue($roleless->refresh()->hasRole('consultant'));
    }

    public function test_autre_is_reported_but_gets_nothing_and_existing_roles_are_untouched(): void
    {
        $autre = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $autre->id,
            'categorie' => Employee::CATEGORIE_AUTRE,
        ]);

        $promoted = User::factory()->create();
        $promoted->assignRole('director');
        Employee::factory()->create([
            'user_id' => $promoted->id,
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
        ]);

        $this->artisan('auth:sync-default-roles')->assertSuccessful();

        $this->assertSame(0, $autre->refresh()->roles()->count());
        $promoted->refresh();
        $this->assertTrue($promoted->hasRole('director'));
        $this->assertFalse($promoted->hasRole('teacher'));
    }

    public function test_responsable_de_systeme_always_gets_super_admin_even_with_another_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('accountant');
        Employee::factory()->create([
            'user_id' => $user->id,
            'categorie' => Employee::CATEGORIE_RESPONSABLE_SYSTEME,
        ]);

        $this->artisan('auth:sync-default-roles')->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->hasRole(Role::SUPER_ADMIN));
        $this->assertTrue($user->hasRole('accountant'));

        // Idempotent: a second run does nothing.
        $this->artisan('auth:sync-default-roles')
            ->expectsOutputToContain('nothing to do')
            ->assertSuccessful();
    }

    public function test_dry_run_writes_nothing_and_the_command_is_idempotent(): void
    {
        $roleless = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $roleless->id,
            'categorie' => Employee::CATEGORIE_COMPTABLE,
        ]);

        $this->artisan('auth:sync-default-roles', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, $roleless->refresh()->roles()->count());

        $this->artisan('auth:sync-default-roles')->assertSuccessful();
        $this->artisan('auth:sync-default-roles')->assertSuccessful();

        $this->assertSame(1, $roleless->refresh()->roles()->count());
        $this->assertTrue($roleless->hasRole('accountant'));
    }
}
