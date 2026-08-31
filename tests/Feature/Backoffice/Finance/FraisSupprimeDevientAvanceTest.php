<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Queries\GetEncaissementsList;
use App\Domain\Registrations\Actions\MettreAJourFraisInscription;
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
use Tests\TestCase;

/**
 * Reported 31/08/2026: a fee added by mistake on a student who had ALREADY
 * paid could not be removed at all — the delete hit the encaissements
 * FK-restrict and the whole edit was refused with « ce frais a des paiements ».
 * The money had no way back into the avance pool.
 *
 * Removing a paid line must release its payments as unallocated avances
 * first — the same release the group flow performs (RetirerFraisGroupe) —
 * so the money stays on the student and can be applied to another fee.
 */
final class FraisSupprimeDevientAvanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_removing_a_paid_fee_turns_its_payments_into_a_reapplicable_avance(): void
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
            'reference' => 'INS-AVX', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $centre->id, 'annee_scolaire_id' => $annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => 1300,
        ]);

        $garde = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Formation', 'montant' => 1000,
            'montant_initial' => 1000, 'date_echeance' => '2025-10-01', 'statut' => 'Non payé',
        ]);

        // The fee added by mistake — and already paid.
        $aRetirer = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais en trop', 'montant' => 300,
            'montant_initial' => 300, 'date_echeance' => '2025-10-01', 'statut' => 'Payé',
        ]);

        $paiement = Encaissement::create([
            'reference' => 'ENC-AVX', 'student_id' => $student->id, 'inscription_fee_id' => $aRetirer->id,
            'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 300, 'methode' => 'Espèces', 'date_paiement' => '2025-10-01',
        ]);

        // Re-submit the fee table WITHOUT the wrongly-added line.
        app(MettreAJourFraisInscription::class)->handle($inscription, [[
            'id' => $garde->id,
            'nom' => 'Formation',
            'montant_initial' => 1000.0,
            'date_echeance' => '2025-10-01',
        ]]);

        // The line is gone…
        $this->assertNull(InscriptionFee::find($aRetirer->id));

        // …the payment row survives (money records are append-only)…
        $paiement = $paiement->fresh();
        $this->assertNotNull($paiement);

        // …and its money is now an unallocated, re-applicable avance.
        $this->assertNull($paiement->inscription_fee_id);
        $this->assertTrue($paiement->isAvance());
        $this->assertSame(300.0, $paiement->montantRestant());

        // It must be reachable from BOTH tabs: listed on « Encaissements »
        // flagged as an avance (money received, not yet allocated), and on
        // the « Avances » tab as applicable — that is the whole point of
        // releasing it rather than blocking the edit.
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $user = $user->fresh();

        $encaissements = app(GetEncaissementsList::class)($user, view: '');
        $ligne = collect($encaissements['data']->items())->firstWhere('id', $paiement->id);
        $this->assertNotNull($ligne, 'The released money must stay on the Encaissements tab.');
        $this->assertTrue($ligne['isAvance']);
        $this->assertNull($ligne['feeNom']);

        $avances = app(GetEncaissementsList::class)($user, view: 'avance');
        $ligneAvance = collect($avances['data']->items())->firstWhere('id', $paiement->id);
        $this->assertNotNull($ligneAvance, 'The released money must show on the Avances tab.');
        $this->assertTrue($ligneAvance['applicable'], 'It must be applicable to another fee.');
        $this->assertSame('300.00', $ligneAvance['montantRestant']);
    }
}
