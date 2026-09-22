<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Reports;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Gestion des rapports — « Liste des dépenses » (onglet Dépenses) : la page,
 * ses filtres, et les deux téléchargements.
 *
 * Les deux tests qui portent le sens du document :
 *
 *  - test_the_total_counts_only_approved_expenses : « argent sorti » a UNE
 *    seule définition dans toute l'application, `statut = Approuvée`. Un
 *    relevé qui additionnerait les lignes En attente / Refusée / Annulée
 *    annoncerait une sortie de caisse supérieure à ce que les tiroirs ont
 *    réellement payé — et contredirait la liste Dépenses, la fiche caisse et
 *    le dashboard, qui portent tous ce filtre.
 *  - test_a_pending_or_cancelled_expense_is_still_printed : elles restent
 *    IMPRIMÉES malgré tout, avec leur statut. Une dépense refusée fait partie
 *    de ce qu'un contrôleur vient vérifier ; l'omettre ferait un document qui
 *    ne permet pas d'expliquer pourquoi une somme demandée n'est pas sortie.
 */
final class RapportDepensesTest extends TestCase
{
    use RefreshDatabase;

    private const CLE = 'liste-depenses';

    /** Compteur pour rester dans les varchar de référence. */
    private static int $refSeq = 0;

    private Etablissement $centre;

    private Caisse $caisse;

    private Employee $agent;

