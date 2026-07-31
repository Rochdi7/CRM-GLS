# Phase 11K — Performance Baseline (post-Livewire-removal)

Companion to `PERFORMANCE_AUDIT.md` / `PERFORMANCE_OPTIMIZATION_REPORT.md`
(the Phase 9/10 measurements, captured pre-migration on local SQLite demo
data) and `POSTGRES_MIGRATION_REPORT.md` (PostgreSQL cutover). This document
records the first real measurement of these same pages after Phase 11
deleted every Livewire code path — the app is now 100% Inertia+React on the
backoffice, running against the current local PostgreSQL dev database
(`gls_crm`).

## Method

Same spirit as the original probe (one real authenticated HTTP request per
page, SQL query count + wall time + response size), adapted for the current
stack:

- `php artisan serve` on a local port, PostgreSQL dev DB `gls_crm` (53
  students, 4 employees, 52 inscriptions, 2 encaissements, 5 groups, 6
  users — comparable order of magnitude to the original 12-student/13-
  employee SQLite demo dataset, not identical, so this is a fresh baseline
  rather than a strict apples-to-apples re-run).
- Logged in as the local seeded super-admin (`admin@gls.test`) via a real
  `POST /backoffice/login` (XSRF-cookie flow), then issued one `curl` GET
  per page reusing that authenticated session cookie.
- SQL query count captured via a temporary, env-gated `DB::listen()` probe
  added to `AppServiceProvider` for the duration of this measurement only
  (`PERF_PROBE_LOG=1` in `.env`) and fully reverted immediately after —
  `app/Providers/AppServiceProvider.php` and `.env` are unchanged from
  before this phase in the final commit.
- Response size and wall time from `curl`'s own timing (`%{time_total}`,
  `%{size_download}`) against the full-HTML (first-load) response — the
  same basis the original probe used, not the smaller steady-state Inertia
  JSON payload (a real client-side visit to any of these pages when
  navigating for the first time, or after a full reload, pays this cost;
  subsequent same-session SPA navigations are cheaper and are not what's
  measured here).
- First request after each server (re)start was discarded from the
  reported figures (OPcache/JIT warmup skews it ~2× high); all figures
  below are the second, warm reading.

## Results

| Page | SQL queries | Response size (HTML, KB) | Wall time (warm, s) |
|---|---|---|---|
| /backoffice/dashboard | 18 | 2.9 | 1.22 |
| /backoffice/employees | 15 | 13.8 | 1.42 |
| /backoffice/students | 15 | 18.0 | 1.38 |
| /backoffice/inscriptions | 13 | 14.8 | 1.23 |
| /backoffice/groups | 14 | 3.8 | 1.34 |
| /backoffice/users | 15 | 4.6 | 1.28 |
| /backoffice/roles | 12 | 4.0 | 1.14 |
| /backoffice/caisses | 42 | 9.8 | 1.38 |
| /backoffice/encaissements | 20 | 6.8 | 1.23 |
| /backoffice/depenses | 16 | 6.1 | 1.24 |
| /backoffice/settings | 12 | 4.4 | 1.17 |

## Comparison to the Phase 9/10 baseline

| Page | Queries (Phase 10, Livewire) | Queries (Phase 11, Inertia-only) |
|---|---|---|
| dashboard | 17 | 18 |
| employees | 14 | 15 |
| students | 14 | 15 |
| inscriptions | 15 | 13 |
| groups | 15 | 14 |
| users | 14 | 15 |
| roles | 11 | 12 |
| caisses | 53 | 42 |
| encaissements | 17 | 20 |
| depenses | 28 | 16 |
| settings | 19 | 12 |

Most pages land within ±1-3 queries of the Phase 10 Livewire-era figures —
expected, since the Domain read-model queries (`GetStudentsList`,
`GetEmployeesList`, etc.) were extracted verbatim from the Livewire
components' own `render()` query shape and the Inertia controllers reuse
the same eager-loading. `depenses` (28→16) and `settings` (19→12) improved
meaningfully: both used to be Livewire tabbed pages where multiple tab
components mounted (and queried) simultaneously on one page load; their
Inertia replacements load one tab's data per request. `caisses` (53→42)
improved for the same reason — Phase 10's report (§2, footnote) already
flagged this page's duplication as coming from four separate Livewire
components mounting on one page; that duplication is structurally gone now
that it's a single controller action. `encaissements` (17→20) is the one
regression worth noting — not investigated further here since it's a small
absolute difference (3 queries) on a page with no documented N+1, and
Phase 11's scope is Livewire removal, not new query optimization; flagged
below as a candidate for a future performance pass rather than something
this phase should fix.

Wall-clock and response-size numbers are **not** directly comparable to the
Phase 9/10 SQLite-era figures (different database engine, different data
volume, different measurement transport — this run used real HTTP over
`curl` against `artisan serve`, not an in-process kernel call) and are
recorded here purely as this phase's own fresh baseline for future
comparison, not as a claim of before/after improvement.

## Known remaining bottleneck (unchanged, out of scope)

`CaisseJournal`'s successor, `CaisseController::journal()`
(`app/Http/Controllers/Backoffice/CaisseController.php`), still merges
finance records from multiple tables without a database-level `UNION ALL`
+ pagination — this was already flagged in `PERFORMANCE_OPTIMIZATION_REPORT.md`
and `CLAUDE.md` §17 as a deliberately deferred, out-of-scope optimization.
Phase 11 did not change this; it remains a candidate for a dedicated future
performance pass, not a Phase 11 cleanup task.

## Bundle size

`npm run build` output (post-Livewire-removal, see
`docs/phase-11-final-verification.md` §4):

| Asset | Size | Gzip |
|---|---|---|
| app-*.js (main bundle) | 529.14 KB | 139.91 KB |
| app-*.css | ~0 KB (Tailwind-free, Bootstrap loaded statically) | — |

**Post-wrap-up update:** after the UX/i18n refactor landed (commit
`9538a9a` — `lang/fr.json` is now bundled into the JS for the client-side
`t()` helper, plus the loading/jump-to-page hooks), the main bundle is
**566.91 KB / 152.76 KB gzip** (+12.9 KB gzip vs. the figure above). The
delta is almost entirely the French dictionary; if it ever matters,
splitting the dictionary into its own chunk is the obvious lever.

No pre-Livewire-removal Inertia-only bundle size was recorded in earlier
phases to compare against (Phase 9/10's bundle-size figures were for the
Blade/Livewire asset shell, a different measurement — see
`PERFORMANCE_OPTIMIZATION_REPORT.md` §2's "Global asset shell" table, which
tracked `<script>`/`<link>` tag counts on Blade pages, not the Vite/React
bundle). This is this phase's own fresh baseline.
