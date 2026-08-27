<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for the money invariants tightened by the 27/08/2026
 * Caisse audit: the refund cap on a LINKED payment, the controller-level
 * authorization of a payment delete, and the centre-context guard on chèque
 * lifecycle moves.
 *
 * `date_paiement` is deliberately NOT covered here — it stays editable by
 * design (docs/phase-10-finance-audit.md §2.4); see the note in
 * EncaissementController::update().
 */
final class RefundAndPaymentInvariantsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function annee(): AnneeScolaire
    {
        return AnneeScolaire::firstOrCreate(
            ['nom' => '2025/2026'],
            ['date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true],
        );
    }

    /**
     * A student with ONE fee-targeted payment (never an avance).
     *
     * @return array{0: Student, 1: Encaissement}
     */
    private function studentWithLinkedPayment(float $montant = 1000): array
    {
        $annee = $this->annee();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_PAYE,
        ]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 100000]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id,
            'agent_id' => Employee::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        return [$student, $encaissement];
    }

    /** A « Déposé » cheque belonging to $centre, ready to be transitioned. */
    private function makeCheque(Etablissement $centre, Student $student): Cheque
    {
        return Cheque::create([
            'reference' => 'CHQ-'.fake()->unique()->numerify('#####'),
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id,
            'numero_cheque' => fake()->unique()->numerify('CHQ###'),
            'banque' => 'Attijariwafa Bank',
            'montant' => 1000,
            'date_reception' => '2025-09-01',
            'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => Cheque::STATUT_DEPOSE,
            'etablissement_id' => $centre->id,
            'agent_id' => Employee::factory()->create(['etablissement_id' => $centre->id])->id,
        ]);
    }

    /** @return \Illuminate\Testing\TestResponse */
    private function refund(User $user, Student $student, Encaissement $enc, string $montant)
    {
        return $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $enc->id,
            'montant' => $montant,
            'date_remboursement' => '2025-09-25',
        ]);
    }

    // ── P1-4 · a LINKED refund is capped by the payment it points at ─────

    public function test_a_linked_payment_can_be_refunded_up_to_its_full_amount(): void
    {
        [$student, $enc] = $this->studentWithLinkedPayment(1000);
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);

        $this->refund($user, $student, $enc, '1000')->assertSessionHasNoErrors();
        $this->assertSame(1, $enc->remboursements()->count());
    }

    public function test_a_linked_refund_cannot_exceed_the_payment(): void
    {
        [$student, $enc] = $this->studentWithLinkedPayment(1000);
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);

        $this->refund($user, $student, $enc, '1001')->assertSessionHasErrors('montant');
        $this->assertSame(0, $enc->remboursements()->count());
    }

    public function test_linked_refunds_are_capped_cumulatively(): void
    {
        [$student, $enc] = $this->studentWithLinkedPayment(1000);
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);

        $this->refund($user, $student, $enc, '600')->assertSessionHasNoErrors();
        // 600 + 401 > 1000 — the second one must be refused…
        $this->refund($user, $student, $enc, '401')->assertSessionHasErrors('montant');
        $this->assertSame(1, $enc->remboursements()->count());

        // …while the exact remaining 400 is still allowed.
        $this->refund($user, $student, $enc, '400')->assertSessionHasNoErrors();
        $this->assertSame(2, $enc->remboursements()->count());
    }

    /**
     * The documented decision (docs/phase-10-finance-audit.md §2.6 Q1) is
     * untouched: a refund tied to NO payment stays uncapped.
     */
    public function test_an_unlinked_refund_is_still_uncapped(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'montant' => '5000',
            'date_remboursement' => '2025-09-25',
        ])->assertSessionDoesntHaveErrors();
    }

    // ── P0-2 · destroy authorizes in the controller too ──────────────────

    public function test_deleting_a_payment_without_the_permission_is_refused(): void
    {
        [, $enc] = $this->studentWithLinkedPayment(1000);
        $user = $this->userWith('payments.view', 'payments.update');
        $this->actingAs($user);

        $this->delete(route('backoffice.encaissements.destroy', $enc))->assertForbidden();
        $this->assertNotNull($enc->fresh());
    }

    // ── P1-9 · chèque lifecycle moves honour the active centre ───────────

    public function test_a_cheque_of_another_centre_cannot_be_transitioned(): void
    {
        $autre = Etablissement::factory()->create();
        $student = Student::factory()->create(['etablissement_id' => $autre->id]);

        $cheque = $this->makeCheque($autre, $student);

        $user = $this->userWith('cheques.view', 'cheques.update');
        $user->employee->syncEtablissements([$this->centre->id, $autre->id]);

        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->patch(route('backoffice.cheques.update-statut', $cheque), [
            'statut' => Cheque::STATUT_REJETE,
        ])->assertSessionHasErrors();

        $this->assertSame(Cheque::STATUT_DEPOSE, $cheque->fresh()->statut);
    }

    public function test_a_cheque_of_the_active_centre_can_still_be_transitioned(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $cheque = $this->makeCheque($this->centre, $student);

        $user = $this->userWith('cheques.view', 'cheques.update');
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->patch(route('backoffice.cheques.update-statut', $cheque), [
            'statut' => Cheque::STATUT_ENCAISSE,
        ])->assertSessionHasNoErrors();

        $this->assertSame(Cheque::STATUT_ENCAISSE, $cheque->fresh()->statut);
    }
}
