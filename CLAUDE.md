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
- Livewire **4** (server-driven UI) with its **bundled Alpine.js**
- Blade components (anonymous) for all reusable UI
- **Bootstrap 5** via the **PreSkool v1.9.7** admin theme (UI source of truth)
- Vite (own code only) + static PreSkool assets
- **PostgreSQL is the only supported database** — see §17 "Database Standard —
  PostgreSQL Only" for the full rule set (search, indexing, JSON, migrations,
  tests, deployment). Read §17 before touching anything database-related.
- Architecture: **modular monolith** — future business logic in `app/Domain/<Module>/`

**Forbidden:** React, Vue, Angular, Inertia, Next.js, Tailwind CSS, second Alpine
instance, redesigning the PreSkool look, client-side DataTables for large lists.

## 2. Backoffice / Frontoffice separation (non-negotiable)

Everything is split into two areas — never mix them:

| Concern | Backoffice (admin) | Frontoffice (public) |
|---|---|---|
| Routes file | `routes/backoffice.php` | `routes/frontoffice.php` |
| URL prefix | `/backoffice/…` | `/…` |
| Route names | `backoffice.*` | `frontoffice.*` |
| Controllers | `App\Http\Controllers\Backoffice` | `App\Http\Controllers\Frontoffice` |
| Form Requests | `App\Http\Requests\Backoffice\<Module>` | `App\Http\Requests\Frontoffice\<Module>` |
| Livewire | `App\Livewire\Backoffice\…` | `App\Livewire\Frontoffice\…` |
| Page views | `resources/views/backoffice/…` | `resources/views/frontoffice/…` |
| Livewire views | `resources/views/livewire/backoffice/…` | `resources/views/livewire/frontoffice/…` |
| Components | `resources/views/components/backoffice/…` | `resources/views/components/frontoffice/…` |
| JS | `resources/js/backoffice/…` | `resources/js/frontoffice/…` |
| SCSS | `resources/scss/backoffice/…` | `resources/scss/frontoffice/…` |
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

## 4. Blade component rules

One convention only: **anonymous components** under `resources/views/components/`.

- `<x-backoffice.layout.app>` — admin shell (header+sidebar+footer+theme-settings).
  Also: `…layout.guest` (auth/error pages), `…layout.print` (printables),
  `…layout.page-header`, `…layout.breadcrumbs`.
- `<x-backoffice.ui.*>` — card, button, badge, alert, modal, table, empty-state,
  pagination, filter-bar (+ `filter-bar.date-field`).
- `<x-backoffice.forms.*>` — input, select, textarea, error (all render validation errors).
- `<x-frontoffice.layout.*>` — independent public shell (app, guest, header, footer).
- `<x-shared.*>` — only for genuinely cross-area components.

Canonical page skeleton:

```blade
<x-backoffice.layout.app :title="__('Students')">
    <x-backoffice.layout.page-header
        :title="__('Students')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Students') => null]" />
    {{-- content --}}
</x-backoffice.layout.app>
```

Never duplicate layout HTML in a page; never create a second layout system.

## 5. Livewire rules

Use Livewire **only** where server-side dynamics are needed: searchable/paginated
tables, filters, forms with validation, payment allocation, attendance entry,
dashboard filters, dependent selects, uploads, bulk actions, backend-driven modals.

Do **not** use Livewire for static UI: layouts, navigation, headers/footers,
info pages, design-only widgets — plain Blade there.

Use Alpine (via Livewire) for frontend-only state: dropdowns, tabs, show/hide,
password visibility, small modal state.

Livewire classes live in `App\Livewire\<Area>\<Module>\…`, views in
`resources/views/livewire/<area>/…`.

## 6. Alpine rules

Alpine is **bundled with Livewire 4 and auto-injected**. Therefore:

- Never `npm install alpinejs`, never `import Alpine`, never `Alpine.start()`,
  never add an Alpine CDN `<script>`.
- Never add `@livewireScripts`/`@livewireStyles` manually — auto-injection handles it.
- Before adding any Alpine-related code, search the repo for existing
  `import Alpine|Alpine.start|alpinejs` — there must be **zero** results outside vendor.

## 7. JavaScript plugin rules

