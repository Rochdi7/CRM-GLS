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

`resources/views/theme-reference/crm-gls/` holds **permanent** copies of all 252
PreSkool views (categorized; see its README.md).

- **Never delete** a reference page — even after using it.
- **Never edit** reference pages (except an intentional theme re-sync).
- **Never route** to reference pages or use them directly in production.
- To build a page: **copy** the reference file into `backoffice/` or `frontoffice/`,
  adapt the copy (layout component, `asset('assets/crm-gls/…')` paths, `__()` strings,
  named routes), and leave the original untouched.
- Reuse theme CSS classes and markup patterns — do not invent a parallel design.
- The original download at `C:\Users\ASUS\Downloads\themeforest-…\preskool-v1.9.7\`
  must never be modified.

**PreSkool React theme reference** (added during the Inertia/React
migration, see `docs/inertia-react-migration-plan.md`):
`resources/theme-reference/crm-gls-react/` is a **reference-only** copy of
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
  Remboursements, Stock, etc.) as soon as it gets one. **The same rule covers
  the Centre *column*, not just the filter** — wrap both the `<th>` and its
  matching `<td>` in `{!centerLocked && …}` (done for Employees, Students,
  Users, GroupsHistorique, Import, Settings/SallesPanel, Stock), and remember to make
  any hard-coded `colSpan` on the empty-state row conditional too. Detail/Show
  pages are exempt: there the centre is an attribute of the single record being
  viewed, not a redundant repeat of the active context (see `Groups/Show.tsx`).
- **Tables display in UPPERCASE.** `.table thead th` and `.table tbody td`
  carry `text-transform: uppercase` in `resources/js/app.css`, so headers and
  cell values render in caps ("rochdi karouali" shows as "ROCHDI KAROUALI")
  however the data was typed. This is deliberately a DISPLAY-ONLY CSS
  transform — the stored value and server-side search/sort keep their original
  casing. Never uppercase in a query, a Domain action, or a React prop; that
  corrupts the data. Cells that must keep exact casing (emails, usernames,
  raw references) get `className="text-normal-case"`; inputs, selects and
  `.dropdown-menu` are already excluded by the stylesheet.
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
  ⚠ **The balance itself moves ONLY through
  `App\Domain\Finance\Support\CaisseLedger`** (`credit()` / `debit()`).
  Never `increment('solde')` / `decrement('solde')` / a raw update on that
  column: those are raw SQL, fire no Eloquent events, and leave the movement
  **invisible to the audit journal** — which is exactly the fraud hole this
  replaced. The ledger records solde avant → montant → solde après plus the
  source record, and transfers must journal BOTH legs. See
  `docs/audit-journal.md` §5b.
- **One dirham = one `caisses` row — payment-method accounts per centre**
  (24/08/2026, `docs/caisse-comptes-methode-architecture.md`). `Caisse::TYPES`
  = Caissière / Externe (physical CASH) + TPE / Chèque / Virement (ONE account
  per centre, `etablissement_id` NOT NULL, no responsable — partial unique
  index + CHECK in PostgreSQL). Provisioned with the centre
  (`EtablissementObserver` → `CaisseProvisioner::compteMethodeFor()`), never
  by hand. Which row a record lands in is decided ONLY by
  `Domain\Finance\Support\CaisseResolver`:
  - **encaissement**: Espèces → the cashier's own till; TPE/Chèque/Virement →
    the ACTIVE-CONTEXT centre's account for that method (fallback: the
    agent's primary centre; the legacy import passes its batch centre). The
    physical till NEVER moves for a non-cash payment.
  - **dépense / remboursement**: ALWAYS the acting employee's physical till
    (`CaisseResolver::tillOf()`), whatever `methode_paiement` says — cash
    settles them (accounting rule confirmed 24/08/2026). Never route these
    by method. ONE exception, `CaisseResolver::forRemboursement()`: a refund
    linked to a payment funded by a chèque now **Rejeté** reverses the
    centre's Chèque account (that money never reached the till).
  - **transfers** move cash between cash accounts only
    (`DemanderTransfertCaisse` / `ValiderTransfertCaisse` refuse a method
    account; `caisseOptions()` never offers one).
  `caisse_id` is stored on the row and immutable, so cancellation / approval /
  avance application reverse or follow the SAME account with no re-derivation.
  **`encaissements.methode` is frozen after creation** like `montant`/
  `caisse_id` (`EncaissementController@update` refuses a different value).
  Nothing is derived on top of stored balances any more (the old
  `GetComptesCaisse::DERIVED_TYPES` counted a TPE payment twice) — never
  reintroduce a live-aggregated "account". Historical non-cash rows still in a
  till are re-homed by `php artisan caisse:recalculer-soldes` (dry-run by
  default, `--apply`, both legs journaled through `CaisseLedger`; refuses
  ambiguous rows unless `--ambiguous=caisse|student`). Never run it on
  production without reading its dry-run output first. Tests: `tests/Feature/Backoffice/Finance/ComptesMethodeTest.php`.
- **Till transfers are two-step and RECIPIENT-validated**: request (balances
  untouched) → acceptance by the **employee who owns the DESTINATION till**
  (balances move). The person whose caisse is about to be credited is the only
  one who confirms they received the money; holding `cash-transfers.validate`
  is not enough on its own, and self-validation is refused (the requester is on
  the source side). ⚠ **Super-admins do NOT bypass this** — `Gate::before` in
  `AppServiceProvider` explicitly excludes `CaisseTransfer@validate`
  (`NO_SUPER_ADMIN_BYPASS`, keyed by model because `SeancePolicy` also has a
  `validate` method). A super-admin approving a transfer into someone else's
  till would defeat the whole two-person control. Enforced in three places that
  must stay in sync: `CaisseTransferPolicy@validate`,
  `Domain\Finance\Actions\ValiderTransfertCaisse` (authoritative, also
  covers non-HTTP callers), and the `canValidate` flag
  `GetCaisseTransfersList` computes per row for the UI.
- **Dépenses are a REQUEST flow when approval is on** (default). Paramètres →
  Système « Validation des dépenses » (`AppSettings::EXPENSE_APPROVAL`,
  `system-settings.update`) switches it:
  - **ON** — `EnregistrerDepense` creates the dépense `En attente` and debits
    **nothing**; the money is on hold in the till. `ApprouverDepense`
    (`expenses.approve`) is the single moment `caisses.solde` moves for that
    expense; `RefuserDepense` moves nothing and keeps the row (audit trail —
    a refused dépense is never deleted, like every other money record).
  - **OFF** — legacy behavior: created `Approuvée`, till debited immediately.
  Turning the switch OFF never releases already-pending dépenses: they never
  debited anything, so they keep waiting for a decision. Both decisions re-read
  the row `lockForUpdate()` and refuse an already-decided expense, so a
  double-click can't double-spend. The Dépenses list reports **approved** money
  as `montantTotal` and pending money separately as `montantEnAttente` — never
  fold the two together.
- **Application-wide switches live in `app_settings`** (key/value), always read
  and written through `App\Support\Settings\AppSettings` — never queried
  directly, so the forever-cache stays coherent and every change is audited
  (`AppSetting` uses `Auditable`). Two storage forms per key:
  - **`valeur`** (text) — the scalar a switch reads as. `bool()`/`setBool()`
    with a fallback in `AppSettings::DEFAULTS`, so an unstored key behaves like
    a fresh install. This is what `EXPENSE_APPROVAL` uses.
  - **`options`** (jsonb) — the structured bag for settings needing more than
    one scalar (a list, a per-center override map, a threshold set).
    `options()`/`option('key', 'dot.path')`/`setOptions()`/`mergeOptions()`,
    with fallbacks in `AppSettings::OPTION_DEFAULTS`. Nothing reads it yet — it
    exists so a future setting is a constant + accessor, never another
    migration on a production table (§17). The two columns are independent: a
    key may carry both a scalar switch and structured config.
  A new switch is therefore **one constant + one accessor on `AppSettings`**
  (plus a `DEFAULTS`/`OPTION_DEFAULTS` entry) — never a new column.
- **Money records (encaissements/depenses/remboursements/transfers) are never
  deleted** — no destroy routes; corrections use compensating entries.
  `montant`/`caisse_id` are not editable after creation.
- **Every "read a balance, then write" money check runs INSIDE the
  transaction on a `lockForUpdate()` row** (audit 22/08/2026): the avance
  remaining (`AppliquerAvance`), the fee remaining and the cheque remaining
  (`EncaissementController@store`), the transfer status
  (`ValiderTransfertCaisse`, transfer cancel), the refunded avance
  (`EnregistrerRemboursement`). A guard evaluated before `DB::transaction`
  on the in-memory model is a double-click double-spend — never move one
  back out. Related invariants now enforced: an avance is applied only to a
  fee of ITS student, never beyond the fee's remaining due; refunds count as
  "used" on an avance (`Encaissement::montantUtilise()`); a refunded
  payment cannot be deleted (the till would be debited twice); a
  cheque-funded row keeps its cheque method/identity on edit, and a cheque
  that funded payments keeps its owner; the Caisse journal lists only rows
  that moved the till (no avance "apply" rows, only approved dépenses, only
  validated transfers). Lookup endpoints that hang off another module's
  record (student inscriptions/cheques/payments) are center-scoped with
  `CenterAccessService`, not with that module's `*.view` permission.
- **`reference` codes are system-generated** via
  `Domain\Shared\Support\ReferenceGenerator` (EMP-/ETU-/INS-/ENC-/DEP-/RMB-/TRF-…),
  never typed by users.
- **Creating an Employee auto-creates its login** (username + one-time password
  flashed to session) via `EmployeeObserver` → `EmployeeCredentialService`,
  **and assigns the default role for its catégorie**
  (`PermissionRegistry::defaultRoleFor()` — the single catégorie→role map,
  shared with `GlsStaffSeeder`) so the account is never role-less/403-locked.
  A later catégorie EDIT re-fires this **only when the login still has no
  role at all** (the « Autre » escape hatch: fixing the job title unlocks the
  account). In every path the default only fills a vacuum: `Autre` ⇒ no
  role, and a user holding ANY role is never touched — `categorie` never
  drives access at runtime (§16); changing access remains the Autorisations
  screen's job. Pass `user_id` explicitly to skip credential creation.
  No public registration ever.
- **`niveau` / `categorie` / all `statut` fields are plain VARCHARs** validated
  against model constants (`Student::NIVEAUX`, `Employee::CATEGORIES`,
  `Group::STATUTS`…) — deliberate; do not "fix" with lookup tables (see the
  Deliberate Simplifications table in gls-crm-schema.md before extending).
- **Audit log / Journal d'audit** — read `docs/audit-journal.md` before
  touching anything audit-related. `spatie/laravel-activitylog` **v5**
  (⚠ v5 namespaces: `Spatie\Activitylog\Models\Concerns\LogsActivity`,
  `Spatie\Activitylog\Support\LogOptions`; there is no
  `dontSubmitEmptyLogs()` — it is `dontLogEmptyChanges()`).
  Non-negotiable rules:
  - **Never add `LogsActivity` + a hand-written `getActivitylogOptions()` to a
    model.** Use `App\Models\Concerns\Auditable` instead — it applies
    `logAll()` (every column, no allowlist), excludes secrets, and takes its
    `log_name` from `App\Support\Audit\AuditLogRegistry`. A per-model
    `logOnly([...])` silently drops edits and is exactly the bug this replaced.
  - **A new audited model = `use Auditable;` + one line in
    `AuditLogRegistry::map()`.** Filters, labels and the finance scope all read
    from that registry, so they never drift from what is recorded.
  - **The journal page resolves ids to names at READ time** via
    `App\Support\Audit\AuditValueResolver` (FK → name, French column
    labels, `19/08/2026` dates, plumbing columns hidden on creations). A new
    FK column that should read as a name gets one line in its
    `FOREIGN_KEYS`/`FIELD_LABELS` map. Never resolve names INTO the stored
    row — the entry must stay the literal values written, or a later rename
    silently rewrites history.
  - **A model with a DB-default column must mirror it in `protected
    $attributes`** (all 13 `statut` models do). Otherwise a `create()` that
    omits the key leaves the model NULL while the row holds the default, and
    the next change is journalled as « avant : vide » — the trail then states
    a false previous value, which is worse than a missing one.
  - **The maintainer login (`AuditLogRegistry::DEVELOPER_EMAIL`) is HIDDEN
    from the journal page, never excluded from recording.** Its entries are
    written like everyone else's; only the read path filters them, with an
    « Inclure le compte technique » toggle to show them again. Never turn
    this into a write-time skip: an unrecorded privileged account is a
    permanent blind spot where money can move untraced.
  - **The journal is append-only.** `App\Models\Activity` throws on update
    and delete (model level, below every Gate — so it holds even for a
    super-admin), and `backoffice.audit-logs.index` is the ONLY route:
    never add store/update/destroy. Pruning is `activitylog:clean` only.
  - Entries carry IP, user-agent, HTTP method, URL, route name and a
    `causer_label` frozen at write time, stamped automatically in
    `Activity::creating()` — never add a second place that writes entries
    without them.
  - Auth events (login, logout, **failed logins**, lockout, password reset) go
    through `App\Listeners\LogAuthenticationActivity`. It is bound by
    Laravel's automatic listener discovery (one `handleX()` per event type) —
    ⚠ never ALSO register it via `Event::subscribe()` or a listen array, or
    every auth event is written to the journal twice. Add new sign-in paths
    there, not in a controller.
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
  session, not in client state. Center switching is governed by « Centres
  affectés » — a single-center employee cannot switch at all; one assigned
  to several may switch among *those* centers but ALWAYS works in exactly
  one at a time, defaulting to their PRIMARY center
  (`employees.etablissement_id`). **« Tous les centres » exists ONLY for
  global users** (super-admin, or a hand-granted `centers.access-all`):
  `CurrentContext::canPickAllCenters()` gates the option in the switcher
  and `setEtablissement(null)` is refused server-side for everyone else —
  never re-offer "all of mine" to multi-center employees. The header no
  longer has the language or notification dropdowns. Seed data:
  `ReferentialDataSeeder` (years 2025/2026-default + 2026/2027, 7 GLS
  branches, 2 rooms each). Tests: `tests/Feature/Backoffice/Context/`,
  `tests/Feature/Backoffice/Inertia/ContextUpdateTest.php`.
- **⚠ Context scoping is MANDATORY on every screen (current and future).**
  The top-bar switcher (year + center) must govern EVERYTHING the user sees
  and creates — a page that ignores it is a bug (23/08/2026 audit). Rules:
  - **Lists / stats / lookups**: records with an `annee_scolaire_id` chain
    (groups, inscriptions, séances, créneaux, encaissements via
    `fee.inscription`) filter on it. Date-carrying money records with no
    year FK (dépenses, remboursements, chèques, journal de caisse rows) use
    the active year's `date_debut`–`date_fin` as the **default date
    window** — `CurrentContext::anneeDateRange()` — which an explicit date
    filter on the page overrides. Students (list + dashboard) belong to the
    years they hold an inscription in, plus never-enrolled students
    (visible in every year: just created, about to be enrolled).
  - **Creates** inherit `etablissement_id`/`annee_scolaire_id` from the
    active context or from the parent record (group → inscription → séance),
    never from client input.
  - **Deliberate exceptions** (do not "fix"): employees/users (staff has no
    year), stock (physical inventory), the transfer-validation inbox (a
    pending transfer must never hide behind a year switch), and the caisse
    journal's header totals + `solde` (they reconcile with the till's
    running balance, which spans years — only the journal ROWS follow the
    year window).
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
  - Static theme assets: `public/assets/crm-gls/{css,js,img,fonts,icons,plugins}`
    → referenced with `{{ asset('assets/crm-gls/…') }}`. Copied from the theme's
    prebuilt `public/build`; treat as vendor files (don't edit).
  - Vite-managed (ours only): `resources/js/{backoffice,frontoffice}/`,
    `resources/scss/{backoffice,frontoffice}/` — loaded via `@vite`.
  - Theme SCSS source kept at `resources/scss/crm-gls/` for reference — **not**
    compiled, **not** imported (would duplicate Bootstrap).
- Languages: **French is the default UI language** (`APP_LOCALE=fr`; fallback en).
  All page content must display in French. User-facing strings always use
  `__('…')` with English source keys translated in `lang/fr.json` — when adding
  any new visible string, add its French translation in the same change.
  Framework messages (validation/auth/pagination) are French via
  `laravel-lang/common` (`lang/fr/*.php` — regenerate with
  `C:\php84\php.exe artisan lang:update` after adding packages).
  AR / EN / DE remain prepared in `lang/*.json` for the future locale switcher.

### Seeders — production only (no demo data exists)

`php artisan db:seed` (and therefore any deploy script) runs **only essential
reference data plus the real GLS staff**, all of it idempotent and safe to
re-run on a live database: `RolesAndPermissionsSeeder`,
`ReferentialDataSeeder` (7 GLS centers, rooms, academic years),
`AdminUserSeeder`, the locked catalogs — `TypeDepenseSeeder`,
`StockTypeSeeder`, `BookStockSeeder`, `FraisSeeder`, `BanqueSeeder`,
`MotifAnnulationSeeder` — and `GlsStaffSeeder`. **It creates no student,
group, inscription, stock quantity or money record.**

**`BookStockSeeder` is catalog data, not demo data**: it creates the 8 GLS
book titles (`BookStockSeeder::TITLES`) as ONE `stock_articles` row PER
center (stock is always per center — `etablissement_id` is never NULL for a
book), at `quantite` = 0 and with NO movement. Real quantities enter through
an « Entrée » movement in Gestion du stock. It is idempotent per
(title, center): re-running only adds titles to centers created since, never
touches an existing quantity. Never give it a starting quantity again (the
deleted version seeded 40 units per row — that is demo data).

⚠ **There is no demo/fake-data seeder any more, and none may be added.** The
whole `Demo*` family (`DemoSeeder`, `DemoData`, `DemoFinance`,
`DemoRoleUsers`, `DemoStock`, `DemoDashboard`, `DemoRecouvrement`,
`DemoLongueDuree`) was **deleted**: this seeder set is
production-only, so `db:seed` is safe to run on the live database without
picking through which classes are fake. The old `ALLOW_DEMO_SEED` env guard
and the `ETU-DEMO*` / `ETU-DASH*` / `ETU-RETARD*` / `EMP-ROLE*` reference
conventions are gone with them.

Never add a seeder that invents business records — not to `DatabaseSeeder`,
not as an opt-in class. Real students, real stock quantities and real money
movements come from the Import screen or from the app itself, never from a
seeder.

**`GlsStaffSeeder`** holds the real `@glszentrum.com` staff (names, catégorie,
téléphone, sexe and center assignments transcribed from
`GLS_Employes_Tous_Centres`). It is keyed on the e-mail address, so re-running
updates instead of duplicating, and it never overwrites an existing password,
an existing `sexe`, or a role granted by hand on the Autorisations screen.
References follow the same `EMP-001` format as
`ReferenceGenerator::make('EMP', 'employees')` — seeded employees must be
indistinguishable from ones created through the UI.

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
  adapted from `theme-reference/crm-gls/authentication/login.blade.php`.
- Credentials (AdminUserSeeder) come from `ADMIN_EMAIL`/`ADMIN_USERNAME`/
  `ADMIN_PASSWORD`. Locally they default to `rafik@glszentrum.com` / `password`
  (the CEO, Mohammed Rafik — the same account `GlsStaffSeeder` lists as
  super-admin; his brother Amine, `amine.rafik@`, is a separate account);
  on any other environment **`ADMIN_PASSWORD` must be set or the seeder
  refuses to run**, so a deploy can never publish a well-known password. A
  non-local admin is created with `must_change_password = true`.
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
Teams OFF. **An employee may work in SEVERAL centers, and must have at least
one** — the `employee_etablissement` pivot (`Employee::etablissements()`) is
the source of truth for ACCESS, while `employees.etablissement_id` remains its
PRIMARY center (where its Caisse lives). Always change both through
`Employee::syncEtablissements()`, never by writing either side directly; it
keeps the primary column stable when an edit merely adds a center. Enforcing
"at least one" lives in the Employees Form Requests
(`etablissement_ids` ⇒ `required|array|min:1`). Non-negotiable rules:

- **Authorization is server-side**: routes use `permission:` middleware,
  resource controllers use policies (`authorizeResource` — base Controller
  extends `Illuminate\Routing\Controller` for this) and authorize again in
  every mutation method. Any permission/role data passed to a React page as
  an Inertia prop (e.g. `CrudPermissions`, `auth.permissions`) is UI
  convenience only — it hides/disables affordances, never a real gate.
- **Check permissions, never role names** (`can('students.view')`). The only
  `hasRole()` usages allowed: the `Gate::before` super-admin bypass, the
  super-admin invariants in `UserAuthorizationService`, and the
  « Responsable de système » guard in the Employees Form Requests (below).
- **« Responsable de système » is the ONE catégorie that maps to
  `super-admin`** (`Employee::CATEGORIE_RESPONSABLE_SYSTEME`,
  `PermissionRegistry::defaultRoleFor()`). Unlike every other catégorie,
  `EmployeeObserver` grants it ALWAYS (on creation and on a catégorie
  change), even when the login already holds another role. Because that
  makes the catégorie a super-admin grant, only a super-admin may select it
  on the Employees form (`Store/UpdateEmployeeRequest` validate
  `categorie` with `hasRole(Role::SUPER_ADMIN)`) — never relax this, or any
  `employees.create` holder could mint a super-admin. No other catégorie
  may ever map to `super-admin`
  (`RolesAndPermissionsSeederTest::test_every_employee_category_has_a_matching_default_role`
  enforces it).
- **Single source of truth**: `App\Support\Authorization\PermissionRegistry`
  (102 `module.action` permissions, French labels, role matrix). New module ⇒
  add permissions THERE, re-run `db:seed --class=RolesAndPermissionsSeeder`
  (idempotent), protect routes, add allowed+denied tests.
- **One role per job title**: the 13 roles in `PermissionRegistry::roles()`
  mirror `Employee::CATEGORIES` one-for-one (except `Autre`, deliberately
  unmapped ⇒ no access). The names line up so granting is obvious, but
  `categorie` is NEVER consulted in an authorization check and changing an
  employee's job title does not change their access. The catégorie→role map
  is `PermissionRegistry::defaultRoleFor()` (single source shared by
  `EmployeeObserver`, `GlsStaffSeeder` and `auth:sync-default-roles` — the
  idempotent bulk repair for role-less logins after a restore/import); keep
  it in sync when a category or role is added.
- **⚠ Only super-admin deletes.** `PermissionRegistry::superAdminOnly()`
  lists what no role preset may hold, and `matrix()` FILTERS every preset
  through it — so writing a `*.delete` into a preset has no effect, and a
  new `*.delete` added to `grouped()` later is locked down automatically.
  Never "fix" a 403 on a delete by editing a preset: either the caller
  should be a super-admin, or the permission is deliberately delegated by
  hand to one user on the Autorisations screen. Same filter also reserves
  `expenses.approve`, `system-settings.*`, `banks.*`,
  `cancellation-reasons.*` and `cash-accounts.*`. `groups.archive` is NOT a
  delete (it snapshots to `groups_historique`) and stays with operational
  roles. See `docs/roles-and-permissions.md` §5.
- **Center scoping is part of authorization**: policies extend
  `App\Policies\Concerns\ResourcePolicy` and combine permission +
  `CenterAccessService` (`centers.access-all` ⇒ all centers; else
  every center the employee is assigned to via the `employee_etablissement`
  pivot, with `etablissement_id` as a fallback for legacy rows; NULL-center
  records are global). Center-scoped list queries must therefore match on the
  pivot too, not only the primary column — see `GetEmployeesList` and
  `GetUsersList` for the pattern.
- **⚠ « Centres affectés » is the ONE authority on center reach — never a
  role.** `centers.access-all` sits in `PermissionRegistry::superAdminOnly()`,
  so NO role preset may carry it (writing it into a preset has no effect —
  `matrix()` filters it out). A user reaches exactly the centers assigned on
  their employee form; the top-bar switcher offers exactly those. A
  cross-center job = more centers assigned on the employee form. A truly
  global non-super-admin account (rare) gets the permission hand-granted,
  one user at a time, on the Autorisations screen; super-admins see
  everything via `Gate::before`. Never "fix" a can't-see-other-centers
  complaint by editing a role preset — assign the centers instead.
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
- Never use `migrate:fresh` in production — **now enforced in code**:
  `AppServiceProvider` calls `DB::prohibitDestructiveCommands()` when
  `APP_ENV=production`, so `migrate:fresh`/`migrate:refresh`/`migrate:reset`/
  `db:wipe` are refused outright (even with `--force`). Added after the
  21/08/2026 incident where a `migrate:fresh --seed` on the VPS dropped all
  production tables past the interactive confirmation; recovery came from
  the nightly pg_dump. `deploy.sh` also snapshots the DB before every deploy
  (see docs/vps-deployment.md § Backups). Never remove this guard.
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

**Read models never call a per-row money accessor in a loop** (24/08/2026
pass): `InscriptionFee::montantPaye()`, `Encaissement::montantUtilise()`/
`montantRestant()` and `Cheque::montantRestant()` each run their own `SUM`
query and exist for the money ACTIONS (one locked row). A list/report that
needs the paid total uses `withSum('encaissements', 'montant')` or a
`GROUP BY` aggregate (`GetAnnualFraisSummary`, `GetRetardsList`,
`GetEncaissementsList`) — the dashboard once ran one query per fee of the
year. Heavy page props (option catalogs, stats) are closures so partial
reloads (`only: [...]`) skip them — `DashboardController`,
`EncaissementController@index`. Guarded by
`tests/Feature/Backoffice/Inertia/DashboardPerformanceTest.php` and
`tests/Feature/Backoffice/Finance/ListPerformanceTest.php` (query count must
not grow with row count). Server-side tuning (Redis cache/sessions, OPcache,
FPM, gzip, PostgreSQL memory) is `docs/vps-performance-tuning.md`.

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
