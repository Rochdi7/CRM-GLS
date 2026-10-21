<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Modifier le montant » d'un transfert encore en attente (21/10/2026).
 *
 * Même famille que le changement de destinataire : le DEMANDEUR corrige sa
 * propre demande, avec le seul droit de créer un transfert (le front office
 * n'a pas `cash-transfers.update`). Ce que ces tests verrouillent : aucun
 * solde ne bouge, le plafond de la demande est rejoué en écartant la demande
 * elle-même de la réserve, et ni le destinataire, ni un tiers, ni un
 * transfert validé ne peuvent être « re-chiffrés ».
 */
final class ChangerMontantTransfertTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    /** @return array{0: Employee, 1: Caisse} */
    private function tillHolder(string $solde = '0.00'): array
    {
        $employee = Employee::factory()->create([
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $till = $employee->till()->first();
        $till->update(['solde' => $solde]);

        return [$employee, $till->fresh()];
    }

    private function signIn(Employee $employee, string $email): User
    {
        $user = User::factory()->create(['email' => $email, 'must_change_password' => false]);
        $employee->forceFill(['user_id' => $user->id])->save();

        // Front-office reach only: create + view, NOT update.
        $user->givePermissionTo('cash-transfers.create', 'cash-transfers.view');

        return $user->fresh();
    }

    private function pendingTransfer(Caisse $source, Caisse $destination, Employee $requester, string $montant = '1000.00'): CaisseTransfer
    {
        return CaisseTransfer::query()->create([
            'reference' => 'TRF-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => $montant,
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'date_transfert' => now()->toDateString(),
            'requested_by' => $requester->id,
            'solde_source_avant' => $source->solde,
            'solde_dest_avant' => $destination->solde,
        ]);
    }

    public function test_the_requester_changes_the_amount_without_moving_any_money(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [, $amine] = $this->tillHolder('100.00');
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $this->actingAs($this->signIn($mustapha, 'mustapha@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '1500.00'])
            ->assertSessionHasNoErrors();

        $fresh = $transfer->fresh();
        $this->assertSame('1500.00', (string) $fresh->montant);
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $fresh->statut);
        $this->assertStringContainsString('→', (string) $fresh->note);

        // Not one dirham moved: a pending request had debited nothing.
        $this->assertSame('5000.00', (string) $source->fresh()->solde);
        $this->assertSame('100.00', (string) $amine->fresh()->solde);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'caisse_transfer',
            'event' => 'transfert_montant_modifie',
            'subject_id' => $transfer->id,
        ]);
    }

    /** The recipient then confirms the NEW amount, and that is what moves. */
    public function test_validation_moves_the_new_amount(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [$amineEmp, $amine] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $this->actingAs($this->signIn($mustapha, 'mustapha@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '700'])
            ->assertSessionHasNoErrors();

        app(ValiderTransfertCaisse::class)->handle($transfer->fresh(), $amineEmp);

        $this->assertSame('4300.00', (string) $source->fresh()->solde);
        $this->assertSame('700.00', (string) $amine->fresh()->solde);
    }

    /**
     * The request cap is replayed with the row itself EXCLUDED from the
     * reservation: a 1 000 DH request on a 5 000 DH till may grow to 5 000
     * (the whole till), but not beyond — and another pending request on the
     * same till still reserves its share.
     */
    public function test_the_amount_is_capped_by_the_till_minus_other_pending_transfers(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [, $amine] = $this->tillHolder();
        [, $mohamed] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);
        $user = $this->signIn($mustapha, 'mustapha@glszentrum.com');

        // Its own 1 000 DH must not count against it: the whole till is reachable.
        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '5000'])
            ->assertSessionHasNoErrors();
        $this->assertSame('5000.00', (string) $transfer->fresh()->montant);

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '5000.01'])
            ->assertSessionHasErrors('montant');
        $this->assertSame('5000.00', (string) $transfer->fresh()->montant);

        // A SECOND pending request reserves 600 DH: this one can hold at most 4 400.
        $autre = $this->pendingTransfer($source, $mohamed, $mustapha, '600.00');

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '4400.01'])
            ->assertSessionHasErrors('montant');

        $errors = session('errors')->get('montant');
        $this->assertStringContainsString($autre->reference, implode(' ', $errors));

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '4400'])
            ->assertSessionHasNoErrors();
        $this->assertSame('4400.00', (string) $transfer->fresh()->montant);
    }

    /** The recipient does not decide how much is sent to them. */
    public function test_the_recipient_cannot_change_it(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [$amineEmp, $amine] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $this->actingAs($this->signIn($amineEmp, 'amine@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '1500'])
            ->assertSessionHasErrors('montant');

        $this->assertSame('1000.00', (string) $transfer->fresh()->montant);
    }

    public function test_a_validated_transfer_cannot_change_amount(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [, $amine] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);
        $transfer->forceFill(['statut' => CaisseTransfer::STATUT_VALIDE])->saveQuietly();

        $this->actingAs($this->signIn($mustapha, 'mustapha@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '1500'])
            ->assertSessionHasErrors('montant');

        $this->assertSame('1000.00', (string) $transfer->fresh()->montant);
    }

    public function test_zero_and_unchanged_amounts_are_refused(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [, $amine] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);
        $user = $this->signIn($mustapha, 'mustapha@glszentrum.com');

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '0'])
            ->assertSessionHasErrors('montant');

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.change-montant', $transfer), ['montant' => '1000.00'])
            ->assertSessionHasErrors('montant');

        $this->assertSame('1000.00', (string) $transfer->fresh()->montant);
    }

    /** The list tells the screen which rows offer the action — requester, pending only. */
    public function test_the_list_flags_the_rows_the_requester_may_rechiffrer(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [$amineEmp, $amine] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $row = fn (User $user) => collect(
            app(GetCaisseTransfersList::class)($user)['data']->items()
        )->firstWhere('id', $transfer->id);

        $this->assertTrue($row($this->signIn($mustapha, 'mustapha@glszentrum.com'))['canChangeMontant']);
        $this->assertFalse($row($this->signIn($amineEmp, 'amine@glszentrum.com'))['canChangeMontant']);
    }
}
