<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Domain\Groups\Queries\GetGroupsList;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tri « Classification » de la liste des groupes (GetGroupsList::applySort).
 *
 * Le tri est fait en SQL parce que la pagination l'est : trier la page reçue
 * ne trierait que les 10 lignes affichées. Ces tests portent donc sur l'ORDRE
 * RENVOYÉ PAR LA REQUÊTE, pas sur ce qu'un composant réarrangerait.
 */
final class GroupsClassificationSortTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function user(): User
    {
        $user = User::factory()->create();
        foreach (['groups.view', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function group(string $nom, string $niveau): Group
    {
        return Group::create([
            'nom' => $nom,
            'niveau' => $niveau,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
            'capacite_max' => 20,
        ]);
    }

    /** @return list<string> */
    private function sortedNiveaux(User $user, string $sort): array
    {
        $rows = app(GetGroupsList::class)(
            $user, '', Group::STATUT_EN_FORMATION, 100, '', '', '', $sort,
        );

        return array_map(fn (array $row): string => (string) $row['niveau'], $rows->items());
    }

    public function test_classification_sorts_in_cefr_order_not_insertion_order(): void
    {
        // Créés dans le désordre : un tri correct ne peut pas venir de l'ordre
        // d'insertion (que `latest()` renverrait).
        foreach (['B2.3', 'A2.1', 'B1.2', 'A1.1', 'A2.3', 'B2.1'] as $i => $niveau) {
            $this->group("G{$i}", $niveau);
        }

        $this->assertSame(
            ['A1.1', 'A2.1', 'A2.3', 'B1.2', 'B2.1', 'B2.3'],
            $this->sortedNiveaux($this->user(), GetGroupsList::SORT_CLASSIFICATION),
        );
    }

    /**
     * Le cœur du tri : la base contient des paliers NUS que le menu déroulant
     * n'offre pas (9 groupes « A1 » en production au 08/09/2026, dont 8 en
     * formation). Un CASE sur la valeur entière les enverrait tout en bas,
     * après B2.3 — ils doivent ouvrir leur propre palier.
     */
    public function test_a_bare_tier_sorts_with_its_own_block_never_at_the_end(): void
    {
        $this->group('nu', 'A1');
        $this->group('sous-niveau', 'A1.2');
        $this->group('premier', 'A1.1');
        $this->group('plus haut', 'B1.1');

        $this->assertSame(
            ['A1', 'A1.1', 'A1.2', 'B1.1'],
            $this->sortedNiveaux($this->user(), GetGroupsList::SORT_CLASSIFICATION),
        );
    }

    /**
     * Une valeur hors barème n'est jamais filtrée — elle reste listée, à la
     * fin : une ligne mal classée reste une ligne à traiter. `niveau` étant
     * NOT NULL en base, le pire cas réel est la chaîne vide, pas NULL.
     */
    public function test_an_unknown_or_empty_niveau_is_listed_last_never_dropped(): void
    {
        $this->group('inconnu', 'Zzz');
        $this->group('vide', '');
        $this->group('connu', 'A1.1');

        $niveaux = $this->sortedNiveaux($this->user(), GetGroupsList::SORT_CLASSIFICATION);

        $this->assertCount(3, $niveaux, 'aucune ligne ne doit disparaître du tri');
        $this->assertSame('A1.1', $niveaux[0]);
    }

    /** Un suffixe non numérique ne doit pas faire échouer la requête entière. */
    public function test_a_non_numeric_sublevel_does_not_break_the_query(): void
    {
        $this->group('bizarre', 'A1.x');
        $this->group('normal', 'A1.1');

        $this->assertCount(2, $this->sortedNiveaux($this->user(), GetGroupsList::SORT_CLASSIFICATION));
    }

    /**
     * Sans clé unique finale, PostgreSQL peut renvoyer deux ordres différents
     * pour deux pages du même tri : une ligne apparaît alors deux fois, ou
     * jamais. Toutes les lignes ont ici le MÊME niveau, le pire cas.
     */
    public function test_pagination_is_stable_when_every_row_shares_a_niveau(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->group('Groupe identique', 'A1.1');
        }

        $user = $this->user();
        $vus = [];

        for ($page = 1; $page <= 3; $page++) {
            $this->app['request']->merge(['page' => $page]);
            \Illuminate\Pagination\Paginator::currentPageResolver(fn (): int => $page);

            $lignes = app(GetGroupsList::class)(
                $user, '', Group::STATUT_EN_FORMATION, 10, '', '', '', GetGroupsList::SORT_CLASSIFICATION,
            );

            foreach ($lignes->items() as $row) {
                $vus[] = $row['id'];
            }
        }

        $this->assertCount(25, $vus);
        $this->assertCount(25, array_unique($vus), 'aucune ligne ne doit être vue deux fois en paginant');
    }

    public function test_the_default_sort_stays_newest_first(): void
    {
        $vieux = $this->group('vieux', 'B2.3');
        $recent = $this->group('recent', 'A1.1');
        $vieux->forceFill(['created_at' => now()->subYear()])->save();

        $user = $this->user();

        // Sans paramètre : ordre historique, le plus récent d'abord — le tri
        // CEFR mettrait « A1.1 » en tête par coïncidence, donc on vérifie sur
        // un jeu où les deux ordres diffèrent.
        $this->assertSame(['recent', 'vieux'], array_map(
            fn (array $r): string => $r['nom'],
            app(GetGroupsList::class)($user, '', Group::STATUT_EN_FORMATION, 100)->items(),
        ));
    }

    public function test_an_unknown_sort_value_falls_back_to_the_default(): void
    {
        $this->group('un', 'A1.1');

        $this->actingAs($this->user())
            ->get(route('backoffice.groups.index', ['sort' => 'n-importe-quoi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('filters.sort', GetGroupsList::DEFAULT_SORT));
    }
}
