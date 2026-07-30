<?php

declare(strict_types=1);

use App\Http\Controllers\Backoffice\AnneeScolaireController;
use App\Http\Controllers\Backoffice\Auth\ForgotPasswordController;
use App\Http\Controllers\Backoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\Auth\LogoutController;
use App\Http\Controllers\Backoffice\Auth\ResetPasswordController;
use App\Http\Controllers\Backoffice\CaisseController;
use App\Http\Controllers\Backoffice\CaisseManagementController;
use App\Http\Controllers\Backoffice\CaisseTransferController;
use App\Http\Controllers\Backoffice\ContextController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\DepenseController;
use App\Http\Controllers\Backoffice\DepenseManagementController;
use App\Http\Controllers\Backoffice\EncaissementController;
use App\Http\Controllers\Backoffice\EtablissementController;
use App\Http\Controllers\Backoffice\GroupController;
use App\Http\Controllers\Backoffice\GroupHistoriqueController;
use App\Http\Controllers\Backoffice\InscriptionController;
use App\Http\Controllers\Backoffice\InscriptionFeeController;
use App\Http\Controllers\Backoffice\PermissionController;
use App\Http\Controllers\Backoffice\ProfileController;
use App\Http\Controllers\Backoffice\SettingController;
use App\Http\Controllers\Backoffice\SalleController;
use App\Http\Controllers\Backoffice\StudentController;
use App\Livewire\Backoffice\Employees\EmployeesIndex;
use App\Livewire\Backoffice\Encaissements\EncaissementsIndex;
use App\Livewire\Backoffice\Groups\GroupsIndex;
use App\Livewire\Backoffice\Inscriptions\InscriptionsIndex;
use App\Livewire\Backoffice\Roles\RoleForm;
use App\Livewire\Backoffice\Roles\RolesIndex;
use App\Livewire\Backoffice\Students\StudentsIndex;
use App\Livewire\Backoffice\Users\ManageAuthorization;
use App\Livewire\Backoffice\Users\UsersIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice Routes
|--------------------------------------------------------------------------
|
| Routes for the administration area (staff, direction, employees).
| URL prefix : /backoffice
| Name prefix: backoffice.
|
| Keep this file thin: point to controllers or Livewire route components.
| Never place business logic in closures.
|
| Pattern: each module is a Livewire index (list + modal CRUD); controllers
| serve read-only detail pages only. Money records (encaissements, depenses,
| remboursements, transferts) have NO destroy route — ever.
|
*/

