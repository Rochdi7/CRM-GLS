<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_a_user_can_upload_their_own_avatar(): void
    {
        // Deliberately NOT faking the `media` disk — Storage::fake() rewrites
        // the disk's custom `url` to the generic /storage/{disk} convention,
        // which would make the /media/ assertion below meaningless (same
        // rationale as DepensesInertiaCrudTest's receipt test).
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('backoffice.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('moi.jpg'),
        ])->assertSessionDoesntHaveErrors();

        $employee->refresh();
        $this->assertSame(1, $employee->getMedia('photo')->count());
        $this->assertStringContainsString('/media/', $employee->getFirstMediaUrl('photo'));

        // Clean up the real file this test wrote to storage/app/media.
        $employee->clearMediaCollection('photo');
    }

    public function test_a_new_avatar_replaces_the_previous_one(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $this->post(route('backoffice.profile.photo.update'), ['photo' => UploadedFile::fake()->image('un.jpg')]);
        $this->post(route('backoffice.profile.photo.update'), ['photo' => UploadedFile::fake()->image('deux.jpg')]);

        // singleFile() collection — the second upload supersedes the first.
        $this->assertSame(1, $employee->refresh()->getMedia('photo')->count());

        $employee->clearMediaCollection('photo');
    }

    public function test_a_user_can_remove_their_own_avatar(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $this->post(route('backoffice.profile.photo.update'), ['photo' => UploadedFile::fake()->image('moi.jpg')]);
        $this->assertSame(1, $employee->refresh()->getMedia('photo')->count());

        $this->delete(route('backoffice.profile.photo.destroy'))->assertSessionDoesntHaveErrors();

        $this->assertSame(0, $employee->refresh()->getMedia('photo')->count());
    }

    public function test_a_non_image_upload_is_refused(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('backoffice.profile.photo.update'), [
            'photo' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('photo');

        $this->assertSame(0, $employee->refresh()->getMedia('photo')->count());
    }

    public function test_a_user_cannot_change_another_users_avatar(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);

        $victim = User::factory()->create();
        $victimEmployee = Employee::factory()->create(['user_id' => $victim->id]);

        // The endpoint takes no id — it always resolves the employee from the
        // authenticated user, so a forged employee_id changes nothing.
        $this->actingAs($user)->post(route('backoffice.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('moi.jpg'),
            'employee_id' => $victimEmployee->id,
        ]);

        $this->assertSame(0, $victimEmployee->refresh()->getMedia('photo')->count());

        $user->employee->refresh()->clearMediaCollection('photo');
    }

    public function test_a_guest_cannot_upload_an_avatar(): void
    {
        $this->post(route('backoffice.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('moi.jpg'),
        ])->assertRedirect(route('backoffice.login'));
    }
}
