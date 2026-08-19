# GLS CRM — Project Rules

Read this before touching any file. These rules bind every session.

## 1. Project overview

School-management CRM for **GLS (Global Language School)** — centers, students,
employees, registrations, groups, attendance, payments, expenses, stock, reports.

**Stack (fixed):**

- Laravel **13** (`laravel/framework ^13.8`)
- PHP **8.4** — ⚠ multiple PHP versions on this machine (XAMPP 8.2 on Machine PATH,
  8.3 prepended by the user's PowerShell profile for other projects).
  **In scripts/tools always use `C:\php84\php.exe`** for artisan and
  `C:\php84\php.exe C:\composer\composer.phar` for composer.
  In the user's interactive PowerShell, `php`/`composer` are directory-aware
  functions (defined in `~\Documents\WindowsPowerShell\profile.ps1`) that select
  PHP 8.4 automatically inside this project — do not remove that override.
- **Inertia.js v3 + React 19 + TypeScript** — the entire backoffice frontend
  (migrated off Livewire; see `docs/inertia-react-migration-plan.md` and
  `docs/inertia-react-migration-status.md` for the full history). Livewire
  has been **fully removed** — no `livewire/livewire` package, no
  `app/Livewire/`, no `resources/views/livewire/`, no Alpine.
- Blade components (anonymous) for the Inertia root shell
  (`resources/views/app.blade.php`) and the still-Blade Frontoffice only —
  **not** for backoffice pages, which are React components under
  `resources/js/Pages/Backoffice/`.
- **Bootstrap 5** via the **PreSkool v1.9.7** admin theme (UI source of truth) —
  visuals/markup/classes are reused as-is in React (see §4); no CSS framework
  change, only the templating layer changed from Blade to JSX.
- Vite (own code only, incl. the React/Inertia bundle) + static PreSkool assets
- **PostgreSQL is the only supported database** — see §17 "Database Standard —
  PostgreSQL Only" for the full rule set (search, indexing, JSON, migrations,
  tests, deployment). Read §17 before touching anything database-related.
- Architecture: **modular monolith** — future business logic in `app/Domain/<Module>/`

**Forbidden:** Livewire, Alpine.js, jQuery plugins (Select2, moment,
daterangepicker, bootstrap-datetimepicker, feather, slimscroll — all removed;
never reintroduce them), Vue, Angular, Next.js, Tailwind CSS, redesigning the
PreSkool look, client-side DataTables for large lists.

## 2. Backoffice / Frontoffice separation (non-negotiable)

Everything is split into two areas — never mix them:

| Concern | Backoffice (admin) | Frontoffice (public) |
|---|---|---|
| Routes file | `routes/backoffice.php` | `routes/frontoffice.php` |
| URL prefix | `/backoffice/…` | `/…` |
| Route names | `backoffice.*` | `frontoffice.*` |
| Controllers | `App\Http\Controllers\Backoffice` | `App\Http\Controllers\Frontoffice` |
| Form Requests | `App\Http\Requests\Backoffice\<Module>` | `App\Http\Requests\Frontoffice\<Module>` |
| Pages (React) | `resources/js/Pages/Backoffice/…` | — (still Blade, see below) |
| Frontoffice views | — | `resources/views/frontoffice/…` |
| Components | `resources/js/Components/…` (shared across backoffice pages) | `resources/views/components/frontoffice/…` |
| JS | `resources/js/Pages/Backoffice/…`, `resources/js/Components/…`, `resources/js/Layouts/…` | `resources/js/frontoffice/…` |
| SCSS | — (Bootstrap 5 loaded statically; no backoffice SCSS bundle) | `resources/scss/frontoffice/…` |
| Tests | `tests/Feature/Backoffice/…` | `tests/Feature/Frontoffice/…` |

Cross-area shared components go to `resources/views/components/shared/` only.
`routes/web.php` only `require`s the two area files — never declare routes in it.

## 3. Theme reference rules

`resources/views/theme-reference/preskool/` holds **permanent** copies of all 252
PreSkool views (categorized; see its README.md).

- **Never delete** a reference page — even after using it.
- **Never edit** reference pages (except an intentional theme re-sync).
- **Never route** to reference pages or use them directly in production.
- To build a page: **copy** the reference file into `backoffice/` or `frontoffice/`,
  adapt the copy (layout component, `asset('assets/preskool/…')` paths, `__()` strings,
  named routes), and leave the original untouched.
