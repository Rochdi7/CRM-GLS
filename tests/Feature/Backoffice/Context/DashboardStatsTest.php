<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Context;

use App\Livewire\Backoffice\Dashboard\DashboardStats;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);
    }

    public function test_stats_are_scoped_to_the_selected_center(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin);

        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();
        Student::factory()->count(3)->create(['etablissement_id' => $rabat->id]);
        Student::factory()->count(5)->create(['etablissement_id' => $casa->id]);

        // "All centers" → everyone.
        Livewire::test(DashboardStats::class)->assertSee('8');

        // Switch to Rabat → only its 3 students.
        app(CurrentContext::class)->setEtablissement($rabat->id);
        Livewire::test(DashboardStats::class)
            ->assertSee('3')
            ->assertSee('GLS'); // center label shown in the banner
    }

    public function test_stats_refresh_on_context_changed_event(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin);

        $rabat = Etablissement::factory()->create();
        Student::factory()->count(4)->create(['etablissement_id' => $rabat->id]);

        $component = Livewire::test(DashboardStats::class)->assertSee('4');

        // Simulate the top-bar switcher narrowing to an empty center.
        $empty = Etablissement::factory()->create();
        app(CurrentContext::class)->setEtablissement($empty->id);

        $component->dispatch('context-changed')->assertSee('0');
    }
}
