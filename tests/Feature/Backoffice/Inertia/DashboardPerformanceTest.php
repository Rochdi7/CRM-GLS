<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

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
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * 24/08/2026 performance pass — the dashboard must cost the SAME number of
 * queries whatever the data volume. The "Résumé des frais annuels" chart used
 * to hydrate every fee of the year and run one SUM per fee
 * (InscriptionFee::montantPaye()), so a centre with thousands of fees paid
 * thousands of queries per dashboard visit. These tests pin both the
 * constant query count and the chart's arithmetic.
 */
final class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private User $admin;

    private Caisse $caisse;

    private Employee $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $year = (int) now()->year;
        $this->annee = AnneeScolaire::create([
            'nom' => ($year - 1).'/'.$year,
            'date_debut' => ($year - 1).'-09-01',
            'date_fin' => $year.'-08-31',
            'par_defaut' => true,
            'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SUPER_ADMIN);
        $this->agent = Employee::factory()->create(['user_id' => $this->admin->id, 'etablissement_id' => $this->centre->id]);
        $this->caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
    }

    /**
     * One inscription + one fee of 1000 due in March of the current year,
     * with 400 already paid in March.
     */
    private function enrollWithPartiallyPaidFee(int $index): void
    {
        $year = (int) now()->year;
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => sprintf('INS-PERF%03d', $index),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => "{$year}-01-10",
            'montant_total' => 1000,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Mars',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => "{$year}-03-15", 'statut' => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
        ]);
        Encaissement::create([
            'reference' => sprintf('ENC-PERF%03d', $index), 'agent_id' => $this->agent->id,
            'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $this->caisse->id, 'montant' => 400, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => "{$year}-03-20",
        ]);
    }

    private function countDashboardQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin)->get(route('backoffice.dashboard'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_dashboard_query_count_does_not_grow_with_the_number_of_fees(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->enrollWithPartiallyPaidFee($i);
        }
        // Warm-up: the very first request of a test also loads the user,
        // roles/permissions cache and persists the default context — those
        // one-off queries would otherwise skew the comparison.
        $this->countDashboardQueries();
        $withThree = $this->countDashboardQueries();

        for ($i = 4; $i <= 15; $i++) {
            $this->enrollWithPartiallyPaidFee($i);
        }
        $withFifteen = $this->countDashboardQueries();

        $this->assertSame($withThree, $withFifteen, "Dashboard queries grew from {$withThree} to {$withFifteen} — a per-row (N+1) pattern is back.");
        $this->assertLessThan(30, $withFifteen);
    }

    public function test_annual_chart_series_are_aggregated_per_month(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->enrollWithPartiallyPaidFee($i);
        }

        $this->actingAs($this->admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('annualFrais.chiffreAffaire.2', '5000.00')
                ->where('annualFrais.collecte.2', '2000.00')
                ->where('annualFrais.resteAPayer.2', '3000.00')
                ->where('annualFrais.encaissements.2', '2000.00')
                ->where('annualFrais.chiffreAffaire.0', '0.00')
                ->where('annualFrais.collecte.0', '0.00')
                ->where('annualFraisYears.0', (int) now()->year)
                ->where('stats.inscriptionsTotal', 5)
                ->where('stats.inscriptionsActives', 5)
                ->where('stats.studentsTotal', 5)
            );
    }

    public function test_annual_chart_follows_the_active_center(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $this->enrollWithPartiallyPaidFee($i);
        }
        $other = Etablissement::factory()->create();

        // CurrentContext refuses a switch for a guest — authenticate first.
        $this->actingAs($this->admin);
        app(CurrentContext::class)->setEtablissement($other->id);

        $this->actingAs($this->admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('annualFrais.chiffreAffaire.2', '0.00')
                ->where('annualFrais.collecte.2', '0.00')
                ->where('annualFrais.encaissements.2', '0.00')
                ->where('stats.inscriptionsTotal', 0)
            );

        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->actingAs($this->admin)
            ->get(route('backoffice.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('annualFrais.chiffreAffaire.2', '2000.00')
                ->where('annualFrais.collecte.2', '800.00')
                ->where('annualFrais.encaissements.2', '800.00')
                ->where('stats.inscriptionsTotal', 2)
            );
    }

    public function test_partial_reload_of_the_calendar_skips_the_other_widgets(): void
    {
        $this->enrollWithPartiallyPaidFee(1);

        $version = (string) $this->actingAs($this->admin)->get(route('backoffice.dashboard'))->inertiaPage()['version'];

        // A partial reload answers JSON (not the Blade shell), so the props
        // are read straight off the payload.
        $props = $this->actingAs($this->admin)
            ->get(route('backoffice.dashboard'), [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'Backoffice/Dashboard/Index',
                'X-Inertia-Partial-Data' => 'seancesCalendar',
            ])
            ->assertOk()
            ->assertJsonPath('props.seancesCalendar.month', now()->format('Y-m'))
            ->json('props');

        $this->assertArrayNotHasKey('stats', $props);
        $this->assertArrayNotHasKey('annualFrais', $props);
        $this->assertArrayNotHasKey('annualFraisYears', $props);
    }
}
