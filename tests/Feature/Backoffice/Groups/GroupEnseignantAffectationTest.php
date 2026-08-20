<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Domain\Attendance\Actions\GenererSeancesDepuisCreneau;
use App\Domain\Groups\Queries\GetGroupFormOptions;
use App\Models\AnneeScolaire;
use App\Models\Creneau;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Seance;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Teacher-assignment history on a group ("affectation prof").
 *
 * The business rule: a group runs with ONE active teacher, but teachers get
 * replaced mid-course. The previous teacher must never be overwritten — each
 * period is kept with its date_debut/date_fin so per-teacher payroll is
 * answerable — and the group's emploi du temps must STOP on changeover so
 * the incoming teacher's séances stay separated from the outgoing one's.
 */
final class GroupEnseignantAffectationTest extends TestCase
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

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function enseignant(): Employee
    {
        return Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
        ]);
    }

    private function group(?Employee $enseignant = null): Group
    {
        return Group::factory()->create([
            'enseignant_id' => $enseignant?->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'date_debut_formation' => '2025-09-01',
            'statut' => Group::STATUT_EN_FORMATION,
        ]);
    }

    private function assignmentActive(Group $group, Employee $enseignant, string $dateDebut): GroupEnseignant
    {
        return GroupEnseignant::create([
            'group_id' => $group->id,
            'enseignant_id' => $enseignant->id,
            'date_debut' => $dateDebut,
            'statut' => GroupEnseignant::STATUT_ACTIF,
        ]);
    }

    public function test_creating_a_group_opens_the_first_assignment_period(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $prof = $this->enseignant();

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'B1 Intensif',
            'niveau' => 'B1.1',
            'enseignant_id' => $prof->id,
            'statut' => Group::STATUT_EN_INSCRIPTION,
            'date_debut_formation' => '2025-09-01',
            'date_fin_formation' => '2026-06-30',
        ])->assertRedirect();

        $group = Group::firstWhere('nom', 'B1 Intensif');

        $this->assertDatabaseHas('group_enseignants', [
            'group_id' => $group->id,
            'enseignant_id' => $prof->id,
            'statut' => GroupEnseignant::STATUT_ACTIF,
            'date_fin' => null,
        ]);
    }

    public function test_changing_the_teacher_archives_the_previous_period_and_opens_a_new_one(): void
    {
        $ancien = $this->enseignant();
        $nouveau = $this->enseignant();
        $group = $this->group($ancien);
        $this->assignmentActive($group, $ancien, '2025-09-01');

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->post(route('backoffice.groups.changer-enseignant', $group), [
                'enseignant_id' => $nouveau->id,
                'date_debut' => '2025-10-01',
                'motif' => 'Indisponibilité',
            ])->assertRedirect(route('backoffice.groups.show', $group));

        // Previous period closed, never deleted — this is the payroll trail.
        $this->assertDatabaseHas('group_enseignants', [
            'group_id' => $group->id,
            'enseignant_id' => $ancien->id,
            'statut' => GroupEnseignant::STATUT_ARCHIVE,
            'date_fin' => '2025-10-01',
        ]);

        $this->assertDatabaseHas('group_enseignants', [
            'group_id' => $group->id,
            'enseignant_id' => $nouveau->id,
            'statut' => GroupEnseignant::STATUT_ACTIF,
            'date_debut' => '2025-10-01',
            'date_fin' => null,
            'motif' => 'Indisponibilité',
        ]);

        // groups.enseignant_id stays a mirror of the active row, so every
        // existing query keeps working.
        $this->assertSame($nouveau->id, $group->fresh()->enseignant_id);
    }

    public function test_only_one_assignment_is_active_at_a_time(): void
    {
        $group = $this->group($premier = $this->enseignant());
        $this->assignmentActive($group, $premier, '2025-09-01');

        $this->actingAs($this->userWith('groups.view', 'groups.update'));

        foreach ([$this->enseignant(), $this->enseignant()] as $i => $prof) {
            $this->post(route('backoffice.groups.changer-enseignant', $group), [
                'enseignant_id' => $prof->id,
                'date_debut' => '2025-1'.($i + 1).'-01',
            ])->assertRedirect();
        }

        $this->assertSame(1, GroupEnseignant::where('group_id', $group->id)
            ->where('statut', GroupEnseignant::STATUT_ACTIF)->count());
        $this->assertSame(3, GroupEnseignant::where('group_id', $group->id)->count());
    }

    public function test_changeover_stops_the_timetable_and_removes_future_planned_seances(): void
    {
        Carbon::setTestNow('2025-10-01');

        $group = $this->group($ancien = $this->enseignant());
        $this->assignmentActive($group, $ancien, '2025-09-01');

        $creneau = Creneau::create([
            'group_id' => $group->id, 'jour_semaine' => 1,
            'heure_debut' => '10:00', 'heure_fin' => '12:00',
            'enseignant_id' => $ancien->id,
        ]);

        $passee = Seance::create([
            'group_id' => $group->id, 'creneau_id' => $creneau->id,
            'date_seance' => '2025-09-15', 'enseignant_id' => $ancien->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);
        $effectuee = Seance::create([
            'group_id' => $group->id, 'creneau_id' => $creneau->id,
            'date_seance' => '2025-10-20', 'enseignant_id' => $ancien->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);
        $future = Seance::create([
            'group_id' => $group->id, 'creneau_id' => $creneau->id,
            'date_seance' => '2025-10-27', 'enseignant_id' => $ancien->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->post(route('backoffice.groups.changer-enseignant', $group), [
                'enseignant_id' => $this->enseignant()->id,
                'date_debut' => '2025-10-15',
            ])->assertRedirect();

        // The outgoing teacher's schedule is closed, not deleted.
        $this->assertSame('2025-10-15', $creneau->fresh()->date_fin?->toDateString());

        // Only future untouched séances go — real activity survives.
        $this->assertModelMissing($future);
        $this->assertModelExists($passee);
        $this->assertModelExists($effectuee);

        Carbon::setTestNow();
    }

    public function test_a_closed_creneau_never_regenerates_seances(): void
    {
        Carbon::setTestNow('2025-10-01');

        $group = $this->group($this->enseignant());
        $creneau = Creneau::create([
            'group_id' => $group->id, 'jour_semaine' => 1,
            'heure_debut' => '10:00', 'heure_fin' => '12:00',
            'date_fin' => '2025-10-15',
        ]);

        app(GenererSeancesDepuisCreneau::class)->generer($creneau);

        $this->assertSame(0, Seance::where('creneau_id', $creneau->id)->count());

        Carbon::setTestNow();
    }

    public function test_saving_the_edit_modal_without_touching_the_teacher_does_not_stop_the_timetable(): void
    {
        Carbon::setTestNow('2025-10-01');

        $group = $this->group($prof = $this->enseignant());
        $this->assignmentActive($group, $prof, '2025-09-01');
        $creneau = Creneau::create([
            'group_id' => $group->id, 'jour_semaine' => 1,
            'heure_debut' => '10:00', 'heure_fin' => '12:00',
            'enseignant_id' => $prof->id,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.update', $group), [
                'nom' => 'Nom modifié',
                'niveau' => $group->niveau,
                'enseignant_id' => $prof->id,
                'statut' => $group->statut,
                'date_debut_formation' => '2025-09-01',
                'date_fin_formation' => '2026-06-30',
            ])->assertRedirect();

        $this->assertNull($creneau->fresh()->date_fin);
        $this->assertSame(1, GroupEnseignant::where('group_id', $group->id)->count());

        Carbon::setTestNow();
    }

    public function test_changing_the_teacher_requires_the_groups_update_permission(): void
    {
        $group = $this->group($this->enseignant());

        $this->actingAs($this->userWith('groups.view'))
            ->post(route('backoffice.groups.changer-enseignant', $group), [
                'enseignant_id' => $this->enseignant()->id,
                'date_debut' => '2025-10-01',
            ])->assertForbidden();
    }

    public function test_assigning_a_teacher_from_the_edit_modal_opens_an_assignment_period(): void
    {
        // A group created without a teacher, then given one through the plain
        // edit modal (not the dedicated changeover screen): the assignment
        // history must start there too.
        $group = $this->group(null);
        $prof = $this->enseignant();

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.update', $group), [
                'nom' => $group->nom,
                'niveau' => $group->niveau,
                'enseignant_id' => $prof->id,
                'statut' => $group->statut,
                'date_debut_formation' => '2025-09-01',
                'date_fin_formation' => '2026-06-30',
            ])->assertRedirect();

        $this->assertDatabaseHas('group_enseignants', [
            'group_id' => $group->id,
            'enseignant_id' => $prof->id,
            'statut' => GroupEnseignant::STATUT_ACTIF,
            'date_fin' => null,
        ]);
        $this->assertSame($prof->id, $group->fresh()->enseignant_id);
    }

    public function test_replacing_the_teacher_from_the_edit_modal_archives_the_previous_period(): void
    {
        $ancien = $this->enseignant();
        $nouveau = $this->enseignant();
        $group = $this->group($ancien);
        $this->assignmentActive($group, $ancien, '2025-09-01');

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.update', $group), [
                'nom' => $group->nom,
                'niveau' => $group->niveau,
                'enseignant_id' => $nouveau->id,
                'statut' => $group->statut,
                'date_debut_formation' => '2025-09-01',
                'date_fin_formation' => '2026-06-30',
            ])->assertRedirect();

        $this->assertDatabaseHas('group_enseignants', [
            'group_id' => $group->id,
            'enseignant_id' => $ancien->id,
            'statut' => GroupEnseignant::STATUT_ARCHIVE,
        ]);
        $this->assertDatabaseHas('group_enseignants', [
            'group_id' => $group->id,
            'enseignant_id' => $nouveau->id,
            'statut' => GroupEnseignant::STATUT_ACTIF,
            'date_fin' => null,
        ]);
        $this->assertSame(2, GroupEnseignant::where('group_id', $group->id)->count());
    }

    public function test_the_edit_modal_records_the_changeover_date_and_motif_like_the_detail_page(): void
    {
        // The modal reveals "Date de prise en charge" + "Motif" as soon as the
        // selected teacher differs from the saved one, so a swap made from the
        // list page lands in "Historique des affectations" with the same
        // information the dedicated « Changer d'enseignant » form records —
        // not an undated, unexplained row.
        Carbon::setTestNow('2025-11-20');

        $ancien = $this->enseignant();
        $nouveau = $this->enseignant();
        $group = $this->group($ancien);
        $this->assignmentActive($group, $ancien, '2025-09-01');

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.update', $group), [
                'nom' => $group->nom,
                'niveau' => $group->niveau,
                'enseignant_id' => $nouveau->id,
                'enseignant_date_debut' => '2025-10-15',
                'enseignant_motif' => "indisponibilité de l'enseignant",
                'statut' => $group->statut,
                'date_debut_formation' => '2025-09-01',
                'date_fin_formation' => '2026-06-30',
            ])->assertRedirect();

        // The outgoing period closes on the chosen date, not on "today".
        $archive = GroupEnseignant::where('group_id', $group->id)
            ->where('enseignant_id', $ancien->id)->sole();
        $this->assertSame(GroupEnseignant::STATUT_ARCHIVE, $archive->statut);
        $this->assertSame('2025-10-15', $archive->date_fin->toDateString());

        // The incoming period opens on it, carrying the motif.
        $actif = GroupEnseignant::where('group_id', $group->id)
            ->where('enseignant_id', $nouveau->id)->sole();
        $this->assertSame(GroupEnseignant::STATUT_ACTIF, $actif->statut);
        $this->assertSame('2025-10-15', $actif->date_debut->toDateString());
        $this->assertSame("indisponibilité de l'enseignant", $actif->motif);
        $this->assertNull($actif->date_fin);

        Carbon::setTestNow();
    }

    public function test_the_edit_modal_changeover_stops_the_timetable_and_reports_it(): void
    {
        Carbon::setTestNow('2025-11-20');

        $ancien = $this->enseignant();
        $nouveau = $this->enseignant();
        $group = $this->group($ancien);
        $this->assignmentActive($group, $ancien, '2025-09-01');

        $creneau = Creneau::create([
            'group_id' => $group->id, 'jour_semaine' => 1,
            'heure_debut' => '10:00', 'heure_fin' => '12:00',
            'enseignant_id' => $ancien->id,
        ]);
        $future = Seance::create([
            'group_id' => $group->id, 'creneau_id' => $creneau->id,
            'date_seance' => '2025-12-01', 'enseignant_id' => $ancien->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.update', $group), [
                'nom' => $group->nom,
                'niveau' => $group->niveau,
                'enseignant_id' => $nouveau->id,
                'enseignant_date_debut' => '2025-11-20',
                'statut' => $group->statut,
                'date_debut_formation' => '2025-09-01',
                'date_fin_formation' => '2026-06-30',
            ])
            ->assertRedirect()
            // Same one-time notice as the detail-page flow, so the user knows
            // a new emploi du temps has to be built.
            ->assertSessionHas('emploiDuTempsArrete');

        $this->assertSame('2025-11-20', $creneau->fresh()->date_fin->toDateString());
        $this->assertModelMissing($future);

        Carbon::setTestNow();
    }

    public function test_teacher_options_include_teachers_attached_to_the_center_through_the_pivot(): void
    {
        // Primary center is ANOTHER branch, but the teacher is assigned to the
        // active one through employee_etablissement — the group form must still
        // offer them, or no assignment period can ever be opened for them here.
        $autreCentre = Etablissement::factory()->create();
        $prof = Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $autreCentre->id,
        ]);
        $prof->etablissements()->sync([$autreCentre->id, $this->centre->id]);

        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->assertContains(
            $prof->id,
            app(GetGroupFormOptions::class)->enseignants()->pluck('id')->all(),
        );
    }

    public function test_an_archived_assignment_period_can_be_corrected_afterwards(): void
    {
        // The changeover stamps today as the outgoing teacher date de fin; the
        // real handover may have been another day, so the row stays editable —
        // dates and motif only.
        $prof = $this->enseignant();
        $group = $this->group($prof);
        $affectation = $this->assignmentActive($group, $prof, '2025-09-01');
        $affectation->update([
            'date_fin' => '2025-10-01',
            'statut' => GroupEnseignant::STATUT_ARCHIVE,
        ]);

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.affectations.update', [$group, $affectation]), [
                'date_debut' => '2025-09-15',
                'date_fin' => '2025-11-20',
                'motif' => 'Date de passation corrigee',
            ])->assertRedirect();

        $affectation->refresh();
        $this->assertSame('2025-09-15', $affectation->date_debut->toDateString());
        $this->assertSame('2025-11-20', $affectation->date_fin->toDateString());
        $this->assertSame('Date de passation corrigee', $affectation->motif);
        // The teacher of the row is never swapped by this endpoint.
        $this->assertSame($prof->id, $affectation->enseignant_id);
    }

    public function test_correcting_the_active_period_keeps_it_open_ended(): void
    {
        $prof = $this->enseignant();
        $group = $this->group($prof);
        $affectation = $this->assignmentActive($group, $prof, '2025-09-01');

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.affectations.update', [$group, $affectation]), [
                'date_debut' => '2025-09-08',
                // Even if a date_fin is posted, the running period stays open.
                'date_fin' => '2025-12-31',
                'motif' => null,
            ])->assertRedirect();

        $affectation->refresh();
        $this->assertSame('2025-09-08', $affectation->date_debut->toDateString());
        $this->assertNull($affectation->date_fin);
        $this->assertTrue($affectation->isActif());
    }

    public function test_a_period_cannot_end_before_it_begins(): void
    {
        $prof = $this->enseignant();
        $group = $this->group($prof);
        $affectation = $this->assignmentActive($group, $prof, '2025-09-01');
        $affectation->update(['date_fin' => '2025-10-01', 'statut' => GroupEnseignant::STATUT_ARCHIVE]);

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.affectations.update', [$group, $affectation]), [
                'date_debut' => '2025-10-01',
                'date_fin' => '2025-09-01',
            ])->assertSessionHasErrors('date_fin');
    }

    public function test_correcting_an_assignment_period_requires_the_groups_update_permission(): void
    {
        $prof = $this->enseignant();
        $group = $this->group($prof);
        $affectation = $this->assignmentActive($group, $prof, '2025-09-01');

        $this->actingAs($this->userWith('groups.view'))
            ->put(route('backoffice.groups.affectations.update', [$group, $affectation]), [
                'date_debut' => '2025-09-08',
            ])->assertForbidden();
    }

    public function test_an_assignment_of_another_group_cannot_be_edited_through_this_group(): void
    {
        $prof = $this->enseignant();
        $group = $this->group($prof);
        $autreGroup = $this->group($prof);
        $affectationAutre = $this->assignmentActive($autreGroup, $prof, '2025-09-01');

        $this->actingAs($this->userWith('groups.view', 'groups.update'))
            ->put(route('backoffice.groups.affectations.update', [$group, $affectationAutre]), [
                'date_debut' => '2025-09-08',
            ])->assertNotFound();
    }
}
