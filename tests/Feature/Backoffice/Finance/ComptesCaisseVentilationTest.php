<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Comptes de caisse » suit le sélecteur de centre (04/09/2026).
 *
 * Même règle et même piège que « Ma caisse » : une caissière n'a qu'UNE caisse
 * à vie (CLAUDE.md §11) mais encaisse pour plusieurs centres. La caisse
 * d'Hafssa Elkhattabi, étiquetée GLS Rabat, portait des paiements GLS Online —
 * et l'onglet annonçait sur Rabat la TOTALITÉ de ses encaissements
 * (112 550 DH) et de son solde (103 900 DH), pendant qu'Online n'en voyait
 * rien.
 *
 * Les trois colonnes monétaires se ventilent donc par centre, sur les mêmes
 * colonnes que les écrans de détail, et la caisse reste visible depuis TOUT
 * centre dont elle détient de l'argent — sinon cet argent devient
 * intransférable depuis le centre qui le possède.
 */
final class ComptesCaisseVentilationTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $online;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->online = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);

        return $user->fresh();
    }

    /** Rejoue Hafssa : caisse rattachée à Rabat, encaisse aussi pour Online. */
    private function hafssa(): Employee
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $agent->syncEtablissements([$this->rabat->id, $this->online->id]);

        $this->cash($agent, $this->rabat, 8000.0);
        $this->cash($agent, $this->online, 500.0);

        return $agent->fresh();
    }

    private function cash(Employee $agent, Etablissement $centre, float $montant): void
    {
        app(EnregistrerEncaissement::class)->handle([
            'student_id' => Student::factory()->create(['etablissement_id' => $centre->id])->id,
            'inscription_fee_id' => null,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-09-01',
            'caisse_id' => $agent->till()->firstOrFail()->id,
        ], $agent);
    }

    /** @return array<string, mixed> the caisse's row on the Comptes tab */
    private function rowFor(User $admin, Employee $agent, ?int $centreId): array
    {
        $this->actingAs($admin);
        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($centreId);

        $till = $agent->till()->firstOrFail();
        $rows = [];

        $this->actingAs($admin)
            ->get(route('backoffice.caisses.index', ['tab' => 'comptes', 'compteTypeFilter' => Caisse::TYPE_CAISSIERE]))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$rows): void {
                $rows = $page->toArray()['props']['comptes']['data'];
            });

        return collect($rows)->firstWhere('id', $till->id) ?? [];
    }

    public function test_le_solde_et_les_totaux_suivent_le_centre_actif(): void
    {
        $agent = $this->hafssa();
        $admin = $this->superAdmin();

        // L'autorité ne bouge pas.
        $this->assertSame('8500.00', (string) $agent->till()->firstOrFail()->solde);

        $rabat = $this->rowFor($admin, $agent, $this->rabat->id);
        $this->assertSame('8000.00', $rabat['solde']);
        $this->assertSame('8000.00', $rabat['encaissements']);

        // La part Online, invisible avant le correctif.
        $online = $this->rowFor($admin, $agent, $this->online->id);
        $this->assertSame('500.00', $online['solde']);
        $this->assertSame('500.00', $online['encaissements']);
    }

    public function test_la_somme_des_parts_retombe_sur_le_solde_stocke(): void
    {
        $agent = $this->hafssa();
        $admin = $this->superAdmin();

        $parts = collect([$this->rabat->id, $this->online->id])
            ->sum(fn (int $id): float => (float) $this->rowFor($admin, $agent, $id)['solde']);

        $this->assertSame((float) $agent->till()->firstOrFail()->solde, $parts);
    }

    public function test_tous_les_centres_montre_le_solde_entier(): void
    {
        $agent = $this->hafssa();
        $admin = $this->superAdmin();

        // « Tous les centres » : rien n'est ventilé, le total réseau est lisible.
        $this->assertSame('8500.00', $this->rowFor($admin, $agent, null)['solde']);
    }

    /**
     * Le piège inverse de la ventilation : si la caisse n'apparaissait que sur
     * son centre de rattachement, les 500 DH encaissés pour Online seraient
     * comptés nulle part et intransférables depuis Online.
     */
    public function test_une_caisse_reste_visible_depuis_tout_centre_dont_elle_detient_l_argent(): void
    {
        $agent = $this->hafssa();
        $admin = $this->superAdmin();
        $till = $agent->till()->firstOrFail();

        // La caisse est rattachée à Rabat…
        $this->assertSame($this->rabat->id, $till->etablissement_id);

        // …et reste pourtant listée sur Online, où dorment 500 DH à elle.
        $this->assertNotSame([], $this->rowFor($admin, $agent, $this->online->id));
    }

    /**
     * « Centres affectés » est la source de vérité de la portée (§16) : une
     * caissière affectée à plusieurs centres tient la caisse de chacun, pas
     * seulement de son centre PRIMAIRE.
     *
     * Signalé le 04/09/2026 : en basculant sur GLS Online, la liste ne
     * montrait que les caisses dont Online est le centre primaire — les
     * employées affectées à Online en secondaire disparaissaient, leur solde
     * avec.
     */
    public function test_une_caisse_est_listee_sur_chaque_centre_affecte_au_responsable(): void
    {
        $admin = $this->superAdmin();

        // Primaire Rabat, également affectée à Online, AUCUN encaissement.
        // Catégorie non-enseignante : la caisse vide d'un Enseignant est
        // masquée comme dormante (DormantTill), ce qui n'a rien à voir avec
        // la portée par centre testée ici.
        $agent = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $agent->syncEtablissements([$this->rabat->id, $this->online->id]);

        // Visible sur son centre primaire…
        $this->assertNotSame([], $this->rowFor($admin, $agent, $this->rabat->id));

        // …et sur son centre secondaire, sans avoir besoin d'y avoir encaissé.
        $this->assertNotSame([], $this->rowFor($admin, $agent, $this->online->id));
    }

    public function test_un_centre_ni_affecte_ni_porteur_d_argent_ne_liste_pas_la_caisse(): void
    {
        $agent = $this->hafssa();
        $admin = $this->superAdmin();
        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);

        // Casablanca n'est ni un centre affecté à Hafssa, ni porteur d'un de
        // ses mouvements : rien à y montrer.
        $this->assertSame([], $this->rowFor($admin, $agent, $casa->id));
    }
}
