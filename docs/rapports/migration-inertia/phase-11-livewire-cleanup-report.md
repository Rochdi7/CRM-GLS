# Phase 11 — Livewire Cleanup: Final Report

Date: 2026-07-31 · Branch: `migration/inertia-react-preskool`

## 1. Executive summary

Phase 11 removed every remaining trace of Livewire from the GLS CRM: 22
Livewire components, their Blade views, the entire legacy admin Blade shell
and shared widget library, the Livewire-era JS/SCSS bundle, all
Livewire-only tests (after porting their behavioral coverage to
Inertia-side tests), and finally the `livewire/livewire` Composer package
itself. The backoffice is now 100% Inertia.js v3 + React 19 + TypeScript on
Laravel 13 / PostgreSQL. Every deletion was preceded by a dependency-graph
classification and a coverage-parity check, executed module-by-module in
small commits with a focused test run after each group, and the final state
was verified by the full PHP suite (307/307 passing), TypeScript, a
production build, and a real-browser (Playwright/Chromium) smoke pass over
every module for both a super-admin and a limited-role user. Along the way
the phase also absorbed, verified, and committed a concurrent UX/i18n
frontend refactor and re-exposed the three migrated Finance modules in the
sidebar. Architecture documentation (CLAUDE.md, README, backoffice/
authorization architecture docs, skills) was rewritten to describe the
current stack.

## 2. Final architecture

- **Backend**: Laravel 13, PHP 8.4, PostgreSQL only (`pgsql`; test DB
  `gls_crm_test`). Thin `final` controllers authorize (policies +
  `permission:` middleware), validate via Form Requests, call
  `app/Domain/<Module>` actions (all money movements transactional,
  invariants unchanged), and return `Inertia::render(...)`.
- **Frontend**: Inertia v3 + React 19 + TypeScript under `resources/js/`
  (`Pages/Backoffice/**`, shared `Components/**`, `Layouts/**`,
  `Hooks/**`, `Lib/i18n.ts`). PreSkool Bootstrap 5 markup/classes reused;
  behavior is 100% React — no Bootstrap JS, no jQuery, no Alpine, no
  Select2 (native `SelectField`), React-state modals (`Modal.tsx`),
  server-side pagination/search/filter via Inertia partial reloads,
  `t()`/`useTranslation()` i18n reading `lang/fr.json`,
  `useInertiaLoading()` busy states.
- **Frontoffice**: still Blade by design (public site is a future phase) —
  4 layout components + home page.
- **Authorization**: unchanged server-side model (spatie/laravel-permission
  v8, PermissionRegistry, center scoping via CenterAccessService); client
  receives `auth.permissions` + `auth.isSuperAdmin` + per-resource
  `CrudPermissions` as UI-convenience props only.

## 3. Audit findings (Phase 11A–C recap)

`docs/phase-11-livewire-cleanup-audit.md` inventoried every Livewire-era
file; `docs/phase-11-dependency-graph.md` classified each as SAFE TO
DELETE / STILL ACTIVE / SHARED WITH INERTIA / UNCERTAIN, then closed every
UNCERTAIN item by re-verification (10 shared Blade widgets, all confirmed
dead). Two coverage gaps (Salles-tab center scoping; Users list following
the employee center while keeping admin accounts) plus 13 further gaps
found during the audit were closed with 16 new Inertia-side test methods
**before** any deletion (`docs/phase-11-test-coverage-mapping.md`,
commit `7456028`).

## 4. Files deleted

By category (full per-file detail in the individual commit messages):

- **22 Livewire components** (`app/Livewire/Backoffice/**`): Students,
  Inscriptions, Groups, Employees, Users, ManageAuthorization, 4 Settings
  tabs, RolesIndex, RoleForm, CaissesIndex, CaisseJournal,
  EncaissementsIndex, DepensesIndex, RemboursementsIndex,
  CaisseTransfersIndex, TypesDepensesIndex, DashboardStats, ProfilePage,
  ContextSwitcher — plus 4 Concerns traits (WithCaisseSelection,
  WithCenterContext, WithPerPage, WithPhoneCountry) and all `.gitkeep`
  scaffolding (the `app/Livewire/` tree no longer exists).
- **All Livewire Blade views** (`resources/views/livewire/**` — tree gone).
- **3 legacy controllers**: the old un-namespaced `EmployeeController`,
  `CaisseManagementController`, `DepenseManagementController`.
- **The old admin Blade shell**: 12 layout components
  (`components/backoffice/layout/**`), 11 dead page views under
  `resources/views/backoffice/**` (auth pages, dashboard, settings,
  permissions, show pages, groups-historique), `vendor/pagination/
  backoffice-links.blade.php`.
- **The shared Blade widget library**: 8 `components/backoffice/forms/*`
  + 14 `components/backoffice/ui/*` files.
- **The Livewire-era asset bundle**: `resources/js/backoffice/{app,theme}.js`,
  `resources/scss/backoffice/app.scss` (+ vite.config.js entries).
