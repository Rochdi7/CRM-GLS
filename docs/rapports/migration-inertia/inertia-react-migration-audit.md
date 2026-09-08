# Inertia + React Migration — Repository Audit

> **SUPERSEDED — migration complete.** This was the Phase 0 origin audit
> that justified overriding the then-current CLAUDE.md (which forbade React/
> Inertia and mandated Livewire). The migration it proposed has since been
> fully executed through Phase 11, CLAUDE.md has been rewritten to describe
> the current Inertia+React architecture, and Livewire has been entirely
> removed. Kept as historical record only — see
> `docs/inertia-react-migration-status.md` for execution history and
> `docs/phase-11-final-verification.md` for final verification.

Status: ~~Phase 0 — audit only. No code changed.~~ **All phases complete.**
Scope (at the time): read-only inspection of the Laravel project and the
PreSkool v1.9.7 React theme source, to ground the migration plan in facts
rather than assumptions.

---

## 1. Git repository state (safety-critical)

`git status` at the start of this audit showed the working tree **already
dirty**, independent of this migration request:

**Modified (tracked), ~45 files** — the bulk of this is an in-progress
PostgreSQL performance-optimization pass (per `PERFORMANCE_AUDIT.md`,
`PERFORMANCE_OPTIMIZATION_REPORT.md`, `POSTGRES_*.md`):
- `composer.json` / `composer.lock`, `config/database.php`, `config/queue.php`
- Two migrations (`activity_log`, `media` tables)
- `database/seeders/DemoDataSeeder.php`
- 12 Livewire index classes (`CaisseTransfersIndex`, `CaissesIndex`,
  `DepensesIndex`, `EmployeesIndex`, `EncaissementsIndex`, `GroupsIndex`,
  `InscriptionsIndex`, `RemboursementsIndex`, `RolesIndex`, `StudentsIndex`,
  `TypesDepensesIndex`, `UsersIndex`) and their matching blade views, plus 4
  Settings tabs — consistent with per-page-size / query-count optimization
- `resources/js/backoffice/app.js`, `select2.blade.php`, `pagination.blade.php`
- `public/assets/crm-gls/js/script.js` (the documented Select2 double-init
  fix from CLAUDE.md §7)
- 4 test files under `Finance/` and `Inscriptions/`
- Docs: `README.md`, `.env.example`, `phpunit.xml`, `gls-crm-schema.md`,
  `gls-crm-laravel-structure.md`, `docs/authorization-audit.md`,
  `docs/backoffice-architecture.md`, `skills/architecture-reviewer/SKILL.md`,
  `PERFORMANCE_AUDIT.md`, `PERFORMANCE_OPTIMIZATION_REPORT.md`

**Untracked, new files**:
- `POSTGRES_AUDIT.md`, `POSTGRES_DOCUMENTATION_UPDATE_REPORT.md`,
  `POSTGRES_MIGRATION_REPORT.md`, `PROJECT_INVENTORY.md`
- `app/Livewire/Backoffice/Concerns/WithPerPage.php`
- `database/migrations/2026_07_29_120000_add_missing_foreign_key_indexes_for_postgres.php`
- `resources/views/components/backoffice/ui/filter-bar.blade.php` (+ `filter-bar/date-field.blade.php`)
- `resources/views/components/backoffice/ui/per-page-select.blade.php`
- `resources/views/vendor/pagination/backoffice-links.blade.php`

**Branch**: `main` only, tracking `origin/main`
(`https://github.com/Rochdi7/CRM-GLS.git`). No other local or remote branches
exist yet.

**Recent history** (`git log --oneline`, newest first):
```
e85ab57 optimize project livewire make it more faster on reload !
cfcac95 Replace Laravel boilerplate README with project README
7709fb6 Sync latest fr.json + inscriptions view edits
badb1d6 Merge GitHub initial commit (keep project README)
2d3eb2d Import GLS CRM — Laravel 13 school-management backoffice
ddaa741 Initial commit
```

### ⚠ Action required before any migration branch/commit

None of the above changes belong to this migration — they are pre-existing,
uncommitted work from a separate (Postgres performance) initiative. Per the
safety rules governing this task, **I will not commit, stash, or discard any
of it without your explicit instruction.** Before creating the
`migration/inertia-react-preskool` branch, you need to tell me one of:

