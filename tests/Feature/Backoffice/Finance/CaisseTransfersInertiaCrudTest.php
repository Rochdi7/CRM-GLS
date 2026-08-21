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

    /**
     * A user who may validate AND owns $destination — validation is
     * RECIPIENT-ONLY, so a validator who does not own the destination till
     * is refused no matter which permissions they hold.
     */
    private function recipientOf(Caisse $destination, string ...$extraPermissions): User
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.validate', ...$extraPermissions);
        $destination->update(['responsable_employee_id' => $user->employee->id]);
        // Employee::caisses() is the ownership source of truth the policy and
        // ValiderTransfertCaisse both read.
        $destination->fresh();

        return $user->fresh();
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
                // The transfer modal's fixed source — the viewer's own till.
                ->has('myCaisse')
            );
    }

    /**
     * "Validation de transfert" is a personal inbox/outbox: the list must
     * include transfers where the viewer's own till is EITHER end (not just
     * transfers they sent), labeled relative to them (Réception when money
     * is arriving into one of their tills, Transfert when it's leaving
     * one), with Expéditeur/Destinataire showing the owning EMPLOYEE's name
     * (resolved via the relation, not read off caisses.nom).
     */
    public function test_transfer_list_labels_direction_relative_to_the_viewer(): void
    {
        $sender = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $senderCaisse = $sender->employee->caisses()->first();
        $receiver = $this->userWith('cash-transfers.view');
        $receiverCaisse = $receiver->employee->caisses()->first();
        $receiverCaisse->update(['solde' => 500]);

        $this->actingAs($sender);
        // No caisse_source_id in the payload — the source is always the
        // sender's own till, derived server-side.
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $receiverCaisse->id,
            'montant' => '300',
        ]);
        $this->assertSame($senderCaisse->id, CaisseTransfer::first()->caisse_source_id);

        // The sender sees it as an outgoing "Transfert".
        $senderResponse = $this->get(route('backoffice.caisses.index', ['tab' => 'transferts']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('transfers.data.0'));
        $senderRow = $senderResponse->viewData('page')['props']['transfers']['data'][0];
        $this->assertSame('Transfert', $senderRow['typeTransaction']);
        $this->assertSame($sender->employee->nomComplet(), $senderRow['expediteur']);
        $this->assertSame($receiver->employee->nomComplet(), $senderRow['destinataire']);

        // The receiver — who never requested it — still sees it, labeled
        // "Réception" from their side.
        $this->actingAs($receiver);
        $receiverResponse = $this->get(route('backoffice.caisses.index', ['tab' => 'transferts']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('transfers.data.0'));
        $receiverRow = $receiverResponse->viewData('page')['props']['transfers']['data'][0];
        $this->assertSame('Réception', $receiverRow['typeTransaction']);
    }

    public function test_requesting_a_transfer_does_not_move_any_balance(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($user);
        $source = $user->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
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

    public function test_transferring_to_your_own_till_is_rejected(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($user);
        $ownCaisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $ownCaisse->id,
            'montant' => '100',
        ])->assertSessionHasErrors('caisse_destination_id');

        $this->assertSame(0, CaisseTransfer::count());
    }

    /**
     * The source till can never be chosen client-side — a tampered
     * caisse_source_id in the payload is simply ignored and the requester's
     * OWN till is used, for every role including super-admin.
     */
    public function test_a_tampered_caisse_source_id_is_ignored(): void
    {
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($user);
        $ownCaisse = $user->employee->caisses()->first();
        $foreignCaisse = $this->caisse(9999);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_source_id' => $foreignCaisse->id,
            'caisse_destination_id' => $destination->id,
            'montant' => '100',
        ])->assertRedirect();

        $this->assertSame($ownCaisse->id, CaisseTransfer::first()->caisse_source_id);
    }

    public function test_validation_by_a_different_employee_moves_both_balances(): void
    {
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $requester->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id,
            'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $validator = $this->recipientOf($destination);
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
        // The requester is on the SOURCE side, so under the recipient-only
        // rule the policy already denies them (403) — a stricter refusal than
        // the old soft form error, and it never reaches the Domain action.
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create', 'cash-transfers.validate');
        $this->actingAs($user);
        $source = $user->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id,
            'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $this->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertForbidden();

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $fresh->statut);
        $this->assertNull($fresh->validated_by);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
        $this->assertSame('500.00', (string) $destination->fresh()->solde);
    }

    public function test_owning_both_ends_still_cannot_self_validate(): void
    {
        // Edge case the recipient rule alone would not cover: an employee who
        // owns BOTH tills is the recipient, so the ownership check passes —
        // the "requester never validates their own transfer" rule is what
        // must stop them (enforced in the policy AND the Domain action).
        $user = $this->userWith('cash-transfers.view', 'cash-transfers.create', 'cash-transfers.validate');
        $this->actingAs($user);
        $source = $user->employee->caisses()->first();
        $source->update(['solde' => 1000]);

        $destination = $this->caisse(500);
        $destination->update(['responsable_employee_id' => $user->employee->id]);

        // The source is server-derived as the requester's FIRST till, so this
        // transfer runs between two tills the same employee owns.
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id,
            'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $this->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertForbidden();

        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
        $this->assertSame('500.00', (string) $destination->fresh()->solde);
    }

    public function test_validation_requires_the_validate_permission(): void
    {
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '100',
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
        $source = $requester->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $validator = $this->recipientOf($destination);
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
        $source = $user->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300', 'note' => 'Initial',
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
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $validator = $this->recipientOf($destination);
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
        $source = $user->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
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
        $destination = $this->caisse(500);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
        ])->assertSessionHasErrors('caisse_source_id');

        $this->assertSame(0, CaisseTransfer::count());
    }

    public function test_a_third_party_validator_who_is_not_the_recipient_is_refused(): void
    {
        // The core of the recipient-only rule: holding cash-transfers.validate
        // is NOT enough. Someone who owns neither end of the transfer must not
        // be able to push money between two other people's tills.
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $requester->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        // A validator with the permission but who owns no end of the transfer.
        $this->actingAs($this->userWith('cash-transfers.view', 'cash-transfers.validate'))
            ->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertForbidden();

        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
        $this->assertSame('500.00', (string) $destination->fresh()->solde);
    }

    public function test_a_super_admin_cannot_validate_someone_elses_transfer(): void
    {
        // Gate::before deliberately does NOT bypass CaisseTransfer@validate:
        // a super-admin approving a transfer into another employee's till
        // would defeat the two-person control entirely.
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $requester->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(\App\Models\Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $superAdmin->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($superAdmin->fresh())
            ->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertForbidden();

        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
        $this->assertSame('500.00', (string) $destination->fresh()->solde);
    }

    public function test_the_recipient_can_validate_and_both_balances_move(): void
    {
        $requester = $this->userWith('cash-transfers.view', 'cash-transfers.create');
        $this->actingAs($requester);
        $source = $requester->employee->caisses()->first();
        $source->update(['solde' => 1000]);
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $recipient = $this->recipientOf($destination);
        $this->actingAs($recipient)
            ->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertRedirect();

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_VALIDE, $fresh->statut);
        $this->assertSame($recipient->employee->id, $fresh->validated_by);
        $this->assertSame('700.00', (string) $source->fresh()->solde);
        $this->assertSame('800.00', (string) $destination->fresh()->solde);
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
        $destination = $this->caisse(500);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id, 'montant' => '300',
        ]);
        $transfer = CaisseTransfer::first();

        $lockedValidator = User::factory()->create();
        $lockedValidator->givePermissionTo('cash-transfers.view', 'cash-transfers.validate');
        Employee::factory()->create(['user_id' => $lockedValidator->id, 'etablissement_id' => $otherCentre->id]);
        $this->actingAs($lockedValidator->fresh());

        $this->put(route('backoffice.caisse-transfers.validate', $transfer))->assertForbidden();
    }
}
