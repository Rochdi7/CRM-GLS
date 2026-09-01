<?php

declare(strict_types=1);

use App\Http\Controllers\Frontoffice\HomeController;
use App\Http\Controllers\Frontoffice\RecuController;
use App\Http\Controllers\Frontoffice\RecuGroupeController;
use Illuminate\Routing\Middleware\ValidateSignature;
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

        // Reçu PDF envoyé à l'ÉTUDIANT par WhatsApp. Publique par nécessité
        // (l'étudiant n'a pas de compte) et verrouillée par `signed` : l'URL
        // est infalsifiable — on ne peut pas énumérer /recu/1, /recu/2 pour
        // lire les reçus des autres — et elle EXPIRE au bout de 7 jours
        // (RecuWhatsAppLink::TTL_DAYS), donc un message transféré ne reste
        // pas une porte ouverte à vie. Voir Frontoffice\RecuController.
        // `download` est exclu de la signature : ce drapeau choisit la FORME
        // servie (page d'atterrissage ou octets du PDF), jamais QUEL reçu.
        // L'id et l'expiration restent signés. Voir ServesRecuDownload.
        Route::get('/recu/{encaissement}', RecuController::class)
            ->middleware(ValidateSignature::absolute(['download']))
            ->name('recu');

        // Variante GROUPÉE du même reçu : les ids voyagent dans la query
        // string, donc la signature les couvre — ajouter ou remplacer un id
        // invalide le lien. Voir Frontoffice\RecuGroupeController.
        Route::get('/recu-groupe', RecuGroupeController::class)
            ->middleware(ValidateSignature::absolute(['download']))
            ->name('recu-groupe');
    });
