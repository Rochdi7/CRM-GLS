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
 * « Statistiques » tab of Gestion des séances (27/10/2026) — présences /
 * absences of one group charted per date and per student, derived from the
 * « Absence par groupe » matrix. Two series only: « Retard » is removed from
 * the system, its legacy rows count as présences.
 */
final class SeancesStatistiquesTest extends TestCase
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

    private function enrollStudent(string $prenom): Student
    {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'nom' => 'Test',
            'prenom' => $prenom,
        ]);

        Inscription::create([
            'reference' => 'INS-STAT-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-03-01',
        ]);

        return $student;
    }

    private function makeSeance(string $date): Seance
    {
        return Seance::create([
            'group_id' => $this->group->id,
            'date_seance' => $date,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);
    }

    /** @return array<string, string> */
    private function window(array $extra = []): array
    {
        return ['groupFilter' => (string) $this->group->id, 'dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31', ...$extra];
    }

    public function test_it_requires_attendance_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.seances.statistiques'))
            ->assertForbidden();
    }

    public function test_a_bare_visit_redirects_to_the_last_month_window(): void
    {
        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.statistiques'))
            ->assertRedirect(route('backoffice.seances.statistiques', [
                'vue' => 'presence',
                'groupFilter' => '',
                'studentFilter' => '',
                'dateFrom' => now()->subMonth()->toDateString(),
                'dateTo' => now()->toDateString(),
            ]));
    }

    public function test_it_renders_empty_without_a_group(): void
    {
        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.statistiques', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/Statistiques')
                ->where('statistiques.parDate', [])
                ->where('statistiques.parEtudiant', [])
                ->where('statistiques.etudiants', [])
            );
    }

    public function test_it_charts_presences_and_absences_per_date_and_per_student_without_retard(): void
    {
        $alice = $this->enrollStudent('Alice');
        $bob = $this->enrollStudent('Bob');

        $s1 = $this->makeSeance('2026-03-02');
        $s2 = $this->makeSeance('2026-03-04');
        // Nobody pointed: left out instead of plotted as an empty class.
        $this->makeSeance('2026-03-06');

        Presence::create(['seance_id' => $s1->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_PRESENT]);
        Presence::create(['seance_id' => $s2->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_JUSTIFIE]);
        // Legacy « Retard » line: counted as a présence, never its own series.
        Presence::create(['seance_id' => $s1->id, 'student_id' => $bob->id, 'statut' => Presence::STATUT_RETARD]);
        Presence::create(['seance_id' => $s2->id, 'student_id' => $bob->id, 'statut' => Presence::STATUT_ABSENT]);

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.statistiques', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/Statistiques')
                ->where('statistiques.parDate', [
                    ['date' => '2026-03-02', 'presents' => 2, 'absents' => 0],
                    ['date' => '2026-03-04', 'presents' => 0, 'absents' => 2],
                ])
                ->count('statistiques.parEtudiant', 2)
                ->where('statistiques.parEtudiant.0.presents', 1)
                ->where('statistiques.parEtudiant.0.absents', 1)
                ->where('statistiques.parEtudiant.1.presents', 1)
                ->where('statistiques.parEtudiant.1.absents', 1)
                ->missing('statistiques.parDate.0.retards')
                ->where('statistiques.totals', ['seances' => 2, 'presents' => 2, 'absents' => 2])
                ->count('statistiques.etudiants', 2)
            );
    }

    public function test_the_student_filter_narrows_both_charts(): void
    {
        $alice = $this->enrollStudent('Alice');
        $bob = $this->enrollStudent('Bob');

        $s1 = $this->makeSeance('2026-03-02');
        $s2 = $this->makeSeance('2026-03-04');

        Presence::create(['seance_id' => $s1->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_PRESENT]);
        Presence::create(['seance_id' => $s1->id, 'student_id' => $bob->id, 'statut' => Presence::STATUT_ABSENT]);
        // Only Bob was called on s2: that date is not Alice's.
        Presence::create(['seance_id' => $s2->id, 'student_id' => $bob->id, 'statut' => Presence::STATUT_PRESENT]);

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.statistiques', $this->window(['studentFilter' => (string) $alice->id])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.studentFilter', (string) $alice->id)
                ->where('statistiques.parDate', [['date' => '2026-03-02', 'presents' => 1, 'absents' => 0]])
                ->count('statistiques.parEtudiant', 1)
                ->where('statistiques.parEtudiant.0.id', $alice->id)
                // The dropdown still lists the whole roll.
                ->count('statistiques.etudiants', 2)
            );
    }

    public function test_a_student_outside_the_group_is_ignored(): void
    {
        $alice = $this->enrollStudent('Alice');
        $s1 = $this->makeSeance('2026-03-02');
        Presence::create(['seance_id' => $s1->id, 'student_id' => $alice->id, 'statut' => Presence::STATUT_PRESENT]);

        $stranger = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.seances.statistiques', $this->window(['studentFilter' => (string) $stranger->id])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.studentFilter', '')
                ->count('statistiques.parEtudiant', 1)
            );
    }
}
