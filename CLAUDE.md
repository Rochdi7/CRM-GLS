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
`docs/rapports/ui/preskool-react-reference-inventory.md` for what was copied/excluded.

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
fully removed (Phase 11, `docs/rapports/migration-inertia/phase-11-final-verification.md`). Pages live
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
- **Filters are NEVER reset as a side effect (every list page, current and
  future).** They are cleared ONLY by the explicit « Réinitialiser les
  filtres » button (`Components/Tables/ResetFiltersButton.tsx`, `ti-filter-off`),
  wired through `TableToolbar`'s `onReset` / `resetActive` props from
  `Hooks/useFilterReset.ts` — which rebuilds every filter key at its default
  (pass `defaults` for keys whose default is not `''`: `perPage`, an open tab,
  a date window, `soldeFilter='restant'`) and reports whether anything
  deviates so the button disables when there is nothing to clear. The other
  half is server-side: a mutation must NOT answer with
  `redirect()->route('backoffice.x.index')` — that drops the whole query
  string and dumps the user on an unfiltered page 1. Use
  `Controllers\Backoffice\Concerns\RedirectsPreservingFilters::
  backToListPreservingFilters($request, $route, $extra)`, which rebuilds the
  redirect from the referer's query (falling back to the bare route when it is
  absent or points off-host — never redirect to a client-supplied URL), drops
  `page` (the row may have moved), and lets `$extra` override only what the
  action itself decides (e.g. `view=avance`). Reported 30/08/2026: a cashier
  who filtered the Avances tab to one student had to retype the filter after
  every single application. Tests:
  `tests/Feature/Backoffice/Finance/FilterPreservingRedirectTest.php`.
- **⚠ Clearing a filter must only ever WIDEN a result set.** If removing a
  value makes rows disappear, that is a bug, not scoping. In
  `GetEncaissementsList` the active-année window applies only when BOTH date
  fields are empty, and an **avance is exempt from it by ROW**
  (`orWhereNull('inscription_fee_id')`) — NOT by which tab is open. Keying that
  exemption on `$view !== 'avance'` meant an avance was still date-windowed on
  the Encaissements tab, so clearing « Date de fin » emptied a student's list
  (5 200 MAD → 0.00 MAD). An avance is money received and not yet allocated, so
  it stays listed whatever its date (§11 « Deliberate exceptions »); a
  fee-attached row of another year does correctly drop out. When adding any
  année/date window to a list query, check what the user sees after CLEARING
  the filter.
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
  (which itself is only selectable by super-admins). Never gate this on a role check in the component — reuse the
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
- **Centre dimension on the ledger — ONE till per employee, forever**
  (01/09/2026, after Fatine Barnicha's till sat in GLS Online with 2 200 DH
  while she cashed in Rabat). The multi-caisse-per-centre model was proposed
  and REJECTED: an employee keeps exactly ONE « Caissière » till
  (`caisses_une_caissiere_par_employe` — never drop it). Instead, EVERY
  `CaisseLedger` entry stamps `etablissement_id` in its jsonb properties (no
  schema change), so one till breaks down per centre from the ledger alone:
  - **encaissement** → the payment's own `etablissement_id`; **suppression**
    → the reversed payment's centre;
  - **remboursement lié** → the ORIGINAL payment's centre (a refund reverses
    a financial context — never the student's CURRENT centre, they may have
    moved); **non lié** → active context, fallback agent primary;
  - **dépense** → group centre (Paiement prof) else active context else
    creator primary; at APPROVAL → group centre else CREATOR's primary,
    never the approver's context (approvers work from « Tous les centres »);
  - **transfert** → each leg stamps its own caisse's centre (a cross-centre
    transfer is an explicit, journaled centre movement).
  Query the entries with `event = 'solde_movement'` — the Caisse model's own
  Auditable entries share `log_name = 'caisse'`. Historical entries lack the
  key: read-time fallback (« Centre du compte »), NEVER a backfill.
  `caisses.solde` stays the only authoritative total; per-centre figures are
  always derived. Three companion rules: (1) **a profile edit never moves a
  caisse** — `Employee::syncEtablissements()` re-points only
  `employees.etablissement_id`, and `EmployeeController@update` flashes a
  `warning` when the till's centre now diverges; (2) re-homing a caisse goes
  ONLY through the existing `CaisseController@update` path, which refuses
  once the caisse carries any movement (`hasMovements`, stricter than
  solde = 0) — never add a weaker action; (3) **`caisses.etablissement_id`
  keeps its stored meaning** — never reinterpret it at read time (e.g. via
  the responsable's primary centre) to make a screen look right. Tests:
  `tests/Feature/Backoffice/Finance/CentreDimensionLedgerTest.php`.
- **⚠ TOUS les écrans finance ventilent par centre — la fiche caisse comprise**
  (09/09/2026). Une caissière n'a qu'UNE caisse à vie mais encaisse pour
  plusieurs centres, donc la part d'un centre dans une caisse se dérive
  TOUJOURS via la source unique `Domain\Finance\Support\VentilationCentre`
  (`soldeDuCentre()` / `scopeDepensesAuCentre()`). Quatre écrans la partagent
  désormais : « Comptes de caisse » (`GetComptesCaisse`), « Caisse globale »
  (`GetCaisseGlobale`), le journal (`GetCaisseJournal`) et **la fiche d'une
  caisse (`GetCaisseDetails`)** — cette dernière était la seule oubliée : elle
  affichait le `caisses.solde` ENTIER au-dessus de listes entières, si bien
  que la caisse de Yassine Ouled Laghzal annonçait 72 740,00 DH sur sa fiche
  et 1 300,00 DH dans « Comptes de caisse » AU MÊME MOMENT, avec des paiements
  Casablanca / Kénitra / Online mêlés. Deux écrans du même argent qui se
  contredisent : l'utilisateur ne peut plus savoir lequel croire. Trois bornes :
  (1) **un total se calcule sur les MÊMES colonnes que les lignes qu'il
  chapeaute** — solde ventilé ⇒ listes ventilées, sinon la page se contredit
  elle-même ; (2) **un transfert n'a pas de centre propre** (il déplace de
  l'argent PHYSIQUE) : il est imputé au centre de rattachement de la caisse,
  donc il DISPARAÎT des listes d'un autre centre, exactement comme
  `VentilationCentre::transfertsDuCentre()` l'exclut du solde ; (3) sur
  « Tous les centres » **rien n'est ventilé** — `caisses.solde` reste
  l'autorité (CaisseLedger) et la somme des parts y retombe. Un écran qui
  affiche un solde ventilé doit le DIRE (`ventileParCentre`), sinon le chiffre
  se lit comme le total du compte. Le modal de transfert est l'exception
  assumée : il montre le solde ENTIER, parce que `DemanderTransfertCaisse`
  valide contre lui. Tests :
  `tests/Feature/Backoffice/Finance/CaisseVentilationCentreTest.php`.
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
  **`encaissements.methode` is CORRECTABLE, but only as a money movement**
  (01/09/2026). It is not a label: it decided which account was credited, so
  the ONLY way to change it is
  `Domain\Payments\Actions\RequalifierMethodeEncaissement`, which in ONE
  transaction debits the old caisse, credits the one `CaisseResolver` returns
  for the new method, and updates `caisse_id` — **both legs journaled through
  `CaisseLedger`**. Never write the column directly: that leaves the money in
  one account and the label on another, with nothing in the journal to explain
  it. `montant` and `caisse_id` stay non-editable by hand. The permission is
  `payments.update-method`, held by the five management roles + super-admin
  (`$managementEdits`) — the front office keeps `payments.update` for the note
  and cheque identity but never moves money (§16). The new caisse's centre is
  the ENCAISSEMENT's, never the corrector's active context. Refused on an
  avance-application row (it credited no caisse), a tracked-cheque payment,
  and an already-refunded payment; `GetEncaissementsList` carries the same
  rule to the UI as `methodeRequalifiable` rather than letting the page
  re-derive it.
  Nothing is derived on top of stored balances any more (the old
  `GetComptesCaisse::DERIVED_TYPES` counted a TPE payment twice) — never
  reintroduce a live-aggregated "account". Historical non-cash PAYMENTS still
  in a till are re-homed by `php artisan caisse:recalculer-soldes` (dry-run by
  default, `--apply`, both legs journaled through `CaisseLedger`; refuses
  ambiguous rows unless `--ambiguous=caisse|student`) — **encaissements
  only**: it never touches a dépense or a remboursement (they always settle
  from the till, see above; the 24/08/2026 audit removed a branch that moved
  them). Never run it on production without reading its dry-run output
  first. `php artisan caisse:verifier-coherence` is the READ-ONLY auditor
  (mis-routed rows, duplicate/missing accounts, tills per employee,
  solde vs journaled movements; `--strict` fails on warnings) — run it
  before and after. Two more PostgreSQL guards (in `create_caisses_table` —
  **every schema change lives in the `create_*` migration of its table; no
  alter migrations. Production, which already ran the create files, gets the
  same change as a one-off idempotent SQL applied by hand, see
  `docs/production-schema-patches.sql`**): `caisses.solde` NOT NULL and ONE « Caissière »
  till per employee (partial unique index); the physical till is always
  `Employee::till()` / `CaisseResolver::tillOf()` — never
  `caisses()->first()`, which also returns an « Externe » safe the employee
  is responsable of. Tests: `tests/Feature/Backoffice/Finance/ComptesMethodeTest.php`,
  `FinancialInvariantsAuditTest.php`.
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
- **⚠ A dépense is never approved beyond what its till holds** (09/09/2026,
  after five dépenses totalling 22 500 DH were approved on a till that had
  received 15 250 DH — El Mehdi Bakhach's till at -7 250 DH, one of them,
  DEP-014, a copy of DEP-012). `Domain\Finance\Support\GardeSoldeCaisse`
  locks the till row FOR UPDATE inside the approval transaction and refuses
  when `montant > solde` (equality is allowed — emptying a till to 0,00 is
  legitimate, same bound as `ValiderTransfertCaisse`). Both money-moving
  paths call it: `ApprouverDepense` and the approval-OFF branch of
  `EnregistrerDepense` (that branch IS an approval: the row is born
  `Approuvée` and debits at once). The till checked is ALWAYS
  `depenses.caisse_id`, the stored row — the same one `CaisseLedger` debits
  in the same call — never a till re-derived from the approver's context, so
  a multi-centre super-admin can never "borrow" another centre's balance.
  Two concurrent approvals on one till serialize on that row lock; the
  second re-reads the reduced balance. The guard runs INSIDE
  `DB::transaction` — moved outside, it proves nothing (§11 above).
  A dépense keyed twice is undone by **compensating entry**, never deleted:
  `Domain\Expenses\Actions\AnnulerDepense` credits the till back through
  `CaisseLedger` (stamping the centre of the ORIGINAL debit), sets
  `statut = Annulée` (`Depense::STATUT_ANNULEE` — every read model already
  sums `Approuvée` only, so the money drops out of the totals without any
  of them learning the concept) and appends `[ANNULÉE] … CORRECTION-<ref>-DUPLICATE`
  to the note. No screen calls it: the operator runs
  `php artisan depenses:annuler-doublon DEP-014 DEP-012` (dry-run by
  default, `--apply`), which names BOTH rows and refuses unless every proof
  matches (same till, amount, date, description, both `Approuvée`, keyed
  within 60 min, exactly one journaled debit, no prior credit) — it never
  guesses which row is the duplicate, and it has no `--force`. Idempotent on
  the stable `correction` reference in the ledger AND the statut.
  **Depuis l'écran** : « Annuler » sur la ligne (`expenses.cancel`,
  `superAdminOnly()`, motif OBLIGATOIRE) appelle la même action —
  `DepenseController@cancel`. Trois choses indissociables : (1) les abilities
  `cancel` ET `update` de `Depense` sont dans
  `AppServiceProvider::NO_SUPER_ADMIN_BYPASS` — **sans elles `Gate::before`
  accorde tout au super-admin**, donc « on n'annule qu'une dépense
  approuvée » et « une dépense refusée/annulée ne se modifie plus »
  deviennent injoignables pour les seuls comptes qui peuvent cliquer (deux
  clics recréditeraient la caisse deux fois) ; la map porte désormais une
  LISTE de modèles par ability, `cancel` en couvrant deux (Remboursement +
  Depense). (2) **Une ligne annulée reste affichée**, barrée, badgée
  « Annulée » avec son motif sous le badge, dans les DEUX onglets — Dépenses
  ET Paiements prof, qui a dû recevoir la colonne Statut qu'il n'avait jamais
  eue : ce sont les mêmes lignes, et sans elle un montant rendu à la caisse
  passait pour un paiement vivant (signalé le 09/09/2026 sur DEP-014).
  (3) Le motif est requis : c'est ce que le journal conserve pour expliquer
  pourquoi de l'argent est revenu. Tests : `AnnulerDepenseEcranTest.php`.
  **« Argent sorti » a UNE seule définition — `statut = Approuvée` — et tout
  écran qui somme des dépenses doit la porter.** Les lectures de caisse le
  faisaient déjà ; `GetDashboardStats` (carte « Dépenses du mois ») et
  `GetAnnualFraisSummary` (récapitulatif annuel) lisaient `depenses` sans
  aucun filtre de statut et comptaient donc l'argent encore « En attente »,
  celui d'une dépense « Refusée » qui n'a jamais bougé, et désormais celui
  rendu par une annulation — trois écrans du même argent qui se
  contredisent. Corrigé le 09/09/2026 ; tout nouvel écran qui agrège des
  dépenses reprend ce filtre. Tests:
  `tests/Feature/Backoffice/Finance/DepenseSoldeInsuffisantTest.php`,
  `AnnulerDepenseDoublonTest.php`.
