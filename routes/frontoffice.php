<?php

declare(strict_types=1);

use App\Http\Controllers\Frontoffice\HomeController;
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