1. Commit the current dirty state to `main` first (as its own commit,
   describing the Postgres/performance work), *then* branch for the
   migration from a clean tree; or
2. Branch for the migration now, carrying the dirty working tree over as
   uncommitted changes onto the new branch (git allows this — dirty files
   move with you when you `git checkout -b`), and commit them there instead;
   or
3. Something else you specify.

No branch has been created yet — this is a decision point, not a completed step.

---

## 2. Current Laravel frontend ownership

- **Livewire is the only interactivity layer.** 22 Livewire components under
  `app/Livewire/Backoffice/` (zero under `Frontoffice/`, which is an empty
  stub — see `PROJECT_INVENTORY.md` §2 and §4 for the full module-by-module
  list already generated for this repo).
- **Alpine.js ships bundled with Livewire 4** — there is no standalone Alpine
  install, no `Alpine.start()`, no CDN script (CLAUDE.md §6 forbids adding
  one; grep confirms zero matches outside vendor).
- **Blade** anonymous components are the only component convention
  (`resources/views/components/{backoffice,frontoffice,shared}/`).
- **jQuery + Bootstrap 5 bundle + Select2 + slimscroll** are loaded as
  classic `<script>` tags from
  `resources/views/components/backoffice/layout/scripts.blade.php` — these
  are **not** ES modules and are **not** managed by Vite. Vite
  (`vite.config.js`) manages only `resources/js/{backoffice,frontoffice}/app.js`
  and the two SCSS entrypoints; PreSkool's own JS/CSS/img/fonts are served as
  static prebuilt files from `public/assets/crm-gls/`.
- `resources/js/backoffice/app.js` exposes `initializeBackofficePlugins()`,
  which (re-)initializes all jQuery plugins on both `DOMContentLoaded` and
  Livewire's `livewire:navigated` event — this is the mechanism that makes
  Select2/daterangepicker/etc. survive Livewire's DOM swaps today. **An
  Inertia migration removes Livewire's DOM-swap model entirely** — this
  reinitialization pattern will need an Inertia-native replacement
  (`router.on('navigate')` or per-page `useEffect`), not a port.
- **No API layer exists.** No `routes/api.php` usage found in
  `bootstrap/app.php` (only `web`, `commands`, `health` registered). All data
  currently flows through Blade view composition + Livewire's own wire
  protocol — there is no REST/JSON contract for React to consume today.

## 3. Current Blade layout hierarchy

- `<x-backoffice.layout.app>` — the admin shell (header + sidebar + footer +
  theme-settings offcanvas), used by essentially every Backoffice page.
- `<x-backoffice.layout.guest>` — auth/error pages (login, password reset).
- `<x-backoffice.layout.print>` — printable views.
- Supporting layout partials: `page-header`, `breadcrumbs`, `head`, `header`,
  `sidebar`, `footer`, `theme-settings`, `toasts`, `scripts`.
- `<x-frontoffice.layout.*>` exists (`app`, `guest`, `header`, `footer`) but
  is barely used — only `/home` renders through it today.

## 4. Current JavaScript initialization model

- Static jQuery-based plugins loaded once per full page (or Livewire
  `livewire:navigated` event), see §2 above.
- `resources/js/backoffice/theme.js` owns dark-mode / sidebar-color / layout
  persistence via `localStorage`, wired to
  `<x-backoffice.layout.theme-settings />`.
- `wire:ignore` is used surgically where a jQuery plugin (e.g. Select2) must
  own DOM inside a Livewire-managed component, with a documented double-init
  fix (CLAUDE.md §7) already applied to `public/assets/crm-gls/js/script.js`.

## 5. Current PreSkool HTML/static assets

