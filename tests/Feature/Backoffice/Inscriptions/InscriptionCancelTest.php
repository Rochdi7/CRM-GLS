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
use App\Models\MotifAnnulation;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Annuler l'inscription" — the reason is mandatory, the end date is
 * recorded, and the unpaid fee lines are disposed of according to the chosen
 * scope (the same two scopes the group-change flow offers).
 */
final class InscriptionCancelTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        MotifAnnulation::create(['nom' => 'Non-paiement', 'statut' => MotifAnnulation::STATUT_ACTIF]);
        MotifAnnulation::create([
            'nom' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
            'statut' => MotifAnnulation::STATUT_ACTIF,
            'is_system' => true,
        ]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function makeGroup(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function makeInscription(string $reference = 'INS-CANCEL-1'): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        return Inscription::create([
            'reference' => $reference,
            'student_id' => $student->id,
            'group_id' => $this->makeGroup()->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-15',
            'montant_total' => 3000,
        ]);
    }

    public function test_cancelling_records_the_reason_and_the_end_date(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $fresh = $inscription->fresh();
        $this->assertSame(Inscription::STATUT_ANNULEE, $fresh->statut);
        $this->assertSame('Non-paiement', $fresh->motif_annulation);
        $this->assertSame('2026-03-01', $fresh->date_fin->toDateString());
    }

    public function test_the_reason_is_required(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'date_fin' => '2026-03-01',
        ])->assertSessionHasErrors('motif_annulation');

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }

    public function test_the_reason_must_come_from_the_active_catalog(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Raison inventée',
            'date_fin' => '2026-03-01',
        ])->assertSessionHasErrors('motif_annulation');

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }

    /**
     * « Changement de groupe » is the system reason ChangerGroupeInscription
     * writes; picking it here would claim a group change that never happened.
     */
    public function test_the_group_change_system_reason_is_not_selectable(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
            'date_fin' => '2026-03-01',
        ])->assertSessionHasErrors('motif_annulation');

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }

    public function test_an_inactive_reason_is_refused(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        MotifAnnulation::create(['nom' => 'Ancienne raison', 'statut' => MotifAnnulation::STATUT_INACTIF]);
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Ancienne raison',
            'date_fin' => '2026-03-01',
        ])->assertSessionHasErrors('motif_annulation');
    }

    public function test_the_end_date_is_required(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
        ])->assertSessionHasErrors('date_fin');
    }

    public function test_scope_all_removes_every_unpaid_fee_and_keeps_the_paid_ones(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $unpaidBefore = $inscription->fees()->create([
            'nom' => 'Frais de janvier', 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-01-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $unpaidAfter = $inscription->fees()->create([
            'nom' => 'Frais de juin', 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-06-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $paid = $inscription->fees()->create([
            'nom' => "Frais d'inscription", 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-09-15', 'statut' => InscriptionFee::STATUT_PAYE,
        ]);

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
            'unpaid_fees_scope' => 'all',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        // Masqués, jamais supprimés : la ligne et son historique restent.
        $this->assertNotNull($unpaidBefore->fresh()->masque_le);
        $this->assertNotNull($unpaidAfter->fresh()->masque_le);
        $this->assertNull($paid->fresh()->masque_le);
        // The stored total follows what is actually still owed.
        $this->assertSame('1000.00', (string) $inscription->fresh()->montant_total);
    }

    /**
     * A fee that fell due while the student was still enrolled was genuinely
     * earned and stays owed; only what falls due after the end date goes.
     */
    public function test_scope_overdue_only_removes_fees_due_after_the_end_date(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $before = $inscription->fees()->create([
            'nom' => 'Frais de janvier', 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-01-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $after = $inscription->fees()->create([
            'nom' => 'Frais de juin', 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-06-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
            'unpaid_fees_scope' => 'overdue_only',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull($before->fresh()->masque_le);
        $this->assertNotNull($after->fresh()->masque_le);
    }

    public function test_no_scope_leaves_every_fee_alone(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $fee = $inscription->fees()->create([
            'nom' => 'Frais de juin', 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-06-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull($fee->fresh()->masque_le);
    }

    /**
     * Une ligne ayant reçu le moindre dirham n'est jamais retirée (décidé le
     * 04/09/2026) : elle reste due pour son reste, et son encaissement reste
     * attaché. C'est un remboursement, pas une suppression de créance, qui
     * rendrait cet argent à l'étudiant.
     */
    public function test_a_partially_paid_fee_is_never_removed(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $fee = $inscription->fees()->create([
            'nom' => 'Frais de juin', 'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-06-01', 'statut' => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
        ]);

        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-CANCEL-1', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 400, 'methode' => 'Espèces', 'date_paiement' => '2026-01-05',
        ]);

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
            'unpaid_fees_scope' => 'all',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull($fee->fresh()->masque_le);
        $this->assertDatabaseHas('encaissements', [
            'id' => $encaissement->id,
            'inscription_fee_id' => $fee->id,
            'montant' => 400,
        ]);
    }

    public function test_an_already_cancelled_inscription_cannot_be_cancelled_again(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();
        $inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
        ])->assertSessionHasErrors('statut');
    }

    public function test_reactivating_clears_the_cancellation_reason(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
        ]);

        $this->assertSame('Non-paiement', $inscription->fresh()->motif_annulation);

        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Active',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $fresh = $inscription->fresh();
        $this->assertSame(Inscription::STATUT_ACTIVE, $fresh->statut);
        $this->assertNull($fresh->motif_annulation);
    }

    public function test_user_without_update_permission_cannot_cancel(): void
    {
        $this->actingAs($this->userWith('registrations.view'));
        $inscription = $this->makeInscription();

        $this->post(route('backoffice.inscriptions.cancel', $inscription), [
            'motif_annulation' => 'Non-paiement',
            'date_fin' => '2026-03-01',
        ])->assertForbidden();

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }
}
