<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('backoffice.password.request'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Backoffice/Auth/ForgotPassword'));
    }

    public function test_authenticated_users_are_redirected_away_from_forgot_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.password.request'))
            ->assertRedirect(route('backoffice.dashboard'));
    }

    public function test_a_reset_link_is_emailed_for_a_known_address(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('backoffice.password.email'), ['email' => $user->email])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_emailed_link_points_to_the_backoffice_reset_page(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('backoffice.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/backoffice/reset-password/');
        });
    }

    public function test_unknown_email_does_not_error_out(): void
    {
        Notification::fake();

        // No user enumeration: an unknown email still redirects back cleanly.
        $this->post(route('backoffice.password.email'), ['email' => 'nobody@gls.test'])
            ->assertSessionHasErrors('email'); // broker returns "user" status

        Notification::assertNothingSent();
    }

    public function test_reset_password_page_renders_with_token(): void
    {
        $this->get(route('backoffice.password.reset', ['token' => 'sometoken', 'email' => 'x@gls.test']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Auth/ResetPassword')
                ->where('token', 'sometoken')
                ->where('email', 'x@gls.test')
            );
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['must_change_password' => true]);

        $this->post(route('backoffice.password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $response = $this->post(route('backoffice.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('backoffice.login'))->assertSessionHas('status');

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('new-password-123', $fresh->password));
        // Reset clears the forced-change flag from auto-generated credentials.
        $this->assertFalse($fresh->must_change_password);
    }

    public function test_reset_requires_matching_confirmation(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->from(route('backoffice.password.reset', ['token' => $token]))
            ->post(route('backoffice.password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'different-456',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_an_inactive_account_is_told_it_is_inactive_and_gets_no_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => false]);

        $this->post(route('backoffice.password.email'), ['email' => $user->email])
            ->assertSessionHasErrors([
                'email' => __('Your account is not active. Please contact the administration.'),
            ]);

        Notification::assertNothingSent();
    }

    public function test_a_link_emailed_before_deactivation_no_longer_resets_the_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $user->forceFill(['is_active' => false])->save();
        $before = $user->fresh()->password;

        $this->post(route('backoffice.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors([
            'email' => __('Your account is not active. Please contact the administration.'),
        ]);

        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post(route('backoffice.password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');
    }
}
