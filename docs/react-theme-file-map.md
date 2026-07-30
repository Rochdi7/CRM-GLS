# PreSkool React Theme — File Map

Status: **Phase 2 shell + Phase 3 auth/profile + Phase 4 dashboard/context
implemented — see §0 (Phase 2), §0b (Phase 3), §0c (Phase 4) for what
actually shipped.** The rest of this document (§1 onward) is the original
Phase 0/1 screening pass and remains accurate for anything not yet built.

Theme source root (original, external — read-only, unchanged):
`C:\Users\ASUS\Downloads\themeforest-jeUxtzLq-preskool-bootstrap-admin-html-template\preskool-v1.9.7\react`

**Permanent in-repo reference copy** (added Phase 4):
`resources/theme-reference/preskool-react/` — see
`docs/preskool-react-reference-inventory.md` for what was copied/excluded
and `resources/theme-reference/preskool-react/README-GLS.md` for usage
rules. From Phase 4 onward, source-path references in this file point to
paths **inside that reference copy**, not the external Downloads folder —
both contain the same files for anything not excluded (the `eps/` icon-source
folder and `public/` demo images were the only exclusions, per the
inventory doc), so either path resolves to the same content.

---

## 0b. Phase 3 — auth/profile adaptations (source → destination)

| Destination | Theme file(s) inspected | Verdict | Notes |
|---|---|---|---|
| `resources/js/Pages/Backoffice/Auth/Login.tsx` | `react/src/feature-module/auth/login/login.tsx` (+ `login-2.tsx`, `login-3.tsx`) | **Rejected as demo-only for logic; markup structure not adapted either** — primary source is the existing `resources/views/backoffice/auth/login.blade.php` instead | Theme's `login.tsx` is a two-column layout (promo panel + form) with social-login buttons, a `Sign In` `<Link>` that navigates to a hardcoded dashboard route instead of submitting a form, `localStorage.setItem('menuOpened', ...)`, and full `react-router-dom` dependency. The existing GLS Blade login page had already chosen the theme's alternate **centered single-column** variant (`login-3.blade.php` per its own comment) with real form POST — that adapted-Blade structure is the actual visual/markup source for the Inertia page, not the raw demo file. Password-visibility *pattern* (state-driven `ti-eye`/`ti-eye-off` class swap) was reused as a technique, not the surrounding demo markup |
| `resources/js/Pages/Backoffice/Auth/ForgotPassword.tsx` | `react/src/feature-module/auth/forgotPassword/forgotPassword.tsx` (+ `-2`, `-3` variants) | **Rejected as demo-only** — same reasoning; `resources/views/backoffice/auth/forgot-password.blade.php` is the real source | Theme file has no `localStorage`/mock-API logic, but is fully `react-router-dom`-dependent (7 references) and uses the two-column layout family, not the GLS centered-card variant already established |
| `resources/js/Pages/Backoffice/Auth/ResetPassword.tsx` | `react/src/feature-module/auth/resetPassword/resetPassword.tsx` (+ `-2`, `-3` variants) | **Rejected as demo-only** — same reasoning; `resources/views/backoffice/auth/reset-password.blade.php` is the real source | Same profile as ForgotPassword: no demo-auth/localStorage, but `react-router-dom`-dependent, wrong layout family for this project |
| `resources/js/Pages/Backoffice/Profile/Index.tsx` | Not sourced from the theme at all | **Not copied — no theme equivalent audited** | The theme's `feature-module/peoples`/generic profile pages (if any) were not the closest match; the existing `resources/views/livewire/backoffice/profile/profile-page.blade.php` (already a GLS-specific adaptation of the theme's card/table conventions) is the sole structural source |
| `resources/js/Layouts/GuestLayout.tsx` | `react/src/feature-module/auth/*` layout wrappers (implicit — no dedicated guest-layout component found; each demo page inlines its own wrapper) | **Rejected — no reusable theme component to adapt** | Built from `resources/views/components/backoffice/layout/guest.blade.php` + the shared structural pattern already present in all three existing Blade auth pages (`vh-100` flex column, centered card, GLS light/dark logo pair) |
| `resources/js/Components/Forms/{FormField,PasswordField,FormError,SubmitButton}.tsx` | Password-toggle technique only, from `login.tsx`'s `togglePasswordVisibility` state pattern; `public/assets/preskool/js/script.js`'s `.toggle-password`/`ti-eye`/`ti-eye-off` class contract (grepped directly) | **Technique reused, markup authored fresh** | No direct theme component for these — the theme repeats raw `<input>`/`<label>` markup inline on every page rather than extracting reusable field components |
| `resources/js/Components/Feedback/AuthStatus.tsx` | No theme equivalent (theme has no `session('status')`-style flash concept — it's a demo with no real backend) | **New, GLS-specific** | Sourced from the existing Blade `@if (session('status'))` pattern |

**Demo-specific code explicitly NOT carried over** (per this phase's stop
list): social-login buttons (Facebook/Google/Apple), the "Or" divider,
register/create-account links (no public registration exists or is
planned), `localStorage.setItem('menuOpened', ...)`, hardcoded
`Link to={routes.adminDashboard}` acting as a fake successful-login
shortcut, the two-column promo-panel layout, all `react-router-dom`
imports and `<Link to="...">` usage (replaced by real `<form onSubmit>` +
Inertia `useForm().post()`, or plain `<a href>` for guest-to-guest
navigation).

---

## 0c. Phase 4 — dashboard/context adaptations (source → destination)

| Destination | Source inspected | Verdict | Notes |
|---|---|---|---|
| `resources/js/Pages/Backoffice/Dashboard/Index.tsx` | Theme's `src/feature-module/mainMenu/adminDashboard/` (in the reference copy) | **Rejected as demo-only** — `resources/views/backoffice/dashboard/index.blade.php` is the real source | The theme's admin dashboard is built around a completely different data model (school-demo KPIs, fake charts/graphs, unrelated widgets) with no GLS equivalent; the existing GLS-adapted Blade page (welcome banner + `@livewire('backoffice.dashboard.dashboard-stats')`) was the sole structural/visual source, matching the established Phase 2/3 convention of preferring the already-adapted Blade markup over the raw theme demo |
| `resources/js/Components/Dashboard/{StatCard,StatsGrid}.tsx` | Not sourced from the theme — no reusable stat-card component was found isolated from `adminDashboard`'s page-specific markup | **New, adapted from the existing Blade card markup** | `resources/views/livewire/backoffice/dashboard/dashboard-stats.blade.php`'s exact card structure (`.avatar.avatar-xl`, `.stat-counter`, border-top secondary-metric row) is the source, copied class-for-class |
| `resources/js/Components/Context/ContextSwitcher.tsx` | Theme's header (`src/core/common/header/index.tsx`, already reference-screened in Phase 2 — Redux/`react-router-dom`-dependent) has no equivalent two-dropdown year/center switcher at all — GLS's own context switcher is a project-specific feature the demo theme doesn't model | **Not sourced from the theme** | `resources/views/livewire/backoffice/context/context-switcher.blade.php` is the sole source — exact dropdown markup/classes/badges, React-owned open state instead of Bootstrap `data-bs-toggle` |

No new theme assets, icons, or SCSS were needed for Phase 4 — every visual
element reuses classes already loaded by the existing static PreSkool CSS.

---

## 0. Phase 2 — actual adaptations (source → destination)

None of these are copies — every one is authored fresh in TSX, using the
theme file and/or the existing adapted Blade component as a **structural/
visual** reference only. Data wiring, state management, and routing are
GLS/Inertia-specific throughout (no Redux, no React Router, no localStorage
demo-auth carried over).

| Destination | Adapted from | Copied / Rewritten | Notes |
|---|---|---|---|
| `resources/js/Components/Theme/Header.tsx` | Primary reference: `resources/views/components/backoffice/layout/header.blade.php` (the already-GLS-trimmed Blade version — no search/notifications/mega-menu). Secondary reference: theme's `react/src/core/common/header/index.tsx` (markup/class names only). | Rewritten | Dropdown state is `useState` + click-outside/Escape listeners (own implementation), not Bootstrap `data-bs-toggle` DOM-scanning and not Redux (theme's version uses `react-redux` + `react-router-dom`'s `Link`, both removed). Logo images reused as-is from `public/assets/images/logo/`. Context display is read-only (IDs only) per Phase 2 scope — no switcher yet (Phase 4) |
| `resources/js/Components/Theme/Sidebar.tsx` | `resources/views/components/backoffice/layout/sidebar.blade.php` (exact group/item/permission-gate structure) | Rewritten | Nav data extracted to `Config/backofficeNavigation.ts` instead of inline Blade `@can`; permission filtering done in JS against the shared `auth.permissions` array. Active-state via `useActivePath` hook, not per-component `request()->routeIs()` calls |
| `resources/js/Components/Theme/Breadcrumbs.tsx` | `resources/views/components/backoffice/layout/breadcrumbs.blade.php` | Rewritten (markup copied exactly: `<nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item">`) | Typed `Breadcrumb[]` prop instead of Blade's `label => url` array convention |
| `resources/js/Components/Theme/PageHeader.tsx` | `resources/views/components/backoffice/layout/page-header.blade.php` | Rewritten (markup copied exactly) | `children` fills the `actions` slot |
| `resources/js/Components/Theme/Footer.tsx` | `resources/views/components/backoffice/layout/footer.blade.php` | Rewritten (markup copied exactly) | No theme equivalent — original PreSkool pages have no footer; this project added a minimal one, ported as-is |
| `resources/js/Components/Theme/MobileSidebarOverlay.tsx` | `public/assets/preskool/js/script.js`'s `.sidebar-overlay`/`opened` class contract (grepped directly, since the React theme's own mobile-overlay handling wasn't separately isolated in the audited files) | Rewritten | React renders/removes the overlay element by state instead of jQuery `.toggleClass('opened')`; click closes, matching existing behavior |
| `resources/js/Components/Feedback/FlashMessages.tsx` | `resources/views/components/backoffice/ui/alert.blade.php` | Rewritten (markup copied exactly) | Dismiss is `useState`, not `data-bs-dismiss="alert"` (no Bootstrap JS on Inertia pages — see `docs/bootstrap-react-integration-decision.md`) |
| `resources/js/Components/Shared/Card.tsx` | `resources/views/components/backoffice/ui/card.blade.php` | Rewritten (markup copied exactly) | |
| `resources/js/Components/Tables/DataTable.tsx` | `resources/views/components/backoffice/ui/table.blade.php` | Rewritten (markup copied exactly) | Visual/structural only — no client-side sort/filter/pagination logic adopted from the theme's own `core/common/dataTable/index.tsx` (that component was screened 🎨 reference-only in §1, and stays that way; CLAUDE.md's DataTables rule applies) |
| `resources/js/Components/Shared/EmptyState.tsx` | `resources/views/components/backoffice/ui/empty-state.blade.php` | Rewritten (markup copied exactly) | |
| `resources/js/Components/Navigation/NavLink.tsx` | No direct theme equivalent — theme always uses `react-router-dom`'s `Link` unconditionally | New, GLS-specific | Explicit Inertia-vs-anchor branch per the migration's routing rules; not adapted from any theme file |
| `resources/js/Hooks/useActivePath.ts` | No theme equivalent — theme derives active state from `react-router-dom`'s `useLocation()` | New, GLS-specific | Reads `usePage().url` instead |
| `resources/js/Config/backofficeNavigation.ts` | `resources/views/components/backoffice/layout/sidebar.blade.php` (data extracted, not the theme's `all_routes.tsx`) | Rewritten as data | Deliberately NOT sourced from the theme's `feature-module/router/all_routes.tsx` — that file encodes ~200 demo routes for a different app; GLS's own Blade sidebar is the only authoritative source for "what modules actually exist here" |

**Assets**: no new image/font/icon files were copied this phase — the shell
reuses the exact same static files already serving the Blade/Livewire pages
(`/assets/images/logo/gls-noir.png`, `/assets/images/logo/gls-blanc.webp`,
`/assets/preskool/img/profiles/avatar-27.jpg`, and the already-loaded
`tabler-icons`/`fontawesome` icon-font classes). Confirms the "no duplicate
asset loading" rule end to end for this phase.

**Theme SCSS**: none imported — `resources/views/app.blade.php` loads the
exact same static `assets/preskool/css/{bootstrap.min,style}.css` the
Blade/Livewire shell uses, so every class referenced above (`.card`,
`.table`, `.sidebar`, `.header`, `.breadcrumb`, `.alert`) resolves from CSS
that was already being served — zero new stylesheet requests introduced.

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
