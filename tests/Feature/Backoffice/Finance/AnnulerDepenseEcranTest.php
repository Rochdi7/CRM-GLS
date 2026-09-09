<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Expenses\Queries\GetDepensesList;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Authorization\PermissionRegistry;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Annuler » depuis l'écran Gestion des dépenses (09/09/2026).
 *
 * Signalé en production : DEP-014, annulée en base par la commande,
 * s'affichait dans l'onglet « Paiements prof » comme un paiement ordinaire —
 * cet onglet n'avait aucune colonne Statut. Un montant rendu à la caisse
 * paraissait donc encore sorti.
 *
 * Ce que ces tests fixent : l'annulation est atteignable depuis l'écran, elle
 * est réservée au super-admin, elle recrédite la caisse, et la ligne annulée
 * se voit comme telle dans les DEUX onglets (mêmes lignes, même statut).
 */
final class AnnulerDepenseEcranTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
    }

    /** @param list<string> $permissions */
    private function userWith(array $permissions, bool $superAdmin = false): User
    {
        $user = User::factory()->create();

        if ($superAdmin) {
            $user->assignRole('super-admin');
        } else {
            foreach ($permissions as $p) {
                $user->givePermissionTo($p);
            }
        }

        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        return $user->fresh();
    }

    private function approvedDepense(string $montant = '7650.00', ?string $typeNom = null): Depense
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $till = $agent->till()->firstOrFail();
        $till->update(['solde' => $montant]);

        $type = TypeDepense::firstOrCreate(
            ['nom' => $typeNom ?? 'Fournitures'],
            ['is_system' => $typeNom === 'Paiement prof', 'statut' => TypeDepense::STATUT_ACTIF],
        );

        return Depense::create([
            'reference' => 'DEP-'.fake()->unique()->numerify('#####'),
            'type_depense_id' => $type->id,
            'caisse_id' => $till->id,
            'agent_id' => $agent->id,
            'montant' => $montant,
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-08-29',
            'statut' => Depense::STATUT_APPROUVEE,
            'approved_at' => now(),
            'description' => '*',
        ]);
    }

    // ── La permission est bien réservée au super-admin ──────────────────

    public function test_expenses_cancel_is_reserved_to_super_admins(): void
    {
        $this->assertContains('expenses.cancel', PermissionRegistry::superAdminOnly());

        // Aucun preset de rôle ne peut la porter — matrix() filtre.
        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains(
                'expenses.cancel',
                $permissions,
                "Le rôle {$role} ne doit pas pouvoir annuler une dépense.",
            );
        }

        // Elle reste attribuable À LA MAIN sur l'écran Autorisations, comme
        // refunds.cancel : superAdminOnly() ferme les PRESETS de rôle, pas la
        // délégation nominative qu'un super-admin décide (seul
        // GLOBAL_CENTER_ACCESS est retiré de grantable()).
        $this->assertContains('expenses.cancel', PermissionRegistry::grantable());
    }

    public function test_a_non_super_admin_cannot_cancel_even_with_every_expense_permission(): void
    {
        $depense = $this->approvedDepense();
        $solde = $depense->caisse->solde;

        $this->actingAs($this->userWith(['expenses.view', 'expenses.create', 'expenses.update', 'expenses.approve']));

        $this->post(route('backoffice.depenses.cancel', $depense), ['motif' => 'doublon'])
            ->assertForbidden();

        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->fresh()->statut);
        $this->assertSame((string) $solde, (string) $depense->caisse->fresh()->solde);
    }

    // ── Le super-admin annule, et la caisse revient ─────────────────────

    public function test_a_super_admin_cancels_and_the_till_is_credited_back(): void
    {
        $depense = $this->approvedDepense('7650.00');
        // La caisse a bien été débitée : elle est à 0 après la dépense.
        $depense->caisse->update(['solde' => '0.00']);

        $this->actingAs($this->userWith([], superAdmin: true));

        $this->post(route('backoffice.depenses.cancel', $depense), ['motif' => 'saisie en double'])
            ->assertRedirect();

        $depense = $depense->fresh();
        $this->assertSame(Depense::STATUT_ANNULEE, $depense->statut);
        $this->assertSame('7650.00', (string) $depense->caisse->fresh()->solde);
        $this->assertStringContainsString(Depense::MARQUEUR_ANNULE, (string) $depense->note);
        $this->assertStringContainsString('saisie en double', (string) $depense->note);
    }

    public function test_the_motive_is_required_and_nothing_moves_without_it(): void
    {
        $depense = $this->approvedDepense('500.00');
        $depense->caisse->update(['solde' => '0.00']);

        $this->actingAs($this->userWith([], superAdmin: true));

        $this->post(route('backoffice.depenses.cancel', $depense), ['motif' => ''])
            ->assertSessionHasErrors('motif');

        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->fresh()->statut);
        $this->assertSame('0.00', (string) $depense->caisse->fresh()->solde);
    }

    public function test_cancelling_twice_credits_the_till_only_once(): void
    {
        $depense = $this->approvedDepense('1000.00');
        $depense->caisse->update(['solde' => '0.00']);
        $superAdmin = $this->userWith([], superAdmin: true);

        $this->actingAs($superAdmin);
        $this->post(route('backoffice.depenses.cancel', $depense), ['motif' => 'doublon'])->assertRedirect();
        $this->assertSame('1000.00', (string) $depense->caisse->fresh()->solde);

        // Deuxième clic : la policy refuse (la ligne n'est plus « Approuvée »).
        $this->post(route('backoffice.depenses.cancel', $depense->fresh()), ['motif' => 'doublon'])
            ->assertForbidden();

        $this->assertSame('1000.00', (string) $depense->caisse->fresh()->solde);
    }

    public function test_a_pending_expense_cannot_be_cancelled_from_the_screen(): void
    {
        $depense = $this->approvedDepense('300.00');
        $depense->update(['statut' => Depense::STATUT_EN_ATTENTE]);

        $this->actingAs($this->userWith([], superAdmin: true));

        $this->post(route('backoffice.depenses.cancel', $depense), ['motif' => 'x'])
            ->assertForbidden();

        $this->assertSame(Depense::STATUT_EN_ATTENTE, $depense->fresh()->statut);
    }

    // ── Ce que l'écran montre ensuite ───────────────────────────────────

    public function test_both_tabs_report_the_cancelled_row_and_drop_it_from_the_totals(): void
    {
        $prof = $this->approvedDepense('7650.00', 'Paiement prof');
        $prof->caisse->update(['solde' => '0.00']);
        $superAdmin = $this->userWith([], superAdmin: true);

        $list = app(GetDepensesList::class);
        $avant = $list($superAdmin, '', '', '', '', '', 25, GetDepensesList::SCOPE_PAIEMENT_PROF, '', false);
        $this->assertSame('7650.00', $avant['montantTotal']);

        $this->actingAs($superAdmin)
            ->post(route('backoffice.depenses.cancel', $prof), ['motif' => 'doublon de DEP-012'])
            ->assertRedirect();

        // L'onglet « Paiements prof » : la ligne reste, le total tombe à 0.
        $apres = $list($superAdmin, '', '', '', '', '', 25, GetDepensesList::SCOPE_PAIEMENT_PROF, '', false);
        $this->assertSame('0.00', $apres['montantTotal']);

        $row = collect($apres['data']->items())->firstWhere('reference', $prof->reference);
        $this->assertNotNull($row, 'La ligne annulée doit rester visible.');
        $this->assertSame(Depense::STATUT_ANNULEE, $row['statut']);
        $this->assertTrue($row['isAnnulee']);
        // Le motif s'affiche sous le badge, sans ouvrir la fiche.
        $this->assertStringContainsString('doublon de DEP-012', (string) $row['motifAnnulation']);

        // L'onglet « Validation » voit exactement le même statut.
        $tous = $list($superAdmin, '', '', '', '', '', 25, GetDepensesList::SCOPE_TOUS, '', false);
        $this->assertSame(
            Depense::STATUT_ANNULEE,
            collect($tous['data']->items())->firstWhere('reference', $prof->reference)['statut'],
        );
    }

    public function test_a_cancelled_expense_can_no_longer_be_edited(): void
    {
        $depense = $this->approvedDepense('400.00');
        $superAdmin = $this->userWith([], superAdmin: true);

        $this->actingAs($superAdmin)
            ->post(route('backoffice.depenses.cancel', $depense), ['motif' => 'doublon'])
            ->assertRedirect();

        $this->assertFalse($superAdmin->can('update', $depense->fresh()));
    }
}
