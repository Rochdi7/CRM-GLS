# Inertia + React Migration — Plan

> **SUPERSEDED — migration complete.** This plan was fully executed through
> Phase 10 (Finance) and Phase 11 (Livewire removal + cleanup); Livewire has
> been entirely removed from the codebase. Kept as historical record of the
> original decision process — see `docs/inertia-react-migration-status.md`
> for the phase-by-phase execution log and
> `docs/phase-11-final-verification.md` for final verification that the
> migration is complete. Do not treat any "Livewire is not removed until…"
> or "both stacks coexist" language below as describing current-state code.

Status: ~~Phase 0 — plan proposed, awaiting explicit approval to implement.~~
**All phases complete.**
Companion to `docs/inertia-react-migration-audit.md` (also superseded — read
both as history, not as a description of the current architecture).

---

## 1. Baseline (recorded before any change)

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | **298/298 passed, 1070 assertions, ~299s** |
| `npm run build` (current Blade/Livewire frontend) | **Succeeds** — 1.18s, outputs `app-*.css`/`app-*.js` for backoffice+frontoffice entries, manifest at `public/build/manifest.json` |
| Git working tree | **Dirty** — pre-existing Postgres/performance work, unrelated to this migration (see audit §1). Unresolved decision point before branching. |
| Known pre-existing issues | None surfaced — full green baseline. Any test failure after this point is attributable to the migration, not pre-existing debt. |

## 2. Dependency comparison and classification

### 2.1 Composer (backend)

| Package | Action | Reason |
|---|---|---|
| `inertiajs/inertia-laravel` | **add**, `^3.1` | Confirmed compatible with `laravel/framework ^13.8` (requires `^11\|^12\|^13`) |
| Everything currently in `composer.json` | **keep, unchanged** | Livewire, Spatie packages, etc. all stay — Livewire is not removed until Phase 10, and only if nothing still depends on it |

No other backend package changes are anticipated for Phase 1.

### 2.2 NPM — classification of every theme dependency

Legend: **required** (needed for the migrated pages to function/look
correct) · **optional** (nice-to-have, defer until a page actually needs it)
· **demo-only** (theme-demo-specific, not applicable to GLS CRM) ·
**replace** (theme's version conflicts with an existing/soon-to-exist
project choice) · **remove** (actively unwanted per the forbidden-stack
rules or duplicate tooling).

