<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit 27/08/2026 — SEC-01/02/03/04, CRUD-F18: administering a login
 * (edit, regenerate password, deactivate, change roles) is gated by
 * UserPolicy, not by the bare `users.assign-roles` ability.
 */
final class UserAdministrationProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $marrakech;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
    }

    private function userIn(Etablissement $centre, ?string $role = null): User
    {
        $user = User::factory()->create();
        if ($role !== null) {
            $user->assignRole($role);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    private function director(Etablissement $centre): User
    {
        return $this->userIn($centre, 'director');
    }

    private function superAdmin(?Etablissement $centre = null): User
    {
        $user = $centre ? $this->userIn($centre) : User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user->fresh();
    }

    /** @return array<string, mixed> */
    private function payload(User $u, bool $active = true): array
    {
        return ['name' => $u->name, 'email' => $u->email, 'username' => $u->username, 'is_active' => $active];
    }

    public function test_director_holds_the_ability_but_cannot_touch_a_super_admin(): void
    {
        $director = $this->director($this->marrakech);
        $this->assertTrue($director->can('users.assign-roles'));
        $ceo = $this->superAdmin($this->marrakech);
        $hash = $ceo->password;
        $this->actingAs($director);

        $this->post(route('backoffice.users.regenerate-password', $ceo))->assertForbidden();
        $this->put(route('backoffice.users.update', $ceo), $this->payload($ceo, false))->assertForbidden();
        $this->put(route('backoffice.users.update', $ceo), ['name' => $ceo->name, 'email' => 'stolen@gls.test', 'is_active' => true])->assertForbidden();
        $this->get(route('backoffice.users.authorization.edit', $ceo))->assertForbidden();
        $this->put(route('backoffice.users.authorization.update', $ceo), ['roles' => ['teacher']])->assertForbidden();

        $ceo->refresh();
        $this->assertSame($hash, $ceo->password);
        $this->assertTrue($ceo->is_active);
        $this->assertTrue($ceo->hasRole(Role::SUPER_ADMIN));
    }

    public function test_director_cannot_administer_a_user_of_another_centre(): void
    {
        $director = $this->director($this->marrakech);
        $other = $this->userIn($this->rabat, 'teacher');
        $this->actingAs($director);

        $this->put(route('backoffice.users.update', $other), $this->payload($other, false))->assertForbidden();
        $this->post(route('backoffice.users.regenerate-password', $other))->assertForbidden();
        $this->put(route('backoffice.users.authorization.update', $other), ['roles' => ['director']])->assertForbidden();

        $this->assertTrue($other->fresh()->is_active);
        $this->assertFalse($other->fresh()->hasRole('director'));
    }

    public function test_director_can_administer_a_user_of_own_centre(): void
    {
        $director = $this->director($this->marrakech);
        $colleague = $this->userIn($this->marrakech, 'teacher');
        $this->actingAs($director);

        $this->put(route('backoffice.users.update', $colleague), $this->payload($colleague, false))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($colleague->fresh()->is_active);

        $this->post(route('backoffice.users.regenerate-password', $colleague))->assertRedirect();
        $this->assertTrue(session()->has('regeneratedPassword'));

        $this->put(route('backoffice.users.authorization.update', $colleague), ['roles' => ['accountant']])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($colleague->fresh()->hasRole('accountant'));
    }

    public function test_director_cannot_edit_own_account_or_own_authorization(): void
    {
        $director = $this->director($this->marrakech);
        $this->actingAs($director);

        $this->put(route('backoffice.users.update', $director), $this->payload($director))->assertForbidden();
        $this->post(route('backoffice.users.regenerate-password', $director))->assertForbidden();
        $this->put(route('backoffice.users.authorization.update', $director), ['roles' => ['director', 'accountant']])
            ->assertForbidden();

        $this->assertFalse($director->fresh()->hasRole('accountant'));
    }

    public function test_super_admin_cannot_edit_own_authorization_through_the_service(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        $this->from(route('backoffice.users.authorization.edit', $admin))
            ->put(route('backoffice.users.authorization.update', $admin), ['roles' => [Role::SUPER_ADMIN, 'director']])
            ->assertSessionHasErrors('roles');
        $this->assertFalse($admin->fresh()->hasRole('director'));
    }

    public function test_a_super_admin_can_deactivate_another_one_while_one_remains(): void
    {
        $admin = $this->superAdmin();
        $ceo = $this->superAdmin($this->marrakech);
        $this->actingAs($admin);

        $this->put(route('backoffice.users.update', $ceo), $this->payload($ceo, false))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($ceo->fresh()->is_active);
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $admin = $this->superAdmin();
        $this->superAdmin(); // another one exists, so "last" is not the reason
        $this->actingAs($admin);

        $this->from(route('backoffice.users.index'))
            ->put(route('backoffice.users.update', $admin), $this->payload($admin, false))
            ->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_super_admin_administers_anyone_in_any_centre(): void
    {
        $admin = $this->superAdmin();
        $other = $this->userIn($this->rabat, 'teacher');
        $this->actingAs($admin);

        $this->put(route('backoffice.users.update', $other), $this->payload($other))->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('backoffice.users.regenerate-password', $other))->assertRedirect();
        $this->put(route('backoffice.users.authorization.update', $other), ['roles' => ['director']])->assertRedirect();
        $this->assertTrue($other->fresh()->hasRole('director'));
    }

    public function test_deactivated_user_is_logged_out_on_next_request_and_remember_token_rotated(): void
    {
        $admin = $this->superAdmin();
        $victim = $this->userIn($this->marrakech, 'teacher');
        $victim->setRememberToken('old-token');
        $victim->save();

        $this->actingAs($victim)->get(route('backoffice.dashboard'))->assertOk();

        $this->actingAs($admin)
            ->put(route('backoffice.users.update', $victim), $this->payload($victim, false))
            ->assertRedirect();

        $victim->refresh();
        $this->assertFalse($victim->is_active);
        $this->assertNotSame('old-token', $victim->getRememberToken());

        // The already-open session is refused on its very next request.
        $this->actingAs($victim)->get(route('backoffice.dashboard'))
            ->assertRedirect(route('backoffice.login'));
        $this->assertGuest();
    }
}
