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
use App\Models\InscriptionHistorique;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Modification du groupe" — the in-place sibling of "Changement de groupe":
 * corrects group_id on the SAME inscription (no historique snapshot, no
 * successor row), so every fee line and payment stays attached and follows
 * the student into the new group.
 */
final class InscriptionModifyGroupTest extends TestCase
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

        return $user->fresh();
    }

    private function makeGroup(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function activeInscription(Group $group, string $reference = 'INS-MG1'): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        return Inscription::create([
            'reference' => $reference, 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
    }

    public function test_it_moves_the_inscription_in_place_with_no_archive_and_no_new_row(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);

        $this->post(route('backoffice.inscriptions.modify-group', $inscription), [
            'new_group_id' => $newGroup->id,
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $fresh = $inscription->fresh();
        $this->assertSame($newGroup->id, $fresh->group_id);
        $this->assertSame(Inscription::STATUT_ACTIVE, $fresh->statut);
        $this->assertSame($newGroup->etablissement_id, $fresh->etablissement_id);
        $this->assertSame($newGroup->annee_scolaire_id, $fresh->annee_scolaire_id);
        // In-place edit: no snapshot, no successor.
        $this->assertSame(0, InscriptionHistorique::count());
        $this->assertSame(1, Inscription::count());
    }

    public function test_paid_fees_and_their_payments_stay_attached_and_follow(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);

        $paidFee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais payé',
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2025-12-01', 'statut' => InscriptionFee::STATUT_PAYE,
        ]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-MG1', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $paidFee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 300, 'methode' => 'Espèces', 'date_paiement' => '2025-12-01',
        ]);

        $this->post(route('backoffice.inscriptions.modify-group', $inscription), [
            'new_group_id' => $newGroup->id,
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        // Nothing money-side was rewritten: the fee still belongs to the
        // same inscription (now in the new group) and the payment still
        // funds that fee — it never became an avance.
        $this->assertSame($inscription->id, $paidFee->fresh()->inscription_id);
        $this->assertSame($paidFee->id, $encaissement->fresh()->inscription_fee_id);
        $this->assertFalse($encaissement->fresh()->isAvance());
        $this->assertSame($newGroup->id, $inscription->fresh()->group_id);
    }

    public function test_only_an_active_inscription_can_be_moved(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);
        $inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        $this->post(route('backoffice.inscriptions.modify-group', $inscription), [
            'new_group_id' => $newGroup->id,
        ])->assertSessionHasErrors('inscription');

        $this->assertSame($oldGroup->id, $inscription->fresh()->group_id);
    }

    public function test_the_target_group_may_already_be_running(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $startedGroup = $this->makeGroup();
        $startedGroup->update(['statut' => Group::STATUT_EN_FORMATION]);
        $inscription = $this->activeInscription($oldGroup);

        // Joining a running group is a normal enrollment — only the CURRENT
        // group must still be « En inscription ».
        $this->post(route('backoffice.inscriptions.modify-group', $inscription), [
            'new_group_id' => $startedGroup->id,
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertSame($startedGroup->id, $inscription->fresh()->group_id);
    }

    public function test_an_inscription_whose_group_already_started_must_use_change_group(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $oldGroup->update(['statut' => Group::STATUT_EN_FORMATION]);
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);

        $this->post(route('backoffice.inscriptions.modify-group', $inscription), [
            'new_group_id' => $newGroup->id,
        ])->assertSessionHasErrors('new_group_id');

        $this->assertSame($oldGroup->id, $inscription->fresh()->group_id);
    }

    public function test_moving_to_the_same_group_is_refused(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $group = $this->makeGroup();
        $inscription = $this->activeInscription($group);

        $this->post(route('backoffice.inscriptions.modify-group', $inscription), [
            'new_group_id' => $group->id,
        ])->assertSessionHasErrors('new_group_id');
    }

    public function test_user_without_change_group_permission_is_forbidden(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);

        $this->post(route('backoffice.inscriptions.modify-group', $inscription), [
            'new_group_id' => $newGroup->id,
        ])->assertForbidden();

        $this->assertSame($oldGroup->id, $inscription->fresh()->group_id);
    }

    public function test_center_scoped_user_cannot_move_another_centers_inscription(): void
    {
        $centerB = Etablissement::factory()->create();
        $studentB = Student::factory()->create(['etablissement_id' => $centerB->id]);
        $groupB = Group::factory()->create(['etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscriptionB = Inscription::create([
            'reference' => 'INS-MG-B', 'student_id' => $studentB->id, 'group_id' => $groupB->id,
            'etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
        $newGroupB = Group::factory()->create(['etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id]);

        $lockedUser = User::factory()->create();
        $lockedUser->givePermissionTo('registrations.view', 'registrations.change-group');
        $lockedUser->employee()->save(Employee::factory()->make(['etablissement_id' => $this->centre->id]));
        $this->actingAs($lockedUser->fresh());

        $this->post(route('backoffice.inscriptions.modify-group', $inscriptionB), [
            'new_group_id' => $newGroupB->id,
        ])->assertForbidden();

        $this->assertSame($groupB->id, $inscriptionB->fresh()->group_id);
    }
}
