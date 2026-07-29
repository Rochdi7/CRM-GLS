<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Livewire\Backoffice\Remboursements\RemboursementsIndex;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

final class RemboursementsCrudTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    /** Super-admin WITH a linked employee (money operations need one). */
    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($user->fresh());

        return $user;
    }

    private function caisse(float $solde = 1000): Caisse
    {
        return Caisse::factory()->create([
            'etablissement_id' => $this->centre->id,
            'solde' => $solde,
        ]);
    }

    public function test_page_requires_refunds_view(): void
    {
        // The route is still bound to the legacy controller (the routes patch
        // swaps it to RemboursementsIndex); the permission gate is asserted on
        // the endpoint, the rendering on the Livewire component below.
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.remboursements.index'))->assertForbidden();

        $u2 = User::factory()->create();
        $u2->givePermissionTo('refunds.view');
        $this->actingAs($u2);
        Livewire::test(RemboursementsIndex::class)->assertOk();
    }

    public function test_create_preselects_the_signed_in_employees_till_and_shows_its_balance(): void
    {
        $user = $this->admin();
        // Every employee owns a till, auto-provisioned by EmployeeObserver.
        $own = Caisse::query()->where('responsable_employee_id', $user->employee->id)->firstOrFail();
        $own->update(['solde' => 750.5]);

        Livewire::test(RemboursementsIndex::class)
            ->call('create')
            ->assertSet('caisse_id', $own->id)
            ->assertSee(number_format(750.5, 2));
    }

    public function test_the_component_renders_for_an_allowed_user(): void
    {
        $this->admin();
        $caisse = $this->caisse();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        Remboursement::create([
            'reference' => 'RMB-000001',
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => 250,
            'date_remboursement' => '2026-05-10',
            'motif' => 'Annulation inscription',
            'agent_id' => Employee::factory()->create(['etablissement_id' => $this->centre->id])->id,
        ]);

        Livewire::test(RemboursementsIndex::class)
            ->assertOk()
            ->assertSee('RMB-000001')
            ->assertSee($student->nom);
    }

    public function test_a_user_without_the_view_permission_cannot_mount_the_component(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u);

        Livewire::test(RemboursementsIndex::class)->assertForbidden();
    }

    public function test_creating_a_refund_requires_the_create_permission(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('refunds.view');
        Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        Livewire::test(RemboursementsIndex::class)
            ->call('create')
            ->assertForbidden();
    }

    public function test_the_amount_and_beneficiary_are_required(): void
    {
        $this->admin();

        Livewire::test(RemboursementsIndex::class)
            ->call('create')
            ->set('montant', '')
            ->set('beneficiaire_id', null)
            ->set('caisse_id', null)
            ->call('save')
            ->assertHasErrors(['montant', 'beneficiaire_id', 'caisse_id']);
    }

    public function test_the_amount_must_be_positive(): void
    {
        $this->admin();
        $caisse = $this->caisse();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        Livewire::test(RemboursementsIndex::class)
            ->call('create')
            ->set('beneficiaire_id', $student->id)
            ->set('caisse_id', $caisse->id)
            ->set('montant', '0')
            ->call('save')
            ->assertHasErrors('montant');
    }

    public function test_recording_a_refund_decreases_the_till_balance_and_generates_an_rmb_reference(): void
    {
        $this->admin();
        $caisse = $this->caisse(1000);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        Livewire::test(RemboursementsIndex::class)
            ->call('create')
            ->set('beneficiaire_id', $student->id)
            ->set('caisse_id', $caisse->id)
            ->set('montant', '250')
            ->set('date_remboursement', '2026-05-10')
            ->set('motif', 'Annulation inscription')
            ->call('save')
            ->assertHasNoErrors();

        $refund = Remboursement::firstWhere('beneficiaire_id', $student->id);
        $this->assertNotNull($refund);
        // The Domain action generated the reference…
        $this->assertStringStartsWith('RMB-', $refund->reference);
        // …and decremented the till in the same transaction.
        $this->assertEquals(750.0, (float) $caisse->fresh()->solde);
        // The acting employee is stamped on the record.
        $this->assertSame(auth()->user()->employee->id, $refund->agent_id);
    }

    public function test_a_refund_amount_and_till_are_immutable_after_creation(): void
    {
        $this->admin();
        $caisse = $this->caisse(1000);
        $other = $this->caisse(500);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        Livewire::test(RemboursementsIndex::class)
            ->call('create')
            ->set('beneficiaire_id', $student->id)
            ->set('caisse_id', $caisse->id)
            ->set('montant', '250')
            ->set('date_remboursement', '2026-05-10')
            ->call('save')
            ->assertHasNoErrors();

        $refund = Remboursement::firstWhere('beneficiaire_id', $student->id);

        // Editing: trying to change the amount and the till has no effect.
        Livewire::test(RemboursementsIndex::class)
            ->call('edit', $refund->id)
            ->set('montant', '999')
            ->set('caisse_id', $other->id)
            ->set('motif', 'Motif corrigé')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $refund->fresh();
        $this->assertEquals(250.0, (float) $fresh->montant);
        $this->assertSame($caisse->id, $fresh->caisse_id);
        // Only the editable fields changed.
        $this->assertSame('Motif corrigé', $fresh->motif);
        // And no extra money moved.
        $this->assertEquals(750.0, (float) $caisse->fresh()->solde);
        $this->assertEquals(500.0, (float) $other->fresh()->solde);
    }

    public function test_refunds_are_never_deleted_and_have_no_destroy_route(): void
    {
        // Money records are never deleted (schema §14) — no destroy route…
        $this->assertFalse(Route::has('backoffice.remboursements.destroy'));
        // …and the component exposes no delete method.
        $this->assertFalse(method_exists(RemboursementsIndex::class, 'delete'));
    }

    public function test_the_list_can_be_searched_and_filtered_by_till_and_date(): void
    {
        $this->admin();
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $c1 = $this->caisse();
        $c2 = $this->caisse();
        $s1 = Student::factory()->create(['nom' => 'Bennani', 'etablissement_id' => $this->centre->id]);
        $s2 = Student::factory()->create(['nom' => 'Cherkaoui', 'etablissement_id' => $this->centre->id]);

        Remboursement::create(['reference' => 'RMB-000001', 'beneficiaire_id' => $s1->id, 'caisse_id' => $c1->id, 'montant' => 100, 'date_remboursement' => '2026-01-10', 'agent_id' => $agent->id]);
        Remboursement::create(['reference' => 'RMB-000002', 'beneficiaire_id' => $s2->id, 'caisse_id' => $c2->id, 'montant' => 200, 'date_remboursement' => '2026-06-10', 'agent_id' => $agent->id]);

        Livewire::test(RemboursementsIndex::class)
            // Search by beneficiary name.
            ->set('search', 'Bennani')
            ->assertSee('RMB-000001')
            ->assertDontSee('RMB-000002')
            // Search by reference.
            ->set('search', 'RMB-000002')
            ->assertSee('RMB-000002')
            ->assertDontSee('RMB-000001')
            ->set('search', '')
            // Till filter.
            ->set('caisseFilter', (string) $c2->id)
            ->assertSee('RMB-000002')
            ->assertDontSee('RMB-000001')
            ->set('caisseFilter', '')
            // Date range filter.
            ->set('dateFrom', '2026-05-01')
            ->assertSee('RMB-000002')
            ->assertDontSee('RMB-000001')
            ->set('dateTo', '2026-05-31')
            ->assertDontSee('RMB-000002');
    }

    public function test_refunds_of_another_center_are_not_listed(): void
    {
        $other = Etablissement::factory()->create();
        $agent = Employee::factory()->create(['etablissement_id' => $other->id]);
        $foreignCaisse = Caisse::factory()->create(['etablissement_id' => $other->id, 'solde' => 500]);
        $foreignStudent = Student::factory()->create(['etablissement_id' => $other->id]);
        Remboursement::create(['reference' => 'RMB-999999', 'beneficiaire_id' => $foreignStudent->id, 'caisse_id' => $foreignCaisse->id, 'montant' => 50, 'date_remboursement' => '2026-02-02', 'agent_id' => $agent->id]);

        // Center-scoped user (no centers.access-all) bound to $this->centre.
        $u = User::factory()->create();
        $u->givePermissionTo(['refunds.view', 'refunds.create']);
        Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        Livewire::test(RemboursementsIndex::class)->assertDontSee('RMB-999999');
    }
}
