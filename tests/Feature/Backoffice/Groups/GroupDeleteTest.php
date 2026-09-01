<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
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
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⚠ L'EXCEPTION à « un groupe ne se supprime jamais » (CLAUDE.md §11) :
 * suppression DÉFINITIVE réservée au super-admin, et UNIQUEMENT pour un
 * groupe créé par erreur — le moindre encaissement ou la moindre séance la
 * fait refuser. Aucun argent n'est déplacé par ce chemin.
 */
final class GroupDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Group $group;

    private Caisse $caisse;

    private Employee $agent;

    /** @var list<InscriptionFee> */
    private array $fees = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = $this->agent->till()->firstOrFail();

        foreach (range(1, 2) as $i) {
            $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
            $inscription = Inscription::create([
                'reference' => 'INS-DEL-'.$i,
                'student_id' => $student->id,
                'group_id' => $this->group->id,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->annee->id,
                'statut' => Inscription::STATUT_ACTIVE,
                'date_inscription' => '2026-01-10',
            ]);
            $fee = InscriptionFee::create([
                'inscription_id' => $inscription->id, 'nom' => 'Frais test',
                'montant_initial' => 500, 'montant' => 500,
                'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
            ]);
            $this->fees[] = $fee;
        }
    }

    /**
     * Un paiement encaissé sur le premier frais — ce qui doit FAIRE ÉCHOUER
     * la suppression. Appelé seulement par les tests qui l'exigent : le cas
     * nominal est un groupe créé par erreur, donc sans argent.
     */
    private function encaisserUnPaiement(): Encaissement
    {
        $fee = $this->fees[0];

        return Encaissement::create([
            'reference' => 'ENC-DEL-1',
            'student_id' => $fee->inscription->student_id,
            'inscription_fee_id' => $fee->id,
            'montant' => 500,
            'methode' => 'Espèces',
            'date_paiement' => '2026-01-15',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    public function test_groups_delete_is_super_admin_only_and_held_by_no_role_preset(): void
    {
        $this->assertContains('groups.delete', PermissionRegistry::superAdminOnly());

        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains('groups.delete', $permissions, "Le preset {$role} ne doit pas porter groups.delete.");
        }
    }

    public function test_a_super_admin_deletes_a_group_that_never_received_money(): void
    {
        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.groups.destroy', $this->group), [
                'confirmation' => $this->group->nom,
            ])
            ->assertRedirect(route('backoffice.groups.index'));

        $this->assertDatabaseMissing('groups', ['id' => $this->group->id]);
        $this->assertSame(0, Inscription::query()->where('group_id', $this->group->id)->count());
        $this->assertSame(0, InscriptionFee::query()->count());
    }

    /**
     * ⚠ LA règle demandée : un seul dirham encaissé et la suppression est
     * refusée — rien n'est détruit, et surtout aucun paiement n'est déplacé
     * ou reconverti au passage.
     */
    public function test_a_group_with_a_single_payment_is_refused(): void
    {
        $encaissement = $this->encaisserUnPaiement();
        $soldeAvant = (float) $this->caisse->fresh()->solde;

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.groups.destroy', $this->group), [
                'confirmation' => $this->group->nom,
            ])
            ->assertSessionHasErrors('group');

        $this->assertDatabaseHas('groups', ['id' => $this->group->id]);
        $this->assertSame(2, Inscription::query()->where('group_id', $this->group->id)->count());
        // L'argent n'a pas bougé d'un pouce : toujours attaché à son frais.
        $this->assertSame(
            $this->fees[0]->id,
            $encaissement->fresh()->inscription_fee_id,
        );
        $this->assertSame($soldeAvant, (float) $this->caisse->fresh()->solde);
    }

    public function test_the_exact_group_name_must_be_retyped(): void
    {
        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.groups.destroy', $this->group), ['confirmation' => 'pas le bon nom'])
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseHas('groups', ['id' => $this->group->id]);
        $this->assertSame(2, Inscription::query()->where('group_id', $this->group->id)->count());
    }

    public function test_a_group_carrying_seances_is_refused(): void
    {
        Seance::create([
            'group_id' => $this->group->id,
            'date_seance' => '2026-02-01',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUTS[0],
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.groups.destroy', $this->group), [
                'confirmation' => $this->group->nom,
            ])
            ->assertSessionHasErrors('group');

        $this->assertDatabaseHas('groups', ['id' => $this->group->id]);
    }

    public function test_a_non_super_admin_cannot_delete_a_group(): void
    {
        $user = User::factory()->create();
        $user->assignRole('director');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($user->fresh())
            ->delete(route('backoffice.groups.destroy', $this->group), [
                'confirmation' => $this->group->nom,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('groups', ['id' => $this->group->id]);
    }

    public function test_the_deletion_impact_endpoint_reports_the_real_numbers(): void
    {
        $this->encaisserUnPaiement();

        $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.groups.deletion-impact', $this->group))
            ->assertOk()
            ->assertJson([
                'inscriptions' => 2,
                'etudiants' => 2,
                'frais' => 2,
                'encaissements' => 1,
                'montantEncaisse' => 500.0,
                'seances' => 0,
            ]);
    }
}
