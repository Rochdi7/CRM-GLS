# Phase 11A — Livewire/Legacy Blade Cleanup: Pre-Deletion Audit

Read-only audit. **Nothing is deleted in this document.** Produced by a
repository-wide search (Explore agent) and independently spot-verified for
every consequential/ambiguous finding before being written up here. Cross-
checked against `docs/legacy-frontend-removal-plan.md`, which already
functions as a near-complete deletion manifest built up phase-by-phase
through Phases 3-10.

**Bottom line up front**: every `App\Livewire\*` class and every Livewire-
only Blade view is confirmed unreferenced by any live route. Three
additional dead files not previously called out in the removal plan were
found (an old un-namespaced `EmployeeController`, and the transitive-dead
status of the entire `resources/js/backoffice/` bundle + `components/
backoffice/layout/*` shell). Two real, confirmed test-coverage gaps exist
(Salles center-scoping, Users admin-account-visibility-under-center-scoping)
that must be closed with new Inertia-side tests before their Livewire-era
sources can be deleted — this is flagged as a stop-condition per the task's
own rules (§Phase 11H: "if not covered, port the behavior... before
deleting").

---

## Section A — `app/Livewire/**` (30 files: 22 real components + 4 shared traits + 4 `.gitkeep` placeholders)

| # | File | Module | Route-referenced? |
|---|---|---|---|
| 1 | `Backoffice/CaisseTransfers/CaisseTransfersIndex.php` | Caisse Transfers | No — `caisse-transfers.index` redirects to `caisses.index`; mutations use `CaisseTransferController`. |
| 2 | `Backoffice/Caisses/CaisseJournal.php` | Caisses (journal) | No — `caisses.journal/{scope}` serves `CaisseController@journal`. |
| 3 | `Backoffice/Caisses/CaissesIndex.php` | Caisses | No — `caisses.index` serves `CaisseController@index`. |
| 4 | `Backoffice/Concerns/WithCaisseSelection.php` | Shared trait | Not a route target; still `use`d by files 1-3 above (not independently deletable first). |
| 5 | `Backoffice/Concerns/WithCenterContext.php` | Shared trait | Not a route target; `use`d by Students/Employees/Groups/Inscriptions/Users/Settings-tab components. |
| 6 | `Backoffice/Concerns/WithPerPage.php` | Shared trait | Same pattern. |
| 7 | `Backoffice/Concerns/WithPhoneCountry.php` | Shared trait | Same pattern — used by Students/Employees Livewire forms. |
| 8 | `Backoffice/Context/ContextSwitcher.php` | Context switcher | No route. Still `@livewire()`-embedded in `components/backoffice/layout/header.blade.php` — but that partial is itself unreachable (see Section C.3). Two-level-dead, not live. |
| 9 | `Backoffice/Dashboard/.gitkeep` | — | Placeholder. |
| 10 | `Backoffice/Dashboard/DashboardStats.php` | Dashboard | No — `dashboard` serves `DashboardController` → Inertia. |
| 11 | `Backoffice/Depenses/DepensesIndex.php` | Dépenses | No — `depenses.index` serves `DepenseController@index`. |
| 12 | `Backoffice/Employees/EmployeesIndex.php` | Employees | No — `employees.index` serves `Employees\EmployeeController@index`. |
| 13 | `Backoffice/Encaissements/EncaissementsIndex.php` | Encaissements | No — `encaissements.index` serves `EncaissementController@index`. |
| 14 | `Backoffice/Groups/GroupsIndex.php` | Groups | No — `groups.index` serves `GroupController@index`. |
| 15 | `Backoffice/Inscriptions/InscriptionsIndex.php` | Inscriptions | No — `inscriptions.index` serves `InscriptionController@index`. |
| 16 | `Backoffice/Profile/ProfilePage.php` | Profile | No — `profile` serves `ProfileController@show`. |
| 17 | `Backoffice/Remboursements/RemboursementsIndex.php` | Remboursements | No — served by `RemboursementController`/redirect. |
| 18 | `Backoffice/Roles/RoleForm.php` | Roles | No — `roles.create`/`.edit` serve `RoleController`. |
| 19 | `Backoffice/Roles/RolesIndex.php` | Roles | No — `roles.index` serves `RoleController@index`. |
| 20 | `Backoffice/Settings/AnneesScolairesTab.php` | Settings | No — `settings` serves `SettingController` → Inertia. |
| 21 | `Backoffice/Settings/EtablissementsTab.php` | Settings | No, same reason. |
| 22 | `Backoffice/Settings/FraisTab.php` | Settings | No, same reason. |
| 23 | `Backoffice/Settings/SallesTab.php` | Settings | No, same reason. **⚠ See Section G — no Inertia-side test currently covers this module's center-scoping.** |
| 24 | `Backoffice/Shared/.gitkeep` | — | Placeholder. |
| 25 | `Backoffice/Students/StudentsIndex.php` | Students | No route. **⚠ Still directly invoked by `tests/Feature/Backoffice/Inertia/ContextUpdateTest.php::test_context_change_through_the_new_endpoint_is_observed_by_a_legacy_livewire_page`** — that one test method must be removed/rewritten as PART OF this file's deletion commit (the test file itself stays; it is otherwise a legitimate Inertia test). |
| 26 | `Backoffice/TypesDepenses/TypesDepensesIndex.php` | Types de dépenses | No — `types-depenses.*` use `TypeDepenseController` resource routes. |
| 27 | `Backoffice/Users/ManageAuthorization.php` | Users (authorization) | No — `users.authorization.*` serve `UserAuthorizationController`. |
| 28 | `Backoffice/Users/UsersIndex.php` | Users | No — `users.index` serves `Users\UserController@index`. |
| 29 | `Frontoffice/Home/.gitkeep` | — | Placeholder — no Frontoffice Livewire ever existed. |
| 30 | `Frontoffice/Shared/.gitkeep` | — | Placeholder. |

**Confirmed via full read of `routes/backoffice.php`, `routes/web.php`, `routes/frontoffice.php`, `routes/console.php`: zero routes target any `App\Livewire\*` class.**

## Section B — `resources/views/livewire/**` (23 files)

Clean 1:1 pairing with Section A components (2:1 for Caisses, which has 2 components). No orphaned views, no components missing a view. Full list:

```
backoffice/.gitkeep
backoffice/caisse-transfers/caisse-transfers-index.blade.php   → CaisseTransfersIndex
backoffice/caisses/caisse-journal.blade.php                    → CaisseJournal
backoffice/caisses/caisses-index.blade.php                     → CaissesIndex
backoffice/context/context-switcher.blade.php                  → ContextSwitcher
backoffice/dashboard/dashboard-stats.blade.php                 → DashboardStats
backoffice/depenses/depenses-index.blade.php                   → DepensesIndex
backoffice/employees/employees-index.blade.php                 → EmployeesIndex
backoffice/encaissements/encaissements-index.blade.php         → EncaissementsIndex
backoffice/groups/groups-index.blade.php                       → GroupsIndex
backoffice/inscriptions/inscriptions-index.blade.php           → InscriptionsIndex
backoffice/profile/profile-page.blade.php                      → ProfilePage
backoffice/remboursements/remboursements-index.blade.php       → RemboursementsIndex
backoffice/roles/role-form.blade.php                           → RoleForm
backoffice/roles/roles-index.blade.php                         → RolesIndex
backoffice/settings/annees-scolaires-tab.blade.php             → AnneesScolairesTab
backoffice/settings/etablissements-tab.blade.php               → EtablissementsTab
backoffice/settings/frais-tab.blade.php                        → FraisTab
backoffice/settings/salles-tab.blade.php                       → SallesTab
backoffice/students/students-index.blade.php                   → StudentsIndex
backoffice/types-depenses/types-depenses-index.blade.php       → TypesDepensesIndex
backoffice/users/manage-authorization.blade.php                → ManageAuthorization
backoffice/users/users-index.blade.php                         → UsersIndex
frontoffice/.gitkeep
```

## Section C — Dead Blade files outside `resources/views/livewire/`

### C.1 — `resources/views/backoffice/` (confirmed dead — no controller/route renders them)

| File | Formerly served | Confirmed dead via |
|---|---|---|
| `auth/login.blade.php` | `backoffice.login` | `LoginController@show` → `Inertia::render('Backoffice/Auth/Login')` |
| `auth/forgot-password.blade.php` | `backoffice.password.request` | `ForgotPasswordController@show` → Inertia |
| `auth/reset-password.blade.php` | `backoffice.password.reset` | `ResetPasswordController@show` → Inertia |
| `dashboard/index.blade.php` | `backoffice.dashboard` | `DashboardController` → Inertia |
| `settings/index.blade.php` | `backoffice.settings` | `SettingController` → Inertia. Still contains 4 `@livewire()` tags (tab components), all transitively dead. |
| `caisses/index.blade.php` | Was `caisses.index` pre-Phase-10 | Only remaining caller `CaisseManagementController` has **zero registered routes** (confirmed: class appears only in a `use` import, never in a `Route::` call). Contains 4 `@livewire()` tags, all transitively dead. |
| `depenses/index.blade.php` | Was `depenses.index` pre-Phase-10 | Only remaining caller `DepenseManagementController` is likewise unrouted. Contains 2 `@livewire()` tags, transitively dead. |
| `caisses/show.blade.php` | `caisses.show` | `CaisseController@show` → Inertia |
| `depenses/show.blade.php` | `depenses.show` | `DepenseController@show` → Inertia |
| `encaissements/show.blade.php` | `encaissements.show` | `EncaissementController@show` → Inertia |
| `caisse-transfers/show.blade.php` | `caisse-transfers.show` | `CaisseTransferController@show` → Inertia |
| `students/show.blade.php` | `students.show` | `StudentController@show` → Inertia |
| `groups/show.blade.php` | `groups.show` | `GroupController@show` → Inertia |
| `inscriptions/show.blade.php` | `inscriptions.show` | `InscriptionController@show` → Inertia |
| `groups-historique/index.blade.php` | `groups-historique.index` | `GroupHistoriqueController@index` → Inertia |
| `permissions/index.blade.php` | `permissions.index` | `PermissionController` → Inertia |
| **`employees/{index,create,show,edit}.blade.php`** | Formerly served by the OLD un-namespaced `EmployeeController` | **New finding, not in the removal plan.** See Section C.5. |

Empty placeholders, no action needed: `auth/.gitkeep`, `pages/.gitkeep`.

The removal plan's §0b claim that `caisses/depenses/encaissements/caisse-transfers` `create`/`edit` Blade views were "already dead pre-Phase-5" is confirmed — those files do not exist on disk at all (nothing to delete there; the plan's wording described controller methods whose views were never built).

