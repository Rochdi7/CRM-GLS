<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Settings;

use App\Livewire\Backoffice\Settings\AnneesScolairesTab;
use App\Livewire\Backoffice\Settings\EtablissementsTab;
use App\Livewire\Backoffice\Settings\SallesTab;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SettingsTest extends TestCase
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
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user->fresh();
    }

    // --- Page access -------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('backoffice.settings'))->assertRedirect(route('backoffice.login'));
    }

    public function test_user_without_any_referential_permission_gets_403(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.settings'))
            ->assertForbidden();
    }

    public function test_user_with_any_referential_view_permission_can_open_settings(): void
    {
        $this->actingAs($this->userWith('rooms.view'))
            ->get(route('backoffice.settings'))
            ->assertOk()
            ->assertSee('Paramètres');
    }

    // --- Établissements tab ------------------------------------------------

    public function test_center_can_be_created_and_edited(): void
    {
        $this->actingAs($this->userWith('centers.view', 'centers.create', 'centers.update'));

        Livewire::test(EtablissementsTab::class)
            ->call('create')
            ->set('nom_centre', 'GLS Casablanca')
            ->set('ville', 'Casablanca')
            ->set('siege_social', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('etablissements', ['nom_centre' => 'GLS Casablanca', 'siege_social' => true]);

        $center = Etablissement::first();
        Livewire::test(EtablissementsTab::class)
            ->call('edit', $center->id)
            ->set('ville', 'Rabat')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Rabat', $center->fresh()->ville);
    }

    public function test_center_creation_validates_required_fields(): void
    {
        $this->actingAs($this->userWith('centers.view', 'centers.create'));

        Livewire::test(EtablissementsTab::class)
            ->call('create')
            ->set('nom_centre', '')
            ->set('ville', '')
            ->call('save')
            ->assertHasErrors(['nom_centre', 'ville']);
    }

    public function test_user_without_create_permission_cannot_add_a_center(): void
    {
        $this->actingAs($this->userWith('centers.view'));

        Livewire::test(EtablissementsTab::class)
            ->call('create')
            ->assertForbidden();
    }

    public function test_center_in_use_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWith('centers.view', 'centers.delete'));
        $center = Etablissement::factory()->create();
        Salle::factory()->create(['etablissement_id' => $center->id]);

        Livewire::test(EtablissementsTab::class)
            ->call('delete', $center->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('etablissements', ['id' => $center->id]);
    }

    // --- Années scolaires tab ---------------------------------------------

    public function test_setting_a_default_year_unsets_the_previous_one(): void
    {
        $this->actingAs($this->userWith('academic-years.view', 'academic-years.create'));

        $existing = AnneeScolaire::create([
            'nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        Livewire::test(AnneesScolairesTab::class)
            ->call('create')
            ->set('nom', '2025/2026')
            ->set('date_debut', '2025-09-01')
            ->set('date_fin', '2026-08-31')
            ->set('par_defaut', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($existing->fresh()->par_defaut);
        $this->assertTrue(AnneeScolaire::where('nom', '2025/2026')->first()->par_defaut);
        $this->assertSame(1, AnneeScolaire::where('par_defaut', true)->count());
    }

    public function test_academic_year_end_date_must_be_after_start(): void
    {
        $this->actingAs($this->userWith('academic-years.view', 'academic-years.create'));

        Livewire::test(AnneesScolairesTab::class)
            ->call('create')
            ->set('nom', '2025/2026')
            ->set('date_debut', '2026-01-01')
            ->set('date_fin', '2025-01-01')
            ->call('save')
            ->assertHasErrors('date_fin');
    }

    // --- Salles tab --------------------------------------------------------

    public function test_room_can_be_created_for_a_center(): void
    {
        $this->actingAs($this->userWith('rooms.view', 'rooms.create'));
        $center = Etablissement::factory()->create();

        Livewire::test(SallesTab::class)
            ->call('create')
            ->set('nom', 'Salle 01')
            ->set('etablissement_id', $center->id)
            ->set('capacite', 20)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salles', ['nom' => 'Salle 01', 'etablissement_id' => $center->id]);
    }

    public function test_room_requires_a_center(): void
    {
        $this->actingAs($this->userWith('rooms.view', 'rooms.create'));

        Livewire::test(SallesTab::class)
            ->call('create')
            ->set('nom', 'Salle 01')
            ->set('etablissement_id', null)
            ->call('save')
            ->assertHasErrors('etablissement_id');
    }

    public function test_user_without_delete_permission_cannot_delete_a_room(): void
    {
        $this->actingAs($this->userWith('rooms.view'));
        $room = Salle::factory()->create(['etablissement_id' => Etablissement::factory()]);

        Livewire::test(SallesTab::class)
            ->call('delete', $room->id)
            ->assertForbidden();

        $this->assertDatabaseHas('salles', ['id' => $room->id]);
    }

    public function test_super_admin_can_manage_every_tab(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin);

        Livewire::test(EtablissementsTab::class)->call('create')->assertOk();
        Livewire::test(AnneesScolairesTab::class)->call('create')->assertOk();
        Livewire::test(SallesTab::class)->call('create')->assertOk();
    }
}
