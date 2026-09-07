<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Audit;

use App\Models\Activity;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The audit journal's DETAIL page applies the same centre scope as its
 * listing — audit 07/09/2026, finding H-7.
 *
 * `AuditLogController::index()` narrows entries to the causers a reader may
 * see (`causerScope()`), but `show()` called `find()` with no such argument.
 * A reader confined to one centre could therefore read ANY entry — including
 * another centre's money trail — simply by guessing or iterating its id. A
 * detail page must never reveal what its own listing filtered out.
 */
final class AuditLogDetailScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** A reader holding audit-logs.view, confined to one centre. */
    private function readerIn(Etablissement $centre): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('audit-logs.view');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $centre->id,
        ]);
        $employee->syncEtablissements([$centre->id]);

        return $user->fresh();
    }

    public function test_a_centre_bound_reader_cannot_open_another_centres_entry_by_id(): void
    {
        $mine = Etablissement::factory()->create();
        $foreign = Etablissement::factory()->create();

        $reader = $this->readerIn($mine);

        // An actor belonging to a centre the reader cannot reach.
        $stranger = User::factory()->create();
        $strangerEmployee = Employee::factory()->create([
            'user_id' => $stranger->id,
            'etablissement_id' => $foreign->id,
        ]);
        $strangerEmployee->syncEtablissements([$foreign->id]);

        // An entry authored by that stranger.
        $entry = Activity::create([
            'log_name' => 'employee',
            'description' => 'Employé modifié',
            'causer_type' => (new User)->getMorphClass(),
            'causer_id' => $stranger->id,
            'event' => 'updated',
            'properties' => ['attributes' => ['nom' => 'X'], 'old' => ['nom' => 'Y']],
        ]);

        // Guessing the id must NOT reveal it.
        $this->actingAs($reader)
            ->get(route('backoffice.9wiwid.show', $entry->id))
            ->assertNotFound();
    }

    public function test_a_reader_still_opens_an_entry_within_its_own_centre(): void
    {
        $mine = Etablissement::factory()->create();
        $reader = $this->readerIn($mine);

        $colleague = User::factory()->create();
        $colleagueEmployee = Employee::factory()->create([
            'user_id' => $colleague->id,
            'etablissement_id' => $mine->id,
        ]);
        $colleagueEmployee->syncEtablissements([$mine->id]);

        $entry = Activity::create([
            'log_name' => 'employee',
            'description' => 'Employé modifié',
            'causer_type' => (new User)->getMorphClass(),
            'causer_id' => $colleague->id,
            'event' => 'updated',
            'properties' => ['attributes' => ['nom' => 'X'], 'old' => ['nom' => 'Y']],
        ]);

        $this->actingAs($reader)
            ->get(route('backoffice.9wiwid.show', $entry->id))
            ->assertOk();
    }

    /**
     * A super-admin has global reach (causerScope() returns null), so the
     * detail page stays fully readable — the fix must not over-restrict.
     */
    public function test_a_super_admin_can_open_any_entry(): void
    {
        $foreign = Etablissement::factory()->create();

        $stranger = User::factory()->create();
        $strangerEmployee = Employee::factory()->create([
            'user_id' => $stranger->id,
            'etablissement_id' => $foreign->id,
        ]);
        $strangerEmployee->syncEtablissements([$foreign->id]);

        $entry = Activity::create([
            'log_name' => 'employee',
            'description' => 'Employé modifié',
            'causer_type' => (new User)->getMorphClass(),
            'causer_id' => $stranger->id,
            'event' => 'updated',
            'properties' => ['attributes' => ['nom' => 'X'], 'old' => ['nom' => 'Y']],
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin->fresh())
            ->get(route('backoffice.9wiwid.show', $entry->id))
            ->assertOk();
    }
}
