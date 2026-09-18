<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * « Validation des dépenses » must list EVERY pending dépense — both the
 * ordinary ones and the « Paiement prof » ones.
 *
 * The Dépenses / Paiements prof tab split (GetDepensesList::SCOPE_*) is a
 * READABILITY choice for the two browsing tabs. Validation is not browsing:
 * it is the only screen where a pending dépense is approved or refused, so a
 * row it does not list can never be decided and its money stays held in the
 * till indefinitely.
 *
 * Reported 04/09/2026 on production: the tab reused the HORS_PAIEMENT_PROF
 * list, so 10 « Paiement prof » rows were unreachable — the badge announced
 * 5 pending while the database held 15, and the « En attente » figure showed
 * 3 585.12 MAD instead of 35 610.62 MAD.
 */
final class ValidationDepensesScopeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

    private TypeDepense $profType;

    private AnneeScolaire $annee;

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
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
    }

    /** A super-admin — `expenses.approve` is in no role preset by design. */
    private function approver(): User
    {
        $user = User::factory()->create();
        $user->assignRole(\App\Models\Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /**
     * The employee's OWN physical till — EmployeeObserver already provisions
     * one per employee (caisses_une_caissiere_par_employe forbids a second),
     * so it is fetched, never re-created.
     */
    private function till(Employee $employee): Caisse
    {
        $caisse = $employee->till()->first();
        $caisse->update(['solde' => '10000.00']);

        return $caisse->fresh();
    }

    /**
     * `$agent` est réutilisable : créer un Employee déclenche
     * `EmployeeObserver` (login + caisse), donc chaque ligne ferait bouger des
     * requêtes étrangères à ce qu'un test de coût mesure.
     */
    private function pending(TypeDepense $type, string $montant, ?Group $group = null, ?Employee $agent = null): Depense
    {
        $agent ??= Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        return Depense::query()->create([
            'reference' => 'DEP-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'type_depense_id' => $type->id,
            'caisse_id' => $this->till($agent)->id,
            'montant' => $montant,
            'statut' => Depense::STATUT_EN_ATTENTE,
            'date_depense' => '2025-09-30',
            'group_id' => $group?->id,
            'periode_debut' => $group !== null ? '2025-09-01' : null,
            'periode_fin' => $group !== null ? '2025-09-30' : null,
            'description' => 'Ligne de test',
            'agent_id' => $agent->id,
        ]);
    }

    private function group(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    public function test_validation_tab_lists_paiement_prof_rows(): void
    {
        $ordinaire = $this->pending($this->type, '120.00');
        $prof = $this->pending($this->profType, '7650.00', $this->group());

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page) use ($ordinaire, $prof): void {
                $refs = collect($page->toArray()['props']['validationDepenses']['data'])
                    ->pluck('reference')
                    ->all();

                // The whole point: BOTH kinds reach the screen that decides them.
                $this->assertContains($ordinaire->reference, $refs);
                $this->assertContains($prof->reference, $refs);
            });
    }

    public function test_validation_totals_count_both_kinds(): void
    {
        $this->pending($this->type, '120.00');
        $this->pending($this->profType, '7650.00', $this->group());

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page): void {
                $props = $page->toArray()['props'];

                // The badge and the « En attente » figure must report the real
                // amount held across the tills, not the Dépenses tab's share.
                $this->assertSame(2, $props['validationEnAttenteCount']);
                $this->assertSame('7770.00', $props['validationMontantEnAttente']);
            });
    }

    public function test_browsing_tabs_keep_their_split(): void
    {
        $ordinaire = $this->pending($this->type, '120.00');
        $prof = $this->pending($this->profType, '7650.00', $this->group());

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page) use ($ordinaire, $prof): void {
                $props = $page->toArray()['props'];
                $depenses = collect($props['depenses']['data'])->pluck('reference')->all();
                $profs = collect($props['paiementsProf']['data'])->pluck('reference')->all();

                // Unchanged: the readability split still holds for browsing.
                $this->assertContains($ordinaire->reference, $depenses);
                $this->assertNotContains($prof->reference, $depenses);
                $this->assertContains($prof->reference, $profs);
                $this->assertNotContains($ordinaire->reference, $profs);
            });
    }

    /**
     * ⚠ A pending dépense is listed under the centre it was KEYED IN, on both
     * the browsing tab and the validation tab — never under the centre its
     * till happens to be attached to.
     *
     * An employee owns ONE till for life, attached to their PRIMARY centre
     * (§11), yet works in several centres. Before `depenses.etablissement_id`
     * (18/09/2026) the centre was resolved through that till, so an expense
     * keyed while working in another centre disappeared from the screen it
     * had just been created on — and, being pending, could not be approved
     * there either: its money stayed held in the till with no screen able to
     * decide it.
     */
    public function test_a_pending_expense_is_listed_under_the_centre_it_was_keyed_in(): void
    {
        $autre = Etablissement::factory()->create();

        // The agent's till belongs to $this->centre (their primary), but the
        // expense was keyed while working in $autre.
        $ailleurs = $this->pending($this->type, '354.00');
        $ailleurs->update(['etablissement_id' => $autre->id]);

        $ici = $this->pending($this->type, '120.00');
        $ici->update(['etablissement_id' => $this->centre->id]);

        $approver = $this->approver();

        // Working in the OTHER centre: only the row keyed there is listed —
        // on the browsing tab AND on the tab that can approve it.
        $this->actingAs($approver)
            ->post(route('backoffice.context.update'), ['etablissement_id' => $autre->id]);

        $this->actingAs($approver)
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page) use ($ailleurs, $ici): void {
                $props = $page->toArray()['props'];
                $depenses = collect($props['depenses']['data'])->pluck('reference')->all();
                $validation = collect($props['validationDepenses']['data'])->pluck('reference')->all();

                $this->assertContains($ailleurs->reference, $depenses);
                $this->assertNotContains($ici->reference, $depenses);

                // Pending money must be decidable from the centre it belongs to.
                $this->assertContains($ailleurs->reference, $validation);
                $this->assertSame('354.00', $props['validationMontantEnAttente']);
                $this->assertSame(1, $props['validationEnAttenteCount']);
            });

        // And symmetrically from the primary centre — the till's centre does
        // not drag the other row along with it.
        $this->actingAs($approver)
            ->post(route('backoffice.context.update'), ['etablissement_id' => $this->centre->id]);

        $this->actingAs($approver)
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page) use ($ailleurs, $ici): void {
                $props = $page->toArray()['props'];
                $depenses = collect($props['depenses']['data'])->pluck('reference')->all();

                $this->assertContains($ici->reference, $depenses);
                $this->assertNotContains($ailleurs->reference, $depenses);
                $this->assertSame('120.00', $props['validationMontantEnAttente']);
            });
    }

    /**
     * La colonne « Centre » porte le centre d'IMPUTATION, et son coût ne
     * grandit pas avec le nombre de lignes.
     *
     * Deux choses indissociables : (1) la valeur affichée vient de
     * `Depense::centreId()` — la règle qui a filtré la liste — donc une
     * dépense saisie ailleurs que dans le centre de sa caisse affiche le
     * centre de SAISIE, sinon la colonne contredirait le filtre ; (2) les
     * relations sont eager-loadées, sinon chaque ligne déclenche sa propre
     * requête (§17 perf : le nombre de requêtes ne suit jamais le nombre de
     * lignes).
     */
    public function test_the_centre_column_shows_the_charged_centre_at_a_constant_query_cost(): void
    {
        $autre = Etablissement::factory()->create();

        $ailleurs = $this->pending($this->type, '354.00');
        $ailleurs->update(['etablissement_id' => $autre->id]);

        $approver = $this->approver();

        // « Tous les centres » — le seul contexte où la colonne est dessinée.
        $this->actingAs($approver)
            ->post(route('backoffice.context.update'), ['etablissement_id' => '']);

        $this->actingAs($approver)
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page) use ($ailleurs, $autre): void {
                $props = $page->toArray()['props'];

                // Sur « Tous les centres » la colonne s'affiche…
                $this->assertFalse($props['centerLocked']);

                $ligne = collect($props['validationDepenses']['data'])
                    ->firstWhere('reference', $ailleurs->reference);

                // …et porte le centre de SAISIE, pas celui de la caisse.
                $this->assertSame($autre->nom_centre, $ligne['etablissement']);
            });

        // Le coût ne suit pas le nombre de lignes. On compte les requêtes qui
        // LISENT les trois tables de `centreNom()` : eager-loadées elles
        // arrivent en un `where … in (…)` par table, donc leur NOMBRE est
        // constant ; sans eager load il en naîtrait une PAR LIGNE.
        //
        // ⚠ On ne compare pas le total brut du rendu : créer une dépense crée
        // aussi un Employee, et `EmployeeObserver` provisionne login + caisse,
        // si bien que d'autres requêtes bougent pour des raisons étrangères à
        // la colonne. C'est précisément ce qu'un compteur global masquerait.
        // Un SEUL agent et un SEUL groupe, créés AVANT la mesure : les créer
        // entre les deux relevés ferait bouger le compteur pour une raison
        // étrangère à la colonne (EmployeeObserver provisionne login + caisse).
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $groupe = $this->group();

        $this->pending($this->type, '10.00', null, $agent)->update(['etablissement_id' => $autre->id]);
        $this->pending($this->profType, '90.00', $groupe, $agent);

        $avant = $this->centreQueryCount($approver);

        // 6 lignes de plus sur les MÊMES agent et groupe : les trois branches
        // de centreId() sont déjà représentées ci-dessus, donc un eager load
        // correct ne coûte pas une requête de plus.
        for ($i = 0; $i < 5; $i++) {
            $this->pending($this->type, '10.00', null, $agent)->update(['etablissement_id' => $autre->id]);
        }
        $this->pending($this->profType, '90.00', $groupe, $agent);

        $this->assertSame(
            $avant,
            $this->centreQueryCount($approver),
            'La colonne Centre déclenche une requête par ligne — vérifier les eager loads de GetDepensesList.',
        );
    }

    /**
     * Combien de requêtes lisent `etablissements`, `caisses` ou `groups`
     * pendant un rendu de l'écran Dépenses.
     */
    private function centreQueryCount(User $user): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get(route('backoffice.depenses.index'))->assertOk();

        $log = DB::getQueryLog();
        DB::disableQueryLog();

        return count(array_filter(
            $log,
            fn (array $q): bool => (bool) preg_match('/\bfrom "(etablissements|caisses|groups)"/i', $q['query']),
        ));
    }

    public function test_a_paiement_prof_can_actually_be_approved(): void
    {
        $prof = $this->pending($this->profType, '7650.00', $this->group());
        $caisse = $prof->caisse;
        $soldeAvant = (float) $caisse->solde;

        $this->actingAs($this->approver())
            ->put(route('backoffice.depenses.approve', $prof))
            ->assertSessionHasNoErrors();

        $this->assertSame(Depense::STATUT_APPROUVEE, $prof->fresh()->statut);
        // Approval is the single moment the till moves for this expense.
        $this->assertEqualsWithDelta($soldeAvant - 7650.00, (float) $caisse->fresh()->solde, 0.001);
    }
}
