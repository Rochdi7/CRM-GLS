<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * A pending transfer must re-check the source balance AT VALIDATION, inside
 * the transaction on the locked row (CLAUDE.md §11: every "read a balance,
 * then write" runs under lockForUpdate).
 *
 * The request only snapshots `solde_source_avant` when it is filed, but days
 * can pass before anyone acts on it and the till keeps living in between.
 *
 * Reported 07/09/2026: Hafssa Elkhattabi filed TRF-021 for 103 900,00 DH on
 * 04/09; nobody validated it, so she re-filed and had 103 400,00 DH approved
 * on 07/09. The forgotten request was still pending, ready to debit
 * 103 900,00 DH from a till then holding 4 800,00 DH — writing a NEGATIVE
 * balance no cash till can hold, and crediting the recipient a second time.
 */
final class TransfertSoldeInsuffisantTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    /** An employee and their auto-provisioned till, funded. */
    private function tillHolder(string $solde): array
    {
        $employee = Employee::factory()->create([
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $till = $employee->till()->first();
        $till->update(['solde' => $solde]);

        return [$employee, $till->fresh()];
    }

    private function pendingTransfer(Caisse $source, Caisse $destination, string $montant, Employee $requester): CaisseTransfer
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

    public function test_a_transfer_larger_than_the_current_balance_is_refused(): void
    {
        // TRF-021's exact shape: filed when the till was full, validated
        // after it had been emptied by another transfer.
        [$hafssa, $source] = $this->tillHolder('103900.00');
        [$rafik, $destination] = $this->tillHolder('0.00');

        $transfer = $this->pendingTransfer($source, $destination, '103900.00', $hafssa);

        // The till drains in the meantime.
        $source->update(['solde' => '4800.00']);

        try {
            app(ValiderTransfertCaisse::class)->handle($transfer->fresh(), $rafik);
            $this->fail('Validation should have been refused — the till no longer holds the money.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('4 800,00', $e->getMessage());
        }

        // Nothing moved, and the row stays pending so a human can decide.
        $this->assertSame('4800.00', (string) $source->fresh()->solde);
        $this->assertSame('0.00', (string) $destination->fresh()->solde);
        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
    }

    public function test_a_transfer_within_the_current_balance_still_validates(): void
    {
        [$hafssa, $source] = $this->tillHolder('5000.00');
        [$rafik, $destination] = $this->tillHolder('0.00');

        $transfer = $this->pendingTransfer($source, $destination, '4800.00', $hafssa);

        app(ValiderTransfertCaisse::class)->handle($transfer->fresh(), $rafik);

        $this->assertSame(CaisseTransfer::STATUT_VALIDE, $transfer->fresh()->statut);
        $this->assertSame('200.00', (string) $source->fresh()->solde);
        $this->assertSame('4800.00', (string) $destination->fresh()->solde);
    }

    /** Emptying a till completely is legitimate — the guard is `<`, not `<=`. */
    public function test_a_transfer_of_the_whole_balance_validates(): void
    {
        [$hafssa, $source] = $this->tillHolder('4800.00');
        [$rafik, $destination] = $this->tillHolder('0.00');

        $transfer = $this->pendingTransfer($source, $destination, '4800.00', $hafssa);

        app(ValiderTransfertCaisse::class)->handle($transfer->fresh(), $rafik);

        $this->assertSame(CaisseTransfer::STATUT_VALIDE, $transfer->fresh()->statut);
        $this->assertSame('0.00', (string) $source->fresh()->solde);
    }
}
