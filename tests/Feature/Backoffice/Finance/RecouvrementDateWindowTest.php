<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Gestion des recouvrements » : la fenêtre par défaut (mois glissant) est
 * appliquée par une REDIRECTION sur visite nue — le motif d'URL canonique
 * d'`EncaissementController@index`. Mais la garde y est plus faible : le
 * SEUL test est `$request->query() === []`, sans l'en-tête
 * `X-Inertia-Partial-Data` ni le marqueur « - » que les deux autres écrans
 * ont reçus après les régressions des 26 et 27/08/2026.
 *
 * `useFilterReset` renvoie CHAQUE clé à '' et `router.get` omet les chaînes
 * vides : effacer les deux dates alors que les autres filtres sont vides
 * produit une query string réellement VIDE, indistinguable d'une première
 * visite. Le contrôleur redirige alors et RÉINJECTE `now()->subMonth()` /
 * `now()` — effacer un filtre RÉTRÉCIT le résultat, ce que §5 interdit.
 *
 * ⚠ Mesuré sur le STATUT **et** la cible. Une version antérieure de ce test
 * n'assertait que l'absence de « dateFrom= » dans `Location` : la requête
 * partielle partait sans `X-Inertia-Version`, repartait en 409 (conflit de
 * version d'assets) avec un `Location` NUL, et l'assertion passait à vide.
 * Un test vert qui ne prouve rien est pire que pas de test.
 */
final class RecouvrementDateWindowTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-08-27', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        Etablissement::factory()->create();

        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user->fresh();
    }

    /** Les en-têtes exacts qu'envoie `reload()` de la page. */
    private function partialHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Backoffice/Recouvrement/Index',
            'X-Inertia-Partial-Data' => 'rows,montantTotal,filters',
        ];
    }

    /**
     * Le comportement VOULU, capturé pour qu'un correctif ne le casse pas :
     * le lien de la barre latérale ouvre bien sur le mois glissant.
     */
    public function test_a_bare_first_visit_redirects_to_the_default_window(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('backoffice.recouvrement.index'));

        $response->assertRedirect();

        $this->assertStringContainsString(
            'dateFrom=',
            (string) $response->headers->get('Location'),
            'Une visite nue doit toujours poser la fenêtre par défaut.',
        );
    }

    /**
     * Le défaut : la page rechargée par elle-même, tous filtres effacés,
     * arrive sans aucune clé — exactement comme une visite nue.
     */
    public function test_clearing_every_filter_does_not_reinject_the_default_window(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->withHeaders($this->partialHeaders())
            ->get(route('backoffice.recouvrement.index'));

        // Une redirection ICI est le bug : elle réinjecte le mois glissant.
        $this->assertNotSame(
            302,
            $response->getStatusCode(),
            'Les deux dates effacées, le recouvrement a redirigé en réinjectant '
            .'la fenêtre du mois glissant : effacer un filtre doit ÉLARGIR (§5). '
            .'Cible : '.(string) $response->headers->get('Location'),
        );
    }
}
