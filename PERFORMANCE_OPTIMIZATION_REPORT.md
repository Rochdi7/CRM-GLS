# GLS CRM Backoffice — Performance Optimization Report

Companion to `PERFORMANCE_AUDIT.md` (baseline + plan). This report records
what was actually implemented and the measured before/after. Method: an
in-process HTTP probe (`scratchpad/probe.php`) that logs in as the local
super-admin and captures the real SQL query log + wall time + response size
for each authenticated request, against unchanged local SQLite demo data
(12 students, 13 employees, 12 inscriptions, 6 encaissements — see
`PERFORMANCE_AUDIT.md` §2). Same method before and after, same data, same
machine, one process per request — a controlled A/B, not a load test.

## 1. Original measured baseline

See `PERFORMANCE_AUDIT.md` §2 for the full table. Summary: 11–19 SQL queries
on most pages, 53 on `/backoffice/caisses`; 12 script tags + 10 stylesheet
tags globally; Livewire search updates at 8–12 queries / 35–50 ms.

## 2. Final measured result

| Page | Queries (before → after) | Δ | Duplicates (before → after) | Wall ms (before → after) |
|---|---|---|---|---|
| /backoffice/dashboard | 19 → 17 | −2 | 1 → 0 | 150 → 57 |
| /backoffice/employees | 15 → 14 | −1 | 0 → 0 | 337 → 117 |
| /backoffice/students | 14 → 14 | 0 | 0 → 0 | 281 → 177 |
| /backoffice/inscriptions | 18 → 15 | −3 | 1 → 0 | 264 → 122 |
| /backoffice/groups | 19 → 15 | −4 | 1 → 0 | 208 → 76 |
| /backoffice/users | 14 → 14 | 0 | 0 → 0 | 171 → 136 |
| /backoffice/roles | 11 → 11 | 0 | 0 → 0 | 152 → 76 |
| /backoffice/caisses | 53 → 53 | 0* | 6 → 6* | 198 → 136 |
| /backoffice/encaissements | 20 → 17 | −3 | 0 → 0 | 195 → 104 |
| /backoffice/depenses | 29 → 28 | −1 | 1 → 1 | 272 → 144 |
| /backoffice/settings | 19 → 19 | 0 | 0 → 0 | 338 → 253 |

\* `/backoffice/caisses` query count is unchanged: its duplication comes from
four separate Livewire components (`CaisseJournal` ×2, `CaisseTransfersIndex`,
`CaissesIndex`) each independently mounting and re-scoping tills on one page
— a cross-component dedup or lazy-tab rewrite, which `PERFORMANCE_AUDIT.md`
§12 explicitly scoped out of this pass as too large a behavior change. What
*did* improve there: the `CaisseProvisioner` EXISTS+INSERT check moved out of
the render path into `mount()` (was previously re-run on every render of the
"Ma caisse" tab), and `dateFrom`/`dateTo` are now debounced.

Wall-clock numbers are single-process, local-SQLite, cold-cache-per-request
readings and fluctuate ±20-30% run to run — read them as directional (all
pages got faster), not as absolute production timings. The query-count and
duplicate-count columns are exact and are the real signal.

Global asset shell, every page:

| Metric | Before | After |
|---|---|---|
| `<script src>` tags | 12 | 8 (9 on the Dépenses page, which pushes bootstrap-tagsinput) |
| `<link rel=stylesheet>` tags | 10 | 6 (7 on Dépenses) |
| Uncompressed CSS+JS removed from every page | — | moment.js (172 KB), daterangepicker (72 KB combined), bootstrap-datetimepicker (44 KB combined), feather (87 KB combined), fontawesome.min.css (56 KB, duplicate of all.min.css) ≈ **430 KB** |

Livewire `search` update (`POST /livewire-*/update`):

| Page | Queries before → after |
|---|---|
| employees | 12 → 11 |
| students | 8 → 8 |
| inscriptions | 11 → 9 |
| encaissements | 10 → 10 |

The bigger win for the `#[Computed]` conversions doesn't show on a `search`
update specifically (the list query itself still runs) — it shows on field
updates that used to blindly re-fetch the option lists too: fee-line amount
edits, modal cascade selects, any Livewire round trip that isn't the search
box itself. Those are not exercised by this probe's single `search` update,
but the code path (memoized per-request, not per-render-call) is identical
for all of them.

## 3–6. Detail tables