- `public/assets/crm-gls/{css,js,img,fonts,icons,plugins}` — vendor,
  prebuilt, treated as read-only (copied from the theme's own `public/build`).
- `resources/views/theme-reference/crm-gls/` — 252 **permanent** reference
  Blade copies of the original HTML theme pages (CLAUDE.md §3). These are
  **never deleted, never edited, never routed to** — they exist purely as a
  copy-source for building real pages. This convention is orthogonal to the
  React migration: the React theme is a *different* source of the same
  design system, but the same "never touch the reference copy, adapt into a
  real page" discipline should carry over (see plan doc).

---

## 6. React theme (PreSkool v1.9.7, React variant) — findings

Source: `C:\Users\ASUS\Downloads\themeforest-jeUxtzLq-preskool-bootstrap-admin-html-template\preskool-v1.9.7\react`

### 6.1 Shape and scale

- `package.json` name is generic (`"template-new"`), version `0.0.0` — this
  is a raw, un-customized template export, not a GLS-specific build.
- **5,281 files** under `src/`, of which:
  - 417 `.tsx`, 3 `.ts`
  - 67 `.scss`, 32 `.css`
  - **4,694 `.eps`** — Illustrator/vector *source* files for the Tabler icon
    set. These are print/design-source assets, never consumed at runtime by
    any web build. **Must not be copied.**
  - Web font files: 17 `.woff`, 12 `.ttf`, 11 `.woff2`, 6 `.eot` (46 total,
    across multiple icon-font families — see §6.6).
- Top-level `src/` layout:
  ```
  src/
  ├── core/
  │   ├── common/       (header, sidebar, dataTable, loader, theme-settings,
  │   │                  selectoption, imageWithBasePath, Taginput)
  │   ├── data/         (json/ — ~150+ mock-data files; redux/ — store + slices;
  │   │                  interface/ — TS types)
  │   └── modals/        (10 standalone Bootstrap-modal components)
  ├── feature-module/
  │   ├── academic, accounts, announcements, application, auth, content,
  │   │   hrm, mainMenu, management, membership, pages, peoples, report,
  │   │   settings, support, uiInterface, userManagement
  │   └── router/        (all_routes.tsx, router.link.tsx, router.tsx)
  ├── style/             (css, fonts, icon, scss)
  ├── types/
  ├── environment.tsx
  ├── main.tsx
  └── index.scss
  ```

### 6.2 App entry point (`src/main.tsx`)

```tsx
createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <Provider store={store}>
      <BrowserRouter basename={base_path}>
        <ALLRoutes />
      </BrowserRouter>
    </Provider>
  </React.StrictMode>
)
```
Imports directly in this file: Bootstrap CSS **and** Bootstrap's JS bundle
from `node_modules`, plus 7 separate icon-font CSS files (feather, boxicons,
weather-icons, typicons, fontawesome ×2, ionicons, tabler-icons), a Redux
store, and the full React Router tree. **This entire entry point is
discarded** — Inertia supplies its own entry (`createInertiaApp`), and none
of `BrowserRouter`/`react-router`/`react-redux`/`@reduxjs/toolkit` are
compatible with an Inertia-driven app (Inertia owns navigation; Laravel
session + shared props replace the Redux global-state role this theme uses
it for, per the audited `themeSettingSlice.tsx`).

`index.html` is a plain SPA shell (`<div id="root">` + one module script) —
not reusable directly, but its `<head>` shows no other surprises (single
favicon link, no inline scripts).

### 6.3 Routing

- `src/feature-module/router/{all_routes.tsx, router.link.tsx, router.tsx}`
  define the entire React Router v7 route table (a huge flat object of
  ~200+ route path constants + `<Route>` tree) for the whole demo app
  (CRM + school + HR + support-desk kitchen sink — this is a multi-vertical
  admin template, not a school-only build).
- **All of `router/` must be replaced.** Under Inertia, Laravel's
  `routes/backoffice.php` remains the single routing authority; React pages
  are resolved by component name via `resolvePageComponent` in `app.tsx`,
  and navigation uses `<Link>`/`router.visit` from `@inertiajs/react`, not
  `react-router`/`react-router-dom`.

### 6.4 Authentication pages

`src/feature-module/auth/` contains: `login` (+ 2 style variants),
`register` (+2), `forgotPassword` (+2), `resetPassword` (+2),
`resetPasswordSuccess` (+2), `emailVerification` (+2), `twoStepVerification`
(+2), `lockScreen`. Each "-2"/"-3" suffix is a purely cosmetic layout variant
(same demo template, alternate background/card styling) — **only one variant
per flow needs adapting** (pick one, discard the other two).

`login.tsx` uses `localStorage` directly (client-side "remember me"/demo
auth flag) — **this must not be ported**; Laravel's existing session-based
`web` guard, `LoginRequest` (rate limiting, email-or-username, is_active
gate) and CSRF stay authoritative, per the non-negotiable rules of this
migration. `register` and `email-verification`/`two-step-verification` are
**not applicable** at all — GLS CRM has no public registration
(Employees auto-provision logins; CLAUDE.md §11/§15) and no 2FA — these
theme pages are demo-only and should not be migrated as functional flows.

### 6.5 Reusable layout components

- `src/core/common/header/index.tsx` and `sidebar/index.tsx` — both use
  `localStorage` (theme/sidebar-state persistence, mirrors the existing
  `resources/js/backoffice/theme.js` role). Reusable as visual/structural
  reference; internal state wiring must be redone against Inertia shared
  props + the existing `theme.js` persistence approach, not copied verbatim.
- `src/core/common/theme-settings/` — layout/dark-mode/sidebar-color settings
  panel; direct visual/structural analog of the current
  `<x-backoffice.layout.theme-settings />` + `theme.js`.
- `src/core/common/dataTable/index.tsx` — a single generic wrapper (likely
  around `antd`'s `Table` or a custom sortable table, needs closer read
  before Phase 5+) — **do not adopt client-side pagination/sorting/filtering
  from this component for real CRM lists**; per CLAUDE.md's DataTables rule
  and this migration's explicit performance requirements, large lists must
  stay server-driven (Inertia partial reloads + Laravel pagination), so this
  component is at most a **visual** reference for table markup/styling, not
  a functional component to reuse as-is.
- `src/core/common/selectoption/selectoption.tsx` — wraps `react-select`
  (theme dependency, see §6.7) for dropdown UI — candidate replacement for
  Select2, needs a deliberate decision (see Dependency comparison).
- `src/core/common/loader/`, `imageWithBasePath/`, `Taginput/` — small,
  low-risk, generically reusable presentational components.
- `src/core/modals/*.tsx` (10 files) — each is a **fully wired, specific**
  modal (e.g. `banIpaddressModal`, `bank_accounts`, `citiesModal`,
  `contactStageModal`, `countriesModal`/`countries_modal` duplicate,
  `customFieldModals`, `dealReportModal`, `filter_modal`, `todoModal`) —
  **none of these map to a GLS CRM modal directly**; they're all demo-domain
  (CRM contacts/deals, IP banning, custom fields). Reusable only as a
  **structural pattern** for "how does this theme wire a controlled Bootstrap
  modal in React" — not copy-paste candidates. Actual GLS modals (Student
  add/edit, Employee add/edit, Inscription fee lines, Group fee assignment,
  Caisse transfer request/validate, etc.) must be built fresh against real
  Inertia forms, following the pattern this reference establishes.

