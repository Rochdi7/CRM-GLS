<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseGlobale;
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
use Tests\TestCase;

/**
 * « Caisse globale » suit le sélecteur de centre (04/09/2026).
 *
 * Troisième écran atteint par le même défaut que « Ma caisse » et « Comptes de
 * caisse » : il filtrait sur `caisses.etablissement_id`, ce qui fait basculer
 * une caisse EN BLOC. Une caissière n'a qu'UNE caisse à vie (CLAUDE.md §11)
 * mais encaisse pour plusieurs centres — sélectionner un centre affichait donc
 * la TOTALITÉ de chaque caisse, y compris l'argent d'un autre centre, et
 * masquait les caissières dont ce centre n'est que secondaire.
 *
 * La règle vit désormais dans `Domain\Finance\Support\VentilationCentre`,
 * partagée par les trois écrans pour qu'ils ne puissent plus diverger.
 */
final class CaisseGlobaleVentilationTest extends TestCase
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
        Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);

        return $user->fresh();
    }

    /** Caisse rattachée à Rabat, encaissant aussi pour Online. */
    private function caissiere(): Employee
    {
        $agent = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
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

    /** @return array<string, mixed> */
    private function globaleFor(User $admin, ?int $centreId): array
    {
        $this->actingAs($admin);
        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($centreId);

        return app(GetCaisseGlobale::class)($admin->fresh());
    }

    private function soldeDe(array $globale, int $caisseId): ?string
    {
        foreach ($globale['comptes'] as $comptes) {
            foreach ($comptes as $compte) {
                if ($compte['id'] === $caisseId) {
                    return $compte['solde'];
                }
            }
        }

        return null;
    }

    public function test_le_solde_affiche_est_celui_du_centre_actif(): void
    {
        $agent = $this->caissiere();
        $admin = $this->superAdmin();
        $till = $agent->till()->firstOrFail();

        // L'autorité ne bouge pas.
        $this->assertSame('8500.00', (string) $till->fresh()->solde);

        $this->assertSame('8000.00', $this->soldeDe($this->globaleFor($admin, $this->rabat->id), $till->id));

        // La part Online, que l'écran attribuait entièrement à Rabat avant.
        $this->assertSame('500.00', $this->soldeDe($this->globaleFor($admin, $this->online->id), $till->id));
    }

    public function test_la_somme_des_parts_retombe_sur_le_solde_stocke(): void
    {
        $agent = $this->caissiere();
        $admin = $this->superAdmin();
        $till = $agent->till()->firstOrFail();

        $parts = collect([$this->rabat->id, $this->online->id])
            ->sum(fn (int $id): float => (float) $this->soldeDe($this->globaleFor($admin, $id), $till->id));

        $this->assertSame((float) $till->fresh()->solde, $parts);
    }

    public function test_tous_les_centres_montre_le_total_reseau(): void
    {
        $agent = $this->caissiere();
        $admin = $this->superAdmin();

        // Rien n'est ventilé : le total réseau reste lisible d'un coup d'œil.
        $this->assertSame('8500.00', $this->soldeDe($this->globaleFor($admin, null), $agent->till()->firstOrFail()->id));
    }

    /**
     * « Centres affectés » gouverne la portée (§16), pas le centre primaire :
     * une caissière affectée à un centre secondaire doit y être listée.
     */
    public function test_une_caisse_est_listee_sur_le_centre_secondaire_de_son_responsable(): void
    {
        $agent = $this->caissiere();
        $admin = $this->superAdmin();
        $till = $agent->till()->firstOrFail();

        $this->assertSame($this->rabat->id, $till->etablissement_id);
        $this->assertNotNull($this->soldeDe($this->globaleFor($admin, $this->online->id), $till->id));
    }

    public function test_un_centre_hors_portee_ne_liste_pas_la_caisse(): void
    {
        $agent = $this->caissiere();
        $admin = $this->superAdmin();
        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);

        $this->assertNull($this->soldeDe($this->globaleFor($admin, $casa->id), $agent->till()->firstOrFail()->id));
    }

    /**
     * Le total des cartes d'en-tête doit décrire les lignes affichées
     * dessous : c'est la leçon du bug « 2 transactions, 500 DH au-dessus d'un
     * solde à 0,00 DH » — un total et sa liste ne lisent jamais deux sources.
     */
    public function test_le_total_des_cartes_egale_la_somme_des_comptes_listes(): void
    {
        $this->caissiere();
        $admin = $this->superAdmin();

        foreach ([$this->rabat->id, $this->online->id, null] as $centreId) {
            $globale = $this->globaleFor($admin, $centreId);

            $sommeDesLignes = collect($globale['comptes'])
                ->flatten(1)
                ->sum(fn (array $compte): float => (float) $compte['solde']);

            $this->assertSame(
                round($sommeDesLignes, 2),
                round((float) $globale['total'], 2),
                'Le total des cartes contredit les comptes listés.',
            );
        }
    }
}