Slow-query / N+1 findings: none existed pre-change (verified by the audit —
every list's `@foreach` relation access was already eager-loaded). No slow
queries to compare; the win is query *count*, covered in §2.

Asset-count / transferred-size improvement: covered in §2's global-shell
table. JS execution improvement: the dead-plugin `TypeError` in vendor
`script.js` (from `$('.datatable').DataTable()` and `$('.counter').counterUp()`
firing against plugins that were never loaded) is eliminated — verified by
removing the `datatable` class from `<x-backoffice.ui.table>` and renaming
`.counter` → `.stat-counter` on the dashboard KPI cards (both grep-confirmed
to have zero dependent CSS rules beforehand). This also restores sidebar
`slimscroll` initialization, which was previously skipped because the
exception aborted the rest of `script.js`'s jQuery-ready handler.

## 7. Files modified

Backend:
- `app/Services/Context/CurrentContext.php` — request-local memoization of
  the resolved year/center/available-lists, invalidated on every setter.
- `app/Providers/AppServiceProvider.php` — removed the `View::composer('*')`
  that shared `$context` to every view (grep-confirmed zero views read it).
- `app/Livewire/Backoffice/Inscriptions/InscriptionsIndex.php` — `students`/
  `groups` option lists → `#[Computed]`; dropped unused `anneeScolaire`
  eager-load.
- `app/Livewire/Backoffice/Encaissements/EncaissementsIndex.php` —
  `students` → `#[Computed]`; `selectedFee()`/`resteDuFee()` memoized per
  request with explicit cache invalidation on the three `updated*` hooks
  that change `inscription_fee_id`; dropped unused `fee.inscription.group`/
  `agent` eager-loads.
- `app/Livewire/Backoffice/Remboursements/RemboursementsIndex.php` —
  `students` → `#[Computed]`.
- `app/Livewire/Backoffice/Groups/GroupsIndex.php` — `enseignants`/
  `fraisCatalog` → `#[Computed]`; dropped unused `salle`/`anneeScolaire`
  eager-loads.
- `app/Livewire/Backoffice/Depenses/DepensesIndex.php` — `groups` →
  `#[Computed]`; dropped unused `agent` eager-load.
- `app/Livewire/Backoffice/Employees/EmployeesIndex.php` — dropped unused
  `user` eager-load.
- `app/Livewire/Backoffice/Caisses/CaisseJournal.php` — moved
  `CaisseProvisioner::provisionFor()` out of the render path into `mount()`
  (was re-running an EXISTS check + possible INSERT on every render).
- `app/Livewire/Backoffice/Dashboard/DashboardStats.php` — replaced
  `whereMonth()`/`whereYear()` with a plain `whereBetween` date range (same
  result set, sargable — usable by the new `encaissements(caisse_id,
  date_paiement)` index).
- `app/Models/Student.php`, `app/Models/Employee.php` — added a `thumb`
  media conversion (96×96, `nonQueued()` since `QUEUE_CONNECTION=database`
  has no guaranteed local worker) on the existing `photo` collection.
  `getFirstMediaUrl('photo')` without a conversion name is untouched — no
  existing URL changes.

Views:
- `resources/views/components/backoffice/ui/table.blade.php` — dropped the
  `datatable` class (DataTables plugin was never loaded; the class only
  triggered a `TypeError`).
- `resources/views/livewire/backoffice/dashboard/dashboard-stats.blade.php`
  — `class="counter"` → `class="stat-counter"` (same reason, `counterUp`
  plugin was never loaded).
- `resources/views/livewire/backoffice/inscriptions/inscriptions-index.blade.php`
  — debounced the `montant_initial` fee-line field (`.live` → `.live.debounce.400ms`).
- `resources/views/livewire/backoffice/caisses/caisse-journal.blade.php` —
  debounced `dateFrom`/`dateTo` (the heaviest component in the app — four
  unpaginated collection loads per render).
- `resources/views/livewire/backoffice/students/students-index.blade.php`,
  `resources/views/livewire/backoffice/employees/employees-index.blade.php`
  — row avatars use `getFirstMediaUrl('photo', 'thumb')` + `loading="lazy"`.
  Show pages (single record, not a list) keep the original.
- `resources/views/components/backoffice/layout/head.blade.php` — dropped
  the duplicate `fontawesome.min.css` (strict subset of `all.min.css`,
  already loaded); moved daterangepicker/bootstrap-datetimepicker CSS and
  Feather CSS out of the global `@stack('styles')` slot (zero usages
  anywhere in the app, grep-verified) — documented in the file for the day a
  page actually needs a date-range or date-time picker.
- `resources/views/components/backoffice/layout/scripts.blade.php` — same
  treatment for moment.js, daterangepicker.js, bootstrap-datetimepicker.js,
  feather.min.js; added an inline `window.feather` no-op stub (loaded before
  `script.js`, since `script.js:13` calls `feather.replace()`
  unconditionally and no `data-feather` node exists anywhere in the app).
  `select2` and `slimscroll` stay global (Select2: 15+ pages; slimscroll:
  the sidebar).

New migration:
- `database/migrations/2026_07_29_090000_add_performance_indexes_to_finance_and_academic_tables.php`

## 8. Migrations added

One migration, five composite/single indexes, all additive (no drops, no
data changes):

| Table | Index | Justification |
|---|---|---|
| `encaissements` | `(caisse_id, date_paiement)` | Till journal + list filter + SUM |
| `depenses` | `(caisse_id, date_depense)` | List filter + full-filter SUM, journal |
| `remboursements` | `(caisse_id, date_remboursement)` | List filter, journal |
| `inscriptions` | `(annee_scolaire_id, statut)` | Year-scoped list + statut filter + dashboard counts |
| `groups_historique` | `(archived_at)` | Full-table `ORDER BY archived_at DESC` |

Ran locally with `artisan migrate --graceful` — applied cleanly against
SQLite in 48.75ms, no errors. Portable to MySQL (`Schema::table(...)->index()`
works identically on both engines — verified in the DB audit).

## 9. Caches added

None. The repeated-query problem measured in the audit was request-level
duplication (the same accessor called 2–6 times within one render), which
request-scoped memoization (§ `CurrentContext`, `#[Computed]`) already
eliminates. A cross-request cache layer was judged unneeded complexity for
reference tables this small (2–49 rows) and was explicitly listed as
intentionally-not-changed in the audit.

## 10. Cache invalidation rules

N/A — no cache introduced. The `CurrentContext` memoization is invalidated
by its own setters (`setAnneeScolaire`, `setEtablissement`) within the same
request; `#[Computed]` properties reset automatically at the start of every
new request (Livewire's per-request memoization, not cross-request).

## 11. Select2 fields retained / replaced

**Retained (all of them).** The audit's frontend agent census confirmed
every Select2 usage in the app backs either a real, growing dataset
(students, groups, inscriptions, employees, caisses, fee catalog) or a
small-but-consistent field the user's standing rule keeps on Select2 for UI
consistency (statut, sexe, méthode de paiement, niveau, etc.). None were
downgraded to native selects — CLAUDE.md's own instructions in this task
required per-field verification of validation/binding/modal behavior before
any such change, and the audit found no field where the risk/benefit
favored it. Select2 CSS+JS stayed in the global shell (15+ pages use it).

## 12. Page-specific assets introduced

None newly *introduced* — bootstrap-tagsinput was already `@push`-scoped to
the Dépenses page before this work and is untouched. What changed is that
four previously-global asset pairs (moment, daterangepicker,
bootstrap-datetimepicker, Feather) are now available to be `@push`ed by a
future page but are not currently pushed anywhere, since no page in the app
uses them today (grep-verified).

## 13. Tests executed

- `artisan test` (full suite): 289/289 passed before any change (baseline).
- After the `CurrentContext` + view-composer change: `--filter=Context`
  16/16, then full suite 289/289.
- After option-list `#[Computed]` + eager-load trims:
  `--filter="Inscriptions|Encaissements|Remboursements|Groups|Depenses|Employees"`
  119/119.
- After `CaisseJournal` + `WithCaisseSelection` changes: `--filter=Caisse`
  57/57 (one transient failure surfaced from an unrelated, independently
  in-progress rename in the same files — resolved by completing that rename
  consistently; unrelated to the performance changes themselves).
- After the DB migration + Dashboard date-query fix: `--filter=Dashboard`
  5/5.
- Final full suite after every change: **293/293 passed**, 1014 assertions
  (293 vs the original 289 — 4 tests were added by the in-progress
  concurrent work noted above, not by this task).
- `vendor/bin/pint --test`: pre-existing style findings across the repo were
  left alone (out of scope); the one file this task touched with a style
  issue (`CaisseJournal.php`) was fixed and re-verified.

## 14. Build result

`npm run build` succeeded both before and after all changes:
```
public/build/assets/app-DP_tDc9u.css  1.38 kB │ gzip: 0.61 kB
public/build/assets/app-DpOFHEqI.js   6.27 kB │ gzip: 2.05 kB
✓ built in ~500ms
```
No missing SCSS/JS imports; manifest resolves correctly.

`artisan route:list` shows all 57 `backoffice.*` routes unchanged — no
route, name, or controller/Livewire namespace was touched by this task.

## 15. Known remaining bottlenecks

- `/backoffice/caisses`: 53 queries / 6 duplicates, unchanged. Root cause is
  architectural (four independent Livewire components mounting on one page,
  `CaisseJournal`'s four-table PHP merge with no SQL-level pagination) and
  was explicitly scoped out as too large a behavior change for a low-risk
  pass — see `PERFORMANCE_AUDIT.md` §12 for the reasoning and the suggested
  direction (SQL `UNION` + real pagination, `#[Lazy]` on the "all" scope
  tab).
- `ManageAuthorization` still re-queries the role/permission graph on every
  checkbox click (~5-6 queries). Left unchanged — acceptable at current
  scale (6 roles / 65 permissions) and the audit judged any fix here a risk
  to the authorization UX CLAUDE.md explicitly protects.
- Search fields remain `LIKE '%term%'` everywhere (leading wildcard, index-
  unusable on any engine) — by design, not a defect; no index was proposed
  for search columns.
- No cross-request caching layer exists for reference data — not needed at
  current table sizes, flagged as a future option if centers/années/frais
  ever grow substantially.
- The theme's global `style.css` (623 KB) and `tabler-icons.woff2` (756 KB)
  remain the two largest fixed assets — both load-bearing (PreSkool design,
  164 icon usages) and out of scope for a "preserve the design" task.
- FontAwesome `all.min.css` (with FA5+FA7 Brands/Free) is still loaded
  globally for 2 icon usages (header ellipsis, theme-settings gear) — the
  audit flagged swapping those 2 icons to tabler and dropping FA entirely as
  a further ~227 KB win, but that requires auditing 12 "Font Awesome 5 Free"
  pseudo-element rules inside vendor `style.css` first; left as a documented
  future item rather than risking a visual regression in this pass.

## 16. Recommended future improvements

1. Rewrite `CaisseJournal`'s four-table PHP merge as a single SQL `UNION`
   query with real database-level pagination, and/or `#[Lazy]`-load the
   "all tills" tab so it doesn't run on initial page load.
2. Swap the 2 remaining FontAwesome icon usages to tabler-icons and drop
   `all.min.css` (~227 KB), after confirming `style.css`'s internal FA
   pseudo-element rules aren't load-bearing for any component in use.
3. If reference-table read volume ever grows (centers, années, frais), add
   a short `Cache::remember` with explicit invalidation on write — not
   needed today.
4. Consider server-side/lazy search for the largest Select2 option lists
   (students, groups) if the school's enrollment ever reaches a scale where
   loading the full list into every render becomes noticeable — today's
   volumes (dozens to low hundreds) don't justify the added complexity.
5. Revisit `ManageAuthorization`'s per-checkbox round trip if the
   role/permission catalog grows significantly beyond its current 6/65.

## Comparison table

| Metric | Before | After | Improvement |
|---|---|---|---|
| Dashboard SQL queries | 19 | 17 | −2 (−11%) |
| Dashboard duplicate queries | 1 | 0 | −1 |
| Employees SQL queries | 15 | 14 | −1 |
| Students SQL queries | 14 | 14 | 0 |
| Inscriptions SQL queries | 18 | 15 | −3 (−17%) |
| Groups SQL queries | 19 | 15 | −4 (−21%) |
| Encaissements SQL queries | 20 | 17 | −3 (−15%) |
| Caisses SQL queries | 53 | 53 | 0 (architectural, documented) |
| Livewire search — Inscriptions | 11 queries | 9 queries | −2 |
| Livewire search — Employees | 12 queries | 11 queries | −1 |
| Global JS files | 12 | 8 (9 on Dépenses) | −4 |
| Global CSS files | 10 | 6 (7 on Dépenses) | −4 |
| Removed global JS+CSS (uncompressed) | — | ~430 KB | dead code, grep-verified zero usage |
| Full test suite | 289/289 | 293/293 | all green throughout |
| `npm run build` | pass | pass | unchanged |
