<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Date d'opération » trail + the « Validation des dépenses » tab.
 *
 * Both are super-admin only, keyed on `expenses.approve` — deliberately
 * absent from every role in PermissionRegistry::matrix().
 *
 * ⚠ The point of these tests is that the trail is STRIPPED SERVER-SIDE, not
 * merely hidden in the UI: a normal user's Inertia payload must not contain
 * createdAt/updatedAt at all, so there is nothing for a crafted request or a
 * devtools peek to recover.
 */
final class DepenseOperationTrailTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
        $this->type = TypeDepense::create([
            'nom' => 'Fournitures', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function makeDepense(User $owner, ?string $dateDepense = null): Depense
    {
        $caisse = $owner->employee->caisses()->first();

        return Depense::create([
            'reference' => 'DEP-'.uniqid(),
            'type_depense_id' => $this->type->id,
            'caisse_id' => $caisse->id,
            'agent_id' => $owner->employee->id,
            'montant' => 500,
            'methode_paiement' => 'Espèces',
            // The BUSINESS date — freely typed, and here deliberately far
            // from "now" so it can never be confused with the trail.
            'date_depense' => $dateDepense ?? '2025-01-15',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function firstDepenseRow(Assert $page): array
    {
        return $page->toArray()['props']['depenses']['data'][0];
    }

    // ---------------------------------------------------------------
    // The permission itself
    // ---------------------------------------------------------------

    public function test_expenses_approve_is_in_no_role_preset(): void
    {
        foreach (\App\Support\Authorization\PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains(
                'expenses.approve',
                $permissions,
                "Role [{$role}] must not preset [expenses.approve] — the audit trail is super-admin only.",
            );
        }
    }

    // ---------------------------------------------------------------
    // The list
    // ---------------------------------------------------------------

    public function test_the_operation_trail_never_reaches_a_normal_user(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create', 'expenses.update');
        $this->makeDepense($user);

        $this->actingAs($user)
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $page->where('canAudit', false);
                $row = $this->firstDepenseRow($page);

                // Absent, not merely null: the controller unsets them.
                $this->assertArrayNotHasKey('createdAt', $row);
                $this->assertArrayNotHasKey('updatedAt', $row);
                $this->assertArrayNotHasKey('wasEdited', $row);
                // The business date is of course still there.
                $this->assertSame('2025-01-15', $row['dateDepense']);
            });
    }

    public function test_a_super_admin_sees_the_operation_trail(): void
    {
        $admin = $this->superAdmin();
        $depense = $this->makeDepense($admin);

        $this->actingAs($admin)
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($depense): void {
                $page->where('canAudit', true);
                $row = $this->firstDepenseRow($page);

                $this->assertSame($depense->created_at->format('d/m/Y H:i'), $row['createdAt']);
                $this->assertSame($depense->updated_at->format('d/m/Y H:i'), $row['updatedAt']);
                // Never touched since creation.
                $this->assertFalse($row['wasEdited']);
            });
    }

    public function test_the_trail_is_independent_of_the_backdatable_business_date(): void
    {
        // The whole reason this column exists: a user may enter today an
        // expense dated months ago, and an auditor must be able to see that.
        $admin = $this->superAdmin();
        $this->makeDepense($admin, '2024-03-02');

        $this->actingAs($admin)
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $row = $this->firstDepenseRow($page);

                $this->assertSame('2024-03-02', $row['dateDepense']);
                $this->assertSame(now()->format('d/m/Y'), substr((string) $row['createdAt'], 0, 10));
            });
    }

    public function test_an_edited_expense_is_flagged_as_modified(): void
    {
        $admin = $this->superAdmin();
        $depense = $this->makeDepense($admin);

        // Travel forward so updated_at is unambiguously later than created_at
        // (they are written microseconds apart on insert, which is exactly
        // what the >1s guard in the query exists to ignore).
        $this->travel(10)->minutes();
        $depense->update(['note' => 'corrigée']);

        $this->actingAs($admin)
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $row = $this->firstDepenseRow($page);

                $this->assertTrue($row['wasEdited']);
                $this->assertNotSame($row['createdAt'], $row['updatedAt']);
            });
    }

    // ---------------------------------------------------------------
    // The detail page (the "eye")
    // ---------------------------------------------------------------

    public function test_the_detail_page_hides_the_trail_from_a_normal_user(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create', 'expenses.update');
        $depense = $this->makeDepense($user);

        $this->actingAs($user)
            ->get(route('backoffice.depenses.show', $depense))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $page->where('canAudit', false);
                $details = $page->toArray()['props']['depense'];

                $this->assertArrayNotHasKey('createdAt', $details);
                $this->assertArrayNotHasKey('updatedAt', $details);
            });
    }

    public function test_the_detail_page_shows_the_trail_to_a_super_admin(): void
    {
        $admin = $this->superAdmin();
        $depense = $this->makeDepense($admin);

        $this->actingAs($admin)
            ->get(route('backoffice.depenses.show', $depense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canAudit', true)
                ->where('depense.createdAt', $depense->created_at->format('d/m/Y H:i'))
                ->where('depense.updatedAt', $depense->updated_at->format('d/m/Y H:i'))
                ->where('depense.wasEdited', false)
                // The other detail fields the « Détails » modal shows.
                ->where('depense.methodePaiement', 'Espèces')
                ->has('depense.statut')
            );
    }

    // ---------------------------------------------------------------
    // The « Validation des dépenses » tab
    // ---------------------------------------------------------------

    public function test_a_normal_user_cannot_open_the_validation_tab(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create');
        $this->makeDepense($user);

        // The tab is not offered, and forcing ?tab=validation changes nothing
        // — canAudit stays false and no trail is sent.
        $this->actingAs($user)
            ->get(route('backoffice.depenses.index', ['tab' => 'validation']))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $page->where('canAudit', false);
                $this->assertArrayNotHasKey('createdAt', $this->firstDepenseRow($page));
            });
    }

    public function test_the_validation_tab_carries_everything_its_columns_need(): void
    {
        $admin = $this->superAdmin();
        $depense = $this->makeDepense($admin);
        $depense->update(['mots_cles' => 'TONER, transport']);

        $this->actingAs($admin)
            ->get(route('backoffice.depenses.index', ['tab' => 'validation']))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $page->where('canAudit', true)->has('depenseStatuts');
                $row = $this->firstDepenseRow($page);

                foreach (['reference', 'typeDepense', 'statut', 'dateDepense', 'createdAt', 'montant', 'groupNom', 'motsCles', 'agent'] as $key) {
                    $this->assertArrayHasKey($key, $row, "Validation tab column [{$key}] is missing from the row.");
                }
            });
    }

    public function test_approving_is_still_refused_without_the_permission(): void
    {
        // The tab is only a view — the real gate stays the policy.
        $user = $this->userWith('expenses.view', 'expenses.create', 'expenses.update');
        $depense = $this->makeDepense($user);

        $this->actingAs($user)
            ->put(route('backoffice.depenses.approve', $depense))
            ->assertForbidden();
    }
}
