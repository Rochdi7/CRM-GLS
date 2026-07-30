# PreSkool React Theme — File Map

Status: **Planning document — no files copied yet.** Records, for every
theme source area inspected during the audit, what it is, whether it's
reusable, and (once actually copied in a later phase) its destination path.
Until Phase 2 begins, the "Destination" column is a *proposal*, not a fact —
update this file at the moment each file is actually copied/adapted, per the
task instructions ("for each copied theme file, record its source and
destination").

Theme source root: `C:\Users\ASUS\Downloads\themeforest-jeUxtzLq-preskool-bootstrap-admin-html-template\preskool-v1.9.7\react`

---

## 1. Legend

- ✅ **Reuse (adapt)** — structural/visual reference worth adapting into the
  target structure. Never a raw copy: paths, imports, data-wiring, and
  routing are always rewritten for Inertia.
- 🎨 **Reference only** — useful to look at while building the real
  component, but not copied file-for-file (e.g. a demo-domain modal used
  only as a wiring pattern).
- 🚫 **Do not copy** — demo-only, mock data, incompatible architecture, or
  explicitly forbidden by the migration rules.

---

## 2. Application shell / entry

| Source | Verdict | Notes | Proposed destination |
|---|---|---|---|
| `src/main.tsx` | 🚫 | `BrowserRouter` + Redux `Provider` + direct Bootstrap/icon-font imports from `node_modules` — entirely incompatible with Inertia's `createInertiaApp` entry model | n/a — replaced by a hand-written `resources/js/app.tsx` |
| `index.html` | 🚫 | Bare Vite SPA shell (`<div id="root">` + one script tag) | n/a — replaced by `resources/views/app.blade.php` |
| `src/environment.tsx` (`base_path` etc.) | 🎨 | Check content before Phase 2; likely just a base-URL constant for React Router's `basename`, not needed once Inertia/Laravel routing is authoritative | n/a |
| `vite.config.ts` | 🚫 | Targets Vite 6 + bare React SPA build, incompatible with `laravel-vite-plugin`'s manifest model | n/a — existing `vite.config.js` is extended instead, not replaced |
| `tsconfig.json` / `tsconfig.app.json` / `tsconfig.node.json` | 🎨 | Reasonable structure reference (app vs. node split) but paths/`include` must be rewritten for `resources/js/**` | New `tsconfig.json` at project root, GLS-specific content |
| `eslint.config.js` | 🎨 | Reference only; scope must change to `resources/js/**` | New config if/when ESLint is added for the frontend |

## 3. Layout / navigation components (`src/core/common/`)

| Source | Verdict | Notes | Proposed destination |
|---|---|---|---|
| `core/common/header/index.tsx` | ✅ | Visual/structural reference for the top bar; uses `localStorage` for theme state — re-wire against existing `theme.js` persistence approach, not copied verbatim; also needs the context switcher (academic year/center) slot that this theme has no concept of | `resources/js/Components/Theme/Header.tsx` |
| `core/common/sidebar/index.tsx` | ✅ | Visual/structural reference for the nav sidebar; the full theme nav tree (200+ demo links) must be replaced with GLS's actual permission-gated module list | `resources/js/Components/Theme/Sidebar.tsx` |
| `core/common/theme-settings/` | ✅ | Direct analog of existing `<x-backoffice.layout.theme-settings />` + `theme.js` — reuse the *visual* offcanvas panel, keep the existing localStorage persistence logic/keys so dark-mode preference isn't reset for existing users | `resources/js/Components/Theme/ThemeSettings.tsx` |
| `core/common/loader/` | ✅ | Small, generic loading-spinner component; low risk | `resources/js/Components/Feedback/Loader.tsx` |
| `core/common/imageWithBasePath/` | 🎨 | Wraps images with the theme's own `base_path` — GLS already has `asset('assets/preskool/…')` and Media Library `/media/<uuid8>/…` URL conventions; do not adopt this component's path logic, only its lazy-loading/fallback pattern if useful | n/a |
| `core/common/Taginput/` | ✅ | Candidate replacement for `components/backoffice/forms/tags-input.blade.php`, only if/when that field is rebuilt in React (Phase 6+, not required for the pilot) | `resources/js/Components/Forms/TagInput.tsx` (deferred) |
| `core/common/selectoption/selectoption.tsx` | 🎨 | Wraps `react-select`; reference for how the theme integrates it, but GLS's actual Select2 replacement decision (§2.2 of the plan doc) determines the real component built | `resources/js/Components/Forms/Select.tsx` (deferred to Phase 6+) |
| `core/common/dataTable/index.tsx` | 🎨 | **Visual reference only** — do not adopt any client-side pagination/sort/filter logic from this component; every GLS list stays server-driven per CLAUDE.md's DataTables rule | `resources/js/Components/Tables/DataTable.tsx` (styling only, logic rebuilt) |

## 4. Modals (`src/core/modals/`)

All 10 files are demo-domain-specific (CRM contacts/deals, IP banning,
custom fields, cities/countries reference data unrelated to GLS's own
`Etablissement`/`Salle` referential tables). **None are copied as-is.**

| Source | Verdict | Notes |
|---|---|---|
| `banIpaddressModal.tsx`, `bank_accounts.tsx`, `citiesModal.tsx`, `contactStageModal.tsx`, `countriesModal.tsx`, `countries_modal.tsx`, `customFieldModals.tsx`, `dealReportModal.tsx`, `filter_modal.tsx`, `todoModal.tsx` | 🎨 | Reference only, for "how does this theme structure a controlled Bootstrap modal (open state, form reset on close, validation display)" — every actual GLS modal (Student add/edit, Employee add/edit, Group fee assignment, Caisse transfer request/validate, etc.) is built fresh against `useForm` + real Inertia submission, following whichever modal-lifecycle pattern is chosen per §2.3 of the plan doc |

Proposed destination for the *pattern* (not the files): a single
`resources/js/Components/Modals/FormModal.tsx` (or similar) shared wrapper,
authored fresh once the react-bootstrap-vs-hand-rolled decision is made.

## 5. Auth pages (`src/feature-module/auth/`)

| Source | Verdict | Notes | Proposed destination |
|---|---|---|---|
| `login/login.tsx` | ✅ (adapt), 🚫 (its `localStorage` demo-auth logic) | Visual reference for the card layout only; all auth logic (email-or-username, rate limiting, `is_active` gate, CSRF) stays server-side via the existing `LoginRequest` | `resources/js/Pages/Backoffice/Auth/Login.tsx` |
| `login/login-2.tsx`, `login-3.tsx` | 🚫 | Cosmetic duplicate variants of the same page — pick one style (login.tsx) and discard the others | n/a |
| `forgotPassword/forgotPassword.tsx` (+`-2`/`-3`) | ✅ / 🚫 | Same pattern as login: one variant adapted, two discarded | `resources/js/Pages/Backoffice/Auth/ForgotPassword.tsx` |
| `resetPassword/resetPassword.tsx` (+`-2`/`-3`) | ✅ / 🚫 | Same pattern | `resources/js/Pages/Backoffice/Auth/ResetPassword.tsx` |
| `resetPasswordSuccess/*` | 🎨 | Optional — current app may just flash-redirect instead of a dedicated success page; decide in Phase 3 | Deferred |
| `register/*` (3 variants) | 🚫 | GLS has **no public registration** — Employees auto-provision logins (CLAUDE.md §11/§15). Do not build this page at all | n/a |
| `emailVerification/*` (3 variants) | 🚫 | No email verification flow exists or is planned | n/a |
| `twoStepVerification/*` (3 variants) | 🚫 | No 2FA exists or is planned | n/a |
| `lockScreen.tsx` | 🚫 | Not a GLS feature | n/a |

## 6. Feature modules (`src/feature-module/`) — by directory

| Source directory | Verdict | Notes |
|---|---|---|
| `academic/` | 🎨 | Reference only for any visual patterns (class/exam pages) that might inform Groups/Inscriptions pages later — GLS's academic model (Groups, Inscriptions, InscriptionFees) is structurally different from the theme's class/exam-centric demo, so no direct reuse expected |
| `accounts/` | 🎨 | Theme's "Accounts" (invoices/income/expenses demo) is the closest visual analog to GLS Finance module — useful reference for Phase 9 table/card layouts, but all data wiring, invariants (no destroy on money records), and Domain-action calls are GLS-specific and rebuilt from scratch |
| `hrm/` | 🎨 | Reference for Employees-adjacent pages (departments, designations) — GLS's `Employee` model/CRUD is simpler (no separate department/designation entities); use only for visual inspiration |
| `peoples/` | 🎨 | Closest analog to GLS's Students/Employees pages; reference for list/detail layout only |
| `management/`, `membership/`, `content/`, `announcements/`, `application/`, `report/`, `support/`, `uiInterface/`, `userManagement/`, `mainMenu/`, `settings/`, `pages/` | 🚫 (mostly) | Multi-vertical demo content (blog, deals, campaigns, FAQ, support tickets, generic "UI kit" showcase pages) with no GLS equivalent. Skim only if a specific later phase needs a specific visual pattern (e.g. `settings/` for the Settings tabs' tab-panel UI); do not treat as a source of pages to port |
| `router/` (`all_routes.tsx`, `router.link.tsx`, `router.tsx`) | 🚫 | Entire React Router route table for the demo app — fully replaced by Laravel's `routes/backoffice.php` + Inertia page resolution |

## 7. Data / state (`src/core/data/`)

| Source | Verdict | Notes |
|---|---|---|
| `data/json/*.tsx` (~150+ files) | 🚫 | Mock/demo data for every vertical the template covers. None sources any GLS page — all GLS data comes from Inertia props populated by real controllers |
| `data/redux/store.tsx` | 🚫 | Redux removed entirely (see plan doc §2.2) |
| `data/redux/themeSettingSlice.tsx` | 🎨 | Reference only for *which* theme settings exist (layout/sidebar-color/dark-mode) — the actual persistence mechanism reuses the project's existing `theme.js` localStorage approach, not Redux |
| `data/interface/index.tsx` | 🎨 | TypeScript interfaces for the demo data shapes — not reusable (wrong domain), but useful as a style reference for how `resources/js/Types/` should be organized |

## 8. Styles (`src/style/`)

| Source | Verdict | Notes | Proposed destination |
|---|---|---|---|
| `style/scss/*` | 🎨 | Compare against the existing `resources/scss/preskool/` reference copy (already in this project, kept for reference, not compiled — CLAUDE.md §12) before importing anything, to avoid re-introducing a duplicate Bootstrap import chain | Only import specific partials actually needed, into `resources/scss/backoffice/` — never wholesale |
| `style/css/*` | 🚫 | Prebuilt/compiled CSS — this project already has the equivalent prebuilt CSS under `public/assets/preskool/css/`; do not double-serve | n/a |
| `style/icon/*` (boxicons, weathericons, typicons, fontawesome, ionicons, tabler-icons webfont) | 🚫 (as npm/import), reuse existing static copies | This project already serves these exact icon-font families as static assets (`public/assets/preskool/icons/`). Importing them again via the React entry duplicates every icon-font file. React pages should reference the **already-loaded** static icon-font classes (`<i class="ti ti-...">` etc.), same markup convention as current Blade views | n/a — no new files, reuse existing `public/assets/preskool/` |
| `style/icon/tabler-icons/eps/*` (4,694 files) | 🚫 | Illustrator source files for the icon set, never used at runtime by any web build | n/a — never copied |
| `style/fonts/*` (46 woff/woff2/ttf/eot files) | 🚫 (as new copies) | Cross-check filenames against `public/assets/preskool/fonts/` before assuming any are missing — if the current static asset set already has the same font family (likely, since both are PreSkool v1.9.7), no copy is needed at all | n/a unless a genuine gap is found |

## 9. Types (`src/types/`)

| Source | Verdict | Notes |
|---|---|---|
| `src/types/*` | 🎨 | Reference only for TS conventions; actual GLS types (`Student`, `Employee`, `Inscription`, etc.) are authored fresh in `resources/js/Types/` to match the real Eloquent models' shapes as exposed through Inertia props, not the theme's demo interfaces |

---

## 10. Summary counts

| Category | Approx. file count | Action |
|---|---|---|
| Reusable as visual/structural reference (✅/🎨) | ~25-30 component/layout files | Adapt individually, on-demand, per phase — never bulk-copied |
| Explicitly excluded (🚫) | ~5,250+ files (mock data, EPS sources, fonts/CSS already covered by existing static assets, entry point, router, unrelated demo modules) | Never copied |

This table will be updated with actual source→destination pairs (with
commit references) as each Phase 2+ component is genuinely adapted — right
now it is a screening/triage pass, not a change log.