### 6.6 Mock data / fake APIs

- `src/core/data/json/` — **~150+ files** of hardcoded demo data
  (`academic_reason.tsx`, `accounts_income_data.tsx`, `feesData.tsx`,
  `feesMaster.tsx`, `feesType.tsx`, `collectFees.tsx`, `class-*.tsx`,
  `exam*.tsx`, `attendance.tsx`, `contactData.tsx`, `dealsData.tsx`, dozens
  more spanning every vertical the template demos). **None of this is
  reusable** — it's the theme's fake in-memory dataset standing in for a
  real backend. Every GLS page must source its data from Inertia props
  populated by the existing Laravel controllers/Livewire-equivalent
  page-controllers, never from these files.
- `src/core/data/redux/` — `store.tsx` (root store) +
  `themeSettingSlice.tsx` (the one slice confirmed to touch `localStorage`,
  for theme/appearance state only — not app data). No evidence (from this
  pass) of Redux holding *business* data beyond UI/theme state, but the full
  slice list needs a follow-up grep before Phase 2 to be certain nothing
  else leans on `core/data/json/*` through Redux at runtime.
- No real API/service layer exists in the theme (no `fetch`/`axios` service
  modules were surfaced by this pass) — it's a pure client-side mock, not an
  API-driven demo. This *simplifies* the migration in one sense (nothing to
  "rip out" at the network layer) but confirms **zero** of the theme's data
  wiring can be trusted; every page's data flow must be rebuilt against real
  Inertia props from day one.

### 6.7 Dependency inventory (full `package.json`)

