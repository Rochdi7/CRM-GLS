<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Support\CaisseResolver;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Payment-method accounts per centre (24/08/2026 refactor,
 * docs/caisse-comptes-methode-architecture.md).
 *
 * The invariant under test: every dirham sits in exactly ONE `caisses` row.
 *  - Espèces → the cashier's physical till;
 *  - TPE / Chèque / Virement → the centre's account for that method,
 *    and NEVER the physical till;
 *  - dépenses and remboursements always debit the physical till, whatever
 *    label they carry (accounting rule: cash settles them);
 *  - a till transfer moves cash between cash accounts only;
 *  - nothing is derived on top of the stored balances any more.
 */
final class ComptesMethodeTest extends TestCase
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

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolledStudentWithFee(float $montant = 1500, ?Etablissement $centre = null): array
    {
        $centre ??= $this->centre;
        $student = Student::factory()->create(['etablissement_id' => $centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    private function compte(Etablissement $centre, string $methode): Caisse
    {
        return Caisse::query()->where('etablissement_id', $centre->id)->where('type', $methode)->firstOrFail();
    }

    private function payLine(User $user, Student $student, Inscription $inscription, InscriptionFee $fee, string $montant, string $methode): void
    {
        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => $montant, 'methode' => $methode, 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect(route('backoffice.encaissements.index'));
    }

    // ── Provisioning & integrity ────────────────────────────────────────

    public function test_a_centre_is_created_with_its_three_method_accounts(): void
    {
        foreach (Caisse::TYPES_METHODE as $methode) {
            $compte = $this->compte($this->centre, $methode);
            $this->assertSame('0.00', (string) $compte->solde);
            $this->assertNull($compte->responsable_employee_id);
            $this->assertStringContainsString($this->centre->nom_centre, $compte->nom);
        }
    }

    public function test_a_centre_can_never_hold_two_accounts_for_the_same_method(): void
    {
        $this->expectException(QueryException::class);

        Caisse::create([
            'nom' => 'Doublon', 'type' => Caisse::TYPE_TPE,
            'etablissement_id' => $this->centre->id, 'statut' => Caisse::STATUT_ACTIVE,
        ]);
    }

    public function test_a_method_account_must_belong_to_a_centre_and_to_no_employee(): void
    {
        $this->expectException(QueryException::class);

        Caisse::create(['nom' => 'Orphelin', 'type' => Caisse::TYPE_VIREMENT, 'etablissement_id' => null]);
    }

    // ── Encaissements ───────────────────────────────────────────────────

    public function test_a_cash_payment_credits_the_cashiers_till_only(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_ESPECES);

        $this->assertSame('1000.00', (string) $user->employee->caisses()->first()->fresh()->solde);
        foreach (Caisse::TYPES_METHODE as $methode) {
            $this->assertSame('0.00', (string) $this->compte($this->centre, $methode)->solde);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonCashMethods')]
    public function test_a_non_cash_payment_credits_only_the_centres_account_for_that_method(string $methode): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        if ($methode === Encaissement::METHODE_CHEQUE) {
            // Cheque lines must reference a tracked cheque of the student.
            $cheque = \App\Models\Cheque::create([
                'reference' => 'CHQ-1', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
                'numero_cheque' => '0001', 'banque' => 'BMCE', 'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
                'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_EN_POSSESSION,
                'montant' => 1000, 'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
            ]);
            $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
                'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
                'payment_lines' => [[
                    'fee_id' => $fee->id, 'montant' => '1000', 'methode' => $methode, 'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id,
                ]],
            ])->assertSessionHasNoErrors();
        } else {
            $this->payLine($user, $student, $inscription, $fee, '1000', $methode);
        }

        $compte = $this->compte($this->centre, $methode);
        $encaissement = Encaissement::query()->firstOrFail();

        // The right account, and the row points at it.
        $this->assertSame('1000.00', (string) $compte->fresh()->solde);
        $this->assertSame($compte->id, $encaissement->caisse_id);

        // The physical till did NOT move — the bug this refactor fixes.
        $this->assertSame('0.00', (string) $user->employee->caisses()->first()->fresh()->solde);

        // Nor did any other method account.
        foreach (array_diff(Caisse::TYPES_METHODE, [$methode]) as $other) {
            $this->assertSame('0.00', (string) $this->compte($this->centre, $other)->solde);
        }

        // The fee is settled exactly as before.
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
    }

    /** @return array<string, array{string}> */
    public static function nonCashMethods(): array
    {
        return [
            'TPE' => [Encaissement::METHODE_TPE],
            'Chèque' => [Encaissement::METHODE_CHEQUE],
            'Virement' => [Encaissement::METHODE_VIREMENT],
        ];
    }

    public function test_one_submit_with_mixed_methods_splits_across_accounts(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20'],
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame('500.00', (string) $user->employee->caisses()->first()->fresh()->solde);
        $this->assertSame('1000.00', (string) $this->compte($this->centre, Encaissement::METHODE_TPE)->solde);
    }

    public function test_a_non_cash_payment_goes_to_the_active_centre_and_never_to_another_centres_account(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $rabat = Etablissement::factory()->create();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        // The employee is working in the OTHER centre (context switcher).
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($rabat->id);
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_VIREMENT);

        $this->assertSame('1000.00', (string) $this->compte($rabat, Encaissement::METHODE_VIREMENT)->solde);
        $this->assertSame('0.00', (string) $this->compte($this->centre, Encaissement::METHODE_VIREMENT)->solde);
        $this->assertSame('0.00', (string) $this->compte($rabat, Encaissement::METHODE_TPE)->solde);
        $this->assertSame('0.00', (string) $user->employee->caisses()->first()->fresh()->solde);
    }

    public function test_all_centres_context_falls_back_to_the_agents_primary_centre(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement(null);

        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_TPE);

        $this->assertSame('1000.00', (string) $this->compte($this->centre, Encaissement::METHODE_TPE)->solde);
    }

    public function test_a_non_cash_avance_credits_the_method_account_and_applying_it_credits_nothing_twice(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        [$student, , $fee] = $this->enrolledStudentWithFee(800);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->actingAs($user)->post(route('backoffice.avances.store'), [
            'student_id' => $student->id, 'montant' => '800', 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-20',
        ])->assertSessionHasNoErrors();

        $compte = $this->compte($this->centre, Encaissement::METHODE_TPE);
        $avance = Encaissement::query()->whereNull('inscription_fee_id')->firstOrFail();
        $this->assertSame('800.00', (string) $compte->fresh()->solde);
        $this->assertSame($compte->id, $avance->caisse_id);
        $this->assertSame('0.00', (string) $user->employee->caisses()->first()->fresh()->solde);

        app(\App\Domain\Payments\Actions\AppliquerAvance::class)->handle($avance, $fee, 800.0);

        $this->assertSame('800.00', (string) $compte->fresh()->solde);
        $this->assertSame(2, Encaissement::count());
        $this->assertSame($compte->id, Encaissement::query()->whereNotNull('applied_from_encaissement_id')->firstOrFail()->caisse_id);
    }

    public function test_cancelling_a_non_cash_payment_reverses_the_same_account_even_if_the_method_is_tampered(): void
    {
        $user = $this->superAdmin();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_TPE);

        $encaissement = Encaissement::query()->firstOrFail();
        $compte = $this->compte($this->centre, Encaissement::METHODE_TPE);
        $till = $user->employee->caisses()->first();

        // The reversal follows the STORED caisse_id, not the label.
        $encaissement->forceFill(['methode' => Encaissement::METHODE_ESPECES])->saveQuietly();

        $this->actingAs($user)->delete(route('backoffice.encaissements.destroy', $encaissement))->assertRedirect();

        $this->assertSame('0.00', (string) $compte->fresh()->solde);
        $this->assertSame('0.00', (string) $till->fresh()->solde);
    }

    public function test_the_method_of_a_recorded_payment_is_frozen(): void
    {
        $user = $this->superAdmin();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_TPE);
        $encaissement = Encaissement::query()->firstOrFail();

        $this->actingAs($user)->put(route('backoffice.encaissements.update', $encaissement), [
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-21',
        ])->assertSessionHasErrors('methode');

        // Echoing the stored value back is fine (the edit modal does that).
        $this->actingAs($user)->put(route('backoffice.encaissements.update', $encaissement), [
            'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-21',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Encaissement::METHODE_TPE, $encaissement->fresh()->methode);
        $this->assertSame('2025-09-21', $encaissement->fresh()->date_paiement->toDateString());
    }

    // ── Dépenses & remboursements: always the physical till ──────────────

    public function test_an_expense_always_debits_the_cashiers_till_whatever_its_label(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $user = $this->userWith('expenses.view', 'expenses.create');
        $type = TypeDepense::create(['nom' => 'Fournitures', 'is_system' => false, 'statut' => 'Actif']);
        $till = $user->employee->caisses()->first();

        $this->actingAs($user)->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $type->id, 'montant' => '300', 'methode_paiement' => Encaissement::METHODE_VIREMENT,
            'date_depense' => '2025-09-22', 'description' => 'Test',
        ])->assertSessionHasNoErrors();

        $this->assertSame('-300.00', (string) $till->fresh()->solde);
        $this->assertSame($till->id, Depense::query()->firstOrFail()->caisse_id);
        $this->assertSame('0.00', (string) $this->compte($this->centre, Encaissement::METHODE_VIREMENT)->solde);
    }

    public function test_a_refund_always_debits_the_cashiers_till(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create', 'payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_TPE);
        $encaissement = Encaissement::query()->firstOrFail();
        $till = $user->employee->caisses()->first();

        $this->actingAs($user)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id, 'encaissement_id' => $encaissement->id,
            'montant' => '200', 'date_remboursement' => '2025-09-23', 'motif' => 'Test',
        ])->assertSessionHasNoErrors();

        $this->assertSame('-200.00', (string) $till->fresh()->solde);
        // The TPE account keeps the 1000: cash left the till, not the card account.
        $this->assertSame('1000.00', (string) $this->compte($this->centre, Encaissement::METHODE_TPE)->solde);
    }

    public function test_refunding_a_rejected_cheque_payment_reverses_the_cheque_account_not_the_till(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create', 'payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $cheque = \App\Models\Cheque::create([
            'reference' => 'CHQ-R', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => '0009', 'banque' => 'BMCE', 'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_EN_POSSESSION,
            'montant' => 1000, 'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
        ]);
        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [[
                'fee_id' => $fee->id, 'montant' => '1000', 'methode' => Encaissement::METHODE_CHEQUE,
                'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id,
            ]],
        ])->assertSessionHasNoErrors();

        $compteCheque = $this->compte($this->centre, Encaissement::METHODE_CHEQUE);
        $till = $user->employee->caisses()->first();
        $encaissement = Encaissement::query()->firstOrFail();
        $this->assertSame('1000.00', (string) $compteCheque->fresh()->solde);

        // The bank bounces the cheque; the refund is recorded against the payment.
        $cheque->update(['statut' => \App\Models\Cheque::STATUT_REJETE]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id, 'encaissement_id' => $encaissement->id,
            'montant' => '1000', 'date_remboursement' => '2025-10-05', 'motif' => 'Chèque 0009 rejeté',
        ])->assertSessionHasNoErrors();

        // The money that never existed leaves the Chèque account; the cash till is untouched.
        $this->assertSame('0.00', (string) $compteCheque->fresh()->solde);
        $this->assertSame('0.00', (string) $till->fresh()->solde);
        $this->assertSame($compteCheque->id, \App\Models\Remboursement::query()->sole()->caisse_id);
    }

    public function test_refunding_a_cheque_payment_that_was_not_rejected_still_debits_the_till(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create', 'payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $cheque = \App\Models\Cheque::create([
            'reference' => 'CHQ-OK', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => '0010', 'banque' => 'BMCE', 'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_ENCAISSE,
            'montant' => 1000, 'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
        ]);
        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [[
                'fee_id' => $fee->id, 'montant' => '1000', 'methode' => Encaissement::METHODE_CHEQUE,
                'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id,
            ]],
        ])->assertSessionHasNoErrors();
        $encaissement = Encaissement::query()->firstOrFail();

        // A student cancels: the cheque was good, cash is handed back from the till.
        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id, 'encaissement_id' => $encaissement->id,
            'montant' => '400', 'date_remboursement' => '2025-10-05', 'motif' => 'Annulation',
        ])->assertSessionHasNoErrors();

        $this->assertSame('1000.00', (string) $this->compte($this->centre, Encaissement::METHODE_CHEQUE)->solde);
        $this->assertSame('-400.00', (string) $user->employee->caisses()->first()->fresh()->solde);
    }

    // ── Transfers: cash only ───────────────────────────────────────────

    public function test_a_transfer_towards_a_method_account_is_refused_and_moves_nothing(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $compte = $this->compte($this->centre, Encaissement::METHODE_TPE);

        $this->actingAs($user)->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $compte->id, 'montant' => '100',
        ])->assertSessionHasErrors('caisse_destination_id');

        $this->assertSame(0, CaisseTransfer::count());
    }

    public function test_the_transfer_form_offers_cash_accounts_only(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        Employee::factory()->create(['etablissement_id' => $this->centre->id]); // another till

        $this->actingAs($user)
            ->get(route('backoffice.caisses.index', ['tab' => 'transferts']))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $ids = collect($page->toArray()['props']['transferCaisses'])->pluck('id');
                $types = Caisse::query()->whereIn('id', $ids)->pluck('type')->unique();
                $this->assertNotEmpty($ids);
                $this->assertEmpty($types->intersect(Caisse::TYPES_METHODE), 'A method account was offered as a transfer target.');
            });
    }

    // ── Screens: no double counting ────────────────────────────────────

    public function test_comptes_de_caisse_lists_every_account_once_with_stored_balances(): void
    {
        $user = $this->superAdmin();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20'],
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes']))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $props = $page->toArray()['props'];
                $rows = collect($props['comptes']['data']);

                // No derived rows: every row is a real account with an id.
                $this->assertTrue($rows->every(fn ($r) => is_int($r['id'])));
                $this->assertArrayNotHasKey('derived', $rows->first());

                // Exactly one TPE row for the centre, carrying the stored solde.
                $tpe = $rows->where('type', Encaissement::METHODE_TPE)->where('centre', $this->centre->nom_centre);
                $this->assertCount(1, $tpe);
                $this->assertSame('1000.00', $tpe->first()['solde']);
                $this->assertTrue($tpe->first()['compteMethode']);

                // Σ of the listed accounts = 500 (till) + 1000 (TPE): the 1000 is counted once.
                $this->assertSame(1500.0, (float) $rows->sum(fn ($r) => (float) $r['solde']));
            });
    }

    public function test_ma_caisse_shows_the_cash_solde_and_caisse_globale_the_method_accounts(): void
    {
        $user = $this->userWith('cash-registers.view', 'payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20'],
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('backoffice.caisses.index', ['tab' => 'ma-caisse']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Everything collected, broken down — but the SOLDE is cash only
                // (the screenshot bug: 2600 for 1300 of cash).
                ->where('journalMine.totalEncaissements', '1500.00')
                ->where('journalMine.encaissementsParMethode.Espèces', '500.00')
                ->where('journalMine.encaissementsParMethode.TPE', '1000.00')
                ->where('journalMine.solde', '500.00')
            );

        // « Caisse globale »: one card per kind, each account listed once.
        $this->actingAs($user)
            ->get(route('backoffice.caisses.index', ['tab' => 'globale']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('globale.cards.0.type', Caisse::TYPE_CAISSIERE)
                ->where('globale.cards.0.total', '500.00')
                ->where('globale.cards.1.type', Caisse::TYPE_TPE)
                ->where('globale.cards.1.total', '1000.00')
                ->where('globale.total', '1500.00')
                ->has('globale.comptes.'.Caisse::TYPE_TPE, 1)
            );
    }

    // ── Audit ──────────────────────────────────────────────────────────

    public function test_a_method_account_movement_is_journaled_like_any_till_movement(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_VIREMENT);

        $compte = $this->compte($this->centre, Encaissement::METHODE_VIREMENT);
        $entry = Activity::query()->where('event', 'solde_movement')->latest('id')->firstOrFail();

        $this->assertSame($compte->id, (int) $entry->subject_id);
        $this->assertSame('0.00', $entry->properties['solde_avant']);
        $this->assertSame('1000.00', $entry->properties['solde_apres']);
        $this->assertSame(Encaissement::METHODE_VIREMENT, $entry->properties['methode']);
    }

    // ── Reconciliation command ─────────────────────────────────────────

    /** A pre-refactor row: non-cash money booked in the cashier's till. */
    private function legacyNonCashRow(Caisse $till, string $methode, float $montant, ?Etablissement $studentCentre = null): Encaissement
    {
        [$student, , $fee] = $this->enrolledStudentWithFee($montant, $studentCentre);
        app(\App\Domain\Finance\Support\CaisseLedger::class)->credit($till->id, $montant, 'legacy');

        return Encaissement::create([
            'reference' => 'ENC-'.uniqid(), 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $till->id, 'agent_id' => $till->responsable_employee_id,
            'montant' => $montant, 'methode' => $methode, 'date_paiement' => '2025-09-10',
        ]);
    }

    public function test_recalculer_soldes_dry_run_changes_nothing(): void
    {
        $till = $this->userWith('payments.view')->employee->caisses()->first();
        $this->legacyNonCashRow($till, Encaissement::METHODE_TPE, 700);

        $this->assertSame(0, Artisan::call('caisse:recalculer-soldes'));

        $this->assertSame('700.00', (string) $till->fresh()->solde);
        $this->assertSame('0.00', (string) $this->compte($this->centre, Encaissement::METHODE_TPE)->solde);
    }

    public function test_recalculer_soldes_apply_rehomes_through_the_ledger_and_is_idempotent(): void
    {
        $till = $this->userWith('payments.view')->employee->caisses()->first();
        $row = $this->legacyNonCashRow($till, Encaissement::METHODE_TPE, 700);
        $before = Activity::query()->where('event', 'solde_movement')->count();

        $this->assertSame(0, Artisan::call('caisse:recalculer-soldes', ['--apply' => true]));

        $compte = $this->compte($this->centre, Encaissement::METHODE_TPE);
        $this->assertSame('0.00', (string) $till->fresh()->solde);
        $this->assertSame('700.00', (string) $compte->fresh()->solde);
        $this->assertSame($compte->id, $row->fresh()->caisse_id);
        // Both legs journaled.
        $this->assertSame($before + 2, Activity::query()->where('event', 'solde_movement')->count());

        // Second run: nothing left to move.
        $this->assertSame(0, Artisan::call('caisse:recalculer-soldes', ['--apply' => true]));
        $this->assertSame('700.00', (string) $compte->fresh()->solde);
        $this->assertSame($before + 2, Activity::query()->where('event', 'solde_movement')->count());
    }

    public function test_recalculer_soldes_refuses_to_guess_an_ambiguous_centre(): void
    {
        $till = $this->userWith('payments.view')->employee->caisses()->first();
        $rabat = Etablissement::factory()->create();
        // Student of ANOTHER centre paid into this centre's till — which centre's TPE?
        $this->legacyNonCashRow($till, Encaissement::METHODE_TPE, 700, $rabat);

        $this->assertSame(1, Artisan::call('caisse:recalculer-soldes', ['--apply' => true]));
        $this->assertSame('700.00', (string) $till->fresh()->solde);

        // An explicit rule unblocks it.
        $this->assertSame(0, Artisan::call('caisse:recalculer-soldes', ['--apply' => true, '--ambiguous' => 'student']));
        $this->assertSame('700.00', (string) $this->compte($rabat, Encaissement::METHODE_TPE)->solde);
        $this->assertSame('0.00', (string) $this->compte($this->centre, Encaissement::METHODE_TPE)->solde);
    }

    public function test_resolver_refuses_a_non_cash_payment_with_no_centre_at_all(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $employee = Employee::factory()->create(['etablissement_id' => null]);

        app(CaisseResolver::class)->resolveFor($employee, Encaissement::METHODE_TPE);
    }
}
