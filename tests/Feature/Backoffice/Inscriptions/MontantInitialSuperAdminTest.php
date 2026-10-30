<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⚠ La colonne « Initial (DH) » d'une ligne de frais est le PRIX du tarif du
 * groupe : seul le super-admin la modifie (`registrations.update-fee-amount`,
 * superAdminOnly(), 30/10/2026). Tout autre porteur de
 * `registrations.manage-fees` / `registrations.create` garde la remise et les
 * semaines, mais un montant initial changé est REFUSÉ (422) — jamais remplacé
 * en silence.
 */
final class MontantInitialSuperAdminTest extends TestCase
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

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user->fresh();
    }

    /**
     * @return array{0: Inscription, 1: InscriptionFee}
     */
    private function inscriptionWithFee(): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-MI1', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => 1300,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Septembre',
            'montant_initial' => 1300, 'montant' => 1300,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function putFee(User $user, Inscription $inscription, InscriptionFee $fee, array $overrides)
    {
        return $this->actingAs($user)
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [array_merge([
                    'id' => $fee->id,
                    'nom' => $fee->nom,
                    'montant_initial' => '1300.00',
                    'date_echeance' => '2025-10-01',
                ], $overrides)],
            ]);
    }

    public function test_no_role_preset_holds_the_permission(): void
    {
        $this->assertContains('registrations.update-fee-amount', PermissionRegistry::superAdminOnly());

        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains('registrations.update-fee-amount', $permissions, "Rôle {$role}");
        }
    }

    public function test_a_changed_initial_amount_is_refused_without_the_permission(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->putFee($this->userWith('registrations.view', 'registrations.manage-fees'), $inscription, $fee, [
            'montant_initial' => '1000.00',
        ])->assertSessionHasErrors('fee_lines.0.montant_initial');

        $this->assertSame('1300.00', $fee->fresh()->montant_initial);
    }

    public function test_the_discount_stays_editable_without_the_permission(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->putFee($this->userWith('registrations.view', 'registrations.manage-fees'), $inscription, $fee, [
            'remise_montant' => '300',
        ])->assertSessionHasNoErrors();

        $fee->refresh();
        $this->assertSame('1300.00', $fee->montant_initial);
        $this->assertSame('1000.00', $fee->montant);
    }

    public function test_a_super_admin_changes_the_initial_amount(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->putFee($this->superAdmin(), $inscription, $fee, [
            'montant_initial' => '1000.00',
        ])->assertSessionHasNoErrors();

        $this->assertSame('1000.00', $fee->fresh()->montant_initial);
    }

    public function test_a_new_line_must_carry_the_group_tariff(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();
        $frais = Frais::create(['nom' => 'Frais de Octobre', 'montant_defaut' => 900, 'statut' => 'Actif']);
        $inscription->group->frais()->attach($frais->id, ['montant' => 1300, 'date_echeance' => '2025-11-01']);
        $user = $this->userWith('registrations.view', 'registrations.manage-fees');

        $nouvelle = fn (string $montant): array => [
            'fee_lines' => [
                ['id' => $fee->id, 'nom' => $fee->nom, 'montant_initial' => '1300.00', 'date_echeance' => '2025-10-01'],
                ['frais_id' => $frais->id, 'nom' => $frais->nom, 'montant_initial' => $montant, 'date_echeance' => '2025-11-01'],
            ],
        ];

        $this->actingAs($user)
            ->put(route('backoffice.inscriptions.fees.update', $inscription), $nouvelle('500.00'))
            ->assertSessionHasErrors('fee_lines.1.montant_initial');

        $this->actingAs($user)
            ->put(route('backoffice.inscriptions.fees.update', $inscription), $nouvelle('1300.00'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $inscription->fees()->count());
    }
}
