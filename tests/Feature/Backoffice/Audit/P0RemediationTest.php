<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Audit;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit 27/08/2026 — P0 remediation: masked fees are never payable (R-01),
 * import batches are centre-scoped (SEC-05), group lookups are centre-scoped
 * (SEC-07), inscription statut/student are locked on update (CRUD-F4,
 * DB-07, DB-08).
 */
final class P0RemediationTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $marrakech;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
    }

    private function userIn(Etablissement $centre, string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    private function superAdmin(Etablissement $centre): User
    {
        $user = $this->userIn($centre, 'teacher');
        $user->assignRole(Role::SUPER_ADMIN);

        return $user->fresh();
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolled(Etablissement $centre, float $montant = 1500): array
    {
        $student = Student::factory()->create(['etablissement_id' => $centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    /** @return array<string, mixed> */
    private function paymentPayload(Student $student, Inscription $inscription, InscriptionFee $fee, string $montant = '500'): array
    {
        return [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ];
    }

    // --- R-01 masked fees -----------------------------------------------

    public function test_masked_fee_is_absent_from_the_unpaid_list_and_cannot_be_paid(): void
    {
        $user = $this->userIn($this->marrakech, 'director');
        [$student, $inscription, $fee] = $this->enrolled($this->marrakech);
        $fee->update(['masque_le' => now()]);
        $this->actingAs($user);

        $names = collect($this->get(route('backoffice.inscriptions.unpaid-fees', $inscription))->json('fees'))->pluck('id');
        $this->assertFalse($names->contains($fee->id));

        $till = $user->employee->caisses()->first();
        $before = (string) $till->solde;

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.encaissements.store'), $this->paymentPayload($student, $inscription, $fee))
            ->assertSessionHasErrors('payment_lines');

        $this->assertSame(0, Encaissement::count());
        $this->assertSame($before, (string) $till->fresh()->solde);
    }

    public function test_unmasked_fee_is_still_payable(): void
    {
        $user = $this->userIn($this->marrakech, 'director');
        [$student, $inscription, $fee] = $this->enrolled($this->marrakech);
        $this->actingAs($user);

        $this->post(route('backoffice.encaissements.store'), $this->paymentPayload($student, $inscription, $fee))
            ->assertRedirect(route('backoffice.encaissements.index'));

        $this->assertSame(1, Encaissement::count());
        $this->assertSame('500.00', (string) $user->employee->caisses()->first()->fresh()->solde);
    }

    // --- SEC-05 import batches ------------------------------------------

    private function batch(Etablissement $centre, Employee $by): ImportBatch
    {
        return ImportBatch::create([
            'module' => ImportBatch::MODULE_STUDENTS, 'original_filename' => 'x.xlsx',
            'etablissement_id' => $centre->id, 'annee_scolaire_id' => $this->annee->id,
            'status' => ImportBatch::STATUT_ANALYZED, 'created_by' => $by->id,
        ]);
    }

    public function test_director_cannot_commit_or_retry_a_batch_of_another_centre(): void
    {
        $director = $this->userIn($this->marrakech, 'director');
        $director->givePermissionTo('import.view', 'import.create');
        $rabatBatch = $this->batch($this->rabat, $this->userIn($this->rabat, 'director')->employee);
        $this->actingAs($director->fresh());

        $this->postJson(route('backoffice.import.students.commit', $rabatBatch), ['selected_row_ids' => [1]])->assertForbidden();
        $this->post(route('backoffice.import.students.retry-failed', $rabatBatch))->assertForbidden();
    }

    public function test_director_can_retry_own_centre_batch_and_super_admin_any(): void
    {
        $director = $this->userIn($this->marrakech, 'director');
        $director->givePermissionTo('import.view', 'import.create');
        $own = $this->batch($this->marrakech, $director->employee);
        $this->actingAs($director->fresh());
        $this->post(route('backoffice.import.students.retry-failed', $own))->assertRedirect();

        $rabatBatch = $this->batch($this->rabat, $director->employee);
        $this->actingAs($this->superAdmin($this->marrakech));
        $this->post(route('backoffice.import.students.retry-failed', $rabatBatch))->assertRedirect();
    }

    // --- SEC-07 group lookups -------------------------------------------

    public function test_group_fee_and_book_lookups_refuse_another_centre_group(): void
    {
        $user = $this->userIn($this->marrakech, 'director');
        $rabatGroup = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id]);
        $ownGroup = Group::factory()->create(['etablissement_id' => $this->marrakech->id, 'annee_scolaire_id' => $this->annee->id]);
        $this->actingAs($user);

        // 403 from the centre guard, or 404 when the route binding is
        // already centre-scoped — either way nothing leaks.
        $this->assertContains($this->getJson(route('backoffice.groups.inscription-fees', $rabatGroup))->status(), [403, 404]);
        $this->assertContains($this->getJson("/backoffice/groups/{$rabatGroup->id}/inscription-livres")->status(), [403, 404]);
        $this->getJson(route('backoffice.groups.inscription-fees', $ownGroup))->assertOk();
        $this->getJson("/backoffice/groups/{$ownGroup->id}/inscription-livres")->assertOk();
    }

    // --- CRUD-F4 / DB-07 / DB-08 ---------------------------------------

    public function test_update_cannot_change_the_student_of_a_paid_registration(): void
    {
        $user = $this->userIn($this->marrakech, 'director');
        [$student, $inscription, $fee] = $this->enrolled($this->marrakech);
        $this->actingAs($user);
        $this->post(route('backoffice.encaissements.store'), $this->paymentPayload($student, $inscription, $fee))->assertRedirect();

        $other = Student::factory()->create(['etablissement_id' => $this->marrakech->id]);
        $this->from(route('backoffice.inscriptions.index'))
            ->put(route('backoffice.inscriptions.update', $inscription), [
                'student_id' => $other->id, 'date_inscription' => '2025-09-15',
            ])->assertSessionHasErrors('student_id');

        $this->assertSame($student->id, $inscription->fresh()->student_id);
    }

    public function test_a_changement_registration_cannot_be_reactivated(): void
    {
        $user = $this->userIn($this->marrakech, 'director');
        [, $inscription] = $this->enrolled($this->marrakech);
        $inscription->update(['statut' => Inscription::STATUT_CHANGEMENT]);
        $this->actingAs($user);

        $this->from(route('backoffice.inscriptions.index'))
            ->patch(route('backoffice.inscriptions.update-statut', $inscription), ['statut' => 'Active'])
            ->assertSessionHasErrors('statut');
        $this->assertSame(Inscription::STATUT_CHANGEMENT, $inscription->fresh()->statut);

        $inscription->update(['statut' => Inscription::STATUT_ANNULEE, 'motif_annulation' => 'x']);
        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), ['statut' => 'Active'])->assertRedirect();
        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }
}