- **⚠ Un remboursement ne sort JAMAIS d'une caisse qu'on n'a pas en main, ni
  au-delà de ce qu'elle contient** (10/09/2026). Deux règles, un seul écran
  (« Ajouter un remboursement ») :
  (1) **Choisir la caisse débitée est une permission**,
  `refunds.choose-till`, portée par le SEUL rôle `director` (+ super-admin) —
  hors de `$operations` et de `$managementEdits`. Le champ « Caisse à
  débiter » listait toutes les caisses espèces du centre actif AVEC leur
  solde à quiconque tenait `refunds.create` : une assistante administrative
  rendait 300 DH depuis le tiroir d'un collègue, dont le comptage de fin de
  journée tombait faux sans rien pour l'expliquer. Le front-office rend
  l'argent qu'il a PHYSIQUEMENT en main, donc sa propre caisse, dérivée au
  serveur par `CaisseResolver::tillOf()`. Trois bornes indissociables : un
  `caisse_id` étranger soumis sans le droit est **REFUSÉ (422), jamais ignoré
  en silence** (accepter puis débiter ailleurs ferait mentir l'écran) ; la
  LISTE des caisses n'est pas servie sans le droit (`remboursementCaisses`
  vide — elle divulguait les soldes des collègues) et l'écran NOMME à la
  place la caisse qui sera débitée, au lieu de masquer l'information ; sa
  PROPRE caisse soumise explicitement reste acceptée, c'est ce que le
  formulaire pré-remplit. L'exception chèque rejeté
  (`CaisseResolver::forRemboursement`) prime toujours sur les deux.
  (2) **Le solde est contrôlé, comme pour une dépense** : `EnregistrerRemboursement`
  appelle `GardeSoldeCaisse` DANS sa transaction, sur la ligne `caisses`
  verrouillée FOR UPDATE — la même que `CaisseLedger` débite juste après.
  Une dépense ne pouvait plus dépasser son tiroir depuis le 09/09/2026, un
  remboursement le pouvait encore alors qu'il débite EXACTEMENT la même
  caisse physique : rendre 5 000 DH depuis un tiroir de 300 DH le laissait à
  -4 700,00 DH. Deux écrans du même argent avec deux règles opposées : le
  trou se déplace simplement vers celui qui ne contrôle rien. Borne
  `montant <= solde`, vider un tiroir jusqu'à 0,00 reste légitime. Tests :
  `tests/Feature/Backoffice/Finance/RemboursementCaisseChoisieTest.php`.
