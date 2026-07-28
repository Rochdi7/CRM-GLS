<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Livewire\Backoffice\Groups\GroupsIndex;
use App\Livewire\Backoffice\Settings\FraisTab;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

final class GroupsCrudTest extends TestCase
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

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    public function test_page_requires_groups_view(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.groups.index'))->assertForbidden();

        $u2 = User::factory()->create();
        $u2->givePermissionTo('groups.view');
        $this->actingAs($u2)->get(route('backoffice.groups.index'))->assertOk();
    }

    public function test_status_tabs_filter_the_groups_list(): void
    {
        $this->admin();
        Group::factory()->create(['nom' => 'Groupe Actif', 'statut' => Group::STATUT_EN_FORMATION, 'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        Group::factory()->create(['nom' => 'Groupe Attente', 'statut' => Group::STATUT_PRE_INSCRIPTION, 'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        Group::factory()->create(['nom' => 'Groupe Fini', 'statut' => Group::STATUT_FIN_FORMATION, 'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);

        // Default tab = En formation.
        Livewire::test(GroupsIndex::class)
            ->assertSet('statutFilter', Group::STATUT_EN_FORMATION)
            ->assertSee('Groupe Actif')
            ->assertDontSee('Groupe Attente')
            ->assertDontSee('Groupe Fini')
            // Pré-inscription tab.
            ->call('setStatutTab', Group::STATUT_PRE_INSCRIPTION)
            ->assertSee('Groupe Attente')
            ->assertDontSee('Groupe Actif')
            // Historique tab (groups ended).
            ->call('setStatutTab', Group::STATUT_FIN_FORMATION)
            ->assertSee('Groupe Fini')
            ->assertDontSee('Groupe Actif')
            // Unknown status is ignored.
            ->call('setStatutTab', 'Nimporte')
            ->assertSet('statutFilter', Group::STATUT_FIN_FORMATION);
    }

    public function test_a_group_carries_all_catalog_fees_with_per_group_amounts(): void
    {
        $this->admin();
        $f1 = Frais::create(['nom' => 'Frais d\'inscription', 'statut' => 'Actif']);
        $f2 = Frais::create(['nom' => 'Frais de Juillet', 'statut' => 'Actif']);

        $component = Livewire::test(GroupsIndex::class)
            ->call('create')
            ->set('nom', 'Herr Driss 13h - Intensifs')
            ->set('niveau', 'B1.1');

        // create() seeds a fraisLignes entry per active catalog fee, each
        // defaulting to 0 (no checkbox) — the user enters the amount per group.
        $this->assertSame('0', $component->get("fraisLignes.{$f1->id}.montant"));

        $component->set("fraisLignes.{$f1->id}.montant", '300')
            ->set("fraisLignes.{$f2->id}.montant", '1300')
            ->call('save')
            ->assertHasNoErrors();

        $group = Group::where('nom', 'Herr Driss 13h - Intensifs')->first();
        $this->assertNotNull($group);
        // Center + academic year come from the active working context, not the form.
        $this->assertSame($this->annee->id, $group->annee_scolaire_id);
        // ALL catalog fees are assigned to the group (even 0 DH ones).
        $this->assertSame(2, $group->frais()->count());
        $this->assertEqualsCanonicalizing([300.0, 1300.0], $group->frais->pluck('pivot.montant')->map(fn ($m) => (float) $m)->all());
    }

    public function test_fees_left_at_zero_are_still_saved_on_the_group(): void
    {
        $this->admin();
        $f1 = Frais::create(['nom' => 'Frais d\'inscription', 'statut' => 'Actif']);
        $f2 = Frais::create(['nom' => 'Frais de Juillet', 'statut' => 'Actif']);

        Livewire::test(GroupsIndex::class)
            ->call('create')
            ->set('nom', 'Groupe Zéro')
            ->set('niveau', 'A1.1')
            // Only fill f1; f2 stays at its default 0.
            ->set("fraisLignes.{$f1->id}.montant", '300')
            ->call('save')
            ->assertHasNoErrors();

        $group = Group::where('nom', 'Groupe Zéro')->first();
        // Both fees are stored; the untouched one keeps 0 DH.
        $this->assertSame(2, $group->frais()->count());
        $this->assertEquals(0.0, (float) $group->frais->firstWhere('id', $f2->id)->pivot->montant);
    }

    public function test_niveau_is_required(): void
    {
        $this->admin();

        Livewire::test(GroupsIndex::class)
            ->call('create')
            ->set('nom', 'X')
            ->set('niveau', '')
            ->call('save')
            ->assertHasErrors('niveau');
    }

    public function test_a_fee_can_be_assigned_with_a_per_group_due_date(): void
    {
        $this->admin();
        $frais = Frais::create(['nom' => 'Frais annuel', 'statut' => 'Actif']);

        $component = Livewire::test(GroupsIndex::class)
            ->call('create')
            ->set('nom', 'Groupe X')
            ->set('niveau', 'A1.1');

        $component->set("fraisLignes.{$frais->id}.montant", '500')
            ->set("fraisLignes.{$frais->id}.date_echeance", '2025-10-18')
            ->call('save')
            ->assertHasNoErrors();

        $group = Group::where('nom', 'Groupe X')->first();
        $this->assertSame('2025-10-18', $group->frais->first()->pivot->date_echeance);
    }

    public function test_a_fee_can_be_classified_with_a_niveau_on_the_group(): void
    {
        $this->admin();
        $frais = Frais::create(['nom' => 'Frais d\'inscription B2', 'statut' => 'Actif']);

        Livewire::test(GroupsIndex::class)
            ->call('create')
            ->set('nom', 'Groupe B2')
            ->set('niveau', 'B2.1')
            ->set("fraisLignes.{$frais->id}.montant", '800')
            ->set("fraisLignes.{$frais->id}.classification", 'B2.1')
            ->call('save')
            ->assertHasNoErrors();

        $group = Group::where('nom', 'Groupe B2')->first();
        $this->assertSame('B2.1', $group->frais->first()->pivot->classification);

        // The classification is prefilled when reopening the edit modal.
        Livewire::test(GroupsIndex::class)
            ->call('edit', $group->id)
            ->assertSet("fraisLignes.{$frais->id}.classification", 'B2.1');
    }

    public function test_a_fee_classification_must_be_a_known_niveau(): void
    {
        $this->admin();
        $frais = Frais::create(['nom' => 'Frais A', 'statut' => 'Actif']);

        Livewire::test(GroupsIndex::class)
            ->call('create')
            ->set('nom', 'Groupe X')
            ->set('niveau', 'A1.1')
            ->set("fraisLignes.{$frais->id}.classification", 'Z9')
            ->call('save')
            ->assertHasErrors("fraisLignes.{$frais->id}.classification");
    }

    public function test_editing_updates_the_per_group_fee_amounts(): void
    {
        $this->admin();
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $a = Frais::create(['nom' => 'Frais A', 'statut' => 'Actif']);
        $b = Frais::create(['nom' => 'Frais B', 'statut' => 'Actif']);
        $group->frais()->attach([$a->id => ['montant' => 100], $b->id => ['montant' => 200]]);

        // Editing changes an amount; all fees stay assigned (no checkbox).
        Livewire::test(GroupsIndex::class)
            ->call('edit', $group->id)
            ->set("fraisLignes.{$b->id}.montant", '250')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $group->fresh();
        $this->assertSame(2, $fresh->frais()->count());
        $this->assertEquals(250.0, (float) $fresh->frais->firstWhere('id', $b->id)->pivot->montant);
    }

    public function test_a_fee_amount_is_required_on_the_group(): void
    {
        $this->admin();
        $frais = Frais::create(['nom' => 'Frais A', 'statut' => 'Actif']);

        // Clearing a fee amount is invalid — every fee needs a montant (0 default).
        Livewire::test(GroupsIndex::class)
            ->call('create')
            ->set('nom', 'Groupe X')
            ->set('niveau', 'A1.1')
            ->set("fraisLignes.{$frais->id}.montant", '')
            ->call('save')
            ->assertHasErrors("fraisLignes.{$frais->id}.montant");
    }

    public function test_groups_cannot_be_deleted_and_have_no_destroy_route(): void
    {
        // There is no groups.destroy route by design (schema §6).
        $this->assertFalse(Route::has('backoffice.groups.destroy'));
    }

    public function test_ending_training_archives_the_group(): void
    {
        $admin = $this->admin();
        // link an employee so archived_by resolves
        Employee::factory()->create(['user_id' => $admin->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($admin->fresh());

        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
        ]);

        $this->post(route('backoffice.groups.archive', $group))
            ->assertRedirect(route('backoffice.groups.show', $group));

        $this->assertSame(Group::STATUT_FIN_FORMATION, $group->fresh()->statut);
        $this->assertNotNull($group->fresh()->historique);
    }

    // --- Frais catalog (Settings tab) --------------------------------------

    public function test_a_catalog_fee_can_be_created(): void
    {
        $this->admin();

        Livewire::test(FraisTab::class)
            ->call('create')
            ->set('nom', 'Frais dexam ÖSD A1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('frais', ['nom' => 'Frais dexam ÖSD A1', 'statut' => 'Actif']);
    }

    public function test_a_fee_assigned_to_a_group_cannot_be_deleted(): void
    {
        $this->admin();
        $frais = Frais::create(['nom' => 'Used fee', 'statut' => 'Actif']);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $group->frais()->attach($frais->id, ['montant' => 300]);

        Livewire::test(FraisTab::class)
            ->call('delete', $frais->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('frais', ['id' => $frais->id]);
    }
}
