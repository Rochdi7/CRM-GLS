<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\MotifAnnulation;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Attendance module (Présences) — séances CRUD + the fiche de présence
 * roll-call endpoint (SeanceController / EnregistrerPresences).
 */
final class SeancesInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /**
     * Enrolls a fresh student in the test group (active inscription).
     */
    private function enrollStudent(): Student
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        Inscription::create([
            'reference' => 'INS-TEST-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => now()->toDateString(),
        ]);

        return $student;
    }

    private function makeSeance(array $attributes = []): Seance
    {
        return Seance::create(array_merge([
            'group_id' => $this->group->id,
            'date_seance' => '2026-03-02',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ], $attributes));
    }

    public function test_index_requires_attendance_view_and_renders_the_react_page(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.seances.index'))
            ->assertForbidden();

        $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/Index', false)
                ->has('seances.data', 1)
                ->has('groupOptions')
                ->has('enseignants')
                ->has('statuts')
                ->where('permissions.create', false)
            );
    }

    public function test_a_seance_inherits_center_and_year_from_its_group(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create'));

        $this->post(route('backoffice.seances.store'), [
            'group_id' => $this->group->id,
            'date_seance' => '2026-03-05',
            'heure_debut' => '13:00',
            'heure_fin' => '15:00',
            'statut' => Seance::STATUT_PREVUE,
        ])->assertRedirect(route('backoffice.seances.index'));

        $seance = Seance::firstOrFail();
        $this->assertSame($this->centre->id, $seance->etablissement_id);
        $this->assertSame($this->annee->id, $seance->annee_scolaire_id);
        // No teacher submitted — the group's own teacher is the default.
        $this->assertSame($this->group->enseignant_id, $seance->enseignant_id);
    }

    public function test_store_requires_attendance_create(): void
    {
        $this->actingAs($this->userWith('attendance.view'))
            ->post(route('backoffice.seances.store'), [
                'group_id' => $this->group->id,
                'date_seance' => '2026-03-05',
                'statut' => Seance::STATUT_PREVUE,
            ])->assertForbidden();
    }

    public function test_a_seance_can_be_updated_and_deleted(): void
    {
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.update', 'attendance.delete'));

        $this->put(route('backoffice.seances.update', $seance), [
            'date_seance' => '2026-03-09',
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
            'statut' => Seance::STATUT_ANNULEE,
            'note' => 'Jour férié',
        ])->assertRedirect();

        $seance->refresh();
        $this->assertSame('2026-03-09', $seance->date_seance->toDateString());
        $this->assertSame(Seance::STATUT_ANNULEE, $seance->statut);

        $this->delete(route('backoffice.seances.destroy', $seance))
            ->assertRedirect(route('backoffice.seances.index'));
        $this->assertDatabaseMissing('seances', ['id' => $seance->id]);
    }

    public function test_show_lists_the_groups_active_students(): void
    {
        $student = $this->enrollStudent();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.show', $seance))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/Show', false)
                ->where('seance.id', $seance->id)
                ->has('seance.students', 1)
                ->where('seance.students.0.id', $student->id)
                ->where('canMark', false)
            );
    }

    public function test_the_roll_call_saves_without_changing_the_seance_statut(): void
    {
        $student = $this->enrollStudent();
        $other = Student::factory()->create(); // NOT enrolled in the group
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'));

        $this->put(route('backoffice.seances.presences.update', $seance), [
            'presences' => [
                $student->id => ['statut' => Presence::STATUT_PRESENT, 'note' => ''],
                // Forged line for a non-enrolled student — must be dropped.
                $other->id => ['statut' => Presence::STATUT_ABSENT, 'note' => ''],
            ],
        ])->assertRedirect(route('backoffice.seances.show', $seance));

        $this->assertDatabaseHas('presences', [
            'seance_id' => $seance->id,
            'student_id' => $student->id,
            'statut' => Presence::STATUT_PRESENT,
        ]);
        $this->assertDatabaseMissing('presences', ['student_id' => $other->id]);
        // Saving the roll call no longer auto-marks the séance "Effectuée" —
        // that's now the explicit "Valider la séance" row-menu action.
        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    public function test_valider_marks_the_seance_effectuee(): void
    {
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'))
            ->post(route('backoffice.seances.valider', $seance))
            ->assertRedirect();

        $this->assertSame(Seance::STATUT_EFFECTUEE, $seance->fresh()->statut);
    }

    public function test_valider_requires_attendance_mark(): void
    {
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view'))
            ->post(route('backoffice.seances.valider', $seance))
            ->assertForbidden();

        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    public function test_annuler_cancels_the_seance_with_a_catalog_reason(): void
    {
        $this->seedMotifs();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'))
            ->post(route('backoffice.seances.annuler', $seance), ['motif' => 'Problème du temps'])
            ->assertRedirect();

        $seance->refresh();
        $this->assertSame(Seance::STATUT_ANNULEE, $seance->statut);
        $this->assertSame('Problème du temps', $seance->motif_annulation);
    }

    public function test_annuler_requires_a_reason(): void
    {
        $this->seedMotifs();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'))
            ->post(route('backoffice.seances.annuler', $seance), ['motif' => ''])
            ->assertSessionHasErrors('motif');

        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    public function test_annuler_refuses_a_reason_outside_the_catalog(): void
    {
        $this->seedMotifs();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'))
            ->post(route('backoffice.seances.annuler', $seance), ['motif' => 'Jour férié'])
            ->assertSessionHasErrors('motif');

        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    public function test_annuler_refuses_an_inactive_reason(): void
    {
        $this->seedMotifs();
        MotifAnnulation::create(['nom' => 'Ancienne raison', 'statut' => MotifAnnulation::STATUT_INACTIF]);
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'))
            ->post(route('backoffice.seances.annuler', $seance), ['motif' => 'Ancienne raison'])
            ->assertSessionHasErrors('motif');

        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    /**
     * « Changement de groupe » is the system reason the inscription
     * group-change flow writes; it says nothing about why a class session did
     * not take place, so the séance form must never accept it.
     */
    public function test_annuler_refuses_the_group_change_system_reason(): void
    {
        $this->seedMotifs();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'))
            ->post(route('backoffice.seances.annuler', $seance), [
                'motif' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
            ])
            ->assertSessionHasErrors('motif');

        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    public function test_annuler_requires_attendance_mark(): void
    {
        $this->seedMotifs();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view'))
            ->post(route('backoffice.seances.annuler', $seance), ['motif' => 'Problème du temps'])
            ->assertForbidden();

        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    /** Starter catalog rows the "Annuler la séance" dropdown offers. */
    private function seedMotifs(): void
    {
        MotifAnnulation::create(['nom' => 'Problème du temps', 'statut' => MotifAnnulation::STATUT_ACTIF]);
        MotifAnnulation::create(['nom' => 'Autre', 'statut' => MotifAnnulation::STATUT_ACTIF]);
        MotifAnnulation::create([
            'nom' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
            'statut' => MotifAnnulation::STATUT_ACTIF,
            'is_system' => true,
        ]);
    }

    public function test_resaving_the_roll_call_updates_instead_of_duplicating(): void
    {
        $student = $this->enrollStudent();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'));

        $payload = fn (string $statut) => [
            'presences' => [$student->id => ['statut' => $statut, 'note' => 'retard bus']],
        ];

        $this->put(route('backoffice.seances.presences.update', $seance), $payload(Presence::STATUT_ABSENT));
        $this->put(route('backoffice.seances.presences.update', $seance), $payload(Presence::STATUT_RETARD));

        $this->assertSame(1, Presence::query()->count());
        $this->assertSame(Presence::STATUT_RETARD, Presence::firstOrFail()->statut);
    }

    public function test_marking_requires_attendance_mark(): void
    {
        $student = $this->enrollStudent();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view'))
            ->put(route('backoffice.seances.presences.update', $seance), [
                'presences' => [$student->id => ['statut' => Presence::STATUT_PRESENT]],
            ])->assertForbidden();
    }

    public function test_an_invalid_presence_statut_is_rejected(): void
    {
        $student = $this->enrollStudent();
        $seance = $this->makeSeance();

        $this->actingAs($this->userWith('attendance.view', 'attendance.mark'))
            ->from(route('backoffice.seances.show', $seance))
            ->put(route('backoffice.seances.presences.update', $seance), [
                'presences' => [$student->id => ['statut' => 'Peut-être']],
            ])->assertSessionHasErrors(["presences.{$student->id}.statut"]);
    }
}
