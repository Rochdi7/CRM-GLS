<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase 3 (docs/inertia-react-migration-plan.md) — Profile converted from
 * the Livewire ProfilePage to Inertia (ProfileController). Verifies the
 * security invariants the task requires: only the signed-in user's own
 * data is editable, and roles/permissions/center/is_active can never be
 * changed through this page regardless of what a request tries to send.
 */
final class ProfileInertiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_exposes_no_sensitive_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.profile'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('user', fn ($u) => collect($u)->keys()->all() === ['name', 'email', 'roles'])
            );
    }

    public function test_is_active_cannot_be_changed_through_profile_update(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('backoffice.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_pays' => 'MA',
            'telephone' => '',
            'whatsapp' => '',
            'is_active' => false,
        ]);

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_roles_cannot_be_changed_through_profile_update(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->actingAs($user)->post(route('backoffice.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_pays' => 'MA',
            'telephone' => '',
            'whatsapp' => '',
            'roles' => [Role::SUPER_ADMIN],
        ]);

        $this->assertTrue($user->fresh()->hasRole('teacher'));
        $this->assertFalse($user->fresh()->hasRole(Role::SUPER_ADMIN));
    }

    public function test_a_user_cannot_update_another_users_profile(): void
    {
        $user = User::factory()->create(['name' => 'Me']);
        $other = User::factory()->create(['name' => 'Someone Else', 'email' => 'other@gls.test']);

        $this->actingAs($user)->post(route('backoffice.profile.update'), [
            'name' => 'Hijacked',
            'email' => $other->email, // would collide with $other's own email
            'phone_pays' => 'MA',
            'telephone' => '',
            'whatsapp' => '',
        ])->assertSessionHasErrors('email'); // unique rule blocks it

        $this->assertSame('Someone Else', $other->fresh()->name);
    }

    public function test_center_assignment_cannot_be_changed_through_profile(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => null]);

        $this->actingAs($user)->post(route('backoffice.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_pays' => 'MA',
            'telephone' => '',
            'whatsapp' => '',
            'etablissement_id' => 999,
        ]);

        $this->assertNull($employee->fresh()->etablissement_id);
    }
}
