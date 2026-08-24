<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

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
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * 24/08/2026 performance pass — Encaissements + Recouvrements lists: constant
 * query counts regardless of volume, and partial reloads that skip the
 * option catalogs (every student of the centre) on each filter/page change.
 */
final class ListPerformanceTest extends TestCase
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
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SUPER_ADMIN);
        $this->agent = Employee::factory()->create(['user_id' => $this->admin->id, 'etablissement_id' => $this->centre->id]);
        $this->caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
    }

    /** One overdue fee (1000 due 2025-10-01) with 250 paid. */
    private function overdueFee(int $index): InscriptionFee
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => sprintf('INS-LP%03d', $index),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => 1000,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais {$index}",
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
        ]);
        Encaissement::create([
            'reference' => sprintf('ENC-LP%03d', $index), 'agent_id' => $this->agent->id,
            'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $this->caisse->id, 'montant' => 250, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-10-05',
        ]);

        return $fee;
    }

    private function countQueries(string $route, array $headers = []): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin)->get($route, $headers)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_recouvrement_query_count_does_not_grow_with_overdue_fees(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->overdueFee($i);
        }
        // Warm-up: first request loads user/permissions/context once.
        $this->countQueries(route('backoffice.recouvrement.index', ['dateFrom' => '', 'dateTo' => '']));
        $withThree = $this->countQueries(route('backoffice.recouvrement.index', ['dateFrom' => '', 'dateTo' => '']));

        for ($i = 4; $i <= 15; $i++) {
            $this->overdueFee($i);
        }
        $withFifteen = $this->countQueries(route('backoffice.recouvrement.index', ['dateFrom' => '', 'dateTo' => '']));

        $this->assertSame($withThree, $withFifteen, "Recouvrement queries grew from {$withThree} to {$withFifteen} — per-fee montantPaye() is back.");
    }

    public function test_recouvrement_reports_the_remaining_balance_from_the_aggregated_sum(): void
    {
        $this->overdueFee(1);

        $this->actingAs($this->admin)
            ->get(route('backoffice.recouvrement.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('retards.data', 1)
                ->where('retards.data.0.resteAPayer', '750.00')
                ->where('retards.data.0.statut', InscriptionFee::STATUT_PAYE_PARTIELLEMENT)
                ->where('bucketCounts.plus30j', 1)
            );
    }

    public function test_encaissements_query_count_does_not_grow_with_rows_on_a_page(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->overdueFee($i);
        }
        $this->countQueries(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']));
        $withThree = $this->countQueries(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']));

        for ($i = 4; $i <= 12; $i++) {
            $this->overdueFee($i);
        }
        $withTwelve = $this->countQueries(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']));

        $this->assertSame($withThree, $withTwelve, "Encaissements queries grew from {$withThree} to {$withTwelve}.");
    }

    public function test_encaissements_partial_reload_skips_the_option_catalogs(): void
    {
        $this->overdueFee(1);

        $version = (string) $this->actingAs($this->admin)->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))->inertiaPage()['version'];

        $partial = [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Inertia-Partial-Component' => 'Backoffice/Encaissements/Index',
            'X-Inertia-Partial-Data' => 'encaissements,filters',
        ];

        // A partial reload answers JSON (not the Blade shell).
        $props = $this->actingAs($this->admin)
            ->get(route('backoffice.encaissements.index', ['search' => 'ENC-LP']), $partial)
            ->assertOk()
            ->assertJsonCount(1, 'props.encaissements.data')
            ->assertJsonPath('props.filters.search', 'ENC-LP')
            ->json('props');

        $this->assertArrayNotHasKey('students', $props);
        $this->assertArrayNotHasKey('caisses', $props);
        $this->assertArrayNotHasKey('banques', $props);

        // The partial reload must not run the student/caisse catalog queries.
        $full = $this->countQueries(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']));
        $onlyRows = $this->countQueries(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']), $partial);
        $this->assertLessThan($full, $onlyRows);
    }

    public function test_encaissements_date_filters_apply_and_ignore_malformed_values(): void
    {
        $this->overdueFee(1);

        $this->actingAs($this->admin)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '2025-10-06']))
            ->assertInertia(fn (Assert $page) => $page->has('encaissements.data', 0));

        $this->actingAs($this->admin)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '2025-10-01', 'dateTo' => '2025-10-31']))
            ->assertInertia(fn (Assert $page) => $page->has('encaissements.data', 1));

        $this->actingAs($this->admin)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => 'not-a-date']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('encaissements.data', 1));
    }
}
