# Legacy Frontend (Livewire/Blade/Alpine) — Removal Plan

Status: **Not scheduled to run. This is Phase 11 (final cleanup) only, and
it does not begin until every module in the migration order (plan doc §5)
has an Inertia replacement that is built, tested, and confirmed in
production use.** Phase 10 (Finance) completed the last module in that
order — see §0g below — but per its own instructions, Phase 10 explicitly
does not begin Phase 11 cleanup either; every file in this document remains
retained until the user separately approves the cleanup phase.

This document exists now (Phase 0) purely so the *criteria* for safe removal
are agreed upfront — not because removal is imminent. Nothing in this file
authorizes deleting anything today.

---

## 0g. Phase 10 additions to the retained-legacy list

Caisses, Encaissements, Dépenses, Remboursements, and CaisseTransfers are
now migrated (docs/inertia-react-migration-status.md, Phase 10 entry) — the
last modules in the migration order (plan doc §5).

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Caisses/CaissesIndex.php` | No longer rendered — `backoffice.caisses.index` now serves `CaisseController@index`; kept for rollback |
| `app/Livewire/Backoffice/Caisses/CaisseJournal.php` | No longer rendered — the journal tab now fetches `CaisseController@journal`; kept for rollback |
| `resources/views/livewire/backoffice/caisses/{caisses-index,caisse-journal}.blade.php` | Owning views of the above |
| `app/Livewire/Backoffice/CaisseTransfers/CaisseTransfersIndex.php` | No longer rendered — the transfers tab now uses `CaisseController@index`'s `transfers` prop + `CaisseTransferController`; kept for rollback |
| `resources/views/livewire/backoffice/caisse-transfers/caisse-transfers-index.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Encaissements/EncaissementsIndex.php` | No longer rendered — `backoffice.encaissements.index` now serves `EncaissementController@index`; kept for rollback |
| `resources/views/livewire/backoffice/encaissements/encaissements-index.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Depenses/DepensesIndex.php` | No longer rendered — `backoffice.depenses.index` now serves `DepenseController@index`; kept for rollback |
| `resources/views/livewire/backoffice/depenses/depenses-index.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Remboursements/RemboursementsIndex.php` | No longer rendered — the remboursements tab now uses `DepenseController@index`'s `remboursements` prop + `RemboursementController`; kept for rollback |
| `resources/views/livewire/backoffice/remboursements/remboursements-index.blade.php` | Owning view of the above |
| `app/Http/Controllers/Backoffice/CaisseManagementController.php` | Superseded — its `abort_unless(canAny(...))` gate now lives inline in `CaisseController::index()`; kept for rollback |
| `app/Http/Controllers/Backoffice/DepenseManagementController.php` | Superseded — same pattern, gate now lives inline in `DepenseController::index()`; kept for rollback |
| `resources/views/backoffice/{caisses,depenses}/index.blade.php` | Owning Blade shells of the two superseded controllers above |

**`app/Http/Requests/Backoffice/{Caisses,Encaissements,Depenses,
Remboursements,CaisseTransfers}/{Store,Update}*Request.php` were NOT
retired** — like every prior phase's equivalent requests, these existed
before Phase 10 (validating either a dead/divergent field set or, for
Caisses, a capability with no live UI at all) and most are now the ACTIVE
Form Requests for the new controller actions. Two exceptions, consistent
with the resolved open questions:

- `Caisses\{Store,Update}CaisseRequest` remain fully dead — no manual Caisse
  CRUD was added (auto-provisioning only, Q-equivalent decision in the
  mapping doc), so `CaisseController` still has no routed `store`/`update`.
- `StoreEncaissementRequest` was rewritten (not merely re-routed) to the
  real multi-row cascade shape — its pre-Phase-10 single-fee shape never
  matched the live Livewire form and would have been actively wrong if
  reused as-is (see the audit's §7 finding).

Nothing to mark unused for the other eight Form Requests — they are now
genuinely live.

---

## 0f. Phase 9 additions to the retained-legacy list

Inscriptions is now migrated (docs/inertia-react-migration-status.md,
Phase 9 entry).

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Inscriptions/InscriptionsIndex.php` | No longer rendered — `backoffice.inscriptions.index` now serves `InscriptionController@index`; kept for rollback |
| `resources/views/livewire/backoffice/inscriptions/inscriptions-index.blade.php` | Owning view of the above |

