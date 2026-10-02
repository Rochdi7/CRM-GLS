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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * « Changer le destinataire » d'un transfert encore en attente (02/10/2026).
 *
 * Cas réel : TRF-193, 65 393,30 DH envoyés à Amine Rafik au lieu de Mohamed
 * Rafik ; la correction avait dû passer par une commande en production.
 * Le DEMANDEUR la fait désormais lui-même, avec le seul droit de créer un
 * transfert — le front office n'a pas `cash-transfers.update`.
 *
 * Ce que ces tests verrouillent : aucun solde ne bouge, seul le nouveau
 * destinataire peut accepter, et ni le destinataire actuel, ni un transfert
 * déjà validé, ne peuvent être « re-routés ».
 */
final class ChangerDestinataireTransfertTest extends TestCase
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

    private function pendingTransfer(Caisse $source, Caisse $destination, Employee $requester): CaisseTransfer
    {
        return CaisseTransfer::query()->create([
            'reference' => 'TRF-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => '1000.00',
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'date_transfert' => now()->toDateString(),
            'requested_by' => $requester->id,
            'solde_source_avant' => $source->solde,
            'solde_dest_avant' => $destination->solde,
        ]);
    }

    public function test_the_requester_changes_the_recipient_without_moving_any_money(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [, $amine] = $this->tillHolder('100.00');
        [, $mohamed] = $this->tillHolder('200.00');
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $this->actingAs($this->signIn($mustapha, 'mustapha@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-destination', $transfer), [
                'caisse_destination_id' => $mohamed->id,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $transfer->fresh();
        $this->assertSame($mohamed->id, (int) $fresh->caisse_destination_id);
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $fresh->statut);
        $this->assertSame('200.00', (string) $fresh->solde_dest_avant);
        $this->assertStringContainsString('→', (string) $fresh->note);

        // Not one dirham moved: a pending request had debited nothing.
        $this->assertSame('5000.00', (string) $source->fresh()->solde);
        $this->assertSame('100.00', (string) $amine->fresh()->solde);
        $this->assertSame('200.00', (string) $mohamed->fresh()->solde);
    }

    /** Only the NEW recipient may accept the receipt afterwards. */
    public function test_only_the_new_recipient_can_validate(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [$amineEmp, $amine] = $this->tillHolder();
        [$mohamedEmp, $mohamed] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $this->actingAs($this->signIn($mustapha, 'mustapha@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-destination', $transfer), [
                'caisse_destination_id' => $mohamed->id,
            ])
            ->assertSessionHasNoErrors();

        try {
            app(ValiderTransfertCaisse::class)->handle($transfer->fresh(), $amineEmp);
            $this->fail('The replaced recipient must not be able to validate.');
        } catch (ValidationException) {
            // expected
        }

        app(ValiderTransfertCaisse::class)->handle($transfer->fresh(), $mohamedEmp);

        $this->assertSame('4000.00', (string) $source->fresh()->solde);
        $this->assertSame('1000.00', (string) $mohamed->fresh()->solde);
        $this->assertSame('0.00', (string) $amine->fresh()->solde);
    }

    /** The current recipient cannot redirect a transfer meant for him. */
    public function test_the_current_recipient_cannot_change_it(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [$amineEmp, $amine] = $this->tillHolder();
        [, $mohamed] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $this->actingAs($this->signIn($amineEmp, 'amine@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-destination', $transfer), [
                'caisse_destination_id' => $mohamed->id,
            ])
            ->assertSessionHasErrors('caisse_destination_id');

        $this->assertSame($amine->id, (int) $transfer->fresh()->caisse_destination_id);
    }

    public function test_a_validated_transfer_cannot_change_recipient(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [, $amine] = $this->tillHolder();
        [, $mohamed] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);
        $transfer->forceFill(['statut' => CaisseTransfer::STATUT_VALIDE])->saveQuietly();

        $this->actingAs($this->signIn($mustapha, 'mustapha@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-destination', $transfer), [
                'caisse_destination_id' => $mohamed->id,
            ])
            ->assertSessionHasErrors('caisse_destination_id');

        $this->assertSame($amine->id, (int) $transfer->fresh()->caisse_destination_id);
    }

    public function test_the_source_till_is_refused_as_recipient(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [, $amine] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $this->actingAs($this->signIn($mustapha, 'mustapha@glszentrum.com'))
            ->put(route('backoffice.caisse-transfers.change-destination', $transfer), [
                'caisse_destination_id' => $source->id,
            ])
            ->assertSessionHasErrors('caisse_destination_id');

        $this->assertSame($amine->id, (int) $transfer->fresh()->caisse_destination_id);
    }

    /** The list tells the screen which rows offer the action — requester, pending only. */
    public function test_the_list_flags_the_rows_the_requester_may_redirect(): void
    {
        [$mustapha, $source] = $this->tillHolder('5000.00');
        [$amineEmp, $amine] = $this->tillHolder();
        $transfer = $this->pendingTransfer($source, $amine, $mustapha);

        $row = fn (User $user) => collect(
            app(GetCaisseTransfersList::class)($user)['data']->items()
        )->firstWhere('id', $transfer->id);

        $this->assertTrue($row($this->signIn($mustapha, 'mustapha@glszentrum.com'))['canChangeDestinataire']);
        $this->assertFalse($row($this->signIn($amineEmp, 'amine@glszentrum.com'))['canChangeDestinataire']);
    }
}
