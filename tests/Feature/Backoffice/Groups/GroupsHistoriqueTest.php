<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GroupsHistoriqueTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);
        $this->centre = Etablissement::factory()->create();
    }

    public function test_page_requires_groups_view(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.groups-historique.index'))->assertForbidden();

        $u2 = User::factory()->create();
        $u2->givePermissionTo('groups.view');
        $this->actingAs($u2)->get(route('backoffice.groups-historique.index'))->assertOk();
    }

    public function test_empty_state_is_shown_when_no_group_archived(): void
    {
        $u = User::factory()->create();
        $u->assignRole(Role::SUPER_ADMIN);

        $this->actingAs($u)->get(route('backoffice.groups-historique.index'))
            ->assertOk()
            ->assertSee(__('No archived groups yet'));
    }

    public function test_archived_group_is_listed(): void
    {
        $u = User::factory()->create();
        $u->assignRole(Role::SUPER_ADMIN);

        $group = Group::create([
            'nom' => 'Groupe A1 Intensif',
            'niveau' => Group::NIVEAUX[0],
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $group->archiverCommeTermine();

        $this->actingAs($u)->get(route('backoffice.groups-historique.index'))
            ->assertOk()
            ->assertSee('Groupe A1 Intensif');
    }
}