**`app/Http/Requests/Backoffice/Inscriptions/{Store,Update}InscriptionRequest.php`
were NOT retired** — like Phase 7/8's equivalent requests, these existed
before Phase 9 (validating a different, dead field set) and are now the
ACTIVE Form Requests for the new controller actions, fully rewritten to
match the live Livewire form exactly. Nothing to mark unused here.

**New for this phase — a working, routed controller became dead code as
part of the migration itself, not before it.** Unlike every prior phase
(where the *Livewire* component became unreferenced while its Form
Requests/controller stayed live), `App\Http\Controllers\Backoffice\
InscriptionFeeController` and its `inscription-fees.{index,store,update,
destroy}` routes were confirmed to have **zero callers anywhere in the
app** (audit doc §12 point 3) — not the create/edit fee-line flow, which
has always gone through `InscriptionsIndex::save()`/now
`InscriptionController::store()` writing `inscription_fees` rows directly,
never through this controller's own endpoints. The routes were removed from
`routes/backoffice.php` with explicit user sign-off (a more consequential
removal than simply not building on top of already-unrouted dead code, since
these routes previously worked and could in theory have had an external
caller). The controller class and its Form Requests
(`app/Http/Requests/Backoffice/InscriptionFees/` if present, or inline
validation — see the controller itself) are left on disk, now fully
unreferenced, for the same rollback-safety reason as every other retained
file in this document.

---

## 0e. Phase 8 additions to the retained-legacy list

Students and Groups are now migrated (docs/inertia-react-migration-
status.md, Phase 8 entry).

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Students/StudentsIndex.php` | No longer rendered — `backoffice.students.index` now serves `StudentController@index`; kept for rollback |
| `resources/views/livewire/backoffice/students/students-index.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Groups/GroupsIndex.php` | No longer rendered — `backoffice.groups.index` now serves `GroupController@index`; kept for rollback |
| `resources/views/livewire/backoffice/groups/groups-index.blade.php` | Owning view of the above |

**`app/Http/Requests/Backoffice/{Students,Groups}/{Store,Update}*Request.php`
were NOT retired** — like Phase 7's Employees requests, these existed
before Phase 8 (serving legacy, unrouted controllers) and are now the
ACTIVE Form Requests for the new controller actions, with their rule sets
corrected to match the live Livewire form exactly (Student requests gained
`phone_pays`/`photo`; Group requests lost `salle_id`/`capacite_max`/
`etablissement_id`/`annee_scolaire_id`, which were never real form fields).
Nothing to mark unused here.

---

## 0d. Phase 7 additions to the retained-legacy list

Four medium CRUD modules are now migrated (docs/inertia-react-migration-
status.md, Phase 7 entry): Employees, Users, Roles, and the per-user
role/direct-permission assignment screen.

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Employees/EmployeesIndex.php` | No longer rendered — `backoffice.employees.index` now serves `EmployeeController@index`; kept for rollback |
| `resources/views/livewire/backoffice/employees/employees-index.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Users/UsersIndex.php` | No longer rendered — `backoffice.users.index` now serves `UserController@index`; kept for rollback |
| `resources/views/livewire/backoffice/users/users-index.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Users/ManageAuthorization.php` | No longer rendered — `backoffice.users.authorization.edit` now serves `UserAuthorizationController@edit`; kept for rollback |
| `resources/views/livewire/backoffice/users/manage-authorization.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Roles/RolesIndex.php` | No longer rendered — `backoffice.roles.index` now serves `RoleController@index`; kept for rollback |
| `resources/views/livewire/backoffice/roles/roles-index.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Roles/RoleForm.php` | No longer rendered — `backoffice.roles.create`/`.edit` now serve `RoleController@create`/`@edit`; kept for rollback |
| `resources/views/livewire/backoffice/roles/role-form.blade.php` | Owning view of the above |

