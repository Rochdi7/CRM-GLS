<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Maintenance;

use App\Models\Activity;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Salle;
use App\Models\User;
use App\Support\Access\HiddenAccount;
use App\Support\Database\DatabaseBrowser;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Gestion de la base de données » — ce que l'outil fait, signé en tant
 * que compte de maintenance (l'accès lui-même est couvert par
 * Access\DatabaseManagementAccessTest).
 *
 * Deux garde-fous STRUCTURELS que le tout-puissant doit garder :
 * `activity_log` reste en lecture seule (journal append-only, §11) et
 * chaque écriture est journalisée sous `database` avec la ligne avant /
 * après — c'est la seule trace de ce que l'outil a changé, puisque les
 * requêtes contournent Eloquent et donc `Auditable`.
 */
final class DatabaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $maintainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $centre = Etablissement::factory()->create();
        $this->maintainer = User::factory()->create(['email' => HiddenAccount::EMAIL]);
        $this->maintainer->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $this->maintainer->id, 'etablissement_id' => $centre->id]);
        $this->maintainer = $this->maintainer->fresh();
    }

    public function test_la_liste_montre_chaque_table_avec_son_nombre_de_lignes(): void
    {
        DB::table('banques')->insert([
            ['nom' => 'Attijariwafa', 'statut' => 'Actif'],
            ['nom' => 'BMCE', 'statut' => 'Actif'],
        ]);

        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/DatabaseManagement/Index')
                ->has('database')
                ->where('tables', function ($tables): bool {
                    $tables = collect($tables)->keyBy('name');

                    return $tables['banques']['rows'] === 2
                        && $tables['banques']['editable'] === true
                        && $tables['activity_log']['readOnly'] === true
                        && $tables['activity_log']['editable'] === false;
                }));
    }

    public function test_une_table_se_parcourt_avec_recherche_tri_et_pagination(): void
    {
        DB::table('banques')->insert([
            ['nom' => 'Attijariwafa', 'statut' => 'Actif'],
            ['nom' => 'BMCE', 'statut' => 'Inactif'],
            ['nom' => 'CIH', 'statut' => 'Actif'],
        ]);

        // Recherche insensible à la casse (ILIKE, §17) sur TOUTES les colonnes.
        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/banques?search=inactif')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/DatabaseManagement/Table')
                ->where('table.name', 'banques')
                ->where('table.primaryKey', ['id'])
                ->where('table.readOnly', false)
                ->where('rows.total', 1)
                ->where('rows.data.0.values.nom', 'BMCE')
                ->where('filters.search', 'inactif'));

        // Tri décroissant sur une colonne validée.
        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/banques?sort=nom&direction=desc')
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.values.nom', 'CIH')
                ->where('rows.data.2.values.nom', 'Attijariwafa'));

        // Une colonne de tri inconnue est ignorée (retour au tri par clé),
        // jamais injectée ; la pagination est serveur (10 lignes minimum).
        DB::table('banques')->insert(
            collect(range(1, 9))->map(fn (int $i) => ['nom' => "Banque {$i}", 'statut' => 'Actif'])->all(),
        );

        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/banques?sort=nom;drop&perPage=10')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.per_page', 10)
                ->where('rows.total', 12)
                ->where('rows.last_page', 2)
                ->where('rows.data.0.values.nom', 'Attijariwafa'));
    }

    public function test_les_colonnes_decrivent_le_schema(): void
    {
        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/salles')
            ->assertInertia(fn (Assert $page) => $page
                ->where('columns', function ($columns): bool {
                    $columns = collect($columns)->keyBy('name');

                    return $columns['id']['primary'] === true
                        && $columns['id']['autoIncrement'] === true
                        && $columns['etablissement_id']['references'] === ['table' => 'etablissements', 'column' => 'id'];
                }));
    }

    /**
     * Une clé étrangère se lit comme un NOM à côté de son id — même
     * résolveur que le journal d'audit (`AuditValueResolver`), donc les deux
     * écrans lisent un id de la même façon. L'id reste la valeur stockée :
     * n'afficher que le nom masquerait quelle ligne est référencée.
     */
    public function test_les_cles_etrangeres_se_lisent_comme_des_noms(): void
    {
        $centre = Etablissement::query()->firstOrFail();
        $salle = Salle::factory()->create([
            'etablissement_id' => $centre->id,
        ]);

        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/salles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.values.etablissement_id', (string) $centre->id)
                ->where(
                    'foreignLabels.etablissement_id.'.$centre->id,
                    $centre->nom_centre,
                ));

        $this->assertSame($centre->id, $salle->fresh()->etablissement_id);
    }

    /** Le nombre de requêtes suit le nombre de TABLES référencées, jamais le nombre de lignes. */
    public function test_les_noms_sont_charges_en_lot(): void
    {
        $centre = Etablissement::query()->firstOrFail();
        Salle::factory()->count(12)->create(['etablissement_id' => $centre->id]);

        $browser = app(DatabaseBrowser::class);
        $rows = $browser->rows('salles', ['perPage' => 50]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $labels = $browser->foreignKeyLabels('salles', $rows->items());
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThanOrEqual(12, count($rows->items()));
        $this->assertSame($centre->nom_centre, $labels['etablissement_id'][(string) $centre->id]);
        $this->assertLessThanOrEqual(
            2,
            $queries,
            'les noms doivent être chargés en lot, pas une requête par ligne'
        );
    }

    public function test_une_table_inconnue_repond_404(): void
    {
        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/pg_shadow')
            ->assertNotFound();

        $this->actingAs($this->maintainer)
            ->post('/backoffice/database-management/nope/rows', ['values' => ['x' => 1]])
            ->assertNotFound();
    }

    public function test_ajouter_une_ligne_applique_les_defauts_et_journalise(): void
    {
        $this->actingAs($this->maintainer)
            ->from('/backoffice/database-management/banques')
            ->post('/backoffice/database-management/banques/rows', [
                'values' => ['id' => '', 'nom' => 'Banque Populaire', 'statut' => '', 'created_at' => '', 'updated_at' => ''],
            ])
            ->assertRedirect('/backoffice/database-management/banques')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banques', ['nom' => 'Banque Populaire', 'statut' => 'Actif']);

        $entry = Activity::query()->where('log_name', 'database')->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame('row inserted', $entry->description);
        $this->assertSame($this->maintainer->id, (int) $entry->causer_id);
        $this->assertSame('banques', $entry->properties['table']);
        $this->assertSame('Banque Populaire', $entry->properties['new']['nom']);
    }

    public function test_modifier_une_ligne_journalise_avant_et_apres(): void
    {
        $id = DB::table('banques')->insertGetId(['nom' => 'BMCE', 'statut' => 'Actif']);

        $this->actingAs($this->maintainer)
            ->put('/backoffice/database-management/banques/rows', [
                'key' => ['id' => $id],
                'values' => ['nom' => 'BMCE Bank of Africa', 'statut' => 'Inactif'],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banques', ['id' => $id, 'nom' => 'BMCE Bank of Africa', 'statut' => 'Inactif']);

        $entry = Activity::query()->where('log_name', 'database')->latest('id')->first();
        $this->assertSame('row updated', $entry->description);
        $this->assertSame('BMCE', $entry->properties['old']['nom']);
        $this->assertSame('BMCE Bank of Africa', $entry->properties['new']['nom']);
    }

    public function test_un_champ_vide_devient_null_sur_une_colonne_nullable(): void
    {
        $id = DB::table('banques')->insertGetId(['nom' => 'CIH', 'statut' => 'Actif', 'created_at' => now()]);

        $this->actingAs($this->maintainer)
            ->put('/backoffice/database-management/banques/rows', [
                'key' => ['id' => $id],
                'values' => ['created_at' => ''],
            ])
            ->assertSessionHas('success');

        $this->assertNull(DB::table('banques')->where('id', $id)->value('created_at'));
    }

    public function test_supprimer_une_ligne_journalise_la_ligne_supprimee(): void
    {
        $id = DB::table('banques')->insertGetId(['nom' => 'CIH', 'statut' => 'Actif']);

        $this->actingAs($this->maintainer)
            ->delete('/backoffice/database-management/banques/rows', ['key' => ['id' => $id]])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('banques', ['id' => $id]);

        $entry = Activity::query()->where('log_name', 'database')->latest('id')->first();
        $this->assertSame('row deleted', $entry->description);
        $this->assertSame('CIH', $entry->properties['old']['nom']);
    }

    public function test_un_refus_de_postgresql_revient_comme_erreur_de_formulaire(): void
    {
        // etablissement_id inexistant → violation de clé étrangère, pas une page 500.
        $this->actingAs($this->maintainer)
            ->post('/backoffice/database-management/salles/rows', [
                'values' => ['nom' => 'Salle X', 'etablissement_id' => 999999, 'capacite' => 10],
            ])
            ->assertSessionHasErrors('database');

        $this->assertDatabaseMissing('salles', ['nom' => 'Salle X']);
    }

    public function test_vider_une_table_exige_de_retaper_son_nom(): void
    {
        DB::table('banques')->insert([['nom' => 'A', 'statut' => 'Actif'], ['nom' => 'B', 'statut' => 'Actif']]);

        $this->actingAs($this->maintainer)
            ->post('/backoffice/database-management/banques/truncate', ['confirmation' => 'banque'])
            ->assertSessionHasErrors('confirmation');

        $this->assertSame(2, DB::table('banques')->count());

        $this->actingAs($this->maintainer)
            ->post('/backoffice/database-management/banques/truncate', ['confirmation' => 'banques'])
            ->assertSessionHas('success');

        $this->assertSame(0, DB::table('banques')->count());

        $entry = Activity::query()->where('log_name', 'database')->latest('id')->first();
        $this->assertSame('table emptied', $entry->description);
        $this->assertSame(2, $entry->properties['old']['rows']);
    }

    /** Le journal d'audit est append-only : même l'outil de maintenance n'y écrit pas. */
    public function test_activity_log_est_en_lecture_seule(): void
    {
        $this->assertContains('activity_log', DatabaseBrowser::READ_ONLY_TABLES);

        $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/activity_log')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('table.readOnly', true));

        $this->actingAs($this->maintainer)
            ->post('/backoffice/database-management/activity_log/rows', ['values' => ['description' => 'forged']])
            ->assertSessionHasErrors('database');

        $this->actingAs($this->maintainer)
            ->delete('/backoffice/database-management/activity_log/rows', ['key' => ['id' => 1]])
            ->assertSessionHasErrors('database');

        $this->actingAs($this->maintainer)
            ->post('/backoffice/database-management/activity_log/truncate', ['confirmation' => 'activity_log'])
            ->assertSessionHasErrors('database');

        $this->assertDatabaseMissing('activity_log', ['description' => 'forged']);
    }

    public function test_export_csv_contient_entete_et_lignes(): void
    {
        DB::table('banques')->insert([['nom' => 'Attijariwafa', 'statut' => 'Actif']]);

        $response = $this->actingAs($this->maintainer)
            ->get('/backoffice/database-management/banques/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('id;nom;statut', $csv);
        $this->assertStringContainsString('Attijariwafa;Actif', $csv);
    }
}
