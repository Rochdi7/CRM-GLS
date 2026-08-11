<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase 3 (docs/inertia-react-migration-plan.md) — login, logout, and
 * password-reset flows converted to Inertia. Verifies the Inertia
 * component/prop contract on top of AuthTest/PasswordResetTest's existing
 * authentication-behavior assertions (rate limiting, is_active gate,
 * email-or-username, session regeneration — all untouched).
 */
final class AuthInertiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shares_null_auth_for_guests(): void
    {
        $this->get(route('backoffice.login'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user', null)
                ->where('auth.permissions', [])
                ->where('context', null)
            );
    }

    public function test_login_page_does_not_expose_password_in_props(): void
    {
        $response = $this->get(route('backoffice.login'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Backoffice/Auth/Login')
        );

        $response->assertDontSee('"password"', false);
    }

    public function test_failed_login_preserves_the_entered_identifier(): void
    {
        $user = User::factory()->create();

        $this->from(route('backoffice.login'))
            ->post(route('backoffice.login.store'), [
                'login' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('login')
            ->assertSessionHasInput('login', $user->email);
    }

    public function test_failed_login_does_not_preserve_the_password(): void
    {
        $user = User::factory()->create();

        $this->from(route('backoffice.login'))
            ->post(route('backoffice.login.store'), [
                'login' => $user->email,
                'password' => 'wrong-password',
            ]);

        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_authenticated_user_shares_minimal_auth_fields_only(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.profile'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user', fn ($authUser) => collect($authUser)->keys()->all() === ['id', 'name', 'email', 'photoUrl'])
            );
    }

    public function test_forgot_password_page_shares_null_auth_for_guests(): void
    {
        $this->get(route('backoffice.password.request'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user', null));
    }

    public function test_reset_password_page_receives_only_token_and_email(): void
    {
        $this->get(route('backoffice.password.reset', ['token' => 'abc123', 'email' => 'x@gls.test']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('token')
                ->has('email')
                ->where('token', 'abc123')
                ->where('email', 'x@gls.test')
                ->missing('user')
                ->missing('password')
            );
    }

    public function test_reset_link_notification_is_requested_for_a_known_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('backoffice.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_logout_requires_post_and_invalidates_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('backoffice.logout'))
            ->assertRedirect(route('backoffice.login'));

        $this->assertGuest();

        // The dashboard is no longer reachable after logout (browser-back
        // does not reveal protected content).
        $this->get(route('backoffice.dashboard'))
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_get_logout_is_not_a_valid_route(): void
    {
        $user = User::factory()->create();

        // The URI exists (POST-only) — GET is rejected at the HTTP-method
        // level (405), which is the correct signal that no GET logout
        // endpoint was ever added.
        $this->actingAs($user)
            ->get('/backoffice/logout')
            ->assertMethodNotAllowed();
    }
}
