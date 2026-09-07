<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The « Validation de transfert » header carries TWO figures answering two
 * different questions (07/09/2026):
 *
 *  - « Ma caisse »           — the viewer's OWN till balance, i.e. what they
 *                              can actually transfer right now. Never a
 *                              colleague's: another person's balance is not
 *                              this screen's business.
 *  - « Total des transferts » — the summed amount over the WHOLE filtered
 *                              set, so it follows the centre switcher and the
 *                              status filter, and never changes on a page
 *                              click (it is not the visible page's subtotal).
 *
 * Both are read from the stored, ledger-maintained `caisses.solde` and from
 * the filtered query — neither is recomputed from the rows on screen, which
 * would ignore payments, expenses and refunds (§11).
 */
final class TransfertTotauxParCentreTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $marrakech;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
    }

    /** @return array{0: Employee, 1: Caisse} */
    private function tillHolder(Etablissement $centre, string $solde): array
    {
        $employee = Employee::factory()->create([
            'etablissement_id' => $centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $employee->syncEtablissements([$centre->id]);
        $till = $employee->till()->first();
        $till->update(['solde' => $solde]);

        return [$employee, $till->fresh()];
    }

    private function transfer(Caisse $source, Caisse $destination, string $montant, Employee $requester): CaisseTransfer
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

    private function superAdminIn(Etablissement $centre, string $solde): User
    {
        [$employee] = $this->tillHolder($centre, $solde);
        $user = User::factory()->create(['must_change_password' => false]);
        $employee->forceFill(['user_id' => $user->id])->save();
        $user->assignRole('super-admin');

        return $user->fresh();
    }

    /** @return array<string, mixed> */
    private function listIn(?Etablissement $centre, User $user): array
    {
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($centre?->id);

        return app(GetCaisseTransfersList::class)($user);
    }

    public function test_the_total_follows_the_centre_switcher(): void
    {
        $viewer = $this->superAdminIn($this->marrakech, '1000.00');

        [$mA, $tillA] = $this->tillHolder($this->marrakech, '50000.00');
        [$mB, $tillB] = $this->tillHolder($this->marrakech, '0.00');
        $this->transfer($tillA, $tillB, '20000.00', $mA);

        [$rA, $tillC] = $this->tillHolder($this->rabat, '50000.00');
        [$rB, $tillD] = $this->tillHolder($this->rabat, '0.00');
        $this->transfer($tillC, $tillD, '5000.00', $rA);

        // Marrakech alone.
        $this->assertSame('20000.00', $this->listIn($this->marrakech, $viewer)['montantTotal']);
        // Rabat alone.
        $this->assertSame('5000.00', $this->listIn($this->rabat, $viewer)['montantTotal']);
        // « Tous les centres » — super-admin only, sums both.
        $this->assertSame('25000.00', $this->listIn(null, $viewer)['montantTotal']);
    }

    public function test_the_balance_shown_is_the_viewers_own_till(): void
    {
        $viewer = $this->superAdminIn($this->marrakech, '7500.00');

        // A colleague holding far more must not change the figure.
        $this->tillHolder($this->marrakech, '999999.00');

        $this->assertSame('7500.00', $this->listIn($this->marrakech, $viewer)['soldeCaisse']);
    }

    /**
     * The balance is the viewer's own till wherever they are looking — it is
     * « what I can transfer », not a per-centre aggregate, so switching the
     * centre changes the transfer total but never this figure.
     */
    public function test_the_balance_does_not_change_with_the_switcher(): void
    {
        $viewer = $this->superAdminIn($this->marrakech, '7500.00');

        $this->assertSame('7500.00', $this->listIn($this->marrakech, $viewer)['soldeCaisse']);
        $this->assertSame('7500.00', $this->listIn($this->rabat, $viewer)['soldeCaisse']);
        $this->assertSame('7500.00', $this->listIn(null, $viewer)['soldeCaisse']);
    }

    /** An account with no employee record has no till — the figure is simply absent. */
    public function test_a_user_without_a_till_gets_null(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('super-admin');

        $this->assertNull($this->listIn($this->marrakech, $user->fresh())['soldeCaisse']);
    }
}