**Runtime `dependencies`** (8): `@hello-pangea/dnd`, `overlayscrollbars`,
`overlayscrollbars-react`, `react ^19.2`, `react-apexcharts`,
`react-perfect-scrollbar`, `simplebar`, `simplebar-react`.

**`devDependencies`** (declared as dev, but several are load-bearing at
runtime in this template — theme's own dependency hygiene is inconsistent,
treat the dev/runtime split as unreliable and classify by actual usage, not
by which package.json bucket the theme author put it in): `@ant-design/icons`,
`@fortawesome/*` (3 packages), `@fullcalendar/*` (4 packages),
`@react-latest-ui/react-sticky-notes`, `@reduxjs/toolkit`, `antd`,
`apexcharts`, `bootstrap ^5.3.8`, `clipboard-copy`, `dragula`, `jquery
^3.7.1`, `moment`, `primeicons`, `primereact`, `quill`,
`react-awesome-stars-rating`, `react-bootstrap`,
`react-bootstrap-daterangepicker`, `react-country-flag`, `react-countup`,
`react-datepicker`, `react-dom ^19.2`, `react-feather`, `react-icons`,
`react-input-mask`, `react-redux`, `react-router ^7.9.6`,
`react-router-dom ^7.9.6`, `react-select`, `react-simple-wysiwyg`,
`react-slick`, `react-tag-input`, `sass-embedded`, `slick-carousel`,
`sweetalert2`, `sweetalert2-react-content`, `weather-icons-react`,
`web-vitals`, `yet-another-react-lightbox`, plus tooling
(`typescript ~5.9.3`, `eslint` + plugins, `@vitejs/plugin-react`,
`vite ^6.3.5`, `@types/*`).

**This is a "kitchen sink" template bundle** — Ant Design *and* PrimeReact
*and* React Bootstrap *and* raw Bootstrap JS all present simultaneously,
three separate icon-font systems plus two React icon packages, two carousel
libraries, two rich-text/WYSIWYG-adjacent packages, jQuery *and jQuery-free*
React equivalents side by side. This is normal for a multi-vertical demo
template sold once and never meant to ship as-is; it is **not** a dependency
list to install wholesale (see Dependency Comparison in the plan doc for a
per-package required/optional/demo-only/replace/remove classification).

### 6.8 Build tooling differences vs. this Laravel project

| | Theme (`react/`) | This project |
|---|---|---|
| Vite | `^6.3.5` | `^8.0.0` (already ahead) |
| Bundler plugin | `@vitejs/plugin-react` | `laravel-vite-plugin ^3.1` (no React plugin yet) |
| TypeScript | `~5.9.3`, 3-file tsconfig split (`tsconfig.json`/`.app.json`/`.node.json`) | none configured — pure JS today |
| Entry model | `index.html` + `main.tsx`, Vite's default (non-Laravel) HTML-driven build | `laravel-vite-plugin`'s manifest-driven build, entries declared in `vite.config.js`, injected via `@vite()`/Blade |
| Dev server config | none (`vite.config.ts` is the bare default, no `server.host` override) | `server.host: '127.0.0.1'` pinned (documented Windows IPv6 fix — **must be preserved**, or the exact same Select2-breaking symptom returns for any new React dev-server usage) |

**Conclusion**: the theme's `vite.config.ts` **cannot replace** or be merged
wholesale into this project's `vite.config.js` — it targets a completely
different build model (standalone SPA vs. Laravel-manifest-driven). The
correct approach is to **add** `@vitejs/plugin-react` and a new
`resources/js/app.tsx` (or similarly named) Inertia entry to the *existing*
`vite.config.js`'s `laravel({ input: [...] })` array, keeping the
`server.host` pin and the existing Backoffice/Frontoffice SCSS/JS entries
untouched. Full detail in the migration plan doc.

### 6.9 RTL support

No RTL-specific files or `dir="rtl"` logic were surfaced in this pass within
`src/core/common` or `style/`. The current Laravel app already handles RTL
itself (Arabic locale loads `bootstrap.rtl.min.css`, layout sets `dir` from
locale — CLAUDE.md §12) — **this logic must be preserved and re-implemented
in the new React layout**, since the theme does not appear to provide it
out of the box. Flagged for a closer check in Phase 2 rather than assumed
absent.

