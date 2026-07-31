# Phase 11B — Active Route and Entry-Point Verification

Authoritative map of every registered application route, generated directly
from `php artisan route:list --json` (not hand-transcribed) and
cross-checked against the Phase 11A audit. **97 backoffice routes + 2
frontoffice routes = 99 total.** Zero duplicate route names (checked
programmatically). Zero routes target a class under `App\Livewire\`.

---

## Backoffice routes (prefix `/backoffice`, name prefix `backoffice.`)

### Auth (guest-only, `RedirectIfAuthenticated`)

| Method | URI | Name | Controller/Action |
|---|---|---|---|
| GET/HEAD | `backoffice/login` | `backoffice.login` | `Auth\LoginController@show` |
| POST | `backoffice/login` | `backoffice.login.store` | `Auth\LoginController@store` |
| GET/HEAD | `backoffice/forgot-password` | `backoffice.password.request` | `Auth\ForgotPasswordController@show` |
| POST | `backoffice/forgot-password` | `backoffice.password.email` | `Auth\ForgotPasswordController@store` |
| GET/HEAD | `backoffice/reset-password/{token}` | `backoffice.password.reset` | `Auth\ResetPasswordController@show` |
| POST | `backoffice/reset-password` | `backoffice.password.update` | `Auth\ResetPasswordController@store` |

All render Inertia pages (`Backoffice/Auth/{Login,ForgotPassword,ResetPassword}`) — confirmed no Blade view is returned by any of these controllers. **No Livewire/Blade fallback.**

### Auth (authenticated) + Dashboard + Context + Profile

| Method | URI | Name | Controller/Action | Inertia page |
|---|---|---|---|---|
| POST | `backoffice/logout` | `backoffice.logout` | `Auth\LogoutController` | — (redirect only) |
| GET/HEAD | `backoffice/dashboard` | `backoffice.dashboard` | `DashboardController` | `Backoffice/Dashboard/Index` |
| POST | `backoffice/context` | `backoffice.context.update` | `ContextController@update` | — (redirect only) |
| GET/HEAD | `backoffice/profile` | `backoffice.profile` | `ProfileController@show` | `Backoffice/Profile/Index` |
| POST | `backoffice/profile` | `backoffice.profile.update` | `ProfileController@updateProfile` | — |
| POST | `backoffice/profile/password` | `backoffice.profile.password.update` | `ProfileController@updatePassword` | — |

### Settings (Établissements / Années scolaires / Salles / Frais / tabbed shell)

| Method | URI | Name | Controller/Action |
|---|---|---|---|
| GET/HEAD | `backoffice/settings` | `backoffice.settings` | `SettingController` (invokable) → `Backoffice/Settings/Index` |
| resource | `backoffice/etablissements` | `backoffice.etablissements.*` | `EtablissementController` (full resource, policy-authorized per action) |
| resource except show | `backoffice/annees-scolaires` | `backoffice.annees-scolaires.*` | `AnneeScolaireController` |
| resource except show | `backoffice/salles` | `backoffice.salles.*` | `SalleController` |
| resource except show | `backoffice/frais` | `backoffice.frais.*` | `FraisController` |

These four resource controllers keep real `create`/`edit` routes even though the Settings tabbed Inertia page is the primary UI — confirmed live (not dead) because `SettingController`'s Inertia page still needs these named routes for its own store/update calls. **Do not remove.**

### People — Employees / Students

| Method | URI | Name | Controller/Action | Permission |
|---|---|---|---|---|
| GET/HEAD | `backoffice/employees` | `backoffice.employees.index` | `Employees\EmployeeController@index` | `employees.view` |
| POST | `backoffice/employees` | `backoffice.employees.store` | `Employees\EmployeeController@store` | `employees.create` |
| PUT | `backoffice/employees/{employee}` | `backoffice.employees.update` | `Employees\EmployeeController@update` | `employees.update` |
| DELETE | `backoffice/employees/{employee}` | `backoffice.employees.destroy` | `Employees\EmployeeController@destroy` | `employees.delete` |
| GET/HEAD | `backoffice/students` | `backoffice.students.index` | `StudentController@index` | `students.view` |
| POST | `backoffice/students` | `backoffice.students.store` | `StudentController@store` | `students.create` |
| PUT | `backoffice/students/{student}` | `backoffice.students.update` | `StudentController@update` | `students.update` |
| DELETE | `backoffice/students/{student}` | `backoffice.students.destroy` | `StudentController@destroy` | `students.delete` |
| GET/HEAD | `backoffice/students/{student}` | `backoffice.students.show` | `StudentController@show` | — |
| GET/HEAD | `backoffice/students/{student}/inscriptions-for-payment` | `backoffice.students.inscriptions-for-payment` | `EncaissementController@studentInscriptions` | — |

**Confirmed**: `EmployeeController::class` in `routes/backoffice.php` resolves via `use App\Http\Controllers\Backoffice\Employees\EmployeeController;` — the namespaced, live class. The old un-namespaced `app/Http/Controllers/Backoffice/EmployeeController.php` is not imported and not referenced anywhere in this file (Phase 11A Section C.5).

### Academic — Groups / Inscriptions

| Method | URI | Name | Controller/Action | Permission |
|---|---|---|---|---|
| GET/HEAD | `backoffice/groups` | `backoffice.groups.index` | `GroupController@index` | `groups.view` |
| POST | `backoffice/groups` | `backoffice.groups.store` | `GroupController@store` | `groups.create` |
| PUT | `backoffice/groups/{group}` | `backoffice.groups.update` | `GroupController@update` | `groups.update` |
| GET/HEAD | `backoffice/groups/{group}` | `backoffice.groups.show` | `GroupController@show` | — |
| POST | `backoffice/groups/{group}/archive` | `backoffice.groups.archive` | `GroupController@archive` | — |
| GET/HEAD | `backoffice/groups-historique` | `backoffice.groups-historique.index` | `GroupHistoriqueController@index` | — |
| GET/HEAD | `backoffice/groups/{group}/inscription-fees` | `backoffice.groups.inscription-fees` | `InscriptionController@groupFees` | — |
| GET/HEAD | `backoffice/inscriptions` | `backoffice.inscriptions.index` | `InscriptionController@index` | `registrations.view` |
| POST | `backoffice/inscriptions` | `backoffice.inscriptions.store` | `InscriptionController@store` | `registrations.create` |
| PUT | `backoffice/inscriptions/{inscription}` | `backoffice.inscriptions.update` | `InscriptionController@update` | `registrations.update` |
| DELETE | `backoffice/inscriptions/{inscription}` | `backoffice.inscriptions.destroy` | `InscriptionController@destroy` | `registrations.delete` |
| GET/HEAD | `backoffice/inscriptions/{inscription}` | `backoffice.inscriptions.show` | `InscriptionController@show` | — |
| GET/HEAD | `backoffice/inscriptions/{inscription}/unpaid-fees` | `backoffice.inscriptions.unpaid-fees` | `EncaissementController@inscriptionFees` | — |

No `backoffice.groups.destroy` (groups are never deleted — schema invariant, unrelated to Livewire cleanup).

### Finance — Caisses / Encaissements / Dépenses / Remboursements / Transferts

| Method | URI | Name | Controller/Action | Permission |
|---|---|---|---|---|
| GET/HEAD | `backoffice/caisses` | `backoffice.caisses.index` | `CaisseController@index` | — (canAny check inline) |
| GET/HEAD | `backoffice/caisses/journal/{scope}` | `backoffice.caisses.journal` | `CaisseController@journal` | — |
| GET/HEAD | `backoffice/caisses/{caisse}` | `backoffice.caisses.show` | `CaisseController@show` | — |
| GET/HEAD | `backoffice/encaissements` | `backoffice.encaissements.index` | `EncaissementController@index` | `payments.view` |
| POST | `backoffice/encaissements` | `backoffice.encaissements.store` | `EncaissementController@store` | `payments.create` |
| PUT | `backoffice/encaissements/{encaissement}` | `backoffice.encaissements.update` | `EncaissementController@update` | `payments.update` |
| GET/HEAD | `backoffice/encaissements/{encaissement}` | `backoffice.encaissements.show` | `EncaissementController@show` | — |
| GET/HEAD | `backoffice/depenses` | `backoffice.depenses.index` | `DepenseController@index` | — (canAny check inline) |
| POST | `backoffice/depenses` | `backoffice.depenses.store` | `DepenseController@store` | `expenses.create` |
| PUT | `backoffice/depenses/{depense}` | `backoffice.depenses.update` | `DepenseController@update` | `expenses.update` |
| DELETE | `backoffice/depenses/{depense}/justificatifs/{media}` | `backoffice.depenses.justificatifs.destroy` | `DepenseController@removeJustificatif` | `expenses.update` |
| GET/HEAD | `backoffice/depenses/{depense}` | `backoffice.depenses.show` | `DepenseController@show` | — |
| resource except show/create/edit | `backoffice/types-depenses` | `backoffice.types-depenses.*` | `TypeDepenseController` | policy-authorized |
| GET/HEAD | `backoffice/remboursements` | `backoffice.remboursements.index` | **Closure** → redirects to `backoffice.depenses.index?tab=remboursements` | `refunds.view` |
| POST | `backoffice/remboursements` | `backoffice.remboursements.store` | `RemboursementController@store` | `refunds.create` |
| PUT | `backoffice/remboursements/{remboursement}` | `backoffice.remboursements.update` | `RemboursementController@update` | `refunds.update` |
| GET/HEAD | `backoffice/caisse-transfers` | `backoffice.caisse-transfers.index` | **Closure** → redirects to `backoffice.caisses.index?tab=transferts` | `cash-transfers.view` |
| POST | `backoffice/caisse-transfers` | `backoffice.caisse-transfers.store` | `CaisseTransferController@store` | `cash-transfers.create` |
| PUT | `backoffice/caisse-transfers/{caisse_transfer}` | `backoffice.caisse-transfers.update` | `CaisseTransferController@update` | `cash-transfers.update` |
| PUT | `backoffice/caisse-transfers/{caisse_transfer}/validate` | `backoffice.caisse-transfers.validate` | `CaisseTransferController@validateAction` | `cash-transfers.validate` |
| GET/HEAD | `backoffice/caisse-transfers/{caisse_transfer}` | `backoffice.caisse-transfers.show` | `CaisseTransferController@show` | — |

**The two Closure targets are intentional, documented, and safe** (deep-link redirect stubs into the tabbed Inertia pages — see `docs/phase-10-finance-audit.md` §1). Not Livewire fallbacks.

### Authorization — Roles / Permissions / Users

| Method | URI | Name | Controller/Action | Permission |
|---|---|---|---|---|
| GET/HEAD | `backoffice/roles` | `backoffice.roles.index` | `Roles\RoleController@index` | `roles.view` |
| GET/HEAD | `backoffice/roles/create` | `backoffice.roles.create` | `Roles\RoleController@create` | `roles.create` |
| POST | `backoffice/roles` | `backoffice.roles.store` | `Roles\RoleController@store` | `roles.create` |
| GET/HEAD | `backoffice/roles/{role}/edit` | `backoffice.roles.edit` | `Roles\RoleController@edit` | `roles.update` |
| PUT | `backoffice/roles/{role}` | `backoffice.roles.update` | `Roles\RoleController@update` | `roles.update` |
| DELETE | `backoffice/roles/{role}` | `backoffice.roles.destroy` | `Roles\RoleController@destroy` | `roles.delete` |
| GET/HEAD | `backoffice/permissions` | `backoffice.permissions.index` | `PermissionController` (invokable) | `permissions.view` |
| GET/HEAD | `backoffice/users` | `backoffice.users.index` | `Users\UserController@index` | `users.view` |
| PUT | `backoffice/users/{user}` | `backoffice.users.update` | `Users\UserController@update` | `users.assign-roles` |
| POST | `backoffice/users/{user}/regenerate-password` | `backoffice.users.regenerate-password` | `Users\UserController@regeneratePassword` | `users.assign-roles` |
| GET/HEAD | `backoffice/users/{user}/authorization` | `backoffice.users.authorization.edit` | `Users\UserAuthorizationController@edit` | `users.assign-roles` |
| PUT | `backoffice/users/{user}/authorization` | `backoffice.users.authorization.update` | `Users\UserAuthorizationController@update` | `users.assign-roles` |

No `backoffice.users.store` (users are only created via Employee creation — schema invariant, unrelated to Livewire cleanup) and no `backoffice.users.destroy` (users are never deleted).

---

## Frontoffice routes (no `/backoffice` prefix, name prefix `frontoffice.`)

| Method | URI | Name | Controller/Action |
|---|---|---|---|
| ANY | `/` | `frontoffice.root` | `Route::redirect('/', '/backoffice/login')` |
| GET/HEAD | `/home` | `frontoffice.home` | `Frontoffice\HomeController` (invokable) → Blade view `frontoffice.home.index` |

`frontoffice.home` is the **only** live Blade-rendered page in the entire application (Frontoffice was never migrated to Livewire or Inertia — intentionally out of scope, per Phase 11A Section C.2). Not a Livewire cleanup target.

---

## Verification checklist (per Phase 11B instructions)

| Check | Result |
|---|---|
| Duplicate route names | **None** — programmatically checked (`sort \| uniq -d` on all 97 backoffice route names returns empty). |
| Legacy Livewire routes still registered | **None** — zero `App\Livewire\*` targets in any route file (`routes/backoffice.php`, `routes/web.php`, `routes/frontoffice.php`, `routes/console.php`). |
| Fallback routes pointing to Blade | **None** for backoffice — every backoffice route targets a real controller/Inertia response or one of the 2 documented redirect closures. `frontoffice.home` legitimately renders Blade (out of scope, not a "fallback"). |
| Dead controller methods | `CaisseManagementController`, `DepenseManagementController`, and the old un-namespaced `EmployeeController` have zero route registrations (Phase 11A Sections C.1, C.5) — confirmed by their absence from this route list entirely. |
| Dead route model bindings | None found — every `{param}` binding in the table above resolves to a model used by its controller's live action. |
| Old redirect targets | The 2 documented Closures (`remboursements.index`, `caisse-transfers.index`) are the only redirects; both point to real, live Inertia routes. No redirect points to a dead route or a Livewire page. |
| Sidebar links pointing to removed routes | **Verified — none.** Every `href` in `resources/js/Config/backofficeNavigation.ts` (13 items across 5 groups) resolves to a live route in this table: `/backoffice/{dashboard,students,employees,inscriptions,groups,caisses,encaissements,depenses,types-depenses,settings,users,roles,permissions}`. **However, that file's own header comment is stale** — it says "Every other item is a real, working link to its existing Livewire/Blade route... keep both in sync until the Blade sidebar is retired (Phase 10)," but every item except Dashboard/Settings now carries `inertia: true` and no Blade sidebar counterpart is live anymore (Phase 11A Section C.3). This comment must be corrected in Phase 11L (documentation cleanup), not a route-safety issue. |
| Breadcrumbs pointing to old routes | Not independently verified — breadcrumbs are generated inline per-page in the `.tsx` files, not from a central old-route config; no evidence of stale breadcrumb targets found during the Phase 11A route-file read. |
| Tests still calling old endpoints | Confirmed in Phase 11A Section G: `tests/Feature/Backoffice/Inertia/ContextUpdateTest.php` calls `Livewire::test(StudentsIndex::class)` directly (not a route call, but a direct component instantiation) — flagged for edit, not a route-level dead-endpoint call. No test file was found making an HTTP request to a route name that doesn't exist in this table. |

**Conclusion: no stop condition is triggered.** Every migrated module's active routes point exclusively to Inertia controller actions. Cleanup may proceed to Phase 11C.
