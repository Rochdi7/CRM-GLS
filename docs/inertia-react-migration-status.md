# Inertia + React Migration — Status Log

Running log of verified milestones. Append one entry per phase; do not rewrite history.

> **Migration status: COMPLETE.** As of Phase 11
> (`docs/phase-11-final-verification.md`), the backoffice is 100% Inertia +
> React + TypeScript and Livewire has been entirely removed from the
> codebase — no `livewire/livewire` package, no `app/Livewire/`, no
> `resources/views/livewire/`. Every phase entry below is a historical
> record of work as it stood *at that point in time* — many older entries
> correctly describe modules as "still Livewire" or "coexisting," which was
> true when written and is superseded by later entries (and finally by
> Phase 11) further down/up this log. Read entries as a changelog, not as
> a description of the current architecture.

---

## Phase 11 — Livewire removal and final cleanup

**Date**: 2026-07-31
**Status**: **Complete.**

Closed 2 confirmed test-coverage gaps (Salles tab center scoping, Users list
center-following-with-admin-visibility) plus 13 further gaps discovered
during the audit, all documented in `docs/phase-11-test-coverage-mapping.md`,
before any deletion began. Classified every remaining Livewire-era file in
`docs/phase-11-dependency-graph.md` (SAFE TO DELETE / STILL ACTIVE / SHARED
WITH INERTIA / UNCERTAIN — all UNCERTAIN items independently re-verified and
closed). Deleted, module by module, with a focused test run and a commit
after each group: Students, Inscriptions, Groups, Employees (+ the old
un-namespaced `EmployeeController`), Users/Authorization, Settings tabs,
Roles, Caisses (+ `CaisseManagementController`), Encaissements, Depenses
(+ `DepenseManagementController`), Remboursements, CaisseTransfers
(+ `WithCaisseSelection`), then the shared Livewire components
(DashboardStats, ProfilePage, ContextSwitcher, the missed
`TypesDepensesIndex`), the 3 remaining Concerns traits, the entire old admin
Blade layout shell, the backoffice JS/SCSS bundle, and the shared Blade
widget library (`forms/*`, `ui/*`). Removed `livewire/livewire` from
`composer.json` once zero real references remained anywhere in the
repository (confirmed via repeated repo-wide greps). Ran a final whole-
application verification pass (routes, TypeScript, build, full test suite —
both per-directory and one successful combined run, 307/307 passing) and a
performance baseline against the current PostgreSQL dev database — see
`docs/phase-11-final-verification.md` and `docs/phase-11-performance-baseline.md`.
Rewrote `CLAUDE.md` §1/§4-7/§11/§16/§17, `README.md`, and
`docs/backoffice-architecture.md` to describe the current Inertia+React
architecture instead of the retired Livewire one; added superseded-banners
to this log and to `docs/inertia-react-migration-{plan,audit}.md`; fixed
`resources/js/Config/backofficeNavigation.ts`'s stale header comment and
restored the `inertia: true` flag / previously-hidden Finance nav items
(Cash management, Expense management, Expense types) that were leftover
from earlier phases despite their routes being fully working Inertia pages.
**Wrap-up addendum (same date):** the concurrent UX/i18n frontend refactor
was reviewed, verified, and committed as three focused commits (`9538a9a`
i18n + loading states, `8505777` super-admin nav visibility, `ec6f5bc`
seeder/faker-locale); the three Finance sidebar items (Cash management /
Expense management / Expense types) were re-exposed after verifying every
enablement condition (`b080265`); the final combined test suite passed
**307/307 (1531 assertions)** in a single process with no stall; and a
real-browser Playwright/Chromium smoke pass covered every module — 25/25
checks as super-admin (0 console errors, 0 failed requests) and 7/7 as a
limited-role teacher (sidebar gating + real 403s confirmed). Only
visual-only checklist items (dark mode, RTL, mobile widths) remain manual
— see `docs/phase-11-manual-browser-checklist.md`. Final report:
`docs/phase-11-livewire-cleanup-report.md` — **PHASE 11 COMPLETE**.

## Phase 10 — Finance migration (Caisses, Encaissements, Dépenses, Remboursements, Transferts)

**Date**: 2026-07-31
**Status**: **Complete.**

Migrated the entire Finance domain from Livewire to Inertia + React: the
"Gestion de la caisse" tabbed page (Ma caisse / Journal des transactions /
Transferts / Comptes de caisse), Payments (Encaissements, with the
cascading student→inscription→fee-lines payment form), and the "Gestion
des dépenses" tabbed page (Dépenses with justificatif uploads /
Remboursements). Full audit in `docs/phase-10-finance-audit.md` and
implementation mapping (including 6 open questions resolved with the user
before any code was written) in `docs/phase-10-finance-mapping.md`. All
legacy Livewire components and Blade views are left completely untouched as
the unreferenced rollback fallback.

### Audit findings and resolved open questions

The audit found the same dead-code shape Phase 9 found for
`inscription-fees.*`: every Store/Update Form Request and most controller
mutation methods for these five modules existed in code but were entirely
unrouted — the Livewire components' own hand-written `rules()`/`save()`
were the real source of truth and were read in full rather than assumed
from the (sometimes subtly diverging) dead controllers.

Six genuine open questions were surfaced to the user before implementation,
all resolved with the recommended, most-conservative option (**preserve
current behavior exactly, invent no new business rules** — see the mapping
doc for full detail):

1. **No insufficient-balance / no maximum-refund-amount check** exists
   anywhere (a Dépense, Remboursement, or validated Transfer can drive a
   till negative; a refund is capped only at `min:0.01`) — preserved as-is.
2. **Remboursements has zero detail/show page** anywhere in the live app —
   not added.
3. **`CaisseTransferController::validateAction()`'s Directeur-level-role
   TODO** (never acted on, and never reachable until this phase) — left as
   unaddressed technical debt, gated by `cash-transfers.validate` only.
4. **`CaisseJournal`'s confirmed performance bottleneck** (4 unpaginated
   collections merged/sorted in PHP per scope, mounted twice per page load)
   — ported as-is; a rewrite is deferred to a dedicated follow-up.
5. **Currency-suffix inconsistency ("DH" vs "MAD")** — preserved per-page
   exactly as each already-migrated Show page uses it (Caisses/
   CaisseTransfers: "DH"; Encaissements/Dépenses: "MAD").
6. **Sidebar Blade/React divergence for "Expense types"** — no code change;
   the Blade sidebar is not rendered once every finance page is Inertia.

### A real bug found and fixed during controller rewrite

The dead `CaisseTransferController::update()` used a hard `abort_unless(...,
403)` for the "not pending" guard and relied on `ResolvesActingEmployee`'s
hard 403 for "no employee record" — but the live Livewire
`CaisseTransfersIndex::save()`/`validateTransfer()` have always used a
**soft form error** for both (no exception, modal stays open with an inline
message). Reproducing the dead controller's hard-abort behavior verbatim
would have been a real UX regression (a full-page 403 instead of an inline
error) invisible until this phase actually routed these actions for the
first time. Fixed by using `ValidationException::withMessages()` instead of
`abort_unless()`/`ResolvesActingEmployee` throughout all five new
Inertia-facing action methods (`EncaissementController::store()`,
`DepenseController::store()`, `RemboursementController::store()`,
`CaisseTransferController::store()`/`update()`/`validateAction()`).

### Files created

- Read-models: `App\Domain\Finance\Queries\{GetCaissesList,GetCaisseJournal,
  GetCaisseTransfersList,GetRemboursementsList}`,
  `App\Domain\Expenses\Queries\GetDepensesList`,
  `App\Domain\Payments\Queries\{GetEncaissementsList,
  GetInscriptionUnpaidFees}`.
- React pages: `resources/js/Pages/Backoffice/{Caisses,Encaissements,
  Depenses}/Index.tsx` (Caisses hosts Journal + Transferts + Comptes as
  client-side tabs; Depenses hosts Remboursements as a client-side tab).
- Tests: `tests/Feature/Backoffice/Finance/{CaissesInertiaCrudTest,
  EncaissementsInertiaCrudTest,DepensesInertiaCrudTest,
  RemboursementsInertiaCrudTest,CaisseTransfersInertiaCrudTest}.php`.

No new shared components — every modal/table/filter reuses the Phase 6-9
component library (Modal, Card, DataTable, TableToolbar, SearchInput,
Pagination, RowActions, SelectField, FormField, TextareaField, FormActions,
StatusBadge, EmptyState).

### Files modified

- Controllers: `CaisseController` (gained `index`/`journal`, `show`
  unchanged), `EncaissementController` (gained `index`/`store`/`update`/
  `studentInscriptions`/`inscriptionFees`, `show` unchanged),
  `DepenseController` (gained `index`/`store`/`update`/
  `removeJustificatif`, `show` unchanged), `RemboursementController`
  (rewritten to `store`/`update` only — its list is now served by
  `DepenseController::index()`), `CaisseTransferController` (gained
  `store`/`update`/`validateAction` — renamed from `validate()`, which
  collided with the inherited `ValidatesRequests::validate()` helper method;
  `show` unchanged). `CaisseManagementController`/
  `DepenseManagementController` are now unreferenced (their `abort_unless`
  gate logic moved into `CaisseController::index()`/`DepenseController::
  index()` directly).
- Form Requests: `Store/UpdateDepenseRequest` gained `justificatifs.*`
  validation (previously undeclared — dead code never exercised a file
  upload); `StoreEncaissementRequest` fully rewritten to the real multi-row
  cascade shape (`payment_lines.*`, with a closure rule enforcing the
  per-row remaining-balance cap, mirroring the Livewire component's dynamic
  `max:reste` rule exactly — the pre-Phase-10 version validated a
  single-fee shape that never matched the live form).
- `routes/backoffice.php` — added `caisses.journal`,
  `encaissements.{store,update}`, `students.inscriptions-for-payment`,
  `inscriptions.unpaid-fees`, `depenses.{store,update,
  justificatifs.destroy}`, `remboursements.{store,update}`,
  `caisse-transfers.{store,update,validate}`; removed the
  `EncaissementsIndex` Livewire route import.
- `resources/js/Config/backofficeNavigation.ts` — Cash management, Payments,
  Expense management nav items marked `inertia: true`.
- `resources/js/Types/index.ts` — additive only: `FinanceOption,
  CaisseRow, CaisseJournalRow, CaisseJournalData, CaisseTransferRow,
  CaisseTransferFormOption, CaissesPageProps, EncaissementRow,
  EncaissementsFilters, EncaissementsPageProps, UnpaidFee, PaymentLine,
  DepenseRow, DepensesFilters, RemboursementRow, DepensesPageProps`.

### Routes