The theme is jQuery-based (loaded statically by `<x-backoffice.layout.scripts />`).
Core always-on plugins: jQuery, Bootstrap bundle, moment, daterangepicker, select2,
bootstrap-datetimepicker, feather, slimscroll, `script.js`.

- Page-specific plugin assets go through `@push('styles')` / `@push('scripts')`
  in the page — never add more always-on scripts without need.
- All initialisation goes through `initializeBackofficePlugins()` in
  `resources/js/backoffice/app.js`, which runs on `DOMContentLoaded` **and**
  `livewire:navigated`. Every initialiser must guard against double-init
  (instance checks / `data-*` flags / `select2-hidden-accessible`).
- Use `wire:ignore` only where a plugin must own its DOM (e.g. Select2 inside a
  Livewire component) and **add a comment explaining why** at the usage site.
- Theme appearance (dark mode, sidebar color, layout, localStorage persistence)
  lives in `resources/js/backoffice/theme.js` + the
  `<x-backoffice.layout.theme-settings />` component. The original
  `theme-script.js` is kept as reference in `public/assets/preskool/js/` but is
  **not loaded** — don't load it (it injects markup with broken paths).
- **Select2 double-init fix (both parts load-bearing)**: every
  `<x-backoffice.forms.select2>` field (modal fields AND `live inline` index
  filters, app-wide) used to render a second, empty Select2 widget stacked
  under the real one, on first page load, no interaction needed. Root cause:
  `public/assets/preskool/js/script.js`'s generic on-ready initializer used
  the selector `$('.select2')` — but Select2's own generated wrapper `<span>`
  also carries the class `select2` (`<span class="select2 select2-container
  ...">`), so once Alpine's `glsSelect2` (`resources/js/backoffice/app.js`)
  had already built the real widget, that vendor script matched the freshly
  built wrapper and called `.select2()` on it again. Fixed by narrowing that
  selector to `select.select2:not(.select2-hidden-accessible)` (real,
  not-yet-initialized `<select>`s only — no production view uses class
  `select2` on a `<select>` today, only `theme-reference/`). This is a rare
  sanctioned edit to the vendor copy in `public/assets/preskool/` (normally
  read-only, §3) — a needed correction, not a redesign; re-apply it if the
  theme assets are ever re-synced. `mountSelect2()` in `app.js` also sweeps
  stray `:scope > .select2-container` siblings before every `$el.select2()`
  call as defense-in-depth against any other double-init path. If a Select2
  field ever shows a duplicate empty dropdown again, check `script.js`'s
  selector first before touching `app.js`.

### DataTables rule

Large CRM lists (students, registrations, payments, employees, attendance,
expenses) must use **Livewire server-side** pagination/search/sort/filtering with
the PreSkool **table design** (`<x-backoffice.ui.table>`). Client-side DataTables
is allowed only for small, genuinely static demo tables.

### List filter rule

Every index page's filters (select2 dropdowns, date-range inputs, search box,
create button) render via **`<x-backoffice.ui.filter-bar>`**
(`resources/views/components/backoffice/ui/filter-bar.blade.php`) as its own
labeled, full-width row inside the card body — **never** cram them into
`<x-backoffice.ui.card>`'s `x-slot:tools` (that slot sits in the card header
next to the `<h4>` title, fighting it for space; with 2+ filters it wraps into
an unlabeled, uneven stack — especially inside a narrower nested tab, e.g.
Gestion des dépenses' tab panes). Pattern:

```blade
<x-backoffice.ui.card :title="__('Expenses')">
    <x-backoffice.ui.filter-bar>
        <x-backoffice.forms.select2 id="d-type-filter" model="typeFilter" live
            :label="__('Expense type')" :placeholder="__('All expense types')">
            …
        </x-backoffice.forms.select2>
        <x-backoffice.ui.filter-bar.date-field :label="__('From date')" model="dateFrom" />
        <x-slot:search>
            <div class="input-icon-start position-relative">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
            </div>
        </x-slot:search>
        @can('create', \App\Models\Depense::class)
            <x-slot:actions>…create button…</x-slot:actions>
        @endcan
    </x-backoffice.ui.filter-bar>
```