**`app/Http/Requests/Backoffice/Employees/{Store,Update}EmployeeRequest.php`
were NOT retired** — unlike the pattern above, these existed before Phase 7
(serving a legacy, unrouted `EmployeeController`) and are now the ACTIVE
Form Requests for the new `Employees\EmployeeController`, with their rule
sets expanded to match the full Livewire form. Nothing to mark unused here.

---

## 0c. Phase 6 additions to the retained-legacy list

Five simple CRUD modules are now migrated (docs/inertia-react-migration-
status.md, Phase 6 entry): Établissements, Années scolaires, Salles, Frais,
Types de dépenses.

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Settings/EtablissementsTab.php` | No longer rendered — `resources/views/backoffice/settings/index.blade.php` no longer exists (replaced by the Inertia `Backoffice/Settings/Index` page); kept for rollback |
| `resources/views/livewire/backoffice/settings/etablissements-tab.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Settings/AnneesScolairesTab.php` | Same — no longer rendered |
| `resources/views/livewire/backoffice/settings/annees-scolaires-tab.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Settings/SallesTab.php` | Same — no longer rendered |
| `resources/views/livewire/backoffice/settings/salles-tab.blade.php` | Owning view of the above |
| `app/Livewire/Backoffice/Settings/FraisTab.php` | Same — no longer rendered |
| `resources/views/livewire/backoffice/settings/frais-tab.blade.php` | Owning view of the above |
| `resources/views/backoffice/settings/index.blade.php` | No longer referenced by `SettingController` (now returns `Inertia::render()`); kept, unused, for rollback — same as every other retained file in this plan, no deletion happens outside Phase 10 |
| `app/Livewire/Backoffice/TypesDepenses/TypesDepensesIndex.php` | No longer rendered — was the 3rd tab of `resources/views/backoffice/depenses/index.blade.php`, now removed (docs/phase-6-simple-crud-inventory.md §Q2); kept for rollback |
| `resources/views/livewire/backoffice/types-depenses/types-depenses-index.blade.php` | Owning view of the above |

**`app/Http/Controllers/Backoffice/{Etablissement,AnneeScolaire,Salle,Frais,
TypeDepense}Controller.php` were NOT retired** — unlike every prior phase's
pattern (retire the Livewire component, keep the old controller as dead
code), these controllers are the ACTIVE Inertia mutation endpoints now.
`EtablissementController`/`AnneeScolaireController`/`SalleController` were
converted in place (their `store`/`update`/`destroy` already existed and
now serve the React forms); `FraisController`/`TypeDepenseController` are
new/rewritten. Nothing to mark unused here.

---

## 0b. Phase 5 additions to the retained-legacy list

8 read-only pages are now migrated (docs/inertia-react-migration-status.md,
Phase 5 entry):

| File | Why retained |
|---|---|
| `resources/views/backoffice/groups-historique/index.blade.php` | No longer referenced by `GroupHistoriqueController::index()` |
| `resources/views/backoffice/students/show.blade.php` | No longer referenced by `StudentController::show()` |
| `resources/views/backoffice/groups/show.blade.php` | No longer referenced by `GroupController::show()`; `archive()` itself is UNCHANGED and still targets `backoffice.groups.show` as its redirect — that route now serves Inertia, so the redirect lands on the new page, not this retained Blade file |
| `resources/views/backoffice/inscriptions/show.blade.php` | No longer referenced by `InscriptionController::show()` |
| `resources/views/backoffice/caisses/show.blade.php` | No longer referenced by `CaisseController::show()`. **Note**: `caisses/{index,create,edit}.blade.php` were never touched this phase — those views back controller methods (`index`/`create`/`edit`) that have **no registered route** at all (dead code, confirmed in the Phase 5 inventory); they were already effectively orphaned before this phase, not newly orphaned by it |
| `resources/views/backoffice/encaissements/show.blade.php` | No longer referenced by `EncaissementController::show()`. Same dead-code caveat applies to `encaissements/{create,edit}.blade.php` |
| `resources/views/backoffice/depenses/show.blade.php` | No longer referenced by `DepenseController::show()`. Same dead-code caveat applies to `depenses/{create,edit}.blade.php` |
| `resources/views/backoffice/caisse-transfers/show.blade.php` | No longer referenced by `CaisseTransferController::show()`. `caisse-transfers/{create}.blade.php` remains dead code as before |

**Employee and Remboursement show views were never touched** — they don't
exist (Remboursement) or were already unreachable dead code before this
phase (Employee) — nothing changed for either.

---

## 0. Phase 3 additions to the retained-legacy list

Auth and Profile are now migrated (docs/inertia-react-migration-status.md,
Phase 3 entry). The following are **retained, unused by any route**, exactly
per this plan's rules — do not delete them yet:

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Profile/ProfilePage.php` | No route points to it anymore (`backoffice.profile` now serves `ProfileController@show`); kept for rollback until Phase 10 |
| `resources/views/livewire/backoffice/profile/profile-page.blade.php` | Owning view of the above — removed together, never separately |
| `resources/views/backoffice/auth/login.blade.php` | No route points to it anymore (`backoffice.login` now serves `Inertia::render()`); kept for rollback |
| `resources/views/backoffice/auth/forgot-password.blade.php` | Same — `backoffice.password.request` now Inertia |
| `resources/views/backoffice/auth/reset-password.blade.php` | Same — `backoffice.password.reset` now Inertia |
| `resources/views/components/backoffice/layout/guest.blade.php` | No longer used by any of the three auth pages above (they used it before Phase 3); check for other callers before ever removing — do not assume it's fully orphaned without a fresh repo-wide grep at Phase 10 time |

