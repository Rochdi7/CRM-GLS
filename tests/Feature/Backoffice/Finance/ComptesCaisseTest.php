<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Comptes de caisse » — the third tab of Gestion de la caisse.
 *
 * The whole point of this suite is the super-admin restriction: the
 * `cash-accounts.*` permissions are deliberately absent from every role in
 * PermissionRegistry::matrix(), so a user who holds every OTHER finance
 * permission (including `cash-registers.*`) must still be refused, and only
 * the Gate::before super-admin bypass gets through.
 */
final class ComptesCaisseTest extends TestCase
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

        return $user->fresh();
    }

    private function superAdmin(): User
    {
        return User::factory()->create()->assignRole('super-admin')->fresh();
    }

    /** No EncaissementFactory exists — the finance suite builds these by hand. */
    private function makeEncaissement(Caisse $caisse, string $montant, string $methode = Encaissement::METHODE_ESPECES): Encaissement
    {
        $group = \App\Models\Group::factory()->create(['etablissement_id' => $this->centre->id]);
        $inscription = \App\Models\Inscription::create([
            'reference' => 'INS-'.uniqid(),
            'student_id' => \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => \App\Models\AnneeScolaire::create([
                'nom' => substr('AS-'.uniqid(), 0, 20), 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
                'par_defaut' => false, 'inscription_ouverte' => true,
            ])->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-01',
        ]);
        $fee = \App\Models\InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais', 'montant' => 1000,
            'date_echeance' => '2025-10-01', 'statut' => 'Non payé',
        ]);

        return Encaissement::create([
            'reference' => 'ENC-'.uniqid(),
            'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id,
            'agent_id' => Employee::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => $montant,
            'methode' => $methode,
            'date_paiement' => '2025-09-15',
        ]);
    }

    private function makeDepense(Caisse $caisse, string $montant, string $methode = Encaissement::METHODE_ESPECES): Depense
    {
        return Depense::create([
            'reference' => 'DEP-'.uniqid(),
            'type_depense_id' => \App\Models\TypeDepense::create([
                'nom' => 'Fournitures '.uniqid(), 'is_system' => false, 'statut' => 'Actif',
            ])->id,
            'caisse_id' => $caisse->id,
            'agent_id' => Employee::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => $montant,
            'methode_paiement' => $methode,
            'date_depense' => '2025-09-16',
        ]);
    }

    // ---------------------------------------------------------------
    // Access
    // ---------------------------------------------------------------

    public function test_cash_accounts_permissions_are_in_no_role_preset(): void
    {
        foreach (\App\Support\Authorization\PermissionRegistry::matrix() as $role => $permissions) {
            foreach (['cash-accounts.view', 'cash-accounts.create', 'cash-accounts.update', 'cash-accounts.delete'] as $permission) {
                $this->assertNotContains(
                    $permission,
                    $permissions,
                    "Role [{$role}] must not preset [{$permission}] — the tab is super-admin only.",
                );
            }
        }
    }

    public function test_a_cash_register_viewer_does_not_see_the_comptes_tab(): void
    {
        // Holds the neighbouring finance permissions and still gets nothing:
        // the tab is a global, NON center-scoped view of where the money is.
        $this->actingAs($this->userWith('cash-registers.view', 'cash-transfers.view'))
            ->get(route('backoffice.caisses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewComptes', false)
                ->where('comptes', null)
            );
    }

    public function test_asking_for_the_comptes_tab_without_permission_falls_back_instead_of_leaking(): void
    {
        $this->actingAs($this->userWith('cash-registers.view'))
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewComptes', false)
                // The forced tab is discarded, so the dataset is never built.
                ->where('comptes', null)
                ->has('journalMine')
            );
    }

    public function test_super_admin_sees_the_comptes_tab_with_its_data(): void
    {
        Caisse::factory()->create([
            'nom' => 'Externe coffre',
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->centre->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Caisse::TYPE_EXTERNE]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Caisses/Index', false)
                ->where('canViewComptes', true)
                ->has('comptes.data', 1)
                ->where('comptes.data.0.type', Caisse::TYPE_EXTERNE)
                ->where('comptePermissions.create', true)
            );
    }

    public function test_only_externe_is_offered_as_a_creatable_type(): void
    {
        // The form must never offer a till (provisioned with its employee) or
        // a payment method (derived from the movements, not a record).
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('compteTypes', [Caisse::TYPE_EXTERNE])
                // The FILTER still offers everything the tab can show.
                ->where('compteTypeFilters', [
                    Caisse::TYPE_CAISSIERE,
                    Encaissement::METHODE_TPE,
                    Encaissement::METHODE_CHEQUE,
                    Encaissement::METHODE_VIREMENT,
                    Caisse::TYPE_EXTERNE,
                ])
            );
    }

    // ---------------------------------------------------------------
    // Derived payment-method rows (TPE / Chèque / Virement)
    // ---------------------------------------------------------------

    public function test_payment_methods_are_real_accounts_provisioned_per_centre(): void
    {
        // One TPE / Chèque / Virement row per centre, created with the centre
        // (EtablissementObserver) — never derived, never double-counted.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Encaissement::METHODE_TPE]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('comptes.data', 1)
                ->where('comptes.data.0.type', Encaissement::METHODE_TPE)
                ->where('comptes.data.0.compteMethode', true)
                ->where('comptes.data.0.centre', $this->centre->nom_centre)
                ->where('comptes.data.0.solde', '0.00')
            );

        $this->assertDatabaseHas('caisses', ['type' => Encaissement::METHODE_TPE, 'etablissement_id' => $this->centre->id]);
    }

    public function test_a_method_accounts_solde_is_the_stored_ledger_balance(): void
    {
        $compte = Caisse::query()->where('etablissement_id', $this->centre->id)->where('type', Encaissement::METHODE_VIREMENT)->firstOrFail();
        app(\App\Domain\Finance\Support\CaisseLedger::class)->credit($compte->id, 1300, 'test');
        $this->makeEncaissement($compte, '1000', Encaissement::METHODE_VIREMENT);
        $this->makeEncaissement($compte, '500', Encaissement::METHODE_VIREMENT);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Encaissement::METHODE_VIREMENT]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('comptes.data', 1)
                ->where('comptes.data.0.encaissements', '1500.00')
                // Stored, not recomputed from the movement totals.
                ->where('comptes.data.0.solde', '1300.00')
            );
    }

    public function test_especes_never_gets_its_own_account_type(): void
    {
        // Cash physically lands in an employee's till, which IS the
        // "Caissière" row — an "Espèces" account would double-count it.
        $till = Caisse::factory()->create(['type' => Caisse::TYPE_CAISSIERE, 'etablissement_id' => $this->centre->id]);
        $this->makeEncaissement($till, '750', Encaissement::METHODE_ESPECES);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes']))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $types = collect($page->toArray()['props']['comptes']['data'])->pluck('type');
                $this->assertFalse($types->contains(Encaissement::METHODE_ESPECES));
            });
    }

    public function test_the_three_method_accounts_are_listed_unfiltered(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes']))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $types = collect($page->toArray()['props']['comptes']['data'])->pluck('type');
                foreach ([Encaissement::METHODE_TPE, Encaissement::METHODE_CHEQUE, Encaissement::METHODE_VIREMENT] as $methode) {
                    // Present even at zero — the account exists as soon as the
                    // centre does, whether or not anyone has paid that way yet.
                    $this->assertTrue($types->contains($methode), "Missing account for [{$methode}].");
                }
            });
    }

    // ---------------------------------------------------------------
    // Listing
    // ---------------------------------------------------------------

    public function test_the_row_totals_come_from_the_accounts_own_movements(): void
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $caisse = Caisse::factory()->create([
            'type' => Caisse::TYPE_CAISSIERE,
            'etablissement_id' => $this->centre->id,
            'responsable_employee_id' => $employee->id,
            'solde' => 100,
        ]);
        $autre = Caisse::factory()->create(['type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id]);

        $this->makeEncaissement($caisse, '700');
        $this->makeEncaissement($caisse, '300');
        $this->makeDepense($caisse, '250');
        // Belongs to the OTHER account — must not bleed into $caisse's totals.
        $this->makeEncaissement($autre, '9999');

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteSearch' => $caisse->nom]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('comptes.data', 1)
                ->where('comptes.data.0.encaissements', '1000.00')
                ->where('comptes.data.0.depenses', '250.00')
                ->where('comptes.data.0.solde', '100.00')
            );
    }

    public function test_the_type_filter_narrows_the_list(): void
    {
        Caisse::factory()->create(['type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id]);
        Caisse::factory()->create(['type' => Caisse::TYPE_CAISSIERE, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Caisse::TYPE_EXTERNE]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('comptes.data', 1)
                ->where('comptes.data.0.type', Caisse::TYPE_EXTERNE)
            );
    }

    public function test_the_search_is_case_insensitive(): void
    {
        Caisse::factory()->create(['nom' => 'Externe coffre', 'type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id]);

        // PostgreSQL LIKE is case-sensitive — this asserts the ILIKE required
        // by CLAUDE.md §17 is really in place.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteSearch' => 'externe COFFRE']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('comptes.data', 1));
    }

    public function test_the_list_is_not_scoped_to_the_active_center(): void
    {
        $autreCentre = Etablissement::factory()->create();
        Caisse::factory()->create(['type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id]);
        Caisse::factory()->create(['type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $autreCentre->id]);

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Caisse::TYPE_EXTERNE]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('comptes.data', 2));
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    public function test_creating_an_account_is_refused_without_the_permission(): void
    {
        $this->actingAs($this->userWith('cash-registers.view', 'cash-registers.create'))
            ->post(route('backoffice.caisses.store'), [
                'nom' => 'Externe test',
                'type' => Caisse::TYPE_EXTERNE,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('caisses', ['nom' => 'Externe test']);
    }

    public function test_super_admin_creates_an_externe_account_opening_at_zero(): void
    {
        // The form is just Type + Nom (+ Centre): no opening-balance field,
        // so a balance is never typed by hand anywhere in the app.
        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.caisses.store'), [
                'nom' => 'Externe coffre',
                'type' => Caisse::TYPE_EXTERNE,
                'etablissement_id' => $this->centre->id,
            ])
            ->assertRedirect(route('backoffice.caisses.index', ['tab' => 'comptes']));

        $this->assertDatabaseHas('caisses', [
            'nom' => 'Externe coffre',
            'type' => Caisse::TYPE_EXTERNE,
            'solde' => '0.00',
            'statut' => Caisse::STATUT_ACTIVE,
        ]);
    }

    public function test_an_opening_balance_can_never_be_smuggled_in_at_creation(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.caisses.store'), [
                'nom' => 'Externe truqué',
                'type' => Caisse::TYPE_EXTERNE,
                'solde' => '999999',
            ])
            ->assertRedirect();

        // Ignored: the account still opens at zero, so the only way money
        // ever reaches it is through CaisseLedger (and thus the journal).
        $this->assertDatabaseHas('caisses', ['nom' => 'Externe truqué', 'solde' => '0.00']);
    }

    public function test_a_caissiere_till_can_never_be_created_by_hand(): void
    {
        // Employee tills belong to CaisseProvisioner (EmployeeObserver) so
        // that exactly one exists per employee — a hand-made second one
        // would silently split their balance.
        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.caisses.store'), [
                'nom' => 'Caissière fantôme',
                'type' => Caisse::TYPE_CAISSIERE,
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('caisses', ['nom' => 'Caissière fantôme']);
    }

    /**
     * TPE / Chèque / Virement are payment methods aggregated live — creating
     * a row for one would make a second, drifting copy of the same money.
     */
    public function test_a_payment_method_can_never_be_created_as_an_account(): void
    {
        foreach ([Encaissement::METHODE_TPE, Encaissement::METHODE_CHEQUE, Encaissement::METHODE_VIREMENT] as $methode) {
            $this->actingAs($this->superAdmin())
                ->post(route('backoffice.caisses.store'), [
                    'nom' => "Compte {$methode}",
                    'type' => $methode,
                ])
                ->assertSessionHasErrors('type');

            $this->assertDatabaseMissing('caisses', ['nom' => "Compte {$methode}"]);
        }
    }

    public function test_an_unknown_type_is_refused(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.caisses.store'), [
                'nom' => 'Compte bizarre',
                'type' => 'Crypto',
            ])
            ->assertSessionHasErrors('type');
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function test_updating_an_account_never_moves_its_balance_or_changes_its_type(): void
    {
        $caisse = Caisse::factory()->create([
            'nom' => 'Externe ancien',
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->centre->id,
            'solde' => 4200,
        ]);

        $this->actingAs($this->superAdmin())
            ->put(route('backoffice.caisses.update', $caisse), [
                'nom' => 'Externe renommé',
                'etablissement_id' => $this->centre->id,
                'statut' => Caisse::STATUT_INACTIVE,
                // Both are smuggled in and must be ignored: a balance moves
                // only through CaisseLedger, and a type is frozen at
                // creation (it would rewrite the meaning of every existing
                // movement).
                'solde' => 999999,
                'type' => Caisse::TYPE_CAISSIERE,
            ])
            ->assertRedirect(route('backoffice.caisses.index', ['tab' => 'comptes']));

        $caisse->refresh();
        $this->assertSame('Externe renommé', $caisse->nom);
        $this->assertSame(Caisse::STATUT_INACTIVE, $caisse->statut);
        $this->assertSame('4200.00', (string) $caisse->solde);
        $this->assertSame(Caisse::TYPE_EXTERNE, $caisse->type);
    }

    public function test_updating_is_refused_without_the_permission(): void
    {
        $caisse = Caisse::factory()->create(['type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($this->userWith('cash-registers.view', 'cash-registers.update'))
            ->put(route('backoffice.caisses.update', $caisse), [
                'nom' => 'Piraté',
                'statut' => Caisse::STATUT_ACTIVE,
            ])
            ->assertForbidden();

        $this->assertSame($caisse->nom, $caisse->fresh()->nom);
    }

    // ---------------------------------------------------------------
    // Delete
    // ---------------------------------------------------------------

    public function test_an_empty_account_is_deletable(): void
    {
        $caisse = Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->centre->id,
            'solde' => 0,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.caisses.destroy', $caisse))
            ->assertRedirect(route('backoffice.caisses.index', ['tab' => 'comptes']));

        $this->assertDatabaseMissing('caisses', ['id' => $caisse->id]);
    }

    public function test_an_account_carrying_movements_is_never_deleted(): void
    {
        // Money records are never deleted (CLAUDE.md §11), so neither is the
        // account they hang off — deactivate it instead.
        $caisse = Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->centre->id,
            'solde' => 0,
        ]);
        $this->makeEncaissement($caisse, '500');

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.caisses.destroy', $caisse))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('caisses', ['id' => $caisse->id]);
    }

    public function test_an_account_still_holding_a_balance_is_never_deleted(): void
    {
        $caisse = Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->centre->id,
            'solde' => 250,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.caisses.destroy', $caisse))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('caisses', ['id' => $caisse->id]);
    }

    public function test_an_employees_own_till_is_never_deletable(): void
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        // EmployeeObserver already provisioned it — that till is owned by
        // CaisseProvisioner, never by this screen.
        $caisse = $employee->caisses()->first() ?? Caisse::factory()->create([
            'type' => Caisse::TYPE_CAISSIERE,
            'etablissement_id' => $this->centre->id,
            'responsable_employee_id' => $employee->id,
            'solde' => 0,
        ]);
        $caisse->update(['solde' => 0]);

        $this->actingAs($this->superAdmin())
            ->delete(route('backoffice.caisses.destroy', $caisse))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('caisses', ['id' => $caisse->id]);
    }

    public function test_deleting_is_refused_without_the_permission(): void
    {
        $caisse = Caisse::factory()->create(['type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id, 'solde' => 0]);

        $this->actingAs($this->userWith('cash-registers.view', 'cash-registers.delete'))
            ->delete(route('backoffice.caisses.destroy', $caisse))
            ->assertForbidden();

        $this->assertDatabaseHas('caisses', ['id' => $caisse->id]);
    }
}