- **A dépense and a « Paiement prof » are the SAME table but two different
  forms** (26/08/2026). Gestion des dépenses has one modal per tab, and the
  contract is enforced server-side by
  `Requests\Backoffice\Depenses\Concerns\PaiementProfRules` (shared by
  `Store`/`UpdateDepenseRequest`), keyed on the SUBMITTED
  `type_depense_id` — never on which modal was open, so a crafted request
  cannot mix the two:

  | field | Dépense | Paiement prof |
  |---|---|---|
  | `type_depense_id` | any ACTIVE type except « Paiement prof » | locked to « Paiement prof » |
  | `group_id` | prohibited (field not shown) | **required** |
  | `periode_debut` / `periode_fin` | prohibited | **required**, fin ≥ début |
  | `reference_facture` | optional | prohibited |
  | `description` | **required** | **required** |

  `periode_debut`/`periode_fin` are the teaching PERIOD the payment covers,
  as opposed to `date_depense` (the day the money left the till) — nullable
  columns, because an ordinary dépense has none. Everything else is
  unchanged: same `depenses` table, same till, same approval flow, same
  money invariants. The Dépenses modal's Type dropdown reuses
  `filterTypeOptions` (« Paiement prof » stripped) so the type can only be
  chosen from the modal that also collects its required fields. Tests:
  `tests/Feature/Backoffice/Finance/PaiementProfModalTest.php`.
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
- **Legacy import refs (`legacy_ref`) are unique PER CENTRE, never
  globally** — students, inscriptions AND encaissements all carry an
  `etablissement_id` + a `(etablissement_id, legacy_ref)` unique index
  (encaissements got theirs on 25/08/2026 after the Rabat payments import
  skipped 4 297 rows: every centre's old CRM numbers payments from P1, so
  « P3 » exists in all seven exports). Every importer's dedupe (preload +
  commit-time re-check) is scoped to the batch centre; never widen it back.
  Other legacy-export facts the importers rely on (25/08/2026, verified by
  running the real pipeline on all seven centres' exports): the old CRM
  holds the same student twice in ~50 cases (two refs, same phone + birth
  date) — `StudentImporter` skips the second copy as `duplicate_in_file`
  (name + birth date, or name + phone when undated; never name alone);
  inscriptions tell real homonyms apart by the export's Téléphone column,
  payments by "exactly one twin is enrolled in this centre+année"; a
  literal `-` is a missing value in EVERY column (Sexe included);
  `SheetReader::FOOTER_CUTOFF_ROW_CAP` must stay far above a full-year
  export (Rabat = 5 340 payment rows; the old 5 000 cap silently dropped
  340 of them). Old-CRM exports "old data" vs "active data" share the SAME
  students/payments files — only the inscriptions file differs by statut.
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
  - **⚠ A `HiddenAccount` filter that reaches the maintainer THROUGH
    `employees` must call `withoutGlobalScopes()` on that subquery.**
    `Employee` is `#[ScopedBy(HiddenAccountScope::class)]`, and a global
    scope applies inside a nested `whereHas`/`whereDoesntHave` too — so the
    subquery looks for the maintainer's employee row in a set the scope has
    already removed him from, finds nothing, and reports "this record has no
    maintainer owner" for the one record that does. Reported 30/08/2026: his
    personal till was listed on « Caisse globale » / « Comptes de caisse »
    with an empty Responsable column (the relation was scoped away at render
    time as well, which is what drew the « — »), and was still offered in the
    transfer and encaissement caisse dropdowns. `hideUsers()` is safe only
    because it queries `users` directly — that is luck, not design. Filter at
    the SINGLE funnel a screen uses (`GetCaisseJournal::caisseIds()` feeds the
    dropdown, the header totals AND the rows) so the three cannot disagree,
    and prove the fix with a query rather than by reading the code. Tests:
    `tests/Feature/Backoffice/Access/HiddenAccountTest.php`.
  - **⚠ EVERY list, dropdown, lookup and total — current and future — must
    route through `HiddenAccount`.** The account is hidden by DISPLAY
    filters, so a new screen is visible-by-default: the maintainer reappears
    the moment a module ships a query nobody filtered. When adding any CRUD
    index, option list, autocomplete, stat card or export that can surface an
    **employee**, a **user**, a **caisse** or a **responsable name**, apply
    the matching funnel in the read model (never in the React component):
    `HiddenAccount::hideEmployees()` (usually automatic —
    `Employee` is `#[ScopedBy(HiddenAccountScope::class)]`),
    `hideUsers($query, $table)`, `hideCaisses()`. Two hidden logins exist and
    both come from `HiddenAccount::emails()` — the technical
    `EMAIL` and the GLS-domain `STAFF_EMAIL` (both are the developer;
    established 07/09/2026 after « Caisse globale » listed two ROCHDI
    KAROUALI tills). Never match one address by hand: filter through
    `emails()` so a third address is one constant, not a repo-wide sweep.
    `AuditLogRegistry::DEVELOPER_EMAIL` deliberately stays `EMAIL` ALONE —
    the « Inclure le compte technique » toggle must keep meaning one account.
    Three things this rule never becomes: a write-time skip (the journal
    records both accounts in full — see the bullet above), an authorization
    bypass (both hold `super-admin` and `Gate::before` treats them normally,
    cash-transfer validation included), or a filter applied to GLS's own
    business records. Hiding is display-only, so at least one super-admin
    must stay VISIBLE (the CEO) or the Autorisations screen shows nobody who
    can grant anything. Verify a new screen with a query as the CEO, not by
    reading the code.
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
- **⚠ Un relevé d'argent REÇU imprime les avances et JAMAIS les applications
  d'avance** (10/09/2026, « Relevé des encaissements », onglet Finance &
  Paiements de Gestion des rapports, `Reports\Queries\GetEncaissementsReport`).
  Une avance est de l'argent reçu — la caisse a bougé — simplement pas encore
  affecté à un frais : elle FIGURE au document, et sa colonne « Type » la nomme
  « Avance » (la colonne « Frais » porte « Avance » plutôt qu'un blanc, qu'on
  lirait comme une donnée manquante). L'omettre ferait un relevé signé et
  tamponné dont le total est INFÉRIEUR à ce que la caisse a encaissé.
  Symétriquement, une ligne d'APPLICATION d'avance
  (`applied_from_encaissement_id` non NULL) est exclue : elle repose sur un
  frais l'argent d'une avance déjà comptée, la caisse n'a jamais bougé pour
  elle, et l'imprimer compterait le même dirham deux fois — mesuré sur la base
  réelle : 3 019 lignes pour **2 316 349 DH** qui auraient gonflé un relevé de
  5,77 M DH. C'est la MÊME règle que l'onglet « Encaissements » de
  `GetEncaissementsList`, et c'est ce qui rend le total du document égal à
  l'argent réellement entré. Trois corollaires : (1) le total vient du SERVEUR
  (un `SUM` SQL sur tout l'ensemble filtré), il n'est jamais réadditionné par
  le gabarit ni par la page — sinon l'écran, le PDF et le classeur finiraient
  par annoncer trois chiffres ; (2) une avance n'a pas de frais, donc pas
  d'inscription, donc **pas d'année** : elle est exempte de la fenêtre d'année
  active, comme dans la liste Encaissements ; (3) ajouter un rapport reste
  **une entrée dans `RapportCatalogue` + sa requête Domain + sa vue Blade** —
  jamais une modification du composant React, qui ne peut donc pas offrir un
  filtre que la requête n'applique pas. ⚠ Les paliers mémoire de
  `RapportPdfRenderer` sont calibrés sur des MESURES (mPDF tamponne le tableau
  entier : 168 Mo à 1 045 lignes, 772 Mo à 7 077) — les remesurer sur un
  rapport de cette taille avant de les rabaisser ; sur un gros volume l'Excel
  (OpenSpout, qui STREAME) sort en 2,5 s contre 170 s pour le PDF. Tests :
  `tests/Feature/Backoffice/Reports/RapportEncaissementsTest.php`.
- **⚠ Un frais payé se transfère à un AUTRE étudiant — uniquement à ZÉRO
  présence** (10/09/2026, `Payments\Actions\TransfererFraisVersAutreEtudiant`).
  Le cas réel : un étudiant s'inscrit, paie, ne vient jamais, et sa sœur se
  présente — « je prends la place de ma sœur, elle n'étudie pas ».
  L'arrangement se réglait à l'oral, hors système : le CRM montrait l'argent
  au nom de quelqu'un qui n'a jamais suivi le cours, et la personne
  réellement en classe apparaissait comme n'ayant rien payé.
  **Ce geste déplace UN encaissement, rien d'autre** : il ne crée aucune
  inscription et n'en clôture aucune. La sœur est inscrite AVANT, par l'écran
  d'inscription habituel ; le dossier du frère reste OUVERT et son frais
  redevient simplement dû (à annuler ensuite avec son motif s'il ne vient
  pas — décider ici de son sort serait une décision que personne n'a
  demandée). Action de ligne sur « Gestion des paiements », jamais un onglet :
  l'opérateur part du paiement qu'il a déjà sous les yeux.
  C'est **l'EXCEPTION assumée** au seul garde-fou que
  `DeplacerEncaissementVersFrais` ne relâche jamais (« l'argent d'un étudiant
  ne solde jamais le frais d'un autre ») — ne JAMAIS assouplir cet outil-là
  « pour faire pareil » : c'est l'action dédiée, avec ses bornes, qui porte
  l'exception, et un test l'assère. Quatre bornes indissociables :
  (1) **ZÉRO présence sur l'inscription SOURCE**, vérifié DANS la transaction
  par `Registrations\Support\GardePresencesInscription` — jamais avant, sinon
  un appel validé entre le contrôle et l'écriture passe au travers (§11).
  **TOUTE ligne d'appel bloque, « Absent » et « Justifié » compris** : le
  critère n'est pas « a-t-il assisté ? » mais « son nom a-t-il été appelé
  dans ce groupe ? ». Assouplir vers « Présent/Retard uniquement » ferait de
  la règle « transférable tant que l'étudiant sèche », l'inverse de
  l'intention. Une présence de la CIBLE ne bloque rien (elle a pu commencer
  avant la paperasse). Il n'existe aucune FK `presences → inscriptions` : la
  liaison est (student × séances du groupe), et cette garde en est la SEULE
  autorité — `GetEncaissementsList` ne fait que la porter à l'écran
  (`sourcePresencesCount`, `transferableAutreEtudiant`).
  (2) **AUCUN argent ne bouge** : `montant`, `methode`, `date_paiement`,
  `caisse_id`, `agent_id` et `caisses.solde` sont inchangés — seule
  l'AFFECTATION change. `encaissements.student_id` suit le frais, lui,
  contrairement à `DeplacerEncaissementVersFrais` (où la cible appartient au
  même étudiant) : sans cela la fiche du frère continuerait de compter cet
  argent pendant que celle de la sœur affiche un frais soldé par un paiement
  qui ne lui appartient pas. Le PAYEUR d'origine est conservé dans l'entrée
  de journal, avec le motif.
  (3) **MÊME CENTRE, et le frais cible est DÉTECTÉ, jamais choisi à la
  main** (demande métier du 10/09/2026 : « no need to select Frais à
  solder »). L'opérateur désigne l'INSCRIPTION cible ; l'action pose
  l'argent sur la ligne du MÊME frais du catalogue (`frais_id`, repli sur le
  `nom` pour les lignes legacy sans catalogue) — « Frais d'inscription » du
  frère solde « Frais d'inscription » de la sœur, jamais son « Frais de
  Mars ». Trois refus distincts, chacun nommant le frais : aucune ligne de ce
  frais sur la cible, ligne MASQUÉE (audit R-01 : l'argent ne se pose jamais
  sur un frais qui n'est plus dû — même garde qu'`AppliquerAvance`), reste dû
  insuffisant (le montant n'est pas fractionné). **Cette règle a UN seul
  code : `Payments\Support\CibleTransfertFrais::resoudre()`**, appelée sous
  verrou par l'action ET en lecture par le dropdown du modal
  (`EncaissementController@transferTargets`), qui n'offre que les dossiers
  qui passeront — une inscription où ce frais est déjà soldé n'apparaît pas
  (demande du 10/09/2026). Ne jamais recopier la règle dans le read-model
  « pour aller plus vite » : l'écran finirait par proposer ce que le serveur
  refuse. Un transfert inter-centres déplacerait du chiffre d'affaires d'un
  établissement à l'autre alors que la caisse créditée ne bouge pas.
  (4) **Motif OBLIGATOIRE** + permission dédiée `payments.transfer-student`,
  **super-admin uniquement** (`superAdminOnly()`, décision du CEO le
  10/09/2026 — un directeur l'a tenue quelques heures) : même classe que
  `students.merge`, aucun preset ne peut la porter. Le détail du paiement
  (`GetEncaissementDetails::transfert()`) affiche le transfert lu depuis le
  journal — date de l'OPÉRATION, de → vers, motif, par qui — parce que la
  ligne elle-même n'en garde rien : `date_paiement` reste celle de
  l'encaissement d'origine (vérifié au journal le 10/09/2026 : seuls
  `student_id` et `inscription_fee_id` changent), et sans ce bloc le reçu
  porte le nom de la sœur avec une date antérieure à son inscription. **Quatre lignes ne se transfèrent JAMAIS** (audit 10/09/2026),
  chacune pour une raison propre : une avance (rien à céder) ; une ligne
  d'APPLICATION d'avance — elle porte un `inscription_fee_id`, donc passe le
  test « est-ce une avance ? », mais son argent appartient à l'avance
  PARENTE restée au nom du frère (et symétriquement un paiement qui a
  lui-même financé des applications) ; un paiement remboursé ; **TOUT
  chèque suivi**, pas seulement un rejeté — `cheques.student_id` désigne
  un propriétaire et `EncaissementController@store` refuse déjà le chèque
  d'un autre étudiant, le transfert contournerait cette invariante par la
  porte de derrière. Un dossier source DÉTACHÉ de son groupe
  (`group_id` NULL, `nullOnDelete`) est refusé comme INDETERMINE, jamais
  compté 0 : sans séances, « aucune présence trouvée » ne prouve rien, et
  c'est le sens d'erreur qui AUTORISE. L'écran AFFICHE le refus avec le
  nombre d'appels au lieu de masquer l'option. Tests :
  `tests/Feature/Backoffice/Finance/TransfertFraisAutreEtudiantTest.php`.
- **⚠ Retirer un frais DÉJÀ PAYÉ libère toujours son argent en avance.**
  Trois chemins retirent un frais d'une inscription et ils doivent se
  comporter à l'identique, sinon celui que l'utilisateur emprunte change ce
  qu'il advient de son argent : le retrait au niveau du GROUPE
  (`Groups\Actions\RetirerFraisGroupe`), la suppression d'une ligne dans le
  modal (`Registrations\Actions\MettreAJourFraisInscription`) et la corbeille
  par ligne (`Registrations\Actions\BasculerVisibiliteFraisInscription::hide`
  — le trou corrigé le 31/08/2026). Tous appellent
  `Payments\Actions\ConvertirEncaissementsEnAvance` AVANT de masquer/supprimer :
  l'encaissement n'est jamais supprimé (les enregistrements monétaires sont
  append-only, §11) et `caisses.solde` ne bouge pas — seul
  `inscription_fee_id` est détaché, ce qui refait du paiement une avance
  réapplicable. Sans cela l'argent reste accroché à une ligne INVISIBLE :
  compté ni dans le dû, ni dans l'onglet Avances, et irrécupérable alors que
  l'étudiant a payé (signalé sur 500 DH de frais d'inscription). Un paiement
  REMBOURSÉ est écarté du lot (son argent a déjà quitté la caisse, le
  convertisseur le refuse) plutôt que de faire échouer tout le retrait, et
  restaurer un frais ne « re-colle » jamais l'avance — entre-temps elle a pu
  être appliquée ailleurs. Rattrapage des lignes masquées avant le correctif :
  `php artisan inscriptions:liberer-paiements-frais-masques` (dry-run par
  défaut, `--apply`). Tests :
  `tests/Feature/Backoffice/Inscriptions/InscriptionFeeVisibilityTest.php`.
- **⚠ Supprimer un groupe ne supprime JAMAIS ses inscriptions** (10/09/2026,
  `Groups\Actions\DetacherInscriptionsGroupeSupprime`). Une inscription est le
  DOSSIER d'un étudiant : elle porte des lignes de frais et, potentiellement,
  de l'argent. `SupprimerGroupe` faisait
  `Inscription::where('group_id', …)->delete()` — le dossier disparaîssait de
  la fiche de l'étudiant et plus rien nulle part n'expliquait pourquoi ; des
  mois plus tard, personne ne pouvait comprendre. Désormais
  `inscriptions.group_id` est **NULLABLE + ON DELETE SET NULL** (comme
  `inscriptions_historique.group_id`, qui appartient à l'INSCRIPTION et non au
  groupe — le laisser en CASCADE effaçait l'historique d'un dossier vivant),
  et la suppression annule chaque inscription encore `Active` avec le motif
  catalogué `MotifAnnulation::MOTIF_GROUPE_SUPPRIME` (« Groupe supprimé »,
  `is_system`). Quatre bornes : (1) **la NOTE porte le nom du groupe et la
  date** — une fois la ligne `groups` détruite c'est le SEUL endroit où ce nom
  survit, l'écran affichant « — » ; elle est AJOUTÉE, jamais écrasée (même
  règle que `AnnulerInscription`) ; (2) **un dossier déjà clos**
  (Annulée/Changement/Expirée/Archivée) **garde son statut ET son motif
  d'origine** — il reçoit la note seule, la suppression du groupe n'a pas à
  réécrire pourquoi il avait été fermé ; (3) **aucun argent ne bouge et aucun
  frais n'est masqué** — contrairement à `CloturerInscriptionsGroupe` (où le
  groupe SURVIT et ses créances doivent cesser d'être réclamées), ici
  `SupprimerGroupe` refuse en amont tout groupe portant le moindre encaissement,
  donc ces frais n'ont par définition jamais reçu un dirham ; (4) les deux
  verrous existants **restent** — un encaissement ou une séance refuse toujours
  la suppression. Le modal DIT que les inscriptions sont conservées
  (`inscriptionsActives` vient du serveur) : un avertissement qui laisse croire
  que les dossiers partent fait renoncer à une suppression légitime. Tests :
  `tests/Feature/Backoffice/Groups/GroupDeleteTest.php`.
