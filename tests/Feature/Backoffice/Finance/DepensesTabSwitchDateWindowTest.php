<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
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
 * « Gestion des dépenses » — l'onglet Dépenses doit répondre la MÊME chose
 * sur l'URL nue de la barre latérale et après un clic d'onglet.
 *
 * Signalé en production le 21/09/2026 : la liste s'ouvrait VIDE sur
 * /backoffice/depenses, puis se remplissait dès qu'on visitait un autre
 * onglet et qu'on revenait. La page étale tout son prop `filters` dans
 * chaque `router.get` — switchTab() compris — si bien qu'un `dateFrom`
 * jamais touché revenait comme une clé PRÉSENTE mais vide. Le contrôleur
 * décidait `$dateFilterEngaged` sur `$request->has(...)`, lisait cela comme
 * « l'utilisateur a saisi une date » et RETIRAIT la fenêtre de l'année
 * active. Deux URLs, deux questions différentes, et l'utilisateur ne
 * pouvait pas savoir laquelle il regardait.
 *
 * Le critère est désormais la VALEUR : vide ⇒ jamais touché (fenêtre
 * d'année), '-' ⇒ effacé explicitement par l'utilisateur (§5 : effacer un
 * filtre doit ÉLARGIR, jamais réduire).
 */
final class DepensesTabSwitchDateWindowTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $centre = Etablissement::factory()->create();
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $employee = Employee::factory()->create([
            'user_id' => $user->id, 'etablissement_id' => $centre->id,
        ]);

        $type = TypeDepense::create([
            'nom' => 'Fournitures', 'is_system' => false,
            'statut' => TypeDepense::STATUT_ACTIF,
        ]);

        // DANS l'année active (2026/2027).
        $courante = Depense::create([
            'reference' => 'DEP-91001',
            'type_depense_id' => $type->id,
            'caisse_id' => $employee->till()->first()->id,
            'montant' => '25.00',
            'statut' => Depense::STATUT_APPROUVEE,
            'date_depense' => '2026-09-15',
            'description' => 'Ligne année active',
            'agent_id' => $employee->id,
        ]);

        // HORS de l'année active (2025/2026).
        $ancienne = Depense::create([
            'reference' => 'DEP-91002',
            'type_depense_id' => $type->id,
            'caisse_id' => $employee->till()->first()->id,
            'montant' => '40.00',
            'statut' => Depense::STATUT_APPROUVEE,
            'date_depense' => '2026-01-15',
            'description' => 'Ligne année précédente',
            'agent_id' => $employee->id,
        ]);

        return [$user->fresh(), $courante, $ancienne];
    }

    /** @return string[] */
    private function references(User $user, array $query): array
    {
        $props = $this->actingAs($user)
            ->get(route('backoffice.depenses.index', $query))
            ->assertOk()
            ->viewData('page')['props'];

        return collect($props['depenses']['data'] ?? [])->pluck('reference')->all();
    }

    public function test_a_tab_switch_answers_exactly_what_the_bare_url_answers(): void
    {
        [$user, $courante, $ancienne] = $this->prepare();

        $bare = $this->references($user, []);

        // Ce que switchTab() envoie : tout le prop `filters` réétalé, dates
        // comprises — vides, car jamais touchées.
        $afterTabSwitch = $this->references($user, [
            'search' => '', 'typeFilter' => '', 'caisseFilter' => '',
            'dateFrom' => '', 'dateTo' => '', 'statutFilter' => '',
            'perPage' => 10, 'tab' => 'depenses',
        ]);

        $this->assertSame(
            $bare,
            $afterTabSwitch,
            'Cliquer sur un onglet a changé le jeu de lignes de l\'onglet '
            .'Dépenses : l\'URL nue et l\'URL d\'après-clic doivent poser la '
            .'MÊME question (bug prod 21/09/2026).',
        );

        $this->assertContains($courante->reference, $bare);
        $this->assertNotContains(
            $ancienne->reference,
            $bare,
            'La fenêtre de l\'année active doit rester le défaut tant '
            .'qu\'aucune date n\'a été saisie.',
        );
    }

    public function test_explicitly_cleared_dates_widen_past_the_active_year(): void
    {
        [$user, $courante, $ancienne] = $this->prepare();

        // Ce que reloadDate() envoie quand l'utilisateur VIDE un champ date.
        $refs = $this->references($user, ['dateFrom' => '-', 'dateTo' => '-']);

        $this->assertContains($courante->reference, $refs);
        $this->assertContains(
            $ancienne->reference,
            $refs,
            'Effacer explicitement les dates doit ÉLARGIR la liste au-delà '
            .'de l\'année active (§5), pas la laisser bornée.',
        );
    }

    /**
     * Après un effacement explicite, les DEUX champs date reviennent vides
     * (« - » n'est pas une date affichable) alors que la fenêtre d'année
     * reste levée. Sans un drapeau porté par le serveur, « Réinitialiser les
     * filtres » se désactiverait et l'utilisateur resterait bloqué sur une
     * liste élargie, sans moyen de revenir à la vue par défaut.
     */
    public function test_the_page_reports_whether_the_year_window_is_lifted(): void
    {
        [$user] = $this->prepare();

        $props = fn (array $q) => $this->actingAs($user)
            ->get(route('backoffice.depenses.index', $q))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertFalse($props([])['dateFilterEngaged']);
        $this->assertTrue($props(['dateFrom' => '-', 'dateTo' => '-'])['dateFilterEngaged']);
        $this->assertTrue($props(['dateFrom' => '2026-01-01'])['dateFilterEngaged']);

        // Le marqueur ne remonte JAMAIS dans les champs : ils doivent rester
        // vides, sinon l'input date afficherait « - ».
        $cleared = $props(['dateFrom' => '-', 'dateTo' => '-']);
        $this->assertSame('', $cleared['filters']['dateFrom']);
        $this->assertSame('', $cleared['filters']['dateTo']);
    }

    /**
     * Un effacement de date SURVIT aux rechargements suivants. Le serveur
     * renvoie les champs vides, donc si la page ne reportait pas le marqueur,
     * une recherche (ou tout autre filtre) renverrait '' et réarmerait la
     * fenêtre d'année : des lignes disparaîtraient en touchant un filtre sans
     * rapport (§5). C'est ce que reload() reporte via `dateFilterEngaged`.
     */
    public function test_a_later_unrelated_filter_keeps_the_window_lifted(): void
    {
        [$user, $courante, $ancienne] = $this->prepare();

        // Ce que reload() envoie pour une recherche APRÈS un effacement de
        // date : le marqueur est reporté, la recherche s'y ajoute.
        $refs = $this->references($user, [
            'dateFrom' => '-', 'dateTo' => '-', 'search' => 'Ligne',
        ]);

        $this->assertContains(
            $ancienne->reference,
            $refs,
            'Chercher après avoir effacé les dates a réarmé la fenêtre '
            .'d\'année : modifier un filtre sans rapport ne doit jamais '
            .'RÉDUIRE le jeu de lignes (§5).',
        );
        $this->assertContains($courante->reference, $refs);
    }

    public function test_an_explicit_date_still_filters(): void
    {
        [$user, $courante, $ancienne] = $this->prepare();

        $refs = $this->references($user, ['dateFrom' => '2026-01-01', 'dateTo' => '2026-01-31']);

        $this->assertContains($ancienne->reference, $refs);
        $this->assertNotContains($courante->reference, $refs);
    }
}
