<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetStudentPaymentsForRefund;
use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Domain\Payments\Actions\SupprimerEncaissement;
use App\Domain\Payments\Support\ResoudreAllocationsAvance;
use App\Models\Activity;
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
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * « Scinder » un paiement lors de la conversion en avance (17/09/2026).
 *
 * Le cas réel : un étudiant paie 1 400 DH sur le groupe A, suit deux
 * semaines, puis change de groupe (autre enseignant). L'école garde 700 DH
 * sur le frais du groupe A et libère 700 DH en avance pour le groupe B.
 *
 * La scission est COMPOSÉE des deux primitives existantes, dans une
 * transaction : détacher la ligne (conversion), puis ré-appliquer la part
 * conservée au MÊME frais (AppliquerAvance). Aucune colonne, aucun montant
 * édité, aucune caisse touchée — et chaque lecture qui raisonne sur
 * `applied_from_encaissement_id` (journal, remboursement, suppression,
 * chaîne d'allocations) doit rester juste sans rien apprendre de nouveau.
 * C'est ce que ces tests prouvent, invariant par invariant.
 */
final class AvanceScindeeSurConversionTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        $this->user = User::factory()->create();
        foreach (['payments.view', 'payments.create', 'payments.update', 'refunds.view', 'refunds.create', 'centers.access-all'] as $p) {
            $this->user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $this->user->id, 'etablissement_id' => $this->centre->id]);
        $this->user = $this->user->fresh();
    }

    // -- Fixtures ---------------------------------------------------------

    /** @return array{0: Inscription, 1: InscriptionFee} */
    private function inscriptionWithFee(Student $student, string $nom, float $montant = 1400): array
    {
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => $nom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    private function till(): Caisse
    {
        return $this->user->employee->till()->firstOrFail();
    }

    /** A fee-attached payment recorded through the real store endpoint, so the till is credited like in production. */
    private function payFee(Student $student, Inscription $inscription, InscriptionFee $fee, string $montant): Encaissement
    {
        $this->actingAs($this->user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors();

        return Encaissement::query()->where('student_id', $student->id)->latest('id')->firstOrFail();
    }

    /**
     * The scenario: 1 400 DH paid on « Frais groupe A », then split 700 / 700.
     *
     * @return array{0: Student, 1: Inscription, 2: InscriptionFee, 3: Encaissement}
     */
    private function scinde(float $libere = 700): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');

        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id], [$encaissement->id => $libere]);

        return [$student, $inscription, $fee, $encaissement->fresh()];
    }

    // -- The reported need --------------------------------------------------

    public function test_the_kept_part_stays_paid_on_the_original_fee_and_the_rest_becomes_an_avance(): void
    {
        [, , $fee, $encaissement] = $this->scinde();

        // The original row is now an avance of its FULL amount, 700 of which
        // is already spent — on the fee it came from.
        $this->assertTrue($encaissement->isAvance());
        $this->assertSame('1400.00', (string) $encaissement->montant);
        $this->assertSame(700.0, $encaissement->montantUtilise());
        $this->assertSame(700.0, $encaissement->montantRestant());

        // The fee shows the two taught weeks as paid, the rest as owed.
        $fee = $fee->fresh();
        $this->assertSame(700.0, $fee->montantPaye());
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->statut);

        // Exactly one application row, linked to the split row, on that fee.
        $application = Encaissement::query()->where('applied_from_encaissement_id', $encaissement->id)->sole();
        $this->assertSame($fee->id, $application->inscription_fee_id);
        $this->assertSame('700.00', (string) $application->montant);
    }

    public function test_the_released_part_can_then_be_applied_to_the_new_group(): void
    {
        [$student, , $feeA, $encaissement] = $this->scinde();
        [, $feeB] = $this->inscriptionWithFee($student, 'Frais groupe B', 700);

        app(AppliquerAvance::class)->handle($encaissement, $feeB, 700.0);

        $this->assertSame(0.0, $encaissement->fresh()->montantRestant());
        $this->assertSame(InscriptionFee::STATUT_PAYE, $feeB->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $feeA->fresh()->statut);

        // The single definition of the chain names BOTH fees, 700 each.
        $allocations = ResoudreAllocationsAvance::terminales([$encaissement->id])[$encaissement->id];
        $this->assertSame(
            [['frais', 'Frais groupe A', 700.0], ['frais', 'Frais groupe B', 700.0]],
            array_map(fn (array $a): array => [$a['kind'], ResoudreAllocationsAvance::libelle($a), $a['montant']], $allocations),
        );
        $this->assertSame('Frais groupe A + Frais groupe B', $encaissement->fresh()->load('applications.fee')->libelleFrais());
    }

    // -- Money invariants (CLAUDE.md §11) ------------------------------------

    public function test_no_till_moves_and_no_money_row_is_created_or_edited(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');
        $till = $this->till();
        $soldeAvant = (string) $till->fresh()->solde;
        $ledgerAvant = Activity::query()->where('log_name', 'caisse')->where('event', 'solde_movement')->count();

        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id], [$encaissement->id => 700.0]);

        // Till untouched, no ledger entry, and the money rows (applied_from
        // NULL — the only ones the journal / comptes / coherence auditor
        // count) still sum to exactly what was received.
        $this->assertSame($soldeAvant, (string) $till->fresh()->solde);
        $this->assertSame($ledgerAvant, Activity::query()->where('log_name', 'caisse')->where('event', 'solde_movement')->count());
        $this->assertSame('1400.00', (string) Encaissement::query()->whereNull('applied_from_encaissement_id')->where('caisse_id', $till->id)->sum('montant'));
        $this->assertSame(1, Encaissement::query()->whereNull('applied_from_encaissement_id')->count());

        // The application row inherits caisse, agent and date from the split
        // row — never the operator, never today.
        $application = Encaissement::query()->where('applied_from_encaissement_id', $encaissement->id)->sole();
        $this->assertSame($encaissement->caisse_id, $application->caisse_id);
        $this->assertSame($encaissement->agent_id, $application->agent_id);
        $this->assertSame('2025-09-20', $application->date_paiement->toDateString());
        $this->assertSame($encaissement->methode, $application->methode);
    }

    public function test_a_refund_of_the_split_avance_is_capped_at_the_released_part(): void
    {
        [$student, , , $encaissement] = $this->scinde();

        // The picker offers the avance for what is still free (700), and
        // never the application row (its money is the parent's).
        $rows = app(GetStudentPaymentsForRefund::class)($student->id);
        $this->assertSame([$encaissement->id], $rows->pluck('id')->all());
        $this->assertSame('700.00', $rows->first()['montantRemboursable']);

        $refund = fn (string $montant) => $this->actingAs($this->user)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $encaissement->id,
            'montant' => $montant,
            'date_remboursement' => '2025-10-01',
            'motif' => 'Changement de groupe',
        ]);

        $refund('700.01')->assertSessionHasErrors('montant');
        $this->assertSame(0, Remboursement::count());

        $refund('700.00')->assertSessionHasNoErrors();
        $this->assertSame(0.0, $encaissement->fresh()->montantRestant());
    }

    public function test_the_split_row_cannot_be_deleted_while_it_carries_the_kept_part(): void
    {
        [, , , $encaissement] = $this->scinde();

        try {
            app(SupprimerEncaissement::class)->handle($encaissement);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('encaissement', $e->errors());
        }

        $this->assertSame(2, Encaissement::count());
    }

    // -- Bounds and atomicity -----------------------------------------------

    public function test_releasing_the_whole_amount_is_a_plain_conversion_with_no_application_row(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');

        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id], [$encaissement->id => 1400.0]);

        $this->assertSame(1, Encaissement::count());
        $this->assertSame(1400.0, $encaissement->fresh()->montantRestant());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
        $this->assertSame(0, Activity::query()->where('event', 'avance_split')->count());
    }

    /** @return list<array{float}> */
    public static function montantsHorsBornes(): array
    {
        return [[0.0], [-1.0], [1400.01], [5000.0]];
    }

    #[DataProvider('montantsHorsBornes')]
    public function test_an_amount_outside_the_row_is_refused_and_nothing_is_detached(float $libere): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');

        try {
            app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id], [$encaissement->id => $libere]);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('montants.'.$encaissement->id, $e->errors());
        }

        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
        $this->assertSame(1, Encaissement::count());
    }

    public function test_one_bad_amount_refuses_the_whole_lot(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $feeA] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $feeB = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Livre',
            'montant_initial' => 200, 'montant' => 200,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $paidA = $this->payFee($student, $inscription, $feeA, '1400.00');
        $paidB = $this->payFee($student, $inscription, $feeB, '200.00');

        try {
            app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$paidA->id, $paidB->id], [
                $paidA->id => 700.0,
                $paidB->id => 999.0, // beyond the 200 row
            ]);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('montants.'.$paidB->id, $e->errors());
        }

        // The valid first row was NOT converted on its own (§11 « signaler
        // plutôt que masquer »): the operator sees one refusal, not a lot
        // half-done.
        $this->assertSame($feeA->id, $paidA->fresh()->inscription_fee_id);
        $this->assertSame($feeB->id, $paidB->fresh()->inscription_fee_id);
        $this->assertSame(2, Encaissement::count());
    }

    public function test_rows_without_an_amount_are_still_converted_in_full_next_to_split_ones(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $feeA] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $feeB = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Livre',
            'montant_initial' => 200, 'montant' => 200,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $paidA = $this->payFee($student, $inscription, $feeA, '1400.00');
        $paidB = $this->payFee($student, $inscription, $feeB, '200.00');

        $count = app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$paidA->id, $paidB->id], [$paidA->id => 700.0]);

        $this->assertSame(2, $count);
        $this->assertSame(700.0, $paidA->fresh()->montantRestant());
        $this->assertSame(200.0, $paidB->fresh()->montantRestant());
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $feeA->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeB->fresh()->statut);
    }

    // -- Where a split is refused but a full conversion still passes --------

    public function test_a_hidden_fee_refuses_the_split_but_accepts_a_full_conversion(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');
        // Legacy state: a hidden fee still carrying a payment (pre-31/08/2026 rows).
        $fee->forceFill(['masque_le' => now(), 'masque_origine' => InscriptionFee::MASQUE_ORIGINE_MANUEL])->save();

        try {
            app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id], [$encaissement->id => 700.0]);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('montants.'.$encaissement->id, $e->errors());
        }
        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);

        // In full: the money is released, nothing is put back on the fee.
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id]);
        $this->assertTrue($encaissement->fresh()->isAvance());
        $this->assertSame(1400.0, $encaissement->fresh()->montantRestant());
    }

    public function test_a_rejected_cheque_refuses_the_split(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $cheque = Cheque::create([
            'reference' => 'CHQ-'.fake()->unique()->numerify('#####'),
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id,
            'numero_cheque' => fake()->unique()->numerify('CHQ#####'),
            'montant' => '1400.00',
            'date_reception' => '2025-09-18',
            'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id,
            'agent_id' => $this->user->employee->id,
        ]);
        $this->actingAs($this->user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1400.00', 'methode' => Encaissement::METHODE_CHEQUE, 'date_paiement' => '2025-09-20', 'cheque_id' => $cheque->id],
            ],
        ])->assertSessionHasNoErrors();
        $encaissement = Encaissement::query()->where('student_id', $student->id)->sole();
        $cheque->forceFill(['statut' => Cheque::STATUT_REJETE])->save();

        try {
            app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$encaissement->id], [$encaissement->id => 700.0]);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('montants.'.$encaissement->id, $e->errors());
        }
        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
    }

    // -- A split of an application row (multi-level chain) ------------------

    public function test_splitting_an_application_row_keeps_the_root_avance_consistent(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $feeA] = $this->inscriptionWithFee($student, 'Frais groupe A');
        [, $feeB] = $this->inscriptionWithFee($student, 'Frais groupe B', 700);

        // A 1 400 avance received as such, fully applied to fee A.
        $avance = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'etablissement_id' => $this->centre->id, 'student_id' => $student->id,
            'inscription_fee_id' => null, 'caisse_id' => $this->till()->id,
            'montant' => 1400, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
            'agent_id' => $this->user->employee->id,
        ]);
        $application = app(AppliquerAvance::class)->handle($avance, $feeA, 1400.0);

        // Split THAT application row: 700 stays on A, 700 is freed.
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$application->id], [$application->id => 700.0]);

        // The root still counts its 1 400 as spent (its child is intact);
        // the freed 700 lives on the detached child, the only place it is
        // available — exactly the 28/08/2026 rule for detached rows.
        $this->assertSame(0.0, $avance->fresh()->montantRestant());
        $this->assertSame($avance->id, $application->fresh()->applied_from_encaissement_id);
        $this->assertSame(700.0, $application->fresh()->montantRestant());
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $feeA->fresh()->statut);

        app(AppliquerAvance::class)->handle($application->fresh(), $feeB, 700.0);

        $allocations = ResoudreAllocationsAvance::terminales([$avance->id])[$avance->id];
        $this->assertSame(
            [['frais', 'Frais groupe A', 700.0], ['frais', 'Frais groupe B', 700.0]],
            array_map(fn (array $a): array => [$a['kind'], ResoudreAllocationsAvance::libelle($a), $a['montant']], $allocations),
        );
    }

    // -- HTTP surface ---------------------------------------------------------

    public function test_the_convert_endpoint_accepts_per_row_amounts(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');

        $this->actingAs($this->user)->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$encaissement->id],
            'montants' => [$encaissement->id => '700'],
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.encaissements.index', ['view' => 'avance']));

        $this->assertSame(700.0, $encaissement->fresh()->montantRestant());
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);

        // The Avances tab shows the split row with what is used / left and
        // where the kept part went.
        $rows = $this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props']['encaissements']['data'];
        $this->assertCount(1, $rows);
        $this->assertSame('700.00', (string) $rows[0]['montantUtilise']);
        $this->assertSame('700.00', (string) $rows[0]['montantRestant']);
        $this->assertSame(['Frais groupe A'], array_column($rows[0]['fraisAppliques'], 'frais'));

        // The journal explains the split.
        $entry = Activity::query()->where('event', 'avance_split')->sole();
        $this->assertSame('700.00', $entry->properties['montant_conserve']);
        $this->assertSame('700.00', $entry->properties['montant_libere']);
        $this->assertSame('Frais groupe A', $entry->properties['frais']);
    }

    public function test_the_convert_endpoint_reports_an_out_of_range_amount_on_the_row(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');

        $this->actingAs($this->user)->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$encaissement->id],
            'montants' => [$encaissement->id => '1400.01'],
        ])->assertSessionHasErrors('montants.'.$encaissement->id);

        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
    }

    public function test_an_empty_amount_means_the_whole_row(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $fee] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $encaissement = $this->payFee($student, $inscription, $fee, '1400.00');

        $this->actingAs($this->user)->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$encaissement->id],
            'montants' => [$encaissement->id => ''],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1400.0, $encaissement->fresh()->montantRestant());
        $this->assertSame(1, Encaissement::count());
    }

    public function test_the_checklist_carries_the_split_rule(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$inscription, $feeA] = $this->inscriptionWithFee($student, 'Frais groupe A');
        $feeB = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Livre',
            'montant_initial' => 200, 'montant' => 200,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $paidA = $this->payFee($student, $inscription, $feeA, '1400.00');
        $paidB = $this->payFee($student, $inscription, $feeB, '200.00');
        $feeB->forceFill(['masque_le' => now(), 'masque_origine' => InscriptionFee::MASQUE_ORIGINE_MANUEL])->save();

        $rows = $this->actingAs($this->user)
            ->getJson(route('backoffice.inscriptions.payments', $inscription))
            ->assertOk()
            ->json('payments');
        $byId = array_column($rows, null, 'id');

        $this->assertTrue($byId[$paidA->id]['splittable']);
        $this->assertNull($byId[$paidA->id]['splitBlocker']);
        $this->assertFalse($byId[$paidB->id]['splittable']);
        $this->assertNotNull($byId[$paidB->id]['splitBlocker']);
    }
}