- **Livewire-only tests**: 15 whole files (each with a verified Inertia
  replacement pairing) + surgical method-level removals in 4 mixed files
  (`SuperAdminProtectionTest`, `CurrentContextTest`, `ContextUpdateTest`,
  `CenterScopingTest` — the last shrank to zero and was deleted).
- **`skills/livewire-component-builder/SKILL.md`** (instructed building
  components for a stack that no longer exists).

## 5. Files preserved and why

- `resources/views/theme-reference/**` and
  `resources/theme-reference/crm-gls-react/**` — permanent theme
  references (CLAUDE.md §3, never deleted).
- `resources/views/app.blade.php` — the Inertia root template (active).
- The Frontoffice Blade views/layouts + `resources/js/frontoffice/app.js`
  + `resources/scss/frontoffice/` — the public area stays Blade by design.
- All controllers, Form Requests, Domain actions/queries, policies,
  models, seeders — active application code; Domain read-model queries
  (`Get*List`) were extracted from the Livewire `render()` methods and are
  the live data layer for the React pages.
- Historical docs (`PERFORMANCE_*`, `POSTGRES_*`, migration plan/audit)
  — kept as records, with SUPERSEDED banners where they described the
  old architecture as current.

## 6. Packages removed / confirmed absent

- **Removed**: `livewire/livewire` (^4.3) — dropped from `composer.json`;
  `composer update livewire/livewire --with-all-dependencies` produced
  exactly 1 removal (nothing else depended on it); package discovery,
  caches, and autoload regenerate with zero Livewire entries.
- **Confirmed absent** (never in `package.json`): alpinejs, jquery,
  select2 — and a repo-wide grep finds zero imports/calls of any of them.

## 7. Tests ported

16 new Inertia-side methods covering: Salles center scoping, Users
center-following + admin visibility, last-super-admin protection (2),
Students orientation-track clearing + CIN, Inscriptions new-student-mode
CIN/professional/exam/domaine/parent-relation (4), Employees photo
replace/oversize/address-search (3), Caisses journal scopes + self-heal +
transfers-only access (3). Six Livewire-reactivity-only assertions were
deliberately not ported (in-memory component state / DOM-morph mechanics
with no Inertia equivalent) — documented in
`docs/phase-11-test-coverage-mapping.md`.

## 8. Test results

- Per directory (each run in isolation): Unit 4 · Authorization/Context/
  Groups 46 · Inertia/Inscriptions 111 · People/Settings/Students 58 ·
  Finance 62 — **281/281 passing**.
- **Combined single-process full suite: 307/307 passing, 1531 assertions,
  ~159s** — completed without the historical stall (run twice during the
  phase, both clean). The 307 vs. 281 delta is root-level Feature tests
  outside the five grouped directories.

## 9. TypeScript result

`npx tsc --noEmit` — clean, zero errors (run after every commit that
touched TS, including the i18n refactor and the Finance nav change).

## 10. Build result

`npm run build` — succeeds. 676 modules transformed, no missing imports.

## 11. Bundle size

Main JS bundle **566.91 KB / 152.76 KB gzip** (Phase 10-era baseline:
529.14 KB / 139.91 KB gzip). The +12.9 KB gzip is the `lang/fr.json`
dictionary now bundled for the client-side `t()` helper plus the new
loading/jump-to-page hooks — flagged in
`docs/phase-11-performance-baseline.md` with dictionary chunk-splitting as
the lever if it ever matters. The pre-existing >500 KB chunk advisory
(code-splitting) remains a known future improvement, unrelated to Phase 11.

## 12. Route verification

`php artisan route:list`: 104 routes total, **97 backoffice** — all
resolving to real `Backoffice\*` controllers, zero Livewire targets, zero
errors; `frontoffice.root`/`frontoffice.home` intact. Money records still
have no destroy routes; groups still archive-only.

## 13. Remaining Livewire references and classification

Repo-wide searches (`App\Livewire`, `Livewire::`, `wire:*`, `@livewire`,
`<livewire:`, `app/Livewire`, `resources/views/livewire`, `select2`,
`jquery`, `glsSelect2`, `select2-hidden-accessible`,
`view('livewire...`) find **zero active code references**. Every remaining
hit is a historical/provenance comment: PHP docblocks in Domain queries /
controllers / Form Requests explaining which Livewire behavior they
replicate, React component docblocks naming the Blade file they were
adapted from, and 2 comment-only `Livewire::test` mentions in test-file
docblocks. `app/Livewire/` and `resources/views/livewire/` do not exist on
disk. `composer.lock` contains no livewire entry.

## 14. Remaining Blade views and their purpose

6 files (excluding theme-reference):

| File | Purpose |
|---|---|
| `resources/views/app.blade.php` | Inertia root template (active) |
| `components/frontoffice/layout/{app,guest,header,footer}.blade.php` | Public Frontoffice shell (Blade by design) |
| `frontoffice/home/index.blade.php` | Public home page (future phase) |

## 15. Finance navigation status

