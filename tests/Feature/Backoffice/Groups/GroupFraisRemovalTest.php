<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Removing a fee from a group (« Modifier le groupe » → trash icon on a
 * « Frais du groupe » row) and its cascade to every inscription of the
 * group — Domain\Groups\Actions\RetirerFraisGroupe.
 *
 * The invariants asserted here are money rules (CLAUDE.md §11), not UI
 * niceties:
 *
 *  - nothing is DELETED — the inscription fee line is hidden (masque_le) and
 *    its payment rows survive intact;
 *  - money already collected on the removed fee comes back as a re-applicable
 *    AVANCE (inscription_fee_id detached), never stranded on a hidden line;
 *  - caisses.solde does NOT move — the cash never left the till, only its
 *    allocation changed.
 */
final class GroupFraisRemovalTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Group $group;

    private Caisse $caisse;

    private Employee $agent;

    private Frais $fraisAvril;

    private Frais $fraisInscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = $this->agent->till()->firstOrFail();

        $this->fraisAvril = Frais::create(['nom' => "Frais d'Avril", 'montant_defaut' => 1300]);
        $this->fraisInscription = Frais::create(['nom' => "Frais d'inscription", 'montant_defaut' => 300]);

        $this->group->frais()->sync([
            $this->fraisAvril->id => ['montant' => 1300, 'date_echeance' => '2026-04-01'],
            $this->fraisInscription->id => ['montant' => 300, 'date_echeance' => '2026-01-01'],
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function enrol(string $nom): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id, 'nom' => $nom]);

        return Inscription::create([
            'reference' => 'INS-RMV-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-01-10',
            'montant_total' => 1600,
        ]);
    }

    private function addFee(Inscription $inscription, Frais $frais, float $montant, float $paye = 0): InscriptionFee
    {
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $frais->id,
            'nom' => $frais->nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => '2026-03-01',
        ]);

        if ($paye > 0) {
            Encaissement::create([
                'reference' => 'ENC-RMV-'.$fee->id,
                'student_id' => $inscription->student_id,
                'etablissement_id' => $this->centre->id,
                'inscription_fee_id' => $fee->id,
                'montant' => $paye,
                'methode' => Encaissement::METHODE_ESPECES,
                'date_paiement' => '2026-01-15',
                'caisse_id' => $this->caisse->id,
                'agent_id' => $this->agent->id,
            ]);
        }

        return $fee;
    }

    public function test_removing_a_group_fee_hides_it_on_every_inscription_of_the_group(): void
    {
        $a = $this->enrol('Alaoui');
        $b = $this->enrol('Bennani');
        $feeA = $this->addFee($a, $this->fraisAvril, 1300);
        $feeB = $this->addFee($b, $this->fraisAvril, 1300);
        // A fee of ANOTHER catalog entry must be left completely alone.
        $autreA = $this->addFee($a, $this->fraisInscription, 300);

        $this->actingAs($this->superAdmin())
            ->delete("/backoffice/groups/{$this->group->id}/frais/{$this->fraisAvril->id}")
            ->assertOk();

        // The group no longer offers the fee — a future inscription in this
        // group will not receive it either.
        $this->assertDatabaseMissing('group_frais', [
            'group_id' => $this->group->id,
            'frais_id' => $this->fraisAvril->id,
        ]);

        // Hidden, NOT deleted — on every inscription of the group.
        $this->assertNotNull($feeA->fresh()->masque_le);
        $this->assertNotNull($feeB->fresh()->masque_le);
        $this->assertDatabaseHas('inscription_fees', ['id' => $feeA->id]);
        $this->assertDatabaseHas('inscription_fees', ['id' => $feeB->id]);

        // Untouched fee of another catalog entry.
        $this->assertNull($autreA->fresh()->masque_le);

        // montant_total drops to the still-visible fees only.
        $this->assertSame(300.0, (float) $a->fresh()->montant_total);
    }

    public function test_money_paid_on_a_removed_fee_comes_back_as_a_reapplicable_avance(): void
    {
        $inscription = $this->enrol('Cherkaoui');
        $fee = $this->addFee($inscription, $this->fraisAvril, 1300, paye: 800);
        $encaissement = Encaissement::query()->where('inscription_fee_id', $fee->id)->firstOrFail();
        $soldeAvant = (float) $this->caisse->fresh()->solde;

        $this->actingAs($this->superAdmin())
            ->delete("/backoffice/groups/{$this->group->id}/frais/{$this->fraisAvril->id}")
            ->assertOk();

        $encaissement->refresh();

        // Detached from its fee => it IS an avance again, and its full 800 DH
        // is available to be applied to another fee of the same student.
        $this->assertNull($encaissement->inscription_fee_id);
        $this->assertTrue($encaissement->isAvance());
        $this->assertSame(800.0, $encaissement->montantRestant());

        // The payment row itself is never deleted, and the till never moved:
        // the cash was already in the drawer, only its allocation changed.
        $this->assertDatabaseHas('encaissements', ['id' => $encaissement->id]);
        $this->assertSame($soldeAvant, (float) $this->caisse->fresh()->solde);
    }

    public function test_restoring_a_removed_fee_reattaches_it_and_unhides_every_line(): void
    {
        $inscription = $this->enrol('Drissi');
        $fee = $this->addFee($inscription, $this->fraisAvril, 1300, paye: 800);

        $user = $this->superAdmin();

        $this->actingAs($user)->delete("/backoffice/groups/{$this->group->id}/frais/{$this->fraisAvril->id}");
        $this->actingAs($user)->post("/backoffice/groups/{$this->group->id}/frais/{$this->fraisAvril->id}/restore")
            ->assertOk();

        $this->assertDatabaseHas('group_frais', [
            'group_id' => $this->group->id,
            'frais_id' => $this->fraisAvril->id,
        ]);
        $this->assertNull($fee->fresh()->masque_le);
        $this->assertSame(1300.0, (float) $inscription->fresh()->montant_total);

        // The freed money stays an avance — restoring a line must never
        // silently re-allocate money; that is AppliquerAvance's explicit,
        // journaled decision.
        $encaissement = Encaissement::query()->where('student_id', $inscription->student_id)->firstOrFail();
        $this->assertNull($encaissement->inscription_fee_id);
    }

    public function test_saving_the_group_after_a_removal_does_not_resurrect_the_fee(): void
    {
        $inscription = $this->enrol('El Amrani');
        $fee = $this->addFee($inscription, $this->fraisAvril, 1300);
        $user = $this->superAdmin();

        $this->actingAs($user)->delete("/backoffice/groups/{$this->group->id}/frais/{$this->fraisAvril->id}");

        // A plain edit-modal save afterwards must NOT re-add every catalog
        // fee (the old normalizedFraisLignes() behavior would have silently
        // undone the removal on the very next save).
        $this->actingAs($user)->put("/backoffice/groups/{$this->group->id}", [
            'nom' => $this->group->nom,
            'niveau' => $this->group->niveau,
            'enseignant_id' => $this->group->enseignant_id,
            'statut' => $this->group->statut,
            'date_debut_formation' => $this->group->date_debut_formation?->toDateString() ?? '2026-01-01',
            'date_fin_formation' => $this->group->date_fin_formation?->toDateString() ?? '2026-06-30',
            'fraisLignes' => [
                $this->fraisInscription->id => ['montant' => '300', 'date_echeance' => '2026-01-01'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('group_frais', [
            'group_id' => $this->group->id,
            'frais_id' => $this->fraisAvril->id,
        ]);
        $this->assertNotNull($fee->fresh()->masque_le);
    }

    public function test_a_refunded_payment_is_left_attached_and_does_not_abort_the_removal(): void
    {
        $inscription = $this->enrol('Fassi');
        $fee = $this->addFee($inscription, $this->fraisAvril, 1300, paye: 800);
        $encaissement = Encaissement::query()->where('inscription_fee_id', $fee->id)->firstOrFail();

        Remboursement::create([
            'reference' => 'RMB-RMV-1',
            'encaissement_id' => $encaissement->id,
            'beneficiaire_id' => $inscription->student_id,
            'etablissement_id' => $this->centre->id,
            'montant' => 800,
            'date_remboursement' => '2026-02-01',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete("/backoffice/groups/{$this->group->id}/frais/{$this->fraisAvril->id}")
            ->assertOk();

        // A refunded payment's money already LEFT the till — turning it into
        // an avance would hand the student money they were given back. It
        // keeps its fee, while the fee line is still hidden as asked.
        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
        $this->assertNotNull($fee->fresh()->masque_le);
    }
}
