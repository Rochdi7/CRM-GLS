<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
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
 * "Changement de groupe" — archives the current inscription
 * (inscriptions_historique snapshot, statut -> Changement) and creates a new
 * Active one in the target group, billed from that group's catalog fees
 * (mirrors InscriptionController::store()'s own fee-line creation).
 */
final class InscriptionChangeGroupTest extends TestCase
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

    private function groupWithFee(float $montant = 500): Group
    {
        $group = $this->makeGroup();
        $frais = Frais::create(['nom' => 'Frais de rentrée', 'statut' => 'Actif']);
        $group->frais()->attach([$frais->id => ['montant' => $montant, 'date_echeance' => '2026-01-01']]);

        return $group;
    }

    private function activeInscription(Group $group): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        return Inscription::create([
            'reference' => 'INS-CG1', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
    }

    public function test_it_archives_the_old_inscription_and_creates_a_new_one_in_the_target_group(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->groupWithFee(500);
        $inscription = $this->activeInscription($oldGroup);

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $newGroup->id,
            'date_fin' => '2026-01-10',
            'date_debut' => '2026-01-11',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $old = $inscription->fresh();
        $this->assertSame(Inscription::STATUT_CHANGEMENT, $old->statut);
        $this->assertSame('2026-01-10', $old->date_fin->toDateString());

        $historique = InscriptionHistorique::where('inscription_id', $inscription->id)->first();
        $this->assertNotNull($historique);
        $this->assertSame($oldGroup->id, $historique->group_id);

        $newInscription = Inscription::where('group_id', $newGroup->id)->first();
        $this->assertNotNull($newInscription);
        $this->assertSame(Inscription::STATUT_ACTIVE, $newInscription->statut);
        $this->assertSame('2026-01-11', $newInscription->date_debut->toDateString());
        $this->assertSame($inscription->student_id, $newInscription->student_id);
        $this->assertSame('500.00', (string) $newInscription->fees()->first()->montant);
        $this->assertSame($newInscription->id, $historique->fresh()->new_inscription_id);
    }

    public function test_only_an_active_inscription_can_change_group(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);
        $inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $newGroup->id,
            'date_fin' => '2026-01-10',
            'date_debut' => '2026-01-11',
        ])->assertSessionHasErrors('inscription');

        $this->assertSame(Inscription::STATUT_ANNULEE, $inscription->fresh()->statut);
    }

    public function test_unpaid_fees_scope_all_removes_every_unpaid_fee_and_frees_its_payment_as_an_avance(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);

        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais impayé',
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2025-12-01', 'statut' => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
        ]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-CG1', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 100, 'methode' => 'Espèces', 'date_paiement' => '2025-12-01',
        ]);

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $newGroup->id,
            'date_fin' => '2026-01-10',
            'date_debut' => '2026-01-11',
            'unpaid_fees_scope' => 'all',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull(InscriptionFee::find($fee->id));
        $freed = $encaissement->fresh();
        $this->assertNull($freed->inscription_fee_id);
        $this->assertTrue($freed->isAvance());
    }

    public function test_unpaid_fees_scope_overdue_only_keeps_fees_due_before_the_end_date(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);

        $overdueFee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais échu avant la fin',
            'montant_initial' => 200, 'montant' => 200,
            'date_echeance' => '2025-12-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $futureFee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais après la fin',
            'montant_initial' => 200, 'montant' => 200,
            'date_echeance' => '2026-03-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $newGroup->id,
            'date_fin' => '2026-01-10',
            'date_debut' => '2026-01-11',
            'unpaid_fees_scope' => 'overdue_only',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNotNull(InscriptionFee::find($overdueFee->id));
        $this->assertNull(InscriptionFee::find($futureFee->id));
    }

    public function test_a_fully_paid_fee_is_never_removed(): void
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

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $newGroup->id,
            'date_fin' => '2026-01-10',
            'date_debut' => '2026-01-11',
            'unpaid_fees_scope' => 'all',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNotNull(InscriptionFee::find($paidFee->id));
    }

    public function test_user_without_change_group_permission_is_forbidden(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $oldGroup = $this->makeGroup();
        $newGroup = $this->makeGroup();
        $inscription = $this->activeInscription($oldGroup);

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $newGroup->id,
            'date_fin' => '2026-01-10',
            'date_debut' => '2026-01-11',
        ])->assertForbidden();

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }

    public function test_center_scoped_user_cannot_change_group_of_another_centers_inscription(): void
    {
        $centerB = Etablissement::factory()->create();
        $studentB = Student::factory()->create(['etablissement_id' => $centerB->id]);
        $groupB = Group::factory()->create(['etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscriptionB = Inscription::create([
            'reference' => 'INS-CG-B', 'student_id' => $studentB->id, 'group_id' => $groupB->id,
            'etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
        $newGroupB = Group::factory()->create(['etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id]);

        $lockedUser = User::factory()->create();
        $lockedUser->givePermissionTo('registrations.view', 'registrations.change-group');
        $lockedUser->employee()->save(Employee::factory()->make(['etablissement_id' => $this->centre->id]));
        $this->actingAs($lockedUser->fresh());

        $this->post(route('backoffice.inscriptions.change-group', $inscriptionB), [
            'new_group_id' => $newGroupB->id,
            'date_fin' => '2026-01-10',
            'date_debut' => '2026-01-11',
        ])->assertForbidden();
    }
}