Route::prefix('backoffice')
    ->name('backoffice.')
    ->group(function (): void {

        // Guest-only (redirects authenticated users to the dashboard)
        Route::middleware('guest')->group(function (): void {
            Route::get('/login', [LoginController::class, 'show'])->name('login');
            Route::post('/login', [LoginController::class, 'store'])->name('login.store');

            // Password reset (backoffice-scoped, `users` broker).
            // The reset link emailed by the notification points to
            // `backoffice.password.reset` (see AppServiceProvider).
            Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])
                ->name('password.request');
            Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
                ->name('password.email');
            Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])
                ->name('password.reset');
            Route::post('/reset-password', [ResetPasswordController::class, 'store'])
                ->name('password.update');
        });

        // Authenticated area
        Route::middleware('auth')->group(function (): void {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::post('/logout', LogoutController::class)->name('logout');

            // Top-bar academic-year/center switcher (Inertia/React Header;
            // docs/inertia-react-migration-plan.md Phase 4). CurrentContext
            // is the single source of truth shared with every still-Livewire
            // page — no permission gate here beyond `auth`, matching the
            // Livewire ContextSwitcher's own behavior (authorization happens
            // inside CurrentContext::setEtablissement(), not the route).
            Route::post('/context', [ContextController::class, 'update'])->name('context.update');

            // The signed-in user's own profile (no permission gate).
            // Inertia/React (docs/inertia-react-migration-plan.md); the
            // Livewire ProfilePage this replaces is kept, unused, for
            // rollback (Phase 10 removes it).
            Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
            Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
            Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

            // Referential data — managed through the tabbed Settings page
            // (Livewire CRUD tabs: établissements, années scolaires, salles).
            // Access = ANY of centers.view / academic-years.view / rooms.view;
            // each tab + its mutations are gated by that resource's permissions.
            Route::get('settings', SettingController::class)->name('settings');

            // Legacy resource routes for the referential data are kept as
            // permission-protected endpoints (still policy-backed); the Settings
            // page is the primary UI for them.
            Route::resource('etablissements', EtablissementController::class);
            Route::resource('annees-scolaires', AnneeScolaireController::class)->except(['show']);
            Route::resource('salles', SalleController::class)->except(['show']);

            // People — employees CRUD is a Livewire page with modal add/edit
            // (the legacy resource controller is retired for the UI).
            Route::get('employees', EmployeesIndex::class)
                ->middleware('permission:employees.view')->name('employees.index');
            // Students — Livewire list + modal CRUD; controller serves the
            // read-only detail page only.
            Route::get('students', StudentsIndex::class)
                ->middleware('permission:students.view')->name('students.index');
            Route::get('students/{student}', [StudentController::class, 'show'])
                ->name('students.show');

            // Academic — groups are NEVER deleted (schema §6)
            // Groups — Livewire list + modal CRUD (with fee assignment).
            // Never deletable; "Fin de formation" archives via the detail page.
            Route::get('groups', GroupsIndex::class)
                ->middleware('permission:groups.view')->name('groups.index');
            Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
            Route::post('groups/{group}/archive', [GroupController::class, 'archive'])->name('groups.archive');
            Route::get('groups-historique', [GroupHistoriqueController::class, 'index'])
                ->name('groups-historique.index');

            // Enrollments — Livewire list + modal CRUD (with manual fee lines);
            // controller serves the read-only detail page only.
            Route::get('inscriptions', InscriptionsIndex::class)
                ->middleware('permission:registrations.view')->name('inscriptions.index');
            Route::get('inscriptions/{inscription}', [InscriptionController::class, 'show'])
                ->name('inscriptions.show');
            Route::resource('inscription-fees', InscriptionFeeController::class)
                ->only(['store', 'update', 'destroy']);

            // Finance — money records are never deleted (audit trail)
            // Gestion de la caisse — ONE tabbed page (Paramètres pattern):
            // Ma caisse + journal des transactions + transferts + comptes de
            // caisse as Livewire tabs. Access = ANY of the two view
            // permissions (checked in CaisseManagementController); each tab
            // is gated by its own permission in the view AND in each component.
            Route::get('caisses', CaisseManagementController::class)
                ->name('caisses.index');
            Route::get('caisses/{caisse}', [CaisseController::class, 'show'])->name('caisses.show');
            // Payments — Livewire list + modal CRUD; controller serves the
            // read-only receipt page only.
            // ⚠ NEVER add a destroy route: a payment is never deleted.
            Route::get('encaissements', EncaissementsIndex::class)
                ->middleware('permission:payments.view')->name('encaissements.index');
            Route::get('encaissements/{encaissement}', [EncaissementController::class, 'show'])
                ->name('encaissements.show');
            // Gestion des dépenses — ONE tabbed page (Paramètres pattern)
            // hosting dépenses + remboursements + types de dépenses as
            // Livewire tabs. Access = ANY of the three view permissions
            // (checked in DepenseManagementController); each tab is gated by
            // its own permission in the view AND inside each component.
            // ⚠ NEVER add a destroy route: an expense is never deleted.
            Route::get('depenses', DepenseManagementController::class)
                ->name('depenses.index');
            Route::get('depenses/{depense}', [DepenseController::class, 'show'])
                ->name('depenses.show');
            // Legacy entry points — deep-link to their tab on the page above.
            // The permission middleware stays so unauthorized users get 403,
            // not a redirect.
            Route::get('types-depenses', fn () => redirect()->route('backoffice.depenses.index', ['tab' => 'types']))
                ->middleware('permission:expense-types.view')->name('types-depenses.index');
            Route::get('remboursements', fn () => redirect()->route('backoffice.depenses.index', ['tab' => 'remboursements']))
                ->middleware('permission:refunds.view')->name('remboursements.index');

            // Till transfers — two-step request/validate flow (structure doc §7)
            // now lives in the « Transferts » tab of Gestion de la caisse; the
            // legacy URL deep-links there (middleware kept so unauthorized
            // users get 403, not a redirect). Controller = read-only detail.
            Route::get('caisse-transfers', fn () => redirect()->route('backoffice.caisses.index', ['tab' => 'transferts']))
                ->middleware('permission:cash-transfers.view')->name('caisse-transfers.index');
            Route::get('caisse-transfers/{caisse_transfer}', [CaisseTransferController::class, 'show'])
                ->name('caisse-transfers.show');

            // Authorization module (Livewire full-page components).
            // Per-action permission middleware; components re-authorize in
            // mount() AND in every mutation method (defense in depth).
            Route::get('roles', RolesIndex::class)
                ->middleware('permission:roles.view')->name('roles.index');
            Route::get('roles/create', RoleForm::class)
                ->middleware('permission:roles.create')->name('roles.create');
            Route::get('roles/{role}/edit', RoleForm::class)
                ->middleware('permission:roles.update')->name('roles.edit');
            Route::get('permissions', PermissionController::class)
                ->middleware('permission:permissions.view')->name('permissions.index');
            Route::get('users', UsersIndex::class)
                ->middleware('permission:users.view')->name('users.index');
            Route::get('users/{user}/authorization', ManageAuthorization::class)
                ->middleware('permission:users.assign-roles')->name('users.authorization.edit');
        });
    });
