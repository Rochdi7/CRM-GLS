<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Domain\Registrations\Actions\ChangerGroupeInscription;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
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

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'annee']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nouvellesInscriptions.duree', 'annee')
                ->where('nouvellesInscriptions.total', 3)
                ->where('nouvellesInscriptions.labels.1', '10/2025')
                ->where('nouvellesInscriptions.counts.1', 2)
                ->where('nouvellesInscriptions.counts.4', 0) // 01/2026: B's successor excluded
                ->where('nouvellesInscriptions.counts.5', 1)); // 02/2026: C only, A's successor excluded
    }

    public function test_durations_bucket_by_day_and_week(): void
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

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => '12s']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('nouvellesInscriptions.counts', 12)
                ->where('nouvellesInscriptions.counts.11', 1));

        // Unknown duration falls back to today.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.dashboard', ['inscDuree' => 'nope']))
            ->assertInertia(fn (Assert $page) => $page->where('nouvellesInscriptions.duree', 'jour'));
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
}
