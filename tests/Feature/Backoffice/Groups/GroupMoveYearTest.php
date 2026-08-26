<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Super-admin-only "move group to another année": the SAME rows change
 * year — group, inscriptions, séances — and payments follow through their
 * fee. Nothing copied, nothing dropped: identical counts before and after.
 */
final class GroupMoveYearTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $de;

    private AnneeScolaire $vers;

    private Etablissement $centre;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->de = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->vers = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->de->id,
        ]);

        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        // The till EmployeeObserver already provisioned — an employee owns
        // exactly one (partial unique index, 24/08/2026 audit).
        $caisse = $agent->till()->firstOrFail();

        foreach (range(1, 3) as $i) {
            $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
            $inscription = Inscription::create([
                'reference' => 'INS-MV-'.$i,
                'student_id' => $student->id,
                'group_id' => $this->group->id,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->de->id,
                'statut' => Inscription::STATUT_ACTIVE,
                'date_inscription' => '2026-01-10',
            ]);
            $fee = InscriptionFee::create([
                'inscription_id' => $inscription->id, 'nom' => 'Frais test',
                'montant_initial' => 500, 'montant' => 500,
                'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
            ]);
            Encaissement::create([
                'reference' => 'ENC-MV-'.$i,
                'student_id' => $student->id,
                'inscription_fee_id' => $fee->id,
                'montant' => 500,
                'methode' => 'Espèces',
                'date_paiement' => '2026-01-15',
                'caisse_id' => $caisse->id,
                'agent_id' => $agent->id,
            ]);
            Seance::create([
                'group_id' => $this->group->id,
                'date_seance' => '2026-02-0'.$i,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->de->id,
                'statut' => Seance::STATUTS[0],
            ]);
        }
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    public function test_a_super_admin_moves_the_group_with_everything_and_the_same_counts(): void
    {
        $before = [
            'inscriptions' => Inscription::query()->where('group_id', $this->group->id)->count(),
            'seances' => Seance::query()->where('group_id', $this->group->id)->count(),
            'encaissements' => Encaissement::query()->count(),
            'montant' => (float) Encaissement::query()->sum('montant'),
        ];

        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.groups.move-year', $this->group), ['annee_scolaire_id' => $this->vers->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame($this->vers->id, $this->group->fresh()->annee_scolaire_id);
        $this->assertSame(0, Inscription::query()->where('group_id', $this->group->id)->where('annee_scolaire_id', $this->de->id)->count());
        $this->assertSame($before['inscriptions'], Inscription::query()->where('group_id', $this->group->id)->where('annee_scolaire_id', $this->vers->id)->count());
        $this->assertSame($before['seances'], Seance::query()->where('group_id', $this->group->id)->where('annee_scolaire_id', $this->vers->id)->count());

        // Payments follow through their inscription — none lost, none touched.
        $this->assertSame($before['encaissements'], Encaissement::query()->count());
        $this->assertSame($before['montant'], (float) Encaissement::query()->sum('montant'));
        $this->assertSame(
            $before['encaissements'],
            Encaissement::query()->whereHas('fee.inscription', fn ($q) => $q->where('annee_scolaire_id', $this->vers->id))->count(),
        );
    }

    public function test_the_statut_can_change_with_the_move_through_the_sanctioned_transitions(): void
    {
        // En inscription → Annulée goes through Group::annuler() (writes the
        // groups_historique snapshot like the row-menu "Annuler" action).
        $this->group->update(['statut' => Group::STATUT_EN_INSCRIPTION]);

        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.groups.move-year', $this->group), [
                'annee_scolaire_id' => $this->vers->id,
                'statut' => Group::STATUT_ANNULEE,
            ])
            ->assertSessionHasNoErrors();

        $group = $this->group->fresh();
        $this->assertSame($this->vers->id, $group->annee_scolaire_id);
        $this->assertSame(Group::STATUT_ANNULEE, $group->statut);
        $this->assertSame(1, \App\Models\GroupHistorique::query()->where('group_id', $group->id)->count());
    }

    public function test_a_finished_group_never_leaves_fin_de_formation(): void
    {
        $this->group->archiverCommeTermine();

        $this->actingAs($this->superAdmin())
            ->from(route('backoffice.groups.show', $this->group))
            ->post(route('backoffice.groups.move-year', $this->group), [
                'annee_scolaire_id' => $this->vers->id,
                'statut' => Group::STATUT_EN_FORMATION,
            ])
            ->assertSessionHasErrors(['statut']);

        $group = $this->group->fresh();
        $this->assertSame(Group::STATUT_FIN_FORMATION, $group->statut);
        // The transaction rolled the year move back too — all or nothing.
        $this->assertSame($this->de->id, $group->annee_scolaire_id);
    }

    public function test_it_refuses_the_same_year(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('backoffice.groups.show', $this->group))
            ->post(route('backoffice.groups.move-year', $this->group), ['annee_scolaire_id' => $this->de->id])
            ->assertSessionHasErrors(['annee_scolaire_id']);

        $this->assertSame($this->de->id, $this->group->fresh()->annee_scolaire_id);
    }

    public function test_a_regular_group_manager_cannot_move_a_group(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('groups.view', 'groups.update', 'groups.archive', 'centers.access-all');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($user->fresh())
            ->post(route('backoffice.groups.move-year', $this->group), ['annee_scolaire_id' => $this->vers->id])
            ->assertForbidden();

        $this->assertSame($this->de->id, $this->group->fresh()->annee_scolaire_id);
    }

    public function test_no_role_preset_may_hold_the_permission(): void
    {
        $this->assertContains('groups.move-year', \App\Support\Authorization\PermissionRegistry::superAdminOnly());

        foreach (\App\Support\Authorization\PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains('groups.move-year', $permissions, "Role {$role} must not hold groups.move-year");
        }
    }
}