- **⚠ Un groupe qui passe TERMINAL clôture ses inscriptions** (09/09/2026).
  « Fin de formation » comme « Annulée » déclenchent
  `Groups\Actions\CloturerInscriptionsGroupe`, dans la MÊME transaction que
  la transition : chaque inscription encore `Active` passe `Annulée` avec le
  motif système `MotifAnnulation::MOTIF_CLOTURE_GROUPE`, ses lignes de frais
  **n'ayant reçu aucun encaissement** sont MASQUÉES (`masque_le`,
  `MASQUE_ORIGINE_GROUPE` — jamais supprimées) et `montant_total` est
  recalculé sur les lignes visibles. C'est le pendant à l'ÉCRITURE du filtre
  `Active` du recouvrement (règle suivante) : sans lui, un groupe clos
  laissait le front office réclamer des frais que plus personne ne doit.
  Trois bornes indissociables : (1) **une ligne qui a reçu le moindre
  dirham n'est jamais masquée** — « Payé » comme « Payé partiellement » ; le
  critère est `whereDoesntHave('encaissements')`, pas le statut (un statut
  dérive, un encaissement non), et un reste dû sur une prestation commencée
  s'annule par un remboursement, pas en effaçant la créance ; par
  construction aucun frais masqué ici ne porte d'argent, donc la règle
  « retirer un frais payé libère son argent en avance » est sans objet et
  `caisses.solde` n'est ni lu ni écrit ; (2) **un frais du catalogue du
  groupe (`group_frais`) n'est détaché que si AUCUN étudiant du groupe ne
  l'a payé** — le test porte sur toutes les inscriptions du groupe quel que
  soit leur statut, car détacher un frais portant de l'argent rendrait son
  montant de référence (le pivot) introuvable ; (3) **les trois chemins de
  clôture partagent la cascade** — `GroupController@archive`, `@annuler` et
  `transitionnerStatut()` (« Déplacer vers une autre année ») ; n'en câbler
  qu'un ferait dépendre le sort des dossiers de l'écran emprunté. Rouvrir le
  groupe (`groups.reopen`) ne défait RIEN : les inscriptions se réactivent
  une par une et un frais masqué se restaure depuis la corbeille — ré-ouvrir
  des créances en masse serait une décision monétaire prise en silence.
  Tests : `tests/Feature/Backoffice/Groups/GroupClotureCascadeTest.php`.