### C.2 — `resources/views/frontoffice/` — **NOT part of this cleanup, must be preserved**

| File | Status |
|---|---|
| `home/index.blade.php` | **LIVE.** `HomeController` → `frontoffice.home` route. Uses `<x-frontoffice.layout.app>` (live layout). |
| `auth/.gitkeep`, `pages/.gitkeep` | Empty placeholders. |

Frontoffice was never migrated to Livewire (its `app/Livewire/Frontoffice/*` dirs are empty `.gitkeep`s) and was never migrated to Inertia either — it is a small, intentionally-still-Blade public page, entirely outside Phase 11's scope. `resources/views/frontoffice/**`, `resources/views/components/frontoffice/**`, and `resources/js/frontoffice/app.js` must all be preserved untouched.

### C.3 — `resources/views/components/backoffice/layout/*` (the entire old admin shell — all transitively dead)

| File | Status |
|---|---|
| `layout/app.blade.php` | Dead — only used by the dead pages in C.1. |
| `layout/guest.blade.php` | Dead — only used by the 3 dead auth Blade views. Removal plan explicitly flagged this one for a fresh repo-wide grep before removal; re-verified here: its only callers anywhere are the dead auth views + itself. Safe pending final re-check at actual deletion time. |
| `layout/head.blade.php` | Dead — only pulled in by `layout/app.blade.php`. Loads `@vite(['resources/scss/backoffice/app.scss', 'resources/js/backoffice/app.js'])` — this is the mechanism making the whole backoffice JS/SCSS bundle dead (Section E). |
| `layout/header.blade.php` | Dead — only pulled in by `layout/app.blade.php`. **Contains the last surviving literal `@livewire('backoffice.context.context-switcher')` tag in the codebase** — transitively dead since its parent shell is dead. This is exactly the scenario the removal plan's §0/§0a explicitly warned about ("a simple route-list check won't surface every caller" for `ContextSwitcher`) — now traced and confirmed dead. |
| `layout/{footer,sidebar,theme-settings,toasts,scripts,page-header,breadcrumbs,print}.blade.php` | All dead — pulled in directly or transitively only by `layout/app.blade.php`. |

