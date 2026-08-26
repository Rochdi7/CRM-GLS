<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Caisse globale » + « Comptes de caisse » — which tills are listed.
 *
 * Teachers never cash in and a departed employee's till is dead weight, so an
 * EMPTY till of either is hidden. One that still holds money is NOT hidden:
 * dropping it would erase that money from the card totals while
 * `caisses.solde` still carries it.
 */
final class CaisseTillVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    private function till(string $nom, string $categorie, string $statut, string $solde, ?Etablissement $centre = null): Caisse
    {
        $centre ??= $this->centre;

        $employee = Employee::factory()->create([
            'nom' => $nom,
            'categorie' => $categorie,
            'statut' => $statut,
            'etablissement_id' => $centre->id,
        ]);

        return Caisse::updateOrCreate(
            ['type' => Caisse::TYPE_CAISSIERE, 'responsable_employee_id' => $employee->id],
            ['nom' => $nom, 'etablissement_id' => $centre->id, 'solde' => $solde, 'statut' => 'Active'],
        );
    }

    private function personnelles(User $user): array
    {
        $props = $this->actingAs($user)
            ->get(route('backoffice.caisses.index', ['tab' => 'globale']))
            ->assertOk()
            ->viewData('page')['props'];

        return $props['globale']['comptes'][Caisse::TYPE_CAISSIERE] ?? [];
    }

    public function test_it_hides_empty_teacher_and_inactive_tills_but_keeps_funded_ones(): void
    {
        $admin = User::factory()->create()->assignRole('super-admin')->fresh();

        $this->till('ACTIF PAYEUR', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_ACTIF, '500.00');
        $this->till('ENSEIGNANT VIDE', Employee::CATEGORIE_ENSEIGNANT, Employee::STATUT_ACTIF, '0.00');
        $this->till('INACTIF VIDE', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_INACTIF, '0.00');
        $this->till('ENSEIGNANT AVEC ARGENT', Employee::CATEGORIE_ENSEIGNANT, Employee::STATUT_ACTIF, '120.00');

        $noms = array_column($this->personnelles($admin), 'nom');

        $this->assertContains('ACTIF PAYEUR', $noms);
        $this->assertContains('ENSEIGNANT AVEC ARGENT', $noms, 'A funded till must never be hidden.');
        $this->assertNotContains('ENSEIGNANT VIDE', $noms);
        $this->assertNotContains('INACTIF VIDE', $noms);
    }

    /** @return list<array<string, mixed>> */
    private function comptes(User $user): array
    {
        $props = $this->actingAs($user)
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Caisse::TYPE_CAISSIERE]))
            ->assertOk()
            ->viewData('page')['props'];

        return $props['comptes']['data'] ?? [];
    }

    public function test_comptes_de_caisse_hides_the_same_dormant_tills(): void
    {
        $admin = User::factory()->create()->assignRole('super-admin')->fresh();

        $this->till('ACTIF PAYEUR', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_ACTIF, '500.00');
        $this->till('ENSEIGNANT VIDE', Employee::CATEGORIE_ENSEIGNANT, Employee::STATUT_ACTIF, '0.00');
        $this->till('INACTIF VIDE', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_INACTIF, '0.00');
        $this->till('ENSEIGNANT AVEC ARGENT', Employee::CATEGORIE_ENSEIGNANT, Employee::STATUT_ACTIF, '120.00');

        $noms = array_column($this->comptes($admin), 'nom');

        $this->assertContains('ACTIF PAYEUR', $noms);
        $this->assertContains('ENSEIGNANT AVEC ARGENT', $noms, 'A funded till must never be hidden.');
        $this->assertNotContains('ENSEIGNANT VIDE', $noms);
        $this->assertNotContains('INACTIF VIDE', $noms);
    }

    /**
     * The Comptes tab paginates, so the hidden rows must be gone from the
     * TOTAL as well — a filter applied after paging would leave phantom pages.
     */
    public function test_comptes_de_caisse_excludes_dormant_tills_from_the_total(): void
    {
        $admin = User::factory()->create()->assignRole('super-admin')->fresh();

        $this->till('ACTIF PAYEUR', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_ACTIF, '500.00');
        foreach (range(1, 20) as $i) {
            $this->till("ENSEIGNANT {$i}", Employee::CATEGORIE_ENSEIGNANT, Employee::STATUT_ACTIF, '0.00');
        }

        $props = $this->actingAs($admin)
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Caisse::TYPE_CAISSIERE]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(1, $props['comptes']['total']);
    }

    public function test_comptes_de_caisse_follows_the_active_centre(): void
    {
        $autre = Etablissement::factory()->create();
        $admin = User::factory()->create()->assignRole('super-admin')->fresh();

        $this->till('ICI', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_ACTIF, '100.00');
        $this->till('AILLEURS', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_ACTIF, '200.00', $autre);

        // « Tous les centres » — both.
        $this->assertEqualsCanonicalizing(
            ['ICI', 'AILLEURS'],
            array_column($this->comptes($admin), 'nom'),
        );

        // One centre selected — only its own accounts.
        $this->actingAs($admin)
            ->post(route('backoffice.context.update'), ['etablissement_id' => $this->centre->id])
            ->assertRedirect();

        $this->assertSame(['ICI'], array_column($this->comptes($admin), 'nom'));
    }

    public function test_it_splits_by_the_active_centre_and_shows_all_for_super_admin_on_tous_les_centres(): void
    {
        $autre = Etablissement::factory()->create();
        $admin = User::factory()->create()->assignRole('super-admin')->fresh();

        $this->till('ICI', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_ACTIF, '100.00');
        $this->till('AILLEURS', Employee::CATEGORIE_COMPTABLE, Employee::STATUT_ACTIF, '200.00', $autre);

        // « Tous les centres » (no centre in the context) — everything.
        $this->assertEqualsCanonicalizing(
            ['ICI', 'AILLEURS'],
            array_column($this->personnelles($admin), 'nom'),
        );

        // One centre selected — only that centre's tills.
        $this->actingAs($admin)
            ->post(route('backoffice.context.update'), ['etablissement_id' => $this->centre->id])
            ->assertRedirect();

        $this->assertSame(['ICI'], array_column($this->personnelles($admin), 'nom'));
    }
}
