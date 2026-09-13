<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Gestion des dépenses » shows FOUR paginated lists on one page, so each
 * one paginates on its OWN query-string key (GetDepensesList::paginate(...,
 * 'pageProf' | 'pageValidation' | 'page')). A pager that writes `page`
 * instead moves a DIFFERENT tab's list and leaves its own exactly where it
 * was — which is what the Validation and Paiements prof pagers did until
 * 14/09/2026: clicking « 2 » only appended `page=2` to the URL and the tab
 * kept showing page 1 (`?page=2&pageValidation=1&tab=validation`).
 *
 * The client side of the fix is `Components/Tables/Pagination.tsx`, which
 * now reads the key back from the paginator's own link URLs; this test pins
 * the server contract that reading depends on.
 */
final class DepensesTabPaginationTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

    private TypeDepense $profType;

    private Caisse $till;

    private Employee $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->type = TypeDepense::create([
            'nom' => 'Fournitures', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        $this->profType = TypeDepense::create([
            'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF, 'is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        // EmployeeObserver already provisioned the physical till
        // (caisses_une_caissiere_par_employe forbids a second one).
        $this->till = $this->agent->till()->first();
        $this->till->update(['solde' => '100000.00']);
    }

    /** A super-admin — `expenses.approve` is in no role preset by design. */
    private function approver(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function depense(int $n, TypeDepense $type): Depense
    {
        return Depense::query()->create([
            'reference' => 'DEP-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'type_depense_id' => $type->id,
            'caisse_id' => $this->till->id,
            'montant' => '10.00',
            'statut' => Depense::STATUT_EN_ATTENTE,
            'date_depense' => '2025-09-30',
            'description' => 'Ligne de test '.$n,
            'agent_id' => $this->agent->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function props(array $query = []): array
    {
        $props = [];

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index', $query))
            ->assertInertia(function ($page) use (&$props): void {
                $props = $page->toArray()['props'];
            });

        return $props;
    }

    public function test_each_tab_paginates_on_its_own_query_key(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->depense($i, $i <= 6 ? $this->type : $this->profType);
        }

        // 12 rows / 10 per page on the Validation tab (BOTH kinds).
        $props = $this->props(['tab' => 'validation', 'perPage' => 10, 'pageValidation' => 2]);
        $this->assertSame(2, $props['validationDepenses']['current_page']);
        $this->assertCount(2, $props['validationDepenses']['data']);

        // `pageProf` is the Paiements prof tab's own key (6 rows, 5 per page).
        $props = $this->props(['tab' => 'paiements-prof', 'perPage' => 5, 'pageProf' => 2]);
        $this->assertSame(2, $props['paiementsProf']['current_page']);
        $this->assertCount(1, $props['paiementsProf']['data']);
    }

    public function test_the_generic_page_key_does_not_move_the_other_tabs(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->depense($i, $i <= 6 ? $this->type : $this->profType);
        }

        // Exactly the URL the broken pager produced. `page` belongs to the
        // Dépenses tab: the two others must stay on page 1 — so a pager that
        // writes `page` visibly does nothing, which is the bug.
        $props = $this->props(['tab' => 'validation', 'perPage' => 10, 'page' => 2]);

        $this->assertSame(1, $props['validationDepenses']['current_page']);
        $this->assertCount(10, $props['validationDepenses']['data']);
        $this->assertSame(1, $props['paiementsProf']['current_page']);
        $this->assertSame(2, $props['depenses']['current_page']);
    }

    public function test_pagination_links_carry_the_key_the_client_reads_back(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->depense($i, $i <= 6 ? $this->type : $this->profType);
        }

        $props = $this->props(['tab' => 'validation', 'perPage' => 10]);

        // Pagination.tsx derives the key from these URLs (the only param
        // equal to its own link's number on every numbered link). Without
        // `pageValidation=N` in them it would fall back to `page`.
        $numbered = collect($props['validationDepenses']['links'])
            ->filter(fn (array $l): bool => $l['url'] !== null && ctype_digit((string) $l['label']));

        $this->assertGreaterThan(1, $numbered->count());
        $numbered->each(function (array $link): void {
            parse_str((string) parse_url($link['url'], PHP_URL_QUERY), $query);
            $this->assertSame($link['label'], $query['pageValidation'] ?? null);
        });
    }
}
