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
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_index_requires_payments_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.encaissements.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('payments.view'))
            ->get(route('backoffice.encaissements.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Encaissements/Index', false)
                ->has('encaissements')
                ->has('caisses')
                ->has('students')
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
        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student1->id, 'inscription_id' => $inscription1->id,
            'date_paiement' => '2025-09-20',
            'numero_cheque' => 'CHQ-1', 'banque' => 'BMCE', 'date_echeance_cheque' => '2025-10-01',
            'payment_lines' => [
                ['fee_id' => $feeFull->id, 'montant' => '1000', 'methode' => 'Chèque', 'date_paiement' => '2025-09-20'],
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

        $chequeRows = $this->get(route('backoffice.encaissements.index', ['view' => 'cheque']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $chequeRows);
        $this->assertSame('Chèque', $chequeRows[0]['methode']);

        $avanceRows = $this->get(route('backoffice.encaissements.index', ['view' => 'avance']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $avanceRows);
        $this->assertSame('400.00', (string) $avanceRows[0]['montant']);
        $this->assertSame('0.00', (string) $avanceRows[0]['montantUtilise']);
        $this->assertSame('400.00', (string) $avanceRows[0]['montantRestant']);

        $allRows = $this->get(route('backoffice.encaissements.index'))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(2, $allRows);
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

        $avanceRows = $this->get(route('backoffice.encaissements.index', ['view' => 'avance']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $avanceRows);
        $this->assertSame('600.00', (string) $avanceRows[0]['montantUtilise']);
        $this->assertSame('0.00', (string) $avanceRows[0]['montantRestant']);
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
            'methode' => 'Virement',
            'date_paiement' => '2025-09-21',
            // Tampered — must have zero effect.
            'montant' => '9999',
            'caisse_id' => $otherCaisse->id,
        ])->assertSessionDoesntHaveErrors();

        $fresh = $encaissement->fresh();
        $this->assertSame('Virement', $fresh->methode);
        $this->assertSame('1500.00', (string) $fresh->montant);
        $this->assertSame($caisse->id, $fresh->caisse_id);
    }

    public function test_no_delete_route_exists_for_payments(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.encaissements.destroy'));
    }
}
