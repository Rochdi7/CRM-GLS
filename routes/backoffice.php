<?php

declare(strict_types=1);

use App\Http\Controllers\Backoffice\AnneeScolaireController;
use App\Http\Controllers\Backoffice\Auth\ForgotPasswordController;
use App\Http\Controllers\Backoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\Auth\LogoutController;
use App\Http\Controllers\Backoffice\Auth\ResetPasswordController;
use App\Http\Controllers\Backoffice\BanqueController;
use App\Http\Controllers\Backoffice\CaisseController;
use App\Http\Controllers\Backoffice\CaisseTransferController;
use App\Http\Controllers\Backoffice\ContextController;
use App\Http\Controllers\Backoffice\CreneauController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\DepenseController;
use App\Http\Controllers\Backoffice\Employees\EmployeeController;
use App\Http\Controllers\Backoffice\EncaissementController;
use App\Http\Controllers\Backoffice\EtablissementController;
use App\Http\Controllers\Backoffice\FraisController;
use App\Http\Controllers\Backoffice\GroupController;
use App\Http\Controllers\Backoffice\GroupHistoriqueController;
use App\Http\Controllers\Backoffice\InscriptionController;
use App\Http\Controllers\Backoffice\PermissionController;
use App\Http\Controllers\Backoffice\ProfileController;
use App\Http\Controllers\Backoffice\RemboursementController;
use App\Http\Controllers\Backoffice\Roles\RoleController;
use App\Http\Controllers\Backoffice\SeanceController;
use App\Http\Controllers\Backoffice\SettingController;
use App\Http\Controllers\Backoffice\SalleController;
use App\Http\Controllers\Backoffice\StockController;
use App\Http\Controllers\Backoffice\StudentController;
use App\Http\Controllers\Backoffice\TypeDepenseController;
use App\Http\Controllers\Backoffice\Users\UserAuthorizationController;
use App\Http\Controllers\Backoffice\Users\UserController;
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
| Keep this file thin: point to controllers only. Never place business
| logic in closures.
|
| Pattern: each module is an Inertia+React list/modal CRUD page backed by
| a thin controller. Money records (encaissements, depenses, remboursements,
| transferts) have NO destroy route — ever.
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
            // Throttled: prevents reset-email bombing of a known address
            // (Phase 12 security hardening). Login POST throttles itself in
            // LoginRequest (5/min per login+IP).
            Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
                ->middleware('throttle:5,1')
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
            // Frais (fee catalog) — new in Phase 6 (docs/phase-6-simple-crud-
            // inventory.md §Q1: no resource controller existed before this
            // phase, only the Livewire FraisTab). Same pattern as the three
            // referential modules above — index/create/edit redirect to
            // Settings, store/update/destroy are the real endpoints.
            Route::resource('frais', FraisController::class)->except(['show']);
            // Banques (bank catalog) — same pattern as Frais above, restricted
            // to super-admin (banks.* permissions are absent from every role
            // in PermissionRegistry::matrix()). Feeds the Chèque payment
            // form's Banque dropdown (Encaissements).
            Route::resource('banques', BanqueController::class)->except(['show']);

            // People — Employees: Inertia/React list + modal add/edit
            // (docs/inertia-react-migration-plan.md Phase 7). The Livewire
            // EmployeesIndex component + its route registration are retired
            // here but the class/view files are kept, unused, for rollback
            // (Phase 10 removes them) — see EmployeeController's docblock for
            // the one deliberate behavior tightening (center-scoped
            // update/delete authorization).
            Route::get('employees', [EmployeeController::class, 'index'])
                ->middleware('permission:employees.view')->name('employees.index');
            Route::post('employees', [EmployeeController::class, 'store'])
                ->middleware('permission:employees.create')->name('employees.store');
            Route::put('employees/{employee}', [EmployeeController::class, 'update'])
                ->middleware('permission:employees.update')->name('employees.update');
            Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
                ->middleware('permission:employees.delete')->name('employees.destroy');
            // Students — Inertia/React list + modal add/edit (Phase 8,
            // docs/phase-8-students-groups-inventory.md). The Livewire
            // StudentsIndex component + its route registration are retired
            // here but the class/view files are kept, unused, for rollback.
            Route::get('students', [StudentController::class, 'index'])
                ->middleware('permission:students.view')->name('students.index');
            Route::post('students', [StudentController::class, 'store'])
                ->middleware('permission:students.create')->name('students.store');
            Route::put('students/{student}', [StudentController::class, 'update'])
                ->middleware('permission:students.update')->name('students.update');
            Route::delete('students/{student}', [StudentController::class, 'destroy'])
                ->middleware('permission:students.delete')->name('students.destroy');
            Route::get('students/{student}', [StudentController::class, 'show'])
                ->name('students.show');

            // Academic — groups are NEVER deleted (schema §6)
            // Groups — Inertia/React list + modal add/edit with per-group fee
            // assignment (Phase 8). Migrated exactly as the current Livewire
            // form exists — no room/capacity/schedule fields (confirmed
            // absent from the live UI, docs/phase-8-students-groups-
            // inventory.md). Never deletable; "Fin de formation" archives via
            // the detail page (Phase 5, unchanged).
            Route::get('groups', [GroupController::class, 'index'])
                ->middleware('permission:groups.view')->name('groups.index');
            Route::post('groups', [GroupController::class, 'store'])
                ->middleware('permission:groups.create')->name('groups.store');
            Route::put('groups/{group}', [GroupController::class, 'update'])
                ->middleware('permission:groups.update')->name('groups.update');
            Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
            Route::post('groups/{group}/archive', [GroupController::class, 'archive'])->name('groups.archive');
            // Quick lifecycle actions from the list's row menu — "Annuler"
            // (-> Annulée, terminal, same groups.archive gate as Fin de
            // formation), "Réactiver" (Annulée -> En inscription), and
            // "Activer" (En inscription -> En formation, training starts).
            Route::post('groups/{group}/annuler', [GroupController::class, 'annuler'])->name('groups.annuler');
            Route::post('groups/{group}/reactiver', [GroupController::class, 'reactiver'])->name('groups.reactiver');
            Route::post('groups/{group}/activer', [GroupController::class, 'activer'])->name('groups.activer');
            Route::get('groups/{group}/students-by-segment', [GroupController::class, 'studentsBySegment'])
                ->name('groups.students-by-segment');
            Route::get('groups-historique', [GroupHistoriqueController::class, 'index'])
                ->name('groups-historique.index');

            // Attendance (Présences) — séances list + modal add/edit; the
            // per-séance fiche de présence lives on the show page, saved as
            // one roll call (attendance.mark). Center + academic year are
            // inherited from the séance's group (SeanceController).
            Route::get('seances', [SeanceController::class, 'index'])
                ->middleware('permission:attendance.view')->name('seances.index');
            Route::post('seances', [SeanceController::class, 'store'])
                ->middleware('permission:attendance.create')->name('seances.store');
            Route::put('seances/{seance}', [SeanceController::class, 'update'])
                ->middleware('permission:attendance.update')->name('seances.update');
            Route::delete('seances/{seance}', [SeanceController::class, 'destroy'])
                ->middleware('permission:attendance.delete')->name('seances.destroy');
            Route::get('seances/{seance}', [SeanceController::class, 'show'])
                ->name('seances.show');
            Route::put('seances/{seance}/presences', [SeanceController::class, 'savePresences'])
                ->middleware('permission:attendance.mark')->name('seances.presences.update');

            // Emploi du temps — weekly recurring schedule grid (créneaux),
            // distinct from the dated séances above. Creating/editing/
            // deleting a créneau generates/syncs its future séances
            // (CreneauController + GenererSeancesDepuisCreneau).
            Route::get('emploi-du-temps', [CreneauController::class, 'index'])
                ->middleware('permission:attendance.view')->name('emploi-du-temps.index');
            Route::post('creneaux', [CreneauController::class, 'store'])
                ->middleware('permission:attendance.create')->name('creneaux.store');
            Route::put('creneaux/{creneau}', [CreneauController::class, 'update'])
                ->middleware('permission:attendance.update')->name('creneaux.update');
            Route::delete('creneaux/{creneau}', [CreneauController::class, 'destroy'])
                ->middleware('permission:attendance.delete')->name('creneaux.destroy');

            // Stock — ONE Inertia page (Articles + Mouvements tabs). Article
            // quantities only move through mouvement endpoints (caisse
            // pattern); movements have NO update/destroy routes — ever
            // (compensating entries only). Articles with history can't be
            // deleted (guard in StockController + restrictOnDelete FK).
            Route::get('stock', [StockController::class, 'index'])
                ->middleware('permission:stock.view')->name('stock.index');
            Route::post('stock-articles', [StockController::class, 'storeArticle'])
                ->middleware('permission:stock.create')->name('stock-articles.store');
            Route::put('stock-articles/{article}', [StockController::class, 'updateArticle'])
                ->middleware('permission:stock.update')->name('stock-articles.update');
            Route::delete('stock-articles/{article}', [StockController::class, 'destroyArticle'])
                ->middleware('permission:stock.delete')->name('stock-articles.destroy');
            Route::post('stock-mouvements', [StockController::class, 'storeMouvement'])
                ->middleware('permission:stock.move')->name('stock-mouvements.store');

            // Enrollments — Inertia/React list + modal add/edit with manual
            // fee lines (Phase 9, docs/phase-9-inscriptions-audit.md +
            // docs/phase-9-inscriptions-mapping.md). Base fields (student/
            // group/statut/dates/note) keep the Livewire form's own
            // create-vs-edit asymmetry; fee-line editing on an existing
            // registration is a separate action below (registrations.manage-fees).
            Route::get('inscriptions', [InscriptionController::class, 'index'])
                ->middleware('permission:registrations.view')->name('inscriptions.index');
            Route::post('inscriptions', [InscriptionController::class, 'store'])
                ->middleware('permission:registrations.create')->name('inscriptions.store');
            Route::put('inscriptions/{inscription}', [InscriptionController::class, 'update'])
                ->middleware('permission:registrations.update')->name('inscriptions.update');
            // Quick status actions from the list's row menu ("Archiver" /
            // "Annuler" / "Réactiver") — a two-way Active <-> {Changement,
            // Annulée} toggle, refused for any other transition
            // (InscriptionController::updateStatut).
            Route::patch('inscriptions/{inscription}/statut', [InscriptionController::class, 'updateStatut'])
                ->middleware('permission:registrations.update')->name('inscriptions.update-statut');
            Route::delete('inscriptions/{inscription}', [InscriptionController::class, 'destroy'])
                ->middleware('permission:registrations.delete')->name('inscriptions.destroy');
            Route::get('inscriptions/{inscription}', [InscriptionController::class, 'show'])
                ->name('inscriptions.show');
            // Fee list for the edit modal (view = registrations.view via
            // policy inside the action); saving changes needs
            // registrations.manage-fees, checked inside updateFees() itself.
            Route::get('inscriptions/{inscription}/fees', [InscriptionController::class, 'fees'])
                ->name('inscriptions.fees');
            Route::put('inscriptions/{inscription}/fees', [InscriptionController::class, 'updateFees'])
                ->middleware('permission:registrations.manage-fees')->name('inscriptions.fees.update');
            Route::post('inscriptions/{inscription}/fees/{fee}/hide', [InscriptionController::class, 'hideFee'])
                ->middleware('permission:registrations.manage-fees')->name('inscriptions.fees.hide');
            Route::post('inscriptions/{inscription}/fees/{fee}/restore', [InscriptionController::class, 'restoreFee'])
                ->middleware('permission:registrations.manage-fees')->name('inscriptions.fees.restore');
            Route::post('inscriptions/{inscription}/change-group', [InscriptionController::class, 'changeGroup'])
                ->middleware('permission:registrations.change-group')->name('inscriptions.change-group');
            // "Frais disponibles" for a group — the create form's live
            // group-fee lookup (docs/phase-9-inscriptions-mapping.md's
            // confirmed decision: a dedicated endpoint, not embedding every
            // group's fees in the initial options payload).
            Route::get('groups/{group}/inscription-fees', [InscriptionController::class, 'groupFees'])
                ->name('groups.inscription-fees');
            // inscription-fees.* (store/update/destroy) intentionally NOT
            // routed here — genuinely dead code pre-Phase-9 (zero callers in
            // any view/React page, confirmed by the audit) and still not
            // part of the live workflow: fee lines are only ever
            // created/edited as part of the parent Inscription's own
            // create transaction (InscriptionController::store()), never
            // standalone.

            // Finance (Phase 10, docs/phase-10-finance-audit.md +
            // docs/phase-10-finance-mapping.md) — money records are never
            // deleted (audit trail). Migrated from Livewire to Inertia+React;
            // legacy Livewire components/Blade views retained unreferenced
            // for rollback (docs/legacy-frontend-removal-plan.md §0g).
            //
            // Gestion de la caisse — ONE Inertia page (Paramètres pattern):
            // Ma caisse + journal des transactions + transferts + comptes de
            // caisse as client-side React tabs, same as the former Livewire
            // tabs. Access = ANY of the two view permissions (checked in
            // CaisseController@index); each tab's data/actions are still
            // gated by their own permission server-side.
            Route::get('caisses', [CaisseController::class, 'index'])->name('caisses.index');
            Route::get('caisses/{caisse}', [CaisseController::class, 'show'])->name('caisses.show');
            Route::get('caisses/journal/{scope}', [CaisseController::class, 'journal'])
                ->where('scope', 'mine|all')->name('caisses.journal');

            // Payments — Inertia list + modal add/edit (the cascading
            // multi-row payment form). Controller serves both the list and
            // the read-only receipt page. ⚠ NEVER add a destroy route.
            Route::get('encaissements', [EncaissementController::class, 'index'])
                ->middleware('permission:payments.view')->name('encaissements.index');
            Route::post('encaissements', [EncaissementController::class, 'store'])
                ->middleware('permission:payments.create')->name('encaissements.store');
            Route::put('encaissements/{encaissement}', [EncaissementController::class, 'update'])
                ->middleware('permission:payments.update')->name('encaissements.update');
            Route::get('encaissements/{encaissement}/recu', [EncaissementController::class, 'recu'])
                ->middleware('permission:payments.view')->name('encaissements.recu');
            Route::get('encaissements/{encaissement}', [EncaissementController::class, 'show'])
                ->name('encaissements.show');
            Route::get('students/{student}/inscriptions-for-payment', [EncaissementController::class, 'studentInscriptions'])
                ->name('students.inscriptions-for-payment');
            Route::get('inscriptions/{inscription}/unpaid-fees', [EncaissementController::class, 'inscriptionFees'])
                ->name('inscriptions.unpaid-fees');
            Route::get('inscriptions/{inscription}/payments', [EncaissementController::class, 'inscriptionPayments'])
                ->name('inscriptions.payments');

            // Avances — unallocated advances (no fee attached, see
            // Encaissement::isAvance()). A create route (fresh money, no
            // fee-line cascade), a convert route that detaches existing
            // payments of an inscription from their fees (the "changement de
            // groupe" money-move flow), and an apply route that spends
            // part/all of one onto a specific fee, without ever editing the
            // avance row itself.
            Route::post('avances', [EncaissementController::class, 'storeAvance'])
                ->middleware('permission:payments.create')->name('avances.store');
            Route::post('avances/convert', [EncaissementController::class, 'convertAvance'])
                ->middleware('permission:payments.create')->name('avances.convert');
            Route::post('avances/{encaissement}/apply', [EncaissementController::class, 'applyAvance'])
                ->middleware('permission:payments.create')->name('avances.apply');

            // Gestion des dépenses — ONE Inertia page hosting dépenses +
            // remboursements as client-side React tabs (Types de dépenses
            // moved OUT to its own Inertia page in Phase 6 — this page is 2
            // tabs only). Access = ANY of the two remaining view permissions
            // (checked in DepenseController@index). ⚠ NEVER add a destroy
            // route: an expense is never deleted.
            Route::get('depenses', [DepenseController::class, 'index'])->name('depenses.index');
            Route::post('depenses', [DepenseController::class, 'store'])
                ->middleware('permission:expenses.create')->name('depenses.store');
            Route::put('depenses/{depense}', [DepenseController::class, 'update'])
                ->middleware('permission:expenses.update')->name('depenses.update');
            Route::delete('depenses/{depense}/justificatifs/{media}', [DepenseController::class, 'removeJustificatif'])
                ->middleware('permission:expenses.update')->name('depenses.justificatifs.destroy');
            Route::get('depenses/{depense}', [DepenseController::class, 'show'])
                ->name('depenses.show');
            // Types de dépenses — Inertia/React page (Phase 6). Real
            // resource routes now (index/store/update/destroy); is_system
            // rows stay locked (TypeDepensePolicy + an explicit unconditional
            // guard in the controller, bypassing even super-admin — see
            // TypeDepenseController's docblock).
            Route::resource('types-depenses', TypeDepenseController::class)
                ->except(['show', 'create', 'edit']);

            Route::get('remboursements', fn () => redirect()->route('backoffice.depenses.index', ['tab' => 'remboursements']))
                ->middleware('permission:refunds.view')->name('remboursements.index');
            Route::post('remboursements', [RemboursementController::class, 'store'])
                ->middleware('permission:refunds.create')->name('remboursements.store');
            Route::put('remboursements/{remboursement}', [RemboursementController::class, 'update'])
                ->middleware('permission:refunds.update')->name('remboursements.update');
            Route::get('students/{student}/payments-for-refund', [RemboursementController::class, 'studentPayments'])
                ->name('students.payments-for-refund');
            // No remboursements.show — zero detail page anywhere in the live
            // app, preserved (docs/phase-10-finance-mapping.md Q2).

            // Till transfers — two-step request/validate flow, now lives in
            // the « Transferts » tab of Gestion de la caisse; the legacy URL
            // deep-links there (middleware kept so unauthorized users get
            // 403, not a redirect).
            Route::get('caisse-transfers', fn () => redirect()->route('backoffice.caisses.index', ['tab' => 'transferts']))
                ->middleware('permission:cash-transfers.view')->name('caisse-transfers.index');
            Route::post('caisse-transfers', [CaisseTransferController::class, 'store'])
                ->middleware('permission:cash-transfers.create')->name('caisse-transfers.store');
            Route::put('caisse-transfers/{caisse_transfer}', [CaisseTransferController::class, 'update'])
                ->middleware('permission:cash-transfers.update')->name('caisse-transfers.update');
            Route::put('caisse-transfers/{caisse_transfer}/validate', [CaisseTransferController::class, 'validateAction'])
                ->middleware('permission:cash-transfers.validate')->name('caisse-transfers.validate');
            Route::get('caisse-transfers/{caisse_transfer}', [CaisseTransferController::class, 'show'])
                ->name('caisse-transfers.show');

            // Authorization module — Roles (Inertia/React migration Phase 7,
            // docs/inertia-react-migration-plan.md). Full-page create/edit
            // (no modal), matching the pre-existing UX exactly. Per-action
            // permission middleware; RoleController re-authorizes in every
            // action AND in every mutation method (defense in depth). The
            // Livewire RolesIndex/RoleForm components are kept, unused, for
            // rollback.
            Route::get('roles', [RoleController::class, 'index'])
                ->middleware('permission:roles.view')->name('roles.index');
            Route::get('roles/create', [RoleController::class, 'create'])
                ->middleware('permission:roles.create')->name('roles.create');
            Route::post('roles', [RoleController::class, 'store'])
                ->middleware('permission:roles.create')->name('roles.store');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
                ->middleware('permission:roles.update')->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:roles.update')->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('permission:roles.delete')->name('roles.destroy');
            Route::get('permissions', PermissionController::class)
                ->middleware('permission:permissions.view')->name('permissions.index');

            // Users — Inertia/React migration Phase 7
            // (docs/inertia-react-migration-plan.md). Users are NEVER created
            // here (only EmployeeObserver produces them) — no store()/create()
            // route exists. Per-action permission middleware; UserController/
            // UserAuthorizationController re-authorize in every action
            // (defense in depth). The Livewire UsersIndex/ManageAuthorization
            // components are kept, unused, for rollback (Phase 10 removes them).
            Route::get('users', [UserController::class, 'index'])
                ->middleware('permission:users.view')->name('users.index');
            Route::put('users/{user}', [UserController::class, 'update'])
                ->middleware('permission:users.assign-roles')->name('users.update');
            // Throttled (Phase 12): admin-only, but regenerating a password
            // disconnects the target user — cap the churn rate.
            Route::post('users/{user}/regenerate-password', [UserController::class, 'regeneratePassword'])
                ->middleware(['permission:users.assign-roles', 'throttle:10,1'])
                ->name('users.regenerate-password');
            Route::get('users/{user}/authorization', [UserAuthorizationController::class, 'edit'])
                ->middleware('permission:users.assign-roles')->name('users.authorization.edit');
            Route::put('users/{user}/authorization', [UserAuthorizationController::class, 'update'])
                ->middleware('permission:users.assign-roles')->name('users.authorization.update');
        });
    });