- **⚠ Le recouvrement ne poursuit QUE les inscriptions `Active`.**
  « Gestion des recouvrements » (`GetRetardsList`) est un miroir des frais
  échus non soldés, et un dossier **Annulée / Changement / Expirée /
  Archivée** est clos : ses frais ne sont plus dus, donc ni listés, ni
  comptés dans le total d'en-tête, ni comptés dans les badges de durée.
  Le filtre vit dans les **deux** chemins de requête — `__invoke()` (lignes +
  `montantTotal`) et `bucketCounts()` (badges de l'onglet « Retards selon la
  durée ») : chacun porte sa propre copie du `whereHas('inscription', …)`,
  donc n'en corriger qu'un fait promettre au badge des lignes que le tableau
  refuse d'afficher. Signalé le 04/09/2026 : la page annonçait 31,7 M DH de
  retards sur 33 790 lignes, dont **30,3 M DH (96 %) sur des dossiers clos**
  (16 684 lignes Annulée, 15 168 Changement) — le front office était envoyé
  réclamer de l'argent que personne ne doit. C'est le pendant en LECTURE du
  masquage des frais à l'annulation : masquer à l'écriture ne rattrape pas
  les inscriptions déjà closes en base, et ne couvre pas `Changement`.
  Tests :
  `tests/Feature/Backoffice/Finance/RecouvrementInscriptionActiveTest.php`.
