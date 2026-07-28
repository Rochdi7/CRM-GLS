<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Livewire\Backoffice\Caisses\CaisseJournal;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gestion de la caisse — the tabbed page (ma caisse / journal / transferts /
 * comptes) and its unified read-only journal component.
 */
final class CaisseManagementPageTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    /** An encaissement + a dépense recorded directly (invariants not under test). */
    private function seedMovements(Caisse $caisse): array
    {
        $student = Student::factory()->create(['etablissement_id' => $caisse->etablissement_id]);
        $agent = Employee::factory()->create(['etablissement_id' => $caisse->etablissement_id]);
        $type = TypeDepense::firstOrCreate(
            ['nom' => 'Loyer'],
            ['is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF],
        );

        // A payment always settles a fee line — build the minimal chain.
        $group = Group::factory()->create([
            'etablissement_id' => $caisse->etablissement_id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-CJ-'.$caisse->id,
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $caisse->etablissement_id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => now()->toDateString(),
            'montant_total' => 1500,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => 1500, 'montant' => 1500,
            'date_echeance' => now()->toDateString(), 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $encaissement = Encaissement::create([
            'reference' => 'ENC-TEST-'.$caisse->id,
            'student_id' => $student->id,
            'inscription_fee_id' => $fee->id,
            'montant' => 1500,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => now()->toDateString(),
            'caisse_id' => $caisse->id,
            'agent_id' => $agent->id,
        ]);

        $depense = Depense::create([
            'reference' => 'DEP-TEST-'.$caisse->id,
            'type_depense_id' => $type->id,
            'caisse_id' => $caisse->id,
            'montant' => 400,
            'date_depense' => now()->toDateString(),
            'agent_id' => $agent->id,
        ]);

        return [$encaissement, $depense];
    }

    // --- Page access --------------------------------------------------------

    public function test_the_page_requires_a_cash_permission(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.caisses.index'))->assertForbidden();

        $u2 = User::factory()->create();
        $u2->givePermissionTo('cash-registers.view');
        $this->actingAs($u2)->get(route('backoffice.caisses.index'))->assertOk();
    }

    public function test_the_legacy_transfers_url_deep_links_to_its_tab(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('cash-transfers.view');
        $this->actingAs($u)
            ->get(route('backoffice.caisse-transfers.index'))
            ->assertRedirect(route('backoffice.caisses.index', ['tab' => 'transferts']));
    }

    public function test_a_transfers_only_user_can_open_the_page(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('cash-transfers.view');
        $this->actingAs($u)->get(route('backoffice.caisses.index'))->assertOk();
    }

    // --- Journal component --------------------------------------------------

    public function test_the_journal_is_forbidden_without_the_view_permission(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u);

        Livewire::test(CaisseJournal::class, ['scope' => 'mine'])->assertForbidden();
    }

    public function test_mine_scope_shows_only_the_own_tills_movements(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('cash-registers.view');
        // The employee's auto-provisioned till.
        $employee = Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        $mine = $employee->caisses()->first();
        $this->seedMovements($mine);

        // Movements in someone else's till must stay invisible.
        $other = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->seedMovements($other);

        Livewire::test(CaisseJournal::class, ['scope' => 'mine'])
            ->assertOk()
            ->assertSee('ENC-TEST-'.$mine->id)
            ->assertSee('DEP-TEST-'.$mine->id)
            ->assertDontSee('ENC-TEST-'.$other->id);
    }

    public function test_all_scope_shows_every_accessible_till(): void
    {
        $this->admin();

        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->seedMovements($caisse);

        Livewire::test(CaisseJournal::class, ['scope' => 'all'])
            ->assertOk()
            ->assertSee('ENC-TEST-'.$caisse->id)
            ->assertSee('DEP-TEST-'.$caisse->id);
    }

    public function test_the_type_filter_narrows_the_journal(): void
    {
        $this->admin();

        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->seedMovements($caisse);

        Livewire::test(CaisseJournal::class, ['scope' => 'all'])
            ->set('typeFilter', CaisseJournal::TYPE_DEPENSE)
            ->assertSee('DEP-TEST-'.$caisse->id)
            ->assertDontSee('ENC-TEST-'.$caisse->id);
    }

    public function test_the_date_filter_bounds_the_journal(): void
    {
        $this->admin();

        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->seedMovements($caisse);

        Livewire::test(CaisseJournal::class, ['scope' => 'all'])
            ->set('dateFrom', now()->addDay()->toDateString())
            ->assertDontSee('ENC-TEST-'.$caisse->id)
            ->assertDontSee('DEP-TEST-'.$caisse->id);
    }

    /**
     * Accounts predating the auto-provisioning rule get their till on first
     * visit — « Ma caisse » must work for every employee-linked account.
     */
    public function test_mine_scope_self_heals_a_missing_till(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('cash-registers.view');
        $employee = Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        // Simulate an employee predating the rule.
        $employee->caisses()->delete();
        $this->assertSame(0, $employee->caisses()->count());
        $this->actingAs($u->fresh());

        Livewire::test(CaisseJournal::class, ['scope' => 'mine'])
            ->assertOk()
            ->assertSee('Caisse — '.$employee->nomComplet());

        $this->assertSame(1, $employee->fresh()->caisses()->count());
    }

    /** The journal never mutates anything — read-only by design. */
    public function test_the_journal_exposes_no_write_actions(): void
    {
        foreach (['create', 'edit', 'save', 'delete'] as $method) {
            $this->assertFalse(method_exists(CaisseJournal::class, $method));
        }
    }
}
