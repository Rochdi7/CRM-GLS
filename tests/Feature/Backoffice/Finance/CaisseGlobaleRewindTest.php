<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseGlobale;
use App\Domain\Finance\Support\CaisseResolver;
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
 * « Caisse globale » : la fenêtre de dates rembobine les QUATRE cartes
 * (09/09/2026).
 *
 * Signalé : « le filtre date de début / date de fin ne s'applique qu'à Caisse
 * personnelle, alors qu'il doit s'appliquer aussi à TPE, chèque et virement ».
 * La cause n'était pas l'écran mais la SOURCE du rembobinage :
 * GetCaisseGlobale::soldesAt() soustrayait les écritures `solde_movement` du
 * journal, or celui-ci couvre quasi exclusivement les caisses physiques
 * (23 810 écritures en base, contre 1 pour les comptes méthode, dont les
 * soldes ont été reconstitués par `caisse:recalculer-soldes` plutôt
 * qu'accumulés mouvement par mouvement). Σ(mouvements après la date) valait
 * donc ~0 pour TPE / Virement / Chèque : leur carte réaffichait le solde
 * D'AUJOURD'HUI sous une date PASSÉE — le pire des résultats, puisque le
 * chiffre a l'air d'une réponse.
 *
 * Le rembobinage se fait désormais depuis les tables SOURCES, sur leurs dates
 * MÉTIER, ce qui corrige au passage un second défaut : l'import legacy a écrit
 * 23 437 de ses écritures le 26/08/2026 quelle que soit la `date_paiement`
 * réelle, si bien que même les caisses physiques rembobinaient au mauvais jour.
 */
final class CaisseGlobaleRewindTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);

        return $user->fresh();
    }

    private function agent(): Employee
    {
        return Employee::factory()->create([
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
    }

    /**
     * Espèces ⇒ la caisse physique de l'agent ; TPE / Virement / Chèque ⇒ le
     * compte du centre pour cette méthode — exactement la résolution du
     * contrôleur (CaisseResolver, §11), pour que le test peuple les quatre
     * comptes comme la production.
     */
    private function encaisser(Employee $agent, string $methode, float $montant, string $date): void
    {
        $caisse = app(CaisseResolver::class)->resolveFor($agent, $methode, $this->centre->id);

        app(EnregistrerEncaissement::class)->handle([
            'student_id' => Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'inscription_fee_id' => null,
            'montant' => $montant,
            'methode' => $methode,
            'date_paiement' => $date,
            'caisse_id' => $caisse->id,
        ], $agent);
    }

    /** @return array<string, string> label de carte => total */
    private function cartes(User $admin, ?string $dateTo): array
    {
        $this->actingAs($admin);
        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $globale = app(GetCaisseGlobale::class)(
            $admin->fresh(),
            $dateTo === null ? [] : ['dateTo' => $dateTo],
        );

        $cartes = [];

        foreach ($globale['cards'] as $carte) {
            $cartes[$carte['label']] = $carte['total'];
        }

        return $cartes;
    }

    /**
     * Le cœur du bug : chaque méthode encaisse en janvier puis en mars, et une
     * date de fin en février doit couper la seconde moitié sur les QUATRE
     * cartes — pas seulement sur « Caisse personnelle ».
     */
    public function test_la_date_de_fin_rembobine_les_quatre_cartes(): void
    {
        $agent = $this->agent();
        $admin = $this->superAdmin();

        $methodes = [
            Encaissement::METHODE_ESPECES => 'Caisse personnelle',
            Encaissement::METHODE_TPE => 'Caisse TPE',
            Encaissement::METHODE_VIREMENT => 'Caisse bancaire',
            Encaissement::METHODE_CHEQUE => 'Caisse chèque',
        ];

        foreach (array_keys($methodes) as $methode) {
            $this->encaisser($agent, $methode, 1000.0, '2027-01-15');
            $this->encaisser($agent, $methode, 500.0, '2027-03-15');
        }

        $aujourdhui = $this->cartes($admin, null);
        $fevrier = $this->cartes($admin, '2027-02-28');

        foreach ($methodes as $carte) {
            $this->assertSame('1500.00', $aujourdhui[$carte], "Sans date, {$carte} montre tout.");
            // Au 28/02 le compte ne détient que l'encaissement de janvier :
            // celui de mars n'a pas encore eu lieu. La régression faisait
            // répondre « 1500.00 » à TPE / bancaire / chèque — le solde
            // d'aujourd'hui sous une date passée.
            $this->assertSame('1000.00', $fevrier[$carte], "Au 28/02, {$carte} ne doit pas encore compter le paiement de mars.");
        }
    }

    /**
     * Une date de fin ANTÉRIEURE au premier paiement ne doit pas afficher
     * 0,00 DH comme si les caisses étaient vides : la page annonce qu'elle ne
     * sait pas (`avantJournal`) et rend les soldes stockés.
     */
    public function test_une_date_anterieure_au_premier_paiement_ne_pretend_pas_zero(): void
    {
        $agent = $this->agent();
        $admin = $this->superAdmin();
        $this->encaisser($agent, Encaissement::METHODE_ESPECES, 2000.0, '2027-01-15');

        $this->actingAs($admin);
        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $globale = app(GetCaisseGlobale::class)($admin->fresh(), ['dateTo' => '2020-01-01']);

        $this->assertTrue($globale['avantJournal']);
        $this->assertNull($globale['asOf']);
        $this->assertSame('2027-01-15', $globale['journalDepuis']);
    }

    /**
     * Le rembobinage lit les dates MÉTIER, pas la date d'écriture. Un paiement
     * saisi aujourd'hui pour une date passée doit compter dans le solde de
     * cette date passée — c'est exactement ce que faisait l'import legacy sur
     * 23 437 lignes.
     */
    public function test_le_rembobinage_suit_la_date_de_paiement_pas_la_date_de_saisie(): void
    {
        $agent = $this->agent();
        $admin = $this->superAdmin();

        // Écrit maintenant, daté d'il y a longtemps.
        $this->encaisser($agent, Encaissement::METHODE_TPE, 700.0, '2027-01-10');

        $this->assertSame('700.00', $this->cartes($admin, '2027-01-31')['Caisse TPE']);
    }

    /**
     * Un compte méthode n'est pas une caisse physique : il n'a pas de
     * responsable et son solde reste rattaché à son centre. Le rembobinage ne
     * doit pas le faire disparaître de l'écran.
     */
    public function test_les_comptes_methode_restent_listes_sous_une_date(): void
    {
        $agent = $this->agent();
        $admin = $this->superAdmin();
        $this->encaisser($agent, Encaissement::METHODE_TPE, 300.0, '2027-01-10');

        $this->actingAs($admin);
        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $globale = app(GetCaisseGlobale::class)($admin->fresh(), ['dateTo' => '2027-01-31']);

        $this->assertNotEmpty($globale['comptes'][Caisse::TYPE_TPE] ?? []);
    }
}