**Exposed.** The three items (Cash management, Expense management, Expense
types) were commented out in the working tree with "Hidden for now"
markers but active in committed history; every enablement condition
verified (routes active behind permission middleware, permission names
match PermissionRegistry, fr.json translations exist, any-of visibility
matches the transfers-only-user server behavior, super-admin covered by
the `isSuperAdmin` prop, no matchPath collisions) — re-enabled in commit
`b080265` and confirmed visible in the browser smoke run.

## 16. Browser verification status

**Functionally smoke-verified in a real headless Chromium (Playwright)**
against `artisan serve` + the production build:

- Super-admin: **25/25 checks**, 0 console errors, 0 failed requests —
  login, all 13 module pages, 3 Finance sidebar items visible, SPA
  navigation (no full reload), debounced search + empty state, modal
  open/Escape/reopen, deep-page refresh, back/forward, legacy
  caisse-transfers redirect.
- Limited role (teacher): **7/7 checks** — sidebar hides Roles/Finance,
  shows Groups; direct `/backoffice/roles` and `/backoffice/caisses`
  return real 403s; Groups loads. (Console showed only the two
  deliberately-triggered 403s.)

Still manual (visual-only): dark mode, RTL, mobile-width sidebar, pixel
regressions — see `docs/phase-11-manual-browser-checklist.md`.

## 17. Known limitations

1. Visual/theme checklist items (dark/RTL/mobile) not machine-verified.
2. `CaisseController::journal()` still merges finance tables in PHP —
   pre-existing, deliberately deferred bottleneck (CLAUDE.md §17).
3. Bundle exceeds Vite's 500 KB advisory; code-splitting (incl. the i18n
   dictionary) is a future optimization.
4. `encaissements` measured +3 SQL queries vs. Phase 10 (17→20) in the
   performance baseline — small, unexplored, flagged for a future pass.
5. Historical Livewire mentions remain in comments/docs by design (they
   document provenance, not dependencies).

## 18. Rollback instructions

Phase 11 is a linear sequence of small commits on
`migration/inertia-react-preskool`; nothing was force-pushed or rewritten.

- To undo any single step: `git revert <hash>` (deletion commits revert
  cleanly back to files-restored; the Composer-removal commit revert
  restores `composer.json`/`composer.lock` — run `composer install`
  afterwards).
- To restore the full pre-Phase-11 state: `git revert` the range
  `f879d57..HEAD` (or branch from `f879d57~1`). After reverting the
  package-removal commit `3b8b09c`, run
  `C:\php84\php.exe C:\composer\composer.phar install` and
  `php artisan optimize:clear`.
- The database was never touched — no migrations, schema, or data changes
  in this phase; no DB rollback exists or is needed.

## 19. Phase 11 commit hashes (chronological)

| Hash | Subject |
|---|---|
| `f879d57` | phase11: add cleanup audit and route map |
| `7456028` | phase11: close all confirmed test-coverage gaps before deletion |
| `3a0f844` | phase11: dependency graph classification |
| `2ac7fe5` | phase11: remove dead student Livewire code |
| `cd6fb41` | phase11: remove dead inscriptions Livewire code |
| `47dea24` | phase11: remove dead groups Livewire code |
| `e6a6b9f` | phase11: remove dead employees and people Livewire code |
| `1605fa0` | phase11: remove dead users and permissions Livewire code |
| `d619338` | phase11: remove dead settings and permissions Livewire code |
| `b82d5d8` | phase11: remove dead roles Livewire code |
| `e61c569` | phase11: remove dead caisses Livewire code |
| `d9adc4f` | phase11: remove dead encaissements Livewire code |
| `415932b` | phase11: remove dead depenses Livewire code |
| `a437c4d` | phase11: remove dead remboursements Livewire code |
| `d9c1ec9` | phase11: remove dead caisse transfers Livewire code |
| `e9fa11d` | phase11: remove dead shared Livewire components and old admin Blade shell |
| `0298c30` | phase11: remove dead backoffice JS/SCSS bundle and shared Blade widgets |
| `3b8b09c` | phase11: remove livewire/livewire package and empty scaffolding dirs |
| `8f98a29` | phase11: final verification pass + fix 3 actively-misleading Livewire comments |
| `886c185` | phase11: document manual browser checklist (pending) and performance baseline |
| `9538a9a` | feat(frontend): add i18n and Inertia loading states *(concurrent-session work, verified+committed in the Phase 11 wrap-up)* |
| `8505777` | fix(auth): share super-admin state with React navigation *(same)* |
| `ec6f5bc` | fix(seed): derive teacher demo logins from employee names, French faker *(same)* |
| `b080265` | fix(navigation): expose migrated finance modules |
| `bd1606a` | phase11: update architecture docs and remove Livewire skill |
| `9bb1fba` | phase11: fix stale SelectField docblock |
| *(final)* | phase11: final report, browser-verification results, baseline update *(the commit adding this file)* |

---

**PHASE 11 COMPLETE** — the application is fully standardized on
Inertia + React; Livewire no longer exists in the codebase, the dependency
tree, or the build.
