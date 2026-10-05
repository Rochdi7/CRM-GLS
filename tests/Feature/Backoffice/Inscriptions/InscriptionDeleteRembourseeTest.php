<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Domain\Finance\Actions\AnnulerRemboursement;
use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
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
use Tests\TestCase;

/**
 * Supprimer une inscription dont tout l'argent a été remboursé (04/10/2026,
 * `Registrations\Actions\SupprimerInscription`). Le paiement et son
 * remboursement restent sur la fiche de l'étudiant ; aucune caisse ne bouge.
 */
final class InscriptionDeleteRembourseeTest extends TestCase
{
    use RefreshDatabase;

    private Employee $agent;

    private Caisse $caisse;

    private Inscription $inscription;

    private Encaissement $paiement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $centre = Etablissement::factory()->create();
        $annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $centre->id]);
        $this->caisse = $this->agent->till()->firstOrFail();
        Caisse::query()->whereKey($this->caisse->id)->update(['solde' => 5000]);

        $student = Student::factory()->create(['etablissement_id' => $centre->id]);
        $this->inscription = Inscription::create([
            'reference' => 'INS-RMB',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-09-01',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $this->inscription->id,
            'nom' => 'Frais de Septembre',
            'montant_initial' => 1200,
            'montant' => 1200,
            'date_echeance' => '2026-09-06',
            'statut' => InscriptionFee::STATUT_PAYE,
        ]);
        $this->paiement = Encaissement::create([
            'reference' => 'ENC-RMB',
            'etablissement_id' => $centre->id,
            'student_id' => $student->id,
            'inscription_fee_id' => $fee->id,
            'montant' => 1200,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-09-02',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
    }

    private function rembourser(float $montant): Remboursement
    {
        return app(EnregistrerRemboursement::class)->handle([
            'beneficiaire_id' => $this->paiement->student_id,
            'encaissement_id' => $this->paiement->id,
            'caisse_id' => $this->caisse->id,
            'montant' => $montant,
            'date_remboursement' => '2026-09-08',
            'motif' => 'Inscription annulée',
        ], $this->agent);
    }

    public function test_a_fully_refunded_registration_is_deleted_and_the_money_stays_on_the_student(): void
    {
        $remboursement = $this->rembourser(1200);
        $soldeAvant = (float) $this->caisse->fresh()->solde;

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull(Inscription::find($this->inscription->id));

        $paiement = $this->paiement->fresh();
        $this->assertNotNull($paiement, 'Le paiement n’est jamais supprimé.');
        $this->assertNull($paiement->inscription_fee_id);
        $this->assertSame($this->inscription->student_id, $paiement->student_id);
        $this->assertSame('1200.00', (string) $paiement->montant);
        $this->assertSame($this->caisse->id, $paiement->caisse_id);

        $remboursement->refresh();
        $this->assertSame($paiement->id, $remboursement->encaissement_id);
        $this->assertSame($this->inscription->student_id, $remboursement->beneficiaire_id);

        $this->assertSame($soldeAvant, (float) $this->caisse->fresh()->solde, 'Aucune caisse ne bouge.');
    }

    public function test_the_deleted_registration_stays_in_the_students_history_with_its_payment_and_refund(): void
    {
        $remboursement = $this->rembourser(1200);

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasNoErrors();

        $this->get(route('backoffice.students.show', $this->inscription->student_id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('student.inscriptionsSupprimees', 1)
                ->where('student.inscriptionsSupprimees.0.reference', 'INS-RMB')
                ->where('student.inscriptionsSupprimees.0.anneeScolaire', '2026/2027')
                ->where('student.inscriptionsSupprimees.0.paiements.0.reference', 'ENC-RMB')
                ->where('student.inscriptionsSupprimees.0.paiements.0.rembourse', '1200.00')
                ->where('student.inscriptionsSupprimees.0.paiements.0.remboursements.0', $remboursement->reference)
                ->where('student.paiements.0.reference', 'ENC-RMB'));
    }

    public function test_a_registration_without_payment_is_deleted_and_an_unlinked_refund_stays_on_the_student(): void
    {
        // No payment on the registration at all — only a refund of the
        // student that is not tied to any payment.
        $this->paiement->update(['inscription_fee_id' => null]);
        $remboursement = app(EnregistrerRemboursement::class)->handle([
            'beneficiaire_id' => $this->inscription->student_id,
            'encaissement_id' => null,
            'caisse_id' => $this->caisse->id,
            'montant' => 300,
            'date_remboursement' => '2026-09-08',
            'motif' => 'Geste commercial',
        ], $this->agent);

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull(Inscription::find($this->inscription->id));
        $remboursement->refresh();
        $this->assertSame($this->inscription->student_id, $remboursement->beneficiaire_id);
        $this->assertSame('300.00', (string) $remboursement->montant);
    }

    public function test_a_partially_refunded_registration_cannot_be_deleted(): void
    {
        $this->rembourser(500);

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasErrors('delete');

        $this->assertNotNull(Inscription::find($this->inscription->id));
        $this->assertNotNull($this->paiement->fresh()->inscription_fee_id);
    }

    public function test_a_cancelled_refund_does_not_count(): void
    {
        app(AnnulerRemboursement::class)->handle($this->rembourser(1200), 'Erreur de saisie');

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasErrors('delete');

        $this->assertNotNull(Inscription::find($this->inscription->id));
    }

    public function test_an_unrefunded_registration_still_cannot_be_deleted(): void
    {
        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasErrors('delete');

        $this->assertNotNull(Inscription::find($this->inscription->id));
    }
}