Grep for `x-backoffice.layout.app`/`x-backoffice.layout.guest` confirms callers are exclusively the dead Blade pages in C.1, `layout/app.blade.php` itself, and one unrelated hit in `resources/views/theme-reference/crm-gls/README.md` (permanent reference material per CLAUDE.md §3 — must not be touched).

### C.4 — `resources/views/components/backoffice/{forms,ui}/*` (shared widgets, Livewire-coupled)

Consumed exclusively by the dead Livewire views (Section B) and dead Blade shells (Section C.1/C.3):

- `forms/select2.blade.php` — `x-data="glsSelect2(...)"` Alpine binding + `wire:model`.
- `forms/select.blade.php`, `forms/tags-input.blade.php` (docblock: "glsTagsInput Alpine bridge"), `forms/phone-input.blade.php`, `forms/phone-country.blade.php`.
- `forms/{error,textarea,input}.blade.php` — **not individually confirmed for `wire:*` presence**; swept in by directory/family pattern. Flagged for a literal per-file grep at actual deletion time (Section H item 6).
- `ui/action-menu.blade.php`, `ui/action-menu/item.blade.php`, `ui/modal.blade.php`, `ui/button.blade.php`, `ui/filter-bar.blade.php`, `ui/filter-bar/date-field.blade.php`, `ui/per-page-select.blade.php`, `ui/{pagination,alert,badge,card,empty-state,sexe-icon,table}.blade.php` — same caveat as above for the ones not individually confirmed.