| Route | Before | After |
|---|---|---|
| `backoffice.caisses.index` (GET) | `CaisseManagementController` (Blade shell) | `CaisseController@index` |
| `backoffice.caisses.journal/{scope}` (GET, new) | — | `CaisseController@journal` (AJAX partial for tab filters) |
| `backoffice.encaissements.index` (GET) | `EncaissementsIndex` (Livewire) | `EncaissementController@index` |
| `backoffice.encaissements.store` (POST, new) | — | `EncaissementController@store` |
| `backoffice.encaissements.update` (PUT, new) | — | `EncaissementController@update` |
| `backoffice.students.{student}.inscriptions-for-payment` (GET, new) | — | `EncaissementController@studentInscriptions` |
| `backoffice.inscriptions.{inscription}.unpaid-fees` (GET, new) | — | `EncaissementController@inscriptionFees` |
| `backoffice.depenses.index` (GET) | `DepenseManagementController` (Blade shell) | `DepenseController@index` |
| `backoffice.depenses.store` / `.update` (new) | — | `DepenseController@store` / `@update` |
| `backoffice.depenses.justificatifs.destroy` (DELETE, new) | — | `DepenseController@removeJustificatif` |
| `backoffice.remboursements.store` / `.update` (new) | — | `RemboursementController@store` / `@update` |
| `backoffice.caisse-transfers.store` / `.update` (new) | — | `CaisseTransferController@store` / `@update` |
| `backoffice.caisse-transfers.validate` (PUT, new) | — | `CaisseTransferController@validateAction` |

`backoffice.remboursements.index`/`backoffice.caisse-transfers.index` remain
the same intentional redirect stubs (deep-link to the tab, clean 403 for
unauthorized visitors) — unchanged. No destroy route anywhere in this
domain — money records are never deleted.

### Prop shapes

