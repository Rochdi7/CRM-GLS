<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Statistiques » tab of Gestion des séances — a placeholder page
 * (« En cours de développement ») behind the same attendance.view
 * permission as the other tabs.
 */
final class SeancesStatistiquesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    public function test_it_requires_attendance_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.seances.statistiques'))
            ->assertForbidden();
    }

    public function test_it_renders_the_placeholder_page(): void
    {
        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.statistiques'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Backoffice/Seances/Statistiques'));
    }
}
