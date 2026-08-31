<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reported 31/08/2026: a payment converted into an avance could no longer be
 * refunded — it vanished from the Remboursement form's payment picker, so the
 * cashier had no way to give the money back.
 *
 * The cause was a single clause in GetStudentPaymentsForRefund:
 * `whereNotNull('inscription_fee_id')`. Converting detaches the fee, so the
 * row stopped matching — even though EnregistrerRemboursement has a dedicated,
 * fully-tested branch for refunding an avance. The read model and the write
 * action disagreed, and the UI followed the read model.
 *
 * These tests pin the picker to what the ACTION will actually accept, in both
 * directions: every row it offers must be refundable, and every refundable row
 * must be offered.
 */
final class RefundAvanceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        foreach (['refunds.view', 'refunds.create', 'payments.view', 'payments.create', 'cheques.view', 'cheques.create', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolled(float $montant = 2000): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de scolarite',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2026-01-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    /**
     * Paying by Chèque requires an already-recorded cheque (Chèques module —
     * there is no manual cheque entry on the payment form), so this helper
     * creates one for the student and hands its id to the payment line.
     */
    private function makeCheque(User $user, Student $student, string $montant): Cheque
    {
        return Cheque::create([
            'reference' => 'CHQ-'.fake()->unique()->numerify('#####'),
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id,
            'numero_cheque' => fake()->unique()->numerify('CHQ#####'),
            'montant' => $montant,
            'date_reception' => '2025-09-18',
            'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id,
            'agent_id' => $user->employee->id,
        ]);
    }

    private function payFee(User $user, Student $student, Inscription $inscription, InscriptionFee $fee, string $montant, ?string $methode = null): Encaissement
    {
        $methode ??= Encaissement::METHODE_ESPECES;

        $line = ['fee_id' => $fee->id, 'montant' => $montant, 'methode' => $methode, 'date_paiement' => '2025-09-20'];

        if ($methode === Encaissement::METHODE_CHEQUE) {
            $line['cheque_id'] = $this->makeCheque($user, $student, $montant)->id;
        }

        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [$line],
        ])->assertSessionHasNoErrors();

        return Encaissement::query()->where('student_id', $student->id)->latest('id')->firstOrFail();
    }

    /** @return list<array<string, mixed>> */
    private function picker(User $user, Student $student): array
    {
        return $this->actingAs($user)
            ->getJson(route('backoffice.students.payments-for-refund', $student))
            ->assertOk()
            ->json('payments');
    }

    private function refund(User $user, Student $student, ?int $encaissementId, string $montant)
    {
        return $this->actingAs($user)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $encaissementId,
            'montant' => $montant,
            'date_remboursement' => '2025-10-01',
            'motif' => 'Desistement',
        ]);
    }

    // -- The reported bug ------------------------------------------------

    public function test_a_payment_converted_into_an_avance_is_still_offered_for_refund(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00');

        // Before conversion the row is obviously refundable.
        $this->assertSame([$encaissement->id], array_column($this->picker($user, $student), 'id'));

        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id]);
        $this->assertTrue($encaissement->fresh()->isAvance());

        // THE BUG: this used to come back empty, stranding the money.
        $rows = $this->picker($user, $student);
        $this->assertSame([$encaissement->id], array_column($rows, 'id'));
        $this->assertTrue($rows[0]['isAvance']);
        $this->assertSame('2000.00', $rows[0]['montantRemboursable']);
    }

    public function test_a_converted_avance_can_actually_be_refunded_end_to_end(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00');
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id]);

        $till = $user->employee->till()->firstOrFail();
        $soldeAvant = (float) $till->fresh()->solde;

        $this->refund($user, $student, $encaissement->id, '2000.00')->assertSessionHasNoErrors();

        $remboursement = Remboursement::query()->where('beneficiaire_id', $student->id)->firstOrFail();
        $this->assertSame($encaissement->id, $remboursement->encaissement_id);
        $this->assertSame($till->id, $remboursement->caisse_id);
        $this->assertEqualsWithDelta($soldeAvant - 2000, (float) $till->fresh()->solde, 0.001);

        // Now fully used: neither applicable nor refundable a second time.
        $this->assertSame(0.0, $encaissement->fresh()->montantRestant());
        $this->assertSame([], $this->picker($user, $student));
    }

    // -- The picker must agree with the action, not overstate it ---------

    public function test_a_partly_applied_avance_offers_only_its_unallocated_remainder(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00');
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id]);

        app(AppliquerAvance::class)->handle($encaissement->fresh(), $fee->fresh(), 750.0);

        $byId = array_column($this->picker($user, $student), null, 'id');

        // The avance now holds 1 250, not 2 000 - pre-filling 2 000 would
        // have the action reject the submit.
        $this->assertArrayHasKey($encaissement->id, $byId);
        $this->assertSame('1250.00', $byId[$encaissement->id]['montantRemboursable']);
        $this->assertSame('2000.00', $byId[$encaissement->id]['montant']);

        $this->refund($user, $student, $encaissement->id, '1250.01')->assertSessionHasErrors('montant');
        $this->refund($user, $student, $encaissement->id, '1250.00')->assertSessionHasNoErrors();
    }

    public function test_an_application_row_is_never_offered_on_its_own(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00');
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id]);
        app(AppliquerAvance::class)->handle($encaissement->fresh(), $fee->fresh(), 750.0);

        $application = Encaissement::query()->whereNotNull('applied_from_encaissement_id')->firstOrFail();

        // Offering it too would let the same 750 leave the till twice - once
        // as the application row, once as the parent avance's remainder.
        $this->assertNotContains($application->id, array_column($this->picker($user, $student), 'id'));
    }

    public function test_a_fully_refunded_fee_payment_drops_out_of_the_picker(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00');

        $this->refund($user, $student, $encaissement->id, '2000.00')->assertSessionHasNoErrors();

        $this->assertSame([], $this->picker($user, $student));
    }

    public function test_a_partly_refunded_fee_payment_offers_only_what_is_left(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00');

        $this->refund($user, $student, $encaissement->id, '500.00')->assertSessionHasNoErrors();

        $rows = $this->picker($user, $student);
        $this->assertCount(1, $rows);
        $this->assertSame('500.00', $rows[0]['dejaRembourse']);
        $this->assertSame('1500.00', $rows[0]['montantRemboursable']);
    }

    // -- Cheque method ---------------------------------------------------

    private function compteCheque(): Caisse
    {
        return Caisse::query()
            ->where('etablissement_id', $this->centre->id)
            ->where('type', Caisse::TYPE_CHEQUE)
            ->firstOrFail();
    }

    public function test_a_cheque_funded_payment_is_offered_and_refunds_from_the_till_while_the_cheque_is_good(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00', Encaissement::METHODE_CHEQUE);

        // The payment credited the centre's Cheque account, not the till.
        $this->assertSame($this->compteCheque()->id, $encaissement->caisse_id);

        $rows = $this->picker($user, $student);
        $this->assertSame([$encaissement->id], array_column($rows, 'id'));
        $this->assertSame(Encaissement::METHODE_CHEQUE, $rows[0]['methode']);

        $till = $user->employee->till()->firstOrFail();
        $soldeTill = (float) $till->fresh()->solde;

        $this->refund($user, $student, $encaissement->id, '2000.00')->assertSessionHasNoErrors();

        // Cash settles a refund (accounting rule 24/08/2026): the till pays,
        // the Cheque account keeps the money it really received.
        $remboursement = Remboursement::query()->where('beneficiaire_id', $student->id)->firstOrFail();
        $this->assertSame($till->id, $remboursement->caisse_id);
        $this->assertEqualsWithDelta($soldeTill - 2000, (float) $till->fresh()->solde, 0.001);
    }

    public function test_a_converted_cheque_avance_is_offered_and_keeps_its_account(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00', Encaissement::METHODE_CHEQUE);

        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id]);
        $encaissement->refresh();
        $this->assertTrue($encaissement->isAvance());

        // Converting must not re-home the money: caisse_id is immutable.
        $this->assertSame($this->compteCheque()->id, $encaissement->caisse_id);

        // And it is offered for refund like any other avance.
        $rows = $this->picker($user, $student);
        $this->assertSame([$encaissement->id], array_column($rows, 'id'));
        $this->assertSame(Encaissement::METHODE_CHEQUE, $rows[0]['methode']);
        $this->assertSame('2000.00', $rows[0]['montantRemboursable']);
    }

    public function test_a_rejected_cheque_avance_reverses_the_cheque_account_not_the_till(): void
    {
        $user = $this->cashier();
        [$student, $inscription, $fee] = $this->enrolled();
        $encaissement = $this->payFee($user, $student, $inscription, $fee, '2000.00', Encaissement::METHODE_CHEQUE);
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id]);
        $encaissement->refresh();

        if ($encaissement->cheque_id === null) {
            $this->markTestSkipped('This payment path does not create a tracked Cheque record.');
        }

        // The bank bounces it.
        Cheque::query()->whereKey($encaissement->cheque_id)->update(['statut' => Cheque::STATUT_REJETE]);

        $this->assertSame([$encaissement->id], array_column($this->picker($user, $student), 'id'));

        $till = $user->employee->till()->firstOrFail();
        $compte = $this->compteCheque();
        $soldeTill = (float) $till->fresh()->solde;
        $soldeCheque = (float) $compte->fresh()->solde;

        $this->refund($user, $student, $encaissement->id, '2000.00')->assertSessionHasNoErrors();

        // Money that never reached the till must not leave it.
        $remboursement = Remboursement::query()->where('beneficiaire_id', $student->id)->firstOrFail();
        $this->assertSame($compte->id, $remboursement->caisse_id);
        $this->assertEqualsWithDelta($soldeTill, (float) $till->fresh()->solde, 0.001);
        $this->assertEqualsWithDelta($soldeCheque - 2000, (float) $compte->fresh()->solde, 0.001);
    }

    public function test_an_avance_of_another_student_is_never_offered(): void
    {
        $user = $this->cashier();
        [$studentA, $inscriptionA, $feeA] = $this->enrolled();
        [$studentB] = $this->enrolled();
        $encaissement = $this->payFee($user, $studentA, $inscriptionA, $feeA, '2000.00');
        app(ConvertirEncaissementsEnAvance::class)->handle($inscriptionA, [$encaissement->id]);

        $this->assertSame([], $this->picker($user, $studentB));

        $this->refund($user, $studentB, $encaissement->id, '100.00')->assertSessionHasErrors('encaissement_id');
    }
}