None of these appear in any route file anymore — confirmed via
`artisan route:list` showing only the new controller actions for
`backoffice.login`, `backoffice.password.request`, `backoffice.password.reset`,
and `backoffice.profile`.

---

## 0a. Phase 4 additions to the retained-legacy list

Dashboard and the Context switcher are now migrated
(docs/inertia-react-migration-status.md, Phase 4 entry):

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Dashboard/DashboardStats.php` | No route/view points to it anymore (`backoffice.dashboard` now serves `DashboardController` → Inertia); kept for rollback |
| `resources/views/livewire/backoffice/dashboard/dashboard-stats.blade.php` | Owning view of the above |
| `resources/views/backoffice/dashboard/index.blade.php` | No longer referenced by `DashboardController`; kept for rollback |
| `app/Livewire/Backoffice/Context/ContextSwitcher.php` | No longer rendered anywhere (it was only ever included from the Blade header, which Inertia pages don't use); kept for rollback — **do not remove even after Phase 10 review without confirming no Blade page still references it**, since unlike Dashboard/Profile/Auth, this component's Blade usage was header-embedded rather than route-embedded, so a simple route-list check won't surface every caller |
| `resources/views/livewire/backoffice/context/context-switcher.blade.php` | Owning view of the above |

`GetDashboardStats`/`DashboardStatsData` under `app/Domain/Reports/` are
**new, permanent** files — not legacy, not candidates for removal; they are
the active server-side stats computation going forward.

---

## 1. Hard precondition

Do not execute **any** step in this document until:

1. All 22 Livewire components listed in `PROJECT_INVENTORY.md` §4 have a
   merged, tested Inertia/React equivalent.
2. Every route currently pointing at a Livewire component
   (`routes/backoffice.php`) has been repointed to the Inertia-rendering
   controller and verified live.
3. The full test suite passes with the Inertia versions of every page.
4. A stakeholder (you) has explicitly signed off that no user-facing
   regression exists across desktop, mobile, dark mode, and — if still
   supported — RTL.

Until then, **Livewire, Alpine (bundled), and every current Blade view stay
exactly as they are.** Phases 1–9 are additive only: new Inertia routes/pages
are built alongside the existing ones, never replacing a route until its
replacement is proven.

---

## 2. What "removal" actually means, module by module

Removal is per-module, not a single big-bang deletion. For each of the 22
Livewire components, "safe to remove" means:

| Artifact type | Condition for removal |
|---|---|
| Livewire class (`app/Livewire/Backoffice/<Module>/*.php`) | Its route no longer references it (route now calls an Inertia-returning controller action) **and** no other Livewire component/view still nests or dispatches to it |
| Livewire view (`resources/views/livewire/backoffice/<module>/*.blade.php`) | Its owning class (above) is removed |
| Any Blade page wrapping it (`resources/views/backoffice/<module>/*.blade.php`, if any) | Same — only once nothing routes to it |
| Module-specific JS/Alpine glue (if any exists beyond the shared `app.js`) | Confirmed zero references via `grep` before deletion |

**Never remove**: `resources/views/theme-reference/preskool/` (permanent
reference copies per CLAUDE.md §3 — this rule is independent of the
Inertia migration and continues to apply). These are not "legacy frontend"
in the sense this document means; they are a permanent build-time reference
library and stay regardless of what serves production traffic.

---

## 3. Order of removal (mirrors the build order, reversed risk-first logic)

Removal should follow the **same phase order** the modules were converted
in (plan doc §5), so that the lowest-risk, most-recently-verified modules
are cleaned up first and the highest-risk finance modules' legacy code stays
available as a fallback the longest:

1. Permissions, groups-historique, other read-only/show pages
2. Simple CRUD (types-depenses, etablissements, annees-scolaires, salles, frais)
3. People (students, employees, users, roles, authorization)
4. Academic (groups, inscriptions, inscription-fees)
5. Finance (caisses, journal, encaissements, depenses, remboursements,
   transfers) — removed **last**, and only after an extended verification
   window given the financial invariants involved (till balances, two-step
   transfer validation, activity logging) — recommend the longest soak time
   here before deleting the Livewire fallback.

---

## 4. Package-level removal (only after every module above is gone)

| Package | Removal condition |
|---|---|
| `livewire/livewire` (composer) | Zero remaining `App\Livewire\*` classes referenced by any route, zero `<livewire:...>`/`@livewire` directives in any Blade view still being served |
| Bundled Alpine (comes with Livewire) | Removed automatically when Livewire is removed — verify nothing else added a standalone Alpine dependency in the interim (re-check CLAUDE.md §6's "zero results outside vendor" grep) |
| `resources/js/backoffice/app.js`'s `initializeBackofficePlugins()` Livewire-navigation hook (`livewire:navigated` listener) | Remove only the Livewire-specific event listener; the plugin-init logic itself may still be needed if any static Blade page (non-Livewire) remains |

## 5. Static asset removal (`public/assets/preskool/`)

Only remove a static PreSkool asset (CSS/JS/font/icon file) once:

1. A code search (`grep`/`Grep`) across the **entire** `resources/`,
   `app/`, and remaining `public/` tree shows zero references to that
   specific file path, **and**
2. The equivalent visual/behavioral need is fully covered by the new React
   theme's own asset (per `docs/react-theme-file-map.md`), **and**
3. A manual visual check confirms no regression (per the Quality Checks in
   CLAUDE.md §14 — theme rendering, dark mode, mobile, RTL).

Do not remove `public/assets/preskool/` wholesale — remove individual proven-
unused files/directories only, in small reviewable commits.

---

## 6. What never gets removed, regardless of migration progress

- `resources/views/theme-reference/preskool/` (252 permanent reference pages)
- Any Domain action (`EnregistrerEncaissement`, `EnregistrerDepense`,
  `EnregistrerRemboursement`, `DemanderTransfertCaisse`,
  `ValiderTransfertCaisse`, `ReferenceGenerator`)
- Any Policy, Form Request, Model, migration, or seeder
- The database schema itself (no schema changes are anticipated by this
  migration at all — it is a presentation-layer change only)
- Tests — existing Feature tests are adapted to assert against the new
  Inertia responses, never simply deleted because "the Livewire version is
  gone." Coverage must be equal or greater after each module's conversion.

---

## 7. Rollback within Phase 10 itself

Because removal happens in small per-module commits (§3), rolling back a
single over-eager removal is a single `git revert` of that module's removal
commit — it does not affect any other module's already-completed removal or
any not-yet-reached module still running on Livewire.

---

## 8. Explicit non-goals of this document

This plan does **not** authorize:

- Deleting any file today.
- Running `git clean`, `rm -rf`, or any bulk deletion command at any point —
  removal is always specific, named files, reviewed individually.
- Treating "the React page looks right" as sufficient proof of safety to
  delete the Livewire fallback — the conditions in §1 (tests, sign-off,
  cross-device/mode verification) must all be met first, every time.
