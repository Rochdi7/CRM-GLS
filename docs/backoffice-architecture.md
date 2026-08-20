# How the Backoffice Works

A practical guide to the admin area of the GLS CRM: how a page is served, how
the UI shell is assembled, and the patterns every module follows. As of
Phase 11 (`docs/phase-11-final-verification.md`), the backoffice is **100%
Inertia + React + TypeScript** — Livewire has been fully removed (no
`livewire/livewire` package, no `app/Livewire/`, no
`resources/views/livewire/`, no Alpine, no jQuery plugins). This document
replaces an earlier Livewire-era version; see
`docs/inertia-react-migration-status.md` for the phase-by-phase migration
history if you need it.

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
| Frontend | Inertia + React + TypeScript | Blade (unchanged) |

`routes/web.php` only `require`s the two files. During the admin-first phase, `/`
redirects to the backoffice login.

**Stack:** Laravel 13 · PHP 8.4 · Inertia.js v3 + React 19 + TypeScript ·
Bootstrap 5 via the **PreSkool** admin theme (visuals/markup reused, no jQuery
plugins) · Vite (our own JS/TS + the React bundle) · PostgreSQL (local dev and
production). UI language is **French** (`__('English key')` → `lang/fr.json`
on the backend; the same translation strings are read via a shared Inertia
prop on the frontend).

### One sentence per layer

```
Route (backoffice.php)
  → middleware: auth + permission:<module>.action
    → Thin controller (App\Http\Controllers\Backoffice\…)
        — authorize(), validate via a Form Request, call Domain actions
        — Inertia::render('Backoffice/<Module>/Index', [...typed props])
      → resources/js/Pages/Backoffice/<Module>/Index.tsx (React)
          — BackofficeLayout.tsx shell (PreSkool theme, Bootstrap 5)
      app/Domain/<Module>/Actions do the real business rules
```

---

## 2. Serving a page

Every backoffice route in [routes/backoffice.php](../routes/backoffice.php)
points at a real controller — there is no Livewire route target anywhere.
Controllers are `final`, mostly resource-style (`index`/`store`/`update`/
`destroy`, plus `show` for read-only detail pages), and contain **no
business logic** — that lives in `app/Domain`.

```php
Route::get('employees', [EmployeeController::class, 'index'])
    ->middleware('permission:employees.view')->name('employees.index');
Route::post('employees', [EmployeeController::class, 'store'])
    ->middleware('permission:employees.create')->name('employees.store');
```

A typical `index()` action authorizes, runs a Domain read-model query (e.g.
`GetEmployeesList`), and renders the Inertia page with typed props:

```php
public function index(Request $request, GetEmployeesList $query): Response
{
    $this->authorize('viewAny', Employee::class);

    return Inertia::render('Backoffice/Employees/Index', [
        'employees' => $query->handle($request),
        'permissions' => [
            'create' => $request->user()->can('create', Employee::class),
            'update' => $request->user()->can('employees.update'),
            'delete' => $request->user()->can('employees.delete'),
        ],
    ]);
}
```

Deliberate absences: money records (encaissements, dépenses, remboursements,
transferts) have **no destroy routes**, and groups have **no delete** — they are
archived via `Group::archiverCommeTermine()` only.

---

## 3. The UI shell (React)

Every authenticated page renders inside the same layout component, never
duplicated:

```tsx
export default function Index({ employees, permissions }: Props) {
    return (
        <BackofficeLayout
            title={__('Employees')}
            breadcrumbs={[{ label: __('Dashboard'), href: route('backoffice.dashboard') }, { label: __('Employees') }]}
        >
            {/* page content */}
        </BackofficeLayout>
    );
}
```

`resources/js/Layouts/BackofficeLayout.tsx` assembles: the header (top bar
with the context switcher and profile menu) → `Sidebar.tsx` → the page
content → footer/theme toggle. It is the direct successor to the old Blade
`x-backoffice.layout.app` shell — same PreSkool markup and classes, React
owns the behavior instead of Blade+Livewire+Alpine.

Reusable building blocks (all under `resources/js/Components/`):

- `Tables/{DataTable,SearchInput,TableToolbar,Pagination,RowActions}.tsx` —
  list-page building blocks (table markup, debounced search, filter-bar
  layout, Bootstrap-styled pagination that navigates via Inertia
  `router.get(...)` with an optional jump-to-page control, row action
  dropdown). `DataTable` takes a `loading` prop — pass `useInertiaLoading()`
  so search/filter/pagination reloads dim the table and show a spinner.
- `Forms/{SelectField,PhoneField,PhoneCountry,SubmitButton,...}.tsx` — form
  inputs, all rendering their own validation error text. `SelectField` is a
  plain native `<select>` — **no Select2, no jQuery plugin anywhere**.
