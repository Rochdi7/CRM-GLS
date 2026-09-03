<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Absence par groupe » — the presence matrix tab (students in rows, the
 * séances of the date window in columns) and its .xlsx export.
 */
final class AbsenceParGroupeTest extends TestCase
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

    private function enrollStudent(string $prenom, string $statut = Inscription::STATUT_ACTIVE): Student
    {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'prenom' => $prenom,
        ]);

        Inscription::create([
            'reference' => 'INS-ABS-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => '2026-03-01',
        ]);

        return $student;
    }

    private function makeSeance(string $date, string $statut = Seance::STATUT_EFFECTUEE): Seance
    {
        return Seance::create([
            'group_id' => $this->group->id,
            'date_seance' => $date,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
        ]);
    }

    public function test_it_requires_attendance_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.seances.absence-par-groupe'))
            ->assertForbidden();
    }

    public function test_it_renders_empty_without_a_group(): void
    {
        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/AbsenceParGroupe')
                ->where('matrice.seances', [])
                ->where('matrice.students', [])
            );
    }

    public function test_it_builds_the_matrix_for_the_selected_group(): void
    {
        $alice = $this->enrollStudent('Alice');
        $bob = $this->enrollStudent('Bob');

        $s1 = $this->makeSeance('2026-03-02');
        $s2 = $this->makeSeance('2026-03-04');

        Presence::create(['seance_id' => $s1->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_PRESENT]);
        Presence::create(['seance_id' => $s2->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_ABSENT]);
        // A "Retard" still counts as attended — the matrix has two letters only.
        Presence::create(['seance_id' => $s1->id, 'student_id' => $bob->id, 'statut' => Presence::STATUT_RETARD]);

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe', ['groupFilter' => $this->group->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/AbsenceParGroupe')
                ->count('matrice.seances', 2)
                ->count('matrice.students', 2)
                // Columns are numbered chronologically.
                ->where('matrice.seances.0.numero', 1)
                ->where('matrice.seances.0.date', '2026-03-02')
                ->where('matrice.seances.1.numero', 2)
                // Alice sorts first: présent then absent.
                ->where('matrice.students.0.prenom', 'Alice')
                ->where('matrice.students.0.presents', 1)
                ->where('matrice.students.0.absents', 1)
                ->where("matrice.students.0.cells.{$s1->id}.lettre", 'P')
                ->where("matrice.students.0.cells.{$s2->id}.lettre", 'A')
                // Bob was late on s1 (P) and never marked on s2 (no cell).
                ->where("matrice.students.1.cells.{$s1->id}.lettre", 'P')
                ->where('matrice.students.1.presents', 1)
                ->where('matrice.totals.presents', 2)
                ->where('matrice.totals.absents', 1)
            );
    }

    /**
     * A séance carrying NO roll-call at all is flagged `saisie = false`, which
     * the page uses to grey its WHOLE column. Same definition the rest of the
     * app uses for an untreated séance (SeanceController@destroy). A séance
     * that WAS pointed stays `saisie = true` even for the students who have no
     * cell on it — that is an individual gap, not a missing day.
     */
    public function test_a_seance_with_no_attendance_at_all_is_flagged_unsaisie(): void
    {
        $alice = $this->enrollStudent('Alice');
        $this->enrollStudent('Bob');

        $pointee = $this->makeSeance('2026-03-02');
        $vide = $this->makeSeance('2026-03-04');

        // Only Alice is marked on the first séance: Bob's missing cell must
        // NOT make the column count as unsaisie.
        Presence::create(['seance_id' => $pointee->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_PRESENT]);

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe', ['groupFilter' => $this->group->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->count('matrice.seances', 2)
                ->where('matrice.seances.0.id', $pointee->id)
                ->where('matrice.seances.0.saisie', true)
                ->where('matrice.seances.1.id', $vide->id)
                ->where('matrice.seances.1.saisie', false)
            );
    }

    /**
     * Row order mirrors « Détails paiement »
     * (GetGroupPaymentMatrix::STATUT_ORDRE): the statut block wins, then the
     * full name inside it. Reading the same group on the two screens must
     * never give two different orders.
     */
    public function test_rows_are_ordered_by_statut_block_then_alphabetically(): void
    {
        $this->enrollStudent('Zoe');
        $this->enrollStudent('Adam', Inscription::STATUT_ANNULEE);
        $this->enrollStudent('Bruno');
        $this->enrollStudent('Yves', Inscription::STATUT_CHANGEMENT);

        $this->makeSeance('2026-03-02');

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe', ['groupFilter' => $this->group->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->count('matrice.students', 4)
                // Active first, alphabetically…
                ->where('matrice.students.0.prenom', 'Bruno')
                ->where('matrice.students.1.prenom', 'Zoe')
                // …then Changement, then Annulée — never interleaved, even
                // though "Adam" would sort first in a flat alphabetical list.
                ->where('matrice.students.2.prenom', 'Yves')
                ->where('matrice.students.3.prenom', 'Adam')
            );
    }

    public function test_the_date_window_narrows_the_columns(): void
    {
        $this->enrollStudent('Alice');
        $this->makeSeance('2026-03-02');
        $this->makeSeance('2026-04-20');

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe', [
                'groupFilter' => $this->group->id,
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->count('matrice.seances', 1)
                ->where('matrice.seances.0.date', '2026-03-02')
            );
    }

    public function test_the_statut_filter_narrows_the_columns_not_the_students(): void
    {
        $this->enrollStudent('Alice');
        $this->makeSeance('2026-03-02', Seance::STATUT_EFFECTUEE);
        $this->makeSeance('2026-03-04', Seance::STATUT_ANNULEE);

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe', [
                'groupFilter' => $this->group->id,
                'statutFilter' => Seance::STATUT_ANNULEE,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->count('matrice.seances', 1)
                ->where('matrice.seances.0.statut', Seance::STATUT_ANNULEE)
                // The student list never shrinks with a séance filter.
                ->count('matrice.students', 1)
            );
    }

    /**
     * A cancelled inscription is attendance HISTORY: the student stays in the
     * matrix, flagged, so the page can paint the row red like the reference
     * CRM — dropping the row would hide présences that really happened.
     */
    public function test_a_non_active_inscription_stays_in_the_matrix_flagged(): void
    {
        $this->enrollStudent('Alice');
        $this->enrollStudent('Zoe', Inscription::STATUT_ANNULEE);
        $this->makeSeance('2026-03-02');

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe', ['groupFilter' => $this->group->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->count('matrice.students', 2)
                ->where('matrice.students.0.actif', true)
                ->where('matrice.students.1.prenom', 'Zoe')
                ->where('matrice.students.1.actif', false)
            );
    }

    public function test_a_group_of_another_year_is_not_shown(): void
    {
        $autre = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $groupAutre = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $autre->id,
        ]);
        Seance::create([
            'group_id' => $groupAutre->id,
            'date_seance' => '2026-10-05',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $autre->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);

        // Active context is the default year (2025/2026) — the other year's
        // séances must not leak into the matrix.
        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe', ['groupFilter' => $groupAutre->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->count('matrice.seances', 0));
    }

    public function test_export_streams_an_xlsx_file(): void
    {
        $alice = $this->enrollStudent('Alice');
        $seance = $this->makeSeance('2026-03-02');
        Presence::create(['seance_id' => $seance->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_PRESENT]);

        $response = $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe.export', ['groupFilter' => $this->group->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $bytes = $response->streamedContent();
        $this->assertGreaterThan(0, strlen($bytes));
        // XLSX is a zip archive.
        $this->assertSame('PK', substr($bytes, 0, 2));
    }

    public function test_export_refuses_without_a_group(): void
    {
        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.absence-par-groupe.export'))
            ->assertSessionHasErrors('groupFilter');
    }
}
