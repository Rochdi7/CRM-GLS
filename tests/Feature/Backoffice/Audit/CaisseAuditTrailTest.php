<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Audit;

use App\Domain\Audit\Queries\GetActivityLogList;
use App\Domain\Expenses\Actions\ApprouverDepense;
use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Support\Settings\AppSettings;
use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Payments\Actions\SupprimerEncaissement;
use App\Models\Activity;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The money trail — « l'argent est tout dans ce CRM ».
 *
 * `caisses.solde` is a stored number with no ledger behind it, and every action
 * used to move it with `increment('solde', …)`: raw SQL that fires no Eloquent
 * event, so the balance change was invisible to the audit journal. A payment
 * appeared in the trail, but the cash it moved did not.
 *
 * These tests pin the fix: every till movement is journalled with the complete
 * arithmetic (balance before → amount → balance after) so a caisse can be
 * verified line by line, and both legs of a transfer are recorded so money
 * never appears to vanish between two tills.
 */
final class CaisseAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    private function employee(): Employee
    {
        $user = User::factory()->create();

        return Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);
    }

    /**
     * A standalone cash account with an opening balance.
     *
     * « Externe », not an ownerless « Caissière »: an employee owns exactly
     * one physical till (partial unique index
     * `caisses_une_caissiere_par_employe`, 24/08/2026 audit) and it is
     * provisioned with the employee — a test that needs an employee's till
     * asks for `$employee->till()`, never a hand-made second one.
     */
    private function caisse(float $solde = 1000): Caisse
    {
        return Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->centre->id,
            'solde' => $solde,
        ]);
    }

    /** The money block the journal exposes for the most recent movement. */
    private function lastMovement(): array
    {
        $entry = Activity::query()
            ->where('event', 'solde_movement')
            ->latest('id')
            ->firstOrFail();

        return app(GetActivityLogList::class)->find($entry->id)['money'];
    }

    // ── The ledger itself ───────────────────────────────────────────────

    public function test_a_credit_records_the_full_arithmetic(): void
    {
        $caisse = $this->caisse(1000);

        app(CaisseLedger::class)->credit($caisse->id, 250.50, 'Test entrée');

        $this->assertSame('1250.50', (string) $caisse->fresh()->solde);

        $money = $this->lastMovement();

        // Before → amount → after is the whole point: a till is verified by
        // reading this sequence, not by recomputing it from other tables.
        $this->assertSame('1 000,00', $money['soldeAvant']);
        $this->assertSame('250,50', $money['montant']);
        $this->assertSame('1 250,50', $money['soldeApres']);
        $this->assertTrue($money['isCredit']);
        $this->assertTrue($money['coherent']);
    }

    public function test_a_debit_records_the_full_arithmetic(): void
    {
        $caisse = $this->caisse(1000);

        app(CaisseLedger::class)->debit($caisse->id, 400, 'Test sortie');

        $this->assertSame('600.00', (string) $caisse->fresh()->solde);

        $money = $this->lastMovement();

        $this->assertSame('1 000,00', $money['soldeAvant']);
        $this->assertSame('600,00', $money['soldeApres']);
        $this->assertFalse($money['isCredit']);
        $this->assertTrue($money['coherent']);
    }

    public function test_a_non_positive_amount_is_refused(): void
    {
        // Direction is carried by credit()/debit(); a negative amount would
        // silently invert it, so it must fail loudly instead.
        $this->expectException(InvalidArgumentException::class);

        app(CaisseLedger::class)->credit($this->caisse()->id, -50, 'Montant négatif');
    }

    // ── Every money action feeds the trail ──────────────────────────────

    public function test_a_payment_journals_the_cash_it_brought_in(): void
    {
        $agent = $this->employee();
        $caisse = $this->caisse(1000);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $encaissement = app(EnregistrerEncaissement::class)->handle([
            'student_id' => $student->id,
            'montant' => '750.00',
            'methode' => 'Espèces',
            'date_paiement' => now()->toDateString(),
            'caisse_id' => $caisse->id,
        ], $agent);

        $this->assertSame('1750.00', (string) $caisse->fresh()->solde);

        $money = $this->lastMovement();

        $this->assertSame('1 000,00', $money['soldeAvant']);
        $this->assertSame('1 750,00', $money['soldeApres']);
        // The movement names the record that caused it, so an investigator can
        // jump straight from the till line to the payment.
        $this->assertSame($encaissement->reference, $money['origineReference']);
        $this->assertTrue($money['coherent']);
    }

    public function test_deleting_a_payment_journals_the_reversal(): void
    {
        $agent = $this->employee();
        $caisse = $this->caisse(1000);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $encaissement = app(EnregistrerEncaissement::class)->handle([
            'student_id' => $student->id,
            'montant' => '500.00',
            'methode' => 'Espèces',
            'date_paiement' => now()->toDateString(),
            'caisse_id' => $caisse->id,
        ], $agent);

        app(SupprimerEncaissement::class)->handle($encaissement);

        $this->assertSame('1000.00', (string) $caisse->fresh()->solde);

        $money = $this->lastMovement();

        // The reversal is its own line — the trail shows money in AND money
        // back out, never a silent return to the original balance.
        $this->assertFalse($money['isCredit']);
        $this->assertSame('1 500,00', $money['soldeAvant']);
        $this->assertSame('1 000,00', $money['soldeApres']);
        $this->assertStringContainsString('Annulation', (string) $money['motif']);
    }

    public function test_an_expense_journals_the_cash_it_took_out(): void
    {
        // Approval OFF: the expense debits on creation, and that movement is
        // the one the journal must carry.
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);

        $agent = $this->employee();
        $caisse = $this->caisse(2000);
        // TypeDepense has no factory (its rows are seeded), so create directly.
        $type = TypeDepense::create([
            'nom' => 'Fournitures de bureau',
            'is_system' => false,
            'statut' => TypeDepense::STATUT_ACTIF,
        ]);

        app(EnregistrerDepense::class)->handle([
            'type_depense_id' => $type->id,
            'caisse_id' => $caisse->id,
            'montant' => '300.00',
            'methode_paiement' => 'Espèces',
            'date_depense' => now()->toDateString(),
            'description' => 'Fournitures',
        ], $agent);

        $this->assertSame('1700.00', (string) $caisse->fresh()->solde);

        $money = $this->lastMovement();

        $this->assertFalse($money['isCredit']);
        $this->assertSame('2 000,00', $money['soldeAvant']);
        $this->assertSame('1 700,00', $money['soldeApres']);
    }

    public function test_a_pending_expense_journals_no_movement_until_approved(): void
    {
        // Approval ON: requesting must journal NO solde_movement at all - the
        // money is still in the till. The movement appears only on approval,
        // and it must be the one carrying the full arithmetic.
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);

        $agent = $this->employee();
        $caisse = $this->caisse(2000);
        $type = TypeDepense::create([
            'nom' => 'Fournitures de bureau',
            'is_system' => false,
            'statut' => TypeDepense::STATUT_ACTIF,
        ]);

        $depense = app(EnregistrerDepense::class)->handle([
            'type_depense_id' => $type->id,
            'caisse_id' => $caisse->id,
            'montant' => '300.00',
            'methode_paiement' => 'Espèces',
            'date_depense' => now()->toDateString(),
            'description' => 'Fournitures',
        ], $agent);

        $this->assertSame('2000.00', (string) $caisse->fresh()->solde);
        $this->assertSame(
            0,
            Activity::query()->where('event', 'solde_movement')->count(),
            'A pending expense must not journal any cash movement.',
        );

        app(ApprouverDepense::class)->handle($depense, $this->employee());

        $this->assertSame('1700.00', (string) $caisse->fresh()->solde);

        $money = $this->lastMovement();
        $this->assertFalse($money['isCredit']);
        $this->assertSame('2 000,00', $money['soldeAvant']);
        $this->assertSame('1 700,00', $money['soldeApres']);
    }

    public function test_a_refund_journals_the_cash_it_took_out(): void
    {
        $agent = $this->employee();
        $caisse = $this->caisse(2000);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        app(EnregistrerRemboursement::class)->handle([
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '150.00',
            'date_remboursement' => now()->toDateString(),
            'motif' => 'Annulation inscription',
        ], $agent);

        $this->assertSame('1850.00', (string) $caisse->fresh()->solde);

        $money = $this->lastMovement();

        $this->assertFalse($money['isCredit']);
        $this->assertSame('1 850,00', $money['soldeApres']);
    }

    // ── Transfers: the highest-risk operation ───────────────────────────

    public function test_a_validated_transfer_journals_both_legs(): void
    {
        $requester = $this->employee();
        $validator = $this->employee();
        $source = $this->caisse(5000);
        $destination = $this->caisse(1000);
        // Validation is RECIPIENT-ONLY: only the employee who owns the
        // destination till may accept (CLAUDE.md §11).
        $destination->update(['responsable_employee_id' => $validator->id]);

        $transfer = app(DemanderTransfertCaisse::class)->handle([
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => '800.00',
            'date_transfert' => now()->toDateTimeString(),
        ], $requester);

        // Requesting must not move a centime — validation does.
        $this->assertSame('5000.00', (string) $source->fresh()->solde);

        app(ValiderTransfertCaisse::class)->handle($transfer, $validator);

        $this->assertSame('4200.00', (string) $source->fresh()->solde);
        $this->assertSame('1800.00', (string) $destination->fresh()->solde);

        $movements = Activity::query()
            ->where('event', 'solde_movement')
            ->latest('id')
            ->take(2)
            ->get()
            ->reverse()
            ->values();

        // Two legs, not one: a transfer logged on a single side would read as
        // money disappearing from the source.
        $this->assertCount(2, $movements);

        $query = app(GetActivityLogList::class);
        $out = $query->find($movements[0]->id)['money'];
        $in = $query->find($movements[1]->id)['money'];

        $this->assertFalse($out['isCredit']);
        $this->assertSame('5 000,00', $out['soldeAvant']);
        $this->assertSame('4 200,00', $out['soldeApres']);

        $this->assertTrue($in['isCredit']);
        $this->assertSame('1 000,00', $in['soldeAvant']);
        $this->assertSame('1 800,00', $in['soldeApres']);
    }

    // ── Reading the trail ───────────────────────────────────────────────

    public function test_the_journal_can_be_filtered_to_one_till(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('audit-logs.view');

        $watched = $this->caisse(1000);
        $other = $this->caisse(1000);

        app(CaisseLedger::class)->credit($watched->id, 100, 'Sur la caisse suivie');
        app(CaisseLedger::class)->credit($other->id, 100, 'Sur une autre caisse');

        $this->actingAs($viewer->fresh())
            ->get(route('backoffice.audit-logs.index', ['caisseId' => $watched->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('entries.data', fn ($rows) => collect($rows)->isNotEmpty()
                    && collect($rows)->every(
                        fn (array $row): bool => $row['subjectId'] === $watched->id,
                    )));
    }

    public function test_movements_appear_under_the_money_only_scope(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('audit-logs.view');

        app(CaisseLedger::class)->credit($this->caisse()->id, 100, 'Mouvement argent');

        $this->actingAs($viewer->fresh())
            ->get(route('backoffice.audit-logs.index', ['financeOnly' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('entries.data', fn ($rows) => collect($rows)
                    ->contains(fn (array $row): bool => $row['money'] !== null)));
    }

    public function test_a_non_money_entry_carries_no_money_block(): void
    {
        // The UI decides what to render by testing for `money`, so it must be
        // null on everything that did not move cash.
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('audit-logs.view');

        $this->actingAs($viewer->fresh());
        Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $entry = Activity::query()->where('log_name', 'student')->latest('id')->firstOrFail();

        $this->assertNull(app(GetActivityLogList::class)->find($entry->id)['money']);
    }
}