| Package | Classification | Reason |
|---|---|---|
| `@inertiajs/react` | **add (required)** | Not in theme; the actual migration target |
| `react`, `react-dom` | **required** | Core; use theme's `^19.2` unless it conflicts with `@inertiajs/react`'s peer range (verify at install time) |
| `typescript` | **required** | Project has none yet; needed for `.tsx` pages |
| `@vitejs/plugin-react` | **required** | Needed to let the existing `laravel-vite-plugin` config also build React/JSX |
| `bootstrap` | **required, reuse existing** | Do **not** install the theme's `bootstrap ^5.3.8` npm copy — this project already serves Bootstrap 5 as a static PreSkool asset (`public/assets/crm-gls/js/bootstrap.bundle.min.js` + CSS). Installing a second copy via npm creates the exact "duplicate Bootstrap bundle" risk flagged in the audit. Keep using the static asset; React components target its already-loaded global `bootstrap` JS API (or use `react-bootstrap` — see below — as a deliberate, singular choice) |
| `react-bootstrap` | **decision needed (see §2.3)** | Candidate way to get accessible, React-owned Bootstrap components (modals, dropdowns) without fighting Bootstrap's own DOM-scanning JS. Mutually exclusive with hand-rolling controlled modals against the static Bootstrap JS bundle — pick one strategy, do not mix |
| `jquery` | **remove (do not add via npm)** | This project already loads jQuery as a static script for the *existing* Livewire/Blade pages, which keep running throughout the migration. React pages must not depend on jQuery at all — if a React page needs Select2-equivalent behavior, use `react-select` (already in the theme, see below) instead of wiring jQuery Select2 into React |
| `react-select` | **optional, likely required later** | Best candidate to replace Select2 *inside React pages only*. Defer actual adoption until a converted CRUD page needs a searchable dropdown (Phase 6+) — do not install speculatively in Phase 1 |
| `antd` (Ant Design) | **remove** | Full second component/design system; conflicts with "do not redesign the PreSkool look" and "no second UI framework" rules. The theme only uses it for icons/specific widgets in some demo pages — none of which are GLS modules |
| `primereact`, `primeicons` | **remove** | Same reasoning as Ant Design — a third component system. Not needed |
| `@reduxjs/toolkit`, `react-redux` | **remove** | Global client state manager for a client-rendered SPA; Inertia + Laravel session + shared props replace this role. The one legitimate use (theme-settings persistence) is already solved by this project's existing `resources/js/backoffice/theme.js` (localStorage-based, no Redux) |
| `react-router`, `react-router-dom` | **remove** | Explicitly replaced by Inertia navigation per this migration's routing rules |
| `@fortawesome/*` (3 packages), `react-feather`, `react-icons`, `weather-icons-react`, `primeicons` | **mostly remove, keep at most one** | This project already serves Feather/Boxicons/Fontawesome/etc. as static PreSkool icon-font CSS (`public/assets/crm-gls/icons/`). Re-importing icon packages via npm risks duplicate icon-font loading (flagged in audit). Prefer reusing the existing static icon fonts (plain `<i class="...">` markup, same as current Blade views) over installing a React icon-component package, unless a specific page genuinely needs SVG-as-component icons |
| `@fullcalendar/*` (4 packages) | **demo-only for now** | No calendar/attendance-calendar module exists yet in this project (Attendance domain is a reserved placeholder per `PROJECT_INVENTORY.md` §16). Do not install until that module is actually built |
| `@ant-design/icons` | **remove** | Tied to Ant Design, itself removed |
| `@react-latest-ui/react-sticky-notes` | **demo-only** | Not a GLS feature |
| `apexcharts`, `react-apexcharts` | **optional** | Candidate for `Dashboard\DashboardStats` if/when it needs richer charts than the current stat cards; not required for the pilot or early phases |
| `moment` | **remove** | The current project's CLAUDE.md-documented static assets already ship `moment` for daterangepicker (unused, per script.js comments) — do not add a second copy via npm. If a React page needs date formatting, prefer native `Intl.DateTimeFormat` or a small dedicated library, decided per-page, not globally |
| `react-datepicker`, `react-bootstrap-daterangepicker` | **optional** | Only if/when a converted page needs a date-range picker; current pages use the static `bootstrap-datetimepicker`/`daterangepicker` jQuery plugins (per CLAUDE.md §7) — for React pages, pick one React-native equivalent when actually needed, do not install both |
| `dragula`, `@hello-pangea/dnd` | **demo-only** | No drag-and-drop feature exists in any current GLS module |
| `react-slick`, `slick-carousel` | **demo-only** | No carousel usage anywhere in this admin CRM |
| `quill`, `react-simple-wysiwyg` | **demo-only** | No rich-text-editing field exists in any current form |
| `react-input-mask` | **optional** | Could help with the existing phone-input component (`components/backoffice/forms/phone-input.blade.php` has a Blade equivalent already) — only if/when that field is rebuilt in React |
| `react-tag-input` | **optional** | Only relevant if `tags-input.blade.php`'s functionality is rebuilt in React — not needed for early phases |
| `react-country-flag` | **optional** | Cosmetic addition to the existing `Phone\Countries` country picker if desired later — not required |
| `sweetalert2`, `sweetalert2-react-content` | **optional** | Could replace Blade's `<x-backoffice.ui.alert>`/toast pattern for React pages; decide once the first React page needs a confirmation dialog (e.g. group archive) — not required for the pilot |
| `react-countup` | **demo-only** | Cosmetic dashboard-counter animation, not required |
| `overlayscrollbars`, `overlayscrollbars-react`, `simplebar`, `simplebar-react`, `react-perfect-scrollbar` | **remove, pick none initially** | Three competing custom-scrollbar libraries in the theme itself; the current sidebar already works with native scrolling + the static `slimscroll` plugin. Do not introduce any of these until a specific layout problem justifies it |
| `yet-another-react-lightbox` | **demo-only** | No image-gallery/lightbox feature exists |
| `clipboard-copy` | **optional** | Trivial, low-risk if a "copy reference code" button is wanted later; not required |
| `react-awesome-stars-rating` | **demo-only** | No rating feature exists |
| `web-vitals` | **optional** | Only relevant if/when adding frontend performance measurement — ties into this migration's own "measure before/after" requirement, worth considering in Phase 10, not earlier |
| `eslint` + plugins, `globals`, `typescript-eslint` | **required (dev)** | Needed to lint the new `.tsx` code; theme's config is a reasonable starting point but must be scoped to `resources/js/**`, not `src/**` |
| `sass-embedded` | **replace** | Project already uses `sass ^1.80.0` (Dart Sass) as a devDependency — do not add a second Sass implementation; keep the existing one |
| `vite` | **do not touch** | Project already has `vite ^8.0.0`, ahead of the theme's `^6.3.5`. Keep the project's version; only add the React plugin to it |

