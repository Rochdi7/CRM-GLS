<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Livewire\Backoffice\Profile\ProfilePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.profile'))
            ->assertOk()
            ->assertSee('backoffice/profile'); // via layout/route usage
    }

    public function test_a_user_can_update_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'email' => 'old@gls.test']);
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('name', 'New Name')
            ->set('email', 'new@gls.test')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@gls.test', $user->email);
    }

    public function test_a_user_can_change_their_password_with_the_correct_current_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-pass')]);
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('current_password', 'current-pass')
            ->set('password', 'brand-new-pass')
            ->set('password_confirmation', 'brand-new-pass')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_password_change_rejects_a_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-pass')]);
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('current_password', 'wrong')
            ->set('password', 'brand-new-pass')
            ->set('password_confirmation', 'brand-new-pass')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('current-pass', $user->fresh()->password));
    }
}