Every list page's props are explicit arrays from a Domain Query class, no
raw Eloquent models. `CaissesPageProps` conditionally nulls out
`caisses`/`journalMine`/`journalAll` vs `transfers`/`transferStatutCounts`
depending on which of the two view permissions the user holds (mirrors the
Blade shell's own per-tab `@can` gating). `DepensesPageProps` does the same
for `depenses` vs `remboursements`. Money always crosses the wire as
`number_format($x, 2, '.', '')` strings (the established `MoneyDisplay`
convention), never raw floats.

### Business rules preserved

Auto-provisioned tills with zero manual CRUD; the cascading payment form
(student→inscription→one row per unpaid fee, no till picker, the acting
employee's own till always used); the multi-row single-submit
`DB::transaction` with per-row fee-ownership verification and full rollback
on any invalid row; every create-vs-edit field-freeze asymmetry (montant/
caisse_id frozen after creation for Encaissements/Dépenses/Remboursements;
tills/amount frozen for Transfers); the two-step transfer request→validate
workflow with `lockForUpdate()` pessimistic locking and the triple-layered
self-validation defense (UI hide, policy gate, Domain-action refusal); the
justificatif upload mime/size contract; the deliberate absence of balance
floor and refund-cap checks.

### Financial rules preserved

All server-side money math stays in the existing, unmodified Domain actions
(`EnregistrerEncaissement`, `EnregistrerDepense`, `EnregistrerRemboursement`,
`DemanderTransfertCaisse`, `ValiderTransfertCaisse`) — none of their
transaction boundaries, locking, or arithmetic were touched. Every React
preview (payment-line running totals, remaining-balance caps) is
display-only; the server independently re-validates and recomputes on save,
including the per-row `max:reste` cap and the cross-registration
fee-ownership guard.

### PostgreSQL transaction safety

Reviewed every new mutation for the Phase-9-class bug (a failed statement
aborting the whole request transaction, not just itself). No new delete
capability was added for any of the four money-movement modules in this
phase (per the preservation list in the mapping doc), so no new
FK-restrict-on-delete exposure was introduced. The multi-row Encaissement
submit's `DB::transaction()` wrap was verified to roll back completely (zero
rows persisted, balance unchanged) when a later row is invalid — covered by
`test_an_invalid_row_rolls_back_the_whole_multi_row_submit`.

### Tests added

150 Finance tests total after this phase (108 existing Livewire-side +
42 new Inertia-side across 5 files): `CaissesInertiaCrudTest` (5),
`EncaissementsInertiaCrudTest` (10, including the multi-row rollback and
tampered-fee-ownership cases), `DepensesInertiaCrudTest` (8, including
receipt upload/mime-rejection/removal), `RemboursementsInertiaCrudTest` (6,
including an explicit test asserting the deliberate absence of a
maximum-refund cap), `CaisseTransfersInertiaCrudTest` (13, including
cross-employee validation, self-validation refusal, double-validation
prevention, and cross-center denial).

### Test results

| Check | Result |
|---|---|
| `artisan test tests/Feature/Backoffice/Finance/` | ✅ 150/150 passing (108 existing Livewire-side + 42 new Inertia-side) |
| Every other test directory, run individually | ✅ All passing — Unit 4/4, Authorization 44/44, Context 16/16, Groups 30/30, Inertia 88/88, Inscriptions 43/43, People 31/31, Settings 24/24, Students 43/43 (473/473 total across all directories) |
| Full suite in one combined `artisan test` invocation | ⚠️ Not completed — see note below |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 529.14 kB / 139.91 kB gzip (Phase 9 baseline: 492.86 kB / 133.58 kB gzip) |

**Full-suite invocation note**: multiple attempts to run the complete suite
in one `artisan test` (or `phpunit`) invocation stalled partway through —
each time the PHP process dropped to near-zero CPU usage with a Postgres
connection left `idle in transaction` on a `DEALLOCATE pdo_stmt_...`
statement, never recovering. This was investigated at length: it is not a
concurrent-session collision (verified via process creation timestamps —
no other session was touching this repository or database), not a
`RefreshDatabase` deadlock in the normal sense (one specific instance of
that real, transient deadlock class *was* captured while investigating —
the same pre-existing operational hazard documented in this file's own
Phase 6 entry above — but re-running that same directory alone immediately
afterward passed cleanly, confirming it was transient contention from
overlapping diagnostic commands during investigation, not a permanent
defect), and reproduced identically whether
piping through `tail`, redirecting straight to a file, or invoking every
directory explicitly by name in one command. Every individual test
directory — including `Finance` in full — passes cleanly and quickly on
its own; the stall appears tied specifically to chaining many directories
together in a single long-running PHPUnit process in this environment, not
to any code from this phase. This is reported honestly as an unresolved
environment limitation rather than a claimed-but-unverified full-suite
pass — the 473 individually-verified tests plus 150 Finance tests
(counted once, not twice — Finance is included in both figures) are the
real regression evidence for this phase.

### Performance measurements

- Caisses list: 5 SQL queries.
- Caisse journal (one scope): 12 SQL queries (the 4-table PHP merge,
  confirmed unchanged per Q4) — ~24 on initial "Gestion de la caisse" page
  load (both `mine` and `all` scopes fetched together), consistent with the
  audit's own characterization of this as the heaviest single component in
  the app.
- Caisse Transfers list: 1 query.
- Encaissements list: 8 queries.
- Dépenses list: 2 queries (list + separate full-filtered-set total).
- Remboursements list: 1 query.
- Bundle grew by ~36.3 kB / ~6.3 kB gzip over Phase 9 — the largest
  multi-page addition yet (3 substantial pages, one with the most complex
  cascading form in the app), still using only the existing shared
  component library.

### Manual browser verification

Not yet performed — pending user verification in a real browser (per the
checklist in the phase instructions: Caisse tabs and journal filters,
Encaissement create with the full cascade + receipt-free chèque fields,
Dépense create/edit with justificatif upload and removal, Remboursement
create/edit, Transfer request/validate/reject/cancel, same-caisse and
insufficient-balance-adjacent behavior, flash messages, modal focus,
mobile responsiveness).

### Known limitations

None blocking. All limitations are the deliberately-preserved absences
documented in the 6 resolved open questions above (no balance floor, no
refund cap, no Remboursement detail page, the unaddressed validate() TODO,
the unaddressed CaisseJournal performance bottleneck, the DH/MAD
inconsistency) — every one a considered "preserve exactly" decision, not an
oversight.

---

## Phase 9 — Inscriptions (registrations) migration

**Date**: 2026-07-31
**Status**: **Complete.**

Migrated Inscriptions — the most business-critical module — from Livewire to
Inertia + React: list + modal create/edit, inline new-student creation, and
the repeatable fee-lines editor with live percentage/fixed-DH discount
preview. `App\Livewire\Backoffice\Inscriptions\InscriptionsIndex` and its
view are left completely untouched as the unreferenced rollback fallback.
Full audit in `docs/phase-9-inscriptions-audit.md` and field-by-field mapping
in `docs/phase-9-inscriptions-mapping.md` (both written before any code, per
the task's own requirement).

### Existing behavior discovered

- The create/edit form has a genuine, deliberate asymmetry: on **create**,
  `date_debut`/`date_fin` and the center/academic year are always re-derived
  from the selected **group** (readonly in the UI; a tampered client value is
  silently overridden server-side), while on **edit** those same two date
  fields are trusted directly from the request — only 6 columns
  (student/group/statut/date_inscription/date_debut/date_fin/note) are ever
  written on update, fees/totals/center/year are never touched again.
  Preserved exactly, not "fixed" into a symmetric behavior.
- A new registration always starts `Inscription::STATUT_ACTIVE` server-side —
  `StoreInscriptionRequest` has no `statut` field at all, matching
  `InscriptionsIndex::save()`'s `!$editing` branch.
- Only the group's **active** catalog fees are ever offered as "Frais
  disponibles"; every fee the group carries is billed (no opt-out checkbox),
  each fee line's due date is pre-filled from the group's own per-fee pivot
  `date_echeance`, and `InscriptionFee::computeMontant()` applies percentage
  discount with priority over a fixed-DH discount when both are present —
  all reproduced exactly, including the priority rule.
- Delete is a try/catch around the raw `->delete()` (not a pre-count guard) —
  a registration with payments hits the `encaissements.inscription_fee_id`
  restrict FK via the `inscription_fees` cascade and surfaces as a 422
  ("This registration has payments and cannot be deleted."). **A real,
  previously-latent bug was found and fixed while testing this path (new,
  never exercised by the Livewire test suite either): the delete must run
  inside its own `DB::transaction()`.** Without it, PostgreSQL aborts the
  entire request-scoped transaction on the constraint violation, not just
  the statement — the PHP `catch` block still runs, but every later query in
  that same request/test then fails with "current transaction is aborted."
  Fixed in `InscriptionController::destroy()`; `InscriptionsIndex::delete()`
  has the identical latent gap and should get the same fix if it is ever
  revisited (currently untouched, per the phase's own scope).
- `registrations.manage-fees` is not enforced anywhere on the live create
  path (audit doc §12 point 1) — the group-fee-lookup endpoint is gated by
  `registrations.create` only, matching `InscriptionsIndex::updatedGroupId()`
  exactly (no separate group-view check either — see next point).
- `InscriptionFeeController` and its `inscription-fees.{store,update,destroy}`
  routes were confirmed genuinely dead (registered, working, zero callers
  anywhere in the app) — removed from `routes/backoffice.php` with explicit
  user sign-off (unlike prior phases' dead code, which had no routes at all,
  making this a more consequential removal). The controller and its Form
  Requests are left in place, now fully unreferenced.

### A design bug caught by the new test suite (fixed before commit)

The first draft of `InscriptionController::groupFees()` additionally called
`$this->authorize('view', $group)`, requiring `groups.view` on top of
`registrations.create`. `GroupPolicy` has no `view` override, so this fell
through to `ResourcePolicy::view()`'s default `groups.view` + center check —
a stricter gate than the Livewire source of truth, which loads a group's fees
in `updatedGroupId()` with no separate group-view authorization at all (the
`groups` options list is already center-scoped by
`GetInscriptionFormOptions::groups()`). A user who can create registrations
but lacks `groups.view` would have been unable to see any group's fees when
enrolling a student — caught by
`test_group_fees_endpoint_returns_only_active_catalog_fees_with_dates`
failing with 403. Removed the extra check.

### Files created

- Read-models: `App\Domain\Registrations\Queries\{GetInscriptionsList,
  GetInscriptionFormOptions,GetGroupInscriptionFees}`.
- React page: `resources/js/Pages/Backoffice/Inscriptions/Index.tsx`.
- Tests: `tests/Feature/Backoffice/Inscriptions/InscriptionsInertiaCrudTest.php`.

No new shared components — the fee-lines sub-table reuses the same
plain-`<table>`-inside-modal pattern established for Groups in Phase 8; the
mode toggle (new/existing student) and inline student form reuse Phase 8's
Students Contact/Parent tab fields and `PhoneField`/`splitPhone`/`joinPhone`
helpers directly.

### Files modified

- `App\Http\Controllers\Backoffice\InscriptionController` — gained
  `index`/`store`/`update`/`destroy`/`groupFees` alongside its existing
  `show`.
- `App\Http\Requests\Backoffice\Inscriptions\{Store,Update}InscriptionRequest`
  — fully rewritten to match the live Livewire field set exactly (previous
  version validated a different, dead field set — see audit doc §5).
- `routes/backoffice.php` — added `inscriptions.{index,store,update,destroy,
  show}` and `groups.inscription-fees`; removed the dead
  `InscriptionsIndex` Livewire route and the dead `inscription-fees.*`
  resource routes.
- `resources/js/Config/backofficeNavigation.ts` — Registrations nav item
  marked `inertia: true`.
- `resources/js/Types/index.ts` — additive only: `InscriptionRow`,
  `InscriptionFormOption`, `InscriptionGroupFee`,
  `InscriptionGroupFeesResponse`, `InscriptionFeeLine`,
  `InscriptionsFilters`, `InscriptionsPageProps`.

### Routes

| Route | Before | After |
|---|---|---|
| `backoffice.inscriptions.index` (GET) | `InscriptionsIndex` (Livewire) | `InscriptionController@index` |
| `backoffice.inscriptions.store` (POST, new) | — | `InscriptionController@store` |
| `backoffice.inscriptions.update` (PUT, new) | — | `InscriptionController@update` |
| `backoffice.inscriptions.destroy` (DELETE, new) | — | `InscriptionController@destroy` |
| `backoffice.groups.inscription-fees` (GET, new) | — | `InscriptionController@groupFees` |
| `backoffice.inscriptions.show` (GET) | unchanged | unchanged |

`inscription-fees.{index,store,update,destroy}` (dead, unrouted-in-practice
`InscriptionFeeController`) removed from the route file entirely — see the
"dead code" note above.

### Prop shapes

`InscriptionsPageProps` — paginated list + filters + all the Student
enum/option lists needed for inline new-student creation (niveaux, domaines,
examenTypes, sexes, parentRelations, countries) + `students`/`groups` select
options, no full Eloquent models. The per-group fee list is deliberately
**not** embedded in the initial payload (unlike Phase 8's Groups
`fraisLignes`, which is embedded) — group count × fee count made a
per-selection lookup the better-scaling choice here, a tradeoff surfaced to
and confirmed by the user before implementation.

### Business rules preserved

Group-derived dates and center/year on create; forced `Active` status on
create; active-only catalog fee filtering; full-catalog-always-billed fee
lines with per-fee due-date inheritance; percentage-priority discount math
(`InscriptionFee::computeMontant()`, unchanged, called server-side only);
edit-mode's 6-field-only / no-fee-line-re-derivation asymmetry;
QueryException-based delete guard (now transaction-safe); center scoping via
the standard `ResourcePolicy::withinCenter()` on the existing
`InscriptionPolicy`.

### Financial rules preserved

All money crosses the wire as server-formatted fixed 2-decimal strings
(`MoneyDisplay` type, `number_format(..., 2, '.', '')`), never raw floats.
The fee-lines table's live pct↔DH sync and running-total preview in React is
display-only (`computeLineMontant()` mirrors `InscriptionFee::computeMontant()`
exactly, including the same priority rule) — the server independently
recomputes every fee line's final `montant` from the raw
initial/pct/DH inputs on save via the real `InscriptionFee::computeMontant()`,
and that server-computed value is what persists. Verified directly by
`test_a_registration_bills_the_selected_group_fees_with_discount`,
`test_dh_discount_takes_effect_when_percentage_is_absent`, and
`test_percentage_discount_takes_priority_over_fixed_amount_when_both_present`.

### Tests added

`tests/Feature/Backoffice/Inscriptions/InscriptionsInertiaCrudTest.php` — 17
tests: index authorization + page shape; group-fee-lookup endpoint (active-
fee filtering, montant/date shape, permission gate); create with an existing
student (fee billing + both discount directions + priority rule + group-
derived dates ignoring tampered input + required-field validation +
permission gate); create with inline new-student mode (student creation +
scoping + contact/parent fields with combined phone + name-required-not-id
validation); update (6-field-only + date asymmetry + permission gate);
delete (success case + payments-blocked 422 case); center scoping (a
center-locked user cannot update another center's registration).

### Test results

| Check | Result |
|---|---|
| `artisan test tests/Feature/Backoffice/Inscriptions/` | ✅ 43/43 passing (26 existing Livewire-side + 17 new Inertia-side) |
| Full suite | ✅ See end-of-phase report for final count |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 492.86 kB / 133.58 kB gzip (Phase 8 baseline: 473.19 kB / 130.34 kB gzip) |

### Performance measurements

- Inscriptions list: 6 SQL queries (pagination count + eager loads), payload
  scales with page size, not with the student enum/option lists (those are
  fixed-size and sent once per page load, same as every other Inertia page).
- Group-fee-lookup endpoint: 1 SQL query (`Group::loadMissing('frais')` with
  the active-only constraint baked into the relation query), ~186 bytes for
  a 2-fee group — trivially small per-selection cost, confirming the
  dedicated-endpoint design decision over embedding every group's fees in
  the initial options payload.
- Bundle grew by ~19.7 kB / ~3.2 kB gzip over Phase 8 — the largest
  single-page addition so far (mode toggle, inline student form, fee-lines
  editor), still using only Phase 6/7/8's shared component library.

### Manual browser verification

Not yet performed — pending user verification in a real browser (create a
registration for an existing student with discounted fee lines in both
directions; create one with a brand-new student including contact/parent
tabs; edit a registration and confirm only the 6 fields are editable with no
fee-line UI at all; attempt to delete a registration that has a payment and
confirm the inline 422 message; switch centers and confirm registrations
list/create are properly scoped).

### Known limitations

None blocking. `registrations.manage-fees` remains unenforced on the create
path, matching the Livewire original exactly — introducing that check is new
authorization work, out of scope for a like-for-like migration.

---

## Phase 9 — Baseline (before Inscriptions migration)

**Date**: 2026-07-31

Full suite green before starting (previous Phase 8 end state: all Students/
Groups/Livewire/Inertia tests passing). Proceeding with Phase 9 implementation.

---

## Phase 8 — Students & Groups migration

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated Students (full CRUD with photo upload, parent/guardian fields inline,
CEFR level + German-track orientation logic) and Groups (full CRUD with
per-group fee-line assignment, teacher scoping, status tabs) from Livewire
to Inertia + React, following the Phase 7 Employees pattern exactly.

### Scope decision (stakeholder-confirmed before implementation)

The task requested migrating Groups' "Rooms, Capacity, Schedules" alongside
Professor assignment. Direct inspection of the live Livewire form
(`groups-index.blade.php`) confirmed **no room, capacity, or schedule field
exists anywhere in the current UI** — despite `Group::$fillable` and a dead,
unrouted `StoreGroupRequest` supporting `salle_id`/`capacite_max`. Decision:
migrate Groups exactly as it exists today (Name, Level, Teacher, Status,
dates, fee lines) — no new fields added. See
docs/phase-8-students-groups-inventory.md for the full audit.

### Existing behavior discovered

- Neither Students nor Groups had real HTTP routes for their mutations —
  every create/update/delete ran only over Livewire's own AJAX protocol.
  New routes were added for both (index/store/update[/destroy for Students]),
  repointing the pre-existing GET index route from the Livewire component to
  the new controller, keeping the same route name/URI.
- `StoreStudentRequest`/`UpdateStudentRequest`/`StoreGroupRequest`/
  `UpdateGroupRequest` existed but were entirely unrouted dead code
  (validating a narrower or, for Groups, wider field set than the live
  Livewire form actually uses) — rules() rewritten to match the live form
  exactly: Student requests gained `phone_pays`/`photo`; Group requests lost
  `salle_id`/`capacite_max`/`etablissement_id`/`annee_scolaire_id` (never
  form inputs — always inherited from `CurrentContext`, matching
  `GroupsIndex::save()`) and gained `fraisLignes.*` validation.
- Students' delete guard and Groups' fee-lines "always full catalog, 0 DH
  default" behavior were reproduced exactly; Groups' create-vs-edit `statut`
  option asymmetry (2 options on create, 3 on edit with a silent-revert
  guard on a raw "Fin de formation" attempt) is preserved — that transition
  only happens through `Group::archiverCommeTermine()` (Phase 5, unchanged).

### Files created

- Read-models: `App\Domain\Students\Queries\GetStudentsList`,
  `App\Domain\Groups\Queries\{GetGroupsList,GetGroupFormOptions}`.
- React pages: `resources/js/Pages/Backoffice/{Students,Groups}/Index.tsx`.
- Tests: `tests/Feature/Backoffice/Students/StudentsInertiaCrudTest.php`,
  `tests/Feature/Backoffice/Groups/GroupsInertiaCrudTest.php`.

No new shared components — Modal, ConfirmDialog, FormField, SelectField,
TextareaField, PhoneField, FormActions, TableToolbar, SearchInput,
RowActions, DataTable, Pagination, Card, EmptyState (all Phase 6/7) covered
every UI need, including the fee-lines sub-table (built with plain
`<table>` markup inside the modal, not a new abstraction — every row is
fixed to one catalog fee, never user-added/removed, so a generic "repeater"
component would have been the wrong shape).

### Files modified

- Controllers: `StudentController`, `GroupController` (both gain
  `index`/`store`/`update`[`/destroy` for Students] alongside their existing
  `show`[`/archive` for Groups]).
- Form Requests: `Store/UpdateStudentRequest`, `Store/UpdateGroupRequest`.
- `routes/backoffice.php` — `students.{store,update,destroy}` and
  `groups.{store,update}` added; `students.index`/`groups.index` repointed
  from the Livewire components to the controllers.
- `resources/js/Config/backofficeNavigation.ts` — Students/Groups nav items
  marked `inertia: true`.
- `resources/js/Types/index.ts` — additive only: `StudentRow`,
  `StudentsFilters`, `StudentsPageProps`, `GroupRow`, `GroupFraisLigne`,
  `GroupsFilters`, `GroupFormOption`, `GroupsPageProps`.

### Routes

| Route | Before | After |
|---|---|---|
| `backoffice.students.index` (GET) | `StudentsIndex` (Livewire) | `StudentController@index` |
| `backoffice.students.store` (POST, new) | — | `StudentController@store` |
| `backoffice.students.update` (PUT, new) | — | `StudentController@update` |
| `backoffice.students.destroy` (DELETE, new) | — | `StudentController@destroy` |
| `backoffice.groups.index` (GET) | `GroupsIndex` (Livewire) | `GroupController@index` |
| `backoffice.groups.store` (POST, new) | — | `GroupController@store` |
| `backoffice.groups.update` (PUT, new) | — | `GroupController@update` |

`backoffice.groups.destroy` still does not exist — groups are never deleted
(schema §6), unchanged.

### Prop shapes

`StudentsPageProps`/`GroupsPageProps` — paginated data + filters +
form-option lists, no full Eloquent models anywhere (every row shape is an
explicit array built in a Domain query class). Groups' list row carries its
own `fraisLignes` (keyed by `frais_id`) so the edit modal prefills without a
second request — a deliberate choice made after reviewing (and rejecting) a
first draft that matched fees by name via an extra GET request.

### Authorization / center scoping

Every mutation authorizes server-side independent of route middleware
(`authorize('create'/'update'/'delete', ...)` inside each action). Students
and Groups both use the standard `ResourcePolicy::withinCenter()` check
(unchanged) — no new center-scoping gap was introduced or found (unlike
Phase 6's Salle finding), since both controllers simply call the existing
policies. Confirmed by dedicated tests: a center-limited user cannot
update/delete a Student or update a Group in another center.

### Legacy files retained (not deleted)

`app/Livewire/Backoffice/{Students/StudentsIndex,Groups/GroupsIndex}.php`
and their Blade views — both unreferenced by any route now, kept for
rollback per the established pattern.

### Automated checks

| Check | Result |
|---|---|
| `artisan test tests/Feature/Backoffice/{Students,Groups}` | ✅ 73/73 passing (44 existing Livewire-side + 29 new Inertia-side) |
| Full suite | Pending final run — see end-of-phase report |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 473.19 kB / 130.34 kB gzip (Phase 7 baseline: 449.46 kB / 126.10 kB gzip) |

### Performance measurements

- Students list: 4 SQL queries, 10-row page payload ≈ 5.1 KB.
- Groups list: 5 SQL queries (includes the separate per-status tab-count
  query), 10-row page payload ≈ 4.7 KB (includes each group's own
  `fraisLignes` pivot data).
- No N+1 queries in either read-model (single eager loads / `withCount()`,
  matching the Livewire originals' own query shape exactly).
- Bundle grew by ~23.7 kB / ~4.2 kB gzip — small, since the shared
  Modal/Form/Table component library from Phase 6/7 covered every UI need;
  only the two page components and their Students-specific tab UI /
  Groups-specific fee-lines table are new code.

### Manual browser verification

Not yet performed — pending user verification in a real browser (create/
edit/delete a Student including photo upload and the Contact/Parent/Autres
informations tabs; create/edit a Group including the fee-lines table,
status tabs, and confirming a raw edit-mode "Fin de formation" selection
does not actually finish the group).

### Known limitations

None blocking. Room/capacity/schedule fields were deliberately not added
(see the scope decision above) — if GLS later wants a room-booking feature,
that is new feature work for a dedicated phase, not part of this migration.

---

## Phase 8 — Baseline (before Students/Groups migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 7 commit
(`8c9854f`) present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **411/411 passing, 1849 assertions** |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 449.46 kB / 126.10 kB gzip (matches Phase 7's final count exactly) |

No pre-existing failures. Proceeding with Phase 8 implementation.

---

## Phase 6 — Baseline (before simple CRUD migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 5 commits
(`c1e08f1`, `9cefcbd`, `547a33c`, `e78bc33`, `bea6278`, `74f0932`, `ab012ea`)
present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **356/356 passing, 1456 assertions** |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 375.45 kB / 110.41 kB gzip |

No pre-existing failures. Proceeding with Phase 6 implementation.

---

## Phase 6 — Simple CRUD modules migration

**Date**: 2026-07-30

**⚠ Concurrent-session note**: this phase was implemented while a second
Claude Code session (different Windows user account, "Outlaw") was
simultaneously working on this same repository — apparently a parallel
"Phase 7" pass migrating Employees/Roles/Users. Several of this phase's
file edits were silently reverted mid-session by that other session's own
git/filesystem operations (`EtablissementController`, `AnneeScolaireController`,
`SalleController`, both `Salle` Form Requests, `resources/js/Types/index.ts`,
and all three initial Settings panel `.tsx` files) and had to be re-applied.
All Phase 6 work was re-verified and re-committed after the collision; final
`route:list`, `tsc --noEmit`, and `npm run build` all pass clean, and the
final PHPUnit run (below) is green. **Two unrelated PHPUnit runs also hit a
transient PostgreSQL deadlock** (`SQLSTATE[40P01]`) on the `RefreshDatabase`
table-drop, consistent with both sessions running tests against the same
`gls_crm_test` database concurrently — resolved on retry both times, not a
real test defect. Flagging this as an operational hazard: **running two
Claude Code sessions against the same repo + same Postgres test database at
once risks losing uncommitted edits and causing spurious test-run
deadlocks.** Recommend not doing so, or at minimum committing
frequently and expecting to re-verify file state before any long
implementation stretch.

### Modules migrated

Établissements (Centers), Années scolaires (Academic Years), Salles (Rooms),
Frais (Fee catalog), Types de dépenses (Expense Types) — all 5 modules
listed in scope, per docs/phase-6-simple-crud-inventory.md.

### Modal/form/table architecture established

- `resources/js/Components/Modals/Modal.tsx` — React-controlled modal
  (role="dialog", aria-modal, focus trap/restore, Escape, backdrop-click,
  body-scroll lock). No `bootstrap.bundle.js`, no `data-bs-toggle`. See
  `docs/bootstrap-react-integration-decision.md`'s "Phase 6 modal decision".
- `resources/js/Components/Modals/ConfirmDialog.tsx` — delete confirmation
  built on `Modal.tsx`.
- `resources/js/Components/Forms/{SelectField,TextareaField,CheckboxField,
  PhoneField,FormActions,FormErrorsSummary}.tsx` — reusable field/action
  primitives. `PhoneField` + `resources/js/Data/countries.ts` port
  `App\Support\Phone\Countries`' split/join logic to TypeScript (built after
  a stakeholder decision to keep the guided country-dial picker rather than
  simplify to one free-text field).
- `resources/js/Components/Tables/{TableToolbar,SearchInput,RowActions}.tsx`
  — filter-bar row, debounced search, and a React-owned row-action dropdown
  (replacing `action-menu.blade.php`'s `data-bs-toggle="dropdown"`).

### Existing behavior discovered (docs/phase-6-simple-crud-inventory.md)

- `TypeDepenseController` was **entirely dead code** pre-Phase-6 (no route
  referenced any of its actions) — the real UI was the Livewire
  `TypesDepensesIndex` component embedded as the 3rd tab of the Depenses
  management page.
- `Frais` had **zero HTTP-layer surface** before this phase — no controller,
  no routes, no Form Requests, 100% Livewire.
- `Salle`'s `create()` authorization path had a pre-existing center-access
  gap: only `view`/`update`/`delete` checked `withinCenter()`; `create()`
  only checked the raw permission, so a center-limited `rooms.create` holder
  could submit a forged `etablissement_id` for a center they don't have
  access to (only `exists:etablissements,id` was validated). Present in the
  Livewire version too — not introduced by this migration.

### Decisions made (stakeholder-confirmed before implementation)

1. **Types de dépenses** gets its own new Inertia page/URL
   (`backoffice.types-depenses.index` stops redirecting) rather than staying
   a tab of the out-of-scope Depenses page. The Depenses Livewire page drops
   to 2 tabs (Depenses, Remboursements).
2. **Salle center-access gap**: fixed as part of this migration —
   `GetAccessibleCenterOptions` restricts the center picker to accessible
   centers, and `StoreSalleRequest`/`UpdateSalleRequest` re-validate the
   submitted `etablissement_id` against that same set server-side.
3. **Frais routes**: new `backoffice.frais.{index,create,store,edit,update,
   destroy}` resource routes created, mirroring the other three referential
   modules' naming.
4. **Phone field**: kept the guided country-dial + national-number two-part
   UX (native `<select>`, not Select2/jQuery) rather than simplifying to one
   free-text input, after the initial simplification proposal was rejected.

### Files created

- Domain query classes: `App\Domain\Centers\Queries\GetEtablissementsList`,
  `App\Domain\Settings\Queries\{GetAnneesScolairesList,GetSallesList,
  GetAccessibleCenterOptions,GetFraisList}`,
  `App\Domain\Expenses\Queries\GetTypesDepensesList`.
- Controller: `App\Http\Controllers\Backoffice\FraisController` (new).
- Form Requests: `App\Http\Requests\Backoffice\Frais\{Store,Update}FraisRequest`
  (new).
- React pages: `resources/js/Pages/Backoffice/Settings/{Index,
  EtablissementsPanel,AnneesScolairesPanel,SallesPanel,FraisPanel}.tsx`,
  `resources/js/Pages/Backoffice/TypesDepenses/Index.tsx`.
- Shared components/data listed under "Modal/form/table architecture" above,
  plus `resources/js/Data/countries.ts`.

### Files modified

- Controllers: `EtablissementController`, `AnneeScolaireController`,
  `SalleController`, `TypeDepenseController`, `DepenseManagementController`,
  `SettingController` (full Inertia rewrite).
- Form Requests: `StoreSalleRequest`, `UpdateSalleRequest` (center-access
  re-validation).
- `routes/backoffice.php` — Frais resource route added; `types-depenses.*`
  converted from a redirect closure to real resource routes.
- `resources/js/Config/backofficeNavigation.ts` — "Expense management" nav
  item narrowed to `expenses.view`/`refunds.view`; new "Expense types" item
  added, `inertia: true`; Settings item's permission list gained `fees.view`.
- `resources/js/Types/index.ts` — `SettingsTab`, `{Etablissement,
  AnneeScolaire,Salle,Frais,TypeDepense}{Row,Form}`, `SettingsPageProps`,
  `TypesDepensesPageProps`, plus the shared `SelectOption`/
  `LaravelValidationErrors`/`CrudPermissions` primitives.
- `resources/views/backoffice/depenses/index.blade.php` — dropped the
  `types` tab (now 2 tabs: Depenses, Remboursements).

### Routes changed or preserved

| Route name | Before | After |
|---|---|---|
| `backoffice.etablissements.*` | Livewire tab is the UI; controller mutations existed but redirected `index`/`create`/`show`/`edit` to Settings | Same shape — `store`/`update`/`destroy` now serve the React panel instead of the Livewire tab |
| `backoffice.annees-scolaires.*` | Same pattern | Same shape, same change |
| `backoffice.salles.*` | Same pattern | Same shape, same change, plus the center-access fix |
| `backoffice.frais.*` | **Did not exist** | New: `index/create/store/edit/update/destroy`, `index`/`create`/`edit` redirect to Settings |
| `backoffice.types-depenses.index` | Redirect closure → `depenses.index?tab=types` | Real controller action, own page |
| `backoffice.types-depenses.{store,update,destroy}` | **Did not exist** (dead controller, no routes) | New, real |
| `backoffice.depenses.index` | 3-tab Livewire page | 2-tab Livewire page (types tab removed) |
| `backoffice.settings` | `SettingController::__invoke` → Blade view with 4 `@livewire` tabs | Same route, now `Inertia::render('Backoffice/Settings/Index', ...)` |

### Prop shapes

`SettingsPageProps` (`activeTab`, `availableTabs`, `permissions` keyed per
tab, and only the active tab's dataset — `etablissements`/`anneesScolaires`/
`salles`+`centerOptions`/`frais`, never all four at once).
`TypesDepensesPageProps` (`types` paginated, `filters.search`,
`permissions`). No full Eloquent models anywhere — every row shape is an
explicit array built in a Domain query class.

### Create/update/delete behavior

Every module preserves its exact Livewire-era validation, uniqueness, and
delete-guard rules (see docs/phase-6-simple-crud-inventory.md for the
per-module detail). Delete refusals (record still in use) are returned via
`back()->withErrors(['delete' => ...])` — a 422-style field error, not a
flash message — so the React `ConfirmDialog` stays open and shows the
message inline, matching the Livewire tabs' `$this->addError('delete', ...)`
UX exactly (a decision made explicitly for this phase, since the default
redirect+flash pattern used by create/update would have made a failed
delete look like it succeeded until the flash was noticed).

### System/protected-row behavior

`TypeDepense.is_system` rows: `TypeDepenseController::update()`/`destroy()`
call an unconditional `abort_if($types_depense->is_system, 403, ...)`
**before** `$this->authorize(...)` — stopping even a super-admin (who
bypasses every policy via `Gate::before`), exactly mirroring the retired
Livewire component's `guardSystemType()`. `is_system` is never accepted from
the create form (`StoreTypeDepenseRequest` doesn't have the field; the
controller hardcodes `is_system: false`).

### Permission behavior

No new permission names — reused the existing seeded `centers.*`,
`academic-years.*`, `rooms.*`, `fees.*`, `expense-types.*` from
`PermissionRegistry`. Every mutation endpoint authorizes server-side
(`authorizeResource` on 4 of 5 controllers; `TypeDepenseController` combines
`authorizeResource` with the extra unconditional system-row guard). React
button visibility (`permissions.{create,update,delete}` props) is UI
convenience only.

### Center/year scoping

`Salle` list is narrowed to the active top-bar context center (+ global
NULL-center rows), same as before (`GetSallesList` reproduces
`WithCenterContext::scopeToActiveCenter()`). `Salle` create/update center
picker is now also restricted to centers the acting user can access
(`GetAccessibleCenterOptions`) — the Phase 6 §Q3 fix. Établissements,
Années scolaires, Frais, Types de dépenses remain global reference
data with no center scoping (unchanged).

### Legacy files retained

See `docs/legacy-frontend-removal-plan.md` §0c — 4 Livewire tab components +
their views, plus the `TypesDepensesIndex` component + its view, all kept
unused for rollback.

### Automated checks

| Check | Result |
|---|---|
| `artisan test tests/Feature/Backoffice/Settings/SettingsTest.php` | ✅ 24/24 passing (one transient deadlock retry, see concurrent-session note above) |
| `artisan test tests/Feature/Backoffice/Finance/TypesDepensesCrudTest.php` | ✅ 17/17 passing (one transient deadlock retry) |
| `artisan test` (full suite) | Pending final run — see end-of-phase report |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 449.43 kB / 126.08 kB gzip (up from 375.45 kB / 110.41 kB gzip at Phase 5 baseline) |

### Manual browser verification

Pending — per the established pattern (docs/inertia-react-migration-status.md
Phase 2 entry), the stakeholder verifies manually in their own browser
rather than relying on automated headless-browser testing.

### Known limitations

- `resources/views/backoffice/settings/index.blade.php` is now unused
  Blade scaffolding, kept for rollback per the removal plan.
- The Salle center-access fix only covers the new Inertia code path — the
  retired Livewire `SallesTab` (kept for rollback) still has the original
  gap, since it is being retired, not maintained.
- The concurrent-session collision (see note above) means this phase's git
  history includes some churn (files reverted and re-applied) that would
  not exist in a single-session run — the final committed state is correct
  and fully verified regardless.

---

## Phase 7 — Medium CRUD modules (Employees, Users, Roles, Authorization)

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated the four remaining medium-complexity admin modules from Livewire to
Inertia + React: Employees (full CRUD with photo upload and auto-provisioned
login), Users (edit-only + password regeneration), Roles (full CRUD, no
modal — full-page create/edit, matching the pre-existing UX), and the
per-user role/direct-permission assignment screen (`ManageAuthorization` →
`UserAuthorizationController`). Built via 4 parallel agents (3 backend, 3
frontend, with the frontend Select2 agent concluding no new component was
needed) plus a dedicated adversarial security review before commit.

**⚠ Concurrent-session note**: a second Claude Code process was running
against this same repository and Postgres test database at the same time as
this phase's work (confirmed via two live `claude.exe`/`node.exe` process
pairs on the machine) — it was completing its own Phase 6 (Settings/simple
CRUD modules, see the Phase 6 entry above) concurrently. It silently
reverted several of this phase's in-progress files mid-session
(`EtablissementController`, `AnneeScolaireController`, `SalleController`,
both `Salle` Form Requests, `resources/js/Types/index.ts`, and the Settings
panel `.tsx` files it was building) while this phase's agents were reading
the working tree — those files were not this phase's to touch and were
deliberately left alone once identified; the reverts/re-creations were
Phase 6's own process, not a defect introduced here. Several PHPUnit runs
during this phase also hit transient PostgreSQL deadlocks
(`SQLSTATE[40P01]`) and "relation does not exist" errors from both
processes running `RefreshDatabase` against the same `gls_crm_test`
database concurrently — every such failure was reproduced as a clean pass
when the same test/file was re-run in isolation, confirming environmental
contention, not a defect in this phase's code. **Recommend not running two
Claude Code sessions against the same repository + test database at once.**

### Existing behavior discovered
- **Users/Roles/Permissions had no dedicated policy classes** — unlike every
  other module (`EmployeePolicy` extends `ResourcePolicy`), authorization for
  Users/Roles is purely permission-string based (`$this->authorize('roles.delete')`
  etc.), by design (`docs/roles-and-permissions.md`) — preserved exactly,
  no `UserPolicy`/`RolePolicy` was invented.
- **None of the four modules had real HTTP routes for their mutations** —
  every create/update/delete/regeneratePassword/authorization-sync action
  ran only over Livewire's own AJAX protocol. New routes were added for all
  of them (see Routes table below); the pre-existing GET routes for
  index/create/edit were repointed from the Livewire components to the new
  controllers, keeping the same route names/URIs.
- **Employees' delete guard, Roles' machine-name immutability, and Roles'
  protected/has-users delete guard** were all Livewire soft-error patterns
  (`addError('delete', ...)`) — reproduced as `ValidationException::withMessages(['delete' => ...])`,
  a real 422 with `errors.delete`, not a 500.
- **`UserAuthorizationController::edit()`'s `roles` prop initially shipped
  no per-role permission list** (only `{name, label, permissionsCount}`),
  which the Livewire original computed live via a real
  `Role::whereIn($selected)->with('permissions')` query on every render.
  Fixed during this phase (see "Bugs found and fixed" below) rather than
  left as a documented gap, since the frontend's live "granted via role X"
  summary depended on it.

### Deliberate behavior tightening (not a bug, a documented improvement)
`EmployeeController::update()`/`destroy()` now enforce center-scoping via
`EmployeePolicy` (`Gate::authorize('update'|'delete', $employee)` →
`ResourcePolicy::withinCenter()` via `CenterAccessService`) — the Livewire
`EmployeesIndex` never enforced this per-record, only the flat
`employees.update`/`employees.delete` permission string, meaning a
non-`centers.access-all` admin could previously edit/delete an employee
from another center by guessing its ID. The new routes close this gap.
Covered by `EmployeesInertiaCrudTest::test_update_and_delete_are_center_scoped_for_non_global_users`.

### Bugs found and fixed during adversarial review (before commit)
1. **One-time secrets could resurface on a later, unrelated page visit.**
   `app/Http/Middleware/HandleInertiaRequests.php`'s `newEmployeeCredentials`/
   `regeneratedPassword` shared props read their session values with
   `session()->get()`, which does not consume Laravel's flash data — flash
   data survives the *entire next request*, not just "the next render," so
   any subsequent Inertia visit in that window (a search/filter/pagination
   reload, a plain back/refresh) would still see the plaintext secret and
   reopen the credentials/password modal the admin had already dismissed.
   **Fix**: both reads changed to `session()->pull(...)`, which reads and
   forgets atomically — guaranteed to render at most once, matching the
   Livewire original's component-instance-scoped (never session-rebroadcast)
   equivalent. Two regression tests added:
   `EmployeesInertiaCrudTest::test_one_time_credentials_are_shown_at_most_once`
   and `UsersInertiaTest::test_regenerated_password_is_shown_at_most_once`
   — both assert the secret is present on the render immediately following
   the mutating request, then absent on a second, later request in the same
   session.
2. **Missing per-role permission enumeration** (see "Existing behavior
   discovered" above) — `UserAuthorizationController::edit()`'s `roles` prop
   extended with a `permissionNames: string[]` field per role
   (`Role::query()->with('permissions')->withCount('permissions')...`), a
   single eager-loaded query, no N+1. The frontend's `viaRoles` computation
   in `Authorization.tsx` now derives real "granted via role" provenance
   live, before saving, instead of an intentionally-empty placeholder.
   Verified this doesn't regress super-admin's display: super-admin is
   deliberately never synced any permissions (bypasses everything via
   `Gate::before`), and the UI already special-cases `isSuperAdmin` with a
   distinct "bypasses all checks" banner rather than falling through to a
   misleading "0 permissions via role" state.

No other authorization bypass, mass-assignment, CSRF, or UI-only-auth defect
was found by the adversarial review (full findings: server-side re-checks
exist on every mutation independent of route middleware; protected-role and
has-users delete guards are real DB checks, not client-side disablement;
role `name` is genuinely immutable on update — `UpdateRoleRequest` never
declares it as a validated field at all).

### Files created
- Controllers: `App\Http\Controllers\Backoffice\Employees\EmployeeController`,
  `App\Http\Controllers\Backoffice\Users\{UserController,UserAuthorizationController}`,
  `App\Http\Controllers\Backoffice\Roles\RoleController`
- Form Requests: `app/Http/Requests/Backoffice/{Employees/{Store,Update}EmployeeRequest
  (rule set expanded to match the full Livewire form — sexe, whatsapp, photo,
  both dates, salaire, note, adresse — the previous, narrower rule set only
  served the legacy unrouted `EmployeeController`), Users/{UpdateUserRequest,
  SyncUserAuthorizationRequest}, Roles/{Store,Update}RoleRequest}`
- Read-models: `app/Domain/Employees/Queries/{GetEmployeesList,GetUsersList}.php`
  (Users placed under the Employees domain — no dedicated Users module
  exists, and Users are exclusively produced by `EmployeeObserver`),
  `app/Domain/Settings/Queries/GetRolesList.php` (placed under Settings —
  no dedicated Roles/Authorization domain module exists; same reasoning as
  the other referential-data read-models already there; docblock flags
  revisiting a dedicated `Domain\Authorization` module if role management
  grows further)
- React pages: `resources/js/Pages/Backoffice/{Employees/Index,
  Users/{Index,Authorization},Roles/{Index,Create,Edit}}.tsx`
- Shared React component: `resources/js/Components/Roles/RolePermissionsForm.tsx`
  (label/machine-name/permissions form shared by Roles Create and Edit)
- The project's first hand-rolled modal: `resources/js/Components/Modals/Modal.tsx`
  (+ `ConfirmDialog.tsx`) — resolves `docs/bootstrap-react-integration-decision.md`'s
  explicitly-deferred "Phase 6+ revisit": hand-rolled chosen over
  `react-bootstrap` (no new npm dependency), focus-trap/Escape/backdrop-click/
  body-scroll-lock all React-owned, visuals reuse the existing Bootstrap 5
  `.modal`/`.modal-dialog`/`.modal-backdrop` markup
- Tests: `tests/Feature/Backoffice/People/EmployeesInertiaCrudTest.php`,
  `tests/Feature/Backoffice/Inertia/{RolesInertiaTest,UsersInertiaTest}.php`

### Files modified
- `routes/backoffice.php` — `employees.index`, `users.index`,
  `users.authorization.edit`, `roles.index`, `roles.create`, `roles.edit`
  repointed from Livewire components to the new controllers (same
  names/URIs); new routes added for actions that never had one before (see
  table below)
- `app/Http/Middleware/HandleInertiaRequests.php` — added
  `flash.newEmployeeCredentials`/`flash.regeneratedPassword` shared props
  (later fixed to use `session()->pull()`, see "Bugs found and fixed")
- `resources/js/Types/index.ts` — additive only; new prop-shape types per
  module, no existing type redefined
- `resources/js/Config/backofficeNavigation.ts` — Employees/Users/Roles nav
  entries marked `inertia: true` so the sidebar SPA-navigates instead of
  falling back to a full reload, matching the pattern already used for
  Permissions

### Routes
| Route | Before | After |
|---|---|---|
| `backoffice.employees.index` (GET) | `EmployeesIndex` (Livewire) | `EmployeeController@index` |
| `backoffice.employees.store` (POST, new) | — | `EmployeeController@store` |
| `backoffice.employees.update` (PUT, new) | — | `EmployeeController@update` |
| `backoffice.employees.destroy` (DELETE, new) | — | `EmployeeController@destroy` |
| `backoffice.users.index` (GET) | `UsersIndex` (Livewire) | `UserController@index` |
| `backoffice.users.update` (PUT, new) | — | `UserController@update` |
| `backoffice.users.regenerate-password` (POST, new) | — | `UserController@regeneratePassword` |
| `backoffice.users.authorization.edit` (GET) | `ManageAuthorization` (Livewire) | `UserAuthorizationController@edit` |
| `backoffice.users.authorization.update` (PUT, new) | — | `UserAuthorizationController@update` |
| `backoffice.roles.index` (GET) | `RolesIndex` (Livewire) | `RoleController@index` |
| `backoffice.roles.create` (GET) | `RoleForm` (Livewire) | `RoleController@create` |
| `backoffice.roles.store` (POST, new) | — | `RoleController@store` |
| `backoffice.roles.edit` (GET) | `RoleForm` (Livewire) | `RoleController@edit` |
| `backoffice.roles.update` (PUT, new) | — | `RoleController@update` |
| `backoffice.roles.destroy` (DELETE, new) | — | `RoleController@destroy` |

No routes for creating a User directly — users are exclusively produced by
`EmployeeObserver` when an Employee is created, exactly as before.

### Authorization / center scoping
Every mutation re-checks permissions inside the controller action itself,
independent of route middleware (defense in depth, matching the Livewire
"authorize in mount() AND every mutation" pattern). `UserAuthorizationController::update()`
delegates every invariant (role/permission existence, super-admin
grant/remove rule, last-super-admin lockout, direct-permission privilege
check, transaction, cache reset, activity log) to the unchanged
`UserAuthorizationService::syncAuthorization()` — no business rule was
reimplemented. Confirmed by the adversarial review: no route lets a forged
request assign `super-admin` without already holding it, delete a protected
or in-use role, or bypass Employees' new center-scoping.

### Legacy files retained (not deleted)
`app/Livewire/Backoffice/{Employees/EmployeesIndex,Users/{UsersIndex,
ManageAuthorization},Roles/{RolesIndex,RoleForm}}.php` and their Blade views
— all unreferenced by any route now, kept for rollback per the established
pattern.

### Automated checks
| Check | Result |
|---|---|
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — main bundle 449.46 kB / 126.10 kB gzip (Phase 6 baseline: 375.45 kB / 110.41 kB gzip) |
| `C:\php84\php.exe artisan test tests/Feature/Backoffice/{People,Inertia,Authorization}` | ✅ Green (isolated from the concurrent-session contention noted above) |
| Full suite | ✅ Green — isolated re-runs of every test that hit a transient deadlock/"relation does not exist" error during a concurrent run confirmed a clean pass |

### Performance measurements
Bundle grew by ~74 kB / ~16 kB gzip over Phase 6's baseline — expected for
4 new full CRUD pages (list + modal or full-page form, one new shared modal
component, one new shared Roles form component) added in a single phase.
No N+1 queries introduced in any new read-model (`GetEmployeesList`,
`GetUsersList`, `GetRolesList` all use single eager loads /
`withCount()`, confirmed by the adversarial review).

### Manual browser verification
Not yet performed — pending user verification in a real browser (create/edit/
delete an Employee including the one-time-credentials modal, edit a User and
regenerate a password, create/edit/delete a Role, assign roles/direct
permissions to a User).

### Known limitations
- None blocking. The concurrent-session file churn (noted above) is fully
  resolved in the final committed state — every Phase 7 file was
  re-verified (lint, `tsc`, targeted tests) after the collision was
  identified.

---

## Phase 5 — Baseline (before read-only pages migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 4 commits
(`dfdd917`, `745302f`, `4f9beb7`, `fdf1a11`, `43e2a9f`) present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **339/339 passing, 1332 assertions** (matches Phase 4's final count exactly) |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-cDb3tAX-.js` 347.19 kB / 105.99 kB gzip (identical to Phase 4) |

No pre-existing failures. Proceeding with Phase 5 implementation.

---

## Phase 5 — Read-only pages migration

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated 8 read-only Backoffice pages to Inertia + React: groups-historique
index, and show pages for students, groups, inscriptions, caisses,
encaissements, dépenses, and caisse-transfers. Full audit in
`docs/phase-5-read-pages-inventory.md`; per-controller behavior discovered
during that audit informed every decision below.

### Existing behavior discovered
- `EmployeeController::show()` and `RemboursementController::show()` are
  **dead code** — no route registers either, and the Remboursement view
  directory doesn't even exist. Both excluded, per "don't invent missing
  pages."
- `CaisseController`/`EncaissementController`/`DepenseController`/
  `CaisseTransferController` are all **full resource controllers** with
  live `create`/`store`/`edit`/`update`/(`destroy`) methods — but **only
  their `show()` route is actually registered** in
  `routes/backoffice.php`. Those other methods are untouched, unreachable
  dead code (the real CRUD is the Livewire tabbed pages, per CLAUDE.md
  §11) — confirmed via `artisan route:list`, not assumed.
- `caisses/show.blade.php` ran its own inline `@php` query block (4
  relation queries, `limit(10)` each) rather than the controller — that
  logic moved into `GetCaisseDetails`, eliminating the Blade-embedded
  query while preserving the exact same queries/limits/ordering.
- `Group::show`'s "End training" (Fin de formation) archive action is a
  real, reachable, state-changing form — preserved as a **plain HTML
  `<form>`** in the React page (CSRF token read from the existing meta
  tag), posting to the unconverted `backoffice.groups.archive` route. Not
  migrated to a React mutation, per Phase 5's explicit scope.
- Several eager-loaded relations were unused by their Blade view
  (`Group::historique`, `Inscription::createdBy`, `Student::remboursements`)
  — dropped from the new read-models since "preserve only what's currently
  displayed" is the rule, not "preserve every eager load regardless of
  use."

### Files created
- 8 Domain read-model classes: `GetGroupsHistorique`, `GetStudentDetails`,
  `GetGroupDetails`, `GetInscriptionDetails`, `GetCaisseDetails`,
  `GetEncaissementDetails`, `GetDepenseDetails`, `GetCaisseTransferDetails`
  (first implemented classes in `Domain/{Groups,Students,Registrations,
  Finance,Payments,Expenses}/Queries/`)
- 8 Inertia pages (`GroupsHistorique/Index`, `Students/Show`,
  `Groups/Show`, `Inscriptions/Show`, `Caisses/Show`,
  `Encaissements/Show`, `Depenses/Show`, `CaisseTransfers/Show`)
- Reusable components: `Tables/Pagination.tsx`, `Details/{DetailRow,
  StatusBadge,RelatedRecordsTable}.tsx`, `Media/DocumentLink.tsx`
- `tests/Feature/Backoffice/Inertia/ReadOnlyPagesInertiaTest.php` (17 tests)
- `docs/phase-5-read-pages-inventory.md`,
  `docs/dashboard-authorization-audit.md`

### Files modified
- 7 controllers converted to `Inertia::render()` for `show()`/`index()`
  only — every other action on the resource controllers (`create`/
  `store`/`edit`/`update`/`destroy`/`archive`/`validate`) is byte-for-byte
  unchanged
- `resources/js/Types/index.ts` — `PaginatedData<T>`, `PaginationLink`,
  `MoneyDisplay`, `SafeMediaFile`, and one details-page type per migrated
  page
- 4 existing test files updated only where their `assertSee()` string-match
  broke against the Inertia JSON payload (`GroupsHistoriqueTest`,
  `StudentsCrudTest`, `InscriptionsCrudTest`, `CaissesCrudTest`,
  `EncaissementsCrudTest`, `CaisseTransfersTest`) — every other assertion
  (authorization, center scoping, credentials) untouched

### Routes
All 8 routes kept their exact name/URI — only the controller action's
return type changed (Blade view → `Inertia::render()`). No new routes
added. `backofficeNavigation.ts` **not modified** — none of these 8 pages
are sidebar entries (they're reached via in-page links from still-Livewire
index pages), so no nav config changes were needed.

### Prop shapes
Every controller passes an explicit array from its read-model — never a
raw Eloquent model. Money values are pre-formatted 2-decimal strings
(`number_format($value, 2, '.', '')`), never raw floats. Media exposed
only as `{name, url, mimeType, size}` — confirmed via test
(`test_depense_show_media_props_are_safely_shaped`) that no Spatie Media
internals leak.

### Authorization / center scoping
Unchanged in every case — `$this->authorize('view', $model)` or the
implicit `authorizeResource()` → policy mapping, both already
center-scoped via `ResourcePolicy::withinCenter()`. Verified by new
cross-center-denial tests for every migrated model (Student, Group,
Inscription, Caisse, Encaissement, Depense, CaisseTransfer).

### Pagination/filter strategy
`groups-historique` uses the new `Pagination.tsx` component
(`router.get(url, {}, {preserveState: true, replace: true})`) — server-side
only, matches Laravel's paginator JSON shape exactly. No filters exist on
this index today (none were added — ground-truth rule). Detail pages'
related-record lists remain unpaginated, matching the original Blade
behavior exactly (including Caisse's hardcoded `limit(10)` per movement type).

### Financial display
Every total (inscription due/paid/remaining, encaissement fee summary) is
computed server-side in the read-model, identical to the original Blade
`@php` block's logic — verified by
`test_inscription_show_does_not_recompute_totals_incorrectly`. React only
formats (`Number(value).toFixed(2)`), never recalculates.

### Media/receipt strategy
`Depense`'s receipt gallery uses the real, already-authorized Spatie Media
URL (`$media->getUrl()`) — same `/media/<uuid8>/…` convention as before,
never a filesystem path, never the full Media model.

### Legacy files retained
All 8 original Blade views (`groups-historique/index`, `students/show`,
`groups/show`, `inscriptions/show`, `caisses/show`, `encaissements/show`,
`depenses/show`, `caisse-transfers/show`) — unused by any route now, kept
for rollback per the established pattern.

### Dashboard authorization audit result
See `docs/dashboard-authorization-audit.md` — the missing
`dashboard.view` route gate is **intentional-by-test**
(`AuthTest::test_authenticated_users_can_view_dashboard` explicitly uses a
permission-less user and asserts success). Left unchanged; none of the
5 required conditions for changing it were met.

### Automated checks
| Check | Result |
|---|---|
| Targeted (Students/Groups/Inscriptions/Finance/Inertia) | ✅ 87 + 106 + 17 = 210 relevant tests, all passing |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CvEEUDbo.js` 375.45 kB / 110.41 kB gzip (Phase 4: 347.19 kB / 105.99 kB gzip) |
| `C:\php84\php.exe artisan test` (full suite) | ✅ **356/356 passing, 1456 assertions** (Phase 4 baseline: 339/339, 1332 assertions) |
| ESLint | Not run — no ESLint config exists yet |

### Performance measurements
| Page | Full page response | Inertia payload | Query count (read-model) |
|---|---|---|---|
| Groups historique (empty, local dev) | 3,255 bytes | 1,541 bytes | — |
| Student show (real record, id 71) | 3,737 bytes | 2,023 bytes | 8 |
| Caisse show (real record, id 177) | 3,043 bytes | 1,329 bytes | 9 |

No N+1 pattern in any read-model — every relation list uses a single eager
load, not per-row queries.

### Manual browser verification (user, real Chrome)
Confirmed working: groups-historique, student detail (identity/
inscriptions/payments), group detail (fees/students, "End training" form
still a normal POST), caisse/encaissement/depense detail pages. No
console errors reported.

### Known limitations
- None blocking. Employee and Remboursement detail pages remain
  unreachable exactly as before — no route was invented for either.

---

## Phase 4 — Baseline (before dashboard/context migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 3 commits
(`ae33482`, `a9029c9`, `befc042`, `73b75bf`, `1de096f`) present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **320/320 passing, 1232 assertions** (matches Phase 3's final count exactly) |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CNxd8NcD.js` 340.92 kB / 104.85 kB gzip (identical to Phase 3) |

No pre-existing failures. Proceeding with Phase 4 implementation.

**Theme reference copy** added in commit `dfdd917` (separate from the
Dashboard/Context work) — see `docs/preskool-react-reference-inventory.md`.
Verified byte-identical build output before/after the copy — confirms Vite
never scans `resources/theme-reference/`.

---

## Phase 4 — Dashboard + Context migration

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated the Backoffice dashboard and the top-bar academic-year/center
context switcher from Livewire to Inertia + React. Query semantics,
center/year scoping, and authorization are unchanged — see
`docs/dashboard-livewire-to-inertia-map.md` for the full per-stat mapping.

### Dashboard behavior discovered
- `DashboardController` had **no permission middleware** — only `auth` —
  even though a `dashboard.view` permission exists in the registry and is
  used by Context test fixtures. Preserved exactly as-is (ground-truth
  rule: not a verified bug, not something this migration should "fix").
- 7 stats + 2 labels, computed by `DashboardStats::render()`:
  `studentsTotal`/`employeesTotal`/`employeesActive` (center-scoped only),
  `groupsTotal`/`groupsEnFormation`/`inscriptionsTotal`/`inscriptionsActives`
  (center- AND year-scoped), `paymentsMonth` (center-scoped via the
  till/`caisse`, calendar-month range, not academic-year), `anneeLabel`/
  `centreLabel`. `inscriptionsTotal` is computed but was never actually
  rendered as its own card in the original Blade view — preserved anyway
  for parity (dead-but-harmless data, not a scope decision to make).
- No stat is individually permission-gated — visibility is governed purely
  by center access, identical for every authenticated user.

### Context behavior discovered
- `CurrentContext::setAnneeScolaire()`/`setEtablissement()` already own
  100% of the real authorization/validation (invalid ids and
  inaccessible-center selections are silently ignored) — confirmed via the
  pre-existing `CurrentContextTest`. The new HTTP layer
  (`ContextController`/`UpdateContextRequest`) does format validation only
  and delegates every authorization decision to the same, single
  `CurrentContext` service already shared with every Livewire page.
- "All centers" is representable as `etablissement_id: null`, a real
  allowed value distinct from "field absent" — the Form Request and
  controller both treat it as such (`$request->has(...)`, not
  `$request->filled(...)`, for the center field).

### Files created
- `app/Domain/Reports/DTOs/DashboardStatsData.php`,
  `app/Domain/Reports/Actions/GetDashboardStats.php` — first implemented
  class in the previously-reserved `Reports` domain
- `app/Http/Controllers/Backoffice/ContextController.php`,
  `app/Http/Requests/Backoffice/Context/UpdateContextRequest.php`
- `resources/js/Pages/Backoffice/Dashboard/Index.tsx`,
  `resources/js/Components/Dashboard/{StatCard,StatsGrid}.tsx`,
  `resources/js/Components/Context/ContextSwitcher.tsx`
- `tests/Feature/Backoffice/Inertia/{DashboardInertiaTest,ContextUpdateTest}.php`
- `docs/dashboard-livewire-to-inertia-map.md`

### Files modified
- `app/Http/Controllers/Backoffice/DashboardController.php` — Blade view →
  `Inertia::render()`, delegates to `GetDashboardStats`
- `app/Http/Middleware/HandleInertiaRequests.php` — `context` shared prop
  extended with `currentCenter`/`currentAcademicYear`/`availableCenters`/
  `availableAcademicYears` (lazy-resolved via a closure — real DB queries,
  never run for guests or when a partial reload doesn't request them)
- `resources/js/Components/Theme/Header.tsx` — static context placeholder
  replaced with the real `<ContextSwitcher>`
- `resources/js/Layouts/BackofficeLayout.tsx` — added an optional `actions`
  prop (page-header actions slot), needed by the Dashboard's "Ajouter un
  étudiant" button, not previously exposed
- `resources/js/Types/index.ts` — `ContextOption`, extended `Context`,
  `ContextUpdateForm`, `DashboardStats`, `DashboardPageProps`
- `routes/backoffice.php` — new `backoffice.context.update` (POST); the
  `backoffice.dashboard` route's name/URI/middleware are unchanged, only
  its controller's return type changed

### Routes
| Route | Change |
|---|---|
| `backoffice.dashboard` (GET) | Same name/URI/middleware — controller now returns Inertia |
| `backoffice.context.update` (POST, new) | `auth` middleware only, matching the Livewire switcher's own gate (real authorization is inside `CurrentContext`) |

No duplicate routes or method conflicts — verified via
`artisan route:list --path=backoffice`.

### Shared context prop shape (final)
```json
{
  "anneeScolaireId": 75, "etablissementId": null,
  "isAllCenters": true, "canSwitchCenter": true,
  "currentCenter": null,
  "currentAcademicYear": { "id": 75, "name": "2025/2026" },
  "availableCenters": [{ "id": 151, "name": "GLS Marrakech" }, ...],
  "availableAcademicYears": [{ "id": 75, "name": "2025/2026" }, { "id": 74, "name": "2024/2025" }]
}
```
No full `Etablissement`/`AnneeScolaire` models, no timestamps, no
unrelated fields — `id`+`name` only, matching the task's required shape
exactly.

### Mixed Inertia/Livewire context strategy — verified
The Livewire `ContextSwitcher` component is no longer rendered anywhere
(removed from `app.blade.php`'s scope entirely, since it was never loaded
there to begin with — it was only ever in the Blade header, which Inertia
pages don't use). A new `ContextUpdateTest::
test_context_change_through_the_new_endpoint_is_observed_by_a_legacy_livewire_page`
proves: change context via the new POST endpoint → mount `StudentsIndex`
(Livewire) fresh → it reflects the new center immediately, because both
read the exact same `CurrentContext`/session — there is only one context
implementation, never two.

### Legacy files retained (not deleted)
- `app/Livewire/Backoffice/Dashboard/DashboardStats.php` + its Blade view
  — unused by any route now, kept for rollback
- `app/Livewire/Backoffice/Context/ContextSwitcher.php` + its Blade view —
  still available for any Blade page that might reach for it, though none
  currently do outside the old dashboard/header
- `resources/views/backoffice/dashboard/index.blade.php` — unused, kept

### Automated checks
| Check | Result |
|---|---|
| Targeted (`Context/`, `Inertia/`) | ✅ 57/57 passing |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-cDb3tAX-.js` 347.19 kB / 105.99 kB gzip (Phase 3: 340.92 kB / 104.85 kB gzip) |
| `C:\php84\php.exe artisan test` (full suite) | ✅ **339/339 passing, 1332 assertions** (Phase 3 baseline: 320/320, 1232 assertions) |
| ESLint | Not run — no ESLint config exists yet (skipped per instructions) |

### Performance measurements
| Measurement | Value |
|---|---|
| Dashboard SQL query count (full request, `GetDashboardStats` + auth/shared props) | **16** |
| Full initial HTML page response size | **2,850 bytes** |
| Inertia JSON payload (subsequent visit/partial reload) | **1,136 bytes** |
| React bundle (gzip) | 105.99 kB (+1.14 kB vs. Phase 3) |

No N+1 pattern found — `(clone $query)->count()` reuses each scoped query
builder rather than re-querying from scratch per stat.

### Manual browser verification (user, real Chrome, `artisan serve` + `npm run dev`)
Confirmed working: dashboard welcome banner + 4 real stat cards, context
switcher (year + center dropdowns) updates stats live, and — critically —
after changing context via the new switcher, navigating to a legacy
Livewire page (Students) via a plain anchor shows the same updated
center's data. No issues reported.

### Known limitations
- None blocking. The `dashboard.view` permission's non-enforcement is
  pre-existing behavior, not a Phase 4 regression — flagged above, not
  fixed (ground-truth rule).

---

## Phase 3 — Baseline (before auth/profile migration)

**Date**: 2026-07-30
**Branch**: `migration/inertia-react-preskool`, clean tree, Phase 2 commits
(`ca356ef`, `a38da4b`, `2d2ebab`, `69df093`) present and unchanged.

| Check | Result |
|---|---|
| `C:\php84\php.exe artisan test` | ✅ **305/305 passing, 1123 assertions** (matches Phase 2's final count exactly) |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CrpMxtFV.js` 325.16 kB / 101.55 kB gzip (identical to Phase 2, no changes yet) |

No pre-existing failures. Proceeding with Phase 3 implementation.

---

## Phase 3 — Auth + Profile migration

**Date**: 2026-07-30
**Status**: **Complete.**

Migrated Backoffice login, forgot-password, reset-password, and the
signed-in profile page from Blade/Livewire to Inertia + React. Every
authentication rule (email-or-username, rate limiting, `is_active` gate,
session regeneration, CSRF, password broker) is unchanged — only the
GET-page rendering layer moved.

### Routes now served by Inertia
- `backoffice.login` (GET), `backoffice.password.request` (GET),
  `backoffice.password.reset` (GET) — unchanged names/URIs/methods, now
  `Inertia::render()` instead of Blade views
- `backoffice.profile` (GET) — new `ProfileController@show`, replacing the
  Livewire `ProfilePage` route component (route name/URI unchanged)
- **New routes**: `backoffice.profile.update` (POST),
  `backoffice.profile.password.update` (POST) — split from the Livewire
  component's two actions (`updateProfile`/`updatePassword`)

### Routes still served by Blade/Livewire (as of Phase 3 — superseded)
Every POST auth action (`login.store`, `password.email`, `password.update`,
`logout`) was already a plain controller action, not Blade — no change
needed there. As of *this phase* (Phase 3), all Dashboard/Settings/Students/
Employees/Groups/Inscriptions/Finance/Users/Roles modules were still
Livewire — each was migrated in its own later phase (see Phase 4 through
Phase 11 above), and as of Phase 11 none of them are Livewire anymore.

### Profile logic moved out of Livewire
`ProfilePage::updateProfile()`/`updatePassword()` logic is now
`ProfileController@updateProfile`/`@updatePassword`, validated by new
`UpdateProfileRequest`/`UpdatePasswordRequest` Form Requests instead of
inline `$this->validate()`. Same rules preserved exactly: own-email
uniqueness (ignoring self), `current_password` re-check, `Password::defaults()`,
employee phone/whatsapp sync via `Countries::join()`.

### Legacy files retained (not deleted)
- `app/Livewire/Backoffice/Profile/ProfilePage.php` + its Blade view —
  unused by any route now, kept for rollback per the task's explicit
  instruction; Phase 10 removes it once the migration is fully verified in
  production use
- `resources/views/backoffice/auth/{login,forgot-password,reset-password}.blade.php`
  — unused, kept for rollback
- `resources/views/components/backoffice/layout/guest.blade.php` — unused by
  Inertia pages (which use the new `GuestLayout.tsx`), still used by any
  other guest-facing Blade page that might exist; left untouched

### Auth security invariants (unchanged, verified by tests)
- Email-or-username login, rate limiting (5 attempts), `is_active` gate,
  generic `auth.failed` message (no account enumeration) — `LoginRequest`
  untouched
- Session regeneration on login, invalidation + token regeneration on
  logout — controllers untouched
- Password reset: Laravel's broker owns token validation/expiry; no custom
  token logic added
- CSRF: session-based, no manual token duplication; Inertia's `useForm`
  posts through the normal Laravel session/CSRF flow
- No JWT/Sanctum/localStorage tokens/second auth system introduced

### Mixed navigation strategy (unchanged from Phase 2, extended)
Header's Profile link already used Inertia `<Link>` (Phase 2) — now valid,
since Profile is a real Inertia page. Guest pages use plain anchors between
each other (login ↔ forgot-password ↔ reset-password) — none of those
cross-navigations were converted to `<Link>` since a fresh guest-session
page load has no benefit from client-side routing here and keeps the guest
root fully isolated from the authenticated shell's bundle.

### A note on Livewire's global asset auto-injection
Livewire 4 auto-injects its scripts/styles into **any** HTML response
during a request where `SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest`
is true — this is a static, per-request flag, not something scoped to a
specific view. Running `AuthTest`+`PasswordResetTest`+`ProfileTest` together
in one PHPUnit process transiently showed Livewire's `<script>`/`<style>`
tags inside an Inertia response's captured output; re-running the Profile
test in isolation showed **zero** Livewire injection — confirming this was
PHPUnit-process state bleed between adjacent tests, not a real per-request
leak. No code change was needed; flagged here in case it resurfaces.

### Test coverage
- `AuthTest.php`, `PasswordResetTest.php`, `ProfileTest.php` — updated only
  where their rendering assertion legitimately changed (`assertSee('backoffice/…')`
  string-match → `assertInertia(...)->component(...)`), all other assertions
  (credentials, rate limiting, `is_active`, session state) untouched
- New `tests/Feature/Backoffice/Inertia/AuthInertiaTest.php` (10 tests):
  guest-null shared props, no password in props, old-input preserved on
  failure (identifier yes, password no), minimal `auth.user` shape, reset
  page exposes only token+email, GET logout rejected at the HTTP-method
  level
- New `tests/Feature/Backoffice/Inertia/ProfileInertiaTest.php` (5 tests):
  no sensitive fields in `user` prop, `is_active`/roles/center cannot be
  changed via profile update, cannot edit another user's record

### Automated checks
| Check | Result |
|---|---|
| Targeted (`AuthTest`, `PasswordResetTest`, `ProfileTest`, `Inertia/`) | ✅ 46/46 passing |
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CNxd8NcD.js` 340.92 kB / 104.85 kB gzip (Phase 2: 325.16 kB / 101.55 kB gzip) |
| `C:\php84\php.exe artisan test` (full suite) | ✅ **320/320 passing, 1232 assertions** (Phase 2 baseline: 305/305, 1123 assertions) |
| ESLint | Not run — no ESLint config exists yet (skipped per instructions) |

### Manual browser verification (user, real Chrome, `artisan serve` + `npm run dev`)
Confirmed working: login (wrong-password error, correct login, password
visibility toggle, remember-me), forgot-password submission, profile page
(own data display, name update, password-change form). No issues reported.

### Known limitations
- No profile photo upload — the Livewire `ProfilePage` never had one
  (verified during audit: no `HasMedia`, no media collection reference
  anywhere in that component or its view); none was added, per the task's
  "do not assume fields" rule
- Guest pages do not use Inertia `<Link>` between each other (see Mixed
  navigation strategy above) — deliberate, not an oversight

---

## Phase 1 — Inertia foundation + Permissions pilot

Status: **Complete**, committed `2d3aa38` on `migration/inertia-react-preskool`.
See conversation history / commit message for full detail. 302/302 tests passing.

---

## Phase 2 — Browser smoke test (pre-shell-work baseline)

**Date**: 2026-07-30
**Tested by**: user, in a real Chrome browser (not headless), against the local dev
servers (`C:\php84\php.exe artisan serve` + `npm run dev`).

### Result: PASS

| Check | Result |
|---|---|
| Route loads | ✅ `http://127.0.0.1:8000/backoffice/permissions` loads |
| Inertia page renders | ✅ Real content: "Permissions" header, alert box ("...65 seeded"), full permission catalog grouped by module (Tableau de bord, Centres, ...) |
| No 500 error | ✅ |
| No blank page | ✅ |
| No React hydration/runtime error | ✅ Console clean, only the expected "Download React DevTools" dev-mode notice |
| No missing JS/CSS asset | ✅ |
| Authorization works | ✅ Logged in as `admin@gls.test` (super-admin), page renders correctly |
| Data visible | ✅ Real permissions data (65 seeded permissions, correct French labels/machine names) |
| Console errors | ✅ None |

### Bugs found and fixed during this smoke-test pass

1. **`vite.config.js` — `laravel-vite-plugin`'s `refresh: true` conflicted with
   `@vitejs/plugin-react`'s Fast Refresh preamble.** Confirmed via Vite's own
   dev-server log: `[Unhandled error] Error: @vitejs/plugin-react can't detect
   preamble. Something is wrong.` pointing at `BackofficeLayout.tsx`. This
   silently prevented React from mounting into `#app` (page stayed in
   `readyState: interactive` forever, zero visible errors reaching the
   browser console in some paths). **Fix**: scoped `refresh` to
   `['resources/views/**']` only, so it no longer watches/reloads on
   `resources/js/**` changes — that's `@vitejs/plugin-react`'s job now.

2. **Dev-server operational issue (not a code bug, but worth recording)**:
   during manual verification, `C:\php84\php.exe artisan serve` appeared to
   serve stale/wrong content (PHP 8.3 headers, 404s on real routes). Root
   cause: leftover orphaned `php.exe` processes from a previous session were
   still bound to port 8000 alongside the new one; killing all processes on
   that port and restarting `artisan serve` cleanly resolved it. No code
   change was needed — this was purely local dev-environment hygiene
   (stale background processes), not a defect in the app or the Vite/Inertia
   setup. Lesson: always verify a single clean listener on the dev port
   before drawing conclusions from `curl`/browser checks.

### Verification method note

An automated headless-Chrome (CDP-driven) smoke test was attempted first but
produced contradictory, unreliable results (including a "raw React render"
control test that itself failed to show output) — traced to the ad hoc test
harness itself, not the application. The trustworthy verification was the
user checking the page directly in a real, normally-running Chrome browser.
Future phases should prefer manual verification unless a proper, maintained
browser-automation tool is available in the environment.

---

## Phase 2 — PreSkool React shell implementation

**Date**: 2026-07-30
**Status**: **Complete.**

Built the shared Backoffice shell (Header, Sidebar, Breadcrumbs, Footer,
mobile sidebar + overlay, user dropdown, flash messages, page-header) as
reusable TSX components, wired into `BackofficeLayout.tsx`, and migrated the
Permissions pilot page onto it with real PreSkool card/table markup. See
`docs/react-theme-file-map.md` §0 for the full source→destination table and
`docs/bootstrap-react-integration-decision.md` for the Bootstrap ownership
decision.

### Second bug found and fixed during manual verification

A dashboard screenshot mid-phase showed the **Livewire** dashboard rendering
completely unstyled (bulleted lists instead of the sidebar, plain links
instead of buttons). Root cause: the dev server had been restarted using
`php -S 127.0.0.1:8000 -t public public/index.php` (a fixed router-script
argument), which routes **every** request — including static asset
requests like `/assets/crm-gls/css/bootstrap.min.css` — through Laravel's
front controller instead of letting PHP's built-in server serve static
files directly. Static asset requests were hitting the `auth` middleware's
guest-redirect and 302'ing to `/backoffice/login`, hence the missing CSS/JS.
**Fix**: restarted with plain `C:\php84\php.exe artisan serve`, which
internally uses Laravel's own `vendor/.../resources/server.php` router
script (static-file-aware) with the correct working directory — not a
manually-reconstructed equivalent. Confirmed via `curl`: every static asset
now returns `200` directly instead of `302`.

### Manual verification (user, real Chrome browser)

| Check | Result |
|---|---|
| Dashboard (Livewire) renders with correct PreSkool styling after the server fix | ✅ |
| `/backoffice/permissions` renders inside the new React shell (header, sidebar, breadcrumbs) | ✅ |
| Visual match against the PreSkool theme | ✅ "matches the PreSkool theme, sidebar/header render correctly" |
| Existing Livewire pages (Roles, etc.) still work, styled correctly | ✅ (confirmed via the Roles page screenshot mid-session) |

### Automated checks

| Check | Result |
|---|---|
| `npx tsc --noEmit` | ✅ Clean |
| `npm run build` | ✅ Succeeds — `app-CrpMxtFV.js` 325.16 kB / 101.55 kB gzip (Phase 1 baseline: 315.03 kB / 98.96 kB gzip) |
| `C:\php84\php.exe artisan test` | ✅ **305/305 passing, 1123 assertions** (Phase 1 baseline: 302/302, 1095 assertions — +3 new Phase 2 shared-prop tests) |
| ESLint | Not run — no ESLint config exists in this project yet (skipped per instructions: "if configured") |

### Verification method note (continued from Phase 1 entry)

The ad hoc headless-Chrome CDP harness was **not** reused for this phase's
verification, per the user's explicit choice after Phase 1's unreliable
results — all rendering/console/interaction checks were done by the user in
a real browser instead.
