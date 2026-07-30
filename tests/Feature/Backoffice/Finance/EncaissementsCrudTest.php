<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Livewire\Backoffice\Encaissements\EncaissementsIndex;
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
use Livewire\Livewire;
use Tests\TestCase;

final class EncaissementsCrudTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);
        $this->centre = Etablissement::factory()->create();
    }

    /** Super-admin WITH an employee profile (money operations need an agent). */
    private function globalUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($user->fresh());

        return $user;
    }

    private function caisse(?Etablissement $centre = null): Caisse
    {
        return Caisse::factory()->create([
            'etablissement_id' => ($centre ?? $this->centre)->id,
            'solde' => 0,
        ]);
    }

    /**
     * A student enrolled in a group of $this->centre with one 1500 MAD fee.
     *
     * @return array{0: Student, 1: Inscription, 2: InscriptionFee}
     */
    private function enrolledStudentWithFee(float $montant = 1500): array
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
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    public function test_create_locks_the_till_to_the_signed_in_employees_own_caisse(): void
    {
        $user = $this->globalUser();
        // Every employee owns a till, auto-provisioned by EmployeeObserver.
        // The modal has no till picker: caisse_id is always the employee's
        // own till, set server-side, never chosen from a dropdown.
        $own = Caisse::query()->where('responsable_employee_id', $user->employee->id)->firstOrFail();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->assertSet('caisse_id', $own->id)
            ->assertDontSee('id="e-caisse"', escape: false)
            ->assertDontSee(__('Balance'));
    }

    /**
     * The index route still points at the legacy controller (swapping it to
     * this component is the pending routes/backoffice.php patch), so the page
     * is exercised through the Livewire component itself.
     */
    public function test_page_renders_for_a_user_with_payments_view(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('payments.view');
        $this->actingAs($u);

        Livewire::test(EncaissementsIndex::class)
            ->assertOk()
            ->assertSee(__('Payments'));
    }

    public function test_page_is_forbidden_without_payments_view(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.encaissements.index'))->assertForbidden();
    }

    public function test_component_mount_is_forbidden_without_permission(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u);

        Livewire::test(EncaissementsIndex::class)->assertForbidden();
    }

    public function test_create_is_forbidden_without_payments_create(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('payments.view');
        $this->actingAs($u);

        Livewire::test(EncaissementsIndex::class)->call('create')->assertForbidden();
    }

    public function test_selecting_a_registration_loads_its_fees_with_due_paid_and_remaining(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();

        $component = Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id);

        // `fees` is render() view data (the cascade result), not a property.
        $fees = collect($component->viewData('fees'));
        $this->assertCount(1, $fees);
        $this->assertSame(1500.0, $fees->first()['du']);
        $this->assertSame(0.0, $fees->first()['paye']);
        $this->assertSame(1500.0, $fees->first()['reste']);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fees->first()['statut']);
    }

    public function test_selecting_a_registration_prefills_one_payment_row_per_unpaid_fee(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();

        $component = Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id);

        $lines = $component->get('paymentLines');
        $this->assertArrayHasKey($fee->id, $lines);
        // Amount is left blank — a row is a no-op unless the user fills it in.
        $this->assertSame('', $lines[$fee->id]['montant']);
        $this->assertSame(Encaissement::METHODE_ESPECES, $lines[$fee->id]['methode']);
    }

    public function test_a_payment_goes_through_the_domain_action_and_increases_the_till_balance(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set("paymentLines.{$fee->id}.montant", '500')
            ->set("paymentLines.{$fee->id}.methode", Encaissement::METHODE_ESPECES)
            ->set("paymentLines.{$fee->id}.date_paiement", '2025-09-20')
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasNoErrors();

        $encaissement = Encaissement::where('inscription_fee_id', $fee->id)->first();
        $this->assertNotNull($encaissement);
        $this->assertSame('500.00', (string) $encaissement->montant);
        // Reference is system-generated (ReferenceGenerator, ENC- prefix).
        $this->assertStringStartsWith('ENC-', $encaissement->reference);
        // The agent is the acting employee.
        $this->assertNotNull($encaissement->agent_id);
        // caisses.solde was incremented in the same transaction.
        $this->assertSame('500.00', (string) $caisse->fresh()->solde);
        // Partial payment → the fee statut flips to "Payé partiellement".
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);
    }

    public function test_fee_status_flips_to_paid_when_fully_settled(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set("paymentLines.{$fee->id}.montant", '1500')
            ->set("paymentLines.{$fee->id}.date_paiement", '2025-09-20')
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
        $this->assertSame('1500.00', (string) $caisse->fresh()->solde);
    }

    public function test_an_amount_above_the_remaining_balance_is_rejected(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set("paymentLines.{$fee->id}.montant", '1600') // more than the 1500 owed
            ->set("paymentLines.{$fee->id}.date_paiement", '2025-09-20')
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasErrors(["paymentLines.{$fee->id}.montant"]);

        $this->assertSame(0, Encaissement::count());
        $this->assertSame('0.00', (string) $caisse->fresh()->solde);
    }

    public function test_remaining_balance_shrinks_after_a_first_payment(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        // First payment of 1000 on the 1500 fee.
        Encaissement::create([
            'reference' => 'ENC-000999', 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'montant' => 1000,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-18',
            'caisse_id' => $caisse->id, 'agent_id' => auth()->user()->employee->id,
        ]);

        // 600 is now above the 500 still owed.
        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set("paymentLines.{$fee->id}.montant", '600')
            ->set("paymentLines.{$fee->id}.date_paiement", '2025-09-20')
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasErrors(["paymentLines.{$fee->id}.montant"]);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->globalUser();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', null)
            ->set('inscription_id', null)
            ->set('caisse_id', null)
            ->call('save')
            ->assertHasErrors(['student_id', 'inscription_id', 'caisse_id', 'paymentLines']);
    }

    public function test_submitting_with_every_row_empty_is_rejected(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set('caisse_id', $caisse->id)
            // Every row's montant left blank — nothing to submit.
            ->call('save')
            ->assertHasErrors(['paymentLines']);

        $this->assertSame(0, Encaissement::count());
        $this->assertSame('0.00', (string) $caisse->fresh()->solde);
    }

    public function test_a_single_submit_can_record_payments_for_several_fees_at_once(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => 3000,
        ]);
        $feeA = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais A',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $feeB = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais B',
            'montant_initial' => 2000, 'montant' => 2000,
            'date_echeance' => '2025-08-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $feeC = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais C',
            'montant_initial' => 500, 'montant' => 500,
            'date_echeance' => '2025-09-30', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set("paymentLines.{$feeA->id}.montant", '1000')
            ->set("paymentLines.{$feeA->id}.date_paiement", '2025-09-20')
            ->set("paymentLines.{$feeB->id}.montant", '500')
            ->set("paymentLines.{$feeB->id}.methode", Encaissement::METHODE_TPE)
            ->set("paymentLines.{$feeB->id}.date_paiement", '2025-09-20')
            // Fee C's row left blank — must NOT create a payment.
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Encaissement::count());
        $this->assertNotNull(Encaissement::where('inscription_fee_id', $feeA->id)->first());
        $this->assertNotNull(Encaissement::where('inscription_fee_id', $feeB->id)->first());
        $this->assertNull(Encaissement::where('inscription_fee_id', $feeC->id)->first());

        // caisses.solde incremented by the sum of both rows.
        $this->assertSame('1500.00', (string) $caisse->fresh()->solde);

        // Each fee's statut recalculated independently.
        $this->assertSame(InscriptionFee::STATUT_PAYE, $feeA->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $feeB->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeC->fresh()->statut);
    }

    public function test_an_invalid_row_rolls_back_the_whole_multi_row_submit(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => 1600,
        ]);
        $feeA = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais A',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $feeB = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais B',
            'montant_initial' => 600, 'montant' => 600,
            'date_echeance' => '2025-08-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            // Row 1 is valid on its own...
            ->set("paymentLines.{$feeA->id}.montant", '1000')
            ->set("paymentLines.{$feeA->id}.date_paiement", '2025-09-20')
            // ...row 2 exceeds what's owed on fee B.
            ->set("paymentLines.{$feeB->id}.montant", '700')
            ->set("paymentLines.{$feeB->id}.date_paiement", '2025-09-20')
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasErrors(["paymentLines.{$feeB->id}.montant"]);

        // Row 1 must NOT have been persisted — the whole submit rolls back.
        $this->assertSame(0, Encaissement::count());
        $this->assertSame('0.00', (string) $caisse->fresh()->solde);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeA->fresh()->statut);
    }

    public function test_cheque_fields_are_required_when_the_method_is_cheque(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set("paymentLines.{$fee->id}.montant", '500')
            ->set("paymentLines.{$fee->id}.date_paiement", '2025-09-20')
            ->set("paymentLines.{$fee->id}.methode", Encaissement::METHODE_CHEQUE)
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasErrors(['numero_cheque', 'banque', 'date_echeance_cheque']);
    }

    public function test_a_payment_can_be_edited_but_montant_and_caisse_never_change(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();
        $autreCaisse = $this->caisse();

        $encaissement = Encaissement::create([
            'reference' => 'ENC-000001', 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'montant' => 500,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
            'caisse_id' => $caisse->id, 'agent_id' => auth()->user()->employee->id,
        ]);

        Livewire::test(EncaissementsIndex::class)
            ->call('edit', $encaissement->id)
            ->set('methode', Encaissement::METHODE_TPE)
            ->set('note', 'Corrigé')
            // Tampered frozen fields — must be ignored by save().
            ->set('montant', '9999')
            ->set('caisse_id', $autreCaisse->id)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $encaissement->fresh();
        $this->assertSame(Encaissement::METHODE_TPE, $fresh->methode);
        $this->assertSame('Corrigé', $fresh->note);
        // Frozen after creation.
        $this->assertSame('500.00', (string) $fresh->montant);
        $this->assertSame($caisse->id, $fresh->caisse_id);
    }

    public function test_edit_is_forbidden_without_payments_update(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();
        $encaissement = Encaissement::create([
            'reference' => 'ENC-000002', 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'montant' => 500,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
            'caisse_id' => $caisse->id, 'agent_id' => auth()->user()->employee->id,
        ]);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('payments.view');
        $viewer->givePermissionTo('centers.access-all');
        $this->actingAs($viewer->fresh());

        Livewire::test(EncaissementsIndex::class)->call('edit', $encaissement->id)->assertForbidden();
    }

    public function test_the_component_exposes_no_delete_path(): void
    {
        // Payments are never deleted: neither a route nor a component method.
        $this->assertFalse(app('router')->has('backoffice.encaissements.destroy'));
        $this->assertFalse(method_exists(EncaissementsIndex::class, 'delete'));
        $this->assertFalse(method_exists(EncaissementsIndex::class, 'destroy'));
    }

    public function test_a_user_without_an_employee_record_cannot_record_a_payment(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN); // permissions yes, employee no
        $this->actingAs($user);

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            ->set("paymentLines.{$fee->id}.montant", '500')
            ->set("paymentLines.{$fee->id}.date_paiement", '2025-09-20')
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasErrors('caisse_id');

        $this->assertSame(0, Encaissement::count());
    }

    public function test_center_scoped_user_only_sees_payments_of_their_center_tills(): void
    {
        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();

        $admin = $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(3000);

        $rCaisse = $this->caisse($rabat);
        $cCaisse = $this->caisse($casa);

        Encaissement::create(['reference' => 'ENC-RABAT1', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id, 'montant' => 100, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20', 'caisse_id' => $rCaisse->id, 'agent_id' => $admin->employee->id]);
        Encaissement::create(['reference' => 'ENC-CASA01', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id, 'montant' => 100, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20', 'caisse_id' => $cCaisse->id, 'agent_id' => $admin->employee->id]);

        $user = User::factory()->create();
        $user->givePermissionTo('payments.view');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $rabat->id]);
        $this->actingAs($user->fresh());

        Livewire::test(EncaissementsIndex::class)
            ->assertSee('ENC-RABAT1')
            ->assertDontSee('ENC-CASA01');
    }

    public function test_the_list_can_be_searched_and_filtered(): void
    {
        $admin = $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(5000);
        $caisseA = $this->caisse();
        $caisseB = $this->caisse();

        Encaissement::create(['reference' => 'ENC-AAA111', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id, 'montant' => 100, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-10', 'caisse_id' => $caisseA->id, 'agent_id' => $admin->employee->id]);
        Encaissement::create(['reference' => 'ENC-BBB222', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id, 'montant' => 200, 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-10-10', 'caisse_id' => $caisseB->id, 'agent_id' => $admin->employee->id]);

        // Search by reference.
        Livewire::test(EncaissementsIndex::class)
            ->set('search', 'AAA111')
            ->assertSee('ENC-AAA111')
            ->assertDontSee('ENC-BBB222');

        // Filter by till.
        Livewire::test(EncaissementsIndex::class)
            ->set('caisseFilter', (string) $caisseB->id)
            ->assertSee('ENC-BBB222')
            ->assertDontSee('ENC-AAA111');

        // Filter by date range.
        Livewire::test(EncaissementsIndex::class)
            ->set('dateFrom', '2025-10-01')
            ->assertSee('ENC-BBB222')
            ->assertDontSee('ENC-AAA111');

        // Filter by method.
        Livewire::test(EncaissementsIndex::class)
            ->set('methodeFilter', Encaissement::METHODE_TPE)
            ->assertSee('ENC-BBB222')
            ->assertDontSee('ENC-AAA111');
    }

    public function test_receipt_page_shows_the_payment_and_the_settled_fee(): void
    {
        $admin = $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        $encaissement = Encaissement::create([
            'reference' => 'ENC-SHOW01', 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'montant' => 500,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
            'caisse_id' => $caisse->id, 'agent_id' => $admin->employee->id,
            'note' => 'Premier versement',
        ]);

        $this->get(route('backoffice.encaissements.show', $encaissement))
            ->assertOk()
            ->assertSee('ENC-SHOW01')
            ->assertSee('Frais de Juillet')
            ->assertSee('Premier versement');
    }

    public function test_a_fee_not_belonging_to_the_selected_registration_is_refused(): void
    {
        $this->globalUser();
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        [$other, $otherInscription, $otherFee] = $this->enrolledStudentWithFee();
        $caisse = $this->caisse();

        Livewire::test(EncaissementsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('inscription_id', $inscription->id)
            // Tampered: inject a row for a fee of ANOTHER registration
            // (loadPaymentLines() would never produce this on its own).
            ->set("paymentLines.{$otherFee->id}", [
                'fee_id' => $otherFee->id,
                'montant' => '100',
                'methode' => Encaissement::METHODE_ESPECES,
                'date_paiement' => '2025-09-20',
            ])
            ->set('caisse_id', $caisse->id)
            ->call('save')
            ->assertHasErrors('paymentLines');

        $this->assertSame(0, Encaissement::count());
    }
}
