<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Groups endpoints (GroupController) built
 * alongside the unchanged Livewire GroupsIndex fallback — see
 * GroupsCrudTest for the Livewire-side coverage of the same business rules
 * (docs/phase-8-students-groups-inventory.md). Deliberately has NO
 * room/capacity/schedule coverage — those fields do not exist in the
 * current UI (see the inventory doc's §Q on scope).
 */
final class GroupsInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    /**
     * Grants centers.access-all alongside the requested permissions so these
     * tests (which create groups across arbitrary/no specific center) aren't
     * blocked by GroupPolicy::withinCenter() — center-scoping itself is
     * covered separately by test_update_is_center_scoped_for_non_global_users.
     */
    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    public function test_index_requires_groups_view_and_renders_the_react_page(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.groups.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('groups.view'))
            ->get(route('backoffice.groups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Groups/Index', false)
                ->has('groups')
                ->has('statutCounts')
                ->has('niveaux')
                ->has('enseignants')
                ->has('fraisCatalog')
                ->where('filters.statutFilter', Group::STATUT_EN_FORMATION)
            );
    }

    public function test_a_group_can_be_created_with_all_catalog_fees(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $f1 = Frais::create(['nom' => "Frais d'inscription", 'statut' => 'Actif']);
        $f2 = Frais::create(['nom' => 'Frais de Juillet', 'statut' => 'Actif']);

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'Herr Driss 13h - Intensifs',
            'niveau' => 'B1.1',
            'statut' => Group::STATUT_EN_INSCRIPTION,
            'date_debut_formation' => '2025-09-01',
            'date_fin_formation' => '2026-06-30',
            'fraisLignes' => [
                $f1->id => ['montant' => '300'],
                $f2->id => ['montant' => '1300'],
            ],
        ])->assertRedirect(route('backoffice.groups.index'));

        $group = Group::where('nom', 'Herr Driss 13h - Intensifs')->firstOrFail();
        // Center + academic year come from the active working context, not the form.
        $this->assertNotNull($group->annee_scolaire_id);
        $this->assertSame(2, $group->frais()->count());
        $this->assertEqualsCanonicalizing([300.0, 1300.0], $group->frais->pluck('pivot.montant')->map(fn ($m) => (float) $m)->all());
    }

    public function test_fees_trashed_before_saving_are_not_attached_to_the_new_group(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $f1 = Frais::create(['nom' => "Frais d'inscription", 'statut' => 'Actif']);
        $f2 = Frais::create(['nom' => "Frais d'exam ÖSD B2", 'statut' => 'Actif']);

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'Groupe sans examen',
            'niveau' => 'A1.1',
            'statut' => Group::STATUT_EN_INSCRIPTION,
            'date_debut_formation' => '2025-09-01',
            'date_fin_formation' => '2026-06-30',
            'fraisLignes' => [
                $f1->id => ['montant' => '300'],
            ],
            'fraisRetires' => [$f2->id],
        ])->assertSessionDoesntHaveErrors();

        $group = Group::where('nom', 'Groupe sans examen')->firstOrFail();
        $this->assertSame([$f1->id], $group->frais()->pluck('frais.id')->all());
    }

    public function test_fees_left_at_zero_are_still_saved(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $f1 = Frais::create(['nom' => "Frais d'inscription", 'statut' => 'Actif']);
        $f2 = Frais::create(['nom' => 'Frais de Juillet', 'statut' => 'Actif']);

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'Groupe Zéro',
            'niveau' => 'A1.1',
            'statut' => Group::STATUT_EN_INSCRIPTION,
            'date_debut_formation' => '2025-09-01',
            'date_fin_formation' => '2026-06-30',
            'fraisLignes' => [
                $f1->id => ['montant' => '300'],
            ],
        ])->assertSessionDoesntHaveErrors();

        $group = Group::where('nom', 'Groupe Zéro')->firstOrFail();
        $this->assertSame(2, $group->frais()->count());
        $this->assertEquals(0.0, (float) $group->frais->firstWhere('id', $f2->id)->pivot->montant);
    }

    public function test_niveau_is_required(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'X', 'niveau' => '', 'statut' => Group::STATUT_EN_INSCRIPTION,
        ])->assertSessionHasErrors('niveau');
    }

    public function test_a_fee_can_be_assigned_with_a_due_date_and_classification(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $frais = Frais::create(['nom' => 'Frais annuel', 'statut' => 'Actif']);

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'Groupe X', 'niveau' => 'A1.1', 'statut' => Group::STATUT_EN_INSCRIPTION,
            'date_debut_formation' => '2025-09-01', 'date_fin_formation' => '2026-06-30',
            'fraisLignes' => [
                $frais->id => ['montant' => '500', 'date_echeance' => '2025-10-18', 'classification' => 'B2.1'],
            ],
        ])->assertSessionDoesntHaveErrors();

        $group = Group::where('nom', 'Groupe X')->firstOrFail();
        $this->assertSame('2025-10-18', $group->frais->first()->pivot->date_echeance);
        $this->assertSame('B2.1', $group->frais->first()->pivot->classification);
    }

    public function test_a_fee_classification_must_be_a_known_niveau(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $frais = Frais::create(['nom' => 'Frais A', 'statut' => 'Actif']);

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'Groupe X', 'niveau' => 'A1.1', 'statut' => Group::STATUT_EN_INSCRIPTION,
            'fraisLignes' => [$frais->id => ['montant' => '100', 'classification' => 'Z9']],
        ])->assertSessionHasErrors("fraisLignes.{$frais->id}.classification");
    }

    public function test_a_fee_amount_is_required(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $frais = Frais::create(['nom' => 'Frais A', 'statut' => 'Actif']);

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'Groupe X', 'niveau' => 'A1.1', 'statut' => Group::STATUT_EN_INSCRIPTION,
            'fraisLignes' => [$frais->id => ['montant' => '']],
        ])->assertSessionHasErrors("fraisLignes.{$frais->id}.montant");
    }

    public function test_editing_updates_fee_amounts_and_keeps_all_fees_assigned(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.update'));
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $a = Frais::create(['nom' => 'Frais A', 'statut' => 'Actif']);
        $b = Frais::create(['nom' => 'Frais B', 'statut' => 'Actif']);
        $group->frais()->attach([$a->id => ['montant' => 100], $b->id => ['montant' => 200]]);

        $this->put(route('backoffice.groups.update', $group), [
            'nom' => $group->nom, 'niveau' => $group->niveau, 'statut' => $group->statut,
            'date_debut_formation' => '2025-09-01', 'date_fin_formation' => '2026-06-30',
            'fraisLignes' => [
                $a->id => ['montant' => '100'],
                $b->id => ['montant' => '250'],
            ],
        ])->assertSessionDoesntHaveErrors();

        $fresh = $group->fresh();
        $this->assertSame(2, $fresh->frais()->count());
        $this->assertEquals(250.0, (float) $fresh->frais->firstWhere('id', $b->id)->pivot->montant);
    }

    public function test_a_raw_transition_to_fin_de_formation_is_silently_reverted(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.update'));
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
        ]);

        $this->put(route('backoffice.groups.update', $group), [
            'nom' => $group->nom, 'niveau' => $group->niveau,
            'statut' => Group::STATUT_FIN_FORMATION,
            'date_debut_formation' => '2025-09-01', 'date_fin_formation' => '2026-06-30',
        ])->assertSessionDoesntHaveErrors();

        // The raw update must NOT actually finish the group — only
        // Group::archiverCommeTermine() (archive route) may do that.
        $this->assertSame(Group::STATUT_EN_FORMATION, $group->fresh()->statut);
        $this->assertNull($group->fresh()->historique);
    }

    public function test_groups_cannot_be_deleted_and_have_no_destroy_route(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.groups.destroy'));
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->actingAs($this->userWith('groups.view'));

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'X', 'niveau' => 'A1.1', 'statut' => Group::STATUT_EN_INSCRIPTION,
        ])->assertForbidden();
    }

    public function test_update_is_center_scoped_for_non_global_users(): void
    {
        $centerA = Etablissement::factory()->create();
        $centerB = Etablissement::factory()->create();

        $groupInB = Group::factory()->create(['etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id]);

        // Deliberately NOT using userWith() here — that helper grants
        // centers.access-all for the other tests' convenience, which would
        // defeat this exact center-scoping check.
        $lockedAdmin = User::factory()->create();
        $lockedAdmin->givePermissionTo('groups.view', 'groups.update');
        $lockedAdmin->employee()->save(Employee::factory()->make(['etablissement_id' => $centerA->id]));
        $this->actingAs($lockedAdmin->fresh());

        $this->put(route('backoffice.groups.update', $groupInB), [
            'nom' => $groupInB->nom, 'niveau' => $groupInB->niveau, 'statut' => $groupInB->statut,
            'date_debut_formation' => '2025-09-01', 'date_fin_formation' => '2026-06-30',
        ])->assertForbidden();
    }

    public function test_list_row_carries_this_groups_own_fee_lines_for_the_edit_modal(): void
    {
        $this->actingAs($this->userWith('groups.view'));
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
        ]);
        $frais = Frais::create(['nom' => 'Frais A', 'statut' => 'Actif']);
        $group->frais()->attach($frais->id, ['montant' => 150, 'classification' => 'B1.1']);

        $this->get(route('backoffice.groups.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where("groups.data.0.fraisLignes.{$frais->id}.montant", '150.00')
                ->where("groups.data.0.fraisLignes.{$frais->id}.classification", 'B1.1')
            );
    }

    /**
     * Covers the "Statistique" badges' drill-down (GetGroupStudentsBySegment):
     * total/active/annulée each filter by inscription statut, "étudiants"
     * dedupes a student who re-enrolled twice into one row.
     */
    public function test_students_by_segment_filters_correctly(): void
    {
        $this->actingAs($this->userWith('groups.view'));
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
        ]);

        $studentActive = \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $studentAnnulee = \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id]);

        \App\Models\Inscription::create([
            'reference' => 'INS-ACTIVE', 'student_id' => $studentActive->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => \App\Models\Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
        \App\Models\Inscription::create([
            'reference' => 'INS-ANNULEE', 'student_id' => $studentAnnulee->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => \App\Models\Inscription::STATUT_ANNULEE, 'date_inscription' => '2025-09-16',
        ]);
        // Same student re-enrolled — "étudiants" must dedupe this to 1, not 2.
        \App\Models\Inscription::create([
            'reference' => 'INS-ACTIVE-2', 'student_id' => $studentActive->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => \App\Models\Inscription::STATUT_CHANGEMENT, 'date_inscription' => '2025-09-20',
        ]);

        $total = $this->get(route('backoffice.groups.students-by-segment', $group).'?segment=total')->json('students');
        $this->assertCount(3, $total);

        $active = $this->get(route('backoffice.groups.students-by-segment', $group).'?segment=active')->json('students');
        $this->assertCount(1, $active);
        $this->assertSame($studentActive->reference, $active[0]['reference']);

        $annulee = $this->get(route('backoffice.groups.students-by-segment', $group).'?segment=annulee')->json('students');
        $this->assertCount(1, $annulee);
        $this->assertSame($studentAnnulee->reference, $annulee[0]['reference']);

        $etudiants = $this->get(route('backoffice.groups.students-by-segment', $group).'?segment=etudiants')->json('students');
        $this->assertCount(2, $etudiants);
    }

    public function test_students_by_segment_requires_view_permission(): void
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.groups.students-by-segment', $group))
            ->assertForbidden();
    }

    // --- annuler / réactiver (row-menu quick lifecycle actions) ---------

    public function test_annuler_sets_statut_to_annulee_and_writes_historique(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.annuler', $group))
            ->assertRedirect(route('backoffice.groups.index'));

        $fresh = $group->fresh();
        $this->assertSame(Group::STATUT_ANNULEE, $fresh->statut);
        $this->assertNotNull($fresh->historique);
    }

    public function test_annuler_is_refused_from_an_already_terminal_status(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $group->archiverCommeTermine();

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.annuler', $group))
            ->assertRedirect();

        $this->assertSame(Group::STATUT_FIN_FORMATION, $group->fresh()->statut);
    }

    public function test_annuler_requires_groups_archive_permission(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view'))
            ->post(route('backoffice.groups.annuler', $group))
            ->assertForbidden();

        $this->assertSame(Group::STATUT_EN_FORMATION, $group->fresh()->statut);
    }

    public function test_reactiver_sets_statut_back_to_en_inscription(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_ANNULEE,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.reactiver', $group))
            ->assertRedirect(route('backoffice.groups.index'));

        $this->assertSame(Group::STATUT_EN_INSCRIPTION, $group->fresh()->statut);
    }

    public function test_reactiver_is_refused_from_fin_de_formation(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $group->archiverCommeTermine();

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.reactiver', $group))
            ->assertRedirect();

        $this->assertSame(Group::STATUT_FIN_FORMATION, $group->fresh()->statut);
    }

    public function test_activer_sets_statut_to_en_formation(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_INSCRIPTION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.activer', $group))
            ->assertRedirect(route('backoffice.groups.index'));

        $this->assertSame(Group::STATUT_EN_FORMATION, $group->fresh()->statut);
    }

    public function test_activer_is_refused_when_not_en_inscription(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.activer', $group))
            ->assertRedirect();

        $this->assertSame(Group::STATUT_EN_FORMATION, $group->fresh()->statut);
    }

    public function test_activer_requires_groups_archive_permission(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_INSCRIPTION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view'))
            ->post(route('backoffice.groups.activer', $group))
            ->assertForbidden();

        $this->assertSame(Group::STATUT_EN_INSCRIPTION, $group->fresh()->statut);
    }

    public function test_retourner_en_inscription_sets_statut_back_to_en_inscription(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.retourner-en-inscription', $group))
            ->assertRedirect(route('backoffice.groups.index'));

        $this->assertSame(Group::STATUT_EN_INSCRIPTION, $group->fresh()->statut);
    }

    public function test_retourner_en_inscription_is_refused_when_not_en_formation(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_INSCRIPTION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.archive'))
            ->post(route('backoffice.groups.retourner-en-inscription', $group))
            ->assertRedirect();

        $this->assertSame(Group::STATUT_EN_INSCRIPTION, $group->fresh()->statut);
    }

    public function test_retourner_en_inscription_requires_groups_archive_permission(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view'))
            ->post(route('backoffice.groups.retourner-en-inscription', $group))
            ->assertForbidden();

        $this->assertSame(Group::STATUT_EN_FORMATION, $group->fresh()->statut);
    }

    public function test_a_group_can_be_cancelled_reactivated_and_cancelled_again_without_error(): void
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $group->annuler();
        $this->assertSame(Group::STATUT_ANNULEE, $group->fresh()->statut);

        $group->reactiver();
        $this->assertSame(Group::STATUT_EN_INSCRIPTION, $group->fresh()->statut);

        $group->annuler();
        $this->assertSame(Group::STATUT_ANNULEE, $group->fresh()->statut);

        // historique() is a HasOne — the second annuler() must refresh the
        // same snapshot row, not error or create a duplicate.
        $this->assertSame(1, $group->historique()->count());
    }

    public function test_historique_tab_combines_fin_de_formation_and_annulee_groups(): void
    {
        $finished = Group::factory()->create([
            'nom' => 'Groupe Terminé', 'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $finished->archiverCommeTermine();

        $cancelled = Group::factory()->create([
            'nom' => 'Groupe Annulé', 'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $cancelled->annuler();

        Group::factory()->create([
            'nom' => 'Groupe Actif', 'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->userWith('groups.view'))
            ->get(route('backoffice.groups.index', ['statutFilter' => Group::STATUT_FIN_FORMATION]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('groups.total', 2)
                ->where('statutCounts.Fin de formation', 2)
            );
    }
}