- **⚠ Un groupe ne peut avoir DEUX créneaux ouverts sur la même case
  horaire** (07/09/2026). `GenererSeancesDepuisCreneau` est idempotent PAR
  CRÉNEAU : il ne recrée pas la séance du jour d'un créneau qui en a déjà une.
  Deux créneaux jumeaux (même groupe + jour + heure de début, tous deux
  `date_fin` NULL) produisent donc chacun légitimement la leur, et le job de
  08:00 écrit deux séances identiques sans jamais se répéter lui-même — le
  groupe se retrouve avec deux appels à faire pour la même classe
  (« Ilyass sept 19H » : 5 créneaux saisis le 02/09, 5 identiques le 04/09).
  Trois protections, chacune couvrant un trou que les autres ne voient pas :
  (1) `CreneauController@store/@update` REFUSE une case déjà occupée via
  `Domain\Attendance\Support\DetecteurCreneauxDoubles` — refus GLOBAL sur
  une saisie multi-jours, sinon la moitié des créneaux passe et l'utilisateur
  ne sait pas lesquels ; (2) le générateur ajoute une garde au niveau du
  GROUPE + date + heure, pour les doublons DÉJÀ en base ; (3)
  `DiagnostiquerEmploiDuTemps::CRENEAUX_DOUBLES` le signale sur la fiche ET la
  liste des groupes — c'est le seul cas du diagnostic où rien ne MANQUE à
  l'écran (l'emploi du temps paraît complet), donc le seul invisible sans
  alerte ; il passe en dernier, un vrai blocage prime. Un créneau CLÔTURÉ
  n'occupe jamais une case : c'est l'emploi du temps d'un enseignant parti,
  conservé pour la paie, et le nouvel enseignant doit pouvoir reprendre le même
  horaire. Rattrapage : `php artisan groupes:supprimer-creneaux-doubles`
  (dry-run par défaut, `--apply`) — garde le plus ANCIEN de chaque paire, ne
  supprime que les séances futures « Prévue » SANS présence, et CLÔTURE au lieu
  de supprimer un créneau dont il reste des séances réelles. La liste des
  groupes passe ses doublons pré-calculés (`doublonsParGroupe`, une requête
  pour toute la page) — jamais une requête par ligne (§ perf). Tests :
  `tests/Feature/Backoffice/Attendance/CreneauxDoublonsTest.php`.
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
  super-admins** (`centers.access-all` is answered by `Gate::before` and is
  not grantable to anyone, see §16):
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
    filter on the page overrides. **Students carry NO année at all** — only
    their INSCRIPTIONS do. Every student list, dropdown, lookup and stat
    card shows every student of the active CENTRE whatever the year
    switcher says: the same person enrolled in 2025/2026 is the same
    person in 2026/2027 and must stay findable and re-enrollable. Never
    re-add a `whereHas('inscriptions', …annee…)` filter to a student
    query (`GetStudentsList`, `GetDashboardStats`).
  - **Creates** inherit `etablissement_id`/`annee_scolaire_id` from the
    active context or from the parent record (group → inscription → séance),
    never from client input.
  - **Writes are guarded, not just reads** (27/08/2026): every store()/
    mutation whose centre/année comes from a CLIENT-CHOSEN parent (group,
    inscription, student, stock article, séance) or that edits a record
    carrying its own centre/année calls the matching
    `Controllers\Backoffice\Concerns\AssertsContextScope` helper
    (`assertGroupInContext` / `assertInscriptionInContext` /
    `assertStudentInContext` / `assertRecordInContext`) right after
    `authorize()`. It checks centre REACH (403) **and** the active context
    (422 on the form field) — the policy alone cannot, since it knows
    nothing about the top-bar switcher and `create()` takes no model. A
    stale dropdown loaded before a switch, or a forged id, must never file
    a record into a year/centre the current screen does not show. Applied
    to inscriptions (create/edit/fees/livres/cancel/change-group),
    encaissements (store/avance/convert/apply), remboursements, chèques,
    stock movements, groupes (every mutation), séances, créneaux and
    dépenses « Paiement prof ». A new module's write path adds the same
    call. Tests: `tests/Feature/Backoffice/Context/ContextScopeWriteGuardTest.php`.
  - **Deliberate exceptions** (do not "fix"): employees/users (staff has no
    year), stock (physical inventory), the transfer-validation inbox (a
    pending transfer must never hide behind a year switch), the caisse
    journal's header totals + `solde` (they reconcile with the till's
    running balance, which spans years — only the journal ROWS follow the
    year window), and the TARGET group of « Changement de groupe » (below).
  - **⚠ « Changement de groupe » may cross an ANNÉE — never a CENTRE**
    (02/09/2026). It is the only write allowed past the année half of the
    guard: a student whose course is interrupted mid-year is moved into
    NEXT year's group, so `InscriptionController@changeGroup` calls
    `assertRecordInContext(..., $anneeId: null, ...)` instead of
    `assertGroupInContext()` — centre reach + active centre still enforced,
    année deliberately not. Its dropdown is fed by
    `GetInscriptionFormOptions::changeGroupGroups()` (centre-scoped, NOT
    year-scoped) plus `anneesScolairesFromGroups()` for the modal's
    « Année scolaire » selector; **every OTHER group dropdown on the page
    keeps `groups()` and its active-year window** (the list filter, the
    create/edit form, « Modification du groupe » — that one corrects a
    group IN PLACE and must never silently re-file an inscription into
    another year). The successor row inherits the TARGET group's année
    (`ChangerGroupeInscription::createNewInscription`), so it is filed
    where the student actually joins and only becomes visible once the
    top-bar year switcher follows — the modal warns about that before
    submitting. Paid fees carried over are still chosen per line by
    `transfer_fee_ids` (the money moves with the fee row, nothing is
    rewritten). Tests:
    `tests/Feature/Backoffice/Inscriptions/InscriptionChangeGroupTest.php`,
    `tests/Feature/Backoffice/Context/ContextScopeWriteGuardTest.php`.
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

**⚠ A seeder GARNIT a catalog — it never reopens what an admin closed, and
never takes a password back.** `db:seed` is meant to be re-run on the live
database, and "re-runnable" means it must not undo a decision made through
the UI (07/09/2026 audit, four offenders fixed):

- **`AdminUserSeeder` writes `password` / `must_change_password` only when
  the row is CREATED.** The previous version re-hashed on every run, so a
  re-seed after a deploy put the CEO's account back on whatever
  `ADMIN_PASSWORD` sat in the server `.env` — and flipped him back to
  `must_change_password` — locking him out with nothing announcing it. A
  password is changed from Profil or by reset; the seeder PROVISIONS an
  account, it never takes one back over. `MaintainerUserSeeder` already did
  this; copy that pattern for any future account seeder.
- **Use `firstOrCreate`, not `updateOrCreate`, whenever the payload carries
  `statut` or a user-tunable value** (`BanqueSeeder`, `FraisSeeder`, the
  salles in `ReferentialDataSeeder`, the ordinary motifs in
  `MotifAnnulationSeeder`). `updateOrCreate` there resurrected an archived
  bank / room / frais / motif as ACTIVE at every deploy, and reset a
  `capacite` or `montant_defaut` an admin had corrected in Paramètres.
  `updateOrCreate` stays legitimate for rows the CODE looks up by name (the
  `is_system` « Changement de groupe » motif must remain active) and for
  structural fields the UI does not expose (`MotifAnnulation::portee`).

