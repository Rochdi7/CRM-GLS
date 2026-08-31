<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reported 31/08/2026: an « Action échouée » on the Inscriptions screen was
 * read as a missing permission for Consultant / Assistante administrative.
 * It was not — the cause was a missing DB column on production. This pins what
 * those two roles genuinely hold, so the next such report is answered by a
 * test result instead of an opinion, and so the front-desk scope agreed on
 * 30/08/2026 (CLAUDE.md §16) cannot silently regress:
 *
 *   MAY modify  — étudiants, inscriptions, groupes, séances.
 *   MAY NOT     — any financial document edit (append-only, §11).
 */
final class AssistantePermsTest extends TestCase
{
    use RefreshDatabase;

    /** The business treats these two job titles as the SAME job (§16). */
    private const FRONT_DESK_ROLES = ['consultant', 'administrative-assistant'];

    private function actorWithRole(string $role): User
    {
        $centre = Etablissement::factory()->create();
        $user = User::factory()->create();

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $centre->id,
        ]);
        $employee->syncEtablissements([$centre->id], $centre->id);

        $user->syncRoles([$role]);

        return $user->fresh();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_front_desk_roles_may_edit_the_four_academic_objects(): void
    {
        foreach (self::FRONT_DESK_ROLES as $role) {
            $user = $this->actorWithRole($role);

            foreach ([
                'students.view', 'students.create', 'students.update',
                'registrations.view', 'registrations.create', 'registrations.update',
                'registrations.change-group', 'registrations.manage-fees',
                'groups.view', 'groups.create', 'groups.update', 'groups.archive',
                'attendance.update', 'attendance.mark',
            ] as $permission) {
                $this->assertTrue(
                    $user->can($permission),
                    "{$role} should hold {$permission}",
                );
            }
        }
    }

    /**
     * The other half of the rule: a front desk CREATES money records but never
     * rewrites one — a mistake is corrected with a compensating entry.
     */
    public function test_front_desk_roles_may_not_edit_money_documents(): void
    {
        foreach (self::FRONT_DESK_ROLES as $role) {
            $user = $this->actorWithRole($role);

            foreach ([
                'expenses.update',
                'refunds.update',
                'cheques.update',
                'cash-transfers.update',
                // Re-dating a payment moves it in the caisse journal and the
                // annual summary — super-admin only since 30/08/2026.
                'payments.update-date',
            ] as $permission) {
                $this->assertFalse(
                    $user->can($permission),
                    "{$role} must NOT hold {$permission}",
                );
            }
        }
    }

    /** Consultant and Assistante administrative are the same job (§16). */
    public function test_the_two_front_desk_roles_are_identical(): void
    {
        $consultant = $this->actorWithRole('consultant')
            ->getAllPermissions()->pluck('name')->sort()->values()->all();

        $assistante = $this->actorWithRole('administrative-assistant')
            ->getAllPermissions()->pluck('name')->sort()->values()->all();

        $this->assertSame($consultant, $assistante);
    }
}
