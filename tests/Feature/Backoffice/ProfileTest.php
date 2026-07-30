<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_requires_authentication(): void
    {
        $this->get(route('backoffice.profile'))->assertRedirect(route('backoffice.login'));
    }

    public function test_a_user_can_open_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@gls.test']);

        $this->actingAs($user)
            ->get(route('backoffice.profile'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Profile/Index')
                ->where('user.name', 'Jane Doe')
                ->where('user.email', 'jane@gls.test')
            );
    }

    public function test_a_user_can_update_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'email' => 'old@gls.test']);

        $this->actingAs($user)
            ->post(route('backoffice.profile.update'), [
                'name' => 'New Name',
                'email' => 'new@gls.test',
                'phone_pays' => 'MA',
                'telephone' => '',
                'whatsapp' => '',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@gls.test', $user->email);
    }

    public function test_a_user_can_change_their_password_with_the_correct_current_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-pass')]);

        $this->actingAs($user)
            ->post(route('backoffice.profile.password.update'), [
                'current_password' => 'current-pass',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_password_change_rejects_a_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-pass')]);

        $this->actingAs($user)
            ->post(route('backoffice.profile.password.update'), [
                'current_password' => 'wrong',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('current-pass', $user->fresh()->password));
    }
}
