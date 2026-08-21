<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React "Gestion de la caisse" page
 * (CaisseController@index/@journal) built alongside the unchanged Livewire
 * CaissesIndex/CaisseJournal fallback — see CaissesCrudTest/
 * CaisseManagementPageTest for the Livewire-side coverage of the same
 * business rules (docs/phase-10-finance-audit.md).
 */
final class CaissesInertiaCrudTest extends TestCase
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

    public function test_index_requires_any_of_the_two_view_permissions(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.caisses.index'))
            ->assertForbidden();

        // Only the ACTIVE tab's heavy dataset is computed per request (every
        // tab switch is a real ?tab=… visit). Default tab for a
        // cash-registers viewer is "ma-caisse" → journalMine only. Comptes de
        // caisse is a third tab, but super-admin only (`cash-accounts.*` is
        // in no role) — see ComptesCaisseTest for its own coverage.
        $this->actingAs($this->userWith('cash-registers.view'))
            ->get(route('backoffice.caisses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Caisses/Index', false)
                ->where('canViewCaisses', true)
                ->where('canViewTransfers', false)
                ->has('journalMine')
                ->where('transfers', null)
            );

        // A transfers-only user defaults to the transferts tab.
        $this->actingAs($this->userWith('cash-transfers.view'))
            ->get(route('backoffice.caisses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewCaisses', false)
                ->where('canViewTransfers', true)
                ->has('transfers')
            );
    }

    public function test_journal_endpoint_requires_cash_registers_view(): void
    {
        $this->actingAs($this->userWith('cash-transfers.view'))
            ->get(route('backoffice.caisses.journal', ['scope' => 'mine']))
            ->assertForbidden();

        $this->actingAs($this->userWith('cash-registers.view'))
            ->get(route('backoffice.caisses.journal', ['scope' => 'mine']))
            ->assertOk()
            ->assertJsonStructure(['caissesInScope', 'totalEncaissements', 'totalDepenses', 'solde', 'rows']);
    }

    public function test_journal_mine_scope_shows_only_the_employees_own_till(): void
    {
        $user = $this->userWith('cash-registers.view');
        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($user);

        $myCaisse = $employee->caisses()->first();
        $otherEmployee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $otherCaisse = $otherEmployee->caisses()->first();

        Encaissement::create([
            'reference' => 'ENC-MINE', 'student_id' => \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'inscription_fee_id' => $this->makeFee()->id, 'caisse_id' => $myCaisse->id, 'agent_id' => $employee->id,
            'montant' => 100, 'methode' => 'Espèces', 'date_paiement' => '2025-09-15',
        ]);
        Encaissement::create([
            'reference' => 'ENC-OTHER', 'student_id' => \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'inscription_fee_id' => $this->makeFee()->id, 'caisse_id' => $otherCaisse->id, 'agent_id' => $otherEmployee->id,
            'montant' => 200, 'methode' => 'Espèces', 'date_paiement' => '2025-09-15',
        ]);

        $response = $this->get(route('backoffice.caisses.journal', ['scope' => 'mine']))->json();

        $references = collect($response['rows'])->pluck('reference');
        $this->assertTrue($references->contains('ENC-MINE'));
        $this->assertFalse($references->contains('ENC-OTHER'));
    }

    /**
     * Ports CaisseManagementPageTest::test_all_scope_shows_every_
     * accessible_till (Livewire).
     */
    public function test_journal_all_scope_shows_every_accessible_till(): void
    {
        $this->actingAs($this->userWith('cash-registers.view'));
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        Encaissement::create([
            'reference' => 'ENC-ALL', 'student_id' => \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'inscription_fee_id' => $this->makeFee()->id, 'caisse_id' => $caisse->id,
            'agent_id' => Employee::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => 100, 'methode' => 'Espèces', 'date_paiement' => '2025-09-15',
        ]);

        $response = $this->get(route('backoffice.caisses.journal', ['scope' => 'all']))->json();

        $this->assertTrue(collect($response['rows'])->pluck('reference')->contains('ENC-ALL'));
    }

    /**
     * Ports CaisseManagementPageTest::test_mine_scope_self_heals_a_
     * missing_till (Livewire) — an employee account predating the
     * auto-provisioning rule gets its till provisioned on first "Ma caisse"
     * visit, matching CaisseProvisioner::provisionFor() being called from
     * GetCaisseJournal (ported from CaisseJournal::mount()).
     */
    public function test_journal_mine_scope_self_heals_a_missing_till(): void
    {
        $user = $this->userWith('cash-registers.view');
        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $employee->caisses()->delete();
        $this->assertSame(0, $employee->caisses()->count());
        $this->actingAs($user);

        $this->get(route('backoffice.caisses.journal', ['scope' => 'mine']))->assertOk();

        $this->assertSame(1, $employee->fresh()->caisses()->count());
    }

    /**
     * Ports CaisseManagementPageTest::test_a_transfers_only_user_can_
     * open_the_page + test_the_legacy_transfers_url_deep_links_to_its_tab
     * (Livewire).
     */
    public function test_a_transfers_only_user_can_open_the_page_and_the_legacy_url_redirects(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('cash-transfers.view');
        $this->actingAs($user);

        $this->get(route('backoffice.caisses.index'))->assertOk();

        $this->get(route('backoffice.caisse-transfers.index'))
            ->assertRedirect(route('backoffice.caisses.index', ['tab' => 'transferts']));
    }

    public function test_journal_type_and_date_filters_narrow_the_rows(): void
    {
        $user = $this->userWith('cash-registers.view');
        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($user);
        $caisse = $employee->caisses()->first();

        Encaissement::create([
            'reference' => 'ENC-IN', 'student_id' => \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'inscription_fee_id' => $this->makeFee()->id, 'caisse_id' => $caisse->id, 'agent_id' => $employee->id,
            'montant' => 100, 'methode' => 'Espèces', 'date_paiement' => '2025-09-15',
        ]);
        Encaissement::create([
            'reference' => 'ENC-OUT-OF-RANGE', 'student_id' => \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'inscription_fee_id' => $this->makeFee()->id, 'caisse_id' => $caisse->id, 'agent_id' => $employee->id,
            'montant' => 100, 'methode' => 'Espèces', 'date_paiement' => '2025-01-01',
        ]);

        $response = $this->get(route('backoffice.caisses.journal', [
            'scope' => 'mine', 'typeFilter' => 'paiement', 'dateFrom' => '2025-09-01', 'dateTo' => '2025-09-30',
        ]))->json();

        $references = collect($response['rows'])->pluck('reference');
        $this->assertTrue($references->contains('ENC-IN'));
        $this->assertFalse($references->contains('ENC-OUT-OF-RANGE'));
    }

    private function makeFee(): \App\Models\InscriptionFee
    {
        $group = \App\Models\Group::factory()->create(['etablissement_id' => $this->centre->id]);
        $inscription = \App\Models\Inscription::create([
            'reference' => 'INS-'.uniqid(), 'student_id' => \App\Models\Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'group_id' => $group->id, 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => \App\Models\AnneeScolaire::create([
                'nom' => substr('AS-'.uniqid(), 0, 20), 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
                'par_defaut' => false, 'inscription_ouverte' => true,
            ])->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-01',
        ]);

        return \App\Models\InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais', 'montant' => 1000,
            'date_echeance' => '2025-10-01', 'statut' => 'Non payé',
        ]);
    }
}
