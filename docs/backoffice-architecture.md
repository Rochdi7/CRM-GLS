# How the Backoffice Works (incl. Livewire)

A practical guide to the admin area of the GLS CRM: how a page is served, how the
UI shell is assembled, where Livewire fits, and the patterns every module follows.
Companion docs: [roles-and-permissions.md](roles-and-permissions.md),
[center-scoping.md](center-scoping.md), `gls-crm-schema.md` and
`gls-crm-laravel-structure.md` at the project root.

---

## 1. The big picture

The backoffice is the **staff-only administration area** of the CRM. It is one of
two strictly separated surfaces:

| | Backoffice | Frontoffice |
|---|---|---|
| Audience | Employees / direction | Students & parents (future) |
| URL prefix | `/backoffice/…` | `/…` |
| Routes | `routes/backoffice.php`, names `backoffice.*` | `routes/frontoffice.php`, names `frontoffice.*` |
| Auth | Session `web` guard, login by email **or** username | Deferred |

`routes/web.php` only `require`s the two files. During the admin-first phase, `/`
redirects to the backoffice login.

**Stack:** Laravel 13 · PHP 8.4 · Livewire 4 (with its bundled Alpine.js) ·
Bootstrap 5 via the **PreSkool** admin theme (jQuery-based) · Vite for our own
JS/SCSS only · PostgreSQL (local dev and production). UI language is **French**
(`__('English key')` → `lang/fr.json`).

### One sentence per layer

```
Route (backoffice.php)
  → middleware: auth + permission:<module>.action
    → Livewire full-page component  (lists + modal CRUD — most screens)
      or thin controller            (read-only detail pages, tabbed page hosts)
        → Blade view wrapped in <x-backoffice.layout.app>
          → Domain actions / models do the real work
```

---

## 2. Serving a page: the two route styles

Look at [routes/backoffice.php](../routes/backoffice.php) — every module follows
one of two shapes:

**1. Livewire full-page component** — the standard for CRUD screens. The route
points directly at the component class; there is no controller:

```php
Route::get('employees', EmployeesIndex::class)
    ->middleware('permission:employees.view')->name('employees.index');
```

**2. Thin controller** — only for read-only detail pages (`students.show`,
`inscriptions.show`, `encaissements.show` receipt…), tabbed page hosts
(`SettingController`, `CaisseManagementController`, `DepenseManagementController`)
and the special `groups.archive` POST. Controllers are `final`, mostly invokable,
and contain **no business logic** — that lives in `app/Domain`.

Deliberate absences: money records (encaissements, dépenses, remboursements,
transferts) have **no destroy routes**, and groups have **no delete** — they are
archived via `Group::archiverCommeTermine()` only.

---

## 3. The UI shell (Blade anonymous components)

Every authenticated page renders inside the same shell, adapted once from the
PreSkool theme and never duplicated:

```blade
<x-backoffice.layout.app :title="__('Students')">
    <x-backoffice.layout.page-header
        :title="__('Students')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Students') => null]" />
    {{-- page content --}}
</x-backoffice.layout.app>
```

[app.blade.php](../resources/views/components/backoffice/layout/app.blade.php)
assembles: `head` (theme CSS + `@vite` for our code) → `header` (top bar with the
context switchers and profile menu) → `sidebar` → the page slot → `footer` →
`theme-settings` (appearance panel) → `toasts` → `scripts` (the static jQuery
theme stack). Auth/error pages use `<x-backoffice.layout.guest>`, printables
`<x-backoffice.layout.print>`.

Reusable building blocks (all anonymous components under
`resources/views/components/backoffice/`):

- `ui.*` — card, table, badge, alert, modal, button, empty-state, pagination,
  action-menu, sexe-icon.
- `forms.*` — input, select, **select2**, textarea, phone-input/phone-country,
  error. All of them render validation errors themselves.
- `shared.*` is reserved for genuinely cross-area components.

New pages are built by **copying** a demo page from
`resources/views/theme-reference/preskool/` (252 reference views — never edited,
never deleted, never routed) and adapting the copy: layout component,
`asset('assets/preskool/…')` paths, `__()` strings, named routes.

---

## 4. Livewire — where the dynamics live

### 4.1 When Livewire is used (and when not)

