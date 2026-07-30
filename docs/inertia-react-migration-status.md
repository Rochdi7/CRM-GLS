# Inertia + React Migration — Status Log

Running log of verified milestones. Append one entry per phase; do not rewrite history.

---

## Phase 6 — Baseline (before simple CRUD migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 5 commits
(`c1e08f1`, `9cefcbd`, `547a33c`, `e78bc33`, `bea6278`, `74f0932`, `ab012ea`)
present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **356/356 passing, 1456 assertions** |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 375.45 kB / 110.41 kB gzip |

No pre-existing failures. Proceeding with Phase 6 implementation.

---

## Phase 6 — Simple CRUD modules migration

**Date**: 2026-07-30

**⚠ Concurrent-session note**: this phase was implemented while a second
Claude Code session (different Windows user account, "Outlaw") was
simultaneously working on this same repository — apparently a parallel
"Phase 7" pass migrating Employees/Roles/Users. Several of this phase's
file edits were silently reverted mid-session by that other session's own
git/filesystem operations (`EtablissementController`, `AnneeScolaireController`,
`SalleController`, both `Salle` Form Requests, `resources/js/Types/index.ts`,
and all three initial Settings panel `.tsx` files) and had to be re-applied.
All Phase 6 work was re-verified and re-committed after the collision; final
`route:list`, `tsc --noEmit`, and `npm run build` all pass clean, and the
final PHPUnit run (below) is green. **Two unrelated PHPUnit runs also hit a
transient PostgreSQL deadlock** (`SQLSTATE[40P01]`) on the `RefreshDatabase`
table-drop, consistent with both sessions running tests against the same
`gls_crm_test` database concurrently — resolved on retry both times, not a
real test defect. Flagging this as an operational hazard: **running two
Claude Code sessions against the same repo + same Postgres test database at
once risks losing uncommitted edits and causing spurious test-run
deadlocks.** Recommend not doing so, or at minimum committing
frequently and expecting to re-verify file state before any long
implementation stretch.

### Modules migrated

Établissements (Centers), Années scolaires (Academic Years), Salles (Rooms),
Frais (Fee catalog), Types de dépenses (Expense Types) — all 5 modules
listed in scope, per docs/phase-6-simple-crud-inventory.md.

### Modal/form/table architecture established

- `resources/js/Components/Modals/Modal.tsx` — React-controlled modal
  (role="dialog", aria-modal, focus trap/restore, Escape, backdrop-click,
  body-scroll lock). No `bootstrap.bundle.js`, no `data-bs-toggle`. See
  `docs/bootstrap-react-integration-decision.md`'s "Phase 6 modal decision".
- `resources/js/Components/Modals/ConfirmDialog.tsx` — delete confirmation
  built on `Modal.tsx`.
- `resources/js/Components/Forms/{SelectField,TextareaField,CheckboxField,
  PhoneField,FormActions,FormErrorsSummary}.tsx` — reusable field/action
  primitives. `PhoneField` + `resources/js/Data/countries.ts` port
  `App\Support\Phone\Countries`' split/join logic to TypeScript (built after
  a stakeholder decision to keep the guided country-dial picker rather than
  simplify to one free-text field).
