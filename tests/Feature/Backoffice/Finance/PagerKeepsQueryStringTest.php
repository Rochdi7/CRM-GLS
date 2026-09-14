<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\StockType;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Parfois quand je change de page, ça recharge la page. »
 *
 * Quatre listes paginaient SANS `withQueryString()`, donc leurs liens ne
 * portaient que `?page=N`. Tout le reste de l'URL — l'onglet ouvert, la
 * recherche en cours — tombait au premier clic sur un numéro de page.
 *
 * Le cas le plus visible est l'onglet « Types » de Gestion du stock
 * (/backoffice/stock?tab=types) : sans `tab`, la page 2 revient sur l'onglet
 * « Articles ». L'utilisateur a cliqué « 2 » et se retrouve ailleurs — ce qui
 * se lit comme un rechargement complet.
 */
final class PagerKeepsQueryStringTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $centre = Etablissement::factory()->create();
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    /** @return array<int, array{url: ?string, label: string}> */
    private function numberedLinks(array $paginator): array
    {
        return collect($paginator['links'])
            ->filter(fn (array $l): bool => $l['url'] !== null && ctype_digit((string) $l['label']))
            ->values()
            ->all();
    }

    public function test_stock_types_pager_keeps_the_open_tab(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            StockType::create([
                'nom' => 'Type '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'is_system' => false,
                'statut' => StockType::STATUT_ACTIF,
            ]);
        }

        $props = [];
        $this->actingAs($this->admin())
            ->get('/backoffice/stock?tab=types')
            ->assertInertia(function ($page) use (&$props): void {
                $props = $page->toArray()['props'];
            });

        $links = $this->numberedLinks($props['stockTypesList']);
        $this->assertNotEmpty($links, 'Attendu plusieurs pages de types de stock.');

        foreach ($links as $link) {
            parse_str((string) parse_url($link['url'], PHP_URL_QUERY), $query);
            $this->assertSame(
                'types',
                $query['tab'] ?? null,
                'Le lien de pagination a perdu l\'onglet ouvert : la page 2 '
                .'ramène l\'utilisateur sur « Articles ».',
            );
        }
    }

    public function test_expense_types_pager_keeps_the_search(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            TypeDepense::create([
                'nom' => 'Fourniture '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'is_system' => false,
                'statut' => TypeDepense::STATUT_ACTIF,
            ]);
        }

        $props = [];
        $this->actingAs($this->admin())
            ->get('/backoffice/types-depenses?search=Fourniture')
            ->assertInertia(function ($page) use (&$props): void {
                $props = $page->toArray()['props'];
            });

        foreach ($this->numberedLinks($props['types']) as $link) {
            parse_str((string) parse_url($link['url'], PHP_URL_QUERY), $query);
            $this->assertSame('Fourniture', $query['search'] ?? null, 'Le pager a perdu la recherche.');
        }
    }

    public function test_roles_pager_keeps_the_search(): void
    {
        $props = [];
        $this->actingAs($this->admin())
            ->get('/backoffice/roles?search=a&per_page=5')
            ->assertInertia(function ($page) use (&$props): void {
                $props = $page->toArray()['props'];
            });

        $links = $this->numberedLinks($props['roles']);
        $this->assertNotEmpty($links, 'Attendu plusieurs pages de rôles (13 rôles seedés).');

        foreach ($links as $link) {
            parse_str((string) parse_url($link['url'], PHP_URL_QUERY), $query);
            $this->assertSame('a', $query['search'] ?? null, 'Le pager a perdu la recherche.');
        }
    }
}
