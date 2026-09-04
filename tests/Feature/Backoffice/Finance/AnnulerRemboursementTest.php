<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetRemboursementsList;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Annulation d'un remboursement depuis l'ecran (03/09/2026).
 *
 * La caisse est recreditee par ecriture compensatoire, la ligne n'est jamais
 * supprimee (§11), et l'action est reservee au super-admin : recrediter une
 * caisse est un mouvement d'argent, pas une correction de libelle.
 */
final class AnnulerRemboursementTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    /** @return array{0: User, 1: Remboursement, 2: \App\Models\Caisse} */
    private function scenario(): array
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $user = $user->fresh();

        $caisse = $user->employee->till()->firstOrFail();
        $caisse->update(['solde' => 1000]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $remboursement = Remboursement::create([
            'reference' => 'RMB-TEST',
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisse->id,
            'etablissement_id' => $this->centre->id,
            'montant' => 300,
            'date_remboursement' => now()->toDateString(),
            'agent_id' => $user->employee->id,
        ]);
        $caisse->update(['solde' => 700]); // le debit d'origine

        return [$user, $remboursement, $caisse];
    }

    public function test_a_super_admin_cancels_a_refund_and_the_till_is_credited_back(): void
    {
        [$user, $remboursement, $caisse] = $this->scenario();

        $this->actingAs($user)
            ->post(route('backoffice.remboursements.cancel', $remboursement), ['motif' => 'Saisi en double'])
            ->assertRedirect();

        $this->assertSame('1000.00', (string) $caisse->fresh()->solde);

        // La ligne survit (append-only) et porte la trace de l'annulation.
        $remboursement->refresh();
        $this->assertTrue(Remboursement::estAnnule($remboursement));
        $this->assertStringContainsString('Saisi en double', (string) $remboursement->note);
        $this->assertDatabaseHas('remboursements', ['id' => $remboursement->id]);
    }

    public function test_a_cancelled_refund_leaves_the_totals(): void
    {
        [$user, $remboursement] = $this->scenario();

        $this->actingAs($user)->post(route('backoffice.remboursements.cancel', $remboursement));

        $totaux = app(GetRemboursementsList::class)->totaux($user->fresh());

        $this->assertSame('0.00', $totaux['montant'], 'Plus aucun argent sorti.');
        $this->assertSame(0, $totaux['count']);
        $this->assertSame(1, $totaux['annules']);
    }

    /**
     * Le garde-fou central : deux clics ne recreditent pas la caisse deux
     * fois pour une seule sortie.
     */
    public function test_a_refund_cannot_be_cancelled_twice(): void
    {
        [$user, $remboursement, $caisse] = $this->scenario();

        $this->actingAs($user)->post(route('backoffice.remboursements.cancel', $remboursement));
        $this->assertSame('1000.00', (string) $caisse->fresh()->solde);

        $this->actingAs($user)
            ->post(route('backoffice.remboursements.cancel', $remboursement))
            ->assertForbidden();

        $this->assertSame('1000.00', (string) $caisse->fresh()->solde, 'La caisse ne bouge plus.');
    }

    /**
     * `refunds.cancel` est dans superAdminOnly() : meme le detenteur de
     * toutes les autres permissions de remboursement ne peut pas annuler.
     */
    public function test_a_non_super_admin_cannot_cancel(): void
    {
        [, $remboursement, $caisse] = $this->scenario();

        $autre = User::factory()->create();
        foreach (['refunds.view', 'refunds.create', 'refunds.update', 'centers.access-all'] as $p) {
            $autre->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $autre->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($autre->fresh())
            ->post(route('backoffice.remboursements.cancel', $remboursement))
            ->assertForbidden();

        $this->assertSame('700.00', (string) $caisse->fresh()->solde);
        $this->assertFalse(Remboursement::estAnnule($remboursement->fresh()));
    }

    /** Aucun preset de role ne peut porter la permission. */
    public function test_no_role_preset_holds_refunds_cancel(): void
    {
        $this->assertContains('refunds.cancel', PermissionRegistry::superAdminOnly());

        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains(
                'refunds.cancel',
                $permissions,
                "Le role {$role} ne doit pas porter refunds.cancel.",
            );
        }
    }
}
