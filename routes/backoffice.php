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
use App\Http\Controllers\Backoffice\ChequeController;
use App\Http\Controllers\Backoffice\ContextController;
use App\Http\Controllers\Backoffice\CreneauController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\AuditLogController;
use App\Http\Controllers\Backoffice\DepenseController;
use App\Http\Controllers\Backoffice\Employees\EmployeeController;
use App\Http\Controllers\Backoffice\EncaissementController;
use App\Http\Controllers\Backoffice\EncaissementReallocationController;
use App\Http\Controllers\Backoffice\EtablissementController;
use App\Http\Controllers\Backoffice\FraisController;
use App\Http\Controllers\Backoffice\GroupController;
use App\Http\Controllers\Backoffice\GroupHistoriqueController;
use App\Http\Controllers\Backoffice\Import\CombinedImportController;
use App\Http\Controllers\Backoffice\Import\EncaissementImportController;
use App\Http\Controllers\Backoffice\Import\ImportBatchController;
use App\Http\Controllers\Backoffice\Import\InscriptionImportController;
use App\Http\Controllers\Backoffice\Import\PresenceImportController;
use App\Http\Controllers\Backoffice\Import\StudentImportController;
use App\Http\Controllers\Backoffice\InscriptionController;
use App\Http\Controllers\Backoffice\MotifAnnulationController;
use App\Http\Controllers\Backoffice\PermissionController;
use App\Http\Controllers\Backoffice\ProfileController;
use App\Http\Controllers\Backoffice\RapportController;
use App\Http\Controllers\Backoffice\RecouvrementController;
use App\Http\Controllers\Backoffice\RemboursementController;
use App\Http\Controllers\Backoffice\Roles\RoleController;
use App\Http\Controllers\Backoffice\SeanceController;
use App\Http\Controllers\Backoffice\SettingController;
use App\Http\Controllers\Backoffice\SystemSettingController;
use App\Http\Controllers\Backoffice\SalleController;
use App\Http\Controllers\Backoffice\StockController;
use App\Http\Controllers\Backoffice\StockTypeController;
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
| a thin controller. Money records (depenses, remboursements, transferts)
| have NO destroy route. Encaissements are the single exception: a destroy
| route exists behind `payments.delete`, a permission held by no role preset
| and granted by hand by a super-admin. It reverses caisses.solde in the same
| transaction (Domain\Payments\Actions\SupprimerEncaissement). Normal
| corrections still use a compensating entry, not a delete.
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
            // Own avatar — stored in the linked Employee's `photo` media
            // collection, the same one the Employees module writes.
            Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
            Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.destroy');

            // Referential data — managed through the tabbed Settings page
            // (Livewire CRUD tabs: établissements, années scolaires, salles).
            // Access = ANY of centers.view / academic-years.view / rooms.view;
            // each tab + its mutations are gated by that resource's permissions.
            Route::get('settings', SettingController::class)->name('settings');
            // Paramètres → Système: application-wide switches (expense
            // approval…). Authorized inside the controller.
            Route::put('settings/system', [SystemSettingController::class, 'update'])
                ->middleware('permission:system-settings.update')->name('settings.system.update');

            // Legacy resource routes for the referential data are kept as
            // permission-protected endpoints (still policy-backed); the Settings
            // page is the primary UI for them.
            Route::resource('etablissements', EtablissementController::class);
            // One-click « Définir par défaut » from the Settings tab (policy
            // `update` on the year; only one year is par_defaut at a time).
            Route::patch('annees-scolaires/{annees_scolaire}/default', [AnneeScolaireController::class, 'setDefault'])
                ->name('annees-scolaires.set-default');
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
            // Raisons d'annulation ou archivage — same super-admin-only
            // pattern as Banques (cancellation-reasons.* permissions are
            // absent from every role). "Changement de groupe" is a locked
            // system row (written by the group-change flow).
            Route::resource('motifs-annulation', MotifAnnulationController::class)
                ->parameters(['motifs-annulation' => 'motifAnnulation'])
                ->except(['show']);

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

            // Academic — groups are never deleted by ordinary roles
            // (schema §6) ; groups.destroy est l'exception super-admin.
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
            // Teacher changeover — archives the outgoing assignment, opens
            // the new one and stops the group's emploi du temps.
            Route::post('groups/{group}/changer-enseignant', [GroupController::class, 'changerEnseignant'])
                ->middleware('permission:groups.change-teacher')->name('groups.changer-enseignant');
            // Corrects the dates/motif of an already-recorded assignment
            // period (the changeover stamps "today"; the real handover may
            // have been another day). Never swaps the row's teacher, never
            // deletes the row — see the controller method.
            Route::put('groups/{group}/affectations/{affectation}', [GroupController::class, 'updateEnseignantAffectation'])
                ->middleware('permission:groups.change-teacher')->name('groups.affectations.update');
            // Removes ONE catalog fee from the group and cascades it to every
            // inscription of the group: the fee lines are HIDDEN (never
            // deleted) and the money already collected on them is released
            // back into re-applicable avances (RetirerFraisGroupe). Restore
            // is the exact reverse — it re-attaches the fee and un-hides the
            // lines, but never re-applies the freed avances (that stays an
            // explicit AppliquerAvance decision).
            Route::delete('groups/{group}/frais/{frai}', [GroupController::class, 'removeFee'])
                ->middleware('permission:groups.update')->name('groups.frais.remove');
            Route::post('groups/{group}/frais/{frai}/restore', [GroupController::class, 'restoreFee'])
                ->middleware('permission:groups.update')->name('groups.frais.restore');
            Route::post('groups/{group}/archive', [GroupController::class, 'archive'])->name('groups.archive');
            // ⚠ Super-admin only (groups.reopen est dans superAdminOnly()) :
            // rouvre un groupe « Fin de formation » ou « Annulée ». Ne touche
            // QUE le statut — paiements, inscriptions, séances et snapshot
            // groups_historique sont laissés intacts (01/09/2026).
            Route::post('groups/{group}/rouvrir', [GroupController::class, 'rouvrir'])
                ->middleware('permission:groups.reopen')->name('groups.rouvrir');
            // Super-admin only (groups.move-year is in superAdminOnly()):
            // re-homes the group + inscriptions + séances + payments to
            // another année scolaire, same counts before and after.
            Route::post('groups/{group}/move-year', [GroupController::class, 'moveYear'])
                ->middleware('permission:groups.move-year')->name('groups.move-year');
            // ⚠ Super-admin only (groups.delete est dans superAdminOnly()) :
            // l'exception à « un groupe ne se supprime jamais » (§11), pour
            // les groupes créés par erreur. Détruit le groupe ET ses
            // inscriptions ; l'argent survit en avances (SupprimerGroupe).
            Route::get('groups/{group}/deletion-impact', [GroupController::class, 'deletionImpact'])
                ->middleware('permission:groups.delete')->name('groups.deletion-impact');
            Route::delete('groups/{group}', [GroupController::class, 'destroy'])
                ->middleware('permission:groups.delete')->name('groups.destroy');
            // Quick lifecycle actions from the list's row menu — "Annuler"
            // (-> Annulée, terminal, same groups.archive gate as Fin de
            // formation), "Réactiver" (Annulée -> En inscription), "Activer"
            // (En inscription -> En formation, training starts), and
            // "Retourner en inscription" (En formation -> En inscription,
            // reverses Activer).
            Route::post('groups/{group}/annuler', [GroupController::class, 'annuler'])->name('groups.annuler');
            Route::post('groups/{group}/reactiver', [GroupController::class, 'reactiver'])->name('groups.reactiver');
            Route::post('groups/{group}/activer', [GroupController::class, 'activer'])->name('groups.activer');
            Route::post('groups/{group}/retourner-en-inscription', [GroupController::class, 'retournerEnInscription'])
                ->name('groups.retourner-en-inscription');
            Route::get('groups/{group}/students-by-segment', [GroupController::class, 'studentsBySegment'])
                ->name('groups.students-by-segment');
            // "Détails paiement" matrix (students × fees) behind the list's
            // kebab menu — money data, so it needs payments.view on top of
            // the GroupPolicy@view check the controller runs.
            Route::get('groups/{group}/payment-matrix', [GroupController::class, 'paymentMatrix'])
                ->middleware('permission:payments.view')->name('groups.payment-matrix');
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
            // Static segment — must stay before seances/{seance} so it isn't
            // swallowed by the wildcard. "Saisir l'absence" tab entry point
            // from the Index list: no séance is pre-selected, so this always
            // renders the fiche de présence page (never redirects/blocks) —
            // today's first séance if one exists, else an empty roll call
            // with the Date/Employé/Séances pickers so the user can pick
            // another day right there.
            Route::get('seances/saisir-absence', [SeanceController::class, 'presences'])
                ->middleware('permission:attendance.view')->name('seances.presences');
            // « Absence par groupe » tab — the presence matrix of ONE group
            // over a date window (students in rows, séances in columns) plus
            // its .xlsx export. Read-only: same attendance.view permission,
            // no dedicated export permission. Static segments, so they stay
            // above seances/{seance} like the one right before.
            Route::get('seances/absence-par-groupe', [SeanceController::class, 'absenceParGroupe'])
                ->middleware('permission:attendance.view')->name('seances.absence-par-groupe');
            Route::get('seances/absence-par-groupe/export', [SeanceController::class, 'absenceParGroupeExport'])
                ->middleware('permission:attendance.view')->name('seances.absence-par-groupe.export');
            Route::get('seances/{seance}', [SeanceController::class, 'show'])
                ->name('seances.show');
            Route::put('seances/{seance}/presences', [SeanceController::class, 'savePresences'])
                ->middleware('permission:attendance.mark')->name('seances.presences.update');
            Route::post('seances/{seance}/valider', [SeanceController::class, 'valider'])
                ->middleware('permission:attendance.mark')->name('seances.valider');
            Route::post('seances/{seance}/annuler', [SeanceController::class, 'annuler'])
                ->middleware('permission:attendance.mark')->name('seances.annuler');

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
            // Types de stock — replaces the old hardcoded CATEGORIES array
            // (product decision: new product types beyond "Livre" are added
            // here without a code change). is_system rows stay locked
            // (StockTypePolicy + explicit unconditional controller guard,
            // same pattern as Types de dépenses). Merged into the "Gestion
            // du stock" page as a third tab (tab=types) — no standalone
            // index page/route anymore, only the store/update/destroy
            // mutation endpoints the tab's forms submit to.
            Route::resource('stock-types', StockTypeController::class)
                ->except(['show', 'create', 'edit', 'index']);

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
            // Cancelling needs a reason + end date + a fee-cleanup decision,
            // so it is its own endpoint rather than a value of update-statut.
            Route::post('inscriptions/{inscription}/annuler', [InscriptionController::class, 'cancel'])
                ->middleware('permission:registrations.update')->name('inscriptions.cancel');
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
            // In-place variant: edits group_id on the SAME inscription (no
            // archival, no successor row) — fees and payments follow. Same
            // gate as change-group: both move a student between groups.
            Route::post('inscriptions/{inscription}/modify-group', [InscriptionController::class, 'modifyGroup'])
                ->middleware('permission:registrations.change-group')->name('inscriptions.modify-group');
            // Books (Chèques-module-style "Livre" stock) assigned to a
            // registration — see AssignerLivresInscription; the same
            // registrations.manage-fees gate as fee-line editing, since both
            // are "adjust what this registration owes/received after the
            // fact" actions.
            Route::get('inscriptions/{inscription}/livres', [InscriptionController::class, 'livres'])
                ->name('inscriptions.livres');
            Route::put('inscriptions/{inscription}/livres', [InscriptionController::class, 'updateLivres'])
                ->middleware('permission:registrations.manage-fees')->name('inscriptions.livres.update');
            // "Frais disponibles" for a group — the create form's live
            // group-fee lookup (docs/phase-9-inscriptions-mapping.md's
            // confirmed decision: a dedicated endpoint, not embedding every
            // group's fees in the initial options payload).
            Route::get('groups/{group}/inscription-fees', [InscriptionController::class, 'groupFees'])
                ->name('groups.inscription-fees');
            // "Livre" stock available at a group's own center — feeds the
            // create form's book multi-select, same gate/shape as
            // inscription-fees above.
            Route::get('groups/{group}/inscription-livres', [InscriptionController::class, 'groupLivres'])
                ->name('groups.inscription-livres');
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
            // Ma caisse + validation de transfert + comptes de caisse as
            // tabs (?tab=…), same as the former Livewire tabs. Access = ANY
            // of the three view permissions (checked in
            // CaisseController@index); each tab's data/actions are still
            // gated by their own permission server-side.
            Route::get('caisses', [CaisseController::class, 'index'])->name('caisses.index');
            Route::get('caisses/{caisse}', [CaisseController::class, 'show'])->name('caisses.show');
            Route::get('caisses/journal/{scope}', [CaisseController::class, 'journal'])
                ->where('scope', 'mine|all')->name('caisses.journal');

            // « Comptes de caisse » mutations — super-admin only in practice:
            // `cash-accounts.create/update/delete` sit in
            // PermissionRegistry::superAdminOnly(), so no role preset holds
            // them and only the Gate::before bypass passes this middleware.
            // (Only `cash-accounts.view`, the READ, was released to the five
            // management roles on 31/08/2026.) Deliberately NOT a Route::resource:
            // there is no create/edit page (modal) and no index of its own
            // (the tab lives on caisses.index).
            //
            // ⚠ This is the ONLY hand-write path for a caisse, and only for
            // the standing Banque/Externe/Chéque/Dépenses accounts — an
            // employee's own till stays owned by CaisseProvisioner. `solde`
            // is writable at creation only (opening balance) and `type` never
            // after it; see the two Form Requests.
            Route::post('caisses', [CaisseController::class, 'store'])
                ->middleware('permission:cash-accounts.create')->name('caisses.store');
            Route::put('caisses/{caisse}', [CaisseController::class, 'update'])
                ->middleware('permission:cash-accounts.update')->name('caisses.update');
            Route::delete('caisses/{caisse}', [CaisseController::class, 'destroy'])
                ->middleware('permission:cash-accounts.delete')->name('caisses.destroy');

            // Payments — Inertia list + modal add/edit (the cascading
            // multi-row payment form). Controller serves both the list and
            // the read-only receipt page. ⚠ NEVER add a destroy route.
            // « Déplacer des encaissements » — bulk correction of money booked
            // against the wrong groupe/année. Super-admin only
            // (payments.reallocate is in PermissionRegistry::superAdminOnly),
            // like groups.move-year: it rewrites which registration — and so
            // which année — a payment belongs to. The listing is deliberately
            // NOT year-scoped, since the rows to fix are the ones the active
            // year hides.
            Route::get('encaissements/reaffecter', [EncaissementReallocationController::class, 'index'])
                ->middleware('permission:payments.reallocate')->name('encaissements.reaffecter.index');
            Route::post('encaissements/reaffecter', [EncaissementReallocationController::class, 'store'])
                ->middleware('permission:payments.reallocate')->name('encaissements.reaffecter.store');

            Route::get('encaissements', [EncaissementController::class, 'index'])
                ->middleware('permission:payments.view')->name('encaissements.index');
            Route::post('encaissements', [EncaissementController::class, 'store'])
                ->middleware('permission:payments.create')->name('encaissements.store');
            Route::put('encaissements/{encaissement}', [EncaissementController::class, 'update'])
                ->middleware('permission:payments.update')->name('encaissements.update');
            // Reçu GROUPÉ — plusieurs encaissements de la MÊME inscription sur
            // un seul reçu (?ids=1,2,3&format=a6|a5|a5x2). Le contrôleur refuse
            // un lot dont les lignes ne partagent pas la même inscription : un
            // reçu porte l'identité d'un seul étudiant.
            Route::get('encaissements/recu-groupe', [EncaissementController::class, 'recuGroupe'])
                ->middleware('permission:payments.view')->name('encaissements.recu-groupe');
            Route::get('encaissements/recu-groupe/whatsapp', [EncaissementController::class, 'recuGroupeWhatsApp'])
                ->middleware('permission:payments.view')->name('encaissements.recu-groupe.whatsapp');
            Route::get('encaissements/{encaissement}/recu', [EncaissementController::class, 'recu'])
                ->middleware('permission:payments.view')->name('encaissements.recu');
            Route::post('encaissements/{encaissement}/recu/email', [EncaissementController::class, 'sendRecuEmail'])
                ->middleware('permission:payments.view')->name('encaissements.recu.email');
            // Lien WhatsApp du reçu : renvoie l'URL click-to-chat construite
            // côté serveur (le PDF y voyage comme URL signée — l'API
            // click-to-chat ne sait pas joindre de fichier).
            Route::get('encaissements/{encaissement}/recu/whatsapp', [EncaissementController::class, 'recuWhatsApp'])
                ->middleware('permission:payments.view')->name('encaissements.recu.whatsapp');
            Route::delete('encaissements/{encaissement}', [EncaissementController::class, 'destroy'])
                ->middleware('permission:payments.delete')->name('encaissements.destroy');
            Route::get('encaissements/{encaissement}', [EncaissementController::class, 'show'])
                ->middleware('permission:payments.view')->name('encaissements.show');
            Route::get('students/{student}/inscriptions-for-payment', [EncaissementController::class, 'studentInscriptions'])
                ->name('students.inscriptions-for-payment');
            // Convert-to-avance cascade: same lookup but statut-unfiltered —
            // a closed dossier (annulée/archivée/changement) is exactly what
            // gets converted, so it must appear here while it never appears
            // in the payable list above.
            Route::get('students/{student}/inscriptions-for-conversion', [EncaissementController::class, 'studentInscriptionsForConversion'])
                ->name('students.inscriptions-for-conversion');
            Route::get('inscriptions/{inscription}/unpaid-fees', [EncaissementController::class, 'inscriptionFees'])
                ->name('inscriptions.unpaid-fees');
            Route::get('inscriptions/{inscription}/payments', [EncaissementController::class, 'inscriptionPayments'])
                ->name('inscriptions.payments');

            // Gestion des recouvrements — read-only overdue-fees report
            // (GetRetardsList). Two client-side tabs ("Retards selon la
            // durée" / "Retards selon les critères") share the same query.
            Route::get('recouvrement', [RecouvrementController::class, 'index'])
                ->middleware('permission:collections.view')->name('recouvrement.index');

            // Gestion des rapports — édition de documents (aperçu, PDF, Excel).
            // ⚠ LECTURE SEULE : ces trois routes sont les seules du module et
            // il ne doit jamais s'en ajouter une qui écrit. Un rapport imprime
            // ce que l'utilisateur peut déjà consulter — même portée (centres
            // affectés + contexte actif), appliquée dans la requête Domain.
            Route::get('rapports', [RapportController::class, 'index'])
                ->middleware('permission:reports.view')->name('rapports.index');
            Route::get('rapports/pdf', [RapportController::class, 'pdf'])
                ->middleware('permission:reports.view')->name('rapports.pdf');
            Route::get('rapports/excel', [RapportController::class, 'excel'])
                ->middleware('permission:reports.view')->name('rapports.excel');

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


            // Chèques — off-ledger inventory of physical checks in hand
            // (garantie / à déposer), tracked reception -> dépôt ->
            // encaissé/rejeté. A Cheque row never moves money by itself;
            // paying with one goes through the normal Encaissement flow
            // (cheque_id link). ⚠ NEVER add a destroy route.
            Route::get('cheques', [ChequeController::class, 'index'])
                ->middleware('permission:cheques.view')->name('cheques.index');
            Route::post('cheques', [ChequeController::class, 'store'])
                ->middleware('permission:cheques.create')->name('cheques.store');
            Route::put('cheques/{cheque}', [ChequeController::class, 'update'])
                ->middleware('permission:cheques.update')->name('cheques.update');
            Route::patch('cheques/{cheque}/statut', [ChequeController::class, 'updateStatut'])
                ->middleware('permission:cheques.update')->name('cheques.update-statut');
            // Records that a rejected chèque was physically handed back to
            // its owner — off-ledger bookkeeping only, same permission as
            // every other chèque lifecycle move.
            Route::patch('cheques/{cheque}/retour', [ChequeController::class, 'markRetourne'])
                ->middleware('permission:cheques.update')->name('cheques.retour');
            // Feeds the "Payer avec un chèque" dropdown in the payment form.
            Route::get('students/{student}/cheques', [ChequeController::class, 'studentCheques'])
                ->name('students.cheques');

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
            // Approval flow (Paramètres → Système « Validation des dépenses »).
            // Approving is what debits the till — a pending dépense has moved
            // no money at all. Refusing keeps the row (audit trail), never
            // deletes it. Both are gated by expenses.approve + DepensePolicy.
            Route::put('depenses/{depense}/approve', [DepenseController::class, 'approve'])
                ->middleware('permission:expenses.approve')->name('depenses.approve');
            Route::put('depenses/{depense}/refuse', [DepenseController::class, 'refuse'])
                ->middleware('permission:expenses.approve')->name('depenses.refuse');
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

            // Legacy-CRM Excel import — Centre + Année scolaire are
            // mandatory, immutable batch scope (import plan). Non-CRUD
            // multi-step flow (upload -> analyze -> preview -> commit ->
            // result), one controller per entity.
            Route::get('import', [ImportBatchController::class, 'index'])
                ->middleware('permission:import.view')->name('import.index');
            Route::get('import/students', [StudentImportController::class, 'create'])
                ->middleware('permission:import.view')->name('import.students.create');
            Route::post('import/students/analyze', [StudentImportController::class, 'analyze'])
                ->middleware('permission:import.create')->name('import.students.analyze');
            Route::get('import/students/{batch}/preview', [StudentImportController::class, 'preview'])
                ->middleware('permission:import.view')->name('import.students.preview');
            Route::post('import/students/{batch}/commit', [StudentImportController::class, 'commit'])
                ->middleware('permission:import.create')->name('import.students.commit');
            Route::post('import/students/{batch}/retry-failed', [StudentImportController::class, 'retryFailed'])
                ->middleware('permission:import.create')->name('import.students.retry-failed');
            Route::get('import/students/{batch}/result', [StudentImportController::class, 'result'])
                ->middleware('permission:import.view')->name('import.students.result');

            // Combined Étudiants + Inscriptions (one wizard, both files) —
            // group-mapping peek reuses import.inscriptions.peek-groupes,
            // and the flow hands over to the inscriptions preview/commit.
            Route::get('import/combine', [CombinedImportController::class, 'create'])
                ->middleware('permission:import.view')->name('import.combine.create');
            Route::post('import/combine/analyze', [CombinedImportController::class, 'analyze'])
                ->middleware('permission:import.create')->name('import.combine.analyze');

            Route::get('import/inscriptions', [InscriptionImportController::class, 'create'])
                ->middleware('permission:import.view')->name('import.inscriptions.create');
            Route::post('import/inscriptions/peek-groupes', [InscriptionImportController::class, 'peekGroupes'])
                ->middleware('permission:import.create')->name('import.inscriptions.peek-groupes');
            Route::post('import/inscriptions/analyze', [InscriptionImportController::class, 'analyze'])
                ->middleware('permission:import.create')->name('import.inscriptions.analyze');
            Route::get('import/inscriptions/{batch}/preview', [InscriptionImportController::class, 'preview'])
                ->middleware('permission:import.view')->name('import.inscriptions.preview');
            Route::post('import/inscriptions/{batch}/commit', [InscriptionImportController::class, 'commit'])
                ->middleware('permission:import.create')->name('import.inscriptions.commit');
            Route::post('import/inscriptions/{batch}/retry-failed', [InscriptionImportController::class, 'retryFailed'])
                ->middleware('permission:import.create')->name('import.inscriptions.retry-failed');
            Route::get('import/inscriptions/{batch}/result', [InscriptionImportController::class, 'result'])
                ->middleware('permission:import.view')->name('import.inscriptions.result');

            Route::get('import/encaissements', [EncaissementImportController::class, 'create'])
                ->middleware('permission:import.view')->name('import.encaissements.create');
            Route::post('import/encaissements/peek-operateurs', [EncaissementImportController::class, 'peekOperateurs'])
                ->middleware('permission:import.create')->name('import.encaissements.peek-operateurs');
            Route::post('import/encaissements/analyze', [EncaissementImportController::class, 'analyze'])
                ->middleware('permission:import.create')->name('import.encaissements.analyze');
            Route::get('import/encaissements/{batch}/preview', [EncaissementImportController::class, 'preview'])
                ->middleware('permission:import.view')->name('import.encaissements.preview');
            Route::post('import/encaissements/{batch}/commit', [EncaissementImportController::class, 'commit'])
                ->middleware('permission:import.create')->name('import.encaissements.commit');
            Route::post('import/encaissements/{batch}/retry-failed', [EncaissementImportController::class, 'retryFailed'])
                ->middleware('permission:import.create')->name('import.encaissements.retry-failed');
            Route::get('import/encaissements/{batch}/result', [EncaissementImportController::class, 'result'])
                ->middleware('permission:import.view')->name('import.encaissements.result');

            Route::get('import/presences', [PresenceImportController::class, 'create'])
                ->middleware('permission:import.view')->name('import.presences.create');
            Route::post('import/presences/peek-groupes', [PresenceImportController::class, 'peekGroupes'])
                ->middleware('permission:import.create')->name('import.presences.peek-groupes');
            Route::post('import/presences/analyze', [PresenceImportController::class, 'analyze'])
                ->middleware('permission:import.create')->name('import.presences.analyze');
            Route::get('import/presences/{batch}/preview', [PresenceImportController::class, 'preview'])
                ->middleware('permission:import.view')->name('import.presences.preview');
            Route::post('import/presences/{batch}/commit', [PresenceImportController::class, 'commit'])
                ->middleware('permission:import.create')->name('import.presences.commit');
            Route::post('import/presences/{batch}/retry-failed', [PresenceImportController::class, 'retryFailed'])
                ->middleware('permission:import.create')->name('import.presences.retry-failed');
            Route::get('import/presences/{batch}/result', [PresenceImportController::class, 'result'])
                ->middleware('permission:import.view')->name('import.presences.result');

            // Journal d'audit — read-only forensic trail (CLAUDE.md §11).
            // ⚠ NEVER add a store/update/destroy route here: audit entries
            // are evidence and the application must offer no way to alter
            // them (App\Models\Activity refuses writes at the model level
            // too, so the guarantee survives a super-admin).
            Route::get('audit-logs', [AuditLogController::class, 'index'])
                ->middleware('permission:audit-logs.view')->name('audit-logs.index');
            Route::get('audit-logs/{activity}', [AuditLogController::class, 'show'])
                ->middleware('permission:audit-logs.view')->name('audit-logs.show');
        });
    });