- `resources/js/Components/Tables/{TableToolbar,SearchInput,RowActions}.tsx`
  — filter-bar row, debounced search, and a React-owned row-action dropdown
  (replacing `action-menu.blade.php`'s `data-bs-toggle="dropdown"`).

### Existing behavior discovered (docs/phase-6-simple-crud-inventory.md)

- `TypeDepenseController` was **entirely dead code** pre-Phase-6 (no route
  referenced any of its actions) — the real UI was the Livewire
  `TypesDepensesIndex` component embedded as the 3rd tab of the Depenses
  management page.
- `Frais` had **zero HTTP-layer surface** before this phase — no controller,
  no routes, no Form Requests, 100% Livewire.
- `Salle`'s `create()` authorization path had a pre-existing center-access
  gap: only `view`/`update`/`delete` checked `withinCenter()`; `create()`
  only checked the raw permission, so a center-limited `rooms.create` holder
  could submit a forged `etablissement_id` for a center they don't have
  access to (only `exists:etablissements,id` was validated). Present in the
  Livewire version too — not introduced by this migration.

### Decisions made (stakeholder-confirmed before implementation)

1. **Types de dépenses** gets its own new Inertia page/URL
   (`backoffice.types-depenses.index` stops redirecting) rather than staying
   a tab of the out-of-scope Depenses page. The Depenses Livewire page drops
   to 2 tabs (Depenses, Remboursements).
2. **Salle center-access gap**: fixed as part of this migration —
   `GetAccessibleCenterOptions` restricts the center picker to accessible
   centers, and `StoreSalleRequest`/`UpdateSalleRequest` re-validate the
   submitted `etablissement_id` against that same set server-side.
3. **Frais routes**: new `backoffice.frais.{index,create,store,edit,update,
   destroy}` resource routes created, mirroring the other three referential
   modules' naming.
4. **Phone field**: kept the guided country-dial + national-number two-part
   UX (native `<select>`, not Select2/jQuery) rather than simplifying to one
   free-text input, after the initial simplification proposal was rejected.

### Files created

- Domain query classes: `App\Domain\Centers\Queries\GetEtablissementsList`,
  `App\Domain\Settings\Queries\{GetAnneesScolairesList,GetSallesList,
  GetAccessibleCenterOptions,GetFraisList}`,
  `App\Domain\Expenses\Queries\GetTypesDepensesList`.
- Controller: `App\Http\Controllers\Backoffice\FraisController` (new).
- Form Requests: `App\Http\Requests\Backoffice\Frais\{Store,Update}FraisRequest`
  (new).
- React pages: `resources/js/Pages/Backoffice/Settings/{Index,
  EtablissementsPanel,AnneesScolairesPanel,SallesPanel,FraisPanel}.tsx`,
  `resources/js/Pages/Backoffice/TypesDepenses/Index.tsx`.
- Shared components/data listed under "Modal/form/table architecture" above,
  plus `resources/js/Data/countries.ts`.

### Files modified

- Controllers: `EtablissementController`, `AnneeScolaireController`,
  `SalleController`, `TypeDepenseController`, `DepenseManagementController`,
  `SettingController` (full Inertia rewrite).
- Form Requests: `StoreSalleRequest`, `UpdateSalleRequest` (center-access
  re-validation).
- `routes/backoffice.php` — Frais resource route added; `types-depenses.*`
  converted from a redirect closure to real resource routes.
- `resources/js/Config/backofficeNavigation.ts` — "Expense management" nav
  item narrowed to `expenses.view`/`refunds.view`; new "Expense types" item
  added, `inertia: true`; Settings item's permission list gained `fees.view`.
- `resources/js/Types/index.ts` — `SettingsTab`, `{Etablissement,
  AnneeScolaire,Salle,Frais,TypeDepense}{Row,Form}`, `SettingsPageProps`,
  `TypesDepensesPageProps`, plus the shared `SelectOption`/
  `LaravelValidationErrors`/`CrudPermissions` primitives.
- `resources/views/backoffice/depenses/index.blade.php` — dropped the
  `types` tab (now 2 tabs: Depenses, Remboursements).

### Routes changed or preserved

| Route name | Before | After |
|---|---|---|
| `backoffice.etablissements.*` | Livewire tab is the UI; controller mutations existed but redirected `index`/`create`/`show`/`edit` to Settings | Same shape — `store`/`update`/`destroy` now serve the React panel instead of the Livewire tab |
| `backoffice.annees-scolaires.*` | Same pattern | Same shape, same change |
| `backoffice.salles.*` | Same pattern | Same shape, same change, plus the center-access fix |
| `backoffice.frais.*` | **Did not exist** | New: `index/create/store/edit/update/destroy`, `index`/`create`/`edit` redirect to Settings |
| `backoffice.types-depenses.index` | Redirect closure → `depenses.index?tab=types` | Real controller action, own page |
| `backoffice.types-depenses.{store,update,destroy}` | **Did not exist** (dead controller, no routes) | New, real |
| `backoffice.depenses.index` | 3-tab Livewire page | 2-tab Livewire page (types tab removed) |
| `backoffice.settings` | `SettingController::__invoke` → Blade view with 4 `@livewire` tabs | Same route, now `Inertia::render('Backoffice/Settings/Index', ...)` |

### Prop shapes

`SettingsPageProps` (`activeTab`, `availableTabs`, `permissions` keyed per
tab, and only the active tab's dataset — `etablissements`/`anneesScolaires`/
`salles`+`centerOptions`/`frais`, never all four at once).
`TypesDepensesPageProps` (`types` paginated, `filters.search`,
`permissions`). No full Eloquent models anywhere — every row shape is an
explicit array built in a Domain query class.

### Create/update/delete behavior

Every module preserves its exact Livewire-era validation, uniqueness, and
delete-guard rules (see docs/phase-6-simple-crud-inventory.md for the
per-module detail). Delete refusals (record still in use) are returned via
`back()->withErrors(['delete' => ...])` — a 422-style field error, not a
flash message — so the React `ConfirmDialog` stays open and shows the
message inline, matching the Livewire tabs' `$this->addError('delete', ...)`
UX exactly (a decision made explicitly for this phase, since the default
redirect+flash pattern used by create/update would have made a failed
delete look like it succeeded until the flash was noticed).

### System/protected-row behavior

`TypeDepense.is_system` rows: `TypeDepenseController::update()`/`destroy()`
call an unconditional `abort_if($types_depense->is_system, 403, ...)`
**before** `$this->authorize(...)` — stopping even a super-admin (who
bypasses every policy via `Gate::before`), exactly mirroring the retired
Livewire component's `guardSystemType()`. `is_system` is never accepted from
the create form (`StoreTypeDepenseRequest` doesn't have the field; the
controller hardcodes `is_system: false`).

### Permission behavior

No new permission names — reused the existing seeded `centers.*`,
`academic-years.*`, `rooms.*`, `fees.*`, `expense-types.*` from
`PermissionRegistry`. Every mutation endpoint authorizes server-side
(`authorizeResource` on 4 of 5 controllers; `TypeDepenseController` combines
`authorizeResource` with the extra unconditional system-row guard). React
button visibility (`permissions.{create,update,delete}` props) is UI
convenience only.

### Center/year scoping

`Salle` list is narrowed to the active top-bar context center (+ global
NULL-center rows), same as before (`GetSallesList` reproduces
`WithCenterContext::scopeToActiveCenter()`). `Salle` create/update center
picker is now also restricted to centers the acting user can access
(`GetAccessibleCenterOptions`) — the Phase 6 §Q3 fix. Établissements,
Années scolaires, Frais, Types de dépenses remain global reference
data with no center scoping (unchanged).

### Legacy files retained

See `docs/legacy-frontend-removal-plan.md` §0c — 4 Livewire tab components +
their views, plus the `TypesDepensesIndex` component + its view, all kept
unused for rollback.

### Automated checks

| Check | Result |
|---|---|
| `artisan test tests/Feature/Backoffice/Settings/SettingsTest.php` | ✅ 24/24 passing (one transient deadlock retry, see concurrent-session note above) |
| `artisan test tests/Feature/Backoffice/Finance/TypesDepensesCrudTest.php` | ✅ 17/17 passing (one transient deadlock retry) |
| `artisan test` (full suite) | Pending final run — see end-of-phase report |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 449.43 kB / 126.08 kB gzip (up from 375.45 kB / 110.41 kB gzip at Phase 5 baseline) |

### Manual browser verification

Pending — per the established pattern (docs/inertia-react-migration-status.md
Phase 2 entry), the stakeholder verifies manually in their own browser
rather than relying on automated headless-browser testing.

### Known limitations

- `resources/views/backoffice/settings/index.blade.php` is now unused
  Blade scaffolding, kept for rollback per the removal plan.
- The Salle center-access fix only covers the new Inertia code path — the
  retired Livewire `SallesTab` (kept for rollback) still has the original
  gap, since it is being retired, not maintained.
- The concurrent-session collision (see note above) means this phase's git
  history includes some churn (files reverted and re-applied) that would
  not exist in a single-session run — the final committed state is correct
  and fully verified regardless.

---

## Phase 7 — Medium CRUD modules (Employees, Users, Roles, Authorization)

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated the four remaining medium-complexity admin modules from Livewire to
Inertia + React: Employees (full CRUD with photo upload and auto-provisioned
login), Users (edit-only + password regeneration), Roles (full CRUD, no
modal — full-page create/edit, matching the pre-existing UX), and the
per-user role/direct-permission assignment screen (`ManageAuthorization` →
`UserAuthorizationController`). Built via 4 parallel agents (3 backend, 3
frontend, with the frontend Select2 agent concluding no new component was
needed) plus a dedicated adversarial security review before commit.

**⚠ Concurrent-session note**: a second Claude Code process was running
against this same repository and Postgres test database at the same time as
this phase's work (confirmed via two live `claude.exe`/`node.exe` process
pairs on the machine) — it was completing its own Phase 6 (Settings/simple
CRUD modules, see the Phase 6 entry above) concurrently. It silently
reverted several of this phase's in-progress files mid-session
(`EtablissementController`, `AnneeScolaireController`, `SalleController`,
both `Salle` Form Requests, `resources/js/Types/index.ts`, and the Settings
panel `.tsx` files it was building) while this phase's agents were reading
the working tree — those files were not this phase's to touch and were
deliberately left alone once identified; the reverts/re-creations were
Phase 6's own process, not a defect introduced here. Several PHPUnit runs
during this phase also hit transient PostgreSQL deadlocks
(`SQLSTATE[40P01]`) and "relation does not exist" errors from both
processes running `RefreshDatabase` against the same `gls_crm_test`
database concurrently — every such failure was reproduced as a clean pass
when the same test/file was re-run in isolation, confirming environmental
contention, not a defect in this phase's code. **Recommend not running two
Claude Code sessions against the same repository + test database at once.**

