<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CenterAccessTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centerA;

    private Etablissement $centerB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centerA = Etablissement::factory()->create();
        $this->centerB = Etablissement::factory()->create();
    }

    /** A user whose employee profile is assigned to center A. */
    private function userInCenterA(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        Employee::factory()->create([
            'user_id' => $user->id, // observer skips credential creation
            'etablissement_id' => $this->centerA->id,
        ]);

        return $user->fresh();
    }

    public function test_service_confines_a_user_to_the_assigned_center(): void
    {
        $service = app(CenterAccessService::class);
        $user = $this->userInCenterA('administrative-assistant');

        $this->assertSame([$this->centerA->id], $service->accessibleCenterIds($user));
        $this->assertTrue($service->canAccessCenter($user, $this->centerA->id));
        $this->assertFalse($service->canAccessCenter($user, $this->centerB->id));
        // Records without a center are global.
        $this->assertTrue($service->canAccessCenter($user, null));
    }

    public function test_centers_access_all_opens_every_center(): void
    {
        $service = app(CenterAccessService::class);
        $user = $this->userInCenterA('director'); // director has centers.access-all

        $this->assertTrue($service->canAccessCenter($user, $this->centerB->id));
    }

    public function test_scope_accessible_centers_filters_queries(): void
    {
        Student::factory()->create(['etablissement_id' => $this->centerA->id]);
        Student::factory()->create(['etablissement_id' => $this->centerB->id]);
        Student::factory()->create(['etablissement_id' => null]);

        $assistant = $this->userInCenterA('administrative-assistant');
        $director = $this->userInCenterA('director');

        $service = app(CenterAccessService::class);

        // Assistant: own center + global rows. Director: everything.
        $this->assertSame(2, $service->scopeAccessibleCenters(Student::query(), $assistant)->count());
        $this->assertSame(3, $service->scopeAccessibleCenters(Student::query(), $director)->count());
    }

    public function test_policies_block_records_from_another_center(): void
    {
        $assistant = $this->userInCenterA('administrative-assistant');

        $studentA = Student::factory()->create(['etablissement_id' => $this->centerA->id]);
        $studentB = Student::factory()->create(['etablissement_id' => $this->centerB->id]);

        $this->assertTrue($assistant->can('view', $studentA));
        $this->assertTrue($assistant->can('update', $studentA));
        $this->assertFalse($assistant->can('view', $studentB));
        $this->assertFalse($assistant->can('update', $studentB));
    }

    public function test_manipulating_a_record_id_from_another_center_returns_403(): void
    {
        $assistant = $this->userInCenterA('administrative-assistant');
        $studentB = Student::factory()->create(['etablissement_id' => $this->centerB->id]);

        // Horizontal privilege escalation via URL id — the center-scoped
        // StudentPolicy::view blocks another center's record server-side (403).
        $this->actingAs($assistant)
            ->get(route('backoffice.students.show', $studentB))
            ->assertForbidden();
    }

    public function test_teacher_sees_groups_but_cannot_touch_finance_records(): void
    {
        $teacher = $this->userInCenterA('teacher');

        $group = Group::factory()->create(['etablissement_id' => $this->centerA->id]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centerA->id]);

        $this->assertTrue($teacher->can('view', $group));
        $this->assertFalse($teacher->can('update', $group));
        $this->assertFalse($teacher->can('view', $caisse));

        $this->actingAs($teacher)
            ->get(route('backoffice.caisses.index'))
            ->assertForbidden();
    }

    public function test_indirectly_scoped_records_follow_their_till_center(): void
    {
        $assistant = $this->userInCenterA('administrative-assistant');
        $caisseB = Caisse::factory()->create(['etablissement_id' => $this->centerB->id]);

        $this->assertFalse($assistant->can('view', $caisseB));
    }

    public function test_a_user_with_permission_but_no_employee_profile_only_sees_global_records(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrative-assistant'); // no employee row

        $globalStudent = Student::factory()->create(['etablissement_id' => null]);
        $centerStudent = Student::factory()->create(['etablissement_id' => $this->centerA->id]);

        $this->assertTrue($user->can('view', $globalStudent));
        $this->assertFalse($user->can('view', $centerStudent));
    }

    // --- Multi-center employees --------------------------------------------

    public function test_a_multi_center_employee_can_access_every_assigned_center(): void
    {
        $service = app(CenterAccessService::class);

        $user = User::factory()->create();
        $user->assignRole('administrative-assistant'); // no centers.access-all

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centerA->id,
        ]);
        $employee->etablissements()->sync([$this->centerA->id, $this->centerB->id]);

        $user = $user->fresh();

        $this->assertEqualsCanonicalizing(
            [$this->centerA->id, $this->centerB->id],
            $service->accessibleCenterIds($user),
        );
        $this->assertTrue($service->canAccessCenter($user, $this->centerA->id));
        // The second center is reachable ONLY through the pivot.
        $this->assertTrue($service->canAccessCenter($user, $this->centerB->id));
    }

    public function test_a_multi_center_employee_sees_records_from_all_its_centers(): void
    {
        $studentA = Student::factory()->create(['etablissement_id' => $this->centerA->id]);
        $studentB = Student::factory()->create(['etablissement_id' => $this->centerB->id]);
        $centerC = Etablissement::factory()->create();
        $studentC = Student::factory()->create(['etablissement_id' => $centerC->id]);

        $user = User::factory()->create();
        $user->assignRole('administrative-assistant');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centerA->id,
        ]);
        $employee->etablissements()->sync([$this->centerA->id, $this->centerB->id]);

        $user = $user->fresh();

        $this->assertTrue($user->can('view', $studentA));
        $this->assertTrue($user->can('view', $studentB));
        // A center they are NOT assigned to stays out of reach.
        $this->assertFalse($user->can('view', $studentC));
    }

    public function test_a_multi_center_employee_may_switch_between_its_own_centers_only(): void
    {
        $centerC = Etablissement::factory()->create();

        $user = User::factory()->create();
        $user->assignRole('administrative-assistant'); // cannot switch centers today

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centerA->id,
        ]);
        $employee->etablissements()->sync([$this->centerA->id, $this->centerB->id]);

        $this->actingAs($user->fresh());

        $context = app(CurrentContext::class);

        // Holding two centers unlocks the top-bar switcher...
        $this->assertTrue($context->canSwitchCenter());
        // ...and it offers exactly those two, never every center in the app.
        $this->assertEqualsCanonicalizing(
            [$this->centerA->id, $this->centerB->id],
            $context->availableCentres()->pluck('id')->all(),
        );

        // Selecting one of their own centers is honored.
        $context->setEtablissement($this->centerB->id);
        $this->assertSame($this->centerB->id, $context->etablissementId());

        // A center they don't hold is silently refused (stays on B).
        $context->setEtablissement($centerC->id);
        $this->assertSame($this->centerB->id, $context->etablissementId());
    }

    public function test_a_single_center_employee_still_cannot_switch_centers(): void
    {
        $assistant = $this->userInCenterA('administrative-assistant');
        $this->actingAs($assistant);

        $context = app(CurrentContext::class);

        $this->assertFalse($context->canSwitchCenter());
        $this->assertSame($this->centerA->id, $context->etablissementId());
        $this->assertFalse($context->isAllCenters());

        // Attempting to widen to "all centers" must not work.
        $context->setEtablissement(null);
        $this->assertSame($this->centerA->id, $context->etablissementId());
    }
}
