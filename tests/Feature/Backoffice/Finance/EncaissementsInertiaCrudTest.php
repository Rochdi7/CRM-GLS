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
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Encaissements endpoints (EncaissementController)
 * built alongside the unchanged Livewire EncaissementsIndex fallback — see
 * EncaissementsCrudTest for the Livewire-side coverage of the same business
 * rules (docs/phase-10-finance-audit.md §2.4).
 */
final class EncaissementsInertiaCrudTest extends TestCase
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

    /**
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

    // --- Destroy (payments.delete) -------------------------------------
    // The single exception to the append-only money rule (CLAUDE.md §11):
    // reachable only with payments.delete, which no role preset carries.

    public function test_destroy_is_forbidden_without_payments_delete(): void
    {
        $user = $this->userWith('payments.view', 'payments.update');
        $agent = $user->employee;
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 500]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-KEEP', 'agent_id' => $agent->id, 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'montant' => 200, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-09-20',
        ]);

        $this->actingAs($user)
            ->delete(route('backoffice.encaissements.destroy', $encaissement))
            ->assertForbidden();

        $this->assertDatabaseHas('encaissements', ['id' => $encaissement->id]);
        $this->assertSame(500.0, (float) $caisse->fresh()->solde);
    }

    public function test_destroy_removes_the_row_and_reverses_the_till_balance(): void
    {
        $user = $this->userWith('payments.view', 'payments.delete');
        $agent = $user->employee;
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 500]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-DEL', 'agent_id' => $agent->id, 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'montant' => 200, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-09-20',
        ]);
        $fee->update(['statut' => InscriptionFee::STATUT_PAYE_PARTIELLEMENT]);

        $this->actingAs($user)
            ->delete(route('backoffice.encaissements.destroy', $encaissement))
            ->assertRedirect(route('backoffice.encaissements.index'));

        $this->assertDatabaseMissing('encaissements', ['id' => $encaissement->id]);
        // solde was incremented by 200 when recorded, so it must drop back.
        $this->assertSame(300.0, (float) $caisse->fresh()->solde);
        // and the fee falls back to unpaid now that nothing is paid against it.
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    public function test_destroy_refuses_an_advance_that_was_already_applied(): void
    {
        $user = $this->userWith('payments.view', 'payments.delete');
        $agent = $user->employee;
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 500]);
        $avance = Encaissement::create([
            'reference' => 'ENC-AV', 'agent_id' => $agent->id, 'student_id' => $student->id, 'inscription_fee_id' => null,
            'caisse_id' => $caisse->id, 'montant' => 300, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-09-20',
        ]);
        Encaissement::create([
            'reference' => 'ENC-AV-APP', 'agent_id' => $agent->id, 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'montant' => 100, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-09-21', 'applied_from_encaissement_id' => $avance->id,
        ]);

        $this->actingAs($user)
            ->delete(route('backoffice.encaissements.destroy', $avance))
            ->assertSessionHasErrors('encaissement');

        $this->assertDatabaseHas('encaissements', ['id' => $avance->id]);
        $this->assertSame(500.0, (float) $caisse->fresh()->solde);
    }

    public function test_destroy_of_an_apply_row_leaves_the_till_balance_untouched(): void
    {
        $user = $this->userWith('payments.view', 'payments.delete');
        $agent = $user->employee;
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 500]);
        $avance = Encaissement::create([
            'reference' => 'ENC-AV2', 'agent_id' => $agent->id, 'student_id' => $student->id, 'inscription_fee_id' => null,
            'caisse_id' => $caisse->id, 'montant' => 300, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-09-20',
        ]);
        $apply = Encaissement::create([
            'reference' => 'ENC-AV2-APP', 'agent_id' => $agent->id, 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'montant' => 100, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-09-21', 'applied_from_encaissement_id' => $avance->id,
        ]);

        $this->actingAs($user)
            ->delete(route('backoffice.encaissements.destroy', $apply))
            ->assertRedirect(route('backoffice.encaissements.index'));

        $this->assertDatabaseMissing('encaissements', ['id' => $apply->id]);
        // An apply row never incremented solde, so deleting it must not decrement it.
        $this->assertSame(500.0, (float) $caisse->fresh()->solde);
    }

    public function test_index_requires_payments_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertForbidden();

        $this->actingAs($this->userWith('payments.view'))
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Encaissements/Index', false)
                ->has('encaissements')
                ->has('caisses')
                ->has('students')
            );
    }

    /**
     * Legacy-import scenario: the money was booked into the till of an
     * operator whose PRIMARY centre is another branch (CaisseProvisioner puts
     * a till in the employee's primary centre). The payment's centre is its
     * STUDENT's centre, so with the context switcher on the student's centre
     * the row must appear — scoping the list by the till's centre is exactly
     * the bug that emptied the Agadir Encaissements page.
     */
    public function test_index_lists_payment_held_in_another_centres_till_when_context_is_on_the_students_centre(): void
    {
        $user = $this->userWith('payments.view');
        $agent = $user->employee;
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);

        $otherCentre = Etablissement::factory()->create();
        $foreignCaisse = Caisse::factory()->create(['etablissement_id' => $otherCentre->id]);

        Encaissement::create([
            'reference' => 'ENC-XTILL', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $foreignCaisse->id,
            'montant' => 300, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ]);

        // Context on the STUDENT's centre — the row must be there.
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('encaissements.data.0.reference', 'ENC-XTILL')
            );

        // Context on the TILL's centre — the payment belongs to the other
        // centre's student, so it must NOT leak here.
        app(CurrentContext::class)->setEtablissement($otherCentre->id);
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('encaissements.data', [])
            );
    }

    /**
     * A centre-less (global) student is visible in every centre, so the
     * payments just collected for them must be listed under the active
     * centre too — the page was empty right after recording them
     * (24/08/2026). The inscription's centre is a second way in.
     */
    public function test_index_lists_payments_of_a_centre_less_student_under_the_active_centre(): void
    {
        $user = $this->userWith('payments.view');
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        $student->update(['etablissement_id' => null]);

        Encaissement::create([
            'reference' => 'ENC-GLOBAL', 'agent_id' => $user->employee->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $user->employee->caisses()->first()->id,
            'montant' => 300, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ]);

        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('encaissements.data.0.reference', 'ENC-GLOBAL'));
    }

    /**
     * A payment that has been FULLY refunded is money that is no longer
     * there: it leaves the Paiements tab (24/08/2026). A partial refund keeps
     * the row and shows the refunded part. Nothing is deleted — the row stays
     * on the student page, the caisse journal and the audit trail.
     */
    public function test_index_hides_fully_refunded_payments_and_flags_partial_ones(): void
    {
        $user = $this->userWith('payments.view');
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        $till = $user->employee->caisses()->first();

        $full = Encaissement::create([
            'reference' => 'ENC-FULL', 'agent_id' => $user->employee->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $till->id,
            'montant' => 600, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ]);
        $partial = Encaissement::create([
            'reference' => 'ENC-PART', 'agent_id' => $user->employee->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $till->id,
            'montant' => 400, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-21',
        ]);
        foreach ([[$full, 600], [$partial, 150]] as [$enc, $montant]) {
            \App\Models\Remboursement::create([
                'reference' => 'RMB-'.$enc->reference, 'beneficiaire_id' => $student->id, 'encaissement_id' => $enc->id,
                'caisse_id' => $till->id, 'montant' => $montant, 'date_remboursement' => '2025-10-01', 'agent_id' => $user->employee->id,
            ]);
        }

        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('encaissements.data', 1)
                ->where('encaissements.data.0.reference', 'ENC-PART')
                ->where('encaissements.data.0.montantRembourse', '150.00')
            );
    }

    /**
     * The top-bar year switcher must scope the list: a payment belongs to
     * the academic year of its fee's inscription, and an avance (no fee) to
     * the year its payment date falls in.
     */
    public function test_index_only_lists_payments_of_the_active_academic_year(): void
    {
        $user = $this->userWith('payments.view');
        $agent = $user->employee;
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        // A payment on an inscription of the DEFAULT year (2025/2026).
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        Encaissement::create([
            'reference' => 'ENC-Y1', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id,
            'montant' => 300, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ]);

        // A payment on an inscription of the NEXT year (2026/2027).
        $nextYear = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $group2 = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $nextYear->id]);
        $inscription2 = Inscription::create([
            'reference' => 'INS-Y2', 'student_id' => $student->id, 'group_id' => $group2->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $nextYear->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-09-15',
            'montant_total' => 1000,
        ]);
        $fee2 = InscriptionFee::create([
            'inscription_id' => $inscription2->id, 'nom' => 'Frais',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-09-30', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        Encaissement::create([
            'reference' => 'ENC-Y2', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee2->id, 'caisse_id' => $caisse->id,
            'montant' => 400, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2026-09-20',
        ]);

        // Default year active: only the 2025/2026 payment is listed.
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('encaissements.data', 1)
                ->where('encaissements.data.0.reference', 'ENC-Y1')
            );

        // Switch to 2026/2027: only that year's payment is listed.
        app(CurrentContext::class)->setAnneeScolaire($nextYear->id);
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('encaissements.data', 1)
                ->where('encaissements.data.0.reference', 'ENC-Y2')
            );
    }

    /**
     * An avance is money received and NOT yet allocated, so it stays
     * outstanding until someone applies it. Scoping the tab to the active
     * year hid real, unspent money with no way to ask for it — clearing both
     * date fields did not bring it back (two RAZANE ZOUINE avances dated
     * 11/07/2025, invisible under 2025/2026 which opens on 01/09, 26/08/2026).
     * The tab now lists the full history; the Paiements tab keeps its year
     * scoping (see test_encaissements_are_scoped_to_the_active_year).
     */
    public function test_avances_tab_lists_every_year(): void
    {
        $user = $this->userWith('payments.view');
        $agent = $user->employee;
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        Encaissement::create([
            'reference' => 'ENC-AV1', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => null, 'caisse_id' => $caisse->id,
            'montant' => 500, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-10-01',
        ]);
        Encaissement::create([
            'reference' => 'ENC-AV2', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => null, 'caisse_id' => $caisse->id,
            'montant' => 500, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2026-10-01',
        ]);

        // Both are listed even though ENC-AV2 falls in the NEXT year.
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', ['view' => 'avance', 'dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('encaissements.data', 2));

        // An explicit date filter still narrows the tab.
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', [
                'view' => 'avance', 'dateFrom' => '2025-09-01', 'dateTo' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('encaissements.data', 1)
                ->where('encaissements.data.0.reference', 'ENC-AV1')
            );
    }

    public function test_student_inscriptions_lookup_returns_current_year_registrations(): void
    {
        $this->actingAs($this->userWith('payments.view', 'payments.create'));
        [$student, $inscription] = $this->enrolledStudentWithFee();

        $response = $this->get(route('backoffice.students.inscriptions-for-payment', $student))->json();

        $this->assertCount(1, $response['inscriptions']);
        $this->assertSame($inscription->id, $response['inscriptions'][0]['id']);
    }

    public function test_inscription_fees_lookup_returns_only_unpaid_fees(): void
    {
        $this->actingAs($this->userWith('payments.view', 'payments.create'));
        [, $inscription, $fee] = $this->enrolledStudentWithFee(1500);

        $paidFee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais déjà payé',
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2025-09-01', 'statut' => InscriptionFee::STATUT_PAYE,
        ]);
        Encaissement::create([
            'reference' => 'ENC-PAID', 'student_id' => $inscription->student_id, 'inscription_fee_id' => $paidFee->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => Employee::first()->id, 'montant' => 300, 'methode' => 'Espèces', 'date_paiement' => '2025-09-01',
        ]);

        $response = $this->get(route('backoffice.inscriptions.unpaid-fees', $inscription))->json();

        $names = collect($response['fees'])->pluck('nom');
        $this->assertTrue($names->contains($fee->nom));
        $this->assertFalse($names->contains('Frais déjà payé'));
    }

    public function test_a_single_fee_payment_increments_balance_and_flips_fee_statut(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'caisse_id' => $caisse->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1500', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertRedirect(route('backoffice.encaissements.index'));

        $this->assertSame(1, Encaissement::count());
        $this->assertSame('1500.00', (string) $caisse->fresh()->solde);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
    }



    /**
     * The Encaissements page's view tabs (wimschool-style): "cheque" lists
     * only cheque payments; "avance" lists only unallocated advances
     * (inscription_fee_id IS NULL, not yet applied to any fee) — both
     * read-only filters over the same list. A partially-paid fee via cash is
     * NOT an avance under the current definition — it's still a normal,
     * fee-targeted payment.
     */
    public function test_view_tabs_filter_cheques_and_avances(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);

        // Fully-paid fee via cheque + a genuine unallocated avance (no fee).
        [$student1, $inscription1, $feeFull] = $this->enrolledStudentWithFee(1000);
        $cheque = \App\Models\Cheque::create([
            'reference' => 'CHQ-TAB-1', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $student1->id,
            'numero_cheque' => 'CHQ-1', 'banque' => 'BMCE', 'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_EN_POSSESSION,
            'montant' => 1000, 'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
        ]);
        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student1->id, 'inscription_id' => $inscription1->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $feeFull->id, 'montant' => '1000', 'methode' => 'Chèque', 'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id],
            ],
        ])->assertRedirect();

        [$student2] = $this->enrolledStudentWithFee(1000);
        $this->post(route('backoffice.avances.store'), [
            'student_id' => $student2->id,
            'montant' => '400',
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-21',
        ])->assertRedirect();

        $this->assertSame(InscriptionFee::STATUT_PAYE, $feeFull->fresh()->statut);

        $chequeRows = $this->get(route('backoffice.encaissements.index', ['view' => 'cheque', 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $chequeRows);
        $this->assertSame('Chèque', $chequeRows[0]['methode']);

        $avanceRows = $this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $avanceRows);
        $this->assertSame('400.00', (string) $avanceRows[0]['montant']);
        $this->assertSame('0.00', (string) $avanceRows[0]['montantUtilise']);
        $this->assertSame('400.00', (string) $avanceRows[0]['montantRestant']);

        // Default "Encaissements" tab = money received: the fee payment AND
        // the avance, the latter flagged so the Frais column reads « Avance ».
        $allRows = collect($this->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data']);
        $this->assertCount(2, $allRows);
        $this->assertSame([false, true], $allRows->sortBy('id')->pluck('isAvance')->values()->all());
        $this->assertNull($allRows->firstWhere('isAvance', true)['feeNom']);
    }

    public function test_an_avance_can_be_applied_to_a_fee_without_touching_caisse_solde_again(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        $this->post(route('backoffice.avances.store'), [
            'student_id' => $student->id,
            'montant' => '600',
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-21',
        ])->assertRedirect();

        $this->assertSame('600.00', (string) $caisse->fresh()->solde);
        $avance = Encaissement::whereNull('inscription_fee_id')->firstOrFail();

        $this->post(route('backoffice.avances.apply', $avance), [
            'fee_id' => $fee->id,
            'montant' => '600',
        ])->assertRedirect();

        // Applying does NOT re-increment the till — the money already
        // arrived when the avance itself was recorded.
        $this->assertSame('600.00', (string) $caisse->fresh()->solde);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);
        $this->assertSame(2, Encaissement::count());

        // A fully used avance leaves the default (« reste ») listing — ask for all of them.
        $avanceRows = $this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'soldeFilter' => 'tous', 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $avanceRows);
        $this->assertSame('600.00', (string) $avanceRows[0]['montantUtilise']);
        $this->assertSame('0.00', (string) $avanceRows[0]['montantRestant']);
    }

    /**
     * The Avances tab defaults to avances that still have money to allocate;
     * « Épuisées » lists the fully used ones, « Toutes » both.
     */
    public function test_avances_tab_defaults_to_avances_with_a_remaining_balance(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        foreach (['600', '400'] as $montant) {
            $this->post(route('backoffice.avances.store'), [
                'student_id' => $student->id,
                'caisse_id' => $caisse->id,
                'montant' => $montant,
                'methode' => 'Espèces',
                'date_paiement' => now()->toDateString(),
            ])->assertRedirect();
        }

        $used = Encaissement::query()->where('montant', 600)->firstOrFail();
        $this->post(route('backoffice.avances.apply', $used), [
            'inscription_id' => $inscription->id,
            'fee_id' => $fee->id,
            'montant' => '600',
        ])->assertRedirect();

        $page = fn (array $extra = []) => $this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'dateFrom' => '', 'dateTo' => ''] + $extra))
            ->viewData('page')['props'];

        $default = $page();
        $this->assertSame('restant', $default['filters']['soldeFilter']);
        $this->assertCount(1, $default['encaissements']['data']);
        $this->assertSame('400.00', (string) $default['encaissements']['data'][0]['montantRestant']);
        $this->assertSame('400.00', (string) $default['montantTotal']);

        $epuise = $page(['soldeFilter' => 'epuise'])['encaissements']['data'];
        $this->assertCount(1, $epuise);
        $this->assertSame($used->id, $epuise[0]['id']);
        $this->assertSame('0.00', (string) $epuise[0]['montantRestant']);

        $this->assertCount(2, $page(['soldeFilter' => 'tous'])['encaissements']['data']);
    }

    /**
     * « Groupe » filter: a fee payment follows its inscription's group, an
     * avance (no fee) follows the groups its student is enrolled in.
     */
    public function test_group_filter_applies_to_payments_and_avances(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        [$studentA, $inscriptionA, $feeA] = $this->enrolledStudentWithFee(1000);
        [$studentB, $inscriptionB, $feeB] = $this->enrolledStudentWithFee(1000);

        foreach ([[$studentA, $inscriptionA, $feeA], [$studentB, $inscriptionB, $feeB]] as [$student, $inscription, $fee]) {
            $this->post(route('backoffice.encaissements.store'), [
                'student_id' => $student->id,
                'inscription_id' => $inscription->id,
                'caisse_id' => $caisse->id,
                'date_paiement' => '2025-09-20',
                'payment_lines' => [
                    ['fee_id' => $fee->id, 'montant' => '300', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                ],
            ])->assertRedirect();
            $this->post(route('backoffice.avances.store'), [
                'student_id' => $student->id,
                'caisse_id' => $caisse->id,
                'montant' => '200',
                'methode' => 'Espèces',
                'date_paiement' => now()->toDateString(),
            ])->assertRedirect();
        }

        $groupA = $inscriptionA->group_id;
        $rows = fn (array $extra) => collect($this->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => ''] + $extra))
            ->viewData('page')['props']['encaissements']['data']);

        // Encaissements tab: 2 fee payments + 2 avances (money received).
        $this->assertCount(4, $rows([]));
        $payments = $rows(['groupFilter' => $groupA]);
        $this->assertCount(2, $payments);
        $this->assertSame([$studentA->id], $payments->pluck('studentId')->unique()->values()->all());

        $this->assertCount(2, $rows(['view' => 'avance']));
        $avances = $rows(['view' => 'avance', 'groupFilter' => $groupA]);
        $this->assertCount(1, $avances);
        $this->assertSame($studentA->id, $avances[0]['studentId']);

        $groups = $this->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['groups'];
        $this->assertContains($groupA, array_column($groups, 'id'));
    }

    public function test_montant_total_is_resent_on_a_partial_reload(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(5000);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1200', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertRedirect();

        // Exactly what the page's reload() sends: an Inertia partial visit
        // asking only for the rows, the total and the echoed filters. A prop
        // missing from that list is not re-sent and blanks out on screen.
        // '-' is what the page sends for a cleared date (route() drops empty
        // strings, so the key would vanish entirely otherwise).
        $response = $this->get(
            route('backoffice.encaissements.index', ['dateFrom' => '-', 'dateTo' => '-']),
            [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Inertia::getVersion(),
                'X-Inertia-Partial-Component' => 'Backoffice/Encaissements/Index',
                'X-Inertia-Partial-Data' => 'encaissements,montantTotal,filters',
            ],
        );

        // A partial reload answers with JSON (not the HTML shell), so the
        // props are read straight off the body rather than via viewData().
        $response->assertOk();
        $props = $response->json('props');

        $this->assertArrayHasKey('montantTotal', $props, 'montantTotal must be re-sent by the partial reload.');
        $this->assertSame('1200.00', (string) $props['montantTotal']);
        $this->assertCount(1, $props['encaissements']['data']);
    }

    public function test_montant_total_follows_the_student_filter(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);

        [$studentA, $inscriptionA, $feeA] = $this->enrolledStudentWithFee(5000);
        [$studentB, $inscriptionB, $feeB] = $this->enrolledStudentWithFee(5000);

        foreach ([[$inscriptionA, $feeA, '900'], [$inscriptionB, $feeB, '2500']] as [$inscription, $fee, $montant]) {
            $this->post(route('backoffice.encaissements.store'), [
                'student_id' => $inscription->student_id, 'inscription_id' => $inscription->id,
                'date_paiement' => '2025-09-20',
                'payment_lines' => [
                    ['fee_id' => $fee->id, 'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                ],
            ])->assertRedirect();
        }

        // Unfiltered: both students' money.
        $this->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('montantTotal', '3400.00'));

        // Narrowing to one student must move the total with the rows — it must
        // never keep a figure from the previous filter state (26/08/2026: the
        // total was left out of the partial-reload `only:` list, so it kept a
        // stale value while the rows below it were correct).
        $this->get(route('backoffice.encaissements.index', [
            'studentFilter' => $studentA->id, 'dateFrom' => '', 'dateTo' => '',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('encaissements.data', 1)
                ->where('montantTotal', '900.00')
            );
    }

    public function test_montant_total_sums_the_whole_filtered_set_not_just_the_page(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(5000);

        foreach (['300', '700'] as $montant) {
            $this->post(route('backoffice.encaissements.store'), [
                'student_id' => $student->id, 'inscription_id' => $inscription->id,
                'date_paiement' => '2025-09-20',
                'payment_lines' => [
                    ['fee_id' => $fee->id, 'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                ],
            ])->assertRedirect();
        }

        $this->get(route('backoffice.encaissements.index', [
            'dateFrom' => '2025-09-01', 'dateTo' => '2025-09-30',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('montantTotal', '1000.00'));

        // Outside the window the total is 0, not the unfiltered sum.
        $this->get(route('backoffice.encaissements.index', [
            'dateFrom' => '2025-10-01', 'dateTo' => '2025-10-31',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('montantTotal', '0.00'));
    }

    public function test_avances_montant_total_reports_what_is_still_available(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);

        $this->post(route('backoffice.avances.store'), [
            'student_id' => $student->id,
            'montant' => '600',
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-21',
        ])->assertRedirect();

        $avanceUrl = route('backoffice.encaissements.index', ['view' => 'avance', 'dateFrom' => '', 'dateTo' => '']);

        // Nothing applied yet: the whole avance is still available.
        $this->get($avanceUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('montantTotal', '600.00'));

        $avance = Encaissement::whereNull('inscription_fee_id')->firstOrFail();
        $this->post(route('backoffice.avances.apply', $avance), [
            'fee_id' => $fee->id,
            'montant' => '250',
        ])->assertSessionHasNoErrors()->assertRedirect();

        // 600 received − 250 applied to a fee = 350 still unallocated. The
        // gross 600 would announce money that is already spent.
        $this->get($avanceUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('montantTotal', '350.00'));
    }

    public function test_applying_more_than_the_avances_remaining_balance_is_rejected(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);

        $this->post(route('backoffice.avances.store'), [
            'student_id' => $student->id,
            'montant' => '100',
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-21',
        ])->assertRedirect();
        $avance = Encaissement::whereNull('inscription_fee_id')->firstOrFail();

        $this->post(route('backoffice.avances.apply', $avance), [
            'fee_id' => $fee->id,
            'montant' => '500',
        ])->assertSessionHasErrors('montant');

        $this->assertSame(1, Encaissement::count());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    public function test_inscription_payments_lookup_lists_fee_attached_payments(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertRedirect();

        $response = $this->get(route('backoffice.inscriptions.payments', $inscription))->json();

        $this->assertCount(1, $response['payments']);
        $this->assertSame('1000.00', $response['payments'][0]['montant']);
        $this->assertSame($fee->nom, $response['payments'][0]['feeNom']);
        $this->assertFalse($response['payments'][0]['rembourse']);
    }

    /**
     * The "changement de groupe" money-move flow: converting detaches the
     * payment from its fee (which drops back to Non payé) WITHOUT touching
     * the till, the freed amount shows on the Avances tab with its full
     * reste, and can then be applied to another inscription's fee.
     */
    public function test_payments_can_be_converted_into_avances_and_reapplied_to_another_inscription(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertRedirect();

        $encaissement = Encaissement::firstOrFail();
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
        $this->assertSame('1000.00', (string) $caisse->fresh()->solde);

        $this->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$encaissement->id],
        ])->assertRedirect(route('backoffice.encaissements.index', ['view' => 'avance']));

        // Detached, fee owed again, money record intact, till untouched.
        $this->assertNull($encaissement->fresh()->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
        $this->assertSame(1, Encaissement::count());
        $this->assertSame('1000.00', (string) $caisse->fresh()->solde);

        $avanceRows = $this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $avanceRows);
        $this->assertSame('0.00', (string) $avanceRows[0]['montantUtilise']);
        $this->assertSame('1000.00', (string) $avanceRows[0]['montantRestant']);
        // « Ancien frais »: the fee it was detached from, read back from the
        // audit journal (the row itself no longer carries it).
        $this->assertSame('Frais de Juillet', $avanceRows[0]['ancienFrais']);
        $this->assertSame($inscription->group->nom, $avanceRows[0]['ancienFraisGroupe']);

        // Re-apply the freed amount onto a second inscription's fee.
        $group2 = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription2 = Inscription::create([
            'reference' => 'INS-NEW', 'student_id' => $student->id, 'group_id' => $group2->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-10-01',
            'montant_total' => 1000,
        ]);
        $fee2 = InscriptionFee::create([
            'inscription_id' => $inscription2->id, 'nom' => 'Frais nouveau groupe',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->post(route('backoffice.avances.apply', $encaissement), [
            'fee_id' => $fee2->id,
            'montant' => '1000',
        ])->assertRedirect();

        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee2->fresh()->statut);
        $this->assertSame('1000.00', (string) $caisse->fresh()->solde);

        // A fully used avance leaves the default (« reste ») listing — ask for all of them.
        $avanceRows = $this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'soldeFilter' => 'tous', 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $avanceRows);
        $this->assertSame('1000.00', (string) $avanceRows[0]['montantUtilise']);
        $this->assertSame('0.00', (string) $avanceRows[0]['montantRestant']);
    }

    public function test_converting_a_payment_of_another_inscription_is_rejected_with_no_side_effects(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        [, $otherInscription] = $this->enrolledStudentWithFee(500);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertRedirect();
        $encaissement = Encaissement::firstOrFail();

        // Tampered payload: the payment belongs to $inscription, not $otherInscription.
        $this->post(route('backoffice.avances.convert'), [
            'inscription_id' => $otherInscription->id,
            'encaissement_ids' => [$encaissement->id],
        ])->assertSessionHasErrors('encaissement_ids');

        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
    }

    /**
     * A payment that was itself paid FROM an avance (an "apply" row) can be
     * converted too: its fee is detached but its applied_from link is kept,
     * so the parent avance's used amount stays correct while the detached
     * row's own montant becomes re-allocatable (Encaissement::isAvance()).
     */
    public function test_converting_an_apply_row_reappears_as_its_own_avance(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        $this->post(route('backoffice.avances.store'), [
            'student_id' => $student->id, 'montant' => '600',
            'methode' => 'Espèces', 'date_paiement' => '2025-09-21',
        ])->assertRedirect();
        $parentAvance = Encaissement::whereNull('inscription_fee_id')->firstOrFail();

        $this->post(route('backoffice.avances.apply', $parentAvance), [
            'fee_id' => $fee->id, 'montant' => '600',
        ])->assertRedirect();
        $applyRow = Encaissement::whereNotNull('applied_from_encaissement_id')->firstOrFail();

        $this->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$applyRow->id],
        ])->assertRedirect();

        $fresh = $applyRow->fresh();
        $this->assertNull($fresh->inscription_fee_id);
        $this->assertSame($parentAvance->id, $fresh->applied_from_encaissement_id);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);

        // A fully used avance leaves the default (« reste ») listing — ask for all of them.
        $avanceRows = collect($this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'soldeFilter' => 'tous', 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data']);
        $this->assertCount(2, $avanceRows);
        // Parent stays fully used (its 600 went to the apply row)…
        $parentRow = $avanceRows->firstWhere('id', $parentAvance->id);
        $this->assertSame('0.00', (string) $parentRow['montantRestant']);
        // …while the detached row carries the re-allocatable 600.
        $detachedRow = $avanceRows->firstWhere('id', $applyRow->id);
        $this->assertSame('600.00', (string) $detachedRow['montantRestant']);
    }

    public function test_amount_above_remaining_balance_is_rejected_with_zero_side_effects(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'caisse_id' => $caisse->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '2000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasErrors('payment_lines.0.montant');

        $this->assertSame(0, Encaissement::count());
        $this->assertSame('0.00', (string) $caisse->fresh()->solde);
    }

    public function test_multi_row_submit_creates_one_encaissement_per_touched_row(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee1] = $this->enrolledStudentWithFee(1000);
        $fee2 = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais 2',
            'montant_initial' => 500, 'montant' => 500,
            'date_echeance' => '2025-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'caisse_id' => $caisse->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee1->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                ['fee_id' => $fee2->id, 'montant' => '500', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(2, Encaissement::count());
        $this->assertSame('1500.00', (string) $caisse->fresh()->solde);
    }

    public function test_an_invalid_row_rolls_back_the_whole_multi_row_submit(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee1] = $this->enrolledStudentWithFee(1000);
        $caisse = $user->employee->caisses()->first();

        // A fee id belonging to a DIFFERENT registration, tampered directly
        // into the payload — simulates a malicious client bypassing the
        // real cascade lookup.
        [, $otherInscription, $foreignFee] = $this->enrolledStudentWithFee(500);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'caisse_id' => $caisse->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee1->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                ['fee_id' => $foreignFee->id, 'montant' => '500', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasErrors('payment_lines');

        $this->assertSame(0, Encaissement::count());
        $this->assertSame('0.00', (string) $caisse->fresh()->solde);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee1->fresh()->statut);
    }

    public function test_no_employee_record_is_refused_with_no_side_effects(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('payments.view', 'payments.create', 'centers.access-all');
        $this->actingAs($user->fresh());

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'caisse_id' => $caisse->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1500', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasErrors('payment_lines');

        $this->assertSame(0, Encaissement::count());
    }

    /**
     * Since the payment-method accounts refactor (24/08/2026) `methode` is
     * frozen too: it decided which account was credited. The tampered value
     * is refused outright (see ComptesMethodeTest for the refusal itself);
     * here the stored method is echoed back, as the edit modal does.
     */
    public function test_edit_never_touches_montant_or_caisse_even_when_tampered(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $caisse = $user->employee->caisses()->first();
        $otherCaisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $encaissement = Encaissement::create([
            'reference' => 'ENC-EDIT', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'agent_id' => $user->employee->id,
            'montant' => 1500, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        $this->put(route('backoffice.encaissements.update', $encaissement), [
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-21',
            // Tampered — must have zero effect.
            'montant' => '9999',
            'caisse_id' => $otherCaisse->id,
        ])->assertSessionDoesntHaveErrors();

        $fresh = $encaissement->fresh();
        $this->assertSame('Espèces', $fresh->methode);
        // The date is frozen too for this user: re-dating a payment needs
        // `payments.update-date` (super-admin only, 30/08/2026) — see
        // test_the_payment_date_is_only_editable_by_a_super_admin below.
        $this->assertSame('2025-09-20', $fresh->date_paiement->toDateString());
        $this->assertSame('1500.00', (string) $fresh->montant);
        $this->assertSame($caisse->id, $fresh->caisse_id);
    }

    /**
     * 30/08/2026 — `date_paiement` is SUPER-ADMIN ONLY.
     *
     * Moving the date relocates the row in the caisse journal and in the
     * annual summary, possibly into a month already reconciled. A holder of
     * `payments.update` may still correct the note and the chèque identity;
     * a posted date is dropped silently (the modal disables the field, so a
     * value arriving here is a stale form or a crafted request).
     */
    public function test_the_payment_date_is_only_editable_by_a_super_admin(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $caisse = $user->employee->caisses()->first();

        $encaissement = Encaissement::create([
            'reference' => 'ENC-DATE', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'agent_id' => $user->employee->id,
            'montant' => 1500, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        $this->put(route('backoffice.encaissements.update', $encaissement), [
            'methode' => 'Espèces',
            'date_paiement' => '2025-10-05',
            'note' => 'Corrigé',
        ])->assertSessionDoesntHaveErrors();

        $fresh = $encaissement->fresh();
        // Date untouched…
        $this->assertSame('2025-09-20', $fresh->date_paiement->toDateString());
        // …while the fields this role MAY correct went through.
        $this->assertSame('Corrigé', $fresh->note);

        // A super-admin re-dates it, via Gate::before.
        $admin = User::factory()->create();
        $admin->assignRole(\App\Models\Role::SUPER_ADMIN);
        $this->actingAs($admin);

        $this->put(route('backoffice.encaissements.update', $encaissement), [
            'methode' => 'Espèces',
            'date_paiement' => '2025-10-05',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('2025-10-05', $encaissement->fresh()->date_paiement->toDateString());
    }

    /**
     * Encaissements are the ONE money record with a destroy route (the others
     * — depenses/remboursements/transferts — stay append-only). It must stay
     * behind permission:payments.delete, a permission carried by no role
     * preset, so only a super-admin has it until they grant it by hand.
     */
    public function test_delete_route_is_gated_by_the_payments_delete_permission(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('backoffice.encaissements.destroy'));

        $middleware = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('backoffice.encaissements.destroy')
            ->gatherMiddleware();

        $this->assertContains('permission:payments.delete', $middleware);
    }

    public function test_no_delete_route_exists_for_the_other_money_records(): void
    {
        foreach (['depenses', 'remboursements', 'caisse-transfers'] as $module) {
            $this->assertFalse(
                \Illuminate\Support\Facades\Route::has("backoffice.{$module}.destroy"),
                "backoffice.{$module}.destroy must not exist — money records are append-only.",
            );
        }
    }

    public function test_payments_delete_is_granted_to_no_role_preset(): void
    {
        foreach (\App\Support\Authorization\PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains(
                'payments.delete',
                $permissions,
                "Role {$role} must not carry payments.delete — a super-admin grants it by hand.",
            );
        }
    }

    // --- paying with a tracked chèque (Chèques module) --------------------

    public function test_paying_with_a_tracked_cheque_links_it_and_fills_its_details(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'cheques.view', 'cheques.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(500);

        $cheque = \App\Models\Cheque::create([
            'reference' => 'CHQ-PAY', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => 'CHQ-PAY-1', 'montant' => 500, 'banque' => 'BMCE',
            'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
        ]);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => 'Chèque', 'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id],
            ],
        ])->assertSessionDoesntHaveErrors();

        $encaissement = Encaissement::firstOrFail();
        $this->assertSame($cheque->id, $encaissement->cheque_id);
        $this->assertSame('CHQ-PAY-1', $encaissement->numero_cheque);
        $this->assertSame('BMCE', $encaissement->banque);
        $this->assertSame('2025-10-01', $encaissement->date_echeance_cheque->toDateString());
        $this->assertSame(0.0, $cheque->fresh()->montantRestant());
    }

    public function test_paying_with_a_tracked_cheque_cannot_exceed_its_remaining_balance(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'cheques.view', 'cheques.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        $cheque = \App\Models\Cheque::create([
            'reference' => 'CHQ-SMALL', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => 'CHQ-SMALL-1', 'montant' => 300, 'date_reception' => '2025-09-01',
            'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
        ]);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Chèque', 'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id],
            ],
        ])->assertSessionHasErrors('payment_lines');

        $this->assertSame(0, Encaissement::count());
    }

    public function test_a_cheque_belonging_to_another_student_cannot_be_used(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'cheques.view', 'cheques.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(500);
        $otherStudent = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $cheque = \App\Models\Cheque::create([
            'reference' => 'CHQ-OTHER', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $otherStudent->id,
            'numero_cheque' => 'CHQ-OTHER-1', 'montant' => 500, 'date_reception' => '2025-09-01',
            'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
        ]);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => 'Chèque', 'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id],
            ],
        ])->assertSessionHasErrors('payment_lines');

        $this->assertSame(0, Encaissement::count());
    }

    // --- Reçu groupé (plusieurs paiements, une seule inscription) --------

    /** Crée un paiement rattaché au frais donné. */
    private function paiementSur(InscriptionFee $fee, Student $student, User $user, float $montant, string $reference): Encaissement
    {
        return Encaissement::create([
            'reference' => $reference, 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => $user->employee->id, 'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ]);
    }

    public function test_recu_groupe_renders_every_selected_payment_of_the_same_inscription(): void
    {
        $user = $this->userWith('payments.view');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $autreFrais = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription 1",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2025-09-30', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $a = $this->paiementSur($fee, $student, $user, 200, 'ENC-G1');
        $b = $this->paiementSur($autreFrais, $student, $user, 300, 'ENC-G2');

        $response = $this->get(route('backoffice.encaissements.recu-groupe', [
            'ids' => $a->id.','.$b->id, 'format' => 'a5',
        ]))->assertOk();

        $response->assertSee('ENC-G1 / ENC-G2', false);
        $response->assertSee('Frais d&#039;inscription 1', false);
        $response->assertSee('Frais de Juillet', false);
        // Le total est la somme des lignes sélectionnées, pas celle du dossier.
        $response->assertSee('500', false);
    }

    public function test_recu_groupe_refuses_payments_of_two_different_registrations(): void
    {
        $user = $this->userWith('payments.view');
        $this->actingAs($user);
        [$studentA, , $feeA] = $this->enrolledStudentWithFee(1500);
        [$studentB, , $feeB] = $this->enrolledStudentWithFee(1500);

        $a = $this->paiementSur($feeA, $studentA, $user, 200, 'ENC-X1');
        $b = $this->paiementSur($feeB, $studentB, $user, 300, 'ENC-X2');

        // La règle vit sur le serveur : le menu grisé côté React n'est qu'un
        // confort, une requête forgée doit être refusée ici.
        $this->get(route('backoffice.encaissements.recu-groupe', [
            'ids' => $a->id.','.$b->id,
        ]))->assertStatus(422);
    }

    public function test_recu_groupe_refuses_an_avance_which_belongs_to_no_registration(): void
    {
        $user = $this->userWith('payments.view');
        $this->actingAs($user);
        [$student, , $fee] = $this->enrolledStudentWithFee(1500);

        $paiement = $this->paiementSur($fee, $student, $user, 200, 'ENC-A1');
        $avance = Encaissement::create([
            'reference' => 'ENC-AV', 'student_id' => $student->id, 'inscription_fee_id' => null,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => $user->employee->id, 'montant' => 400,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ]);

        $this->get(route('backoffice.encaissements.recu-groupe', [
            'ids' => $paiement->id.','.$avance->id,
        ]))->assertStatus(422);
    }

    public function test_recu_groupe_requires_payments_view(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($user->fresh())
            ->get(route('backoffice.encaissements.recu-groupe', ['ids' => '1']))
            ->assertForbidden();
    }

    public function test_recu_can_be_emailed_to_a_given_address(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = $this->userWith('payments.view');
        $this->actingAs($user);
        [$student, , $fee] = $this->enrolledStudentWithFee(1500);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-MAIL', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => $user->employee->id, 'montant' => 1500, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        $this->post(route('backoffice.encaissements.recu.email', $encaissement), [
            'email' => 'parent@example.test',
        ])->assertRedirect();

        // Queued, never sent inline: the PDF render + SMTP must not block
        // the cashier's request (worker: crm-gls-queue.service).
        \Illuminate\Support\Facades\Mail::assertNotSent(\App\Domain\Payments\Mail\EncaissementRecuMail::class);
        \Illuminate\Support\Facades\Mail::assertQueued(
            \App\Domain\Payments\Mail\EncaissementRecuMail::class,
            fn ($mail) => $mail->hasTo('parent@example.test'),
        );
    }

    public function test_queued_recu_mail_survives_serialization_and_renders_the_pdf(): void
    {
        $user = $this->userWith('payments.view');
        $this->actingAs($user);
        [$student, , $fee] = $this->enrolledStudentWithFee(1500);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-MAIL3', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => $user->employee->id, 'montant' => 1500, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        // What the worker does: unserialize the job payload, then build the mail.
        $mail = unserialize(serialize(new \App\Domain\Payments\Mail\EncaissementRecuMail($encaissement)));

        $this->assertSame($encaissement->id, $mail->encaissement->id);
        $this->assertCount(1, $mail->attachments());
        $this->assertStringContainsString('ENC-MAIL3', $mail->envelope()->subject);
    }

    public function test_recu_email_embeds_its_logo_instead_of_linking_to_it(): void
    {
        // The regression this guards (27/08/2026): the header logo was an
        // asset() URL to a .webp. A URL is fetched by the mail client, so
        // Gmail could not reach a local APP_URL and image proxies block it —
        // the header rendered as a broken-image box — and no major mail
        // client decodes WebP anyway. It must travel INSIDE the message.
        $user = $this->userWith('payments.view');
        $this->actingAs($user);
        [$student, , $fee] = $this->enrolledStudentWithFee(1500);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-MAIL4', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => $user->employee->id, 'montant' => 1500, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        $html = (new \App\Domain\Payments\Mail\EncaissementRecuMail($encaissement))->render();

        // Self-contained: embed() inlines it, and Symfony turns it into a
        // cid: part when the message is actually assembled.
        $this->assertMatchesRegularExpression('/<img[^>]+src="(data:image\/png;base64,|cid:)/', $html);
        $this->assertDoesNotMatchRegularExpression('/<img[^>]+src="https?:\/\//', $html);
        $this->assertStringNotContainsString('.webp', $html);
    }

    public function test_recu_email_requires_a_valid_address(): void
    {
        $user = $this->userWith('payments.view');
        $this->actingAs($user);
        [$student, , $fee] = $this->enrolledStudentWithFee(1500);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-MAIL2', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => $user->employee->id, 'montant' => 1500, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        $this->post(route('backoffice.encaissements.recu.email', $encaissement), [
            'email' => 'not-an-email',
        ])->assertSessionHasErrors('email');
    }

    // --- money-integrity guards (audit 22/08/2026) ------------------------

    private function avanceFor(Student $student, User $user, float $montant): Encaissement
    {
        $this->actingAs($user)->post(route('backoffice.avances.store'), [
            'student_id' => $student->id,
            'montant' => (string) $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-21',
        ])->assertSessionDoesntHaveErrors();

        return Encaissement::whereNull('inscription_fee_id')->where('student_id', $student->id)->latest('id')->firstOrFail();
    }

    public function test_an_avance_cannot_be_applied_to_another_students_fee(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        [$studentA] = $this->enrolledStudentWithFee(1000);
        [, , $feeOfB] = $this->enrolledStudentWithFee(1000);
        $avance = $this->avanceFor($studentA, $user, 600);

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.avances.apply', $avance), ['fee_id' => $feeOfB->id, 'montant' => '600'])
            ->assertSessionHasErrors('fee_id');

        $this->assertSame(1, Encaissement::count());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeOfB->fresh()->statut);
    }

    public function test_an_avance_cannot_overpay_a_fee(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        [$student, , $fee] = $this->enrolledStudentWithFee(500);
        $avance = $this->avanceFor($student, $user, 1000);

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.avances.apply', $avance), ['fee_id' => $fee->id, 'montant' => '600'])
            ->assertSessionHasErrors('montant');
        $this->assertSame('1000.00', number_format($avance->fresh()->montantRestant(), 2, '.', ''));

        // The exact remaining due is accepted.
        $this->post(route('backoffice.avances.apply', $avance), ['fee_id' => $fee->id, 'montant' => '500'])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
        $this->assertSame('500.00', number_format($avance->fresh()->montantRestant(), 2, '.', ''));

        // A fully paid fee accepts nothing more.
        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.avances.apply', $avance), ['fee_id' => $fee->id, 'montant' => '1'])
            ->assertSessionHasErrors('montant');
    }

    public function test_a_refunded_avance_can_no_longer_be_applied_and_a_refund_is_capped_to_its_remaining(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update', 'refunds.create');
        [$student, , $fee] = $this->enrolledStudentWithFee(1000);
        $avance = $this->avanceFor($student, $user, 600);
        $caisse = $user->employee->caisses()->first();

        // Refund more than the avance holds → refused.
        $this->from(route('backoffice.depenses.index'))
            ->post(route('backoffice.remboursements.store'), [
                'beneficiaire_id' => $student->id,
                'encaissement_id' => $avance->id,
                'montant' => '700',
                'date_remboursement' => '2025-09-22',
            ])->assertSessionHasErrors('montant');

        // Refund the whole avance → the student's credit is consumed.
        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $avance->id,
            'montant' => '600',
            'date_remboursement' => '2025-09-22',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('0.00', (string) $caisse->fresh()->solde);
        $this->assertSame('0.00', number_format($avance->fresh()->montantRestant(), 2, '.', ''));

        // The same money cannot be applied to a fee afterwards.
        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.avances.apply', $avance), ['fee_id' => $fee->id, 'montant' => '600'])
            ->assertSessionHasErrors('montant');
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    public function test_a_refund_must_target_a_payment_of_the_same_student(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'refunds.create');
        [$studentA] = $this->enrolledStudentWithFee(1000);
        [$studentB] = $this->enrolledStudentWithFee(1000);
        $avanceOfA = $this->avanceFor($studentA, $user, 300);

        $this->from(route('backoffice.depenses.index'))
            ->post(route('backoffice.remboursements.store'), [
                'beneficiaire_id' => $studentB->id,
                'encaissement_id' => $avanceOfA->id,
                'montant' => '100',
                'date_remboursement' => '2025-09-22',
            ])->assertSessionHasErrors('encaissement_id');
    }

    public function test_a_refunded_payment_cannot_be_deleted(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.delete', 'refunds.create');
        [$student] = $this->enrolledStudentWithFee(1000);
        $avance = $this->avanceFor($student, $user, 500);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $avance->id,
            'montant' => '200',
            'date_remboursement' => '2025-09-22',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame('300.00', (string) $caisse->fresh()->solde);

        $this->from(route('backoffice.encaissements.index'))
            ->delete(route('backoffice.encaissements.destroy', $avance))
            ->assertSessionHasErrors('encaissement');

        $this->assertDatabaseHas('encaissements', ['id' => $avance->id]);
        $this->assertSame('300.00', (string) $caisse->fresh()->solde);
    }

    public function test_a_payment_cannot_be_recorded_on_another_students_registration(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$studentA] = $this->enrolledStudentWithFee(1000);
        [, $inscriptionB, $feeB] = $this->enrolledStudentWithFee(1000);

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.encaissements.store'), [
                'student_id' => $studentA->id,
                'inscription_id' => $inscriptionB->id,
                'date_paiement' => '2025-09-20',
                'payment_lines' => [
                    ['fee_id' => $feeB->id, 'montant' => '100', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                ],
            ])->assertSessionHasErrors('inscription_id');

        $this->assertSame(0, Encaissement::count());
    }

    public function test_two_rows_for_the_same_fee_cannot_together_exceed_its_remaining(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.encaissements.store'), [
                'student_id' => $student->id,
                'inscription_id' => $inscription->id,
                'date_paiement' => '2025-09-20',
                'payment_lines' => [
                    ['fee_id' => $fee->id, 'montant' => '800', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                    ['fee_id' => $fee->id, 'montant' => '800', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                ],
            ])->assertSessionHasErrors('payment_lines');

        $this->assertSame(0, Encaissement::count());
        $this->assertSame('0.00', (string) $user->employee->caisses()->first()->fresh()->solde);
    }

    public function test_a_cheque_funded_payment_keeps_its_cheque_method_on_edit(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update', 'cheques.view', 'cheques.create');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(500);

        $cheque = \App\Models\Cheque::create([
            'reference' => 'CHQ-EDIT', 'source' => \App\Models\Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => 'CHQ-EDIT-1', 'montant' => 500, 'banque' => 'BMCE',
            'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => \App\Models\Cheque::TYPE_A_DEPOSER, 'statut' => \App\Models\Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id, 'agent_id' => $user->employee->id,
        ]);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => 'Chèque', 'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id],
            ],
        ])->assertSessionDoesntHaveErrors();
        $encaissement = Encaissement::firstOrFail();

        $this->from(route('backoffice.encaissements.index'))
            ->put(route('backoffice.encaissements.update', $encaissement), [
                'methode' => 'Espèces', 'date_paiement' => '2025-09-21',
            ])->assertSessionHasErrors('methode');

        // Retyped cheque identity is ignored; the note is still editable.
        $this->put(route('backoffice.encaissements.update', $encaissement), [
            'methode' => 'Chèque', 'numero_cheque' => 'OTHER', 'banque' => 'CIH',
            'date_echeance_cheque' => '2025-12-01', 'date_paiement' => '2025-09-21', 'note' => 'ok',
        ])->assertSessionDoesntHaveErrors();

        $fresh = $encaissement->fresh();
        $this->assertSame('CHQ-EDIT-1', $fresh->numero_cheque);
        $this->assertSame('BMCE', $fresh->banque);
        $this->assertSame('ok', $fresh->note);
        // The date needs `payments.update-date` (super-admin only) since
        // 30/08/2026 — this user holds `payments.update` alone.
        $this->assertSame('2025-09-20', $fresh->date_paiement->toDateString());
    }

    public function test_show_requires_payments_view(): void
    {
        $owner = $this->userWith('payments.view', 'payments.create');
        [$student] = $this->enrolledStudentWithFee(1000);
        $avance = $this->avanceFor($student, $owner, 100);

        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.encaissements.show', $avance))
            ->assertForbidden();
    }

    // --- Une inscription non ACTIVE ne se paie pas ----------------------
    // Annulée / Archivée / Expirée / Changement : le dossier est clos, ses
    // frais ne sont plus dus. L'argent reçu s'enregistre en avance, puis
    // s'applique à une inscription active. Le dropdown filtre, le serveur
    // refuse (le filtre client n'est qu'un confort d'interface, §5).

    public function test_the_registration_lookup_lists_only_active_registrations(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $this->actingAs($user);

        [$student, $active] = $this->enrolledStudentWithFee(1000);
        [, $archivee] = $this->enrolledStudentWithFee(1000);
        $archivee->update(['student_id' => $student->id, 'statut' => Inscription::STATUT_ARCHIVEE]);

        $ids = collect($this->get(route('backoffice.students.inscriptions-for-payment', $student))->json('inscriptions'))
            ->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($archivee->id));
    }

    public function test_a_payment_on_a_non_active_registration_is_refused(): void
    {
        foreach ([Inscription::STATUT_ARCHIVEE, Inscription::STATUT_ANNULEE, Inscription::STATUT_EXPIREE] as $statut) {
            $user = $this->userWith('payments.view', 'payments.create');
            $this->actingAs($user);
            [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
            $inscription->update(['statut' => $statut]);
            $caisse = $user->employee->caisses()->first();

            $this->from(route('backoffice.encaissements.index'))
                ->post(route('backoffice.encaissements.store'), [
                    'student_id' => $student->id,
                    'inscription_id' => $inscription->id,
                    'caisse_id' => $caisse->id,
                    'date_paiement' => '2025-09-20',
                    'payment_lines' => [
                        ['fee_id' => $fee->id, 'montant' => '1500', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
                    ],
                ])->assertSessionHasErrors('inscription_id');

            // Rien n'a bougé : ni encaissement, ni solde de caisse.
            $this->assertSame(0, Encaissement::where('student_id', $student->id)->count(), $statut);
            $this->assertSame('0.00', (string) $caisse->fresh()->solde, $statut);
        }
    }

    public function test_an_advance_cannot_be_applied_to_a_non_active_registrations_fee(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $avance = $this->avanceFor($student, $user, 600);
        $inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.avances.apply', $avance), ['fee_id' => $fee->id, 'montant' => '600'])
            ->assertSessionHasErrors('fee_id');

        $this->assertSame(600.0, (float) $avance->fresh()->montantRestant());
    }

    /**
     * Le sens INVERSE reste ouvert : convertir en avance libère l'argent
     * d'un dossier qu'on vient justement de fermer (changement de groupe,
     * annulation). L'exiger actif emprisonnerait le versement.
     */
    public function test_payments_of_a_cancelled_registration_can_still_be_converted_into_an_advance(): void
    {
        $user = $this->userWith('payments.view', 'payments.create', 'payments.update');
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'caisse_id' => $caisse->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionDoesntHaveErrors();

        $encaissement = Encaissement::where('inscription_fee_id', $fee->id)->firstOrFail();
        $inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.avances.convert'), [
                'inscription_id' => $inscription->id,
                'encaissement_ids' => [$encaissement->id],
            ])->assertSessionDoesntHaveErrors();

        $this->assertNull($encaissement->fresh()->inscription_fee_id);
    }
}
