<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Expenses\Actions\ApprouverDepense;
use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Models\Activity;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * A dépense can never be approved beyond what its till holds
 * (GardeSoldeCaisse, 09/09/2026).
 *
 * Incident: five dépenses (22 500 DH) were approved one after the other on
 * El Mehdi Bakhach's till, which had only received 15 250 DH by transfers —
 * nothing compared the amount to the balance, and the till ended at
 * -7 250,00 DH. The rule is `montant <= solde`, checked INSIDE the approval
 * transaction on the till row locked FOR UPDATE, so two approvals racing on
 * the same till are serialized by PostgreSQL and the second one re-reads the
 * already-reduced balance.
 */
final class DepenseSoldeInsuffisantTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $marrakech;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
    }

    // ── fixtures ────────────────────────────────────────────────────────

    /** A cashier of $centre and their auto-provisioned till holding $solde. */
    private function cashierWithTill(Etablissement $centre, string $solde): array
    {
        $user = User::factory()->create();
        $user->givePermissionTo('expenses.view');
        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $till = $employee->till()->firstOrFail();
        $till->update(['solde' => $solde]);

        return [$employee, $till->fresh()];
    }

    private function approver(Etablissement $activeCentre): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['expenses.view', 'expenses.approve', 'centers.access-all']);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $activeCentre->id]);
        app(CurrentContext::class)->setEtablissement($activeCentre->id);

        return $user->fresh();
    }

    private function type(): TypeDepense
    {
        return TypeDepense::firstOrCreate(
            ['nom' => 'Fournitures'],
            ['is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF],
        );
    }

    private function pending(Employee $agent, Caisse $till, string $montant): Depense
    {
        return Depense::create([
            'reference' => 'DEP-'.fake()->unique()->numerify('#####'),
            'type_depense_id' => $this->type()->id,
            'caisse_id' => $till->id,
            'agent_id' => $agent->id,
            'montant' => $montant,
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-08-29',
            'statut' => Depense::STATUT_EN_ATTENTE,
            'description' => '*',
        ]);
    }

    private function ledgerDebits(Depense $depense): int
    {
        return Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->where('properties->origine_type', Depense::class)
            ->whereRaw("(properties->'origine_id')::text = ?", [(string) $depense->id])
            ->where('properties->sens', 'Sortie')
            ->count();
    }

    // ── Test 1 · sufficient balance ─────────────────────────────────────

    public function test_an_expense_covered_by_the_till_is_approved_and_debited_once(): void
    {
        [$agent, $till] = $this->cashierWithTill($this->rabat, '10000.00');
        $depense = $this->pending($agent, $till, '7650.00');

        $this->actingAs($this->approver($this->rabat));
        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasNoErrors();

        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->fresh()->statut);
        $this->assertSame('2350.00', (string) $till->fresh()->solde);
        $this->assertSame(1, $this->ledgerDebits($depense));
    }

    // ── Test 2 · insufficient balance ───────────────────────────────────

    public function test_an_expense_larger_than_the_till_is_refused_with_a_french_message(): void
    {
        // DEP-014's exact shape: 7 650 DH against a till holding 400 DH.
        [$agent, $till] = $this->cashierWithTill($this->rabat, '400.00');
        $depense = $this->pending($agent, $till, '7650.00');

        $this->actingAs($this->approver($this->rabat));
        $response = $this->put(route('backoffice.depenses.approve', $depense));

        $response->assertSessionHasErrors('statut');
        $message = session('errors')->first('statut');
        $this->assertStringStartsWith("Impossible d'approuver cette dépense", $message);
        $this->assertStringContainsString('400,00', $message);
        $this->assertStringContainsString('7 650,00', $message);

        // Nothing moved: still pending, no ledger debit, balance intact.
        $this->assertSame(Depense::STATUT_EN_ATTENTE, $depense->fresh()->statut);
        $this->assertNull($depense->fresh()->approved_at);
        $this->assertSame(0, $this->ledgerDebits($depense));
        $this->assertSame('400.00', (string) $till->fresh()->solde);
    }

    // ── Test 3 · exact balance ──────────────────────────────────────────

    public function test_an_expense_equal_to_the_till_balance_is_approved_down_to_zero(): void
    {
        [$agent, $till] = $this->cashierWithTill($this->rabat, '7650.00');
        $depense = $this->pending($agent, $till, '7650.00');

        $this->actingAs($this->approver($this->rabat));
        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasNoErrors();

        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->fresh()->statut);
        $this->assertSame('0.00', (string) $till->fresh()->solde);
    }

    // ── Test 4 · double approval ────────────────────────────────────────

    public function test_approving_the_same_expense_twice_debits_the_till_once(): void
    {
        [$agent, $till] = $this->cashierWithTill($this->rabat, '10000.00');
        $depense = $this->pending($agent, $till, '1000.00');
        $approver = $this->approver($this->rabat);
        $action = app(ApprouverDepense::class);

        $action->handle($depense, $approver->employee);

        // Second click — the row is re-read under lock and already decided.
        try {
            $action->handle($depense, $approver->employee);
            $this->fail('A second approval must be refused.');
        } catch (ValidationException $e) {
            $this->assertSame('Cette dépense a déjà été traitée.', $e->errors()['statut'][0]);
        }

        // Same through HTTP: the policy refuses a decided row before the action.
        $this->actingAs($approver);
        $this->put(route('backoffice.depenses.approve', $depense))->assertForbidden();

        $this->assertSame('9000.00', (string) $till->fresh()->solde);
        $this->assertSame(1, $this->ledgerDebits($depense));
    }

    // ── Test 5 · two expenses against one limited till ──────────────────

    /**
     * The two approvals share the till row lock: A takes it, debits, commits;
     * B then acquires it and reads 300, not 1 000. Sequential here because
     * RefreshDatabase wraps the test in one transaction (a second PostgreSQL
     * connection cannot see the fixtures), but the ORDER of operations is
     * exactly what the lock imposes on two concurrent requests.
     */
    public function test_only_one_of_two_expenses_can_consume_a_limited_balance(): void
    {
        [$agent, $till] = $this->cashierWithTill($this->rabat, '1000.00');
        $a = $this->pending($agent, $till, '700.00');
        $b = $this->pending($agent, $till, '700.00');
        $approver = $this->approver($this->rabat);
        $action = app(ApprouverDepense::class);

        $action->handle($a, $approver->employee);
        $this->assertSame('300.00', (string) $till->fresh()->solde);

        $this->expectException(ValidationException::class);

        try {
            $action->handle($b, $approver->employee);
        } finally {
            $this->assertSame(Depense::STATUT_APPROUVEE, $a->fresh()->statut);
            $this->assertSame(Depense::STATUT_EN_ATTENTE, $b->fresh()->statut);
            $this->assertSame(0, $this->ledgerDebits($b));
            // Never negative.
            $this->assertSame('300.00', (string) $till->fresh()->solde);
        }
    }

    // ── Test 6 · centre isolation ───────────────────────────────────────

    public function test_the_check_uses_the_expense_own_till_never_another_centre_till(): void
    {
        [$agentA, $tillA] = $this->cashierWithTill($this->rabat, '500.00');
        [, $tillB] = $this->cashierWithTill($this->marrakech, '10000.00');

        $depense = $this->pending($agentA, $tillA, '600.00');

        // A super-admin reaching every centre, working IN the expense's centre.
        $approver = $this->approver($this->rabat);
        $approver->employee->syncEtablissements([$this->rabat->id, $this->marrakech->id]);

        $this->actingAs($approver);
        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasErrors('statut');

        $this->assertSame(Depense::STATUT_EN_ATTENTE, $depense->fresh()->statut);
        $this->assertSame('500.00', (string) $tillA->fresh()->solde);
        // Marrakech's money was never consulted, let alone touched.
        $this->assertSame('10000.00', (string) $tillB->fresh()->solde);
        $this->assertSame(0, $this->ledgerDebits($depense));
    }

    // ── The approval-OFF path is an approval path too ───────────────────

    public function test_with_approval_off_a_direct_expense_is_refused_when_the_till_cannot_cover_it(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        [$agent, $till] = $this->cashierWithTill($this->rabat, '400.00');
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        $payload = [
            'type_depense_id' => $this->type()->id,
            'caisse_id' => $till->id,
            'montant' => '7650.00',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-08-29',
            'description' => '*',
        ];

        try {
            app(EnregistrerDepense::class)->handle($payload, $agent);
            $this->fail('The expense must be refused.');
        } catch (ValidationException $e) {
            $this->assertStringStartsWith("Impossible d'enregistrer cette dépense", $e->errors()['montant'][0]);
        }

        // No row at all — a refused expense leaves no trace.
        $this->assertSame(0, Depense::query()->where('caisse_id', $till->id)->count());
        $this->assertSame('400.00', (string) $till->fresh()->solde);

        // …and a covered one still goes straight through, debiting at once.
        $ok = app(EnregistrerDepense::class)->handle([...$payload, 'montant' => '400.00'], $agent);
        $this->assertSame(Depense::STATUT_APPROUVEE, $ok->statut);
        $this->assertSame('0.00', (string) $till->fresh()->solde);
    }
}
