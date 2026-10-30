<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Modifier » sur la fiche d'un groupe (30/10/2026). Le bouton renvoyait sur
 * la liste sans rien ouvrir : le modal d'édition vit sur la liste, il est
 * désormais ouvert par `?edit=<id>`, dont la ligne est servie à part
 * (`editGroup`) parce que le groupe peut être sur une autre page.
 */
final class GroupEditFromShowTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
            'nom' => 'ANASS 19H A1',
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    public function test_the_show_page_links_modifier_to_the_list_edit_modal(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.groups.show', $this->group))
            ->assertInertia(fn (Assert $page) => $page
                ->where('group.canEdit', true)
                ->where('group.editUrl', route('backoffice.groups.index', [
                    'statutFilter' => Group::STATUT_EN_FORMATION,
                    'edit' => $this->group->id,
                ])));
    }

    public function test_the_list_serves_the_group_to_edit_even_off_the_current_page(): void
    {
        // Onze groupes plus récents : celui à éditer tombe en page 2.
        Group::factory()->count(11)->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->group->annee_scolaire_id,
            'statut' => Group::STATUT_EN_FORMATION,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.groups.index', ['edit' => $this->group->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('editGroup.id', $this->group->id)
                ->where('editGroup.nom', 'ANASS 19H A1')
                ->has('editGroup.fraisLignes')
                ->has('editGroup.fraisRetires'));
    }

    public function test_no_group_is_served_without_the_parameter(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.groups.index'))
            ->assertInertia(fn (Assert $page) => $page->where('editGroup', null));
    }

    /**
     * Signalé le 30/10/2026 (MOUSSA 17H A1) : 30 étudiants, badges 28 + 0 + 1 —
     * l'inscription Archivée n'avait aucun badge. Les badges doivent couvrir
     * tous les statuts, sur la liste comme sur la fiche.
     */
    public function test_the_statistic_badges_add_up_to_every_inscription(): void
    {
        $statuts = [
            Inscription::STATUT_ACTIVE, Inscription::STATUT_ACTIVE,
            Inscription::STATUT_ANNULEE, Inscription::STATUT_CHANGEMENT,
            Inscription::STATUT_ARCHIVEE, Inscription::STATUT_TRANSFEREE,
            Inscription::STATUT_EXPIREE,
        ];

        foreach ($statuts as $i => $statut) {
            $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
            Inscription::create([
                'reference' => 'INS-BDG-'.$i,
                'student_id' => $student->id,
                'group_id' => $this->group->id,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->group->annee_scolaire_id,
                'statut' => $statut,
                'date_inscription' => '2026-01-10',
                'montant_total' => 0,
            ]);
        }

        $user = $this->superAdmin();
        $expected = [
            'inscriptionsActivesCount' => 2,
            'inscriptionsAnnuleesCount' => 1,
            'inscriptionsChangementCount' => 1,
            'inscriptionsArchiveesCount' => 1,
            'inscriptionsAutresCount' => 2,
        ];

        $this->actingAs($user)
            ->get(route('backoffice.groups.index'))
            ->assertInertia(function (Assert $page) use ($expected) {
                foreach ($expected as $key => $count) {
                    $page->where("groups.data.0.{$key}", $count);
                }
            });

        $this->actingAs($user)
            ->get(route('backoffice.groups.show', $this->group))
            ->assertInertia(function (Assert $page) use ($expected) {
                foreach ($expected as $key => $count) {
                    $page->where("group.{$key}", $count);
                }
            });

        $this->assertSame(7, array_sum($expected));

        $this->actingAs($user)
            ->getJson(route('backoffice.groups.students-by-segment', [$this->group, 'segment' => 'archivee']))
            ->assertOk()
            ->assertJsonCount(1, 'students');
        $this->actingAs($user)
            ->getJson(route('backoffice.groups.students-by-segment', [$this->group, 'segment' => 'autres']))
            ->assertOk()
            ->assertJsonCount(2, 'students');
    }

    public function test_a_group_of_another_centre_is_not_served(): void
    {
        $autre = Group::factory()->create([
            'etablissement_id' => Etablissement::factory()->create()->id,
            'annee_scolaire_id' => $this->group->annee_scolaire_id,
        ]);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('director');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id])
            ->syncEtablissements([$this->centre->id]);

        $this->actingAs($user->fresh())
            ->get(route('backoffice.groups.index', ['edit' => $autre->id]))
            ->assertInertia(fn (Assert $page) => $page->where('editGroup', null));
    }
}
