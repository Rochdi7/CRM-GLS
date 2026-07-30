<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Livewire\Backoffice\CaisseTransfers\CaisseTransfersIndex;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

final class CaisseTransfersTest extends TestCase
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

    public function test_page_requires_cash_transfers_view(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.caisse-transfers.index'))->assertForbidden();

        // The route is still bound to the legacy controller (the routes patch
        // swaps it to CaisseTransfersIndex); rendering is asserted on the
        // Livewire component itself.
        $u2 = User::factory()->create();
        $u2->givePermissionTo('cash-transfers.view');
        $this->actingAs($u2);
        Livewire::test(CaisseTransfersIndex::class)->assertOk();
    }

    public function test_a_user_without_the_view_permission_cannot_mount_the_component(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u);

        Livewire::test(CaisseTransfersIndex::class)->assertForbidden();
    }

    public function test_requesting_a_transfer_requires_the_create_permission(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('cash-transfers.view');
        Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->assertForbidden();
    }

    public function test_requesting_a_transfer_does_not_move_any_balance(): void
    {
        $this->admin();
        $source = $this->caisse(1000);
        $destination = $this->caisse(200);

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->set('caisse_source_id', $source->id)
            ->set('caisse_destination_id', $destination->id)
            ->set('montant', '300')
            ->set('note', 'Réapprovisionnement')
            ->call('save')
            ->assertHasNoErrors();

        $transfer = CaisseTransfer::first();
        $this->assertNotNull($transfer);
        $this->assertStringStartsWith('TRF-', $transfer->reference);
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->statut);
        $this->assertNull($transfer->validated_by);

        // ⚠ Balances are UNTOUCHED at request time (structure doc §7).
        $this->assertEquals(1000.0, (float) $source->fresh()->solde);
        $this->assertEquals(200.0, (float) $destination->fresh()->solde);
        // Only the *_avant snapshots were captured.
        $this->assertEquals(1000.0, (float) $transfer->solde_source_avant);
        $this->assertEquals(200.0, (float) $transfer->solde_dest_avant);
        $this->assertNull($transfer->solde_source_apres);
    }

    public function test_source_and_destination_must_differ_and_the_amount_is_required(): void
    {
        $this->admin();
        $source = $this->caisse();

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->set('caisse_source_id', $source->id)
            ->set('caisse_destination_id', $source->id)
            ->set('montant', '')
            ->call('save')
            ->assertHasErrors(['caisse_destination_id', 'montant']);
    }

    public function test_validation_by_a_different_employee_moves_both_balances(): void
    {
        // Requester.
        $requester = User::factory()->create();
        $requester->assignRole(Role::SUPER_ADMIN);
        $requesterEmp = Employee::factory()->create(['user_id' => $requester->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($requester->fresh());

        $source = $this->caisse(1000);
        $destination = $this->caisse(200);

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->set('caisse_source_id', $source->id)
            ->set('caisse_destination_id', $destination->id)
            ->set('montant', '300')
            ->call('save')
            ->assertHasNoErrors();

        $transfer = CaisseTransfer::first();
        $this->assertSame($requesterEmp->id, $transfer->requested_by);

        // A DIFFERENT employee validates.
        $validator = User::factory()->create();
        $validator->assignRole(Role::SUPER_ADMIN);
        $validatorEmp = Employee::factory()->create(['user_id' => $validator->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($validator->fresh());

        Livewire::test(CaisseTransfersIndex::class)
            ->call('validateTransfer', $transfer->id)
            ->assertHasNoErrors();

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_VALIDE, $fresh->statut);
        $this->assertSame($validatorEmp->id, $fresh->validated_by);

        // Balances moved here, and only here.
        $this->assertEquals(700.0, (float) $source->fresh()->solde);
        $this->assertEquals(500.0, (float) $destination->fresh()->solde);
        $this->assertEquals(700.0, (float) $fresh->solde_source_apres);
        $this->assertEquals(500.0, (float) $fresh->solde_dest_apres);
    }

    public function test_self_validation_is_refused_and_leaves_the_balances_unchanged(): void
    {
        $this->admin();
        $source = $this->caisse(1000);
        $destination = $this->caisse(200);

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->set('caisse_source_id', $source->id)
            ->set('caisse_destination_id', $destination->id)
            ->set('montant', '300')
            ->call('save')
            ->assertHasNoErrors();

        $transfer = CaisseTransfer::first();

        // The requester tries to validate his OWN transfer → refused by the
        // Domain action, surfaced as an error (never bypassed).
        Livewire::test(CaisseTransfersIndex::class)
            ->call('validateTransfer', $transfer->id)
            ->assertHasErrors('validate');

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $fresh->statut);
        $this->assertNull($fresh->validated_by);
        // ⚠ No money moved.
        $this->assertEquals(1000.0, (float) $source->fresh()->solde);
        $this->assertEquals(200.0, (float) $destination->fresh()->solde);
    }

    public function test_validating_requires_the_validate_permission(): void
    {
        $this->admin();
        $source = $this->caisse(1000);
        $destination = $this->caisse(200);

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->set('caisse_source_id', $source->id)
            ->set('caisse_destination_id', $destination->id)
            ->set('montant', '300')
            ->call('save')
            ->assertHasNoErrors();

        $transfer = CaisseTransfer::first();

        // Another employee WITHOUT cash-transfers.validate.
        $u = User::factory()->create();
        $u->givePermissionTo(['cash-transfers.view', 'cash-transfers.create']);
        Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        Livewire::test(CaisseTransfersIndex::class)
            ->call('validateTransfer', $transfer->id)
            ->assertForbidden();

        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
        $this->assertEquals(1000.0, (float) $source->fresh()->solde);
    }

    public function test_a_validated_transfer_cannot_be_edited_or_revalidated(): void
    {
        $requester = User::factory()->create();
        $requester->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $requester->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($requester->fresh());

        $source = $this->caisse(1000);
        $destination = $this->caisse(200);

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->set('caisse_source_id', $source->id)
            ->set('caisse_destination_id', $destination->id)
            ->set('montant', '300')
            ->call('save');

        $transfer = CaisseTransfer::first();

        $validator = User::factory()->create();
        $validator->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $validator->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($validator->fresh());

        Livewire::test(CaisseTransfersIndex::class)->call('validateTransfer', $transfer->id);
        $this->assertSame(CaisseTransfer::STATUT_VALIDE, $transfer->fresh()->statut);

        // A validated transfer is frozen: the edit modal refuses to open…
        Livewire::test(CaisseTransfersIndex::class)
            ->call('edit', $transfer->id)
            ->assertSet('showModal', false)
            ->assertSet('editingId', null);

        // …and it can never be validated twice (no double money move).
        Livewire::test(CaisseTransfersIndex::class)
            ->call('validateTransfer', $transfer->id)
            ->assertHasErrors('validate');

        $this->assertEquals(700.0, (float) $source->fresh()->solde);
        $this->assertEquals(500.0, (float) $destination->fresh()->solde);
    }

    public function test_a_pending_transfer_can_be_edited_and_cancelled(): void
    {
        $this->admin();
        $source = $this->caisse(1000);
        $destination = $this->caisse(200);

        Livewire::test(CaisseTransfersIndex::class)
            ->call('create')
            ->set('caisse_source_id', $source->id)
            ->set('caisse_destination_id', $destination->id)
            ->set('montant', '300')
            ->set('note', 'Note initiale')
            ->call('save')
            ->assertHasNoErrors();

        $transfer = CaisseTransfer::first();

        // Only the note is editable; the amount stays frozen.
        Livewire::test(CaisseTransfersIndex::class)
            ->call('edit', $transfer->id)
            ->assertSet('showModal', true)
            ->set('note', 'Note corrigée')
            ->set('montant', '9999')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $transfer->fresh();
        $this->assertSame('Note corrigée', $fresh->note);
        $this->assertEquals(300.0, (float) $fresh->montant);

        // Cancelling a pending transfer never moves money.
        Livewire::test(CaisseTransfersIndex::class)->call('cancel', $transfer->id);
        $this->assertSame(CaisseTransfer::STATUT_ANNULE, $transfer->fresh()->statut);
        $this->assertEquals(1000.0, (float) $source->fresh()->solde);
        $this->assertEquals(200.0, (float) $destination->fresh()->solde);
    }

    public function test_status_tabs_filter_the_transfers_list(): void
    {
        $this->admin();
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $c1 = $this->caisse();
        $c2 = $this->caisse();

        $base = ['caisse_source_id' => $c1->id, 'caisse_destination_id' => $c2->id, 'montant' => 100, 'date_transfert' => now(), 'requested_by' => $agent->id];
        CaisseTransfer::create([...$base, 'reference' => 'TRF-000001', 'statut' => CaisseTransfer::STATUT_EN_ATTENTE]);
        CaisseTransfer::create([...$base, 'reference' => 'TRF-000002', 'statut' => CaisseTransfer::STATUT_VALIDE]);
        CaisseTransfer::create([...$base, 'reference' => 'TRF-000003', 'statut' => CaisseTransfer::STATUT_ANNULE]);

        Livewire::test(CaisseTransfersIndex::class)
            // Default tab = En attente.
            ->assertSet('statutFilter', CaisseTransfer::STATUT_EN_ATTENTE)
            ->assertSee('TRF-000001')
            ->assertDontSee('TRF-000002')
            ->assertDontSee('TRF-000003')
            ->call('setStatutTab', CaisseTransfer::STATUT_VALIDE)
            ->assertSee('TRF-000002')
            ->assertDontSee('TRF-000001')
            ->call('setStatutTab', CaisseTransfer::STATUT_ANNULE)
            ->assertSee('TRF-000003')
            ->assertDontSee('TRF-000001')
            // An unknown status is ignored.
            ->call('setStatutTab', 'Nimporte')
            ->assertSet('statutFilter', CaisseTransfer::STATUT_ANNULE);
    }

    public function test_the_list_can_be_searched_and_filtered_by_source_till(): void
    {
        $this->admin();
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $c1 = $this->caisse();
        $c2 = $this->caisse();
        $c3 = $this->caisse();

        CaisseTransfer::create(['reference' => 'TRF-000001', 'caisse_source_id' => $c1->id, 'caisse_destination_id' => $c3->id, 'montant' => 100, 'date_transfert' => now(), 'statut' => CaisseTransfer::STATUT_EN_ATTENTE, 'requested_by' => $agent->id]);
        CaisseTransfer::create(['reference' => 'TRF-000002', 'caisse_source_id' => $c2->id, 'caisse_destination_id' => $c3->id, 'montant' => 200, 'date_transfert' => now(), 'statut' => CaisseTransfer::STATUT_EN_ATTENTE, 'requested_by' => $agent->id]);

        Livewire::test(CaisseTransfersIndex::class)
            ->set('search', 'TRF-000002')
            ->assertSee('TRF-000002')
            ->assertDontSee('TRF-000001')
            ->set('search', '')
            ->set('caisseFilter', (string) $c1->id)
            ->assertSee('TRF-000001')
            ->assertDontSee('TRF-000002');
    }

    public function test_transfers_of_another_center_are_not_listed(): void
    {
        $other = Etablissement::factory()->create();
        $agent = Employee::factory()->create(['etablissement_id' => $other->id]);
        $fs = Caisse::factory()->create(['etablissement_id' => $other->id, 'solde' => 500]);
        $fd = Caisse::factory()->create(['etablissement_id' => $other->id, 'solde' => 500]);
        CaisseTransfer::create(['reference' => 'TRF-999999', 'caisse_source_id' => $fs->id, 'caisse_destination_id' => $fd->id, 'montant' => 50, 'date_transfert' => now(), 'statut' => CaisseTransfer::STATUT_EN_ATTENTE, 'requested_by' => $agent->id]);

        $u = User::factory()->create();
        $u->givePermissionTo(['cash-transfers.view', 'cash-transfers.create']);
        Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        Livewire::test(CaisseTransfersIndex::class)->assertDontSee('TRF-999999');
    }

    public function test_transfers_are_never_deleted_and_have_no_destroy_route(): void
    {
        // Money records are never deleted (schema §15) — no destroy route…
        $this->assertFalse(Route::has('backoffice.caisse-transfers.destroy'));
        // …and the component exposes no delete method.
        $this->assertFalse(method_exists(CaisseTransfersIndex::class, 'delete'));
    }

    public function test_the_detail_page_renders_the_balance_snapshots(): void
    {
        $this->admin();
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $source = $this->caisse(1000);
        $destination = $this->caisse(200);

        $transfer = CaisseTransfer::create([
            'reference' => 'TRF-000042',
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => 300,
            'date_transfert' => now(),
            'solde_source_avant' => 1000,
            'solde_dest_avant' => 200,
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'requested_by' => $agent->id,
        ]);

        $this->get(route('backoffice.caisse-transfers.show', $transfer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/CaisseTransfers/Show')
                ->where('transfer.reference', 'TRF-000042')
                ->where('transfer.caisseSource', $source->nom)
            );
    }
}