Livewire is used **only** where server-side dynamics are needed: searchable /
paginated / filtered lists, forms with validation, modal CRUD, payment entry,
dependent selects, uploads, dashboard widgets, tab content with mutations.
Static UI (layouts, navigation, info pages) is plain Blade. Frontend-only state
(dropdown open/closed, tabs, show/hide) is Alpine.

Classes live in `App\Livewire\Backoffice\<Module>\…`, views in
`resources/views/livewire/backoffice/…`.

### 4.2 The standard module: a Livewire index with modal CRUD

Almost every module is **one full-page component**: a server-side list plus an
add/edit **modal** — no separate create/edit pages. Current roster:

| Component | Route | Notes |
|---|---|---|
| `Employees\EmployeesIndex` | `backoffice.employees.index` | Creating an employee auto-creates its login (Observer); one-time credentials shown after save |
| `Students\StudentsIndex` | `backoffice.students.index` | Photo upload (media `photo` collection), niveau/center filters; detail page via controller |
| `Groups\GroupsIndex` | `backoffice.groups.index` | Assigns catalog fees via `group_frais` (per-group montant + due date); archive, never delete |
| `Inscriptions\InscriptionsIndex` | `backoffice.inscriptions.index` | Student+group selects, fee lines in one transaction, scoped to the active year |
| `Encaissements\EncaissementsIndex` | `backoffice.encaissements.index` | Money — goes through Domain actions, never deleted |
| `Users\UsersIndex`, `Roles\*`, `ManageAuthorization` | `backoffice.users.*`, `backoffice.roles.*` | Authorization module |
| `Profile\ProfilePage` | `backoffice.profile` | Own info + password, no permission gate |

Tabbed pages reuse the same idea, one component per tab, hosted by a thin
controller view: **Paramètres** (`Settings\{Etablissements,AnneesScolaires,Salles,Frais}Tab`),
**Gestion de la caisse** (`Caisses\CaissesIndex`, `Caisses\CaisseJournal`,
`CaisseTransfers\CaisseTransfersIndex`), **Gestion des dépenses**
(`Depenses\DepensesIndex`, `Remboursements\RemboursementsIndex`,
`TypesDepenses\TypesDepensesIndex`).

### 4.3 Anatomy of an index component

[EmployeesIndex.php](../app/Livewire/Backoffice/Employees/EmployeesIndex.php) is
the canonical example. The recurring pieces:

```php
final class EmployeesIndex extends Component
{
    use AuthorizesRequests;   // $this->authorize(...)
    use WithPagination;       // server-side pagination ($paginationTheme = 'bootstrap')
    use WithFileUploads;      // when the modal has an upload
    use WithCenterContext;    // shared concern: center scoping helpers
    use WithPhoneCountry;     // shared concern: phone-country fields + rules

    public string $search = '';        // wire:model.live from the search box
    public bool $showModal = false;    // drives the Alpine modal
    public ?int $editingId = null;     // null = create, id = edit
    // ... public properties = the modal form fields

    protected function rules(): array { /* Livewire validation, model constants via Rule::in() */ }

    public function mount(): void
    {
        $this->authorize('employees.view');   // defense in depth (route already has middleware)
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'employees.update' : 'employees.create');
        $data = $this->validate();
        // persist (Domain action / model), close modal, dispatch toast
    }
}
```

Key conventions, in order of importance:

1. **Authorize in `mount()` AND in every mutation method.** The route
   `permission:` middleware is the first gate, the component re-checks — never
   rely on hiding a button with `@can`.
2. **Filters reset pagination** (`updatingX() → resetPage()`).
3. **Validation uses model constants** (`Rule::in(Employee::CATEGORIES)`) —
   statuses/levels are VARCHARs validated in code, not lookup tables.
4. **Center scoping** comes from `CenterAccessService` (via the
   `WithCenterContext` concern): users without `centers.access-all` only ever see
   their own center's records.
5. **Money and group-lifecycle mutations call Domain actions**
   (`EnregistrerEncaissement`, `Group::archiverCommeTermine()`…) — a Livewire
   component never moves money or flips a group status with a raw update.

### 4.4 The modal pattern (Alpine-driven, not Bootstrap-JS)

Modals are opened/closed by a **Livewire property entangled with Alpine** — we do
not use Bootstrap's JS modal API:

```blade
<div x-data="{ show: @entangle('showModal') }" x-show="show" ...>
```

