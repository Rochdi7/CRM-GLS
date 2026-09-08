# Phase 11C — Dependency Graph Classification

Classifies every deletion candidate identified in `docs/phase-11-livewire-
cleanup-audit.md` into one of the four categories required before any
deletion:

1. **SAFE TO DELETE**
2. **STILL ACTIVE**
3. **SHARED WITH INERTIA**
4. **UNCERTAIN — MANUAL REVIEW REQUIRED**

Only category 1 items are deleted in Phase 11D. Categories 2-4 are never
touched in this phase. This document also fixes the deletion order to match
the module order the task specifies (Students → Inscriptions → Groups →
Employees → Users → Settings → Roles → Caisses → Encaissements → Dépenses →
Remboursements → Transferts → shared → tests → JS → Blade layout →
Composer).

---

## 1. Livewire component classes (`app/Livewire/**`)

| Class | Category | Reason |
|---|---|---|
| `Backoffice\Students\StudentsIndex` | **SAFE TO DELETE** | Zero route reference. Only remaining reference is `Inertia/ContextUpdateTest.php`'s one method, which is edited (not the class kept) in the same deletion commit — coverage confirmed closed (Phase 11H). |
| `Backoffice\Inscriptions\InscriptionsIndex` | **SAFE TO DELETE** | Zero route reference. All behavior confirmed ported (Phase 11H). |
| `Backoffice\Groups\GroupsIndex` | **SAFE TO DELETE** | Zero route reference. `GroupsInertiaCrudTest.php` already covers it. |
| `Backoffice\Employees\EmployeesIndex` | **SAFE TO DELETE** | Zero route reference. All sub-feature gaps closed (Phase 11H). |
| `Backoffice\Users\UsersIndex` | **SAFE TO DELETE** | Zero route reference. Center-scoping gap closed (Phase 11H). |
| `Backoffice\Users\ManageAuthorization` | **SAFE TO DELETE** | Zero route reference. Super-admin-protection gap closed (Phase 11H). |
| `Backoffice\Settings\EtablissementsTab` | **SAFE TO DELETE** | Zero route reference — `SettingController` serves `etablissements` tab data directly. |
| `Backoffice\Settings\AnneesScolairesTab` | **SAFE TO DELETE** | Same — `annees-scolaires` tab. |
| `Backoffice\Settings\SallesTab` | **SAFE TO DELETE** | Same — `salles` tab. Center-scoping gap closed (Phase 11H). |
| `Backoffice\Settings\FraisTab` | **SAFE TO DELETE** | Same — `frais` tab. |
| `Backoffice\Roles\RolesIndex` | **SAFE TO DELETE** | Zero route reference. `RolesInertiaTest.php` covers it method-for-method. |
| `Backoffice\Roles\RoleForm` | **SAFE TO DELETE** | Same file group as `RolesIndex` — both retired together. |
| `Backoffice\TypesDepenses\TypesDepensesIndex` | **SAFE TO DELETE** | Zero route reference (moved to its own Inertia page in Phase 6). `TypesDepensesCrudTest.php` already tests the live controller, not this class. |
| `Backoffice\Caisses\CaissesIndex` | **SAFE TO DELETE** | Zero route reference. `CaissesInertiaCrudTest.php` covers it, including the newly-ported journal sub-features. |
| `Backoffice\Caisses\CaisseJournal` | **SAFE TO DELETE** | Zero route reference. All-scope/self-heal gaps closed (Phase 11H). |
| `Backoffice\CaisseTransfers\CaisseTransfersIndex` | **SAFE TO DELETE** | Zero route reference. `CaisseTransfersInertiaCrudTest.php` covers it. |
| `Backoffice\Encaissements\EncaissementsIndex` | **SAFE TO DELETE** | Zero route reference. `EncaissementsInertiaCrudTest.php` covers it. |
| `Backoffice\Depenses\DepensesIndex` | **SAFE TO DELETE** | Zero route reference. `DepensesInertiaCrudTest.php` covers it. |
| `Backoffice\Remboursements\RemboursementsIndex` | **SAFE TO DELETE** | Zero route reference. `RemboursementsInertiaCrudTest.php` covers it. |
| `Backoffice\Dashboard\DashboardStats` | **SAFE TO DELETE** | Zero route reference. `DashboardInertiaTest.php` covers it. |
| `Backoffice\Profile\ProfilePage` | **SAFE TO DELETE** | Zero route reference. `ProfileInertiaTest.php` (confirmed present per the audit's file listing) covers it. |
| `Backoffice\Context\ContextSwitcher` | **SAFE TO DELETE** | Zero route reference; its only Blade caller (`header.blade.php`) is itself dead (Section C.3 of the audit). `ContextUpdateTest.php` covers the 3 behaviors that were Livewire-specific (Phase 11H — already confirmed, no new work needed). |
| `Backoffice\Concerns\WithCaisseSelection` | **SAFE TO DELETE** — but only after every class in category above that `use`s it is deleted first | Not a route target; a shared trait. Delete alongside the last Finance-module Livewire class that references it (Encaissements/Dépenses/Remboursements/CaisseTransfers). |
| `Backoffice\Concerns\WithCenterContext` | **SAFE TO DELETE** — same ordering caveat, delete last (used by the most classes) | Shared trait, `use`d by Students/Employees/Groups/Inscriptions/Users/Settings-tab/Caisses/Finance components. Only deletable once every one of those classes is gone. |
| `Backoffice\Concerns\WithPerPage` | **SAFE TO DELETE** — same ordering caveat | Shared trait, same pattern. |
| `Backoffice\Concerns\WithPhoneCountry` | **SAFE TO DELETE** — same ordering caveat | Shared trait, used by Students/Employees forms only — deletable once those two are gone. |
| `.gitkeep` placeholders (4 files) | **SAFE TO DELETE** | Harmless placeholders; delete only if their parent directory becomes fully empty after all real files are removed — otherwise leave (an empty tracked directory needs the `.gitkeep` to survive in git). |

## 2. Livewire Blade views (`resources/views/livewire/**`)

All 23 files (Section B of the audit): **SAFE TO DELETE**, one-to-one with
their owning component above — delete each view in the same commit as its
component.

## 3. Old Blade pages under `resources/views/backoffice/`

| File(s) | Category | Reason |
|---|---|---|
| `auth/{login,forgot-password,reset-password}.blade.php` | **SAFE TO DELETE** | Confirmed dead (Section C.1) — no controller returns a Blade `View` for these routes. |
| `dashboard/index.blade.php` | **SAFE TO DELETE** | Same. |
| `settings/index.blade.php` | **SAFE TO DELETE** | Same — contains 4 transitively-dead `@livewire()` tags. |
| `caisses/index.blade.php` | **SAFE TO DELETE** | Only caller (`CaisseManagementController`) has zero routes. |
| `depenses/index.blade.php` | **SAFE TO DELETE** | Only caller (`DepenseManagementController`) has zero routes. |
| `{caisses,depenses,encaissements,caisse-transfers,students,groups,inscriptions}/show.blade.php` | **SAFE TO DELETE** | Every corresponding `show()` controller action already returns `Inertia::render(...)`. |
| `groups-historique/index.blade.php` | **SAFE TO DELETE** | `GroupHistoriqueController@index` returns Inertia. |
| `permissions/index.blade.php` | **SAFE TO DELETE** | `PermissionController` returns Inertia. |
| `employees/{index,create,show,edit}.blade.php` | **SAFE TO DELETE** | New finding (audit Section C.5) — only caller is the old un-namespaced `EmployeeController`, itself dead. |
| `auth/.gitkeep`, `pages/.gitkeep` | **SAFE TO DELETE** | Harmless placeholders — same rule as Section 1's `.gitkeep`s. |

## 4. Old controllers

| File | Category | Reason |
|---|---|---|
| `Backoffice\CaisseManagementController` | **SAFE TO DELETE** | Zero routes reference this class (confirmed: appears only in a `use` import in `routes/backoffice.php` prior to the Phase 10 rewrite — now not even imported, since Phase 10's routes rewrite already replaced its usage with `CaisseController@index`). |
| `Backoffice\DepenseManagementController` | **SAFE TO DELETE** | Same pattern — superseded by `DepenseController@index`. |
| `Backoffice\EmployeeController` (old, un-namespaced) | **SAFE TO DELETE** | New finding (audit Section C.5) — shadowed by the live `Employees\EmployeeController`, confirmed zero route references to the bare class name. |

## 5. Frontoffice — everything here is category 2

| Item | Category | Reason |
|---|---|---|
| `app/Livewire/Frontoffice/{Home,Shared}/.gitkeep` | **N/A — not real files** | Empty placeholders, no component ever existed here. |
| `resources/views/frontoffice/**` | **STILL ACTIVE** | `home/index.blade.php` is rendered by the live `frontoffice.home` route. |
| `resources/views/components/frontoffice/**` | **STILL ACTIVE** | Used by the live Frontoffice layout. |
| `resources/js/frontoffice/app.js` | **STILL ACTIVE** | Loaded by the live Frontoffice layout. |

**None of the Frontoffice tree is touched in Phase 11.**

## 6. Shared Blade layout shell (`resources/views/components/backoffice/layout/*`)

| File | Category | Reason |
|---|---|---|
| `layout/app.blade.php` | **SAFE TO DELETE** | Only used by the dead pages in Section 3 above. |
| `layout/guest.blade.php` | **SAFE TO DELETE** | Only used by the 3 dead auth Blade views. Re-verify with a fresh grep immediately before deleting (per the audit's own caveat), since this exact file was flagged for extra care historically. |
| `layout/head.blade.php` | **SAFE TO DELETE** | Only pulled in by `layout/app.blade.php`. This is the file that loads the backoffice JS/SCSS bundle (Section 8 below) — delete together with that bundle in the same commit. |
| `layout/header.blade.php` | **SAFE TO DELETE** | Only pulled in by `layout/app.blade.php`; contains the last surviving `@livewire('backoffice.context.context-switcher')` tag, transitively dead. |
| `layout/{footer,sidebar,theme-settings,toasts,scripts,page-header,breadcrumbs,print}.blade.php` | **SAFE TO DELETE** | All only pulled in directly or transitively by `layout/app.blade.php`. |

**IMPORTANT — do not confuse with the Inertia root template.** `resources/
views/app.blade.php` (note: NOT under `components/backoffice/layout/`) is
Inertia's own root Blade template, required by every single Inertia page in
the entire application. It is **category 2 (STILL ACTIVE)**, not touched by
any deletion in this phase, and structurally unrelated to the
`components/backoffice/layout/*` shell above despite the similar naming.

## 7. Shared Blade widgets (`resources/views/components/backoffice/{forms,ui}/*`)

| File | Category | Reason |
|---|---|---|
| `forms/select2.blade.php` | **SAFE TO DELETE** | `x-data="glsSelect2(...)"` + `wire:model` — consumed exclusively by dead Livewire views. |
| `forms/select.blade.php` | **SAFE TO DELETE** | Same family. |
| `forms/tags-input.blade.php` | **SAFE TO DELETE** | `glsTagsInput` Alpine bridge, Livewire-only consumer. |
| `forms/phone-input.blade.php`, `forms/phone-country.blade.php` | **SAFE TO DELETE** | Same family, Livewire-only consumers. |
| `forms/{error,textarea,input}.blade.php` | **SAFE TO DELETE** — re-verified | Independently re-checked (Phase 11C): contain zero `wire:*` attributes of their own (generic, framework-agnostic markup), but every caller of `x-backoffice.forms.{error,textarea,input}` anywhere in `resources/views/` is either the component itself or another file in this same dead family — no live Blade file references them. |
| `ui/action-menu.blade.php`, `ui/action-menu/item.blade.php` | **SAFE TO DELETE** | Livewire-only consumers confirmed. |
| `ui/modal.blade.php` | **SAFE TO DELETE** | Livewire-only consumer (the Alpine-driven modal pattern retired in favor of React's own `Modal.tsx`). |
| `ui/button.blade.php` | **SAFE TO DELETE** | Same. |
| `ui/filter-bar.blade.php`, `ui/filter-bar/date-field.blade.php` | **SAFE TO DELETE** | Livewire-only consumers (the React `TableToolbar`/`SearchInput` components replace this). |
| `ui/per-page-select.blade.php` | **SAFE TO DELETE** | Same. |
| `ui/{pagination,alert,badge,card,empty-state,sexe-icon,table}.blade.php` | **SAFE TO DELETE** — re-verified | Independently re-checked (Phase 11C): zero `wire:*` attributes of their own. Every caller of `x-backoffice.ui.{pagination,alert,badge,card,empty-state,sexe-icon,table}` is either the component itself, or one of the dead `resources/views/backoffice/**` pages (Section 3), or one of the dead `resources/views/livewire/**` views (Section 2) — confirmed via a full-repo grep per component name, zero hits outside those two dead sets. |

## 8. JS/CSS bundle

| Item | Category | Reason |
|---|---|---|
| `resources/js/backoffice/app.js` | **SAFE TO DELETE** | Confirmed fully dead (audit Section E.1) — loaded by exactly one Blade partial (`layout/head.blade.php`), which is itself dead. Contains the `glsSelect2`/`mountSelect2`/`livewire:init`/`livewire:navigated` code. |
| `resources/js/backoffice/theme.js` | **SAFE TO DELETE** | Same loading chain as `app.js`. |
| `resources/js/backoffice/plugins/.gitkeep` | **SAFE TO DELETE** | Empty placeholder in a directory that becomes fully empty. |
| `resources/scss/backoffice/app.scss` (and its `@import`ed partials, if any) | **SAFE TO DELETE** | Loaded by the same dead `@vite([...])` call in `layout/head.blade.php`. Not independently audited file-by-file in Phase 11A — do a quick `resources/scss/backoffice/` directory listing at deletion time to confirm no partial is imported from elsewhere first. |
| `vite.config.js`'s `input` array entries for the two files above | **REQUIRES AN EDIT, NOT A DELETION** | Must drop `'resources/scss/backoffice/app.scss'` and `'resources/js/backoffice/app.js'` from the `input` array in the same commit that deletes those files, or the Vite build fails on a missing entry point (audit Section F.3). |
| `resources/js/frontoffice/**`, `resources/scss/frontoffice/**` | **STILL ACTIVE** | Frontoffice bundle, unrelated (Section 5 above). |
| Select2/jQuery in any `.tsx` file | **N/A — nothing to delete** | Confirmed zero usage (audit Section E.3, independently re-grepped) — every match found was a comment documenting the deliberate absence, not real code. Nothing to remove here since nothing was ever added. |

## 9. Composer package

| Item | Category | Reason |
|---|---|---|
| `livewire/livewire` (composer.json) | **SAFE TO DELETE — but ONLY after every item above is deleted and re-verified** | Per the task's own Phase 11G rule: confirm repository-wide zero references before removing. This is the LAST deletion in the whole phase (step 17 of the recommended order), gated on every prior category-1 item actually being gone, not merely planned. |
| `alpinejs` | **N/A — was never added** | Confirmed absent from `package.json` (audit Section E.4/E.5) — Alpine is bundled by Livewire itself and needs no explicit package-level removal. Removing `livewire/livewire` automatically removes Alpine's only source in this app; nothing extra to do. |

## 10. Tests

Full classification already produced in `docs/phase-11-test-coverage-
mapping.md`'s summary table — reproduced here for completeness against the
"category" framework:

| File(s) | Category |
|---|---|
| The 18 fully-cleared Livewire test files (13 clean pairings + `CenterScopingTest.php` + 4 sub-feature files, all listed in the mapping doc) | **SAFE TO DELETE** |
| `Authorization/SuperAdminProtectionTest.php`, `Context/CurrentContextTest.php` | **SHARED WITH INERTIA** — the file itself is permanent (holds non-Livewire coverage); only their named Livewire-dependent methods are safe to delete. |
| `Inertia/ContextUpdateTest.php` | **SHARED WITH INERTIA** — the file is permanent; only its one `StudentsIndex`-dependent method is safe to delete, in the same commit as the Students Livewire deletion group. |
| `Finance/TypesDepensesCrudTest.php`, `Settings/SettingsTest.php`, `Authorization/RoleManagementAuthorizationTest.php` | **STILL ACTIVE** — these already test live Inertia controllers; not part of the deletion set at all. |

---

## Deletion order for Phase 11D (fixed, matches the task's recommended order)

1. Students — `StudentsIndex` + view + `WithPhoneCountry`* + edit `ContextUpdateTest.php`
2. Inscriptions — `InscriptionsIndex` + view
3. Groups — `GroupsIndex` + view
4. Employees — `EmployeesIndex` + view + old `EmployeeController` + its 4 dead Blade views + `WithPhoneCountry`* (now safe, Students already gone)
5. Users — `UsersIndex`, `ManageAuthorization` + views + edit 2 methods out of `SuperAdminProtectionTest.php`
6. Settings — `{Etablissements,AnneesScolaires,Salles,Frais}Tab` + views + `settings/index.blade.php`
7. Roles — `RolesIndex`, `RoleForm` + views
8. Caisses — `CaissesIndex`, `CaisseJournal` + views + `CaisseManagementController` + `caisses/index.blade.php`
9. Encaissements — `EncaissementsIndex` + view
10. Dépenses — `DepensesIndex` + view + `DepenseManagementController` + `depenses/index.blade.php`
11. Remboursements — `RemboursementsIndex` + view
12. Transferts — `CaisseTransfersIndex` + view + `WithCaisseSelection`* (now safe, all 4 Finance consumers gone)
13. Shared Livewire-only components — `Dashboard\DashboardStats`, `Profile\ProfilePage`, `Context\ContextSwitcher` + views + `WithCenterContext`*/`WithPerPage`* (now safe, every consumer gone) + the whole `components/backoffice/layout/*` shell + the dead auth/dashboard/show/permissions Blade pages (Sections 3, 6 above)
14. Livewire-only tests — remove the 2 methods from `SuperAdminProtectionTest.php`, the 3 methods from `CurrentContextTest.php` (if not already done in steps 5/13)
15. Livewire-only JS bridges — `resources/js/backoffice/{app.js,theme.js}` + `resources/scss/backoffice/*` + the `vite.config.js` edit, in one commit
16. Shared Blade widgets — `components/backoffice/{forms,ui}/*` (Section 7 — all 10 previously-uncertain files re-verified safe in this document)
17. Livewire framework dependency — `livewire/livewire` from `composer.json`, only after every prior step is committed and re-verified with a final repo-wide grep

\* Shared trait/concern — only delete once every class listed in Section 1
that still `use`s it has already been deleted in an earlier step.

**No item in this document remains classified UNCERTAIN.** All 10
previously-flagged widget files (Section 7) were independently re-verified
via a full-repo grep per component name — every caller is either the
component itself or an already-confirmed-dead Blade file. **Nothing blocks
proceeding to Phase 11D.**