**Confirmed via grep**: every file anywhere under `resources/views/` containing `wire:model|wire:click|wire:submit|wire:poll|wire:ignore|wire:key|wire:navigate|wire:confirm|wire:loading|wire:target|wire:init` is either a Section B Livewire view or one of these shared widgets. **Zero `wire:*` attributes exist in `app.blade.php`, `resources/views/frontoffice/**`, or any currently-routed backoffice Blade file.**

### C.5 — New finding: old un-namespaced `app/Http/Controllers/Backoffice/EmployeeController.php`

**Independently verified, confirmed dead.** `routes/backoffice.php` line 17 imports `use App\Http\Controllers\Backoffice\Employees\EmployeeController;` (the namespaced, live class) — every `EmployeeController::class` reference in the routes file (lines 123-130) resolves to that import, **not** to the bare `app/Http/Controllers/Backoffice/EmployeeController.php` (no `Employees\` subnamespace). That bare file:
- Has zero route registrations anywhere (`grep` for the bare class name outside its own file and the `use Employees\EmployeeController` import returns nothing).
- Calls `view('backoffice.employees.{index,create,show,edit}')` — Blade views that would need routing to ever render, and don't.
- Is structurally identical in shape/purpose to `CaisseManagementController`/`DepenseManagementController` (both already confirmed dead and documented in the removal plan's §0g) but was **not** called out in the removal plan's own §0d (Phase 7) entry — a gap in that document, now closed here.

**Recommend: safe to delete alongside the People/Employees Livewire cleanup group**, together with its four dead Blade views (`resources/views/backoffice/employees/{index,create,show,edit}.blade.php`).

## Section D — Livewire framework wiring

- No `LivewireServiceProvider` or custom provider registering Livewire components/directives. `app/Providers/AppServiceProvider.php` (the only provider) contains zero Livewire code — only `CurrentContext` singleton registration, `EmployeeObserver`, the `Gate::before` super-admin bypass, and the password-reset URL override.
- No `config/livewire.php` (Livewire's default config was never published/customized).
- `bootstrap/app.php` has one Livewire-related **comment** only, no code.
- No route anywhere targets `App\Livewire\*`.
- Livewire is registered purely via Composer package auto-discovery (`livewire/livewire` in `composer.json`), no explicit provider array override.

**Conclusion: no explicit framework wiring beyond the Composer dependency — consistent with Livewire's zero-config auto-discovery model.**

## Section E — JS/CSS findings

### E.1 — `resources/js/backoffice/` (app.js, theme.js, plugins/.gitkeep)

- `app.js` contains: `livewire:init`/`Livewire.on('toast', ...)` (line 397), `livewire:navigated` re-init hook (line 446), the `glsSelect2` Alpine component + `mountSelect2()` (lines 36-72) reacting to a custom `gls-select2-modal-opened` event and toggling `select2-hidden-accessible` (lines 240/275/410), and a `glsTagsInput` Alpine bridge.
- Loaded by exactly one Blade partial: `components/backoffice/layout/head.blade.php`, itself only pulled in by `layout/app.blade.php` (Section C.3) — which is not rendered by any live route.
- **Conclusion: the entire `resources/js/backoffice/` directory (`app.js`, `theme.js`) is fully dead code.** Still listed in `vite.config.js`'s `input` array (Vite still *builds* it; nothing *serves* it) — see Section F.3 for the required config edit.

### E.2 — `resources/js/frontoffice/` — **live, must be preserved**

`app.js` is a near-empty stub with zero actual Livewire/Alpine code (only a preventive comment). Loaded by `components/frontoffice/layout/{app,guest}.blade.php`, used by the live `frontoffice.home` route. Not a deletion candidate.

### E.3 — Select2/jQuery in the React app — independently re-verified, confirmed absent

Directly grepped `resources/js/**/*.{tsx,ts}` for `select2`/`jquery` (not just inferred from Blade-side absence). Matches found in exactly 6 files — **every single one is a code comment explicitly documenting the deliberate absence of Select2/jQuery**, not actual usage:

- `Components/Context/ContextSwitcher.tsx` — "no Select2, no jQuery."
- `Components/Forms/PhoneField.tsx` — "native `<select>` (no Select2/jQuery on Inertia pages...)"
- `Components/Forms/SelectField.tsx` — explains why Select2's Blade-side rule doesn't apply to Inertia modals.
- `Components/Forms/PasswordField.tsx` — references the OLD jQuery handler in `public/assets/crm-gls/js/script.js` (vendor file, untouched, correctly not imported).
- `Components/Modals/Modal.tsx` — "no bootstrap.bundle.js... no jQuery."
- `Layouts/BackofficeLayout.tsx` — "React-owned instead of jQuery/Bootstrap-JS."

**Confirmed: zero live React page uses Select2 or jQuery.** The Select2 Alpine bridge (`glsSelect2`, `mountSelect2`, `select2-hidden-accessible`, `gls-select2-modal-opened`) in `resources/js/backoffice/app.js` is fully dead.

### E.4 — Alpine.js standalone-import check

`alpinejs` does not appear anywhere in `package.json` (dependencies: only `@inertiajs/react`, `react`, `react-dom`; devDependencies: `@types/react`, `@types/react-dom`, `@vitejs/plugin-react`, `concurrently`, `laravel-vite-plugin`, `sass`, `typescript`, `vite`). CLAUDE.md's rule ("never explicitly import Alpine — Livewire bundles it") is being followed; nothing to flag.

## Section F — composer.json / package.json / vite.config

### F.1 — composer.json

`"livewire/livewire": "^4.3"` is the only Livewire-specific package (confirmed present in `composer.lock` too). No other Livewire-ecosystem packages. **Not evaluated for removal in this audit** — that determination happens only after Blade/component deletion is complete and re-verified (Phase 11G).

### F.2 — package.json

No Livewire-related JS packages, no `@livewire/*` scoped packages, no `alpinejs` direct dependency (Section E.4). Only Inertia/React/Vite tooling present.

### F.3 — vite.config.js — ⚠ requires an EDIT (not a deletion) once the backoffice bundle is removed

The `laravel()` plugin's `input` array still lists:
```js
input: [
    'resources/scss/backoffice/app.scss',
    'resources/js/backoffice/app.js',
    'resources/scss/frontoffice/app.scss',
    'resources/js/frontoffice/app.js',
    'resources/js/app.tsx',
],
```
Once `resources/js/backoffice/app.js`/`resources/scss/backoffice/app.scss` are deleted, this array must drop those two entries or the Vite build will fail on a missing entry point. This is an action item for the actual deletion phase (11D/11F), not something to do in the audit.

No dedicated Livewire Vite plugin exists (Livewire 4 needs none — it self-injects scripts, and per CLAUDE.md this project never even calls `@livewireScripts`/`@livewireStyles` manually).

## Section G — Test file pairing (Livewire → Inertia) and confirmed coverage gaps

### G.1 — Clean pairings (safe once the underlying Livewire component is confirmed dead)

| Livewire test | Component(s) | Inertia equivalent |
|---|---|---|
| `Students/StudentsCrudTest.php` | `StudentsIndex` | `Students/StudentsInertiaCrudTest.php` |
| `Groups/GroupsCrudTest.php` | `GroupsIndex`, (`FraisTab` incidentally) | `Groups/GroupsInertiaCrudTest.php` |
| `Inscriptions/InscriptionsCrudTest.php` | `InscriptionsIndex` | `Inscriptions/InscriptionsInertiaCrudTest.php` |
| `People/EmployeesCrudTest.php` | `EmployeesIndex` | `People/EmployeesInertiaCrudTest.php` |
| `People/UsersCrudTest.php` | `UsersIndex` | `Inertia/UsersInertiaTest.php` (different directory — see note below) |
| `Authorization/UserAuthorizationTest.php` | `ManageAuthorization` | `Inertia/UsersInertiaTest.php` (near-identical method-name parity confirmed) |
| `Authorization/RoleManagementLivewireTest.php` | `RolesIndex`, `RoleForm` | `Inertia/RolesInertiaTest.php` (method-for-method parity confirmed: unauthorized-forbidden, viewer-cannot-delete, create, validation, update-permissions, super-admin-immutable ×2, role-with-users-cannot-be-deleted, unused-role-can-be-deleted) |
| `Finance/CaissesCrudTest.php` | `CaissesIndex` | `Finance/CaissesInertiaCrudTest.php` |
| `Finance/CaisseTransfersTest.php` | `CaisseTransfersIndex` | `Finance/CaisseTransfersInertiaCrudTest.php` |
| `Finance/EncaissementsCrudTest.php` | `EncaissementsIndex` | `Finance/EncaissementsInertiaCrudTest.php` |
| `Finance/DepensesCrudTest.php` | `DepensesIndex` | `Finance/DepensesInertiaCrudTest.php` |
| `Finance/RemboursementsCrudTest.php` | `RemboursementsIndex` | `Finance/RemboursementsInertiaCrudTest.php` |
| `Context/DashboardStatsTest.php` | `DashboardStats` | `Inertia/DashboardInertiaTest.php` |

**Naming-convention note**: the `*CrudTest ↔ *InertiaCrudTest` pairing in the same directory holds for Students/Employees/Groups/Inscriptions/Finance, but Users/Roles/Context/Dashboard/Auth/Profile/Permissions all have their Inertia coverage in the separate `tests/Feature/Backoffice/Inertia/` directory instead, under different base names. Not a functional problem, but the deletion commits must search the right directory.

### G.2 — Files needing per-sub-feature verification before deletion (plausible but not individually confirmed at method level)

| Livewire test | Sub-feature | Status |
|---|---|---|
| `Students/StudentOrientationTest.php` | German-track CEFR orientation fields | Plausibly folded into `StudentsInertiaCrudTest.php` — not individually confirmed method-by-method. |
| `Inscriptions/InscriptionStudentFieldsTest.php` | Inline new-student creation within enrollment | Same caveat, vs `InscriptionsInertiaCrudTest.php`. |
| `People/EmployeeProfileFieldsTest.php` | Employee photo/postal fields | Same caveat, vs `EmployeesInertiaCrudTest.php`. |
| `Finance/CaisseManagementPageTest.php` | `CaisseJournal` read-only page | Same caveat, vs `Finance/CaissesInertiaCrudTest.php`. |

**Action required before deletion**: open each `*InertiaCrudTest.php` above and confirm the specific sub-feature assertions exist at the method level. Do not delete the 4 Livewire-only files above until this is done.

### G.3 — Files that are Livewire-test-in-comment-only (already migrated, NOT part of the deletion set)

`Finance/TypesDepensesCrudTest.php` and `Settings/SettingsTest.php` — both mention `Livewire::test(...)` only in historical docblock comments; zero actual Livewire calls in executable code. Both already test the live Inertia controllers. Leave as-is.

### G.4 — Mixed files — method-level surgery required, file must NOT be deleted wholesale

| File | Livewire-dependent methods (must be removed/rewritten) | Non-Livewire methods (MUST be preserved) |
|---|---|---|
| `Authorization/SuperAdminProtectionTest.php` | `test_the_last_super_admin_cannot_lose_the_role` (line 38), `test_a_super_admin_can_be_demoted_when_another_remains` (line 55) — both call `Livewire::test(ManageAuthorization::class, ...)` | `test_gate_before_grants_abilities_only_to_super_admins`, `test_assign_super_admin_command_assigns_the_role`, `test_assign_super_admin_command_fails_for_unknown_email`, `test_non_super_admin_users_do_not_bypass_unknown_abilities` — test `Gate::before` and the `auth:assign-super-admin` Artisan command directly, nothing to do with Livewire. |
| `Context/CurrentContextTest.php` | `test_switcher_component_changes_year_and_dispatches_event`, `test_switcher_component_changes_center`, `test_center_scoped_user_switcher_cannot_change_center` — all 3 call `Livewire::test(ContextSwitcher::class)` | `test_defaults_to_the_default_academic_year`, `test_year_can_be_switched_and_persists_in_session`, `test_global_user_can_switch_center_and_select_all`, `test_center_scoped_user_is_locked_to_their_center` — test the framework-agnostic `CurrentContext` service directly. |

The 3 Livewire-specific `CurrentContextTest` methods are already behaviorally covered by `Inertia/ContextUpdateTest.php` (confirmed: that file tests the same year/center-switch scenarios via the real `POST /backoffice/context` endpoint). The 2 Livewire-specific `SuperAdminProtectionTest` methods need their equivalent confirmed in `Inertia/UsersInertiaTest.php` before deletion (that file does have `test_only_a_super_admin_may_grant_super_admin` and `test_super_admin_can_grant_direct_permissions` — the "last super-admin can't lose the role" and "can be demoted when another remains" specific scenarios were **not** independently confirmed present there; flagged for verification before deleting those 2 methods).

### G.5 — ⚠ Confirmed real coverage gap — `Context/CenterScopingTest.php` — STOP CONDITION for 2 of its 7 scenarios

This is a single cross-cutting file with 7 methods, each exercising a different module's center-scoping through a Livewire component:

```
test_students_list_is_scoped_to_the_selected_center            (StudentsIndex)
test_employees_list_is_scoped_to_the_selected_center            (EmployeesIndex)
test_groups_list_and_tab_counts_are_scoped_to_the_selected_center (GroupsIndex)
test_inscriptions_list_and_selects_are_scoped_to_the_selected_center (InscriptionsIndex)
test_salles_tab_is_scoped_to_the_selected_center                (SallesTab)
test_users_list_follows_the_employee_center_but_keeps_admin_accounts (UsersIndex)
test_lists_refresh_when_the_context_changes                    (cross-cutting, all of the above)
```

**Independently re-verified against every corresponding `*InertiaCrudTest.php`/`Inertia/*Test.php` file**:

| Scenario | Inertia-side equivalent found? |
|---|---|
| Students center scoping | ✅ `StudentsInertiaCrudTest::test_center_scoped_user_only_sees_their_center_students` + `test_update_and_delete_are_center_scoped_for_non_global_users` |
| Employees center scoping | ✅ `EmployeesInertiaCrudTest::test_update_and_delete_are_center_scoped_for_non_global_users` |
| Groups center scoping | ✅ `GroupsInertiaCrudTest::test_update_is_center_scoped_for_non_global_users` |
| Inscriptions center scoping | ✅ `InscriptionsInertiaCrudTest::test_center_scoped_user_cannot_view_other_center_registrations` |
| **Salles tab center scoping** | ❌ **No equivalent found anywhere** in `Settings/SettingsTest.php` or elsewhere. |
| **Users list: follows employee center but keeps admin accounts visible** | ❌ **No equivalent found** in `Inertia/UsersInertiaTest.php` — that file covers role/permission-assignment authorization thoroughly but not this specific center-scoping-with-admin-exception behavior. |
| Lists refresh on context change | No single unified equivalent (this is a Livewire-specific reactivity concept — Inertia pages re-fetch via full requests, so the "refresh on event" framing doesn't translate 1:1; the underlying behavior — that a changed context produces different scoped results on the next page load — IS implicitly covered by the center-scoping tests above, just not as a dedicated "does it live-refresh" test). |

**This is a genuine, confirmed coverage gap and a stop condition per the task's own rules** ("if a deleted test has no equivalent behavioral coverage" / "port the behavior... before deleting the old test"). **Recommendation**: before `SallesTab` and `UsersIndex` can be deleted, add two new test methods — one in the Settings Inertia test file (or a new `Settings/SallesInertiaTest.php` if Salles ever gets its own file) asserting Salles-tab center scoping, and one in `Inertia/UsersInertiaTest.php` asserting the admin-accounts-visible-despite-center-scoping behavior — both ported from `CenterScopingTest.php`'s existing assertions. Only then should `CenterScopingTest.php` be deleted (the other 5 scenarios are already safely covered elsewhere).

### G.6 — Directory listing for completeness

All `tests/Feature/Backoffice/**` files enumerated and cross-checked; no test file was found outside the categories above. `Authorization/RoleManagementAuthorizationTest.php` and `Authorization/{CenterAccessTest,RolesAndPermissionsSeederTest}.php` contain zero Livewire calls and are already Inertia-era in substance (route/HTTP-level tests) — not part of the deletion set.

## Section H — Full route list

Confirmed via full read of `routes/backoffice.php`, `routes/web.php` (pure loader — `require`s frontoffice.php + backoffice.php), `routes/frontoffice.php`, `routes/console.php` (default `inspire` command only — no `routes/auth.php` exists separately, auth routes live inline in `backoffice.php`'s guest group).

**Zero `App\Livewire\*` route targets found in any file.** Every backoffice module route now targets a real controller (invokable, resource, or explicit action), except two intentional redirect closures preserved for deep-linking:
- `backoffice.remboursements.index` → redirects to `backoffice.depenses.index?tab=remboursements`
- `backoffice.caisse-transfers.index` → redirects to `backoffice.caisses.index?tab=transferts`

(Full per-route table with method/URI/name/controller/permission omitted here for brevity — already exhaustively documented across the Phase 6-10 status entries in `docs/inertia-react-migration-status.md`; nothing in that table has changed since Phase 10 completed. Re-verified route count: 92 backoffice routes + 2 frontoffice routes, matching the count already confirmed at the end of Phase 10.)

## Section I — Cross-check against `docs/legacy-frontend-removal-plan.md`

That document (361 lines) is itself already a near-complete deletion manifest, built section-by-section as each phase completed:

- **§0g (Phase 10, Finance)**: Caisses/CaisseJournal/CaisseTransfers/Encaissements/Depenses/Remboursements + views + `CaisseManagementController`/`DepenseManagementController` + their Blade shells. Matches Section A/B/C.1 exactly.
- **§0f (Phase 9, Inscriptions)**: InscriptionsIndex + view, plus `InscriptionFeeController`/its Form Requests (dead-as-part-of-migration, not dead-before-migration).
- **§0e (Phase 8, Students/Groups)**, **§0d (Phase 7, Employees/Users/Roles)**, **§0c (Phase 6, Settings tabs)**, **§0b (Phase 5, read-only show pages)**, **§0/§0a (Phase 3/4, Auth/Profile/Dashboard/Context)** — all match Sections A/B/C.1 with no discrepancies.
- **§1**: States cleanup cannot begin until all 22 components have tested Inertia equivalents, every route is repointed, the full suite passes, and explicit sign-off is given. This audit is the first step toward satisfying that precondition — deletion itself has not yet started.
- **§2-§3**: Safe-to-remove criteria and removal order (read-only → simple CRUD → People → Academic → Finance last) — this audit's Section D of the task's recommended order matches.
- **§4**: Package-level Livewire/Alpine removal conditions — conditioned on zero remaining referenced Livewire classes/directives, which Section A/D now confirms. Also flags removing only the `livewire:navigated` listener from `app.js`, a **more conservative** framing than this audit's Section E.1 finding that the *entire* `app.js` file is dead (because zero backoffice Blade pages route anywhere anymore, not just the one listener). This audit's finding supersedes §4's more cautious original framing — full removal of `app.js`/`theme.js`, not just one listener, is justified given the confirmed-dead status of every caller.
- **§5**: Static asset (`public/assets/crm-gls/`) removal — explicitly out of scope, not investigated.
- **§6 (permanent, never remove)**: `resources/views/theme-reference/crm-gls/`, all Domain actions, Policies, Form Requests, Models, migrations, seeders, the DB schema, and the principle that existing tests must be **adapted, never simply deleted** without equivalent coverage — this is the exact rule driving Section G's stop-condition treatment.
- **§7/§8**: Rollback-via-git-revert strategy; explicit non-goals (no bulk `rm -rf`, no "looks right" as proof).

**This audit independently re-derives the same conclusions as the removal plan via direct verification**, with 3 refinements: (1) the old un-namespaced `EmployeeController` + its dead Blade views (Section C.5, not previously documented), (2) the full transitive-dead status of `components/backoffice/layout/*` and the entire `resources/js/backoffice/` bundle (Section C.3/E.1, more complete than §4's partial framing), (3) the 2 confirmed test-coverage gaps in Section G.5 that must be closed before `SallesTab`/`UsersIndex` can be safely deleted.

## Section J — Remaining items requiring manual judgment / follow-up at actual deletion time

1. **`resources/views/components/backoffice/forms/{error,textarea,input}.blade.php`** and several `ui/*.blade.php` files — swept into the "Livewire-coupled shared widget" bucket by directory/family pattern, but not individually grepped for `wire:*` presence file-by-file. Do one more targeted grep per file immediately before deleting each, to rule out an unexpectedly-generic file with zero real Livewire coupling that might be reused somewhere unexpected.
2. **`CLAUDE.md` is stale** relative to the current migration state (still describes Livewire as the primary pattern in multiple sections, ~30 mentions). This is a documentation-currency issue tracked for Phase 11L, not a code-safety issue for 11A-11D.
3. **`vite.config.js` needs an edit** (drop 2 entries from `input`), not a deletion, once the backoffice JS/SCSS bundle is removed (Section F.3) — tracked as an explicit action item for Phase 11D/F execution.
4. **Empty `.gitkeep` placeholders** throughout (`app/Livewire/{Backoffice/{Dashboard,Shared},Frontoffice/{Home,Shared}}/.gitkeep`, `resources/views/livewire/{backoffice,frontoffice}/.gitkeep`, `resources/views/backoffice/{auth,pages}/.gitkeep`) — harmless, not meaningful deletion targets, noted for completeness only.

---

## Summary verdict for Phase 11B (route/entry-point verification)

Every migrated module's routes point exclusively to Inertia controller actions. No active route uses Livewire or a legacy Blade page. **No stop condition is triggered by Section H** — cleanup may proceed to Phase 11C (dependency graph classification) once the two test-coverage gaps in Section G.5 are either closed with new tests or explicitly accepted as a scoped, documented exception by the user.