- `Modals/{Modal,ConfirmDialog}.tsx` — see §4.
- `Context/ContextSwitcher.tsx` — the top-bar year/center switcher.
- `resources/js/Lib/i18n.ts` (+ `Hooks/useTranslation.ts`) — the `t()`
  translation helper reading `lang/fr.json` (same dictionary as Laravel's
  `__()`); every user-visible React string goes through it.
- `resources/js/Hooks/useInertiaLoading.ts` — shared busy-state for any
  in-flight Inertia visit (global router listener).

New pages are still built by **copying** a demo page from
`resources/views/theme-reference/crm-gls/` or
`resources/theme-reference/crm-gls-react/` (never edited, never deleted,
never routed) and adapting the copy into a real `.tsx` page — see
`docs/react-theme-file-map.md` for the copy-and-adapt mapping already done.

---

## 4. Modals — React state, not Bootstrap JS or Alpine

Every module is **one list page with an add/edit modal** — no separate
create/edit pages. The modal is entirely React-controlled:

```tsx
<Modal show={showModal} title={editing ? __('Edit employee') : __('New employee')} onClose={closeModal} processing={form.processing}>
    {/* form fields */}
</Modal>
```

`resources/js/Components/Modals/Modal.tsx` owns open/close state, Escape,
backdrop-click, focus trap/restore, and body-scroll lock — the same
responsibilities Alpine + `@entangle` used to have, now entirely on the
client with no server round-trip needed to open/close. Visuals reuse the
existing Bootstrap 5 `.modal`/`.modal-dialog`/`.modal-backdrop` markup —
**never** `data-bs-toggle`/`data-bs-dismiss` or Bootstrap's own JS.
`ConfirmDialog.tsx` builds on `Modal` for delete confirmations.

Submission goes through Inertia's `useForm()` — `form.post(route(...))` /
`form.put(...)` — which posts to the real controller endpoint, gets
validated server-side by a Form Request, and either returns validation
errors (rendered inline per field) or a redirect + flash message.

### Current module roster

| Module | Route | Controller | Notes |
|---|---|---|---|
| Employees | `backoffice.employees.index` | `Backoffice\Employees\EmployeeController` | Creating an employee auto-creates its login (Observer); one-time credentials shown after save |
| Students | `backoffice.students.index` | `Backoffice\StudentController` | Photo upload (media `photo` collection), niveau/center filters; detail page via `show()` |
| Groups | `backoffice.groups.index` | `Backoffice\GroupController` | Assigns catalog fees via `group_frais` (per-group montant + due date); archive, never delete |
| Inscriptions | `backoffice.inscriptions.index` | `Backoffice\InscriptionController` | Student+group selects, fee lines in one transaction, scoped to the active year |
| Encaissements | `backoffice.encaissements.index` | `Backoffice\EncaissementController` | Money — goes through Domain actions, never deleted |
| Dépenses / Remboursements / Types de dépenses | `backoffice.depenses.*` | `Backoffice\{Depense,Remboursement,TypeDepense}Controller` | Client-side React tabs on one Inertia page |
| Caisses / Caisse Transfers | `backoffice.caisses.*` | `Backoffice\{Caisse,CaisseTransfer}Controller` | "Ma caisse"/"Toutes les caisses" tabs, two-step transfers |
| Users / Roles / Authorization | `backoffice.{users,roles}.*` | `Backoffice\Users\*`, `Backoffice\Roles\RoleController` | Authorization module |
| Settings | `backoffice.settings` | `Backoffice\SettingController` | One React panel per tab (Établissements, Années scolaires, Salles, Frais) |
| Profile | `backoffice.profile` | `Backoffice\ProfileController` | Own info + password, no permission gate |

---

## 5. Authorization in React pages

1. **Real enforcement is 100% server-side** — the route's `permission:`
   middleware, plus `$this->authorize(...)` (policy) in the controller
   action, checked again on every mutation. This never changes regardless
   of what the frontend does.
2. **Client-side permission props are UI convenience only.** Controllers pass
   a `permissions: { create, update, delete }` shape (or the broader
   `auth.permissions: string[]` shared prop used for nav filtering,
   `resources/js/Config/backofficeNavigation.ts`) so React can hide/disable
   a button — never a real gate. A user could tamper with the client and
   still hit a real 403 from the backend.
3. **Center scoping** comes from `CenterAccessService`, applied inside the
   policy/query, not the frontend.
4. **Money and group-lifecycle mutations call Domain actions**
   (`EnregistrerEncaissement`, `Group::archiverCommeTermine()`…) from the
   controller — never a raw model update.