Every direct default-slot child is one labeled filter (select2 **without**
`inline` — keep its own label; `filter-bar.date-field` for dates); `search`
and `actions` are optional named slots rendered at the row's end, aligned
right on desktop. Already applied to all 13 real list pages (Depenses,
Remboursements, Encaissements, Students, Employees, Inscriptions, Users,
Types de dépenses, Groups, Caisse Transfers, Roles, Caisses, Caisse Journal).
Not used on `role-form.blade.php` / `manage-authorization.blade.php` — those
are single-record edit forms, not lists, and their `tools` slots hold static
badges, not filters.

## 8. Route naming rules

- Backoffice: `backoffice.dashboard`, `backoffice.students.index`,
  `backoffice.students.create`, `backoffice.payments.index`, …
- Frontoffice: `frontoffice.home`, `frontoffice.student.login`, …
- Always link with `route('…')` — never hard-coded URLs.
- Route files stay thin; no business logic in closures; controllers or Livewire
  route components only.

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

All validation goes through Form Requests (or Livewire validation) — never inline
in controllers.

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
HTTP layer (controllers/requests/Livewire) calls Domain code — never the reverse.

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
- CRUD Blade/Livewire screens for these resources are the NEXT phase — routes,
  controllers, requests, models, seeders are done; controllers reference
  `backoffice.<resource>.<action>` views that do not exist yet.
- **Employees & Users CRUD are Livewire pages with modal add/edit** (not
  separate create/edit pages). Employees: `backoffice.employees.index`
  (`Employees\EmployeesIndex`) — modal add/edit, the one-time login credentials
  are shown after creation (EmployeeObserver auto-creates the User), delete is
  blocked when the employee has activity. `Employee::CATEGORIES` is the 10-value
  screenshot list (Directeur, Commercial, Enseignant, Comptable, Responsable
  Marketing, Assistante administrative, Directeur des opérations, Directrice
  pédagogique, Directeur Qualité et Amélioration continue, Autre). Users:
  `backoffice.users.index` (`Users\UsersIndex`) — edit-only modal (name/email/
  username, `is_active` toggle, password regeneration); users are NEVER created
  here (they come from employees). Role assignment stays on
  `backoffice.users.authorization.edit`. Own profile:
  `backoffice.profile` (`Profile\ProfilePage`) — the signed-in user edits their
  own info + changes password (behind `auth`, no permission gate); the header
  "Profil" link points here. Livewire modals are Alpine-driven
  (`x-data="{ show: @entangle('showModal') }"`), not Bootstrap-JS. Tests:
  `tests/Feature/Backoffice/People/`, `ProfileTest`.
- **Frais catalog → Groups → Inscriptions fee chain** (the pic-3 flow):
  a managed **`frais`** catalog (Paramètres → Frais tab, `Settings\FraisTab`,
  `fees.*` permissions, `FraisPolicy`) holds predefined fees with a default
  amount. **Groups** (`backoffice.groups.index`, `Groups\GroupsIndex` — modal
  CRUD; detail `groups.show`; archive via POST `groups.archive` →
  `Group::archiverCommeTermine`, never deleted) **assign catalog fees** via the
  **`group_frais`** pivot (per-group `montant` **and `date_echeance`** — same
  fee can have a different amount + due date per group). When enrolling
  (`InscriptionsIndex`), selecting a group loads **its** assigned fees as
  "Frais disponibles" (checkbox + montant_initial + **remise %/DH** + note +
  a due date pre-filled from the group's per-fee `date_echeance` +
  final montant + échéance); `inscription_fees` carries
  `frais_id/montant_initial/remise_pct/remise_montant/note` and
  `InscriptionFee::computeMontant()` derives the final `montant`
  (pct first, else fixed DH). Starter catalog: `FraisSeeder`. Tests:
  `tests/Feature/Backoffice/Groups/`, updated `Inscriptions/`.
