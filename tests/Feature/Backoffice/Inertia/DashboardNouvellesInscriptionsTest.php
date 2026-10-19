<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Domain\Registrations\Actions\ChangerGroupeInscription;
use App\Domain\Reports\Actions\GetNouvellesInscriptionsChart;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Nouvelles inscriptions » dashboard bar chart (GetNouvellesInscriptionsChart):
 * every signed-in user (no permission), bucketed by duration (today first).
 * Only a student's FIRST dossier counts: a group change, a re-enrolment or a
 * legacy successor is a later dossier, so none of them is a new registration.
 */
final class DashboardNouvellesInscriptionsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-15 10:00:00');
        $this->seed(RolesAndPermissionsSeeder::class);
        $annee = AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user;
    }

    private function inscription(Student $student, string $date, string $statut = Inscription::STATUT_ACTIVE, ?string $dateFin = null): Inscription
    {
        return Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numberBetween(1000, 99999),
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->group->annee_scolaire_id,
            'statut' => $statut,
            'date_inscription' => $date,
            'date_debut' => $date,
            'date_fin' => $dateFin,
        ]);
    }

    public function test_only_new_registrations_are_counted_by_month(): void
    {
        $a = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $b = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $c = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        // A: new in 10/2025, then changed group in-app on 02/2026 (linked successor).
        $oldA = $this->inscription($a, '2025-10-05', Inscription::STATUT_CHANGEMENT, '2026-02-10');
        $newA = $this->inscription($a, '2026-02-11');
        DB::table('inscriptions_historique')->insert([
            'inscription_id' => $oldA->id, 'new_inscription_id' => $newA->id, 'student_id' => $a->id,
            'group_id' => $this->group->id, 'montant_paye' => 0, 'date_fin' => '2026-02-10', 'archived_at' => now(),
        ]);

        // B: legacy change without link — successor starts on the predecessor's date_fin.
        $this->inscription($b, '2025-10-20', Inscription::STATUT_CHANGEMENT, '2026-01-15');
        $this->inscription($b, '2026-01-15');

        // C: a genuinely new registration, later cancelled — still new.
        $this->inscription($c, '2026-02-01', Inscription::STATUT_ANNULEE);

        // The free window over the whole school year (the old « Année
        // scolaire » button, 19/10/2026) — longer than 62 days, so one bar
        // per month.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'periode', 'inscDebut' => '2025-09-01', 'inscFin' => '2026-08-31']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nouvellesInscriptions.duree', 'periode')
                ->where('nouvellesInscriptions.debut', '2025-09-01')
                ->where('nouvellesInscriptions.fin', '2026-08-31')
                ->has('nouvellesInscriptions.counts', 12)
                ->where('nouvellesInscriptions.periode', '01/09/2025 – 31/08/2026')
                ->where('nouvellesInscriptions.total', 3)
                ->where('nouvellesInscriptions.labels.1', '10/2025')
                ->where('nouvellesInscriptions.counts.1', 2)
                ->where('nouvellesInscriptions.counts.4', 0) // 01/2026: B's successor excluded
                ->where('nouvellesInscriptions.counts.5', 1)); // 02/2026: C only, A's successor excluded
    }

    public function test_durations_bucket_by_day_and_month(): void
    {
        $s = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($s, '2026-03-14');

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => '7j']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('nouvellesInscriptions.counts', 7)
                ->where('nouvellesInscriptions.labels.5', '14/03')
                ->where('nouvellesInscriptions.counts.5', 1)
                ->where('nouvellesInscriptions.total', 1));

        // « 1 mois » = 16/02 → 15/03, one bar per day; the 14/03 row sits on the day before last.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => '1m']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('nouvellesInscriptions.counts', 28)
                ->where('nouvellesInscriptions.labels.0', '16/02')
                ->where('nouvellesInscriptions.labels.26', '14/03')
                ->where('nouvellesInscriptions.counts.26', 1)
                ->where('nouvellesInscriptions.total', 1));

        // « 3 mois » / « 6 mois » bucket by MONTH — never by week (23/09/2026).
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => '3m']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('nouvellesInscriptions.counts', 3)
                ->where('nouvellesInscriptions.labels.0', '01/2026')
                ->where('nouvellesInscriptions.labels.2', '03/2026')
                ->where('nouvellesInscriptions.counts.2', 1));

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => '6m']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('nouvellesInscriptions.counts', 6)
                ->where('nouvellesInscriptions.labels.0', '10/2025')
                ->where('nouvellesInscriptions.counts.5', 1));

        // The week bucket is gone: the old « 12 semaines » key falls back to today.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => '12s']))
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.duree', 'jour'));

        // Unknown duration falls back to today.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'nope']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nouvellesInscriptions.duree', 'jour')
                ->where('nouvellesInscriptions.debut', null)
                ->where('nouvellesInscriptions.fin', null));

        // The old « annee » key is gone: it falls back to today too.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'annee']))
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.duree', 'jour'));
    }

    public function test_a_free_period_buckets_by_day_when_short_and_by_month_when_long(): void
    {
        $s = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($s, '2026-03-14');
        $this->inscription(Student::factory()->create(['etablissement_id' => $this->centre->id]), '2026-01-20');

        // 10 days → one bar per day, inclusive of both ends.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'periode', 'inscDebut' => '2026-03-10', 'inscFin' => '2026-03-19']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nouvellesInscriptions.duree', 'periode')
                ->has('nouvellesInscriptions.counts', 10)
                ->where('nouvellesInscriptions.labels.0', '10/03')
                ->where('nouvellesInscriptions.labels.4', '14/03')
                ->where('nouvellesInscriptions.counts.4', 1)
                ->where('nouvellesInscriptions.total', 1)
                ->where('nouvellesInscriptions.periode', '10/03/2026 – 19/03/2026'));

        // Exactly 62 days still reads by day; 63 flips to months.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'periode', 'inscDebut' => '2026-01-01', 'inscFin' => '2026-03-03']))
            ->assertInertia(fn (Assert $page) => $page->has('nouvellesInscriptions.counts', 62));

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'periode', 'inscDebut' => '2026-01-01', 'inscFin' => '2026-03-15']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('nouvellesInscriptions.counts', 3)
                ->where('nouvellesInscriptions.labels.0', '01/2026')
                ->where('nouvellesInscriptions.counts.0', 1)
                ->where('nouvellesInscriptions.counts.2', 1)
                ->where('nouvellesInscriptions.total', 2));

        // A single day is a one-bar window — never the hourly view.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'periode', 'inscDebut' => '2026-03-14', 'inscFin' => '2026-03-14']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('nouvellesInscriptions.counts', 1)
                ->where('nouvellesInscriptions.counts.0', 1)
                ->where('nouvellesInscriptions.periode', '14/03/2026 – 14/03/2026'));
    }

    public function test_a_free_period_without_a_valid_pair_falls_back_to_today(): void
    {
        $admin = $this->superAdmin();

        foreach ([
            ['inscDebut' => '2026-03-10'],                                   // missing end
            ['inscFin' => '2026-03-10'],                                     // missing start
            ['inscDebut' => '2026-03-19', 'inscFin' => '2026-03-10'],        // inverted
            ['inscDebut' => '10/03/2026', 'inscFin' => '19/03/2026'],        // wrong format
            ['inscDebut' => '2026-02-31', 'inscFin' => '2026-03-10'],        // not a calendar date
        ] as $params) {
            $this->actingAs($admin)
                ->get(route('backoffice.dashboard', ['inscDuree' => 'periode'] + $params))
                ->assertInertia(fn (Assert $page) => $page
                    ->where('nouvellesInscriptions.duree', 'jour')
                    ->where('nouvellesInscriptions.debut', null)
                    ->where('nouvellesInscriptions.fin', null));
        }
    }

    public function test_clicking_a_bar_of_a_free_period_lists_its_students(): void
    {
        $nouveau = Student::factory()->create(['etablissement_id' => $this->centre->id, 'nom' => 'Bennani']);
        $this->inscription($nouveau, '2026-01-20');
        $this->inscription(Student::factory()->create(['etablissement_id' => $this->centre->id]), '2026-03-14');

        // Month bucket of a long window.
        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', [
                'duree' => 'periode', 'key' => '2026-01', 'debut' => '2026-01-01', 'fin' => '2026-03-15',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.nom', 'Bennani')
            ->assertJsonPath('total', 1);

        // Day bucket of a short window.
        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', [
                'duree' => 'periode', 'key' => '2026-01-20', 'debut' => '2026-01-15', 'fin' => '2026-01-25',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.studentId', $nouveau->id);

        // A month key against a window the chart draws by day is not a bar it showed.
        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', [
                'duree' => 'periode', 'key' => '2026-01', 'debut' => '2026-01-15', 'fin' => '2026-01-25',
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'students')
            ->assertJsonPath('total', 0);

        // « periode » without its dates is nothing the chart showed — empty, never « today ».
        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', ['duree' => 'periode', 'key' => '2026-01']))
            ->assertOk()
            ->assertJsonCount(0, 'students')
            ->assertJsonPath('total', 0);
    }

    public function test_today_buckets_by_the_hour_the_row_was_keyed(): void
    {
        $s = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($s, '2026-03-15'); // created_at = now() = 10:00
        $this->inscription(Student::factory()->create(['etablissement_id' => $this->centre->id]), '2026-03-14');

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'jour']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nouvellesInscriptions.duree', 'jour')
                ->has('nouvellesInscriptions.counts', 24)
                ->where('nouvellesInscriptions.labels.10', '10h')
                ->where('nouvellesInscriptions.counts.10', 1)
                ->where('nouvellesInscriptions.total', 1)
                ->where('nouvellesInscriptions.periode', '15/03/2026'));
    }

    public function test_a_re_enrolment_of_an_existing_student_is_not_a_new_registration(): void
    {
        $ancien = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($ancien, '2025-10-05', Inscription::STATUT_ANNULEE);
        // Re-enrolled by hand today, no historique link, no Changement row.
        $this->inscription($ancien, '2026-03-15');

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'jour']))
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.total', 0));
    }

    public function test_a_group_change_keeps_the_original_registration_date(): void
    {
        $s = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $old = $this->inscription($s, '2025-10-05');
        $newGroup = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->group->annee_scolaire_id]);

        $new = app(ChangerGroupeInscription::class)->handle($old, $newGroup, '2026-03-14', '2026-03-15', null, null, null);

        $this->assertSame('2025-10-05', $new->date_inscription->toDateString());

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'jour']))
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.total', 0));
    }

    public function test_every_role_receives_the_chart(): void
    {
        foreach (Role::query()->pluck('name') as $name) {
            $user = User::factory()->create();
            $user->assignRole($name);

            $this->actingAs($user)
                ->get(route('backoffice.dashboard'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.duree', 'jour'));
        }
    }

    public function test_a_user_with_no_role_or_permission_still_receives_the_chart(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.duree', 'jour'));
    }

    public function test_clicking_a_bar_lists_exactly_the_students_it_counts(): void
    {
        $nouveau = Student::factory()->create(['etablissement_id' => $this->centre->id, 'nom' => 'Alaoui', 'prenom' => 'Sara']);
        $this->inscription($nouveau, '2026-03-12');
        // Same day, but an existing student's second dossier — not counted, not listed.
        $ancien = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($ancien, '2025-10-05');
        $this->inscription($ancien, '2026-03-12');
        // Another day of the same window.
        $this->inscription(Student::factory()->create(['etablissement_id' => $this->centre->id]), '2026-03-13');

        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', ['duree' => '7j', 'key' => '2026-03-12']))
            ->assertOk()
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.studentId', $nouveau->id)
            ->assertJsonPath('students.0.nom', 'Alaoui')
            ->assertJsonPath('students.0.dateInscription', '12/03/2026')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('lastPage', 1)
            ->assertJsonPath('canViewStudents', true);
    }

    public function test_a_bar_with_many_students_is_paginated_server_side(): void
    {
        $perPage = GetNouvellesInscriptionsChart::STUDENTS_PER_PAGE;
        for ($n = 0; $n < $perPage + 3; $n++) {
            $this->inscription(Student::factory()->create(['etablissement_id' => $this->centre->id]), '2026-03-12');
        }

        $user = $this->superAdmin();
        $params = ['duree' => '7j', 'key' => '2026-03-12'];

        $this->actingAs($user)
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', $params))
            ->assertOk()
            ->assertJsonCount($perPage, 'students')
            ->assertJsonPath('total', $perPage + 3)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('lastPage', 2)
            ->assertJsonPath('perPage', $perPage);

        $this->actingAs($user)
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', $params + ['page' => 2]))
            ->assertOk()
            ->assertJsonCount(3, 'students')
            ->assertJsonPath('page', 2)
            ->assertJsonPath('total', $perPage + 3);

        // Past the end: empty rows, but the real total — never a widened list.
        $this->actingAs($user)
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', $params + ['page' => 9]))
            ->assertOk()
            ->assertJsonCount(0, 'students')
            ->assertJsonPath('total', $perPage + 3);

        // A bogus page falls back to the first one.
        $this->actingAs($user)
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', $params + ['page' => 0]))
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonCount($perPage, 'students');
    }

    public function test_today_bar_lists_by_the_hour_and_rejects_foreign_keys(): void
    {
        $s = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($s, '2026-03-15'); // keyed at 10:00

        $user = $this->superAdmin();
        $this->actingAs($user)
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', ['duree' => 'jour', 'key' => '10']))
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.heure', '10:00');

        foreach ([['jour', '11'], ['jour', "10' OR 1=1"], ['nope', '10'], ['7j', '2026-03']] as [$duree, $key]) {
            $this->actingAs($user)
                ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', ['duree' => $duree, 'key' => $key]))
                ->assertOk()
                ->assertJsonCount(0, 'students');
        }
    }

    public function test_a_user_without_students_view_gets_names_but_no_link(): void
    {
        $this->inscription(Student::factory()->create(['etablissement_id' => $this->centre->id]), '2026-03-15');

        $this->actingAs(User::factory()->create())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', ['duree' => 'jour', 'key' => '10']))
            ->assertOk()
            ->assertJsonPath('canViewStudents', false);
    }

    public function test_a_student_who_paid_before_the_dossier_is_not_new(): void
    {
        // Salé, 23/09/2026 : the imported dossier was deleted the day a new one
        // was keyed, and the 07/08 payment moved onto the new one. Nothing in
        // `inscriptions` says she is a returning student — her money does.
        $hiba = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $nouveau = $this->inscription($hiba, '2026-03-15');
        $fee = InscriptionFee::create([
            'inscription_id' => $nouveau->id, 'nom' => 'Frais de Mars',
            'montant_initial' => 300, 'montant' => 300, 'date_echeance' => '2026-03-15',
        ]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        Encaissement::create([
            'reference' => 'ENC-HIBA', 'agent_id' => $agent->id,
            'student_id' => $hiba->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => 300, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2026-02-07',
        ]);
        // A genuinely new student who paid ON enrolment day still counts.
        $neuf = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $insNeuf = $this->inscription($neuf, '2026-03-15');
        $feeNeuf = InscriptionFee::create([
            'inscription_id' => $insNeuf->id, 'nom' => 'Frais de Mars',
            'montant_initial' => 300, 'montant' => 300, 'date_echeance' => '2026-03-15',
        ]);
        Encaissement::create([
            'reference' => 'ENC-NEUF', 'agent_id' => $agent->id,
            'student_id' => $neuf->id, 'inscription_fee_id' => $feeNeuf->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => 300, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2026-03-15',
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'jour']))
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.total', 1));

        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', ['duree' => 'jour', 'key' => '10']))
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.studentId', $neuf->id);
    }

    public function test_a_student_whose_earlier_dossier_was_deleted_is_not_new(): void
    {
        $ancien = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $vieux = $this->inscription($ancien, '2025-10-05');
        $vieux->delete(); // journaled as `deleted` — the journal is append-only
        $this->inscription($ancien, '2026-03-15');

        // A dossier keyed by mistake and re-keyed the SAME day is still new.
        $neuf = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($neuf, '2026-03-15')->delete();
        $this->inscription($neuf, '2026-03-15');

        $this->assertDatabaseHas('activity_log', ['log_name' => 'inscription', 'event' => 'deleted', 'subject_id' => $vieux->id]);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'jour']))
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.total', 1));

        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.dashboard.nouvelles-inscriptions', ['duree' => 'jour', 'key' => '10']))
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.studentId', $neuf->id);
    }
}