**Net effect**: of ~46 theme dependencies, roughly **6 are added**
(`@inertiajs/react`, `react`, `react-dom`, `typescript`, `@vitejs/plugin-react`,
plus dev-only lint tooling), **1 backend package added**
(`inertiajs/inertia-laravel`), and the remaining ~40 are deferred,
substituted with existing project assets, or rejected outright. This keeps
`package.json` additions minimal and reviewable, per the "merge only
required dependencies" rule.

### 2.3 Open decision: modal/component strategy

Before Phase 2, one architectural choice needs your sign-off (this is a
proposal, not a decision made unilaterally):

**Recommended**: adopt `react-bootstrap` for interactive Bootstrap primitives
(Modal, Dropdown, Offcanvas) that the theme's own components lean on, since
it wraps the *same* Bootstrap 5 CSS this project already uses, without
requiring Bootstrap's jQuery-free-but-DOM-scanning JS bundle to coexist with
React's virtual DOM. The static Bootstrap CSS keeps being served as today;
only its *JS* behavior (modal open/close, dropdown toggle) moves to
`react-bootstrap`'s React-owned lifecycle for React-rendered pages only.
Blade/Livewire pages continue using the static Bootstrap JS bundle exactly
as they do now — the two coexist because they own disjoint sets of pages
during the transition.

---

## 3. Target directory structure

As specified in your instructions, with one addition (`Pages/Backoffice/*`
mapped 1:1 against the 22 existing Livewire modules from
`PROJECT_INVENTORY.md` §4, so nothing gets lost in translation):

