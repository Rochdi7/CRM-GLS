<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Activity;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Authorization\PermissionRegistry;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Corriger le MONTANT d'une dépense — super-admin uniquement (02/10/2026,
 * `expenses.update-amount`, CorrigerMontantDepense).
 *
 * Sur une dépense approuvée le montant est déjà sorti de la caisse : la
 * correction passe la DIFFÉRENCE par CaisseLedger (débit contrôlé par
 * GardeSoldeCaisse, ou crédit), jamais une simple réécriture de colonne.
 */
final class DepenseMontantCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
    }

    /** @param list<string> $permissions */
    private function userWith(array $permissions, bool $superAdmin = false): User
    {
        $user = User::factory()->create();

        if ($superAdmin) {
            $user->assignRole('super-admin');
        } else {
            foreach ($permissions as $p) {
                $user->givePermissionTo($p);
            }
        }

        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        return $user->fresh();
    }

    private function depense(string $statut, string $montant, string $soldeCaisse): Depense
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $till = $agent->till()->firstOrFail();
        $till->update(['solde' => $soldeCaisse]);

        $type = TypeDepense::firstOrCreate(
            ['nom' => 'Fournitures'],
            ['is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF],
        );

        return Depense::create([
            'reference' => 'DEP-'.fake()->unique()->numerify('#####'),
            'type_depense_id' => $type->id,
            'caisse_id' => $till->id,
            'agent_id' => $agent->id,
            'montant' => $montant,
            'methode_paiement' => 'Espèces',
            'date_depense' => now()->toDateString(),
            'statut' => $statut,
            'approved_at' => $statut === Depense::STATUT_APPROUVEE ? now() : null,
            'description' => 'Fournitures bureau',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Depense $depense, string $montant): array
    {
        return [
            'type_depense_id' => $depense->type_depense_id,
            'montant' => $montant,
            'methode_paiement' => 'Espèces',
            'date_depense' => $depense->date_depense->toDateString(),
            'description' => $depense->description,
        ];
    }

    public function test_the_permission_is_reserved_to_super_admins(): void
    {
        $this->assertContains('expenses.update-amount', PermissionRegistry::superAdminOnly());

        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains('expenses.update-amount', $permissions, "Rôle {$role}");
        }
    }

    public function test_raising_an_approved_expense_debits_the_difference(): void
    {
        $depense = $this->depense(Depense::STATUT_APPROUVEE, '1000.00', '2000.00');
        $this->actingAs($this->userWith([], superAdmin: true));

        $this->put(route('backoffice.depenses.update', $depense), $this->payload($depense, '1500.00'))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('1500.00', (string) $depense->fresh()->montant);
        $this->assertSame('1500.00', (string) $depense->caisse->fresh()->solde);
        $this->assertTrue(
            Activity::query()
                ->where('event', 'solde_movement')
                ->where('properties->origine_id', (string) $depense->id)
                ->where('properties->correction', 'CORRECTION-'.$depense->reference.'-MONTANT')
                ->exists(),
        );
    }

    public function test_lowering_an_approved_expense_credits_the_difference(): void
    {
        $depense = $this->depense(Depense::STATUT_APPROUVEE, '1000.00', '0.00');
        $this->actingAs($this->userWith([], superAdmin: true));

        $this->put(route('backoffice.depenses.update', $depense), $this->payload($depense, '600.00'))
            ->assertSessionHasNoErrors();

        $this->assertSame('600.00', (string) $depense->fresh()->montant);
        $this->assertSame('400.00', (string) $depense->caisse->fresh()->solde);
    }

    public function test_a_raise_beyond_the_till_balance_is_refused_and_nothing_moves(): void
    {
        $depense = $this->depense(Depense::STATUT_APPROUVEE, '1000.00', '100.00');
        $this->actingAs($this->userWith([], superAdmin: true));

        $this->put(route('backoffice.depenses.update', $depense), $this->payload($depense, '1500.00'))
            ->assertSessionHasErrors('montant');

        $this->assertSame('1000.00', (string) $depense->fresh()->montant);
        $this->assertSame('100.00', (string) $depense->caisse->fresh()->solde);
    }

    public function test_a_pending_expense_changes_amount_without_touching_the_till(): void
    {
        $depense = $this->depense(Depense::STATUT_EN_ATTENTE, '1000.00', '50.00');
        $this->actingAs($this->userWith([], superAdmin: true));

        $this->put(route('backoffice.depenses.update', $depense), $this->payload($depense, '13625.05'))
            ->assertSessionHasNoErrors();

        $this->assertSame('13625.05', (string) $depense->fresh()->montant);
        $this->assertSame('50.00', (string) $depense->caisse->fresh()->solde);
    }

    public function test_a_non_super_admin_cannot_change_the_amount_but_can_still_edit(): void
    {
        $depense = $this->depense(Depense::STATUT_APPROUVEE, '1000.00', '2000.00');
        $this->actingAs($this->userWith(['expenses.view', 'expenses.update']));

        $this->put(route('backoffice.depenses.update', $depense), $this->payload($depense, '1500.00'))
            ->assertSessionHasErrors('montant');

        $this->assertSame('1000.00', (string) $depense->fresh()->montant);
        $this->assertSame('2000.00', (string) $depense->caisse->fresh()->solde);

        // The unchanged amount the form echoes back is accepted.
        $this->put(route('backoffice.depenses.update', $depense), [
            ...$this->payload($depense, '1000.00'),
            'note' => 'corrigée',
        ])->assertSessionHasNoErrors();

        $this->assertSame('corrigée', $depense->fresh()->note);
    }
}
