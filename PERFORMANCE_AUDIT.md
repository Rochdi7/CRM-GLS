# GLS CRM Backoffice — Performance Audit

Date: 2026-07-29. Scope: `/backoffice/*` (admin area only). Method: static code
audit (4 parallel read-only agents covering Livewire components, context/auth
services, database schema/indexes, and views/assets) + an in-process HTTP
probe (`scratchpad/probe.php`) that logs in as the local super-admin and
measures real Laravel requests (SQL query log, wall time, response size)
against local SQLite demo data. No files were modified during this phase.

## 1. Executive summary

The app is architecturally sound — no true N+1 was found in any list's
`@foreach` loop, no `wire:poll`, searches already use `.live.debounce.400ms`
on primary fields, and pagination/eager-loading follow the documented
patterns. The real cost is a **fixed per-request tax paid on every single
page and every single Livewire round trip**, caused by three compounding
issues:

1. `CurrentContext` (the year/center singleton shared to every view) never
   memoizes its resolved models — every `anneeScolaireId()`/`etablissementId()`
   call is a fresh `SELECT … WHERE id = ?`, and components call it 2–6 times
   per render.
2. Several "pick list" queries (all students, all groups, all teachers, all
   établissements) run inside `render()` unmemoized, so they re-execute on
   every keystroke of every search box and every modal field, not just on
   page load.
3. The global asset shell loads ~430–460 KB of JS/CSS (moment.js, two date
   pickers, Feather icons, a duplicate FontAwesome subset) that no page in
   the app currently uses, plus the shared `<x-backoffice.ui.table>` and
   dashboard `.counter` classes accidentally collide with two theme jQuery
   plugins (`DataTable()`, `counterUp()`) that were never loaded — this
   throws a `TypeError` in the theme's ready handler on ~27 pages and
   silently skips sidebar `slimscroll` initialization.

None of this requires touching the PreSkool design, Bootstrap, routes, or
business logic. The fixes are memoization, debouncing, query scoping,
`#[Computed]`, and moving four inert `<script>`/`<link>` tags behind
`@push`.

## 2. Measured baseline (local dev, SQLite, APP_DEBUG=true)

In-process probe, one PHP process per page, admin user with
`centers.access-all` (worst case: sees all 7 centers). See
`scratchpad/baseline.md` for the full JSON. Local demo data is tiny (12
students, 12 inscriptions, 13 employees, 6 encaissements) — **absolute ms
numbers are not representative of production MySQL under real volume**; the
query *counts* and *shapes* are the actionable signal.

| Page | Status | Wall ms | SQL queries | Exact dup queries | HTML KB |
|---|---|---|---|---|---|
| /backoffice/dashboard | 200 | 150 | 19 | 1 | 39.5 |
| /backoffice/employees | 200 | 337 | 15 | 0 | 101.7 |
| /backoffice/students | 200 | 281 | 14 | 0 | 106.4 |
| /backoffice/inscriptions | 200 | 264 | 18 | 1 | 101.1 |
| /backoffice/groups | 200 | 208 | 19 | 1 | 53.6 |
| /backoffice/users | 200 | 171 | 14 | 0 | 66.4 |
| /backoffice/roles | 200 | 152 | 11 | 0 | 48.3 |
| /backoffice/caisses | 200 | 198 | **53** | **6** | 95.9 |
| /backoffice/encaissements | 200 | 195 | 20 | 0 | 63.9 |
| /backoffice/depenses | 200 | 272 | 29 | 1 | 115.0 |
| /backoffice/settings | 200 | 338 | 19 | 0 | 135.5 |

Livewire `search` update (`POST /livewire-*/update`):

| Page | Wall ms | Queries | Payload KB |
|---|---|---|---|
| employees | 49 | 12 | 43.5 |
| students | 39 | 8 | 39.2 |
| inscriptions | 35 | 11 | 42.1 |
| encaissements | 39 | 10 | 18.1 |

Global shell on every page: **12 `<script src>` tags, 10 `<link rel=stylesheet>`
tags**, ~3 MB uncompressed before any content image (theme `style.css` 623 KB,
`tabler-icons.woff2` 756 KB, Livewire runtime 233 KB, jQuery+Bootstrap+moment+
2 date pickers+Feather ≈ 500 KB — see §6).