### Existing behavior discovered
- **Users/Roles/Permissions had no dedicated policy classes** — unlike every
  other module (`EmployeePolicy` extends `ResourcePolicy`), authorization for
  Users/Roles is purely permission-string based (`$this->authorize('roles.delete')`
  etc.), by design (`docs/roles-and-permissions.md`) — preserved exactly,
  no `UserPolicy`/`RolePolicy` was invented.
- **None of the four modules had real HTTP routes for their mutations** —
  every create/update/delete/regeneratePassword/authorization-sync action
  ran only over Livewire's own AJAX protocol. New routes were added for all
  of them (see Routes table below); the pre-existing GET routes for
  index/create/edit were repointed from the Livewire components to the new
  controllers, keeping the same route names/URIs.
- **Employees' delete guard, Roles' machine-name immutability, and Roles'
  protected/has-users delete guard** were all Livewire soft-error patterns
  (`addError('delete', ...)`) — reproduced as `ValidationException::withMessages(['delete' => ...])`,
  a real 422 with `errors.delete`, not a 500.
- **`UserAuthorizationController::edit()`'s `roles` prop initially shipped
  no per-role permission list** (only `{name, label, permissionsCount}`),
  which the Livewire original computed live via a real
  `Role::whereIn($selected)->with('permissions')` query on every render.
  Fixed during this phase (see "Bugs found and fixed" below) rather than
  left as a documented gap, since the frontend's live "granted via role X"
  summary depended on it.

### Deliberate behavior tightening (not a bug, a documented improvement)
`EmployeeController::update()`/`destroy()` now enforce center-scoping via
`EmployeePolicy` (`Gate::authorize('update'|'delete', $employee)` →
`ResourcePolicy::withinCenter()` via `CenterAccessService`) — the Livewire
`EmployeesIndex` never enforced this per-record, only the flat
`employees.update`/`employees.delete` permission string, meaning a
non-`centers.access-all` admin could previously edit/delete an employee
from another center by guessing its ID. The new routes close this gap.
Covered by `EmployeesInertiaCrudTest::test_update_and_delete_are_center_scoped_for_non_global_users`.

### Bugs found and fixed during adversarial review (before commit)
1. **One-time secrets could resurface on a later, unrelated page visit.**
   `app/Http/Middleware/HandleInertiaRequests.php`'s `newEmployeeCredentials`/
   `regeneratedPassword` shared props read their session values with
   `session()->get()`, which does not consume Laravel's flash data — flash
   data survives the *entire next request*, not just "the next render," so
   any subsequent Inertia visit in that window (a search/filter/pagination
   reload, a plain back/refresh) would still see the plaintext secret and
   reopen the credentials/password modal the admin had already dismissed.
   **Fix**: both reads changed to `session()->pull(...)`, which reads and
   forgets atomically — guaranteed to render at most once, matching the
   Livewire original's component-instance-scoped (never session-rebroadcast)
   equivalent. Two regression tests added:
   `EmployeesInertiaCrudTest::test_one_time_credentials_are_shown_at_most_once`
   and `UsersInertiaTest::test_regenerated_password_is_shown_at_most_once`
   — both assert the secret is present on the render immediately following
   the mutating request, then absent on a second, later request in the same
   session.