- **Étudiants & Inscriptions CRUD are Livewire pages with modal add/edit**
  (same Alpine modal pattern as Employees). Students:
  `backoffice.students.index` (`Students\StudentsIndex`) — modal with photo
  upload (media `photo` collection, `/media/<uuid8>/…` URLs), niveau (CEFR) +
  center filters, center-scoped via `CenterAccessService`/`StudentPolicy`; the
  read-only detail page is `backoffice.students.show`
  (`StudentController@show`) showing info + inscriptions + payments. Inscriptions:
  `backoffice.inscriptions.index` (`Inscriptions\InscriptionsIndex`) — modal with
  student+group selects and **manual fee lines** (repeatable rows added in one
  transaction; `montant_total` = sum of fees; `etablissement_id`/`annee_scolaire_id`
  inherited from the group); list is scoped to the active academic year from the
  context switcher; detail page `backoffice.inscriptions.show` shows fee lines +
  payment summary (dû/payé/reste). Both pages have no destroy route surprises —
  delete is guarded (activity history / payments block it). Tests:
  `tests/Feature/Backoffice/Students/`, `tests/Feature/Backoffice/Inscriptions/`.
- **Active working context** (selected académic year + center): every screen is
  scoped to `App\Services\Context\CurrentContext` (session-backed singleton,
  shared to all views as `$context`). Top-bar switchers
  (`App\Livewire\Backoffice\Context\ContextSwitcher`) persist the choice and
  dispatch `context-changed`; context-aware widgets (e.g.
  `Dashboard\DashboardStats`) listen via `#[On('context-changed')]` and refresh
  live. Center switching is permission-aware — only `centers.access-all` users
  can change center or pick "Tous les centres"; others are locked to their
  employee's `etablissement_id`. The header no longer has the language or
  notification dropdowns. Seed data: `ReferentialDataSeeder` (years 2024/2025 +
  2025/2026-default, 7 GLS branches, 2 rooms each). Tests:
  `tests/Feature/Backoffice/Context/`.
- **Referential data (établissements, années scolaires, salles) is managed via
  the tabbed Paramètres page** — route `backoffice.settings`
  (`SettingController`), view `backoffice/settings/index.blade.php`, one Livewire
  CRUD tab each under `App\Livewire\Backoffice\Settings\*Tab`. Access = ANY of
  `centers.view`/`academic-years.view`/`rooms.view`; each tab + its mutations are
  gated by that resource's own permissions (authorize in `mount()` AND every
  mutation). The old `backoffice.{etablissements,annees-scolaires,salles}.*`
  resource routes are kept as permission-protected endpoints but the Settings
  tabs are the primary UI. Tests: `tests/Feature/Backoffice/Settings/`.

## 12. Theme design preservation

- Preserve Bootstrap 5 and PreSkool visuals, spacing, breakpoints exactly.
- Reuse theme classes/markup (find them via `theme-reference/` demo pages).
- Never introduce another UI framework or redesign components.
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
5. No duplicate-Alpine console errors; no JS errors introduced.
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
  extends `Illuminate\Routing\Controller` for this), Livewire components
  authorize in `mount()` AND in every mutation method. `@can` in Blade is
  UI convenience only.
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
  `backoffice.users.authorization.edit`, `backoffice.permissions.index`
  (Livewire 4 + PreSkool; permissions page is read-only Blade).
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
Continue to measure query counts, duplicate queries, Livewire renders,
unpaginated collections, eager component mounting, large Select2 option
lists, and PHP-side sorting/merging — see `PERFORMANCE_AUDIT.md` and
`PERFORMANCE_OPTIMIZATION_REPORT.md` for the established methodology.

The known remaining bottleneck is `CaisseJournal`
(`app/Livewire/Backoffice/Caisses/CaisseJournal.php`), which currently merges
four tables' finance records in PHP with no SQL-level pagination and should
eventually move to a PostgreSQL `UNION ALL` with database pagination — flagged
in both performance documents, intentionally deferred as out-of-scope for
those passes.

### PostgreSQL extensions (not currently installed — future tools only)

Do not add these to migrations without a measured need:

- **`pg_trgm`** — useful for measured fuzzy or substring-search bottlenecks
  (trigram GIN indexes on `nom`/`prenom`/`reference`).
- **`unaccent`** — useful for accent-insensitive text search (relevant for
  French/German/Arabic names).
- **`pgcrypto`** — useful for PostgreSQL-generated UUIDs or cryptographic
  functions, not currently needed (all PKs are bigint identity).