## 3. Backend bottlenecks

### 3.1 `CurrentContext` has no request memoization (highest impact)

`app/Services/Context/CurrentContext.php` is registered as a per-request
singleton (`AppServiceProvider.php:24`) but holds no state — every accessor
re-queries:

- `anneeScolaire()` (`CurrentContext.php:33-48`): `AnneeScolaire::find($id)`
  every call (+ up to 2 fallback queries when the session has no year).
- `anneeScolaireId()` (`:50-53`): hydrates the **full model** just to read
  `->id`.
- `etablissement()` (`:74-88`) / `etablissementId()` (`:90-93`): same pattern,
  plus a permission check.
- `availableAnnees()` (`:66`) / `availableCentres()` (`:132-142`): full-table
  `SELECT *`, re-run by anything that calls them (currently only
  `ContextSwitcher`, but nothing prevents duplication).

Verified duplicate calls **within a single `render()`**:
`GroupsIndex.php:249` and `:259` (year, twice); `InscriptionsIndex.php:528`
and `:549` (twice); `DashboardStats.php:34-35,66-67` (4 calls);
`WithCenterContext::scopeToActiveCenter()` (`Concerns/WithCenterContext.php:38`)
adds one more `Etablissement::find` **every time it's invoked** — 2–3× per
render in Employees/Students/Groups/Depenses/etc.

`ContextSwitcher` (rendered in the header on **every** authenticated page —
`header.blade.php:52`) alone costs ~4-5 queries per page load
(`ContextSwitcher.php:31-43`).

**Estimated fixed overhead before any page content**: ~11-15 queries per full
page load (session read/write, user, Spatie permission cache read, roles,
permissions, `CenterAccessService`'s one `employees` lookup, plus 4-9 context
queries depending on how many components the page mounts), repeated in
degraded form (3-6 fewer) on every Livewire AJAX round trip.

**Fix**: add private `?AnneeScolaire $resolvedYear` / `?Etablissement
$resolvedCenter` (+ resolved-flags) to the singleton; invalidate in
`setAnneeScolaire()`/`setEtablissement()` (same-request switcher writes must
see the new value). Request-scoped only — no cross-user state, safe.
**Risk: low.**

### 3.2 Unbounded option lists re-queried on every render, not just page load

None of these are wrapped in `#[Computed]`, so a bare Livewire update (a
debounced search keystroke, an undebounced date/amount field, or any
`wire:model.live` toggle) re-runs them:

| Component | Query | Line |
|---|---|---|
| InscriptionsIndex | ALL students `->get()` | `InscriptionsIndex.php:541-544` |
| InscriptionsIndex | ALL groups of the year `->get()` | `:546-550` |
| EncaissementsIndex | ALL students `->get()` | `EncaissementsIndex.php:383-387` |
| RemboursementsIndex | ALL students `->get()` | `RemboursementsIndex.php:236-240` |
| GroupsIndex | ALL teacher-category employees | `GroupsIndex.php:270-273` |
| GroupsIndex | ALL active frais catalog | `GroupsIndex.php:274` |
| DepensesIndex | ALL scoped groups | `DepensesIndex.php:344-347` |
| Employees/Students/Caisses/Settings\SallesTab | établissements list | bounded (7 rows), low impact |

Compounded in `InscriptionsIndex` by an **undebounced**
`wire:model.live="feeLines.{{$i}}.montant_initial"`
(`inscriptions-index.blade.php:288`) — every keystroke in a fee amount field
re-fetches all students and all groups.

**Fix**: wrap in `#[Computed]` (Livewire caches per-request automatically) —
zero behavior change, same data, computed once per request instead of once
per render call. **Risk: low** (must verify the computed name doesn't collide
with an existing public property).

### 3.3 Undebounced Livewire filters

`wire:model.live` with **no debounce** on fields that trigger a full list
re-render:

- `EncaissementsIndex`: `dateFrom`/`dateTo` (`encaissements-index.blade.php:28,30`)
- `DepensesIndex`: `dateFrom`/`dateTo` (`depenses-index.blade.php:20,22`)
- `RemboursementsIndex`: `dateFrom`/`dateTo` (`remboursements-index.blade.php:14,16`)
- `CaisseJournal`: `dateFrom`/`dateTo` (`caisse-journal.blade.php:65,67`)
- `InscriptionsIndex`: `feeLines.*.montant_initial` (`inscriptions-index.blade.php:288`,
  §3.2 above)

