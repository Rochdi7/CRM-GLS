<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get(route('backoffice.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Backoffice/Auth/Login'));
    }

    public function test_root_url_redirects_to_login_for_guests(): void
    {
        $this->get('/')
            ->assertRedirect('/backoffice/login');
    }

    public function test_guests_are_redirected_from_dashboard_to_login(): void
    {
        $this->get(route('backoffice.dashboard'))
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_users_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $this->post(route('backoffice.login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('backoffice.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_login_with_their_username(): void
    {
        $user = User::factory()->create(['username' => 'j.dupont']);

        $this->post(route('backoffice.login.store'), [
            'login' => 'j.dupont',
            'password' => 'password',
        ])->assertRedirect(route('backoffice.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_deactivated_accounts_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->from(route('backoffice.login'))
            ->post(route('backoffice.login.store'), [
                'login' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('backoffice.login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_users_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->from(route('backoffice.login'))
            ->post(route('backoffice.login.store'), [
                'login' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('backoffice.login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_from_login_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.login'))
            ->assertRedirect(route('backoffice.dashboard'));
    }

    public function test_authenticated_users_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.dashboard'))
            ->assertOk();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('backoffice.logout'))
            ->assertRedirect(route('backoffice.login'));

        $this->assertGuest();
    }
}
