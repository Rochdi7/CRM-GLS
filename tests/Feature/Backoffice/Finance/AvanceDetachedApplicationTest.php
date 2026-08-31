<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
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
 * Reported 31/08/2026: after a paid fee was removed from an inscription the
 * money became an avance that showed NOWHERE and could not be re-applied.
 *
 * Cause: an "apply" row keeps `applied_from_encaissement_id` forever, so once
 * ConvertirEncaissementsEnAvance detached it from its fee the SAME dirhams
 * were counted twice — still "used" on the parent avance (restant = 0, so the
 * parent dropped out of the « Solde restant » filter) AND available on the
 * detached child, which the Encaissements tab excludes as an application row.
 */
final class AvanceDetachedApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_detached_application_gives_its_money_back_to_the_parent_avance(): void
    {
        $centre = Etablissement::factory()->create();
        $annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $student = Student::factory()->create(['etablissement_id' => $centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $centre->id]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $centre->id]);
        $group = Group::factory()->create([
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);

        $inscription = Inscription::create([
            'reference' => 'INS-AV1', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $centre->id, 'annee_scolaire_id' => $annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => 1000,
        ]);

        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais', 'montant' => 1000,
            'date_echeance' => '2025-10-01', 'statut' => 'Non payé',
        ]);

        // The avance the student paid up front.
        $avance = Encaissement::create([
            'reference' => 'ENC-AV', 'student_id' => $student->id, 'inscription_fee_id' => null,
            'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 1000, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
        ]);

        // The application row AppliquerAvance writes when it is spent.
        $application = Encaissement::create([
            'reference' => 'ENC-AP', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 1000, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
            'applied_from_encaissement_id' => $avance->id,
        ]);

        $this->assertSame(0.0, $avance->fresh()->montantRestant(), 'While applied, the avance is fully used.');

        // The fee is removed from the inscription: its money goes back to
        // being an unallocated avance.
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$application->id]);

        // The detached row is itself a re-applicable avance…
        $application = $application->fresh();
        $this->assertTrue($application->isAvance());
        $this->assertSame(1000.0, $application->montantRestant());

        // …so the parent must NOT still count it as spent, or the same 1 000 DH
        // would be both used and available.
        $this->assertSame(0.0, $avance->fresh()->montantRestant());
    }
}