```
resources/js/
├── app.tsx                      ← Inertia entry (createInertiaApp)
├── bootstrap.ts                 ← axios/csrf-equivalent setup if needed
├── Pages/
│   ├── Backoffice/
│   │   ├── Auth/                ← Login, ForgotPassword, ResetPassword
│   │   ├── Dashboard/
│   │   ├── Students/
│   │   ├── Employees/
│   │   ├── Inscriptions/
│   │   ├── Groups/
│   │   │   └── Historique/
│   │   ├── Finance/
│   │   │   ├── Caisses/         ← incl. CaisseJournal
│   │   │   ├── Encaissements/
│   │   │   ├── Depenses/
│   │   │   ├── Remboursements/
│   │   │   ├── Transfers/       ← CaisseTransfers
│   │   │   └── TypesDepenses/
│   │   ├── Settings/            ← Etablissements/AnneesScolaires/Salles/Frais tabs
│   │   ├── Users/                ← index + ManageAuthorization
│   │   ├── Roles/                ← index + RoleForm
│   │   ├── Permissions/          ← read-only
│   │   └── Profile/
│   └── Frontoffice/               ← empty until Frontoffice work resumes
├── Layouts/
│   ├── BackofficeLayout.tsx
│   ├── GuestLayout.tsx
│   └── FrontofficeLayout.tsx
├── Components/
│   ├── Theme/                    ← header, sidebar, theme-settings, footer
│   ├── Forms/                    ← input, select, textarea, error, phone
│   ├── Tables/                   ← server-paginated table wrapper
│   ├── Modals/                   ← controlled modal wrapper(s)
│   ├── Navigation/                ← breadcrumbs, pagination, per-page-select
│   ├── Feedback/                  ← alerts, badges, flash/toast
│   └── Shared/
├── Hooks/
├── Types/
├── Utils/
├── Services/
└── styles/                        ← only if SCSS is reorganized; otherwise
                                      keep using resources/scss/backoffice/
```

`resources/scss/backoffice/app.scss` stays the SCSS entry (no need to
duplicate under `resources/js/styles/` unless a real reason emerges).

---

## 4. Inertia architecture (Phase 1 detail)

- **New Blade root**: `resources/views/app.blade.php` — HTML shell,
  `@vite(['resources/js/app.tsx'])`, `@inertiaHead`, `@inertia`, CSRF meta
  tag. Deliberately excludes `<x-backoffice.layout.scripts />` and any
  Livewire directive — this view is Inertia-only.
- **Middleware**: `Inertia\Middleware\HandleInertiaRequests`, registered in
  `bootstrap/app.php`'s `withMiddleware()`. Shared props limited to:
  authenticated user (id, name, minimal display fields — not the full model),
  current `CenterAccessService`-resolved center + `CurrentContext`
  (year/center), permission list (flat array of `can()` strings the frontend
  needs, not the full Spatie permission objects), flash messages, validation
  errors (Inertia's default), app locale. No full Eloquent models, no
  sensitive fields (password hashes, tokens) ever shared globally.
- **Vite config change**: add `'resources/js/app.tsx'` to the existing
  `laravel({ input: [...] })` array in `vite.config.js` and add
  `react()` to the `plugins` array — the existing four entries
  (backoffice/frontoffice SCSS+JS) and the `server.host` pin stay untouched.
- Livewire is **not removed** in Phase 1 — both stacks coexist; Inertia
  pages simply don't yet exist for anything but the pilot.

---

## 5. Migration order (adopting your phase list, unchanged in substance)

Phase 0 (this audit) → 1 (Inertia foundation) → 2 (PreSkool React shell) →
3 (Auth/profile) → 4 (Dashboard/context) → 5 (read-heavy: permissions,
groups historique, show pages) → 6 (simple CRUD: types-depenses,
etablissements, annees-scolaires, salles, frais) → 7 (people: students,
employees, users, roles, authorization) → 8 (academic: groups, inscriptions,
inscription-fees) → 9 (finance: caisses, journal, encaissements, depenses,
remboursements, transfers) → 10 (cleanup, only after everything above is
converted and tested).

No changes to this ordering are proposed — it correctly sequences
lowest-risk-first and defers finance to last, matching this project's own
"money records are the highest-scrutiny surface" posture already reflected
in CLAUDE.md §11.

---

## 6. Pilot module recommendation

**Recommended pilot: Permissions read-only page**
(`backoffice.permissions.index` → `PermissionController`).

Reasons:
- Zero mutation risk — it's read-only Blade today (per
  `PROJECT_INVENTORY.md` §7/§4), so there is no form, no validation, no
  Domain action, no money, no center-scoping edge case to get wrong on the
  very first attempt.
