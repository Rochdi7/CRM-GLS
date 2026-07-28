<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\People;

use App\Livewire\Backoffice\Users\UsersIndex;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class UsersCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['users.view', 'users.assign-roles']);

        return $user->fresh();
    }

    public function test_a_user_can_be_edited(): void
    {
        $this->actingAs($this->manager());
        $target = User::factory()->create(['is_active' => true]);

        Livewire::test(UsersIndex::class)
            ->call('edit', $target->id)
            ->set('name', 'Renamed User')
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('Renamed User', $target->name);
        $this->assertFalse($target->is_active);
    }

    public function test_email_must_stay_unique(): void
    {
        $this->actingAs($this->manager());
        $a = User::factory()->create(['email' => 'a@gls.test']);
        $b = User::factory()->create(['email' => 'b@gls.test']);

        Livewire::test(UsersIndex::class)
            ->call('edit', $b->id)
            ->set('email', 'a@gls.test')
            ->call('save')
            ->assertHasErrors('email');
    }

    public function test_password_can_be_regenerated(): void
    {
        $this->actingAs($this->manager());
        $target = User::factory()->create(['must_change_password' => false]);
        $oldHash = $target->password;

        Livewire::test(UsersIndex::class)
            ->call('edit', $target->id)
            ->call('regeneratePassword')
            ->assertSet('regeneratedPassword', fn ($v) => is_string($v) && strlen($v) >= 12);

        $target->refresh();
        $this->assertNotSame($oldHash, $target->password);
        $this->assertTrue($target->must_change_password);
    }

    public function test_editing_requires_the_management_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('users.view'); // can list, cannot edit
        $this->actingAs($viewer);

        Livewire::test(UsersIndex::class)
            ->call('edit', User::factory()->create()->id)
            ->assertForbidden();
    }
}
