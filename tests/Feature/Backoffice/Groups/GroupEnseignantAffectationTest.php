<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Domain\Attendance\Actions\GenererSeancesDepuisCreneau;
use App\Models\AnneeScolaire;
use App\Models\Creneau;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Seance;
use App\Models\User;
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
}
