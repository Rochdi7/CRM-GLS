<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Support\Access\HiddenAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cancelling a transfer is normally the two parties' call — the requester or
 * the recipient. The maintainer may clear ANY pending transfer (07/09/2026).
 *
 * Why: a request nobody acts on is a live hazard. TRF-021 sat pending for
 * three days, ready to debit 103 900,00 DH from a till that had since
 * dropped to 4 800,00 DH, and until now only its two parties could clear it
 * — an abandoned request needed the very people who had forgotten it.
 *
 * ⚠ The maintainer's account is HIDDEN from the journal's default view
 * (HiddenAccount), so a third-party cancellation by him would otherwise
 * leave no visible trace of who voided someone else's money movement. The
 * mandatory reason is what keeps it legible: it is written into the
 * transfer's own note, in the record itself and not only in the activity
 * log.
 */
final class AnnulationTransfertParMainteneurTest extends TestCase
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
    private function tillHolder(string $solde = '5000.00'): array
    {
        $employee = Employee::factory()->create([
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $till = $employee->till()->first();
        $till->update(['solde' => $solde]);

        return [$employee, $till->fresh()];
    }

    private function signIn(Employee $employee, string $email, string ...$permissions): User
    {
        $user = User::factory()->create(['email' => $email, 'must_change_password' => false]);
        $employee->forceFill(['user_id' => $user->id])->save();

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

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

    public function test_the_maintainer_cancels_a_transfer_he_is_no_party_to(): void
    {
        [$hafssa, $source] = $this->tillHolder();
        [$rafik, $destination] = $this->tillHolder('0.00');
        $transfer = $this->pendingTransfer($source, $destination, $hafssa);

        [$maintainer] = $this->tillHolder('0.00');
        $user = $this->signIn($maintainer, HiddenAccount::EMAIL, 'cash-transfers.update');

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.update', $transfer), [
                'statut' => CaisseTransfer::STATUT_ANNULE,
                'motif_annulation' => 'Doublon — la caisse a déjà été vidée',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $transfer->fresh();
        $this->assertSame(CaisseTransfer::STATUT_ANNULE, $fresh->statut);
        // The reason lands in the record itself, not only the activity log.
        $this->assertStringContainsString('Doublon', (string) $fresh->note);
        // No money ever moved: a pending transfer had debited nothing.
        $this->assertSame('5000.00', (string) $source->fresh()->solde);
        $this->assertSame('0.00', (string) $destination->fresh()->solde);
    }

    public function test_the_maintainer_must_give_a_reason(): void
    {
        [$hafssa, $source] = $this->tillHolder();
        [$rafik, $destination] = $this->tillHolder('0.00');
        $transfer = $this->pendingTransfer($source, $destination, $hafssa);

        [$maintainer] = $this->tillHolder('0.00');
        $user = $this->signIn($maintainer, HiddenAccount::EMAIL, 'cash-transfers.update');

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.update', $transfer), [
                'statut' => CaisseTransfer::STATUT_ANNULE,
                'motif_annulation' => '   ',
            ])
            ->assertSessionHasErrors('motif_annulation');

        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
    }

    /** The widening is for the maintainer alone — not for anyone holding the permission. */
    public function test_a_third_party_still_cannot_cancel(): void
    {
        [$hafssa, $source] = $this->tillHolder();
        [$rafik, $destination] = $this->tillHolder('0.00');
        $transfer = $this->pendingTransfer($source, $destination, $hafssa);

        [$autre] = $this->tillHolder('0.00');
        $user = $this->signIn($autre, 'autre@glszentrum.com', 'cash-transfers.update');

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.update', $transfer), [
                'statut' => CaisseTransfer::STATUT_ANNULE,
                'motif_annulation' => 'je veux annuler',
            ])
            ->assertSessionHasErrors('statut');

        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
    }

    /** A party cancels their own transfer without needing a reason. */
    public function test_the_requester_cancels_without_a_reason(): void
    {
        [$hafssa, $source] = $this->tillHolder();
        [$rafik, $destination] = $this->tillHolder('0.00');
        $transfer = $this->pendingTransfer($source, $destination, $hafssa);

        $user = $this->signIn($hafssa, 'hafssa@glszentrum.com', 'cash-transfers.update');

        $this->actingAs($user)
            ->put(route('backoffice.caisse-transfers.update', $transfer), [
                'statut' => CaisseTransfer::STATUT_ANNULE,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(CaisseTransfer::STATUT_ANNULE, $transfer->fresh()->statut);
    }
}
