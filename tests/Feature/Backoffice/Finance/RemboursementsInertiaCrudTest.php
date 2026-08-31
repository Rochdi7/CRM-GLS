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
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Remboursements endpoints (RemboursementController
 * store/update, served from the Depenses tabbed page) built alongside the
 * unchanged Livewire RemboursementsIndex fallback — see
 * RemboursementsCrudTest for the Livewire-side coverage of the same
 * business rules (docs/phase-10-finance-audit.md §2.6). No maximum-refund
 * check is added (docs/phase-10-finance-mapping.md Q1: preserved) and no
 * detail page is added (Q2: preserved).
 */
final class RemboursementsInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    /**
     * @return array{0: Student, 1: Encaissement} a student with one
     *     fee-targeted payment (not an avance)
     */
    private function studentWithPayment(float $montant = 500): array
    {
        $annee = AnneeScolaire::firstOrCreate(
            ['nom' => '2025/2026'],
            ['date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true],
        );
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_PAYE,
        ]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'agent_id' => Employee::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        return [$student, $encaissement];
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

    public function test_depenses_index_exposes_remboursements_when_permitted(): void
    {
        $this->actingAs($this->userWith('refunds.view'))
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewDepenses', false)
                ->where('canViewRemboursements', true)
                ->has('remboursements')
                ->has('students')
            );
    }

    public function test_a_remboursement_can_be_created_and_decrements_the_caisse(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 1000]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '150',
            'date_remboursement' => '2025-09-20',
            'motif' => 'Annulation',
        ])->assertRedirect(route('backoffice.depenses.index', ['tab' => 'remboursements']));

        $remboursement = Remboursement::where('beneficiaire_id', $student->id)->first();
        $this->assertNotNull($remboursement);
        $this->assertStringStartsWith('RMB-', $remboursement->reference);
        $this->assertSame('850.00', (string) $caisse->fresh()->solde);
    }

    /**
     * Regression: the create form never has a caisse field (the till is
     * always the acting employee's own), so the real-world payload has NO
     * caisse_id at all. Before this fix, caisse_id was still `required`
     * server-side, which silently failed every submission from the actual
     * UI — this is the "click Enregistrer, nothing happens" bug.
     */
    public function test_a_remboursement_can_be_created_with_no_caisse_id_in_the_payload(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 1000]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'montant' => '150',
            'date_remboursement' => '2025-09-20',
        ])->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('backoffice.depenses.index', ['tab' => 'remboursements']));

        $remboursement = Remboursement::where('beneficiaire_id', $student->id)->first();
        $this->assertNotNull($remboursement);
        $this->assertSame($caisse->id, $remboursement->caisse_id);
        $this->assertSame('850.00', (string) $caisse->fresh()->solde);
    }

    public function test_no_maximum_refund_amount_check_exists(): void
    {
        // Confirms the deliberate absence of a cap (docs/phase-10-finance-
        // mapping.md Q1) — a refund larger than the till's balance is still
        // accepted, matching current live behavior exactly.
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 100]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '5000',
            'date_remboursement' => '2025-09-20',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('-4900.00', (string) $caisse->fresh()->solde);
    }

    public function test_montant_and_caisse_are_frozen_on_update_even_when_tampered(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create', 'refunds.update');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 1000]);
        $otherCaisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $remboursement = Remboursement::create([
            'reference' => 'RMB-EDIT', 'beneficiaire_id' => $student->id, 'caisse_id' => $caisse->id,
            'montant' => 150, 'date_remboursement' => '2025-09-20', 'agent_id' => $user->employee->id,
        ]);

        $this->put(route('backoffice.remboursements.update', $remboursement), [
            'date_remboursement' => '2025-09-21',
            'motif' => 'Corrigé',
            // Tampered — must have zero effect.
            'montant' => '9999',
            'caisse_id' => $otherCaisse->id,
            'beneficiaire_id' => Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
        ])->assertSessionDoesntHaveErrors();

        $fresh = $remboursement->fresh();
        $this->assertSame('150.00', (string) $fresh->montant);
        $this->assertSame($caisse->id, $fresh->caisse_id);
        $this->assertSame($student->id, $fresh->beneficiaire_id);
        $this->assertSame('Corrigé', $fresh->motif);
    }

    public function test_no_delete_route_and_no_show_route_exist_for_refunds(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.remboursements.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.remboursements.show'));
    }

    public function test_create_is_forbidden_without_the_permission(): void
    {
        $this->actingAs($this->userWith('refunds.view'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '150',
            'date_remboursement' => '2025-09-20',
        ])->assertForbidden();
    }

    /**
     * Covers the create form's "which payment are we refunding?" cascade
     * (GetStudentPaymentsForRefund) — selecting a student lists everything of
     * theirs that can still be given back.
     *
     * ⚠ Avances ARE listed (31/08/2026). This test previously asserted the
     * opposite ("excluding unallocated avances"), which is what shipped the
     * bug: an avance is money the school holds and has not earned, so it is
     * the most refundable thing there is, and EnregistrerRemboursement has a
     * dedicated branch for it. Excluding it here made that branch unreachable
     * from the UI. See RefundAvanceVisibilityTest for the full flow.
     */
    public function test_student_payments_lists_fee_payments_and_avances_alike(): void
    {
        $this->actingAs($this->userWith('refunds.view', 'refunds.create'));
        [$student, $encaissement] = $this->studentWithPayment(500);

        $avance = Encaissement::create([
            'reference' => 'ENC-AVANCE', 'student_id' => $student->id, 'inscription_fee_id' => null,
            'caisse_id' => $encaissement->caisse_id, 'agent_id' => $encaissement->agent_id,
            'montant' => 200, 'methode' => 'Espèces', 'date_paiement' => '2025-09-21',
        ]);

        $response = $this->get(route('backoffice.students.payments-for-refund', $student))->json();
        $rows = collect($response['payments'])->keyBy('id');

        $this->assertCount(2, $response['payments']);

        $this->assertFalse($rows[$encaissement->id]['isAvance']);
        $this->assertSame('500.00', $rows[$encaissement->id]['montant']);
        $this->assertSame('0.00', $rows[$encaissement->id]['dejaRembourse']);
        $this->assertSame('500.00', $rows[$encaissement->id]['montantRemboursable']);

        $this->assertTrue($rows[$avance->id]['isAvance']);
        $this->assertSame('200.00', $rows[$avance->id]['montantRemboursable']);
    }

    /**
     * Picking a payment links the refund back to it (encaissement_id) —
     * traceability, not a hard cap: the amount stays whatever was submitted.
     */
    public function test_a_remboursement_can_be_linked_to_the_payment_it_refunds(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 1000]);
        [$student, $encaissement] = $this->studentWithPayment(500);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $encaissement->id,
            'caisse_id' => $caisse->id,
            'montant' => '500',
            'date_remboursement' => '2025-09-22',
        ])->assertRedirect(route('backoffice.depenses.index', ['tab' => 'remboursements']));

        $remboursement = Remboursement::where('beneficiaire_id', $student->id)->firstOrFail();
        $this->assertSame($encaissement->id, $remboursement->encaissement_id);

        // The whole 500 came back, so the row has nothing left to give and
        // drops out of the picker entirely — offering it again would only
        // produce a submit the action rejects.
        $response = $this->get(route('backoffice.students.payments-for-refund', $student))->json();
        $this->assertSame([], $response['payments']);
    }

    /**
     * The partial case the assertion above no longer covers: a payment only
     * half given back stays in the picker, reporting what it already refunded
     * and what is still refundable.
     */
    public function test_a_partly_refunded_payment_reports_what_is_left(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $user->employee->caisses()->first()->update(['solde' => 1000]);
        [$student, $encaissement] = $this->studentWithPayment(500);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $encaissement->id,
            'montant' => '200',
            'date_remboursement' => '2025-09-22',
        ])->assertSessionDoesntHaveErrors();

        $response = $this->get(route('backoffice.students.payments-for-refund', $student))->json();

        $this->assertCount(1, $response['payments']);
        $this->assertSame('200.00', $response['payments'][0]['dejaRembourse']);
        $this->assertSame('300.00', $response['payments'][0]['montantRemboursable']);
    }

    public function test_a_remboursement_without_a_linked_payment_is_still_allowed(): void
    {
        // Goodwill/legacy refunds unrelated to any tracked payment stay valid
        // (encaissement_id is nullable — no hard requirement was added).
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 1000]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '150',
            'date_remboursement' => '2025-09-20',
        ])->assertSessionDoesntHaveErrors();

        $remboursement = Remboursement::where('beneficiaire_id', $student->id)->firstOrFail();
        $this->assertNull($remboursement->encaissement_id);
    }
}