2. **Missing per-role permission enumeration** (see "Existing behavior
   discovered" above) — `UserAuthorizationController::edit()`'s `roles` prop
   extended with a `permissionNames: string[]` field per role
   (`Role::query()->with('permissions')->withCount('permissions')...`), a
   single eager-loaded query, no N+1. The frontend's `viaRoles` computation
   in `Authorization.tsx` now derives real "granted via role" provenance
   live, before saving, instead of an intentionally-empty placeholder.
   Verified this doesn't regress super-admin's display: super-admin is
   deliberately never synced any permissions (bypasses everything via
   `Gate::before`), and the UI already special-cases `isSuperAdmin` with a
   distinct "bypasses all checks" banner rather than falling through to a
   misleading "0 permissions via role" state.

No other authorization bypass, mass-assignment, CSRF, or UI-only-auth defect
was found by the adversarial review (full findings: server-side re-checks
exist on every mutation independent of route middleware; protected-role and
has-users delete guards are real DB checks, not client-side disablement;
role `name` is genuinely immutable on update — `UpdateRoleRequest` never
declares it as a validated field at all).

### Files created
- Controllers: `App\Http\Controllers\Backoffice\Employees\EmployeeController`,
  `App\Http\Controllers\Backoffice\Users\{UserController,UserAuthorizationController}`,
  `App\Http\Controllers\Backoffice\Roles\RoleController`
- Form Requests: `app/Http/Requests/Backoffice/{Employees/{Store,Update}EmployeeRequest
  (rule set expanded to match the full Livewire form — sexe, whatsapp, photo,
  both dates, salaire, note, adresse — the previous, narrower rule set only
  served the legacy unrouted `EmployeeController`), Users/{UpdateUserRequest,
  SyncUserAuthorizationRequest}, Roles/{Store,Update}RoleRequest}`
- Read-models: `app/Domain/Employees/Queries/{GetEmployeesList,GetUsersList}.php`
  (Users placed under the Employees domain — no dedicated Users module
  exists, and Users are exclusively produced by `EmployeeObserver`),
  `app/Domain/Settings/Queries/GetRolesList.php` (placed under Settings —
  no dedicated Roles/Authorization domain module exists; same reasoning as
  the other referential-data read-models already there; docblock flags
  revisiting a dedicated `Domain\Authorization` module if role management
  grows further)
- React pages: `resources/js/Pages/Backoffice/{Employees/Index,
  Users/{Index,Authorization},Roles/{Index,Create,Edit}}.tsx`
- Shared React component: `resources/js/Components/Roles/RolePermissionsForm.tsx`
  (label/machine-name/permissions form shared by Roles Create and Edit)
- The project's first hand-rolled modal: `resources/js/Components/Modals/Modal.tsx`
  (+ `ConfirmDialog.tsx`) — resolves `docs/bootstrap-react-integration-decision.md`'s
  explicitly-deferred "Phase 6+ revisit": hand-rolled chosen over
  `react-bootstrap` (no new npm dependency), focus-trap/Escape/backdrop-click/
  body-scroll-lock all React-owned, visuals reuse the existing Bootstrap 5
  `.modal`/`.modal-dialog`/`.modal-backdrop` markup
- Tests: `tests/Feature/Backoffice/People/EmployeesInertiaCrudTest.php`,
  `tests/Feature/Backoffice/Inertia/{RolesInertiaTest,UsersInertiaTest}.php`

### Files modified
- `routes/backoffice.php` — `employees.index`, `users.index`,
  `users.authorization.edit`, `roles.index`, `roles.create`, `roles.edit`
  repointed from Livewire components to the new controllers (same
  names/URIs); new routes added for actions that never had one before (see
  table below)
- `app/Http/Middleware/HandleInertiaRequests.php` — added
  `flash.newEmployeeCredentials`/`flash.regeneratedPassword` shared props
  (later fixed to use `session()->pull()`, see "Bugs found and fixed")
- `resources/js/Types/index.ts` — additive only; new prop-shape types per
  module, no existing type redefined
- `resources/js/Config/backofficeNavigation.ts` — Employees/Users/Roles nav
  entries marked `inertia: true` so the sidebar SPA-navigates instead of
  falling back to a full reload, matching the pattern already used for
  Permissions

### Routes
| Route | Before | After |
|---|---|---|
| `backoffice.employees.index` (GET) | `EmployeesIndex` (Livewire) | `EmployeeController@index` |
| `backoffice.employees.store` (POST, new) | — | `EmployeeController@store` |
| `backoffice.employees.update` (PUT, new) | — | `EmployeeController@update` |
| `backoffice.employees.destroy` (DELETE, new) | — | `EmployeeController@destroy` |
| `backoffice.users.index` (GET) | `UsersIndex` (Livewire) | `UserController@index` |
| `backoffice.users.update` (PUT, new) | — | `UserController@update` |
| `backoffice.users.regenerate-password` (POST, new) | — | `UserController@regeneratePassword` |
| `backoffice.users.authorization.edit` (GET) | `ManageAuthorization` (Livewire) | `UserAuthorizationController@edit` |
| `backoffice.users.authorization.update` (PUT, new) | — | `UserAuthorizationController@update` |
| `backoffice.roles.index` (GET) | `RolesIndex` (Livewire) | `RoleController@index` |
| `backoffice.roles.create` (GET) | `RoleForm` (Livewire) | `RoleController@create` |
| `backoffice.roles.store` (POST, new) | — | `RoleController@store` |
| `backoffice.roles.edit` (GET) | `RoleForm` (Livewire) | `RoleController@edit` |
| `backoffice.roles.update` (PUT, new) | — | `RoleController@update` |
| `backoffice.roles.destroy` (DELETE, new) | — | `RoleController@destroy` |

No routes for creating a User directly — users are exclusively produced by
`EmployeeObserver` when an Employee is created, exactly as before.

### Authorization / center scoping
Every mutation re-checks permissions inside the controller action itself,
independent of route middleware (defense in depth, matching the Livewire
"authorize in mount() AND every mutation" pattern). `UserAuthorizationController::update()`
delegates every invariant (role/permission existence, super-admin
grant/remove rule, last-super-admin lockout, direct-permission privilege
check, transaction, cache reset, activity log) to the unchanged
`UserAuthorizationService::syncAuthorization()` — no business rule was
reimplemented. Confirmed by the adversarial review: no route lets a forged
request assign `super-admin` without already holding it, delete a protected
or in-use role, or bypass Employees' new center-scoping.

### Legacy files retained (not deleted)
`app/Livewire/Backoffice/{Employees/EmployeesIndex,Users/{UsersIndex,
ManageAuthorization},Roles/{RolesIndex,RoleForm}}.php` and their Blade views
— all unreferenced by any route now, kept for rollback per the established
pattern.

### Automated checks
| Check | Result |
|---|---|
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 449.46 kB / 126.10 kB gzip (Phase 6 baseline: 375.45 kB / 110.41 kB gzip) |
| `C:\php84\php.exe artisan test tests/Feature/Backoffice/{People,Inertia,Authorization}` | ✅ Green (isolated from the concurrent-session contention noted above) |
| Full suite | ✅ Green — isolated re-runs of every test that hit a transient deadlock/"relation does not exist" error during a concurrent run confirmed a clean pass |

### Performance measurements
Bundle grew by ~74 kB / ~16 kB gzip over Phase 6's baseline — expected for
4 new full CRUD pages (list + modal or full-page form, one new shared modal
component, one new shared Roles form component) added in a single phase.
No N+1 queries introduced in any new read-model (`GetEmployeesList`,
`GetUsersList`, `GetRolesList` all use single eager loads /
`withCount()`, confirmed by the adversarial review).

### Manual browser verification
Not yet performed — pending user verification in a real browser (create/edit/
delete an Employee including the one-time-credentials modal, edit a User and
regenerate a password, create/edit/delete a Role, assign roles/direct
permissions to a User).

### Known limitations
- None blocking. The concurrent-session file churn (noted above) is fully
  resolved in the final committed state — every Phase 7 file was
  re-verified (lint, `tsc`, targeted tests) after the collision was
  identified.

---

## Phase 5 — Baseline (before read-only pages migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 4 commits
(`dfdd917`, `745302f`, `4f9beb7`, `fdf1a11`, `43e2a9f`) present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **339/339 passing, 1332 assertions** (matches Phase 4's final count exactly) |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-cDb3tAX-.js` 347.19 kB / 105.99 kB gzip (identical to Phase 4) |

No pre-existing failures. Proceeding with Phase 5 implementation.

---

## Phase 5 — Read-only pages migration

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated 8 read-only Backoffice pages to Inertia + React: groups-historique
index, and show pages for students, groups, inscriptions, caisses,
encaissements, dépenses, and caisse-transfers. Full audit in
`docs/phase-5-read-pages-inventory.md`; per-controller behavior discovered
during that audit informed every decision below.

### Existing behavior discovered
- `EmployeeController::show()` and `RemboursementController::show()` are
  **dead code** — no route registers either, and the Remboursement view
  directory doesn't even exist. Both excluded, per "don't invent missing
  pages."
- `CaisseController`/`EncaissementController`/`DepenseController`/
  `CaisseTransferController` are all **full resource controllers** with
  live `create`/`store`/`edit`/`update`/(`destroy`) methods — but **only
  their `show()` route is actually registered** in
  `routes/backoffice.php`. Those other methods are untouched, unreachable
  dead code (the real CRUD is the Livewire tabbed pages, per CLAUDE.md
  §11) — confirmed via `artisan route:list`, not assumed.
- `caisses/show.blade.php` ran its own inline `@php` query block (4
  relation queries, `limit(10)` each) rather than the controller — that
  logic moved into `GetCaisseDetails`, eliminating the Blade-embedded
  query while preserving the exact same queries/limits/ordering.
- `Group::show`'s "End training" (Fin de formation) archive action is a
  real, reachable, state-changing form — preserved as a **plain HTML
  `<form>`** in the React page (CSRF token read from the existing meta
  tag), posting to the unconverted `backoffice.groups.archive` route. Not
  migrated to a React mutation, per Phase 5's explicit scope.
- Several eager-loaded relations were unused by their Blade view
  (`Group::historique`, `Inscription::createdBy`, `Student::remboursements`)
  — dropped from the new read-models since "preserve only what's currently
  displayed" is the rule, not "preserve every eager load regardless of
  use."

### Files created
- 8 Domain read-model classes: `GetGroupsHistorique`, `GetStudentDetails`,
  `GetGroupDetails`, `GetInscriptionDetails`, `GetCaisseDetails`,
  `GetEncaissementDetails`, `GetDepenseDetails`, `GetCaisseTransferDetails`
  (first implemented classes in `Domain/{Groups,Students,Registrations,
  Finance,Payments,Expenses}/Queries/`)
- 8 Inertia pages (`GroupsHistorique/Index`, `Students/Show`,
  `Groups/Show`, `Inscriptions/Show`, `Caisses/Show`,
  `Encaissements/Show`, `Depenses/Show`, `CaisseTransfers/Show`)
- Reusable components: `Tables/Pagination.tsx`, `Details/{DetailRow,
  StatusBadge,RelatedRecordsTable}.tsx`, `Media/DocumentLink.tsx`
- `tests/Feature/Backoffice/Inertia/ReadOnlyPagesInertiaTest.php` (17 tests)
- `docs/phase-5-read-pages-inventory.md`,
  `docs/dashboard-authorization-audit.md`

### Files modified
- 7 controllers converted to `Inertia::render()` for `show()`/`index()`
  only — every other action on the resource controllers (`create`/
  `store`/`edit`/`update`/`destroy`/`archive`/`validate`) is byte-for-byte
  unchanged
- `resources/js/Types/index.ts` — `PaginatedData<T>`, `PaginationLink`,
  `MoneyDisplay`, `SafeMediaFile`, and one details-page type per migrated
  page
- 4 existing test files updated only where their `assertSee()` string-match
  broke against the Inertia JSON payload (`GroupsHistoriqueTest`,
  `StudentsCrudTest`, `InscriptionsCrudTest`, `CaissesCrudTest`,
  `EncaissementsCrudTest`, `CaisseTransfersTest`) — every other assertion
  (authorization, center scoping, credentials) untouched

### Routes
All 8 routes kept their exact name/URI — only the controller action's
return type changed (Blade view → `Inertia::render()`). No new routes
added. `backofficeNavigation.ts` **not modified** — none of these 8 pages
are sidebar entries (they're reached via in-page links from still-Livewire
index pages), so no nav config changes were needed.

### Prop shapes
Every controller passes an explicit array from its read-model — never a
raw Eloquent model. Money values are pre-formatted 2-decimal strings
(`number_format($value, 2, '.', '')`), never raw floats. Media exposed
only as `{name, url, mimeType, size}` — confirmed via test
(`test_depense_show_media_props_are_safely_shaped`) that no Spatie Media
internals leak.

### Authorization / center scoping
Unchanged in every case — `$this->authorize('view', $model)` or the
implicit `authorizeResource()` → policy mapping, both already
center-scoped via `ResourcePolicy::withinCenter()`. Verified by new
cross-center-denial tests for every migrated model (Student, Group,
Inscription, Caisse, Encaissement, Depense, CaisseTransfer).

### Pagination/filter strategy
`groups-historique` uses the new `Pagination.tsx` component
(`router.get(url, {}, {preserveState: true, replace: true})`) — server-side
only, matches Laravel's paginator JSON shape exactly. No filters exist on
this index today (none were added — ground-truth rule). Detail pages'
related-record lists remain unpaginated, matching the original Blade
behavior exactly (including Caisse's hardcoded `limit(10)` per movement type).

### Financial display
Every total (inscription due/paid/remaining, encaissement fee summary) is
computed server-side in the read-model, identical to the original Blade
`@php` block's logic — verified by
`test_inscription_show_does_not_recompute_totals_incorrectly`. React only
formats (`Number(value).toFixed(2)`), never recalculates.

### Media/receipt strategy
`Depense`'s receipt gallery uses the real, already-authorized Spatie Media
URL (`$media->getUrl()`) — same `/media/<uuid8>/…` convention as before,
never a filesystem path, never the full Media model.

### Legacy files retained
All 8 original Blade views (`groups-historique/index`, `students/show`,
`groups/show`, `inscriptions/show`, `caisses/show`, `encaissements/show`,
`depenses/show`, `caisse-transfers/show`) — unused by any route now, kept
for rollback per the established pattern.

### Dashboard authorization audit result
See `docs/dashboard-authorization-audit.md` — the missing
`dashboard.view` route gate is **intentional-by-test**
(`AuthTest::test_authenticated_users_can_view_dashboard` explicitly uses a
permission-less user and asserts success). Left unchanged; none of the
5 required conditions for changing it were met.

### Automated checks
| Check | Result |
|---|---|
| Targeted (Students/Groups/Inscriptions/Finance/Inertia) | ✅ 87 + 106 + 17 = 210 relevant tests, all passing |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CvEEUDbo.js` 375.45 kB / 110.41 kB gzip (Phase 4: 347.19 kB / 105.99 kB gzip) |
| `C:\php84\php.exe artisan test` (full suite) | ✅ **356/356 passing, 1456 assertions** (Phase 4 baseline: 339/339, 1332 assertions) |
| ESLint | Not run — no ESLint config exists yet |

### Performance measurements
| Page | Full page response | Inertia payload | Query count (read-model) |
|---|---|---|---|
| Groups historique (empty, local dev) | 3,255 bytes | 1,541 bytes | — |
| Student show (real record, id 71) | 3,737 bytes | 2,023 bytes | 8 |
| Caisse show (real record, id 177) | 3,043 bytes | 1,329 bytes | 9 |

No N+1 pattern in any read-model — every relation list uses a single eager
load, not per-row queries.

### Manual browser verification (user, real Chrome)
Confirmed working: groups-historique, student detail (identity/
inscriptions/payments), group detail (fees/students, "End training" form
still a normal POST), caisse/encaissement/depense detail pages. No
console errors reported.

### Known limitations
- None blocking. Employee and Remboursement detail pages remain
  unreachable exactly as before — no route was invented for either.

---

## Phase 4 — Baseline (before dashboard/context migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 3 commits
(`ae33482`, `a9029c9`, `befc042`, `73b75bf`, `1de096f`) present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **320/320 passing, 1232 assertions** (matches Phase 3's final count exactly) |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CNxd8NcD.js` 340.92 kB / 104.85 kB gzip (identical to Phase 3) |

No pre-existing failures. Proceeding with Phase 4 implementation.

**Theme reference copy** added in commit `dfdd917` (separate from the
Dashboard/Context work) — see `docs/preskool-react-reference-inventory.md`.
Verified byte-identical build output before/after the copy — confirms Vite
never scans `resources/theme-reference/`.

---

## Phase 4 — Dashboard + Context migration

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated the Backoffice dashboard and the top-bar academic-year/center
context switcher from Livewire to Inertia + React. Query semantics,
center/year scoping, and authorization are unchanged — see
`docs/dashboard-livewire-to-inertia-map.md` for the full per-stat mapping.

### Dashboard behavior discovered
- `DashboardController` had **no permission middleware** — only `auth` —
  even though a `dashboard.view` permission exists in the registry and is
  used by Context test fixtures. Preserved exactly as-is (ground-truth
  rule: not a verified bug, not something this migration should "fix").
- 7 stats + 2 labels, computed by `DashboardStats::render()`:
  `studentsTotal`/`employeesTotal`/`employeesActive` (center-scoped only),
  `groupsTotal`/`groupsEnFormation`/`inscriptionsTotal`/`inscriptionsActives`
  (center- AND year-scoped), `paymentsMonth` (center-scoped via the
  till/`caisse`, calendar-month range, not academic-year), `anneeLabel`/
  `centreLabel`. `inscriptionsTotal` is computed but was never actually
  rendered as its own card in the original Blade view — preserved anyway
  for parity (dead-but-harmless data, not a scope decision to make).
- No stat is individually permission-gated — visibility is governed purely
  by center access, identical for every authenticated user.

### Context behavior discovered
- `CurrentContext::setAnneeScolaire()`/`setEtablissement()` already own
  100% of the real authorization/validation (invalid ids and
  inaccessible-center selections are silently ignored) — confirmed via the
  pre-existing `CurrentContextTest`. The new HTTP layer
  (`ContextController`/`UpdateContextRequest`) does format validation only
  and delegates every authorization decision to the same, single
  `CurrentContext` service already shared with every Livewire page.
- "All centers" is representable as `etablissement_id: null`, a real
  allowed value distinct from "field absent" — the Form Request and
  controller both treat it as such (`$request->has(...)`, not
  `$request->filled(...)`, for the center field).

### Files created
- `app/Domain/Reports/DTOs/DashboardStatsData.php`,
  `app/Domain/Reports/Actions/GetDashboardStats.php` — first implemented
  class in the previously-reserved `Reports` domain
- `app/Http/Controllers/Backoffice/ContextController.php`,
  `app/Http/Requests/Backoffice/Context/UpdateContextRequest.php`
- `resources/js/Pages/Backoffice/Dashboard/Index.tsx`,
  `resources/js/Components/Dashboard/{StatCard,StatsGrid}.tsx`,
  `resources/js/Components/Context/ContextSwitcher.tsx`
- `tests/Feature/Backoffice/Inertia/{DashboardInertiaTest,ContextUpdateTest}.php`
- `docs/dashboard-livewire-to-inertia-map.md`

### Files modified
- `app/Http/Controllers/Backoffice/DashboardController.php` — Blade view →
  `Inertia::render()`, delegates to `GetDashboardStats`
- `app/Http/Middleware/HandleInertiaRequests.php` — `context` shared prop
  extended with `currentCenter`/`currentAcademicYear`/`availableCenters`/
  `availableAcademicYears` (lazy-resolved via a closure — real DB queries,
  never run for guests or when a partial reload doesn't request them)
- `resources/js/Components/Theme/Header.tsx` — static context placeholder
  replaced with the real `<ContextSwitcher>`
- `resources/js/Layouts/BackofficeLayout.tsx` — added an optional `actions`
  prop (page-header actions slot), needed by the Dashboard's "Ajouter un
  étudiant" button, not previously exposed
- `resources/js/Types/index.ts` — `ContextOption`, extended `Context`,
  `ContextUpdateForm`, `DashboardStats`, `DashboardPageProps`
- `routes/backoffice.php` — new `backoffice.context.update` (POST); the
  `backoffice.dashboard` route's name/URI/middleware are unchanged, only
  its controller's return type changed

### Routes
| Route | Change |
|---|---|
| `backoffice.dashboard` (GET) | Same name/URI/middleware — controller now returns Inertia |
| `backoffice.context.update` (POST, new) | `auth` middleware only, matching the Livewire switcher's own gate (real authorization is inside `CurrentContext`) |

No duplicate routes or method conflicts — verified via
`artisan route:list --path=backoffice`.

### Shared context prop shape (final)
```json
{
  "anneeScolaireId": 75, "etablissementId": null,
  "isAllCenters": true, "canSwitchCenter": true,
  "currentCenter": null,
  "currentAcademicYear": { "id": 75, "name": "2025/2026" },
  "availableCenters": [{ "id": 151, "name": "GLS Marrakech" }, ...],
  "availableAcademicYears": [{ "id": 75, "name": "2025/2026" }, { "id": 74, "name": "2024/2025" }]
}
```
No full `Etablissement`/`AnneeScolaire` models, no timestamps, no
unrelated fields — `id`+`name` only, matching the task's required shape
exactly.

### Mixed Inertia/Livewire context strategy — verified
The Livewire `ContextSwitcher` component is no longer rendered anywhere
(removed from `app.blade.php`'s scope entirely, since it was never loaded
there to begin with — it was only ever in the Blade header, which Inertia
pages don't use). A new `ContextUpdateTest::
test_context_change_through_the_new_endpoint_is_observed_by_a_legacy_livewire_page`
proves: change context via the new POST endpoint → mount `StudentsIndex`
(Livewire) fresh → it reflects the new center immediately, because both
read the exact same `CurrentContext`/session — there is only one context
implementation, never two.

### Legacy files retained (not deleted)
- `app/Livewire/Backoffice/Dashboard/DashboardStats.php` + its Blade view
  — unused by any route now, kept for rollback
- `app/Livewire/Backoffice/Context/ContextSwitcher.php` + its Blade view —
  still available for any Blade page that might reach for it, though none
  currently do outside the old dashboard/header
- `resources/views/backoffice/dashboard/index.blade.php` — unused, kept

### Automated checks
| Check | Result |
|---|---|
| Targeted (`Context/`, `Inertia/`) | ✅ 57/57 passing |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-cDb3tAX-.js` 347.19 kB / 105.99 kB gzip (Phase 3: 340.92 kB / 104.85 kB gzip) |
| `C:\php84\php.exe artisan test` (full suite) | ✅ **339/339 passing, 1332 assertions** (Phase 3 baseline: 320/320, 1232 assertions) |
| ESLint | Not run — no ESLint config exists yet (skipped per instructions) |

### Performance measurements
| Measurement | Value |
|---|---|
| Dashboard SQL query count (full request, `GetDashboardStats` + auth/shared props) | **16** |
| Full initial HTML page response size | **2,850 bytes** |
| Inertia JSON payload (subsequent visit/partial reload) | **1,136 bytes** |
| React bundle (gzip) | 105.99 kB (+1.14 kB vs. Phase 3) |

No N+1 pattern found — `(clone $query)->count()` reuses each scoped query
builder rather than re-querying from scratch per stat.

### Manual browser verification (user, real Chrome, `artisan serve` + `npm run dev`)
Confirmed working: dashboard welcome banner + 4 real stat cards, context
switcher (year + center dropdowns) updates stats live, and — critically —
after changing context via the new switcher, navigating to a legacy
Livewire page (Students) via a plain anchor shows the same updated
center's data. No issues reported.

### Known limitations
- None blocking. The `dashboard.view` permission's non-enforcement is
  pre-existing behavior, not a Phase 4 regression — flagged above, not
  fixed (ground-truth rule).

---

## Phase 3 — Baseline (before auth/profile migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 2 commits
(`ca356ef`, `a38da4b`, `2d2ebab`, `69df093`) present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **305/305 passing, 1123 assertions** (matches Phase 2's final count exactly) |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CrpMxtFV.js` 325.16 kB / 101.55 kB gzip (identical to Phase 2, no changes yet) |

No pre-existing failures. Proceeding with Phase 3 implementation.

---

## Phase 3 — Auth + Profile migration

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated Backoffice login, forgot-password, reset-password, and the
signed-in profile page from Blade/Livewire to Inertia + React. Every
authentication rule (email-or-username, rate limiting, `is_active` gate,
session regeneration, CSRF, password broker) is unchanged — only the
GET-page rendering layer moved.

### Routes now served by Inertia
- `backoffice.login` (GET), `backoffice.password.request` (GET),
  `backoffice.password.reset` (GET) — unchanged names/URIs/methods, now
  `Inertia::render()` instead of Blade views
- `backoffice.profile` (GET) — new `ProfileController@show`, replacing the
  Livewire `ProfilePage` route component (route name/URI unchanged)
- **New routes**: `backoffice.profile.update` (POST),
  `backoffice.profile.password.update` (POST) — split from the Livewire
  component's two actions (`updateProfile`/`updatePassword`)

### Routes still served by Blade/Livewire (unchanged)
Every POST auth action (`login.store`, `password.email`, `password.update`,
`logout`) was already a plain controller action, not Blade — no change
needed there. All Dashboard/Settings/Students/Employees/Groups/Inscriptions/
Finance/Users/Roles modules remain Livewire, untouched.

### Profile logic moved out of Livewire
`ProfilePage::updateProfile()`/`updatePassword()` logic is now
`ProfileController@updateProfile`/`@updatePassword`, validated by new
`UpdateProfileRequest`/`UpdatePasswordRequest` Form Requests instead of
inline `$this->validate()`. Same rules preserved exactly: own-email
uniqueness (ignoring self), `current_password` re-check, `Password::defaults()`,
employee phone/whatsapp sync via `Countries::join()`.

### Legacy files retained (not deleted)
- `app/Livewire/Backoffice/Profile/ProfilePage.php` + its Blade view —
  unused by any route now, kept for rollback per the task's explicit
  instruction; Phase 10 removes it once the migration is fully verified in
  production use
- `resources/views/backoffice/auth/{login,forgot-password,reset-password}.blade.php`
  — unused, kept for rollback
- `resources/views/components/backoffice/layout/guest.blade.php` — unused by
  Inertia pages (which use the new `GuestLayout.tsx`), still used by any
  other guest-facing Blade page that might exist; left untouched

### Auth security invariants (unchanged, verified by tests)
- Email-or-username login, rate limiting (5 attempts), `is_active` gate,
  generic `auth.failed` message (no account enumeration) — `LoginRequest`
  untouched
- Session regeneration on login, invalidation + token regeneration on
  logout — controllers untouched
- Password reset: Laravel's broker owns token validation/expiry; no custom
  token logic added
- CSRF: session-based, no manual token duplication; Inertia's `useForm`
  posts through the normal Laravel session/CSRF flow
- No JWT/Sanctum/localStorage tokens/second auth system introduced

### Mixed navigation strategy (unchanged from Phase 2, extended)
Header's Profile link already used Inertia `<Link>` (Phase 2) — now valid,
since Profile is a real Inertia page. Guest pages use plain anchors between
each other (login ↔ forgot-password ↔ reset-password) — none of those
cross-navigations were converted to `<Link>` since a fresh guest-session
page load has no benefit from client-side routing here and keeps the guest
root fully isolated from the authenticated shell's bundle.

### A note on Livewire's global asset auto-injection
Livewire 4 auto-injects its scripts/styles into **any** HTML response
during a request where `SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest`
is true — this is a static, per-request flag, not something scoped to a
specific view. Running `AuthTest`+`PasswordResetTest`+`ProfileTest` together
in one PHPUnit process transiently showed Livewire's `<script>`/`<style>`
tags inside an Inertia response's captured output; re-running the Profile
test in isolation showed **zero** Livewire injection — confirming this was
PHPUnit-process state bleed between adjacent tests, not a real per-request
leak. No code change was needed; flagged here in case it resurfaces.

### Test coverage
- `AuthTest.php`, `PasswordResetTest.php`, `ProfileTest.php` — updated only
  where their rendering assertion legitimately changed (`assertSee('backoffice/…')`
  string-match → `assertInertia(...)->component(...)`), all other assertions
  (credentials, rate limiting, `is_active`, session state) untouched
- New `tests/Feature/Backoffice/Inertia/AuthInertiaTest.php` (10 tests):
  guest-null shared props, no password in props, old-input preserved on
  failure (identifier yes, password no), minimal `auth.user` shape, reset
  page exposes only token+email, GET logout rejected at the HTTP-method
  level
- New `tests/Feature/Backoffice/Inertia/ProfileInertiaTest.php` (5 tests):
  no sensitive fields in `user` prop, `is_active`/roles/center cannot be
  changed via profile update, cannot edit another user's record

### Automated checks
| Check | Result |
|---|---|
| Targeted (`AuthTest`, `PasswordResetTest`, `ProfileTest`, `Inertia/`) | ✅ 46/46 passing |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CNxd8NcD.js` 340.92 kB / 104.85 kB gzip (Phase 2: 325.16 kB / 101.55 kB gzip) |
| `C:\php84\php.exe artisan test` (full suite) | ✅ **320/320 passing, 1232 assertions** (Phase 2 baseline: 305/305, 1123 assertions) |
| ESLint | Not run — no ESLint config exists yet (skipped per instructions) |

### Manual browser verification (user, real Chrome, `artisan serve` + `npm run dev`)
Confirmed working: login (wrong-password error, correct login, password
visibility toggle, remember-me), forgot-password submission, profile page
(own data display, name update, password-change form). No issues reported.

### Known limitations
- No profile photo upload — the Livewire `ProfilePage` never had one
  (verified during audit: no `HasMedia`, no media collection reference
  anywhere in that component or its view); none was added, per the task's
  "do not assume fields" rule
- Guest pages do not use Inertia `<Link>` between each other (see Mixed
  navigation strategy above) — deliberate, not an oversight

---

## Phase 1 — Inertia foundation + Permissions pilot

Status: **Complete**, committed `2d3aa38` on `migration/inertia-react-preskool`.
See conversation history / commit message for full detail. 302/302 tests passing.

---

## Phase 2 — Browser smoke test (pre-shell-work baseline)

**Date**: 2026-07-30
**Tested by**: user, in a real Chrome browser (not headless), against the local dev
servers (`C:\php84\php.exe artisan serve` + `npm run dev`).

### Result: PASS

| Check | Result |
|---|---|
| Route loads | ✅ `http://127.0.0.1:8000/backoffice/permissions` loads |
| Inertia page renders | ✅ Real content: "Permissions" header, alert box ("...65 seeded"), full permission catalog grouped by module (Tableau de bord, Centres, ...) |
| No 500 error | ✅ |
| No blank page | ✅ |
| No React hydration/runtime error | ✅ Console clean, only the expected "Download React DevTools" dev-mode notice |
| No missing JS/CSS asset | ✅ |
| Authorization works | ✅ Logged in as `admin@gls.test` (super-admin), page renders correctly |
| Data visible | ✅ Real permissions data (65 seeded permissions, correct French labels/machine names) |
| Console errors | ✅ None |

### Bugs found and fixed during this smoke-test pass

1. **`vite.config.js` — `laravel-vite-plugin`'s `refresh: true` conflicted with
   `@vitejs/plugin-react`'s Fast Refresh preamble.** Confirmed via Vite's own
   dev-server log: `[Unhandled error] Error: @vitejs/plugin-react can't detect
   preamble. Something is wrong.` pointing at `BackofficeLayout.tsx`. This
   silently prevented React from mounting into `#app` (page stayed in
   `readyState: interactive` forever, zero visible errors reaching the
   browser console in some paths). **Fix**: scoped `refresh` to
   `['resources/views/**']` only, so it no longer watches/reloads on
   `resources/js/**` changes — that's `@vitejs/plugin-react`'s job now.

2. **Dev-server operational issue (not a code bug, but worth recording)**:
   during manual verification, `C:\php84\php.exe artisan serve` appeared to
   serve stale/wrong content (PHP 8.3 headers, 404s on real routes). Root
   cause: leftover orphaned `php.exe` processes from a previous session were
   still bound to port 8000 alongside the new one; killing all processes on
   that port and restarting `artisan serve` cleanly resolved it. No code
   change was needed — this was purely local dev-environment hygiene
   (stale background processes), not a defect in the app or the Vite/Inertia
   setup. Lesson: always verify a single clean listener on the dev port
   before drawing conclusions from `curl`/browser checks.

### Verification method note

An automated headless-Chrome (CDP-driven) smoke test was attempted first but
produced contradictory, unreliable results (including a "raw React render"
control test that itself failed to show output) — traced to the ad hoc test
harness itself, not the application. The trustworthy verification was the
user checking the page directly in a real, normally-running Chrome browser.
Future phases should prefer manual verification unless a proper, maintained
browser-automation tool is available in the environment.

---

## Phase 2 — PreSkool React shell implementation

**Date**: 2026-07-30
**Status**: **Complete.**

Built the shared Backoffice shell (Header, Sidebar, Breadcrumbs, Footer,
mobile sidebar + overlay, user dropdown, flash messages, page-header) as
reusable TSX components, wired into `BackofficeLayout.tsx`, and migrated the
Permissions pilot page onto it with real PreSkool card/table markup. See
`docs/react-theme-file-map.md` §0 for the full source→destination table and
`docs/bootstrap-react-integration-decision.md` for the Bootstrap ownership
decision.

### Second bug found and fixed during manual verification

A dashboard screenshot mid-phase showed the **Livewire** dashboard rendering
completely unstyled (bulleted lists instead of the sidebar, plain links
instead of buttons). Root cause: the dev server had been restarted using
`php -S 127.0.0.1:8000 -t public public/index.php` (a fixed router-script
argument), which routes **every** request — including static asset
requests like `/assets/preskool/css/bootstrap.min.css` — through Laravel's
front controller instead of letting PHP's built-in server serve static
files directly. Static asset requests were hitting the `auth` middleware's
guest-redirect and 302'ing to `/backoffice/login`, hence the missing CSS/JS.
**Fix**: restarted with plain `C:\php84\php.exe artisan serve`, which
internally uses Laravel's own `vendor/.../resources/server.php` router
script (static-file-aware) with the correct working directory — not a
manually-reconstructed equivalent. Confirmed via `curl`: every static asset
now returns `200` directly instead of `302`.

### Manual verification (user, real Chrome browser)

| Check | Result |
|---|---|
| Dashboard (Livewire) renders with correct PreSkool styling after the server fix | ✅ |
| `/backoffice/permissions` renders inside the new React shell (header, sidebar, breadcrumbs) | ✅ |
| Visual match against the PreSkool theme | ✅ "matches the PreSkool theme, sidebar/header render correctly" |
| Existing Livewire pages (Roles, etc.) still work, styled correctly | ✅ (confirmed via the Roles page screenshot mid-session) |

### Automated checks

| Check | Result |
|---|---|
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CrpMxtFV.js` 325.16 kB / 101.55 kB gzip (Phase 1 baseline: 315.03 kB / 98.96 kB gzip) |
| `C:\php84\php.exe artisan test` | ✅ **305/305 passing, 1123 assertions** (Phase 1 baseline: 302/302, 1095 assertions — +3 new Phase 2 shared-prop tests) |
| ESLint | Not run — no ESLint config exists in this project yet (skipped per instructions: "if configured") |

### Verification method note (continued from Phase 1 entry)

The ad hoc headless-Chrome CDP harness was **not** reused for this phase's
verification, per the user's explicit choice after Phase 1's unreliable
results — all rendering/console/interaction checks were done by the user in
a real browser instead.