---

## 6. Toasts and the working context

- **Toasts/flash messages** — every create/update/delete redirects with a
  flash message (`session('success', ...)`); the shared `flash`  Inertia prop
  renders it as a Bootstrap toast on the frontend.
- **Context** — the top-bar `ContextSwitcher.tsx` posts to
  `backoffice.context.update` (`ContextController@update`), which persists
  the selected academic year + center into
  `App\Services\Context\CurrentContext` (session-backed singleton, shared to
  every Inertia page via the `context` prop —
  `App\Http\Middleware\HandleInertiaRequests`). Because context lives in the
  session rather than client state, every subsequent page load/reload
  reflects it automatically — there's no client-side event bus needed.
  Center switching is permission-aware: only `centers.access-all` users can
  change center or pick "Tous les centres".

---

## 7. File uploads

Uploads go through a normal multipart form (Inertia's `useForm()` handles
`FormData` automatically when a field is a `File`) into
**spatie/laravel-medialibrary** collections on the dedicated `media` disk.
Public URLs are always `/media/<8-char-uuid>/<file>` (custom
`ShortUuidPathGenerator`) — never `/storage/…`. Existing collections:
`Student`/`Employee` `photo`, `Student.documents`, `Depense.justificatifs`.

---

## 8. Authorization & auth (summary)

Full details in [roles-and-permissions.md](roles-and-permissions.md) and
[authorization-architecture.md](authorization-architecture.md). What matters
day-to-day:

- spatie/laravel-permission v8, `web` guard, teams off. The single source of
  truth is `App\Support\Authorization\PermissionRegistry` (61 `module.action`
  permissions + role matrix); re-seed with `RolesAndPermissionsSeeder` after
  changing it.
- **Server-side everywhere**: `permission:` middleware on routes, policies on
  resource controllers, `authorize()` in every controller action that
  mutates. Client-side permission props are UI sugar only (§5). Check
  permissions, never role names.
- **Center scoping is part of authorization**: policies combine the permission
  with `CenterAccessService` (see [center-scoping.md](center-scoping.md)).
- `super-admin` bypasses everything via `Gate::before` and is heavily protected.
- Login accepts **email or username** (employees get an auto-generated login via
  `EmployeeObserver` → `EmployeeCredentialService`; no public registration).
  Deactivated users (`is_active = false`) cannot sign in. Password reset is
  backoffice-scoped. Local dev: `admin@gls.test` / `password`.

---

## 9. The Domain layer underneath

HTTP-layer code (controllers, Form Requests) is thin; business rules
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

## 10. Adding a new backoffice module — checklist

1. **Permissions**: add `<module>.<action>` entries to `PermissionRegistry`,
   re-run `db:seed --class=RolesAndPermissionsSeeder`.
2. **Route + controller**: resource route in `routes/backoffice.php` behind
   `permission:<module>.view` (+ `show` route if a detail page is needed).
   French resource slugs, `backoffice.*` names. Controller authorizes,
   validates via a Form Request, calls Domain actions for anything with
   rules, returns `Inertia::render(...)`.
3. **Page**: `resources/js/Pages/Backoffice/<Module>/Index.tsx` — reuse
   `DataTable`/`SearchInput`/`TableToolbar`/`Pagination` for the list
   (pass `useInertiaLoading()` as `DataTable`'s `loading` prop), `Modal`
   for add/edit, native `SelectField` for dropdowns, every visible string
   through `t('…')` (`@/Lib/i18n`) + French translation in `lang/fr.json`.
4. **Tests**: `tests/Feature/Backoffice/<Module>/` — allowed **and** denied
   (403) cases, center scoping, invariants, Inertia page/prop assertions
   (`assertInertia`).
5. **Quality gate** (CLAUDE.md §14): `artisan test`, `npm run build`,
   `npx tsc --noEmit`, `route:list`, render the page (desktop + mobile + dark
   mode), `theme-reference/` untouched.

---

## 11. Everyday commands

Always PHP 8.4 (`C:\php84\php.exe`), always from the project root:

```powershell
C:\php84\php.exe artisan serve          # http://127.0.0.1:8000/backoffice/login
C:\php84\php.exe artisan test
C:\php84\php.exe artisan route:list
C:\php84\php.exe artisan migrate:fresh --seed   # full demo dataset (see DatabaseSeeder)
npm run dev                              # Vite watch (React + Frontoffice JS)
npx tsc --noEmit                         # TypeScript check
```

Seeding order (see `DatabaseSeeder`): roles & permissions → admin user →
referential data (years, 7 centers, rooms) → expense types → fee catalog → demo
role users → demo academic data → demo finance movements. All idempotent.
