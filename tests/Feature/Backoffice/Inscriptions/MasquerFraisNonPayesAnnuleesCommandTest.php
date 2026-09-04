<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `inscriptions:masquer-frais-non-payes-annulees` — le rattrapage du dû
 * fantôme porté par les inscriptions DÉJÀ annulées.
 *
 * Le contrat tient en deux points : une ligne n'ayant jamais rien reçu est
 * masquée (pas supprimée) ; une ligne ayant reçu le moindre dirham n'est
 * jamais touchée, même partiellement payée.
 */
final class MasquerFraisNonPayesAnnuleesCommandTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function makeInscription(string $statut, string $reference): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        return Inscription::create([
            'reference' => $reference,
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => '2025-09-15',
            'montant_total' => 3000,
        ]);
    }

    private function fee(Inscription $inscription, string $nom, string $statut): InscriptionFee
    {
        return $inscription->fees()->create([
            'nom' => $nom, 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-06-01', 'statut' => $statut,
        ]);
    }

    private function payer(Inscription $inscription, InscriptionFee $fee, float $montant, string $ref): Encaissement
    {
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        return Encaissement::create([
            'reference' => $ref,
            'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id,
            'agent_id' => $agent->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-01-05',
        ]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $inscription = $this->makeInscription(Inscription::STATUT_ANNULEE, 'INS-DRY-1');
        $fee = $this->fee($inscription, 'Frais de juin', InscriptionFee::STATUT_NON_PAYE);

        $this->artisan('inscriptions:masquer-frais-non-payes-annulees')->assertSuccessful();

        $this->assertNull($fee->fresh()->masque_le);
    }

    public function test_apply_hides_the_unpaid_fee_without_deleting_it(): void
    {
        $inscription = $this->makeInscription(Inscription::STATUT_ANNULEE, 'INS-APPLY-1');
        $fee = $this->fee($inscription, 'Frais de juin', InscriptionFee::STATUT_NON_PAYE);

        $this->artisan('inscriptions:masquer-frais-non-payes-annulees', ['--apply' => true])
            ->assertSuccessful();

        $fresh = $fee->fresh();
        $this->assertNotNull($fresh, 'La ligne ne doit jamais être supprimée.');
        $this->assertNotNull($fresh->masque_le);
        $this->assertSame(InscriptionFee::MASQUE_ORIGINE_MANUEL, $fresh->masque_origine);
        $this->assertNull($inscription->fresh()->montant_total);
    }

    public function test_a_fee_that_received_money_is_never_touched(): void
    {
        $inscription = $this->makeInscription(Inscription::STATUT_ANNULEE, 'INS-MONEY-1');
        $partiel = $this->fee($inscription, 'Frais partiel', InscriptionFee::STATUT_PAYE_PARTIELLEMENT);
        $paye = $this->fee($inscription, 'Frais payé', InscriptionFee::STATUT_PAYE);
        $this->payer($inscription, $partiel, 400, 'ENC-MASQ-1');
        $this->payer($inscription, $paye, 1000, 'ENC-MASQ-2');

        $this->artisan('inscriptions:masquer-frais-non-payes-annulees', ['--apply' => true])
            ->assertSuccessful();

        $this->assertNull($partiel->fresh()->masque_le);
        $this->assertNull($paye->fresh()->masque_le);
        // Aucun encaissement détaché : la commande ne déplace pas d'argent.
        $this->assertDatabaseHas('encaissements', [
            'reference' => 'ENC-MASQ-1', 'inscription_fee_id' => $partiel->id,
        ]);
    }

    public function test_an_active_inscription_is_left_alone(): void
    {
        $inscription = $this->makeInscription(Inscription::STATUT_ACTIVE, 'INS-ACTIVE-1');
        $fee = $this->fee($inscription, 'Frais de juin', InscriptionFee::STATUT_NON_PAYE);

        $this->artisan('inscriptions:masquer-frais-non-payes-annulees', ['--apply' => true])
            ->assertSuccessful();

        $this->assertNull($fee->fresh()->masque_le);
    }

    public function test_it_is_idempotent(): void
    {
        $inscription = $this->makeInscription(Inscription::STATUT_ANNULEE, 'INS-IDEM-1');
        $fee = $this->fee($inscription, 'Frais de juin', InscriptionFee::STATUT_NON_PAYE);

        $this->artisan('inscriptions:masquer-frais-non-payes-annulees', ['--apply' => true]);
        $premier = $fee->fresh()->masque_le;

        $this->artisan('inscriptions:masquer-frais-non-payes-annulees', ['--apply' => true])
            ->expectsOutputToContain('Rien à faire.')
            ->assertSuccessful();

        $this->assertEquals($premier, $fee->fresh()->masque_le);
    }
}
