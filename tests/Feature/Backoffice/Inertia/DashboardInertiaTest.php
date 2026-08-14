<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Role;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase 4 (docs/inertia-react-migration-plan.md) — Dashboard converted from
 * the Livewire DashboardStats to Inertia (DashboardController +
 * GetDashboardStats). Query semantics preserved exactly — see
 * docs/dashboard-livewire-to-inertia-map.md — this file verifies the
 * Inertia contract on top of that.
 */
final class DashboardInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('backoffice.dashboard'))
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_authenticated_user_sees_the_dashboard_component(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Dashboard/Index')
                ->has('stats.studentsTotal')
                ->has('stats.employeesTotal')
                ->has('stats.employeesActive')
                ->has('stats.groupsTotal')
                ->has('stats.groupsEnFormation')
                ->has('stats.inscriptionsTotal')
                ->has('stats.inscriptionsActives')
                ->has('stats.inscriptionsAnnulees')
                ->has('stats.inscriptionsChangement')
                ->has('stats.paymentsMonth')
                ->has('stats.depensesMonth')
                ->has('stats.depensesMonthCount')
                ->has('stats.anneeLabel')
                ->has('stats.centreLabel')
                ->has('seancesCalendar.month')
                ->has('seancesCalendar.days')
            );
    }

    public function test_stats_prop_contains_no_full_models(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats', fn ($stats) => collect($stats)->keys()->all() === [
                    'studentsTotal', 'employeesTotal', 'employeesActive', 'enseignantsTotal', 'parentsTotal', 'groupsTotal',
                    'groupsEnFormation', 'inscriptionsTotal', 'inscriptionsActives', 'inscriptionsAnnulees', 'inscriptionsChangement',
                    'paymentsMonth', 'depensesMonth', 'depensesMonthCount', 'anneeLabel', 'centreLabel',
                ])
            );
    }

    public function test_zero_state_renders_correctly_with_no_data(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.studentsTotal', 0)
                ->where('stats.paymentsMonth', '0.00')
                ->where('stats.depensesMonth', '0.00')
                ->where('stats.depensesMonthCount', 0)
            );
    }

    public function test_seances_calendar_groups_month_sessions_by_day(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $annee = AnneeScolaire::query()->where('nom', '2025/2026')->firstOrFail();
        $centre = Etablissement::factory()->create();
        $group = Group::factory()->create(['etablissement_id' => $centre->id, 'annee_scolaire_id' => $annee->id]);

        $inMonth = now()->startOfMonth()->addDays(2);
        Seance::create([
            'group_id' => $group->id,
            'date_seance' => $inMonth->toDateString(),
            'heure_debut' => '19:00',
            'heure_fin' => '21:30',
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);
        // Previous month — must not appear for the default (current) month.
        Seance::create([
            'group_id' => $group->id,
            'date_seance' => now()->subMonthNoOverflow()->toDateString(),
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

        $this->actingAs($admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('seancesCalendar.month', now()->format('Y-m'))
                ->has('seancesCalendar.days', 1)
                ->has("seancesCalendar.days.{$inMonth->toDateString()}", 1)
                ->where("seancesCalendar.days.{$inMonth->toDateString()}.0.statut", Seance::STATUT_EFFECTUEE)
                ->where("seancesCalendar.days.{$inMonth->toDateString()}.0.heureDebut", '19:00')
            );
    }

    public function test_seances_calendar_follows_the_requested_month(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $target = now()->subMonthNoOverflow();

        $this->actingAs($admin)
            ->get(route('backoffice.dashboard', ['calMonth' => $target->format('Y-m')]))
            ->assertInertia(fn (Assert $page) => $page->where('seancesCalendar.month', $target->format('Y-m')));

        // Malformed month falls back to the current one instead of erroring.
        $this->actingAs($admin)
            ->get(route('backoffice.dashboard', ['calMonth' => 'not-a-month']))
            ->assertInertia(fn (Assert $page) => $page->where('seancesCalendar.month', now()->format('Y-m')));
    }

    public function test_stats_follow_the_selected_center(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();
        Student::factory()->count(3)->create(['etablissement_id' => $rabat->id]);
        Student::factory()->count(5)->create(['etablissement_id' => $casa->id]);

        $this->actingAs($admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.studentsTotal', 8));

        app(\App\Services\Context\CurrentContext::class)->setEtablissement($rabat->id);

        $this->actingAs($admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.studentsTotal', 3));
    }

    public function test_stats_follow_the_selected_academic_year(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $oldYear = AnneeScolaire::create(['nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31', 'par_defaut' => false, 'inscription_ouverte' => false]);
        $newYear = AnneeScolaire::query()->where('nom', '2025/2026')->firstOrFail();
        $centre = Etablissement::factory()->create();

        Group::factory()->create(['etablissement_id' => $centre->id, 'annee_scolaire_id' => $oldYear->id]);
        Group::factory()->count(2)->create(['etablissement_id' => $centre->id, 'annee_scolaire_id' => $newYear->id]);

        $this->actingAs($admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.groupsTotal', 2));

        app(\App\Services\Context\CurrentContext::class)->setAnneeScolaire($oldYear->id);

        $this->actingAs($admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.groupsTotal', 1));
    }

    public function test_all_centers_selection_aggregates_every_center(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();
        Student::factory()->create(['etablissement_id' => $rabat->id]);
        Student::factory()->create(['etablissement_id' => $casa->id]);

        app(\App\Services\Context\CurrentContext::class)->setEtablissement($rabat->id);
        app(\App\Services\Context\CurrentContext::class)->setEtablissement(null); // back to "all"

        $this->actingAs($admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.studentsTotal', 2));
    }

    public function test_dashboard_stats_do_not_produce_an_excessive_query_count(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        Student::factory()->count(3)->create();
        Employee::factory()->count(2)->create();

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('backoffice.dashboard'))->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Generous ceiling — not a tight regression pin, just a guard against
        // an accidental N+1 (per-row) pattern creeping into the stats query.
        $this->assertLessThan(30, $queryCount, "Dashboard executed {$queryCount} queries — check for N+1s.");
    }
}
