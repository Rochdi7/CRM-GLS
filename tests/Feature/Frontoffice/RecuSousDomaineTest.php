<?php

declare(strict_types=1);

namespace Tests\Feature\Frontoffice;

use App\Domain\Payments\Support\RecuWhatsAppLink;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Reçus servis depuis leur SOUS-DOMAINE dédié (`recu.glsinstitut.com`).
 *
 * Le reste de la suite tourne sans `RECU_DOMAIN` et verrouille les chemins
 * historiques (`/recu/{id}`) — voir RecuWhatsAppTest. Ici on force la
 * configuration de PRODUCTION et on vérifie les trois propriétés qui la
 * rendent sûre :
 *
 *  1. le lien envoyé à l'étudiant naît sur le sous-domaine ;
 *  2. sa signature reste valide LÀ (elle couvre le host, donc une URL
 *     générée pour un domaine et servie depuis un autre échouerait) ;
 *  3. l'ancien chemin `/recu/{id}` n'existe plus sur APP_URL — sinon deux
 *     routes répondraient à la même requête et la vérification de signature
 *     porterait sur le mauvais host.
 */
final class RecuSousDomaineTest extends TestCase
{
    use RefreshDatabase;

    private const DOMAINE = 'recu.glsinstitut.com';

    private const APP = 'https://app.glsinstitut.com';

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();

        // La configuration de production : backoffice sur APP_URL, reçus sur
        // leur propre sous-domaine.
        config([
            'app.url' => self::APP,
            'app.recu_domain' => self::DOMAINE,
        ]);
        URL::forceRootUrl(self::APP);
        URL::forceScheme('https');

        // Les routes sont construites au boot, AVANT ce config() : sans ce
        // rechargement elles porteraient encore la forme sans sous-domaine et
        // le test ne prouverait rien.
        $this->reloadRecuRoutes();

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    /**
     * Re-enregistre UNIQUEMENT les routes du frontoffice, par-dessus celles
     * du boot. On ne vide surtout pas la collection : le backoffice y vit
     * aussi, et la page 403 rendue par le middleware `signed` a besoin de
     * `backoffice.dashboard` pour se dessiner.
     *
     * Les nouvelles definitions ecrasent les anciennes a nom egal, donc
     * `frontoffice.recu` pointe ensuite sur la forme sous-domaine.
     */
    private function reloadRecuRoutes(): void
    {
        require base_path('routes/frontoffice.php');
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    /** Un encaissement réel, créé directement (aucun écran backoffice ici). */
    private function payment(): Encaissement
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => 1000,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_PAYE,
        ]);

        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'inscription_fee_id' => $fee->id,
            'caisse_id' => Caisse::factory()->create([
                'etablissement_id' => $this->centre->id,
            ])->id,
            'agent_id' => Employee::factory()->create([
                'etablissement_id' => $this->centre->id,
            ])->id,
            'montant' => 1000,
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-20',
        ]);
    }

    public function test_the_link_sent_to_the_student_lives_on_the_receipt_subdomain(): void
    {
        $url = (new RecuWhatsAppLink())->pdfUrl($this->payment());

        // `parse_url` plutot qu'un prefixe de chaine : l'APP_URL de test
        // porte un port (:8000) que la production n'a pas, et ce test parle
        // du HOTE, pas du port.
        $this->assertSame(self::DOMAINE, parse_url($url, PHP_URL_HOST));
        $this->assertSame('https', parse_url($url, PHP_URL_SCHEME));

        // Le sous-domaine porte déjà le sens « reçu » : le chemin ne le
        // répète pas.
        $this->assertStringNotContainsString('/recu/', $url);
        $this->assertStringNotContainsString(self::APP, $url);
    }

    public function test_the_grouped_link_lives_on_the_receipt_subdomain_too(): void
    {
        $url = (new RecuWhatsAppLink())->pdfUrlGroupe(collect([$this->payment()]));

        $this->assertSame(self::DOMAINE, parse_url($url, PHP_URL_HOST));
        $this->assertSame('/groupe', parse_url($url, PHP_URL_PATH));
    }

    /**
     * La propriété qui porte toute la sécurité : la signature est calculée
     * SUR le host du sous-domaine, donc elle est acceptée quand la requête
     * arrive vraiment là — et le PDF est servi.
     */
    public function test_a_signed_link_is_honoured_on_the_subdomain(): void
    {
        $encaissement = $this->payment();
        $url = (new RecuWhatsAppLink())->pdfUrl($encaissement);

        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );
    }

    /** Sans signature, l'énumération /1, /2… reste fermée sur le sous-domaine. */
    public function test_an_unsigned_link_is_refused_on_the_subdomain(): void
    {
        $encaissement = $this->payment();

        $this->get('https://'.self::DOMAINE.'/'.$encaissement->id)
            ->assertForbidden();
    }

    /**
     * Une signature calculée pour le sous-domaine ne doit PAS ouvrir le même
     * document depuis APP_URL : c'est ce qu'un repli `/recu/{id}` laissé en
     * place casserait. On vérifie qu'aucune route ne répond là.
     */
    public function test_only_the_subdomain_form_is_registered(): void
    {
        // On interroge une table de routage construite a partir du SEUL
        // fichier de routes, comme au boot en production : `reloadRecuRoutes`
        // ajoute par-dessus l'existant, donc compter dans la collection du
        // test verrait aussi la forme chargee au demarrage.
        $routes = $this->recuRoutesAsRegisteredAtBoot();

        $this->assertCount(2, $routes, 'Deux routes recu, pas une de plus : '
            .'un repli /recu/{id} sur APP_URL creerait une seconde route '
            .'atteignable par la meme requete, et la signature serait alors '
            .'verifiee contre le mauvais host.');

        foreach ($routes as $route) {
            $this->assertSame(self::DOMAINE, $route->getDomain());
            $this->assertStringNotContainsString('recu/', $route->uri());
        }
    }

    /**
     * @return array<int, \Illuminate\Routing\Route>
     */
    private function recuRoutesAsRegisteredAtBoot(): array
    {
        $router = new \Illuminate\Routing\Router($this->app['events'], $this->app);

        (function () use ($router): void {
            $Route = \Illuminate\Support\Facades\Route::getFacadeRoot();
            \Illuminate\Support\Facades\Route::swap($router);

            try {
                require base_path('routes/frontoffice.php');
            } finally {
                \Illuminate\Support\Facades\Route::swap($Route);
            }
        })();

        return collect($router->getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with((string) $r->getName(), 'frontoffice.recu'))
            ->values()
            ->all();
    }

    /**
     * Le garde « lien joignable » doit porter sur l'hôte qui apparaît
     * VRAIMENT dans le message. Un APP_URL local n'empêche plus l'envoi dès
     * lors que les reçus sortent sur un vrai sous-domaine public.
     */
    public function test_reachability_is_judged_on_the_receipt_domain_not_app_url(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        $this->assertTrue((new RecuWhatsAppLink())->pdfUrlIsPubliclyReachable());

        // Et un sous-domaine local reste refusé.
        config(['app.recu_domain' => 'recu.localhost']);
        $this->assertFalse((new RecuWhatsAppLink())->pdfUrlIsPubliclyReachable());
    }
}
