<?php

declare(strict_types=1);

use App\Http\Controllers\Frontoffice\HomeController;
use App\Http\Controllers\Frontoffice\RecuController;
use App\Http\Controllers\Frontoffice\RecuGroupeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontoffice Routes
|--------------------------------------------------------------------------
|
| Routes for the public-facing area (students, parents, visitors).
| Name prefix: frontoffice.
|
| The root URL currently redirects to the Backoffice login (admin-first
| phase). The public home page lives at /home; when the Frontoffice is
| launched, swap the redirect for the home page again.
|
| Keep this file thin: point to controllers only. Never place business
| logic in closures.
|
*/

Route::name('frontoffice.')
    ->group(function (): void {
        Route::redirect('/', '/backoffice/login')->name('root');

        Route::get('/home', HomeController::class)->name('home');
    });

/*
|--------------------------------------------------------------------------
| Reçus étudiants — sous-domaine dédié (recu.glsinstitut.com)
|--------------------------------------------------------------------------
|
| Reçu PDF envoyé à l'ÉTUDIANT par WhatsApp. Publique par nécessité
| (l'étudiant n'a pas de compte) et verrouillée par `signed` : l'URL est
| infalsifiable — on ne peut pas énumérer /1, /2 pour lire les reçus des
| autres — et elle EXPIRE au bout de 7 jours (RecuWhatsAppLink::TTL_DAYS),
| donc un message transféré ne reste pas une porte ouverte à vie.
|
| Quand `RECU_DOMAIN` est défini, ces routes vivent sur CE domaine et nulle
| part ailleurs : le chemin perd son préfixe `/recu` (le sous-domaine le
| porte déjà) et le domaine ne sert RIEN d'autre — aucune route
| authentifiée, aucune session backoffice. Sans la variable (local, ou tant
| que le DNS n'est pas en place), elles retombent sur APP_URL avec leurs
| chemins historiques `/recu/{id}` et `/recu-groupe`.
|
| ⚠ Les deux formes ne coexistent JAMAIS, et c'est délibéré.
| Enregistrer en plus un repli `/recu/{id}` sur APP_URL donnerait deux
| routes qu'une même requête peut atteindre : celle sans contrainte de
| domaine répond en premier, et le lien du sous-domaine se retrouve servi
| par une route dont la signature a été calculée pour un AUTRE host. Un
| ancien lien cesse donc de fonctionner le jour de la bascule (7 jours de
| validité au maximum) : basculer un jour creux, ou renvoyer le reçu.
|
| ⚠ La signature couvre le HOST autant que le chemin :
| `URL::temporarySignedRoute()` doit produire l'URL du sous-domaine, ce que
| `Route::domain()` fait tout seul. Ne JAMAIS reecrire l'hote dans la chaine
| après génération, la signature ne correspondrait plus.
|
*/

$recuDomain = config('app.recu_domain');
$surSousDomaine = is_string($recuDomain) && $recuDomain !== '';

Route::domain($surSousDomaine ? $recuDomain : null)
    ->name('frontoffice.')
    ->group(function () use ($surSousDomaine): void {
        // Variante GROUPÉE du même reçu : les ids voyagent dans la query
        // string, donc la signature les couvre — ajouter ou remplacer un id
        // invalide le lien. Voir Frontoffice\RecuGroupeController.
        //
        // Déclarée AVANT la route à paramètre : sur le sous-domaine,
        // « groupe » serait sinon avalé comme un id d'encaissement.
        Route::get($surSousDomaine ? '/groupe' : '/recu-groupe', RecuGroupeController::class)
            ->middleware('signed')
            ->name('recu-groupe');

        // `whereNumber` : un id d'encaissement est un entier. Sans cette
        // contrainte, sur le sous-domaine la route avalerait n'importe quel
        // premier segment (/favicon.ico, /robots.txt) avant de rendre un 403
        // de signature au lieu d'un 404.
        Route::get($surSousDomaine ? '/{encaissement}' : '/recu/{encaissement}', RecuController::class)
            ->middleware('signed')
            ->whereNumber('encaissement')
            ->name('recu');
    });
