<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetStudentPaymentsForRefund;
use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Domain\Payments\Support\ResoudreAllocationsAvance;
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
use Tests\TestCase;

/**
 * Le MÊME argent converti en avance PLUSIEURS fois (17/09/2026) : payé sur
 * A → converti → appliqué sur B → reconverti → appliqué sur C → scindé
 * (600 gardés sur C, 400 libérés) → appliqué sur D → reconverti. À chaque
 * tour la chaîne `applied_from` s'allonge d'un niveau ; aucune lecture ne
 * doit compter un dirham deux fois ni en perdre un.
 */
final class AvanceConvertieEnBoucleTest extends TestCase
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

    /** @return array{0: Inscription, 1: InscriptionFee} */
    private function inscriptionWithFee(Student $student, string $nom, float $montant = 1000): array
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

    /** @param  array<string, mixed>  $extra */
    private function pay(Student $student, Inscription $inscription, InscriptionFee $fee, string $montant, array $extra = []): Encaissement
    {
        $this->actingAs($this->user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [array_merge(['fee_id' => $fee->id, 'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'], $extra)],
        ])->assertSessionHasNoErrors();

        return Encaissement::query()->where('student_id', $student->id)->latest('id')->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function avancesTab(string $solde = 'restant'): array
    {
        return $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.index', ['view' => 'avance', 'soldeFilter' => $solde, 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props'];
    }

    public function test_the_same_money_survives_four_conversions_without_duplication_or_loss(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$insA, $feeA] = $this->inscriptionWithFee($student, 'Frais A');
        [$insB, $feeB] = $this->inscriptionWithFee($student, 'Frais B');
        [$insC, $feeC] = $this->inscriptionWithFee($student, 'Frais C');
        [$insD, $feeD] = $this->inscriptionWithFee($student, 'Frais D');

        $R = $this->pay($student, $insA, $feeA, '1000.00');
        $till = $this->till();
        $solde = (string) $till->fresh()->solde;
        $convertir = app(ConvertirEncaissementsEnAvance::class);
        $appliquer = app(AppliquerAvance::class);

        // Tour 1 : A → avance → B
        $convertir->handle($insA, [$R->id]);
        $A1 = $appliquer->handle($R->fresh(), $feeB, 1000.0);
        // Tour 2 : B → avance → C
        $convertir->handle($insB, [$A1->id]);
        $A2 = $appliquer->handle($A1->fresh(), $feeC, 1000.0);
        // Tour 3 : scission sur C — 600 restent, 400 libérés → D
        $convertir->handle($insC, [$A2->id], [$A2->id => 400.0]);
        $A2b = Encaissement::query()->where('applied_from_encaissement_id', $A2->id)->sole();
        $A3 = $appliquer->handle($A2->fresh(), $feeD, 400.0);

        // Un seul dirham reçu, une seule ligne monétaire, caisse immobile.
        $this->assertSame($solde, (string) $till->fresh()->solde);
        $this->assertSame(1, Encaissement::query()->whereNull('applied_from_encaissement_id')->count());
        $this->assertSame('1000.00', (string) Encaissement::query()->whereNull('applied_from_encaissement_id')->sum('montant'));

        // Ce que chaque frais reçoit.
        $this->assertSame(0.0, $feeA->fresh()->montantPaye());
        $this->assertSame(0.0, $feeB->fresh()->montantPaye());
        $this->assertSame(600.0, $feeC->fresh()->montantPaye());
        $this->assertSame(400.0, $feeD->fresh()->montantPaye());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeA->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeB->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $feeC->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $feeD->fresh()->statut);

        // Chaque maillon est épuisé — l'argent est TOUJOURS sur un frais.
        foreach ([$R, $A1, $A2] as $row) {
            $this->assertSame(0.0, $row->fresh()->montantRestant(), $row->reference);
        }
        $this->assertSame($A1->id, $A2->fresh()->applied_from_encaissement_id);
        $this->assertSame($A2->id, $A2b->applied_from_encaissement_id);
        $this->assertSame($A2->id, $A3->applied_from_encaissement_id);

        // La chaîne depuis la racine nomme C 600 + D 400 et rien d'autre.
        $allocations = ResoudreAllocationsAvance::terminales([$R->id])[$R->id];
        $this->assertSame(
            [['frais', 'Frais C', 600.0], ['frais', 'Frais D', 400.0]],
            array_map(fn (array $a): array => [$a['kind'], ResoudreAllocationsAvance::libelle($a), $a['montant']], $allocations),
        );
        $this->assertSame(1000.0, array_sum(array_column($allocations, 'montant')));

        // Onglet Avances : rien de « restant », trois lignes « épuisées »,
        // total d'en-tête 0 — le SQL de la liste concorde avec montantRestant().
        $restant = $this->avancesTab('restant');
        $this->assertSame([], $restant['encaissements']['data']);
        $this->assertSame('0.00', (string) $restant['montantTotal']);
        $epuise = $this->avancesTab('epuise');
        $this->assertEqualsCanonicalizing([$R->id, $A1->id, $A2->id], array_column($epuise['encaissements']['data'], 'id'));

        // Rien à rembourser : tout est posé sur des frais.
        $this->assertSame([], app(GetStudentPaymentsForRefund::class)($student->id)->all());

        // L'auditeur de cohérence ne voit aucune anomalie.
        $this->artisan('caisse:verifier-coherence', ['--strict' => true])->assertExitCode(0);

        // Tour 4 : reconvertir D en entier → 400 redeviennent disponibles sur A3 seul.
        $convertir->handle($insD, [$A3->id]);
        $this->assertSame(400.0, $A3->fresh()->montantRestant());
        $this->assertSame(0.0, $A2->fresh()->montantRestant());
        $restant = $this->avancesTab('restant');
        $this->assertSame([$A3->id], array_column($restant['encaissements']['data'], 'id'));
        $this->assertSame('400.00', (string) $restant['montantTotal']);
        $this->assertSame('Frais D', $restant['encaissements']['data'][0]['ancienFrais']);
        $picker = app(GetStudentPaymentsForRefund::class)($student->id);
        $this->assertSame([$A3->id], $picker->pluck('id')->all());
        $this->assertSame('400.00', $picker->first()['montantRemboursable']);

        $allocations = ResoudreAllocationsAvance::terminales([$R->id])[$R->id];
        $this->assertSame(
            [['frais', 'Frais C', 600.0], ['non_lie', 'Frais non lié', 400.0]],
            array_map(fn (array $a): array => [$a['kind'], ResoudreAllocationsAvance::libelle($a), $a['montant']], $allocations),
        );
    }

    public function test_a_reconverted_application_of_a_rejected_cheque_is_not_applicable(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$insA, $feeA] = $this->inscriptionWithFee($student, 'Frais A');
        [, $feeB] = $this->inscriptionWithFee($student, 'Frais B');
        [, $feeC] = $this->inscriptionWithFee($student, 'Frais C');
        $cheque = Cheque::create([
            'reference' => 'CHQ-'.fake()->unique()->numerify('#####'), 'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id, 'numero_cheque' => fake()->unique()->numerify('CHQ#####'),
            'montant' => '1000.00', 'date_reception' => '2025-09-18', 'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => Cheque::STATUT_EN_POSSESSION, 'etablissement_id' => $this->centre->id,
            'agent_id' => $this->user->employee->id,
        ]);
        $R = $this->pay($student, $insA, $feeA, '1000.00', ['methode' => Encaissement::METHODE_CHEQUE, 'cheque_id' => $cheque->id]);

        app(ConvertirEncaissementsEnAvance::class)->handle($insA, [$R->id]);
        $A1 = app(AppliquerAvance::class)->handle($R->fresh(), $feeB, 1000.0);
        $cheque->forceFill(['statut' => Cheque::STATUT_REJETE])->save();

        // Splitting the application (keeping part of it on B) is refused:
        // the kept part would be re-applied money that never existed. The
        // checklist says so too.
        $checklist = $this->actingAs($this->user)->getJson(route('backoffice.inscriptions.payments', $feeB->inscription))->json('payments');
        $this->assertFalse(collect($checklist)->firstWhere('id', $A1->id)['splittable']);
        try {
            app(ConvertirEncaissementsEnAvance::class)->handle($feeB->inscription, [$A1->id], [$A1->id => 400.0]);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('montants.'.$A1->id, $e->errors());
        }

        // Reconvert the application in full: its money is the rejected cheque's.
        app(ConvertirEncaissementsEnAvance::class)->handle($feeB->inscription, [$A1->id]);

        $row = collect($this->avancesTab('restant')['encaissements']['data'])->firstWhere('id', $A1->id);
        $this->assertNotNull($row);
        $this->assertFalse($row['applicable'], 'A detached application of a rejected cheque must not be offered for application.');
        $this->assertTrue($row['chequeRejete']);

        try {
            app(AppliquerAvance::class)->handle($A1->fresh(), $feeC, 1000.0);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('avance', $e->errors());
        }
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeC->fresh()->statut);
    }

    public function test_refunding_a_reconverted_application_of_a_rejected_cheque_reverses_the_cheque_account_not_the_till(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$insA, $feeA] = $this->inscriptionWithFee($student, 'Frais A');
        [$insB, $feeB] = $this->inscriptionWithFee($student, 'Frais B');
        $cheque = Cheque::create([
            'reference' => 'CHQ-'.fake()->unique()->numerify('#####'), 'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id, 'numero_cheque' => fake()->unique()->numerify('CHQ#####'),
            'montant' => '1000.00', 'date_reception' => '2025-09-18', 'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => Cheque::STATUT_EN_POSSESSION, 'etablissement_id' => $this->centre->id,
            'agent_id' => $this->user->employee->id,
        ]);
        $R = $this->pay($student, $insA, $feeA, '1000.00', ['methode' => Encaissement::METHODE_CHEQUE, 'cheque_id' => $cheque->id]);
        $compteCheque = $R->caisse;
        $this->assertTrue($compteCheque->isCompteMethode());

        app(ConvertirEncaissementsEnAvance::class)->handle($insA, [$R->id]);
        $A1 = app(AppliquerAvance::class)->handle($R->fresh(), $feeB, 1000.0);
        $cheque->forceFill(['statut' => Cheque::STATUT_REJETE])->save();
        app(ConvertirEncaissementsEnAvance::class)->handle($insB, [$A1->id]);

        $till = $this->till();
        $tillAvant = (string) $till->fresh()->solde;
        $compteAvant = (float) $compteCheque->fresh()->solde;

        $this->actingAs($this->user)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $A1->id,
            'montant' => '1000.00',
            'date_remboursement' => '2025-10-01',
            'motif' => 'Chèque rejeté',
        ])->assertSessionHasNoErrors();

        $remboursement = Remboursement::query()->where('beneficiaire_id', $student->id)->sole();
        // THE HOLE: this used to debit the cashier's physical till.
        $this->assertSame($compteCheque->id, $remboursement->caisse_id);
        $this->assertSame($tillAvant, (string) $till->fresh()->solde);
        $this->assertEqualsWithDelta($compteAvant - 1000, (float) $compteCheque->fresh()->solde, 0.001);

        $this->artisan('caisse:verifier-coherence', ['--strict' => true])->assertExitCode(0);
    }
}