- Still exercises the full real stack end-to-end: real permission-gated
  route (`permission:permissions.view`), real controller returning real
  data, real `HandleInertiaRequests` shared props (permission list needs to
  reach the sidebar/nav for *every* future page anyway), real
  `BackofficeLayout.tsx` (header/sidebar/breadcrumbs) rendering for the
  first time.
- Small enough to fully verify (build success, no console errors, no
  Livewire regression elsewhere, correct permission gate) before touching
  anything with a form or a financial consequence.

Dashboard shell was considered but rejected as the pilot because
`Dashboard\DashboardStats` already listens to `context-changed` and pulls
center/year-scoped aggregates — more moving parts than needed for a first
proof-of-concept. It remains next, in Phase 4, once the shell is proven.

---

## 7. Rollback strategy

- All Phase 1+ work happens on `migration/inertia-react-preskool` (branch
  not yet created — blocked on the git-state decision in the audit doc §1).
- A tagged/identified commit marks the pre-migration baseline (298/298
  tests, working `npm run build`) before any Inertia file is added.
- Because Livewire/Blade pages are **not touched or removed** until Phase
  10, rollback for any single phase is: `git checkout main` — no page a
  real user depends on is ever mid-migration-broken, since the old route
  keeps serving the old Livewire component until its Inertia replacement is
  merged and verified.
- Per-phase rollback: each phase is its own small commit/PR; reverting one
  phase's commit removes only that phase's new files/route changes, since
  earlier phases' pages are already independently verified and phases don't
  rewrite each other's files.
- `composer.json`/`package.json` changes are additive only in every phase
  except Phase 10 (removal) — reverting a phase never has to "un-remove" a
  dependency another still-active page needs.

---

## 8. Exact files planned for Phase 1 (proposal — not yet created)

1. `composer.json` — add `inertiajs/inertia-laravel` (via `composer require`,
   not hand-edited)
2. `package.json` — add `@inertiajs/react`, `react`, `react-dom`,
   `@vitejs/plugin-react`, `typescript` (+ minimal `@types/react`,
   `@types/react-dom`) via `npm install`, reviewed before commit
3. `bootstrap/app.php` — register `HandleInertiaRequests` middleware
4. `app/Http/Middleware/HandleInertiaRequests.php` — new, defines shared props
5. `resources/views/app.blade.php` — new Inertia root view
6. `resources/js/app.tsx` — new Inertia entry (`createInertiaApp`)
7. `resources/js/Pages/Backoffice/Permissions/Index.tsx` — pilot page
8. `resources/js/Layouts/BackofficeLayout.tsx` — minimal first cut (can be
   built out further in Phase 2)
9. `tsconfig.json` — new, scoped to `resources/js/**`
10. `vite.config.js` — modified: add `react()` plugin + new entry
11. `app/Http/Controllers/Backoffice/PermissionController.php` — modified:
    return `Inertia::render(...)` instead of a Blade view, authorization
    logic unchanged
12. `routes/backoffice.php` — unchanged (same route name/URI/middleware,
    only the controller's return type changes)

## 9. Exact commands planned for Phase 1 (proposal — shown, not yet run)

```powershell
# Backend
C:\php84\php.exe C:\composer\composer.phar require inertiajs/inertia-laravel

# Frontend (reviewed against package.json before commit)
npm install @inertiajs/react react react-dom
npm install -D @vitejs/plugin-react typescript @types/react @types/react-dom

# Scaffolding (Laravel's own installer, if the installed version provides one)
C:\php84\php.exe artisan inertia:middleware   # or manual file creation if unavailable

# Verification
npm run build
C:\php84\php.exe artisan route:list --name=backoffice.permissions
C:\php84\php.exe artisan test --filter=Permission
```

No destructive command appears in this list. Nothing here touches
Livewire, migrations, or the database.

---

## 10. What happens after this document is approved

I will not run any of the §9 commands or create any of the §8 files until
you confirm: (a) how to handle the pre-existing dirty working tree (audit
§1), and (b) that this plan, dependency classification, and pilot choice are
approved as proposed or with your adjustments.