- Server side: `openCreateModal()` / `openEditModal($id)` fill the form
  properties and set `$showModal = true`; `save()` sets it back to `false`.
- Client side: Alpine reacts instantly to `show` — the modal animates without a
  round-trip, but its state stays authoritative on the server.
- Our modals sit at z-index 1060; that's why the Select2 bridge attaches its
  dropdown to the modal (see §6).

### 4.5 Events: toasts and the working context

Components talk to each other and to the JS layer with Livewire events:

- **Toasts** — every create/update/delete dispatches a `toast` event (or flashes
  to session for full redirects); `<x-backoffice.layout.toasts />` +
  `showBackofficeToast()` in [app.js](../resources/js/backoffice/app.js) render
  it as a Bootstrap toast.
- **Context** — the top-bar `Context\ContextSwitcher` persists the selected
  academic year + center into `App\Services\Context\CurrentContext`
  (session-backed singleton, available in all views as `$context`) and
  dispatches `context-changed`. Context-aware widgets refresh live:

```php
#[On('context-changed')]
public function refreshStats(): void { ... }   // e.g. Dashboard\DashboardStats
```

  Every list is implicitly scoped through this context (e.g. inscriptions are
  filtered to the active year). Center switching is permission-aware: only
  `centers.access-all` users can change center or pick "Tous les centres".

### 4.6 Shared concerns

