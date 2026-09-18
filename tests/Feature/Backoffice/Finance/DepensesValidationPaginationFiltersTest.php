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
 * Reproduction of the 14/09/2026 report: on « Validation des dépenses », page
 * 1 lists 48 rows over 5 pages, and clicking « 2 » empties the tab —
 * « Aucune dépense », « Montant total : 0.00 MAD ».
 *
 * The pager is NOT the culprit: it navigates to the paginator's own link URL.
 * The rows vanish because those links drop the CLEARED date filters, which
 * re-arms the active-year window server-side (`$dateFilterEngaged`).
 */
final class DepensesValidationPaginationFiltersTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

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

        // Two contiguous years, exactly like production: the ACTIVE one is
        // 2026/2027, while every dépense below is dated inside 2025/2026.
        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-08-27', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->till = $this->agent->till()->first();
        $this->till->update(['solde' => '100000.00']);
    }

    private function approver(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /** 12 dépenses dated in 2025/2026 — OUTSIDE the active year's window. */
    private function seedDepenses(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Depense::query()->create([
                'reference' => 'DEP-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'type_depense_id' => $this->type->id,
                'caisse_id' => $this->till->id,
                'montant' => '10.00',
                'statut' => Depense::STATUT_EN_ATTENTE,
                'date_depense' => '2026-01-15',
                'description' => 'Ligne de test '.$i,
                'agent_id' => $this->agent->id,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function props(array $query): array
    {
        $props = [];

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index', $query))
            ->assertInertia(function ($page) use (&$props): void {
                $props = $page->toArray()['props'];
            });

        return $props;
    }

    /**
     * Page 1 with the date filters PRESENT-BUT-EMPTY (the URL the filter bar
     * produces) lists the rows — the year window is deliberately not armed.
     */
    public function test_cleared_date_filters_widen_the_validation_tab(): void
    {
        $this->seedDepenses();

        $props = $this->props([
            'tab' => 'validation', 'perPage' => 10,
            'dateFrom' => '', 'dateTo' => '',
            'search' => '', 'typeFilter' => '', 'caisseFilter' => '', 'statutFilter' => '',
        ]);

        $this->assertSame(12, $props['validationDepenses']['total']);
        $this->assertSame('120.00', $props['validationMontantEnAttente']);
    }

    /**
     * THE BUG. The pager follows the paginator's own link URLs, so those
     * links must carry the cleared date keys forward. If they don't, page 2
     * re-arms the year window and the tab empties out.
     */
    public function test_the_pager_links_carry_the_cleared_date_filters(): void
    {
        $this->seedDepenses();

        $props = $this->props([
            'tab' => 'validation', 'perPage' => 10,
            'dateFrom' => '', 'dateTo' => '',
            'search' => '', 'typeFilter' => '', 'caisseFilter' => '', 'statutFilter' => '',
        ]);

        $next = collect($props['validationDepenses']['links'])
            ->first(fn (array $l): bool => $l['url'] !== null && $l['label'] === '2');

        $this->assertNotNull($next, 'Expected a link to page 2.');

        parse_str((string) parse_url($next['url'], PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('dateFrom', $query, 'The page-2 link dropped dateFrom.');
        $this->assertArrayHasKey('dateTo', $query, 'The page-2 link dropped dateTo.');
    }

    /**
     * End to end: following page 2 must keep listing rows. This is the exact
     * user-visible symptom — 48 rows on page 1, « Aucune dépense » on page 2.
     */
    public function test_page_two_still_lists_rows(): void
    {
        $this->seedDepenses();

        $props = $this->props([
            'tab' => 'validation', 'perPage' => 10, 'pageValidation' => 2,
            'dateFrom' => '', 'dateTo' => '',
            'search' => '', 'typeFilter' => '', 'caisseFilter' => '', 'statutFilter' => '',
        ]);

        $this->assertSame(2, $props['validationDepenses']['current_page']);
        $this->assertCount(2, $props['validationDepenses']['data']);
        $this->assertSame(12, $props['validationDepenses']['total']);
    }

    /**
     * Même cause, autre geste (18/09/2026) : approuver ou refuser depuis
     * l'onglet « Validation des dépenses » répondait par un
     * `redirect()->route('backoffice.depenses.index')` NU. La query string
     * disparaissait, donc `tab` ET les clés de date vidées avec elle : le
     * read-model ré-armait la fenêtre de l'année active et l'onglet
     * s'affichait « Aucune dépense / Montant total : 0,00 MAD » — il fallait
     * recharger la page à la main pour revoir les lignes restantes.
     */
    public function test_refusing_returns_to_the_validation_tab_with_its_filters(): void
    {
        $this->seedDepenses();
        $depense = Depense::query()->firstOrFail();

        $filtered = route('backoffice.depenses.index', [
            'tab' => 'validation', 'perPage' => 10,
            'dateFrom' => '', 'dateTo' => '',
            'search' => '', 'typeFilter' => '', 'caisseFilter' => '', 'statutFilter' => '',
        ]);

        $response = $this->actingAs($this->approver())
            ->from($filtered)
            ->put(route('backoffice.depenses.refuse', $depense), ['motif_refus' => 'Doublon']);

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('validation', $query['tab'] ?? null, 'The redirect dropped the open tab.');
        $this->assertArrayHasKey('dateFrom', $query, 'The redirect dropped the cleared dateFrom.');
        $this->assertArrayHasKey('dateTo', $query, 'The redirect dropped the cleared dateTo.');
    }

    /** Le même pour l'approbation — c'est le geste qui débite la caisse. */
    public function test_approving_returns_to_the_validation_tab_with_its_filters(): void
    {
        $this->seedDepenses();
        $depense = Depense::query()->firstOrFail();

        $filtered = route('backoffice.depenses.index', [
            'tab' => 'validation', 'perPage' => 10, 'dateFrom' => '', 'dateTo' => '',
        ]);

        $response = $this->actingAs($this->approver())
            ->from($filtered)
            ->put(route('backoffice.depenses.approve', $depense));

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('validation', $query['tab'] ?? null);
        $this->assertArrayHasKey('dateFrom', $query);
    }

    /**
     * Et le bout en bout : la page servie APRÈS le refus liste encore les
     * lignes restantes, sans rechargement manuel.
     */
    public function test_the_list_served_after_a_refusal_still_has_rows(): void
    {
        $this->seedDepenses();
        $depense = Depense::query()->firstOrFail();

        $filtered = route('backoffice.depenses.index', [
            'tab' => 'validation', 'perPage' => 10,
            'dateFrom' => '', 'dateTo' => '',
            'search' => '', 'typeFilter' => '', 'caisseFilter' => '', 'statutFilter' => '',
        ]);

        $user = $this->approver();

        $location = (string) $this->actingAs($user)
            ->from($filtered)
            ->put(route('backoffice.depenses.refuse', $depense), ['motif_refus' => 'Doublon'])
            ->headers->get('Location');

        $props = [];
        $this->actingAs($user)->get($location)->assertInertia(function ($page) use (&$props): void {
            $props = $page->toArray()['props'];
        });

        $this->assertSame(12, $props['validationDepenses']['total']);
        $this->assertSame('110.00', $props['validationMontantEnAttente']);
    }
}