Prove it by SIMULATION, never by reading the seeder: on a scratch DB, change
the admin password, archive a bank + a room + a frais, tune an amount, re-run
`db:seed --force`, and assert all of it survived.

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
  (les `module.action` permissions — 120 au 10/09/2026, mais ne recopiez pas
  ce nombre : `PermissionRegistry::names()` en est la seule autorité et le
  test dérive son compte de là — French labels, role matrix). New module ⇒
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
- **⚠ Rôles refondus le 30–31/08/2026 — quatre règles à ne pas défaire.**
  (1) **Consultant et Assistante administrative sont le MÊME poste** : ils
  partagent `$operations` verbatim, et un test l'assère. (2) **Un front-office
  ne MODIFIE que les quatre objets pédagogiques** — étudiants, inscriptions,
  groupes, séances : toute la finance lui est **création seule** (une erreur
  se corrige par une écriture compensatoire, jamais en réécrivant le
  document). Les opérations de paiement essentielles restent à sa portée :
  enregistrer une avance, la convertir/l'appliquer, saisir un chèque — ce
  sont des créations. La seule édition financière qu'il garde est
  `payments.update` (note + identité du chèque). (3) **`payments.update-date`
  est super-admin uniquement** : re-dater un encaissement le déplace dans le
  journal de caisse et le récapitulatif annuel, vers un mois peut-être déjà
  rapproché — ferme le risque signalé par l'audit du 27/08/2026 (garde en
  trois endroits : `superAdminOnly()`, `EncaissementController@update` qui
  retire le champ, `UpdateEncaissementRequest` qui ne l'exige que du
  titulaire). `montant`/`caisse_id`/`methode` restent gelés pour TOUT LE
  MONDE, super-admin compris — invariants monétaires (§11), pas des
  permissions. (4) **Le stock physique n'appartient qu'à
  `marketing-manager`** (+ le bypass super-admin) : `stock.create/update/move`
  et le catalogue des types ont été retirés de tous les autres presets, qui
  gardent `stock.view`. Les cinq rôles de direction (directeur, dir. des
  opérations, dir. financier, directrice pédagogique, responsable RH)
  ajoutent `$managementEdits` à `$operations` : `expenses.update`,
  `refunds.update`, `cheques.update`, `cash-transfers.update` et la LECTURE
  de « Comptes de caisse » (`cash-accounts.view` — l'onglet suit le sélecteur
  de centre, donc ils y voient leurs centres affectés, jamais le réseau ;
  créer/modifier un compte reste super-admin). `employees.create` n'est plus
  dans aucun preset (embaucher crée un login, et un « Responsable de
  système » crée un super-admin). Détail complet et tableau :
  `docs/roles-and-permissions.md` §5b.
- **⚠ A physical front-desk GESTURE gets its own permission — never a wider
  `*.update`** (07/09/2026). « Remise à la banque » and the rest of a
  chèque's bank journey (En possession → Déposé → Encaissé | Rejeté, plus
  « restitué ») are `cheques.deposit`, held by EVERY role through
  `defaultForEveryRole()`; `cheques.update` — rewriting a chèque's owner,
  number or amount — stays with the management roles. The employee who
  physically carries the chèques to the bank is the one who records it, and
  it is not a directeur. Merging the two would have handed the front office
  the power to rewrite a money document, which the roles rework forbids
  (§16). Safe because depositing touches NO caisse: `caisses.solde` did not
  move when the chèque was recorded and does not move here; a rejection's
  money consequences go through a remboursement, which keeps its own
  permissions. Enforced in three places that must stay in sync —
  `ChequePolicy::deposit()` (centre scope kept), the route middleware, and
  the `canDeposit` prop the page uses to draw the menu item. Same shape as
  `cash-transfers.validate`. Tests:
  `RolesAndPermissionsSeederTest::test_every_role_can_deposit_a_cheque_at_the_bank`
  (both halves: everyone deposits, the front office still cannot update).