- Reuse theme CSS classes and markup patterns — do not invent a parallel design.
- The original download at `C:\Users\ASUS\Downloads\themeforest-…\preskool-v1.9.7\`
  must never be modified.

**PreSkool React theme reference** (added during the Inertia/React
migration, see `docs/inertia-react-migration-plan.md`):
`resources/theme-reference/preskool-react/` is a **reference-only** copy of
the purchased React variant of the same theme. **Never import production
components directly from it.** Copy and adapt reviewed components into
`resources/js/` instead, document the mapping in
`docs/react-theme-file-map.md`, and never run `npm install` inside the
reference directory. See its own `README-GLS.md` for the full rule set and
`docs/preskool-react-reference-inventory.md` for what was copied/excluded.

**All modals are controlled by React state** — this is the only modal
architecture in the app (Livewire/Alpine modals no longer exist). Never use
Bootstrap modal JavaScript, jQuery modal initialization, or `wire:` attributes
— no `bootstrap.bundle.js`, no `data-bs-toggle`/`data-bs-dismiss`. Open/close
state, Escape, backdrop-click, focus trap/restore, and body-scroll lock are
all owned by `resources/js/Components/Modals/Modal.tsx` (props: `show`,
`title`, `onClose`, `processing`, `size`; established Phase 6,
`docs/bootstrap-react-integration-decision.md`), with
`resources/js/Components/Modals/ConfirmDialog.tsx` built on top of it for
delete confirmations. Visuals reuse the existing Bootstrap 5
`.modal`/`.modal-dialog`/`.modal-backdrop` markup/classes — only the
behavior layer is React, never Bootstrap's own JS.

## 4. Blade component rules (Frontoffice + Inertia root shell only)

Backoffice pages are React (`resources/js/Pages/Backoffice/…`) — see §5.
Blade anonymous components under `resources/views/components/` are still the
convention for whatever remains Blade-rendered:

- `resources/views/app.blade.php` — the Inertia root template (loads
  `resources/js/app.tsx`; do not confuse with the old, deleted
  `x-backoffice.layout.app` admin shell).
- `<x-frontoffice.layout.*>` — independent public shell (app, guest, header, footer).
- `<x-shared.*>` — only for genuinely cross-area components.

Never duplicate layout HTML in a page; never create a second layout system.

## 5. React/Inertia frontend rules (backoffice)

The backoffice is **100% Inertia + React + TypeScript** — Livewire has been
fully removed (Phase 11, `docs/phase-11-final-verification.md`). Pages live
in `resources/js/Pages/Backoffice/<Module>/{Index,Create,Edit,Show}.tsx`,
backed by a thin Laravel controller (`App\Http\Controllers\Backoffice\…`)
that authorizes, validates via Form Requests, and returns
`Inertia::render(...)` with typed props. Server-side pagination/search/sort/
filtering is standard for every list page — Laravel's paginator, serialized
as Inertia props (`PaginatedData<T>` in `resources/js/Types/index.ts`), never
a client-side dataset.

Shared components (reuse these — do not re-invent per page):

- `resources/js/Layouts/BackofficeLayout.tsx` — the admin shell
  (header+sidebar+footer+theme toggle), analogous to the old
  `x-backoffice.layout.app`.
- `resources/js/Components/Modals/{Modal,ConfirmDialog}.tsx` — see §3.
- `resources/js/Components/Tables/{DataTable,SearchInput,TableToolbar,Pagination,RowActions}.tsx`
  — list-page building blocks. `SearchInput` debounces (~400ms) before
  calling the page's own `reload(filters)`, which does
  `router.get(url, filters, { preserveState: true, preserveScroll: true, replace: true })`
  — an Inertia partial reload driven entirely by server-side filtering, not
  a client-side hook. `TableToolbar` is the filter-bar layout (labeled filter
  slots + `search` slot + optional `actions` slot), the direct successor to
  the old `<x-backoffice.ui.filter-bar>`. `Pagination` renders the same
  Bootstrap `.pagination` markup but navigates via `router.get(...)` instead
  of `<a href>`, so query-string filters persist across pages.
- **Centre filter dropdown rule (every list page, current and future):** if a
  CRUD index page filters by `etablissement_id` (a "Centre" `SelectField` in
  the `TableToolbar`), that dropdown must be wrapped in `{!centerLocked && (
  … )}` and the controller's `index()` must pass `'centerLocked' =>
  ! $context->isAllCenters()` (see `StudentController@index`,
  `EmployeeController@index` for the pattern). Reasoning: `CurrentContext`
  already scopes every query server-side to the active center when one is
  selected (§11 "Active working context"), so showing a redundant Centre
  filter once the user has switched to Marrakech/Rabat/etc. is misleading —
  it should only appear when the top-bar switcher is on "Tous les centres"
  (which itself is only selectable by `centers.access-all`/super-admin
  users). Never gate this on a role check in the component — reuse the
  existing `centerLocked` prop so the rule stays in sync with the context
  switcher automatically. Apply this to any new module's list page that adds
  a Centre column/filter (Groups, Inscriptions, Encaissements, Depenses,
  Remboursements, Stock, etc.) as soon as it gets one.
- `resources/js/Components/Forms/SelectField.tsx` — a plain native `<select>`
  styled with Bootstrap's `.form-select`. **Never Select2 or any jQuery
  plugin** — Inertia pages load no jQuery/Select2 assets at all; native
  `<select>` (or, if a future page genuinely needs async/searchable options,
  a new React-native combobox — not a jQuery bridge) is the only pattern.
- `resources/js/Components/Forms/{PhoneField,PhoneCountry,PasswordField,SubmitButton}.tsx`
  and other `Components/Forms/*` — the React equivalents of the old
  `<x-backoffice.forms.*>` widgets.
- **i18n**: `t()` from `resources/js/Lib/i18n.ts` (or the
  `useTranslation()` hook wrapper) — reads `lang/fr.json` directly, the
  SAME English-key/French-value dictionary Laravel's `__()` uses, so both
  sides always agree. Every user-visible string in a React component goes
  through `t('English key')`; add the French value to `lang/fr.json` in
  the same change (missing keys fall back to the English key, never
  throw).
- **Loading states**: `useInertiaLoading()`
  (`resources/js/Hooks/useInertiaLoading.ts`) — true while any Inertia
  visit is in flight (global router start/finish listener, 200ms
  minimum-visible floor); pass it as `DataTable`'s `loading` prop on list
  pages so search/filter/pagination reloads show busy feedback.

Client-side authorization is **UI convenience only** — hiding a nav item or
disabling a button. Nav items filter on the shared `auth.permissions: string[]`
Inertia prop (`resources/js/Config/backofficeNavigation.ts`), with
`auth.isSuperAdmin` short-circuiting visibility for super-admins (who hold no
direct permissions — Gate::before grants everything server-side); per-resource
`CrudPermissions { create, update, delete }` are computed server-side per
controller and passed as page props. Real enforcement is always the backend
policy/`$user->can()` check inside the controller — never trust the client
prop for anything security-relevant.

## 6. No Alpine, no jQuery plugins

Alpine.js, Select2, moment, daterangepicker, bootstrap-datetimepicker,
feather, slimscroll — all were Livewire-era dependencies and have been fully
removed (Phase 11). They must never come back:

- Never `npm install alpinejs`, never `import Alpine`, never add an Alpine
  CDN `<script>`.
- Never import jQuery or any jQuery plugin into `resources/js/` (backoffice
  or frontoffice).
- Before adding any Alpine/jQuery-flavored code, grep the repo for
  `import Alpine|Alpine.start|alpinejs|jquery|select2|\$\(` under
  `resources/js/` — there must be **zero** real results (doc-comment
  mentions explaining they're intentionally absent are fine; actual imports
  or calls are not).

## 7. Frontoffice JavaScript rules

The Frontoffice stays Blade + a minimal Vite entry
(`resources/js/frontoffice/app.js`) — Bootstrap 5 (static) plus whatever
Frontoffice-specific behavior is added as that area grows. No Alpine, no
jQuery plugins there either. Theme appearance for the backoffice (dark mode,
sidebar color, layout, localStorage persistence) now lives in the React
`BackofficeLayout`/theme components, not a Blade `theme-settings` component.

### DataTables rule

Large CRM lists (students, registrations, payments, employees, attendance,
expenses) must use **Inertia server-side** pagination/search/sort/filtering
(§5) with the PreSkool **table markup** (`resources/js/Components/Tables/DataTable.tsx`).
Client-side DataTables (the jQuery plugin) is not used anywhere in the app.

## 8. Route naming rules

- Backoffice: `backoffice.dashboard`, `backoffice.students.index`,
  `backoffice.students.create`, `backoffice.payments.index`, …
- Frontoffice: `frontoffice.home`, `frontoffice.student.login`, …
- Always link with `route('…')` — never hard-coded URLs.
- Route files stay thin; no business logic in closures; controllers only.

## 9. Controller namespaces

- `App\Http\Controllers\Backoffice` (+ `\Auth` subnamespace)
- `App\Http\Controllers\Frontoffice` (+ `\Auth` subnamespace)

Controllers are thin (`final`, invokable where suitable, return types, strict
types). Business logic belongs in `app/Domain` Actions/Services — never in
controllers, Blade, or routes.

## 10. Form Request namespaces

- `App\Http\Requests\Backoffice\<Module>\…` — e.g.
  `App\Http\Requests\Backoffice\Students\StoreStudentRequest`
- `App\Http\Requests\Frontoffice\<Module>\…`

All validation goes through Form Requests — never inline in controllers.

## 11. Domain architecture

Future business modules live under `app/Domain/`:

```
app/Domain/
├── Shared/{Actions,DTOs,Enums,Exceptions,Services,Support}
├── Centers/  Employees/  Students/  Registrations/  Groups/
├── Attendance/  Finance/  Payments/  Expenses/  Stock/  Reports/  Settings/
```

Inside a module, prefer `Actions` (single-purpose classes), `DTOs`, `Enums`,
`Models`, `Services`. No premature repository pattern, no microservices.
HTTP layer (controllers/requests) calls Domain code — never the reverse.

### Database layer (implemented — read the architecture docs first)

The approved 15-table schema and its rationale live in **`gls-crm-schema.md`** and
**`gls-crm-laravel-structure.md`** at the project root — read them before touching
the database layer. Non-negotiable invariants already enforced in code:

- **Groups are never deleted.** Transition to "Fin de formation" ONLY via
  `Group::archiverCommeTermine()` (writes the `groups_historique` snapshot in
  the same transaction). Never `->update(['statut' => …])` directly.
- **`caisses.solde` is application-maintained** (no ledger). Every money
  operation goes through a Domain action that adjusts it in ONE transaction:
  `EnregistrerEncaissement`, `EnregistrerDepense`, `EnregistrerRemboursement`,
  `DemanderTransfertCaisse` / `ValiderTransfertCaisse`. Never move money in a
  controller or with raw updates.
- **Till transfers are two-step**: request (balances untouched) → validation by
  a **different** employee (balances move). Self-validation is refused.
- **Money records (encaissements/depenses/remboursements/transfers) are never
  deleted** — no destroy routes; corrections use compensating entries.
  `montant`/`caisse_id` are not editable after creation.
- **`reference` codes are system-generated** via
  `Domain\Shared\Support\ReferenceGenerator` (EMP-/ETU-/INS-/ENC-/DEP-/RMB-/TRF-…),
  never typed by users.
- **Creating an Employee auto-creates its login** (username + one-time password
  flashed to session) via `EmployeeObserver` → `EmployeeCredentialService`.
  Pass `user_id` explicitly to skip. No public registration ever.
- **`niveau` / `categorie` / all `statut` fields are plain VARCHARs** validated
  against model constants (`Student::NIVEAUX`, `Employee::CATEGORIES`,
  `Group::STATUTS`…) — deliberate; do not "fix" with lookup tables (see the
  Deliberate Simplifications table in gls-crm-schema.md before extending).
- **Audit log**: `spatie/laravel-activitylog` **v5** (⚠ v5 namespaces:
  `Spatie\Activitylog\Models\Concerns\LogsActivity`,
  `Spatie\Activitylog\Support\LogOptions`). Fraud-relevant models (Student,
  Inscription, Encaissement, Depense, Remboursement, CaisseTransfer) carry the
  trait; keep it on any new money-touching model.
- **`is_system` expense types** are seeded (TypeDepenseSeeder) and locked; the
  admin form only creates custom types.
- **File uploads**: `spatie/laravel-medialibrary` v11 on the dedicated `media`
  disk (`storage/app/media`, symlinked to `public/media` by `storage:link`).
  Public URLs are **`/media/<8-char-uuid>/<file>`** — never `/storage/…` — via
  `App\Support\Media\ShortUuidPathGenerator` (first 8 chars of the media uuid
  as directory; keep this generator, changing it breaks every existing URL).
  Models with media implement `HasMedia` + `InteractsWithMedia` and declare
  collections in `registerMediaCollections()` — existing: `Student` (`photo`
  single-file, `documents`) and `Depense` (`justificatifs` receipts). Usage:
  `$model->addMediaFromRequest('file')->toMediaCollection('photo')`,
  URL via `$model->getFirstMediaUrl('photo')`. ⚠ PHP 8.4 needs `ext-exif`
  (enabled in `C:\php84\php.ini`) — if a new machine fails on install, enable
  `extension=exif` there.
- Route naming: French resource slugs (`backoffice.etablissements.index`,
  `backoffice.annees-scolaires.*`, `backoffice.caisse-transfers.validate`…).
  ⚠ `caisses` needs `->parameters(['caisses' => 'caisse'])` (bad singularization).
- **Every CRUD module is an Inertia+React list page with a modal add/edit**
  (not separate create/edit pages) — routes, controllers, requests, models,
  seeders, and the React `Index.tsx` pages are all done (Phase 11 completed
  the full Livewire→Inertia migration; no Livewire remains anywhere).
  Employees: `backoffice.employees.index`
  (`Backoffice\Employees\EmployeeController`) — modal add/edit
  (`resources/js/Pages/Backoffice/Employees/Index.tsx`), the one-time login
  credentials are shown after creation (EmployeeObserver auto-creates the
  User), delete is blocked when the employee has activity.
  `Employee::CATEGORIES` is the 10-value screenshot list (Directeur,
  Commercial, Enseignant, Comptable, Responsable Marketing, Assistante
  administrative, Directeur des opérations, Directrice pédagogique, Directeur
  Qualité et Amélioration continue, Autre). Users: `backoffice.users.index`
  (`Backoffice\Users\UserController`) — edit-only modal (name/email/username,
  `is_active` toggle, password regeneration); users are NEVER created here
  (they come from employees). Role assignment stays on
  `backoffice.users.authorization.edit`
  (`Backoffice\Users\UserAuthorizationController`). Own profile:
  `backoffice.profile` (`Backoffice\ProfileController`) — the signed-in user
  edits their own info + changes password (behind `auth`, no permission
  gate); the header "Profil" link points here. Modals are React state, not
  Alpine/Bootstrap-JS (§3/§5). Tests: `tests/Feature/Backoffice/People/`,
  `tests/Feature/Backoffice/Inertia/ProfileInertiaTest.php`.
- **Frais catalog → Groups → Inscriptions fee chain** (the pic-3 flow):
  a managed **`frais`** catalog (Paramètres → Frais tab, `FraisController`,
  `fees.*` permissions, `FraisPolicy`) holds predefined fees with a default
  amount. **Groups** (`backoffice.groups.index`, `GroupController` — modal
  CRUD; detail `groups.show`; archive via POST `groups.archive` →
  `Group::archiverCommeTermine`, never deleted) **assign catalog fees** via the
  **`group_frais`** pivot (per-group `montant` **and `date_echeance`** — same
  fee can have a different amount + due date per group). When enrolling
  (Inscriptions `Index.tsx`), selecting a group loads **its** assigned fees as
  "Frais disponibles" (checkbox + montant_initial + **remise %/DH** + note +
  a due date pre-filled from the group's per-fee `date_echeance` +
  final montant + échéance); `inscription_fees` carries
  `frais_id/montant_initial/remise_pct/remise_montant/note` and
  `InscriptionFee::computeMontant()` derives the final `montant`
  (pct first, else fixed DH). Starter catalog: `FraisSeeder`. Tests:
  `tests/Feature/Backoffice/Groups/`, `Inscriptions/`.
- **Étudiants & Inscriptions CRUD** (same React modal pattern as Employees).
  Students: `backoffice.students.index` (`Backoffice\StudentController`) —
  modal with photo upload (media `photo` collection, `/media/<uuid8>/…`
  URLs), niveau (CEFR) + center filters, center-scoped via
  `CenterAccessService`/`StudentPolicy`; the read-only detail page is
  `backoffice.students.show` (`StudentController@show`) showing info +
  inscriptions + payments. Inscriptions: `backoffice.inscriptions.index`
  (`Backoffice\InscriptionController`) — modal with student+group selects and
  **manual fee lines** (repeatable rows added in one transaction;
  `montant_total` = sum of fees; `etablissement_id`/`annee_scolaire_id`
  inherited from the group); list is scoped to the active academic year from
  the context switcher; detail page `backoffice.inscriptions.show` shows fee
  lines + payment summary (dû/payé/reste). Both pages have no destroy route
  surprises — delete is guarded (activity history / payments block it).
  Tests: `tests/Feature/Backoffice/Students/`, `Inscriptions/`.
- **Active working context** (selected académic year + center): every screen
  is scoped to `App\Services\Context\CurrentContext` (session-backed
  singleton, shared to every Inertia page via the `context` shared prop, see
  `App\Http\Middleware\HandleInertiaRequests`). The top-bar switcher
  (`resources/js/Components/Context/ContextSwitcher.tsx`) posts to
  `backoffice.context.update` (`ContextController@update`), which persists
  the choice through `CurrentContext` and redirects back; context-aware
  widgets (e.g. the dashboard stat cards,
  `resources/js/Pages/Backoffice/Dashboard/Index.tsx`) simply re-render on
  the next Inertia navigation/reload, since context lives server-side in the
  session, not in client state. Center switching is permission-aware — only
  `centers.access-all` users can change center or pick "Tous les centres";
  others are locked to their employee's `etablissement_id`. The header no
  longer has the language or notification dropdowns. Seed data:
  `ReferentialDataSeeder` (years 2024/2025 + 2025/2026-default, 7 GLS
  branches, 2 rooms each). Tests: `tests/Feature/Backoffice/Context/`,
  `tests/Feature/Backoffice/Inertia/ContextUpdateTest.php`.
- **Referential data (établissements, années scolaires, salles) is managed via
  the tabbed Paramètres page** — route `backoffice.settings`
  (`SettingController`), one React panel per tab under
  `resources/js/Pages/Backoffice/Settings/{Etablissements,AnneesScolaires,Salles,Frais}Panel.tsx`.
  Access = ANY of `centers.view`/`academic-years.view`/`rooms.view`; each tab
  + its mutations are gated by that resource's own permissions (authorize in
  the controller AND every mutation). The `backoffice.{etablissements,
  annees-scolaires,salles}.*` resource routes remain as permission-protected
  endpoints the Settings tabs call into. Tests: `tests/Feature/Backoffice/Settings/`.

## 12. Theme design preservation

- Preserve Bootstrap 5 and PreSkool visuals, spacing, breakpoints exactly.
- Reuse theme classes/markup (find them via `theme-reference/` demo pages).
- Never introduce another UI framework or redesign components.
- ⚠ **`fs-*` is a PIXEL scale here, not Bootstrap's heading scale.** PreSkool's
  `style.css` loads *after* `bootstrap.min.css` and redefines the whole
  `.fs-*` range so **the number is the pixel size**: `fs-24` = 1.5rem (24px),
  `fs-18` = 18px, `fs-13` = 13px. That means Bootstrap's `fs-1`…`fs-6` become
  1px…6px — `fs-4` renders 4px text, `fs-5` renders 5px. Never write
  `fs-1`…`fs-9` expecting a heading size; use the pixel value you actually
  want (`fs-24`, `fs-20`, `fs-18`, `fs-16`, `fs-14`, `fs-13`). The only
  legitimate single-digit use is the theme's own `ti-circle-filled fs-5`
  status-badge dot, where a 5px glyph is the intent.
- Verify changes in: **desktop + mobile** (sidebar/mobile menu), **dark mode**
  (header toggle), and **RTL** (Arabic locale loads `bootstrap.rtl.min.css`;
  layouts set `dir` from locale).
- Assets:
  - Static theme assets: `public/assets/preskool/{css,js,img,fonts,icons,plugins}`
    → referenced with `{{ asset('assets/preskool/…') }}`. Copied from the theme's
    prebuilt `public/build`; treat as vendor files (don't edit).
  - Vite-managed (ours only): `resources/js/{backoffice,frontoffice}/`,
    `resources/scss/{backoffice,frontoffice}/` — loaded via `@vite`.
  - Theme SCSS source kept at `resources/scss/preskool/` for reference — **not**
    compiled, **not** imported (would duplicate Bootstrap).
- Languages: **French is the default UI language** (`APP_LOCALE=fr`; fallback en).
  All page content must display in French. User-facing strings always use
  `__('…')` with English source keys translated in `lang/fr.json` — when adding
  any new visible string, add its French translation in the same change.
  Framework messages (validation/auth/pagination) are French via
  `laravel-lang/common` (`lang/fr/*.php` — regenerate with
  `C:\php84\php.exe artisan lang:update` after adding packages).
  AR / EN / DE remain prepared in `lang/*.json` for the future locale switcher.

## 13. Commands

Always from the project root, always with PHP 8.4:

```powershell
Set-Location "C:\Users\ASUS\Desktop\Projects\crm gls"

C:\php84\php.exe C:\composer\composer.phar install
npm install
npm run dev
npm run build
C:\php84\php.exe artisan serve
C:\php84\php.exe artisan test
C:\php84\php.exe artisan route:list
C:\php84\php.exe artisan optimize:clear
C:\php84\php.exe artisan make:… 
```

## 14. Quality checks — before declaring any work complete

1. `C:\php84\php.exe artisan test` passes.
2. `npm run build` succeeds (no missing SCSS/JS imports).
3. `C:\php84\php.exe artisan route:list` shows correctly named routes.
4. Affected pages render (serve + open, or HTTP-request the route) with theme
   CSS/JS/img URLs resolving (no 404s in the network tab / no broken `build/…` paths).
5. No console errors introduced (React warnings, hydration mismatches, etc.).
   Also run `npx tsc --noEmit` — no TypeScript errors.
6. New files are in the correct area (Backoffice vs Frontoffice) and namespace.
7. `theme-reference/` untouched (`git status` shows no changes there).
8. User-facing strings wrapped in `__('…')`.

## 15. Authentication

Two independent auth surfaces — do not merge them.

**Backoffice login is implemented** (session-based, `web` guard):

- Routes: `backoffice.login` (GET/POST `/backoffice/login`), `backoffice.logout`
  (POST). Dashboard and all future admin pages sit behind `auth` middleware.
- The root URL `/` currently **redirects to the Backoffice login** (admin-first
  phase); the public Frontoffice home lives at `/home`. Swap back in
  `routes/frontoffice.php` when the public site launches.
- Guest/user redirects are configured in `bootstrap/app.php`
  (`redirectGuestsTo` → backoffice.login, `redirectUsersTo` → backoffice.dashboard).
- Controllers: `Backoffice\Auth\LoginController` + `LogoutController`; validation
  + rate limiting (5 attempts) in `Requests\Backoffice\Auth\LoginRequest`.
- **Login accepts email OR username** (single `login` field; employees may have
  no email, their username is always auto-generated). Deactivated accounts
  (`users.is_active = false`) can never sign in — enforced in `LoginRequest`.
- View: `backoffice/auth/login.blade.php` on `<x-backoffice.layout.guest>`,
  adapted from `theme-reference/preskool/authentication/login.blade.php`.
- Local dev credentials (AdminUserSeeder): `admin@gls.test` / `password` —
  **local only, replace before any deployment**.
- Tests: `tests/Feature/Backoffice/AuthTest.php` — keep green.

**Password reset is implemented** (backoffice-scoped, `users` broker): routes
`backoffice.password.request`/`.email`/`.reset`/`.update` behind `guest`;
controllers `Backoffice\Auth\ForgotPasswordController` + `ResetPasswordController`
(+ Form Requests); views `backoffice/auth/{forgot,reset}-password.blade.php` on
`<x-backoffice.layout.guest>`. The reset email points at the backoffice page via
`ResetPassword::createUrlUsing` in `AppServiceProvider`; a successful reset
clears `must_change_password`. Mail is `log` in dev (link in
`storage/logs/laravel.log`). Tests: `tests/Feature/Backoffice/PasswordResetTest.php`.

**Frontoffice** auth (`frontoffice.auth.*` — students/parents) is still
deferred; use `<x-frontoffice.layout.guest>` when building it.

## 16. Roles & permissions (implemented — read docs/roles-and-permissions.md first)

`spatie/laravel-permission` v8 on the `web` guard, `User` model (`HasRoles`).
Teams OFF (employees have ONE center). Non-negotiable rules:

- **Authorization is server-side**: routes use `permission:` middleware,
  resource controllers use policies (`authorizeResource` — base Controller
  extends `Illuminate\Routing\Controller` for this) and authorize again in
  every mutation method. Any permission/role data passed to a React page as
  an Inertia prop (e.g. `CrudPermissions`, `auth.permissions`) is UI
  convenience only — it hides/disables affordances, never a real gate.
- **Check permissions, never role names** (`can('students.view')`). The only
  `hasRole()` usages allowed: the `Gate::before` super-admin bypass and the
  super-admin invariants in `UserAuthorizationService`.
- **Single source of truth**: `App\Support\Authorization\PermissionRegistry`
  (61 `module.action` permissions, French labels, role matrix). New module ⇒
  add permissions THERE, re-run `db:seed --class=RolesAndPermissionsSeeder`
  (idempotent), protect routes, add allowed+denied tests.
- **Center scoping is part of authorization**: policies extend
  `App\Policies\Concerns\ResourcePolicy` and combine permission +
  `CenterAccessService` (`centers.access-all` ⇒ all centers; else the
  employee's `etablissement_id`; NULL-center records are global).
- **Super-admin safety**: role `super-admin` bypasses everything via
  `Gate::before`; it is protected (no rename/edit/delete), only super-admins
  grant/remove it, the last one can never lose it. First assignment:
  `C:\php84\php.exe artisan auth:assign-super-admin <email>`.
- Roles carry a French `label` column (`App\Models\Role`); machine names are
  immutable after creation. Role/permission mutations go through
  `UserAuthorizationService` (transaction + activity log `authorization`).
- UI: `backoffice.roles.*`, `backoffice.users.index`,
  `backoffice.users.authorization.edit`, `backoffice.permissions.index` —
  all Inertia + React + PreSkool; permissions page is read-only.
- Tests live in `tests/Feature/Backoffice/Authorization/` — keep green; never
  weaken a 403 assertion to make a feature pass.

## 17. Database Standard — PostgreSQL Only

This project uses PostgreSQL as its only supported database engine.

- Local development: PostgreSQL
- Automated tests: PostgreSQL
- Staging: PostgreSQL
- Production: PostgreSQL
- Laravel connection: `pgsql`
- Target PostgreSQL version: PostgreSQL 17
- Minimum acceptable production version: PostgreSQL 16+

The full audit and migration history (what changed, why, and what was verified)
lives in `POSTGRES_AUDIT.md` and `POSTGRES_MIGRATION_REPORT.md` — read those
before any further database-layer work.

### Database compatibility

- Do not add SQLite compatibility.
- Do not add MySQL or MariaDB compatibility.
- Do not add database-driver conditional branches (`DB::getDriverName()` checks
  etc.) — `config/database.php` declares only the `pgsql` connection; keep it
  that way.
- Do not use SQLite `:memory:` for tests.
- Do not test database behavior against a different engine than production.
- All migrations, seeders, tests, and queries must run against PostgreSQL.

### Search rules

All case-insensitive user-facing searches must use PostgreSQL `ILIKE`.

Correct:

```php
$query->where('nom', 'ilike', "%{$search}%");
```

Incorrect:

```php
$query->where('nom', 'like', "%{$search}%");
```

PostgreSQL `LIKE` is case-sensitive (unlike MySQL's default collation) —
replacing `ilike` with `like` silently reintroduces a search regression users
will notice immediately (e.g. searching "dupont" no longer finding "Dupont").

For large datasets, do not automatically introduce full-text or fuzzy search.
First measure search performance. If needed, consider `pg_trgm`, GIN trigram
indexes, or PostgreSQL full-text search (see § PostgreSQL extensions below).
Preserve existing search behavior unless a feature specifically requests fuzzy
search.

### Foreign-key index rules

PostgreSQL does **not** automatically create an index on the referencing side
of a foreign key (unlike MySQL/InnoDB). Whenever adding:

```php
$table->foreignId('student_id')->constrained();
```

verify whether a standalone or composite index already covers `student_id`.
Add an index when the column is used for filtering, joins, eager loading,
center scoping, sorting, or finance queries. Do not add a redundant
single-column index when an existing composite index already begins with that
column (e.g. `encaissements.caisse_id` is covered by the
`(caisse_id, date_paiement)` composite — no separate index needed).

### JSON rules

Prefer `jsonb()` over `json()` for application JSON data unless exact textual
JSON representation, key order, or whitespace preservation is explicitly
required.

Correct default:

```php
$table->jsonb('properties')->nullable();
```

Only add GIN indexes when the application actually filters or searches inside
JSONB data — don't add them speculatively.

### Migration rules

- Before the first production deployment, existing project-owned migrations
  may still be corrected in place (this is what happened during the
  PostgreSQL migration — see `POSTGRES_MIGRATION_REPORT.md` §2 for the two
  `json()`→`jsonb()` edits made to already-applied local migrations).
- After a migration has run in **production**: never edit it — create a new
  migration instead.
- Never use `migrate:fresh` in production.
- Production uses `php artisan migrate --force`.

### Query rules

Review every use of `DB::raw()`, `selectRaw()`, `whereRaw()`, `havingRaw()`,
`orderByRaw()` — queries must use PostgreSQL-compatible syntax. Do not
introduce MySQL-only functions such as `GROUP_CONCAT`, `IFNULL`,
`DATE_FORMAT`, `FIND_IN_SET`, `FIELD`. Prefer Laravel query-builder methods
where practical.

### Money rules

Keep financial columns as fixed precision:

```php
$table->decimal('montant', 12, 2);
```

Never use floating-point columns for monetary amounts. Do not change existing
finance behavior during database optimizations (see §11's Finance invariants —
those rules are independent of and unaffected by the database engine).

### Date-query rules

Prefer sargable date ranges:

```php
$query->whereBetween('date_paiement', [$start, $end]);
```

Avoid wrapping indexed date columns in SQL functions when a direct range can
produce the same result.

### Test rules

Tests must use:

```env
DB_CONNECTION=pgsql
DB_DATABASE=gls_crm_test
```

The test database must be separate from local development, staging, and
production. **Never point PHPUnit at `gls_crm`.** Commands using
`migrate:fresh`, `RefreshDatabase`, truncation, or destructive seeders must
only run against `gls_crm_test`.

### Environment defaults

Standard local environment:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gls_crm
DB_USERNAME=postgres
DB_PASSWORD=postgres
DB_SSLMODE=prefer
```

(Local dev currently uses the `postgres` superuser role for simplicity —
production must use a dedicated non-superuser role, see below.)

Standard production environment:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gls_crm
DB_USERNAME=gls_crm_app
DB_PASSWORD=<secure-password>
DB_SSLMODE=prefer
```

When PostgreSQL is remote, use `DB_SSLMODE=require`, or preferably
`verify-full` with a CA certificate.

### Production security

When Laravel and PostgreSQL run on the same VPS:

- PostgreSQL should listen only on localhost.
- Port `5432` should not be publicly exposed.
- Laravel must use a dedicated application role (`gls_crm_app`), never the
  `postgres` superuser.
- Production credentials must not be committed.

### Performance rules

PostgreSQL does not automatically solve application-level inefficiencies.
Continue to measure query counts, duplicate queries, unpaginated
collections, and PHP-side sorting/merging — see `PERFORMANCE_AUDIT.md`,
`PERFORMANCE_OPTIMIZATION_REPORT.md`, and
`docs/phase-11-performance-baseline.md` for the established methodology
(the first two predate the Inertia+React migration and use Livewire-era
terminology like "Livewire renders"/"Select2 option lists" in their own
historical measurements — read them as a record of what was measured then,
not as current-state facts).

The known remaining bottleneck is `CaisseController::journal()`
(`app/Http/Controllers/Backoffice/CaisseController.php`, the Inertia
successor to the old `CaisseJournal` Livewire component), which currently
merges four tables' finance records in PHP with no SQL-level pagination and
should eventually move to a PostgreSQL `UNION ALL` with database pagination
— flagged in both performance documents, intentionally deferred as
out-of-scope for those passes and for Phase 11.

### PostgreSQL extensions (not currently installed — future tools only)

Do not add these to migrations without a measured need:

- **`pg_trgm`** — useful for measured fuzzy or substring-search bottlenecks
  (trigram GIN indexes on `nom`/`prenom`/`reference`).
- **`unaccent`** — useful for accent-insensitive text search (relevant for
  French/German/Arabic names).
- **`pgcrypto`** — useful for PostgreSQL-generated UUIDs or cryptographic
  functions, not currently needed (all PKs are bigint identity).
