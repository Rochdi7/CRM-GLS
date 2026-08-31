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
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Same class of defect as RefundAvanceVisibilityTest, found by auditing every
 * read-model against the action it feeds (31/08/2026).
 *
 * `AppliquerAvance` refuses an avance funded by a cheque the bank REJECTED
 * (audit DB-05: the Chèque account was already reversed, so that money never
 * existed). But `montantRestant()` is pure arithmetic — montant minus what was
 * applied and refunded — and knows nothing about the cheque. So the Avances tab
 * reported the full amount as still available and rendered « Appliquer à un
 * frais » on money the action could only ever refuse.
 *
 * That is the mirror image of the refund bug: there a read-model HID a row the
 * action accepts; here it OFFERS an action the write path rejects. Both come
 * from a list query re-deriving a rule instead of carrying the action's own.
 *
 * The row therefore now ships `applicable` (and `chequeRejete` for the badge),
 * and the UI gates on that rather than on the remaining amount.
 */
final class AvanceApplicabilityTest extends TestCase
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

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
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

    private function compteCheque(): Caisse
    {
        return Caisse::query()
            ->where('etablissement_id', $this->centre->id)
            ->where('type', Caisse::TYPE_CHEQUE)
            ->firstOrFail();
    }

    /** An unallocated avance funded by a tracked cheque. */
    private function avanceParCheque(User $user, Student $student, float $montant = 2000): array
    {
        $cheque = Cheque::create([
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

        $avance = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'etablissement_id' => $this->centre->id,
            'inscription_fee_id' => null,
            'cheque_id' => $cheque->id,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_CHEQUE,
            'date_paiement' => '2025-09-20',
            'caisse_id' => $this->compteCheque()->id,
            'agent_id' => $user->employee->id,
        ]);

        return [$avance, $cheque];
    }

    /** @return array<string, mixed>|null */
    private function avanceRow(int $id): ?array
    {
        $row = null;

        $this->get(route('backoffice.encaissements.index', ['view' => 'avance']))
            ->assertInertia(function (Assert $page) use ($id, &$row): void {
                $row = collect($page->toArray()['props']['encaissements']['data'])->firstWhere('id', $id);
            });

        return $row;
    }

    // -- The bug ---------------------------------------------------------

    public function test_an_avance_funded_by_a_rejected_cheque_is_not_offered_for_application(): void
    {
        $user = $this->superAdmin();
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolled();
        [$avance, $cheque] = $this->avanceParCheque($user, $student);

        // While the cheque is good, the avance is applicable.
        $row = $this->avanceRow($avance->id);
        $this->assertNotNull($row);
        $this->assertTrue($row['applicable']);
        $this->assertFalse($row['chequeRejete']);
        $this->assertSame('2000.00', $row['montantRestant']);

        // The bank bounces it.
        $cheque->update(['statut' => Cheque::STATUT_REJETE]);

        // THE BUG: montantRestant is still 2 000 (correct arithmetic — the
        // money was never applied), but the action refuses it, so the UI must
        // no longer offer « Appliquer à un frais ».
        $row = $this->avanceRow($avance->id);
        $this->assertNotNull($row);
        $this->assertSame('2000.00', $row['montantRestant']);
        $this->assertFalse($row['applicable']);
        $this->assertTrue($row['chequeRejete']);
    }

    public function test_the_action_still_refuses_a_rejected_cheque_avance(): void
    {
        $user = $this->superAdmin();
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolled();
        [$avance, $cheque] = $this->avanceParCheque($user, $student);
        $cheque->update(['statut' => Cheque::STATUT_REJETE]);

        $this->post(route('backoffice.avances.apply', $avance), [
            'inscription_id' => $inscription->id,
            'fee_id' => $fee->id,
            'montant' => '2000.00',
        ]);

        // Server-side gate is the real one — the flag only mirrors it.
        $this->assertSame(0, $avance->fresh()->applications()->count());
        $this->assertSame(
            InscriptionFee::STATUT_NON_PAYE,
            $fee->fresh()->statut,
            'A bounced cheque must not settle a fee.',
        );
    }

    public function test_an_ordinary_avance_stays_applicable(): void
    {
        $user = $this->superAdmin();
        $this->actingAs($user);
        [$student, $inscription, $fee] = $this->enrolled();

        $avance = Encaissement::create([
            'reference' => 'ENC-CASH', 'student_id' => $student->id,
            'etablissement_id' => $this->centre->id, 'inscription_fee_id' => null,
            'montant' => 2000, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-09-20',
            'caisse_id' => $user->employee->till()->firstOrFail()->id,
            'agent_id' => $user->employee->id,
        ]);

        $row = $this->avanceRow($avance->id);
        $this->assertNotNull($row);
        $this->assertTrue($row['applicable']);
        $this->assertFalse($row['chequeRejete']);

        // And it really does apply.
        $this->post(route('backoffice.avances.apply', $avance), [
            'inscription_id' => $inscription->id,
            'fee_id' => $fee->id,
            'montant' => '2000.00',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $avance->fresh()->applications()->count());
    }

    public function test_a_cheque_still_in_hand_or_cashed_stays_applicable(): void
    {
        $user = $this->superAdmin();
        $this->actingAs($user);
        [$student] = $this->enrolled();

        foreach ([Cheque::STATUT_EN_POSSESSION, Cheque::STATUT_DEPOSE, Cheque::STATUT_ENCAISSE] as $statut) {
            [$avance, $cheque] = $this->avanceParCheque($user, $student);
            $cheque->update(['statut' => $statut]);

            $row = $this->avanceRow($avance->id);
            $this->assertNotNull($row, "Missing row for statut {$statut}");
            $this->assertTrue($row['applicable'], "A « {$statut} » cheque must stay applicable.");
            $this->assertFalse($row['chequeRejete']);
        }
    }

    // -- The remedy must stay open --------------------------------------

    public function test_a_rejected_cheque_avance_can_still_be_refunded_off_the_cheque_account(): void
    {
        $user = $this->superAdmin();
        $this->actingAs($user);
        [$student] = $this->enrolled();
        [$avance, $cheque] = $this->avanceParCheque($user, $student);
        $cheque->update(['statut' => Cheque::STATUT_REJETE]);

        // Refusing to APPLY must not also block the reversal — that is the
        // whole remedy for a bounced cheque.
        $rows = $this->getJson(route('backoffice.students.payments-for-refund', $student))->json('payments');
        $this->assertSame([$avance->id], array_column($rows, 'id'));

        $till = $user->employee->till()->firstOrFail();
        $compte = $this->compteCheque();
        $soldeTill = (float) $till->fresh()->solde;
        $soldeCheque = (float) $compte->fresh()->solde;

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $avance->id,
            'montant' => '2000.00',
            'date_remboursement' => '2025-10-01',
        ])->assertSessionHasNoErrors();

        // Money that never reached the till must not leave it.
        $remboursement = Remboursement::query()->where('beneficiaire_id', $student->id)->firstOrFail();
        $this->assertSame($compte->id, $remboursement->caisse_id);
        $this->assertEqualsWithDelta($soldeTill, (float) $till->fresh()->solde, 0.001);
        $this->assertEqualsWithDelta($soldeCheque - 2000, (float) $compte->fresh()->solde, 0.001);
    }
}
