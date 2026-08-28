<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Audit;

use App\Models\AnneeScolaire;
use App\Models\Banque;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Role;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Audit 27/08/2026 — P2 remediation: roles form (SEC-08), forced password
 * change (SEC-06), centre-scoped journal (SEC-10), delete guards
 * (CRUD-F9/F11/F12/F15), uniqueness (CRUD-F16).
 */
final class P2RemediationTest extends TestCase
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

    private function userIn(Etablissement $centre, ?string $role = null, bool $super = false): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        if ($role) {
            $user->assignRole($role);
        }
        if ($super) {
            $user->assignRole(Role::SUPER_ADMIN);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    public function test_roles_form_refuses_super_admin_only_permissions(): void
    {
        $this->actingAs($this->userIn($this->marrakech, super: true));

        $this->from(route('backoffice.roles.create'))->post(route('backoffice.roles.store'), [
            'name' => 'shady', 'label' => 'Shady', 'permissions' => ['payments.view', 'payments.delete'],
        ])->assertSessionHasErrors('permissions.1');

        $this->assertNull(Role::query()->where('name', 'shady')->first());
    }

    public function test_a_login_with_a_one_time_password_is_sent_to_its_profile(): void
    {
        $user = $this->userIn($this->marrakech, 'teacher');
        $user->forceFill(['must_change_password' => true])->save();
        $this->actingAs($user->fresh());

        $this->get(route('backoffice.dashboard'))->assertRedirect(route('backoffice.profile'));
        $this->get(route('backoffice.profile'))->assertOk();

        $this->post(route('backoffice.profile.password.update'), [
            'current_password' => 'password', 'password' => 'N3w-Str0ng-Pass!', 'password_confirmation' => 'N3w-Str0ng-Pass!',
        ]);
        $this->assertFalse($user->fresh()->must_change_password);
        $this->get(route('backoffice.dashboard'))->assertOk();
    }

    public function test_journal_is_scoped_to_the_readers_centres(): void
    {
        $director = $this->userIn($this->marrakech, 'director');
        $director->givePermissionTo('audit-logs.view');
        $colleague = $this->userIn($this->marrakech, 'teacher');
        $stranger = $this->userIn($this->rabat, 'teacher');

        activity('test')->causedBy($colleague)->log('marrakech action');
        activity('test')->causedBy($stranger)->log('rabat action');

        $this->actingAs($director->fresh())
            ->get(route('backoffice.audit-logs.index', ['logName' => 'test']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('entries.data', function ($rows) {
                $descriptions = collect($rows)->pluck('description');

                return $descriptions->contains('marrakech action') && ! $descriptions->contains('rabat action');
            }));

        $this->actingAs($this->userIn($this->rabat, super: true))
            ->get(route('backoffice.audit-logs.index', ['logName' => 'test']))
            ->assertInertia(fn (Assert $page) => $page->where('entries.data', fn ($rows) => collect($rows)->pluck('description')->contains('rabat action')));
    }

    public function test_bank_used_by_a_cheque_can_neither_be_deleted_nor_renamed(): void
    {
        $admin = $this->userIn($this->marrakech, super: true);
        $banque = Banque::create(['nom' => 'BMCE', 'statut' => 'Actif']);
        $student = Student::factory()->create(['etablissement_id' => $this->marrakech->id]);
        Cheque::create([
            'reference' => 'CHQ-B', 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => '1', 'banque' => 'BMCE', 'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => Cheque::TYPE_A_DEPOSER, 'statut' => Cheque::STATUT_EN_POSSESSION, 'montant' => 100,
            'etablissement_id' => $this->marrakech->id, 'agent_id' => $admin->employee->id,
        ]);
        $this->actingAs($admin);

        $this->from(route('backoffice.settings'))->delete(route('backoffice.banques.destroy', $banque))->assertSessionHasErrors('delete');
        $this->from(route('backoffice.settings'))->put(route('backoffice.banques.update', $banque), ['nom' => 'BMCI', 'statut' => 'Actif'])->assertSessionHasErrors('nom');
        $this->assertSame('BMCE', $banque->fresh()->nom);
    }

    public function test_default_academic_year_cannot_be_deleted(): void
    {
        $this->actingAs($this->userIn($this->marrakech, super: true));

        $this->from(route('backoffice.settings'))
            ->delete(route('backoffice.annees-scolaires.destroy', $this->annee))
            ->assertSessionHasErrors('delete');
        $this->assertNotNull($this->annee->fresh());
    }

    public function test_student_with_a_cheque_cannot_be_deleted(): void
    {
        $admin = $this->userIn($this->marrakech, super: true);
        $student = Student::factory()->create(['etablissement_id' => $this->marrakech->id]);
        Cheque::create([
            'reference' => 'CHQ-S', 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => '2', 'banque' => 'CIH', 'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => Cheque::TYPE_A_DEPOSER, 'statut' => Cheque::STATUT_EN_POSSESSION, 'montant' => 100,
            'etablissement_id' => $this->marrakech->id, 'agent_id' => $admin->employee->id,
        ]);
        $this->actingAs($admin);

        $this->from(route('backoffice.students.index'))
            ->delete(route('backoffice.students.destroy', $student))
            ->assertSessionHasErrors('delete');
        $this->assertNotNull($student->fresh());
    }

    public function test_seance_cannot_be_cancelled_through_update_nor_deleted_once_validated(): void
    {
        $this->actingAs($this->userIn($this->marrakech, super: true));
        $group = Group::factory()->create(['etablissement_id' => $this->marrakech->id, 'annee_scolaire_id' => $this->annee->id]);
        $seance = Seance::create([
            'group_id' => $group->id, 'date_seance' => '2026-03-02', 'etablissement_id' => $this->marrakech->id,
            'annee_scolaire_id' => $this->annee->id, 'statut' => Seance::STATUT_PREVUE,
        ]);

        $this->from(route('backoffice.seances.index'))->put(route('backoffice.seances.update', $seance), [
            'date_seance' => '2026-03-02', 'statut' => Seance::STATUT_ANNULEE,
        ])->assertSessionHasErrors('statut');
        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);

        $seance->update(['statut' => Seance::STATUT_EFFECTUEE]);
        $this->from(route('backoffice.seances.index'))->delete(route('backoffice.seances.destroy', $seance))->assertSessionHasErrors('delete');
        $this->assertNotNull($seance->fresh());
    }

    public function test_centre_names_are_unique_and_a_student_cannot_be_enrolled_twice_in_a_group(): void
    {
        $admin = $this->userIn($this->marrakech, super: true);
        $this->actingAs($admin);

        $this->from(route('backoffice.settings'))->post(route('backoffice.etablissements.store'), [
            'nom_centre' => 'GLS Rabat',
        ])->assertSessionHasErrors('nom_centre');

        $group = Group::factory()->create(['etablissement_id' => $this->marrakech->id, 'annee_scolaire_id' => $this->annee->id]);
        $student = Student::factory()->create(['etablissement_id' => $this->marrakech->id]);
        $payload = [
            'inscription_mode' => 'existing', 'student_id' => $student->id, 'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [['frais_id' => null, 'nom' => 'Frais', 'montant_initial' => '300']],
        ];
        $this->post(route('backoffice.inscriptions.store'), $payload)->assertRedirect(route('backoffice.inscriptions.index'));
        $this->from(route('backoffice.inscriptions.index'))->post(route('backoffice.inscriptions.store'), $payload)->assertSessionHasErrors('group_id');

        $this->assertSame(1, Inscription::query()->where('student_id', $student->id)->count());
    }
}
