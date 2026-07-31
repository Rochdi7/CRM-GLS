<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Caisse Transfers endpoints
 * (CaisseTransferController store/update/validateAction, served from the
 * Caisses tabbed page) built alongside the unchanged Livewire
 * CaisseTransfersIndex fallback — see CaisseTransfersTest for the
 * Livewire-side coverage of the same business rules
 * (docs/phase-10-finance-audit.md §2.3).
 */
final class CaisseTransfersInertiaCrudTest extends TestCase
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

    private function caisse(float $solde = 1000): Caisse
    {
        return Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => $solde]);
    }

    public function test_caisses_index_exposes_transfers_when_permitted(): void
    {
        $this->actingAs($this->userWith('cash-transfers.view'))
            ->get(route('backoffice.caisses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canViewTransfers', true)
                ->has('transfers')
                ->has('transferCaisses')
            );
    }

    public function test_requesting_a_transfer_does_not_move_any_balance(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($user);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => '300',
            'note' => 'Réapprovisionnement',
        ])->assertRedirect(route('backoffice.caisses.index', ['tab' => 'transferts']));

        $transfer = CaisseTransfer::first();
        $this->assertNotNull($transfer);
        $this->assertStringStartsWith('TRF-', $transfer->reference);
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->statut);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
        $this->assertSame('500.00', (string) $destination->fresh()->solde);
        $this->assertSame('1000.00', (string) $transfer->solde_source_avant);
        $this->assertNull($transfer->solde_source_apres);
    }

    public function test_same_caisse_source_and_destination_is_rejected(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($user);
        $caisse = $this->caisse();

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $caisse->id,
            'caisse_destination_id' => $caisse->id,
            'montant' => '100',
        ])->assertSessionHasErrors('caisse_destination_id');
    }

    public function test_validation_by_a_different_employee_moves_both_balances(): void
    {
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $validator = $this->userWith('cash-transfers.view', 'cash-transfers.validate');
        $this->actingAs($validator);

        $this->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertRedirect(route('backoffice.caisses.index', ['tab' => 'transferts']));

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_VALIDE, $fresh->statut);
        $this->assertSame($validator->employee->id, $fresh->validated_by);
        $this->assertSame('700.00', (string) $source->fresh()->solde);
        $this->assertSame('800.00', (string) $destination->fresh()->solde);
        $this->assertSame('700.00', (string) $fresh->solde_source_apres);
        $this->assertSame('800.00', (string) $fresh->solde_dest_apres);
    }

    public function test_self_validation_is_refused_and_leaves_balances_unchanged(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create', 'cash-transfers.validate');
        $this->actingAs($user);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $this->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertSessionHasErrors('validate');

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $fresh->statut);
        $this->assertNull($fresh->validated_by);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
        $this->assertSame('500.00', (string) $destination->fresh()->solde);
    }

    public function test_validation_requires_the_validate_permission(): void
    {
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id, 'caisse_destination_id' => $destination->id, 'montant' => '100',
        ]);
        $transfer = CaisseTransfer::first();

        $this->actingAs($this->userWith('cash-transfers.view', 'cash-transfers.create'))
            ->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertForbidden();
    }

    public function test_a_validated_transfer_is_frozen_and_cannot_be_double_validated(): void
    {
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id, 'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $validator = $this->userWith('cash-transfers.view', 'cash-transfers.validate');
        $this->actingAs($validator);
        $this->put(route('backoffice.caisse-transfers.validate', $transfer))->assertRedirect();

        // Second validation attempt must fail and not double-move money.
        $this->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertSessionHasErrors('validate');

        $this->assertSame('700.00', (string) $source->fresh()->solde);
        $this->assertSame('800.00', (string) $destination->fresh()->solde);
    }

    public function test_pending_transfer_editability_only_note_sticks(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create', 'cash-transfers.update');
        $this->actingAs($user);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id, 'caisse_destination_id' => $destination->id, 'montant' => '300', 'note' => 'Initial',
        ]);
        $transfer = CaisseTransfer::first();
        $otherCaisse = $this->caisse(200);

        $this->put(route('backoffice.caisse-transfers.update', $transfer), [
            'note' => 'Corrigé',
            // Tampered — structurally absent from update rules, must have zero effect.
            'montant' => '9999',
            'caisse_source_id' => $otherCaisse->id,
        ])->assertSessionDoesntHaveErrors();

        $fresh = $transfer->fresh();
        $this->assertSame('Corrigé', $fresh->note);
        $this->assertSame('300.00', (string) $fresh->montant);
        $this->assertSame($source->id, $fresh->caisse_source_id);
    }

    public function test_a_validated_transfer_cannot_be_edited(): void
    {
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create', 'cash-transfers.update');
        $this->actingAs($requester);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id, 'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $validator = $this->userWith('cash-transfers.view', 'cash-transfers.validate');
        $this->actingAs($validator);
        $this->put(route('backoffice.caisse-transfers.validate', $transfer));

        $this->actingAs($requester);
        $this->put(route('backoffice.caisse-transfers.update', $transfer), ['note' => 'Trop tard'])
            ->assertSessionHasErrors('note');

        $this->assertNotSame('Trop tard', $transfer->fresh()->note);
    }

    public function test_cancelling_a_pending_transfer_moves_no_money(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create', 'cash-transfers.update');
        $this->actingAs($user);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id, 'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $this->put(route('backoffice.caisse-transfers.update', $transfer), [
            'note' => $transfer->note ?? '',
            'statut' => CaisseTransfer::STATUT_ANNULE,
        ])->assertSessionDoesntHaveErrors();

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_ANNULE, $fresh->statut);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
        $this->assertSame('500.00', (string) $destination->fresh()->solde);
    }

    public function test_no_employee_record_is_refused_with_no_side_effects(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('cash-transfers.view', 'cash-transfers.create', 'centers.access-all');
        $this->actingAs($user->fresh());
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id, 'caisse_destination_id' => $destination->id, 'montant' => '300',
        ])->assertSessionHasErrors('caisse_source_id');

        $this->assertSame(0, CaisseTransfer::count());
    }

    public function test_no_destroy_route_exists(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.caisse-transfers.destroy'));
    }

    public function test_cross_center_validation_is_denied(): void
    {
        $otherCentre = Etablissement::factory()->create();
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $this->caisse(1000);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $source->id, 'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $lockedValidator = User::factory()->create();
        $lockedValidator->givePermissionTo('cash-transfers.view', 'cash-transfers.validate');
        Employee::factory()->create(['user_id' => $lockedValidator->id, 'etablissement_id' => $otherCentre->id]);
        $this->actingAs($lockedValidator->fresh());

        $this->put(route('backoffice.caisse-transfers.validate', $transfer))->assertForbidden();
    }
}