- **⚠ Un écran ABSENT de la barre latérale n'est pas un écran protégé**
  (07/09/2026, « Échéances en masse », `/backoffice/bulk-echeance`,
  `FeeDueDateBulkController`). Un outil ponctuel peut légitimement ne pas
  avoir d'entrée dans `resources/js/Config/backofficeNavigation.ts` — c'est
  une décision d'ERGONOMIE, jamais de sécurité : la route reste atteignable
  par son URL, donc elle porte son `permission:` comme n'importe quelle
  autre et le contrôleur revérifie (§5 : le prop client n'est qu'un
  confort d'interface). Ne jamais « sécuriser » un écran en le retirant du
  menu.
  L'outil applique UNE date d'échéance à plusieurs lignes de frais d'un
  groupe d'un coup, au lieu de rouvrir le modal de chaque inscription. Son
  droit `fee-due-dates.bulk-update` est dans `defaultForEveryRole()` pour la
  même raison que `cheques.deposit` : **aucun argent ne bouge** — il n'écrit
  que `inscription_fees.date_echeance`, jamais un montant, un statut, un
  encaissement ni une caisse. Une échéance est une date de rappel.
  Deux règles que tout futur écran « en masse » doit reprendre :
  (1) **la portée est revérifiée À L'ÉCRITURE, ligne par ligne**, pas
  seulement dans le read-model — les ids arrivent cochés depuis le
  navigateur, donc forgeables ; (2) **un id hors portée refuse le LOT
  ENTIER** au lieu d'être filtré en silence, sinon l'opérateur croit avoir
  modifié 30 lignes quand 28 seulement ont bougé (§11 « signaler plutôt que
  masquer »). Une ligne MASQUÉE est refusée de la même façon : elle n'est
  plus due et son argent a été libéré en avance. Chaque ligne passe par
  `save()` sur un modèle Eloquent, jamais un `update()` de masse, sinon
  `Auditable` ne journalise rien. Tests :
  `tests/Feature/Backoffice/Inscriptions/BulkFeeDueDateTest.php`.
- **⚠ Modifier un groupe CLOS est une IDENTITÉ, pas une permission**
  (07/09/2026). L'onglet Historique des Groupes est en lecture seule : un
  dossier « Fin de formation » / « Annulée » ne se retouche pas, sinon la
  ligne vivante et le snapshot `groups_historique` divergent. Le SEUL compte
  de maintenance (`HiddenAccount::EMAIL`) y échappe via
  `GroupPolicy@updateClosed`, pour corriger un nom, un niveau ou une date
  saisis de travers par l'import legacy **sans passer par `reopen`** — qui
  remettrait le groupe dans les listes actives et le rendrait de nouveau
  inscriptible. Trois règles indissociables : (1) l'ability est dans
  `AppServiceProvider::NO_SUPER_ADMIN_BYPASS` (`'updateClosed' =>
  Group::class`) — **sans cette ligne `Gate::before` l'accorde à TOUS les
  super-admins**, le CEO compris, et « un dossier clos est clos » ne tient
  plus que par convention (même mécanisme que `CaisseTransfer@validate`) ;
  (2) `EMAIL` **seul**, jamais `emails()` — `STAFF_EMAIL` est un compte de
  staff, pas l'identité de maintenance ; (3) le **statut reste verrouillé
  pour tout le monde**, mainteneur inclus — `GroupController::update()` le
  repointe sur la valeur stockée et le modal gèle le champ, sortir d'un
  statut terminal passe UNIQUEMENT par `reopen`. Le prop
  `canEditClosedGroups` ne dessine que le bouton (§5). Tests :
  `tests/Feature/Backoffice/Groups/GroupUpdateClosedTest.php`.
- **⚠ « Gestion de la base de données » est réservée au SEUL compte de
  maintenance** (09/09/2026, `/backoffice/database-management`,
  `DatabaseManagementController` + `App\Support\Database\DatabaseBrowser`).
  Explorateur de tables piloté par des boutons — liste des tables, parcours
  paginé/recherché (ILIKE sur toutes les colonnes)/trié, ajout, modification,
  suppression d'une ligne, vidage d'une table (nom à retaper) et export CSV
  — sans jamais saisir de SQL. Il écrit DIRECTEMENT dans les tables, hors
  de toute action Domain, de tout observer et de tout invariant monétaire
  (§11) : outil de réparation, pas écran d'exploitation. Trois règles :
  (1) l'accès est une IDENTITÉ (`HiddenAccount::EMAIL` seul, ability
  `database.manage` dans `AppServiceProvider::MAINTAINER_ONLY_ABILITIES`,
  décidée AU-DESSUS du bypass super-admin — le CEO reçoit 403), rejouée par
  le middleware `can:`, le contrôleur et chaque Form Request ; (2) chaque
  écriture est journalisée sous le log `database` avec la ligne AVANT et
  APRÈS dans la même transaction — `Auditable` ne voit pas ces requêtes,
  c'est la seule trace ; (3) `activity_log` et `migrations` sont en LECTURE
  SEULE (`DatabaseBrowser::READ_ONLY_TABLES`) : le journal est append-only
  au niveau modèle et cet outil contourne Eloquent, il doit donc refuser
  lui-même. Hors de la barre latérale, comme les deux autres outils de
  maintenance — et ce n'est pas ce qui le protège. Deux règles de LECTURE
  ajoutées le 09/09/2026 après usage réel : (a) **une clé étrangère se lit
  comme un NOM à côté de son id** (« Herr Driss 13h #2 »), résolu par le
  MÊME `Support\Audit\AuditValueResolver` que le journal d'audit — un
  écran de plus qui lit un id ne redéfinit jamais sa propre table de
  correspondance ; le nom est un CONFORT D'AFFICHAGE, l'id reste la valeur
  stockée, soumise et toujours visible (n'afficher que le nom masquerait
  quelle ligne est référencée, et un renommage réécrirait en silence ce que
  l'écran prétend montrer, §11). Les noms sont chargés EN LOT (une requête
  par table référencée, jamais une par ligne) et le schéma est mémoïsé par
  requête ; (b) **une table que le rôle applicatif ne peut pas lire ne fait
  pas tomber la page** — production 09/09/2026, `tmp_caisse_snapshot_0901`,
  un instantané de réparation créé par le superutilisateur `postgres` : le
  `count()` levait « permission denied » et emportait tout l'index. Elle est
  désormais signalée en rouge (« Accès refusé au rôle applicatif ») et
  l'ouvrir renvoie à la liste avec la raison de PostgreSQL. Le correctif est
  côté serveur (`ALTER TABLE … OWNER TO gls_crm_app`, ou supprimer la
  table) — l'écran ne fait que le signaler. La liste des tables est aussi
  bornée au schéma de la connexion (`public`), sans quoi une table d'un
  autre schéma revient sous son nom nu et 500 à la lecture. Tests :
  `tests/Feature/Backoffice/Access/DatabaseManagementAccessTest.php`,
  `tests/Feature/Backoffice/Maintenance/DatabaseManagementTest.php`.
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
  role, never a permission.** `centers.access-all`
  (`PermissionRegistry::GLOBAL_CENTER_ACCESS`) is an ability NAME only,
  answered by `Gate::before` for super-admins: **nobody else can ever hold
  it** (24/08/2026). It is excluded from `PermissionRegistry::grantable()` /
  `groupedGrantable()` (what the Roles form and the Autorisations screen
  offer), refused by `Store/UpdateRoleRequest`,
  `SyncUserAuthorizationRequest` AND `UserAuthorizationService`, and
  `RolesAndPermissionsSeeder` strips any stale grant from EVERY role
  (custom ones included — they are not re-synced by the preset loop) and
  every user's direct permissions on each run. A user reaches exactly the
  centers assigned on their employee form; the top-bar switcher offers
  exactly those, and « Tous les centres » is drawn for super-admins only.
  A cross-center job = more centers assigned on the employee form. Never
  "fix" a can't-see-other-centers complaint by editing a role or granting
  a permission — assign the centers instead; a global view = super-admin.
  Tests: `tests/Feature/Backoffice/Authorization/GlobalCenterAccessLockTest.php`.
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
lives in `docs/rapports/postgres/POSTGRES_AUDIT.md` and `docs/rapports/postgres/POSTGRES_MIGRATION_REPORT.md` — read those
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

- **⚠ Editing a `create_*` migration does NOT change any database that has
  already run it — not production, and NOT the local `gls_crm` either.**
  Every schema change lives in the `create_*` migration of its table (no alter
  migrations, see §11), so after editing one you MUST also apply the same
  change as idempotent SQL to every database that already exists:

  ```powershell
  # 1. the local dev DB you are about to click through — NEVER migrate:fresh it
  C:\php84\php.exe artisan tinker --execute="DB::statement('ALTER TABLE t ADD COLUMN IF NOT EXISTS c varchar(20) NOT NULL DEFAULT ''x''');"
  # 2. append the same statements to docs/production-schema-patches.sql
  ```

  Verifying only on a freshly migrated scratch DB proves nothing about the DB
  the app actually serves: on 31/08/2026 `motifs_annulation.portee` was added
  to the create migration and checked on `gls_crm_scratch`, while every page
  reading that catalogue 500’d with « column "portee" does not exist ».
  After any schema edit, load an affected page (or query the real DB) before
  reporting the work done — `Schema::hasColumn()` on `gls_crm` is the check.
- Before the first production deployment, existing project-owned migrations
  may still be corrected in place (this is what happened during the
  PostgreSQL migration — see `docs/rapports/postgres/POSTGRES_MIGRATION_REPORT.md` §2 for the two
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

**⚠ `select()` after a `withSum()`/`withCount()`/`addSelect()` ERASES them
silently** (10/09/2026). `Query\Builder::select()` starts by resetting
`$this->columns` AND `$this->bindings['select']`, so a
`->select('encaissements.*')` added to a query that already carried
`withSum('remboursements as remboursements_total')` dropped that column:
`montantRembourse` read 0.00 on every partially refunded payment and three
"can this row be edited?" flags flipped to true on rows the actions refuse.
No error, no empty result — just a wrong number. When a read-model needs
the base columns next to computed ones, use **`addSelect('table.*')`**,
never `select()`; and after touching any list query, re-read one row that
exercises the aggregate you did not write.

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
collections, and PHP-side sorting/merging — see `docs/rapports/performance/PERFORMANCE_AUDIT.md`,
`docs/rapports/performance/PERFORMANCE_OPTIMIZATION_REPORT.md`, and
`docs/rapports/performance/phase-11-performance-baseline.md` for the established methodology
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

## 18. Documentation & rapports — où va un fichier .md

Deux natures de document, deux emplacements — ne pas les mélanger :

- **Documentation vivante** → `docs/` (racine du dossier) et la racine du
  projet. Elle décrit l'**état courant** et se met à jour avec le code :
  `docs/audit-journal.md`, `docs/roles-and-permissions.md`,
  `docs/vps-deployment.md`, `docs/*-architecture.md`, `docs/*-plan.md`,
  `docs/legacy-import-cli.md`, plus `README.md`, `CLAUDE.md`,
  `gls-crm-schema.md`, `gls-crm-laravel-structure.md`, `PROJECT_INVENTORY.md`.
- **Rapports datés** → `docs/rapports/<catégorie>/`. Ce sont des **traces
  historiques** : ce qui a été mesuré, audité ou migré à un moment donné.
  Elles ne sont jamais réécrites pour refléter l'état courant, et jamais
  supprimées.

Catégories existantes (voir `docs/rapports/README.md` pour l'index complet) :

| Dossier | Contenu |
|---|---|
| `docs/rapports/audits/` | audits généraux, autorisations, production |
| `docs/rapports/finance/` | audits financiers, caisse, invariants monétaires |
| `docs/rapports/performance/` | mesures et rapports d'optimisation |
| `docs/rapports/postgres/` | audit et migration PostgreSQL |
| `docs/rapports/migration-inertia/` | migration Livewire → Inertia + React |
| `docs/rapports/ui/` | thème PreSkool et interface |

**Un nouveau rapport ne se dépose jamais à la racine du projet.** Il va dans
la catégorie qui lui correspond (ou une nouvelle catégorie si aucune ne
convient) et s'ajoute au tableau de `docs/rapports/README.md` dans le même
changement — sinon l'index ment dès le rapport suivant.

⚠ **Déplacer un rapport casse les liens qui le citent.** Ces fichiers sont
référencés depuis des commentaires de code, des tests, `routes/backoffice.php`
et d'autres documents. Après tout déplacement, réécrire chaque référence puis
vérifier qu'aucun chemin `.md` cité ne pointe dans le vide. Exception :
`resources/theme-reference/` est en lecture seule (§3) — ses pointeurs
périmés se laissent tels quels, on n'édite pas ce dossier pour les corriger.
