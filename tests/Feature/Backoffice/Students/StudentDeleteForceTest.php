<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Role;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Supprimer une fiche étudiant orpheline » (21/09/2026).
 *
 * LE CAS RÉEL — une fiche créée par erreur au guichet, appelée deux jours
 * dans un groupe où elle n'a JAMAIS été inscrite, puis abandonnée. Elle ne
 * porte ni inscription, ni paiement, ni chèque : seulement deux « Absent »
 * qui ne décrivent personne. La garde la bloquait au même titre qu'un
 * dossier vivant, si bien que la seule issue était la console.
 *
 * ⚠ CE QUE CES TESTS FIXENT, et qu'aucun assouplissement futur ne doit
 * défaire : le forçage ne sait effacer QUE des lignes d'appel. Dès qu'il y
 * a de l'argent ou un dossier, il ne s'applique pas — pour personne,
 * super-admin compris. Élargir le forçage à un encaissement ferait de la
 * suppression d'étudiant un moyen de faire disparaître de l'argent, ce que
 * tout le reste du module interdit (CLAUDE.md §11 : les enregistrements
 * monétaires ne se suppriment jamais).
 */
final class StudentDeleteForceTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Group $group;

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
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user->fresh();
    }

    private function userWith(string ...$permissions): User
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $user = $employee->user;

        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /**
     * Un employé et SA caisse physique — l'observer en provisionne déjà une
     * à la création (CaisseProvisioner), donc on la relit au lieu d'en
     * fabriquer une seconde : un employé n'a qu'UN tiroir à vie (§11).
     *
     * @return array{0: Employee, 1: Caisse}
     */
    private function tillHolder(): array
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        $caisse = $employee->till()->first() ?? Caisse::create([
            'nom' => 'Caisse '.$employee->id,
            'type' => Caisse::TYPE_CAISSIERE,
            'responsable_employee_id' => $employee->id,
            'solde' => 0,
            'statut' => 'Active',
        ]);

        return [$employee, $caisse];
    }

    private function orphanStudent(int $presences = 2): Student
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        // Une présence par séance : (seance_id, student_id) est unique —
        // un étudiant n'est appelé qu'une fois par cours.
        for ($i = 0; $i < $presences; $i++) {
            $seance = Seance::create([
                'group_id' => $this->group->id,
                'date_seance' => '2026-03-0'.($i + 2),
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->annee->id,
                'statut' => Seance::STATUT_EFFECTUEE,
            ]);

            Presence::create([
                'seance_id' => $seance->id,
                'student_id' => $student->id,
                'statut' => Presence::STATUT_ABSENT,
            ]);
        }

        return $student;
    }

    public function test_a_super_admin_deletes_an_orphan_record_and_its_attendance_lines(): void
    {
        $student = $this->orphanStudent();

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.students.destroy', $student), ['force' => true])
            ->assertRedirect(route('backoffice.students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('presences', ['student_id' => $student->id]);

        // Les lignes effacées sont au journal AVANT de disparaître : c'est
        // la seule trace qui reste de ce qui a été détruit.
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'student',
            'event' => 'presences_orphelines_supprimees',
            'subject_id' => $student->id,
        ]);
    }

    public function test_without_force_the_record_is_still_refused(): void
    {
        $student = $this->orphanStudent(1);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.students.destroy', $student))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('presences', ['student_id' => $student->id]);
    }

    public function test_a_record_with_nothing_attached_is_deleted_without_force(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.students.destroy', $student))
            ->assertRedirect(route('backoffice.students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    /**
     * ⚠ LA borne. Le forçage n'efface que des appels : il ne regarde même
     * pas l'argent, et ne doit jamais devenir un moyen de le faire
     * disparaître.
     */
    public function test_money_blocks_the_delete_even_for_a_super_admin_with_force(): void
    {
        $student = $this->orphanStudent();

        [$employee, $caisse] = $this->tillHolder();

        Encaissement::create([
            'reference' => 'ENC-DELTEST-1',
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'montant' => 100,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-03-02',
            'caisse_id' => $caisse->id,
            'agent_id' => $employee->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.students.destroy', $student), ['force' => true])
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('encaissements', ['student_id' => $student->id]);
        // Les présences non plus : le refus est ENTIER, pas partiel.
        $this->assertDatabaseHas('presences', ['student_id' => $student->id]);
    }

    public function test_a_registration_blocks_the_delete_even_with_force(): void
    {
        $student = $this->orphanStudent();

        Inscription::create([
            'reference' => 'INS-DELTEST-1',
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-03-01',
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.students.destroy', $student), ['force' => true])
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('presences', ['student_id' => $student->id]);
    }

    /**
     * Un titulaire de `students.delete` qui n'est pas super-admin ne force
     * pas : la permission ouvre la suppression ordinaire, pas l'effacement
     * de lignes d'appel.
     */
    public function test_a_non_super_admin_cannot_force(): void
    {
        $student = $this->orphanStudent(1);
        $user = $this->userWith('students.view', 'students.delete');

        // Peu importe PAR OÙ il est arrêté (403 de la policy ou refus du
        // forçage) : ce qui est fixé ici, c'est que rien ne disparaît.
        $this->actingAs($user)
            ->delete(route('backoffice.students.destroy', $student), ['force' => true]);

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('presences', ['student_id' => $student->id]);
    }

    /**
     * L'écran NOMME ce qu'il va détruire — sans la date, le statut et le
     * groupe, l'utilisateur ne peut pas juger si ces appels sont des
     * scories ou la trace d'un vrai passage en classe.
     */
    public function test_the_blockers_endpoint_names_the_attendance_lines(): void
    {
        $student = $this->orphanStudent(1);

        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.students.delete-blockers', $student))
            ->assertOk()
            ->assertJsonPath('forcable', true)
            ->assertJsonCount(1, 'presences')
            ->assertJsonPath('presences.0.statut', Presence::STATUT_ABSENT)
            ->assertJsonPath('presences.0.groupe', $this->group->nom)
            ->assertJsonPath('encaissements', 0);
    }

    /**
     * Le read-model porte la MÊME règle que l'écriture : la page ne redérive
     * jamais « peut-on forcer ? », sinon elle finirait par dessiner un
     * bouton que destroy() refuse (CLAUDE.md §5).
     */
    public function test_the_blockers_endpoint_refuses_to_offer_forcing_when_money_exists(): void
    {
        $student = $this->orphanStudent();

        [$employee, $caisse] = $this->tillHolder();

        Encaissement::create([
            'reference' => 'ENC-DELTEST-2',
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'montant' => 100,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-03-02',
            'caisse_id' => $caisse->id,
            'agent_id' => $employee->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.students.delete-blockers', $student))
            ->assertOk()
            ->assertJsonPath('forcable', false);
    }

    public function test_the_blockers_endpoint_does_not_offer_forcing_to_a_non_super_admin(): void
    {
        $student = $this->orphanStudent(1);

        $response = $this->actingAs($this->userWith('students.view', 'students.delete'))
            ->getJson(route('backoffice.students.delete-blockers', $student));

        // Soit l'autorisation l'arrête en amont, soit l'endpoint répond sans
        // jamais proposer le forçage — jamais un bouton que destroy()
        // refuserait ensuite (CLAUDE.md §5).
        if ($response->status() === 200) {
            $response->assertJsonPath('forcable', false);
        } else {
            $this->assertContains($response->status(), [302, 403]);
        }
    }
}