    private TypeDepense $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->centre = Etablissement::factory()->create();
        $this->caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->type = TypeDepense::create(['nom' => 'Syndic', 'statut' => TypeDepense::STATUT_ACTIF]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /** @return array<string, string> */
    private function window(array $extra = []): array
    {
        return [
            'rapport' => self::CLE,
            'dateFrom' => '2025-09-01',
            'dateTo' => '2026-08-31',
            ...$extra,
        ];
    }

    private function depense(
        float $montant,
        string $statut = Depense::STATUT_APPROUVEE,
        string $description = 'Charges du mois',
        string $date = '2025-10-01',
        ?TypeDepense $type = null,
    ): Depense {
        return Depense::create([
            'reference' => 'DEP-R'.(++self::$refSeq),
            'type_depense_id' => ($type ?? $this->type)->id,
            'caisse_id' => $this->caisse->id,
            'etablissement_id' => $this->centre->id,
            'agent_id' => $this->agent->id,
            'montant' => $montant,
            'methode_paiement' => Depense::METHODES[0],
            'date_depense' => $date,
            'description' => $description,
            'statut' => $statut,
        ]);
    }

    // ─────────────────────────── Accès ───────────────────────────

    public function test_it_requires_the_reports_permission(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertForbidden();
    }

    public function test_downloads_require_the_reports_permission_too(): void
    {
        $user = $this->userWith('dashboard.view');

        $this->actingAs($user)->get(route('backoffice.rapports.pdf', $this->window()))->assertForbidden();
        $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->window()))->assertForbidden();
    }

    // ─────────────────────── Le rapport existe ───────────────────────

    /**
     * L'onglet Dépenses sert désormais un rapport : le sélecteur ne peut
     * proposer que ce que le serveur sert (RapportCatalogue), donc c'est le
     * catalogue qui doit le porter.
     */
    public function test_the_expenses_tab_offers_the_expenses_report(): void
    {
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.rapport', self::CLE)
                ->where('onglets.4.key', 'depenses')
                ->where('onglets.4.rapports.0.value', self::CLE));
    }

    /** Les filtres dessinés sont décidés par le serveur, jamais par le composant. */
    public function test_it_exposes_its_own_filters(): void
    {
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filtresVisibles', ['typeDepenseFilter', 'statutDepenseFilter']));
    }

    /**
     * Les statuts offerts viennent de la CONSTANTE du modèle : un écran de
     * plus qui recopierait la liste finirait par proposer un statut que la
     * table ne porte pas — ou par en oublier un.
     */
    public function test_the_status_filter_is_served_from_the_model_constant(): void
    {
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where(
                    'statutDepenseOptions',
                    array_map(static fn (string $s): array => ['value' => $s, 'label' => $s], Depense::STATUTS),
                ));
    }

    // ─────────────────── Le total (le cœur du document) ───────────────────

    /**
     * ⚠ LE test du fichier. « Argent sorti » = `statut = Approuvée`, partout.
     * Une dépense En attente retient l'argent dans le tiroir, une Refusée ne
     * l'a jamais bougé, une Annulée l'a rendu par écriture compensatoire :
     * aucune des trois ne compte au total, sinon le relevé annoncerait une
     * sortie supérieure à ce que les caisses ont payé.
     */
    public function test_the_total_counts_only_approved_expenses(): void
    {
        $this->depense(1000.00, Depense::STATUT_APPROUVEE);
        $this->depense(500.00, Depense::STATUT_EN_ATTENTE);
        $this->depense(300.00, Depense::STATUT_REFUSEE);
        $this->depense(200.00, Depense::STATUT_ANNULEE);

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Les QUATRE lignes sont dans le document…
                ->where('nombreLignes', 4)
                // …mais seule l'approuvée est sortie de la caisse.
                ->where('montantTotal', '1 000,00 DH'));
    }

    /**
     * Le pendant du test précédent : une ligne non approuvée reste IMPRIMÉE,
     * avec son statut. L'omettre ferait un document qui ne permet pas
     * d'expliquer pourquoi une somme demandée n'est pas sortie.
     */
    public function test_a_pending_or_cancelled_expense_is_still_printed(): void
    {
        $this->depense(500.00, Depense::STATUT_EN_ATTENTE, 'Achat en attente');
        $this->depense(200.00, Depense::STATUT_ANNULEE, 'Doublon annule');

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.excel', $this->window()));

        $response->assertOk();

        // Le classeur les porte toutes les deux, et le total qu'il rappelle
        // dans son en-tête reste celui de l'argent réellement sorti : 0,00.
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 2)
                ->where('montantTotal', '0,00 DH'));
    }

    // ─────────────────────────── Les filtres ───────────────────────────

    public function test_the_status_filter_restricts_the_document(): void
    {
        $this->depense(1000.00, Depense::STATUT_APPROUVEE);
        $this->depense(500.00, Depense::STATUT_EN_ATTENTE);

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window([
                'statutDepenseFilter' => Depense::STATUT_EN_ATTENTE,
            ])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 1)
                // Filtré sur « En attente », plus rien n'est sorti de la
                // caisse : le total suit le filtre, comme les lignes.
                ->where('montantTotal', '0,00 DH'));
    }

    public function test_the_type_filter_restricts_the_document(): void
    {
        $autre = TypeDepense::create(['nom' => 'Logistiques', 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->depense(1000.00);
        $this->depense(400.00, Depense::STATUT_APPROUVEE, 'Transport', '2025-10-02', $autre);

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window(['typeDepenseFilter' => (string) $autre->id])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 1)
                ->where('montantTotal', '400,00 DH'));
    }

    /**
     * Un statut forgé retombe sur « aucun filtre » plutôt que d'atteindre la
     * requête SQL — et le document sort complet, pas vide.
     */
    public function test_a_forged_status_falls_back_to_no_filter(): void
    {
        $this->depense(1000.00);

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window(['statutDepenseFilter' => 'Valide'])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.statutDepenseFilter', '')
                ->where('nombreLignes', 1));
    }

    /** La fenêtre de dates borne le document — une dépense hors période n'y est pas. */
    public function test_the_date_window_bounds_the_document(): void
    {
        $this->depense(1000.00, Depense::STATUT_APPROUVEE, 'Dans la fenetre', '2025-10-01');
        $this->depense(999.00, Depense::STATUT_APPROUVEE, 'Hors fenetre', '2026-10-01');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 1)
                ->where('montantTotal', '1 000,00 DH'));
    }

    // ─────────────────────── Les téléchargements ───────────────────────

    public function test_it_downloads_a_pdf(): void
    {
        $this->depense(1000.00);

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.pdf', $this->window()));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('liste-depenses', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_it_downloads_an_excel_workbook(): void
    {
        $this->depense(1000.00);

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.excel', $this->window()));

        $response->assertOk();
        $this->assertStringContainsString('liste-depenses', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * Un document vide sort quand même — avec sa mention, plutôt qu'une page
     * blanche que l'utilisateur lirait comme une panne.
     */
    public function test_an_empty_period_still_produces_a_document(): void
    {
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.pdf', $this->window()))
            ->assertOk();
    }
}
