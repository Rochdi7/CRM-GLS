<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Context;

use App\Livewire\Backoffice\Settings\SallesTab;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every CRUD list must follow the center selected in the top-bar context
 * switcher: only the active center's rows (plus global NULL-center rows)
 * may appear. Covers only the one module whose Inertia-side test suite
 * does not yet have its own dedicated file (Salles is part of the
 * Settings tabbed page, not a standalone CRUD module) — every other
 * module's scenario here was superseded and removed once its Livewire
 * component was deleted (docs/phase-11-test-coverage-mapping.md).
 */
final class CenterScopingTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $casa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
    }

    private function globalUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    public function test_salles_tab_is_scoped_to_the_selected_center(): void
    {
        $this->globalUser();
        Salle::factory()->create(['nom' => 'SalleRabatX', 'etablissement_id' => $this->rabat->id]);
        Salle::factory()->create(['nom' => 'SalleCasaX', 'etablissement_id' => $this->casa->id]);

        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        Livewire::test(SallesTab::class)
            ->assertSee('SalleRabatX')
            ->assertDontSee('SalleCasaX');
    }

}