Cross-component behaviour is factored into traits under
`App\Livewire\Backoffice\Concerns\`:

- `WithCenterContext` — center options + scoping tied to `CenterAccessService`.
- `WithPhoneCountry` — the phone-country select fields and their rules, merged
  into `rules()` with `...$this->phonePaysRules()`.

### 4.7 File uploads

Uploads go through Livewire (`WithFileUploads`) into **spatie/laravel-medialibrary**
collections on the dedicated `media` disk. Public URLs are always
`/media/<8-char-uuid>/<file>` (custom `ShortUuidPathGenerator`) — never
`/storage/…`. Existing collections: `Student`/`Employee` `photo`,
`Student.documents`, `Depense.justificatifs`.

---

## 5. Alpine.js — the one rule that keeps breaking projects

Alpine is **bundled with Livewire 4 and auto-injected**. Therefore, in this repo:

- never `npm install alpinejs`, `import Alpine`, `Alpine.start()`, or add an
  Alpine CDN script — a second instance breaks everything;
- never add `@livewireScripts` / `@livewireStyles` manually;
- custom Alpine components are registered on the `alpine:init` event using
  `window.Alpine` (see `glsSelect2` in app.js).

Alpine's job here: modal show/hide, dropdowns, tabs, small client-only state —
anything that shouldn't need a server round-trip.

---

## 6. JavaScript & theme plugins

Two JS layers load on every backoffice page:

1. **Static theme stack** (`<x-backoffice.layout.scripts />`): jQuery, Bootstrap
   bundle, moment, daterangepicker, select2, datetimepicker, feather,
   slimscroll, PreSkool `script.js`. Vendor files under
   `public/assets/preskool/` — never edited.
2. **Our Vite module** ([resources/js/backoffice/app.js](../resources/js/backoffice/app.js)),
   loaded deferred so the theme globals already exist.

Rules that make jQuery plugins and Livewire coexist:

- All initialisation goes through `initializeBackofficePlugins()`, which runs on
  `DOMContentLoaded` **and** `livewire:navigated` (Livewire replaces DOM on
  navigation). Every initialiser guards against double-init.
- When a plugin must own its DOM (Select2 inside a Livewire component), the
  element is wrapped in `wire:ignore` **with a comment explaining why**. The
  `<x-backoffice.forms.select2>` component + the `glsSelect2` Alpine bridge keep
  the widget and the entangled Livewire property in sync in both directions —
  this is why **every CRUD dropdown uses Select2**, not native selects.
- Appearance (dark mode, sidebar, layout) is handled by our
  `resources/js/backoffice/theme.js` + the theme-settings component, persisted in
  localStorage. The original `theme-script.js` is reference-only and not loaded.
- Page-specific plugin assets go through `@push('styles')` / `@push('scripts')`.

**DataTables:** large lists are always Livewire server-side (search, sort,
pagination) rendered with `<x-backoffice.ui.table>` in the PreSkool table design.
Client-side DataTables is only tolerated for tiny static demo tables.

---

## 7. Authorization & auth (summary)

Full details in [roles-and-permissions.md](roles-and-permissions.md) and
[authorization-architecture.md](authorization-architecture.md). What matters
day-to-day:

- spatie/laravel-permission v8, `web` guard, teams off. The single source of
  truth is `App\Support\Authorization\PermissionRegistry` (61 `module.action`
  permissions + role matrix); re-seed with `RolesAndPermissionsSeeder` after
  changing it.
- **Server-side everywhere**: `permission:` middleware on routes, policies on
  resource controllers, `authorize()` in Livewire `mount()` + every mutation.
  `@can` in Blade is UI sugar only. Check permissions, never role names.
- **Center scoping is part of authorization**: policies combine the permission
  with `CenterAccessService` (see [center-scoping.md](center-scoping.md)).
- `super-admin` bypasses everything via `Gate::before` and is heavily protected.
- Login accepts **email or username** (employees get an auto-generated login via
  `EmployeeObserver` → `EmployeeCredentialService`; no public registration).
  Deactivated users (`is_active = false`) cannot sign in. Password reset is
  backoffice-scoped. Local dev: `admin@gls.test` / `password`.

---

## 8. The Domain layer underneath

HTTP-layer code (controllers, Form Requests, Livewire) is thin; business rules
live in `app/Domain/<Module>/{Actions,DTOs,Enums,Services}`. Non-negotiable
invariants the backoffice UI must respect (full list in `gls-crm-schema.md`):

- `caisses.solde` is application-maintained: every money movement goes through a
  Domain action in **one transaction** (`EnregistrerEncaissement`,
  `EnregistrerDepense`, `EnregistrerRemboursement`, the two-step
  `DemanderTransfertCaisse` / `ValiderTransfertCaisse` — self-validation refused).
- Money records are **never deleted or re-amounted**; corrections are
  compensating entries.
- Groups are **never deleted** — `Group::archiverCommeTermine()` archives with a
  `groups_historique` snapshot.
- `reference` codes (EMP-/ETU-/INS-/ENC-/DEP-…) come from
  `Domain\Shared\Support\ReferenceGenerator`, never typed by users.
- Fraud-relevant models carry the activitylog trait — keep it on any new
  money-touching model.

---

## 9. Adding a new backoffice module — checklist

1. **Permissions**: add `<module>.<action>` entries to `PermissionRegistry`,
   re-run `db:seed --class=RolesAndPermissionsSeeder`.
2. **Route**: Livewire index in `routes/backoffice.php` behind
   `permission:<module>.view` (+ controller `show` route if a detail page is
   needed). French resource slugs, `backoffice.*` names.
3. **Component**: `App\Livewire\Backoffice\<Module>\<Module>Index` — pagination,
   search/filters with `resetPage()`, Alpine modal (`@entangle('showModal')`),
   `authorize()` in `mount()` and every mutation, Domain actions for anything
   with rules, toast on success.
4. **View**: copy the closest `theme-reference/preskool/` page, adapt it inside
   `<x-backoffice.layout.app>`, use `ui.*`/`forms.*` components, Select2 on all
   dropdowns, every visible string in `__('…')` + French translation in
   `lang/fr.json`.
5. **Tests**: `tests/Feature/Backoffice/<Module>/` — allowed **and** denied
   (403) cases, center scoping, invariants.
6. **Quality gate** (CLAUDE.md §14): `artisan test`, `npm run build`,
   `route:list`, render the page (desktop + mobile + dark mode), no duplicate
   Alpine, `theme-reference/` untouched.

---

## 10. Everyday commands

Always PHP 8.4 (`C:\php84\php.exe`), always from the project root:

```powershell
C:\php84\php.exe artisan serve          # http://127.0.0.1:8000/backoffice/login
C:\php84\php.exe artisan test
C:\php84\php.exe artisan route:list
C:\php84\php.exe artisan migrate:fresh --seed   # full demo dataset (see DatabaseSeeder)
npm run dev                              # Vite watch for our JS/SCSS
```

Seeding order (see `DatabaseSeeder`): roles & permissions → admin user →
referential data (years, 7 centers, rooms) → expense types → fee catalog → demo
role users → demo academic data → demo finance movements. All idempotent.