Date inputs fire one event per native `<input type=date>` change (not
per-keystroke) so the practical harm is smaller than a text field, but each
fire re-runs the full filtered query + (for Depenses) a `SUM()` + the §3.2
option-list reloads. **Fix**: add `.debounce.400ms` where the field is a
free-typed value; date pickers can stay live since the browser widget already
limits event frequency — recommend leaving dates as-is and confirming with
manual testing, only debouncing `montant_initial`. **Risk: none** — additive
Blade attribute only.

### 3.4 `CaisseJournal` — heaviest single component

`app/Livewire/Backoffice/Caisses/CaisseJournal.php:121-217` loads **four
full, unpaginated collections** (`Encaissement`, `Depense`, `Remboursement`,
`CaisseTransfer`) per till in scope, merges + sorts + paginates **in PHP**
(`slice()` at `:244`). It is mounted **twice** on the caisses page
(`backoffice/caisses/index.blade.php:36,39` — scope `mine` and `all`)
alongside `CaisseTransfersIndex` and `CaissesIndex` on the same page load —
this is the direct cause of the 53-query/6-duplicate measurement on
`/backoffice/caisses`. `CaisseProvisioner::provisionFor()` also runs inside
the render path (`CaisseJournal.php:104`) — an EXISTS check + possible INSERT
on every render, not just `mount()`.

**Fix** (moderate risk, still safe): move `provisionFor()` to `mount()` only;
debounce `dateFrom`/`dateTo`; defer the `all`-scope journal load until its
tab is actually opened (Livewire's native lazy-loading — `#[Lazy]` — is the
cleanest mechanism and does not change markup/behavior, only initial load
timing). A full SQL-`UNION` rewrite of the four-table merge is **out of
scope for this pass** — flagged as a future item (§ items not changed).

### 3.5 Small wasted eager-loads (free wins, zero risk)

Relations eager-loaded but never rendered in the corresponding list view —
removing them cuts one query + hydration cost per render with **no behavior
change** (verified against each Blade view):

- `EmployeesIndex.php:265` — `user` (unused in `employees-index.blade.php`)
- `GroupsIndex.php:244` — `salle`, `anneeScolaire` (unused in the list row)
- `InscriptionsIndex.php:523` — `anneeScolaire` (unused in the list row)
- `EncaissementsIndex.php:364` — `fee.inscription.group` (only `fee` itself
  is used; the nested chain is not), `agent` (unused)
- `DepensesIndex.php:327` — `agent` (unused)

### 3.6 `EncaissementsIndex` redundant per-render queries

Two near-identical caisse queries back to back (`EncaissementsIndex.php:351-361`);
`resteDuFee()` (`:428`) performs a `find()` + `SUM()` on every render once a
fee is selected, and again inside `rules()` (`:142`) during validation — same
value computed twice per submit. **Fix**: cache the `InscriptionFee` lookup
result within the request (a private property set once in `updatedFeeId`-style
hook), reuse in both places. **Risk: low**, purely internal.

### 3.7 `ManageAuthorization` re-queries the role/permission graph per checkbox

`wire:model.live` on `selectedRoles` and `directPermissions`
(`manage-authorization.blade.php:66,205`) triggers `$this->user->load(...)`
+ 2 `Role` queries (`ManageAuthorization.php:81-97`) on **every checkbox
click**. Acceptable at current role-table scale (6 roles, 65 permissions);
flagged but **not changed in this pass** — reducing round-trips here would
mean batching checkbox state client-side, which risks the authorization
UX/behavior the CLAUDE.md explicitly protects. Left as a documented future
item.

## 4. Frontend bottlenecks

### 4.1 Dead-plugin `TypeError` aborts the theme's ready handler on ~27 pages

`<x-backoffice.ui.table>` (`components/backoffice/ui/table.blade.php:17`)
renders `class="table datatable ..."`; dashboard KPI cards render
`class="counter"` (`dashboard-stats.blade.php:19,36,56,76`). The vendor
`public/assets/preskool/js/script.js` (never edited — read only) calls,
unconditionally guarded only by element *existence*:

```js
// script.js:150-151
if ($('.datatable').length > 0) { $('.datatable').DataTable({...}); }
// script.js:294
if ($('.counter').length > 0) { $('.counter').counterUp({...}); }
```

Neither `DataTable` nor `counterUp` is loaded by any script tag in the
project (`js/script.js`'s own dependency list was checked — absent). This
throws a `TypeError` inside `$(document).ready(...)`, which **aborts
everything after that line in the same handler** — including sidebar
`slimscroll` init (`script.js:397`) and submenu logic (`:417`, currently
unused by our flat sidebar so low practical impact, but the console error is
real and permanent on every list/show page (26 views use `ui.table`) and the
dashboard.

**Fix**: drop the `datatable` class from `ui/table.blade.php` (verify no
`.datatable`-scoped rule in `style.css` is load-bearing — checked: the only
`.datatable*` rules are `.datatable-length/-info/-paginate` wrapper classes
we don't use) and rename `.counter` to a non-colliding class (e.g.
`stat-counter`) on the four dashboard `<h2>`s, updating any SCSS/JS selector
that targets `.counter` (none found). **Risk: low** — pure class rename, no
visual change (no CSS rule keys off `.counter` or `.datatable` in our SCSS).

### 4.2 ~430-460 KB of unused global JS/CSS

Verified by grep across all non-`theme-reference` views: **zero** usages of
`.datetimepicker`, daterangepicker, or `moment()` anywhere in the app, and
**zero** `data-feather` nodes (feather.replace() replaces nothing).

| Asset | Size | Used anywhere? |
|---|---|---|
| `js/moment.js` | 172.3 KB | No |
| `plugins/daterangepicker/daterangepicker.js` + `.css` | 64.7 + 7.5 KB | No |
| `js/bootstrap-datetimepicker.min.js` + `.css` | 37.5 + 7.0 KB | No |
| `js/feather.min.js` + `css/feather.css` | 74.3 + 13.1 KB | No (0 `data-feather` nodes) |
| `plugins/fontawesome/css/fontawesome.min.css` | 55.9 KB | Redundant subset of `all.min.css` (also loaded) |

**Fix**: move the first four pairs behind `@push('styles')`/`@push('scripts')`
on demand (none needed today — kept documented for the day a real date-range
or date-time picker page ships) and drop `fontawesome.min.css` outright
(`all.min.css` is a superset, already loaded, and both together is pure
duplication). Feather requires a one-line stub
(`window.feather = { replace(){} }`) before `script.js` runs, since
`script.js:13` calls `feather.replace()` unconditionally — removing the
script without the stub would throw immediately. **Risk: low**, all four are
provably dead code paths verified by grep, not assumption.

Select2 (84 KB combined), jQuery, Bootstrap bundle, tabler-icons — all
confirmed in active use across 15+ Livewire pages and **stay global**, per
the user's standing rule that every CRUD dropdown uses Select2.

### 4.3 Images

- Student/employee avatars render the **original uploaded file**, full-size,
  in list rows (`students-index.blade.php:61-62`,
  `employees-index.blade.php:56-57`) — no media-library conversion is
  registered on either model (`Student.php`, `Employee.php` define
  `registerMediaCollections()` only). No `loading="lazy"`.
- Sidebar ships a 227 KB SVG on every page (`sidebar.blade.php:24`).

**Fix**: register a `thumb` media conversion (small, non-destructive —
originals untouched, `getFirstMediaUrl('photo')` calls without a conversion
name keep working exactly as today) and use it in the three list/show views;
add `loading="lazy"` to row avatars only (not the header logo, to avoid any
flash). **Risk: low**, additive; requires `spatie/laravel-medialibrary`'s
image-manipulation dependency check before implementing (confirmed present:
package `^11.23`).

## 5. Livewire bottlenecks

Covered in §3.1–3.7. Positives confirmed by the audit (kept as-is): zero
`wire:poll` anywhere; **no true N+1** in any list `@foreach` — every
relation accessed in a Blade loop is eager-loaded on the corresponding
`render()`; every list search already uses `.live.debounce.400ms`; modal
create/edit fields are consistently deferred `wire:model` (not `.live`);
`paginate()` is correct everywhere because every list view displays a total
count via `<x-backoffice.ui.pagination>` (switching to `simplePaginate()`
would remove UI functionality — **not done**, per the "do not remove
functionality" rule).

## 6. Database bottlenecks

Full detail from the DB audit agent. Key finding: **no business table
declares an explicit `->index()`** — all indexing today relies on
`foreignId()->constrained()`, which auto-indexes on MySQL (InnoDB requires an
index for every FK) but creates **no index at all on SQLite**. Local dev
is therefore not representative of production; adding explicit indexes below
also makes local SQLite behavior match MySQL.

Justified by real query patterns (§ full list in the DB agent's findings,
cross-checked against `app/Livewire`, `app/Http`, `app/Domain`):

| Table | Composite index | Real pattern |
|---|---|---|
| `encaissements` | `(caisse_id, date_paiement)` | Till journal + list filter + SUM, `caisses/show` recent-10 |
| `depenses` | `(caisse_id, date_depense)` | List filter + full-filter SUM, journal, `caisses/show` |
| `remboursements` | `(caisse_id, date_remboursement)` | List filter, journal, `caisses/show` |
| `inscriptions` | `(annee_scolaire_id, statut)` | Year-scoped list + statut filter + dashboard counts |
| `groups_historique` | `(archived_at)` | Full-table `ORDER BY archived_at DESC` |

`groups(annee_scolaire_id, statut)` and `caisse_transfers(statut)` were
considered and **excluded** — current row counts (4 groups, 2 transfers even
at seeded scale) don't justify a composite; the MySQL FK auto-index on
`annee_scolaire_id`/`caisse_source_id` is sufficient today. `students`,
`employees` searches are `LIKE '%term%'` (leading wildcard — index-unusable
on any engine) so **no index is proposed for search columns**, only for the
`caisse_id`/`annee_scolaire_id` prefix that the disjunctive center-scope
filter can't otherwise use efficiently.

**Also flagged (code-level, not indexes)**: `DashboardStats.php:52-53` uses
`whereMonth()`/`whereYear()` and several list filters use `whereDate()` —
both wrap the column in a SQL function, making it **non-sargable** (an index
on `date_paiement` alone would not be used). Since the columns are already
plain `DATE`/`DATETIME`, rewriting to plain range comparisons
(`whereBetween` / `>=` `<=`) is a pure correctness-neutral query change that
makes the composite indexes above actually effective. **Risk: low** — same
result set, verified against column types in the migrations.

Portable migration: `Schema::table('t', fn ($t) => $t->index([...]))` works
identically on SQLite and MySQL — one migration covers both environments.
`->fullText()` is **not** used (unsupported on SQLite; not needed since all
search is `%LIKE%` by design).

## 7. Asset bottlenecks

See §4.1–4.2. Summary: 12 script tags / 10 stylesheet tags globally, ~3 MB
uncompressed shell. Provably-dead weight (moment, both date pickers,
feather, duplicate FontAwesome core) = ~430-460 KB removable with zero
functional risk. Theme `style.css` (623 KB) and `tabler-icons.woff2`
(756 KB) are load-bearing and **not touched**.

## 8. Duplicate initialization problems

Audited `app/js/backoffice/app.js` and `theme.js`: **no duplicate-init bugs
found**. Every jQuery plugin re-init on `livewire:navigated` is already
guarded (`select2-hidden-accessible` check, `Tooltip.getInstance`/
`Popover.getInstance` checks, Feather's `replace()` is naturally idempotent).
Alpine is registered exactly once via `alpine:init` — confirmed zero
`import Alpine`/`Alpine.start()`/CDN script anywhere outside `theme-reference`
and zero `alpinejs` in `package.json`. No `@livewireScripts`/`@livewireStyles`
duplication. **This phase requires no changes** — it was a design constraint
that turned out to already be correctly implemented.

## 9. Risk level per proposed fix

| Fix | Risk | Reason |
|---|---|---|
| `CurrentContext` memoization | Low | Request-scoped, invalidated on write, no cross-user state |
| `#[Computed]` on option lists | Low | Same query, same result, cached per-request only |
| Debounce `montant_initial` fee field | None | Additive Blade attribute |
| `CaisseJournal`: move provisioning to `mount()`, `#[Lazy]` on `all` scope | Medium | Changes initial-load timing of one tab; verified against existing tests |
| Drop unused eager-loads | None | Verified unused in the corresponding Blade view |
| Rename `.counter`, drop `datatable` class | Low | No CSS rule depends on either class name (grep-verified) |
| Move moment/date-pickers/feather behind `@push` | Low | Grep-verified zero current usage; documented for future re-add |
| Drop duplicate `fontawesome.min.css` | None | Strict subset of `all.min.css`, already loaded |
| DB index migration | Low | Additive, portable, no data change, new migration file (no edits to existing migrations) |
| Non-sargable date query rewrite | Low | Same result set, verified against column types |
| Media conversion + lazy avatars | Low | Additive conversion, originals untouched, existing URLs unaffected |

## 10. Exact files planned for modification

Backend: `app/Services/Context/CurrentContext.php`,
`app/Livewire/Backoffice/{Groups/GroupsIndex,Inscriptions/InscriptionsIndex,
Encaissements/EncaissementsIndex,Employees/EmployeesIndex,
Depenses/DepensesIndex,Remboursements/RemboursementsIndex,
Caisses/CaisseJournal,Dashboard/DashboardStats}.php`,
`app/Livewire/Backoffice/Concerns/WithCenterContext.php`,
`app/Models/Student.php`, `app/Models/Employee.php`.

Views: `resources/views/components/backoffice/ui/table.blade.php`,
`resources/views/livewire/backoffice/dashboard/dashboard-stats.blade.php`,
`resources/views/livewire/backoffice/inscriptions/inscriptions-index.blade.php`,
`resources/views/livewire/backoffice/{students,employees}/*-index.blade.php`
(avatar tags), `resources/views/components/backoffice/layout/{head,scripts}.blade.php`.

New: one migration `database/migrations/*_add_performance_indexes_to_finance_and_academic_tables.php`.

Untouched (confirmed by design and re-confirmed at the end):
`resources/views/theme-reference/preskool/`, `public/assets/preskool/`,
all route files/names, all controller/Livewire namespaces, all Form Requests,
all Domain actions, `lang/fr.json` (only additions, no deletions).

## 11. Expected impact

- Per-page fixed query overhead: −4 to −9 queries (context memoization +
  dropped unused eager-loads), most visible on `/backoffice/caisses`
  (53 → roughly mid-20s expected, to be re-measured in Phase 17).
- Every Livewire round trip on Inscriptions/Encaissements/Remboursements/
  Groups/Depenses: stops re-fetching full option tables on unrelated field
  changes.
- Global page weight: −430 to −460 KB uncompressed JS/CSS, removed console
  `TypeError` on 27 pages, sidebar `slimscroll` init restored.
- Money-table read paths (till journal, dashboard revenue, list filters) gain
  usable composite indexes on production MySQL; local SQLite dev gets its
  first real secondary indexes.

## 12. Items intentionally not changed

- `CaisseJournal`'s four-table PHP merge is not rewritten as a SQL `UNION` —
  too large a behavior-risk change for this pass (pagination semantics,
  sorting, transaction-safety of a hand-rolled union across differently-shaped
  tables). Mitigated instead via lazy tab loading + moving the provisioning
  side-effect out of the render path.
- `ManageAuthorization`'s per-checkbox re-query is left as-is — acceptable at
  current scale (6 roles/65 permissions) and any fix risks the authorization
  UX explicitly protected by CLAUDE.md §16.
- `paginate()` → `simplePaginate()` — not applied anywhere; every list
  displays a total count, so the exact-count requirement in the task's own
  Phase 5 rules this out everywhere it was considered.
- FULLTEXT search indexes — not applicable; all search is `LIKE '%term%'`
  by design and FULLTEXT is unsupported on SQLite (would break local dev).
- No caching layer (`Cache::remember`) added for reference data — the
  memoization fix in §3.1 already eliminates the duplicate queries at the
  request level, which is where the actual repetition was measured; a
  cross-request cache was judged unnecessary added complexity for tables of
  2–49 rows.
