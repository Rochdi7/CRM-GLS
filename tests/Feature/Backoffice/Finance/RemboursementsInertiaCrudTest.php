<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Remboursements endpoints (RemboursementController
 * store/update, served from the Depenses tabbed page) built alongside the
 * unchanged Livewire RemboursementsIndex fallback — see
 * RemboursementsCrudTest for the Livewire-side coverage of the same
 * business rules (docs/phase-10-finance-audit.md §2.6). No maximum-refund
 * check is added (docs/phase-10-finance-mapping.md Q1: preserved) and no
 * detail page is added (Q2: preserved).
 */
final class RemboursementsInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
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

    public function test_depenses_index_exposes_remboursements_when_permitted(): void
    {
        $this->actingAs($this->userWith('refunds.view'))
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewDepenses', false)
                ->where('canViewRemboursements', true)
                ->has('remboursements')
                ->has('students')
            );
    }

    public function test_a_remboursement_can_be_created_and_decrements_the_caisse(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 1000]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '150',
            'date_remboursement' => '2025-09-20',
            'motif' => 'Annulation',
        ])->assertRedirect(route('backoffice.depenses.index', ['tab' => 'remboursements']));

        $remboursement = Remboursement::where('beneficiaire_id', $student->id)->first();
        $this->assertNotNull($remboursement);
        $this->assertStringStartsWith('RMB-', $remboursement->reference);
        $this->assertSame('850.00', (string) $caisse->fresh()->solde);
    }

    public function test_no_maximum_refund_amount_check_exists(): void
    {
        // Confirms the deliberate absence of a cap (docs/phase-10-finance-
        // mapping.md Q1) — a refund larger than the till's balance is still
        // accepted, matching current live behavior exactly.
        $user = $this->userWith('refunds.view', 'refunds.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 100]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '5000',
            'date_remboursement' => '2025-09-20',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('-4900.00', (string) $caisse->fresh()->solde);
    }

    public function test_montant_and_caisse_are_frozen_on_update_even_when_tampered(): void
    {
        $user = $this->userWith('refunds.view', 'refunds.create', 'refunds.update');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 1000]);
        $otherCaisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $remboursement = Remboursement::create([
            'reference' => 'RMB-EDIT', 'beneficiaire_id' => $student->id, 'caisse_id' => $caisse->id,
            'montant' => 150, 'date_remboursement' => '2025-09-20', 'agent_id' => $user->employee->id,
        ]);

        $this->put(route('backoffice.remboursements.update', $remboursement), [
            'date_remboursement' => '2025-09-21',
            'motif' => 'Corrigé',
            // Tampered — must have zero effect.
            'montant' => '9999',
            'caisse_id' => $otherCaisse->id,
            'beneficiaire_id' => Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
        ])->assertSessionDoesntHaveErrors();

        $fresh = $remboursement->fresh();
        $this->assertSame('150.00', (string) $fresh->montant);
        $this->assertSame($caisse->id, $fresh->caisse_id);
        $this->assertSame($student->id, $fresh->beneficiaire_id);
        $this->assertSame('Corrigé', $fresh->motif);
    }

    public function test_no_delete_route_and_no_show_route_exist_for_refunds(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.remboursements.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.remboursements.show'));
    }

    public function test_create_is_forbidden_without_the_permission(): void
    {
        $this->actingAs($this->userWith('refunds.view'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'montant' => '150',
            'date_remboursement' => '2025-09-20',
        ])->assertForbidden();
    }
}