### 6.10 `localStorage` usage — full list (theme-wide)

Only **4 files** reference `localStorage` in the entire theme source:
`core/common/header/index.tsx`, `core/common/sidebar/index.tsx`,
`core/data/redux/themeSettingSlice.tsx`, `feature-module/auth/login/login.tsx`.
The first three are legitimate theme-appearance persistence (dark
mode/sidebar state) — same *category* of thing `resources/js/backoffice/theme.js`
already does, and fine to keep doing client-side. `login.tsx`'s usage is
demo/mock-auth-adjacent and **must not** be carried into the real login page.

---

## 7. Risk register

| Risk | Detail | Mitigation |
|---|---|---|
| **Dirty working tree** | ~45 modified + 9 untracked files predate this request (Postgres perf work) | Resolved via decision point in §1 before any branch/commit |
| **CLAUDE.md contradiction** | Current rules forbid this exact migration | User confirmed override; CLAUDE.md itself needs a rewrite pass (tracked as documentation debt, not done in Phase 0) |
| **Theme is a multi-vertical kitchen-sink template** | 200+ demo routes, ~150 mock-data files, none school-CRM-specific to GLS | Treat as a **component/style source only**; every page's data/logic is rebuilt against real Inertia props, nothing is "converted" 1:1 |
| **Vite version mismatch (6 vs 8) + no React plugin today** | Theme's own Vite config is incompatible with `laravel-vite-plugin`'s model | Add `@vitejs/plugin-react` to the existing config; do not import the theme's `vite.config.ts` |
| **No TypeScript configured yet in this project** | Theme is fully TS; this repo is plain JS | New `tsconfig.json` needed, scoped to `resources/js/**`; does not affect PHP or existing JS |
| **Redux + React Router are structurally incompatible with Inertia's navigation/state model** | Theme's `main.tsx` wraps everything in `<Provider>` + `<BrowserRouter>` | Both discarded at the entry point; theme-settings (dark mode etc.) re-implemented via existing `theme.js`-style localStorage, not Redux |
| **jQuery-plugin reinitialization pattern (`livewire:navigated`) has no Inertia equivalent yet** | Select2/daterangepicker/etc. currently re-init on Livewire's DOM-swap event | Needs an explicit Inertia `router.on('navigate')` (or per-page effect) replacement — flagged for Phase 2, not solved by this audit |
| **Duplicate Bootstrap JS risk** | Theme imports Bootstratp bundle from `node_modules`; current app loads it as a static `<script>` from `public/assets/crm-gls/js/` | Exactly one Bootstrap JS instance must remain once React pages mount — needs an explicit decision in Phase 2 (see plan doc) |
| **Duplicate icon-font risk** | Theme entry imports 7 separate icon-font CSS files; current app already serves Feather/Boxicons/etc. as static PreSkool assets | Reuse the **existing static font assets**, do not double-import from `node_modules` per theme page |
| **Financial-module conversion risk** | Highest business risk in the whole migration | Explicitly scheduled **last** (Phase 9), after every non-financial module is converted and tested; Domain actions untouched throughout |
| **Money records / Group deletion invariants** | Must never regress during UI rewrite | No delete route/button may be introduced for Encaissements/Depenses/Remboursements/CaisseTransfers/Groups at any phase — enforced by keeping existing routes as the only source of truth |
| **Center-scoping / permission regressions** | Livewire `mount()`-time authorization must carry over to Inertia controllers | Every converted controller must re-assert `authorize()`/policy calls that the corresponding Livewire component currently performs in `mount()` and each mutation method |

---

## 8. Documentation debt (not resolved by this audit)

- `CLAUDE.md` still states React/Inertia/Next.js are forbidden and Livewire
  is the only interactivity layer — contradicts this approved migration.
  Per the migration plan, `CLAUDE.md` should be rewritten **as part of
  Phase 1**, once the Inertia foundation is real, not before — editing the
  rules to describe a stack that doesn't exist yet would make the file
  actively misleading in the gap. Until then, treat this audit + the
  approved override as the authoritative exception.
- `PROJECT_INVENTORY.md` (already in the repo, untracked) will need a
  "Frontend: Inertia+React (migrating)" note once Phase 1 lands.
