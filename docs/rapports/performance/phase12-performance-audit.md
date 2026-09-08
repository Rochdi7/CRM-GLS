# Phase 12 — Performance Audit (baseline measurements)

Date: 2026-07-31 · Method: authenticated HTTP probes against `artisan serve`
+ local PostgreSQL dev DB (`gls_crm`), one warm request per page, SQL
query log + timing + peak memory captured via a temporary env-gated
`DB::listen` hook (fully reverted after measurement); Inertia props payload
measured from the embedded `<script data-page>` JSON; bundle sizes from
`vite build`.

## Per-page baseline (BEFORE Phase 12 optimizations)

| Page | Wall (warm) | SQL queries | SQL ms | Peak MB | Props payload |
|---|---|---|---|---|---|
| /backoffice/dashboard | 1.18 s | 18 | 125.7 | 32 | 1.2 KB |
| /backoffice/students | 1.34 s | 15 | 110.5 | 36 | 16.8 KB |
| /backoffice/inscriptions | 1.35 s | 16 | 121.3 | 36 | 15.0 KB |
| /backoffice/groups | 1.32 s | 17 | 124.9 | 34 | 4.5 KB |
| /backoffice/employees | 1.31 s | 15 | 220.2 | 36 | 13.8 KB |
| /backoffice/users | 1.21 s | 15 | 109.0 | 34 | 3.9 KB |
| /backoffice/settings | 1.22 s | 12 | 138.4 | 34 | 2.7 KB |
| /backoffice/roles | 1.11 s | 12 | 110.0 | 34 | 2.3 KB |
| /backoffice/permissions | 1.18 s | 11 | 122.0 | 32 | 4.5 KB |
| /backoffice/caisses | 1.35 s | **40** | 154.8 | 34 | 8.2 KB |
| /backoffice/encaissements | 1.20 s | 14 | 101.6 | 34 | 3.2 KB |
| /backoffice/depenses | 1.35 s | 16 | 122.5 | 34 | 3.1 KB |
| /backoffice/types-depenses | 1.47 s | 11 | 81.8 | 34 | 1.7 KB |
| /backoffice/groups-historique | 1.21 s | 11 | 188.2 | 34 | 1.6 KB |

## Key findings

1. **Fixed per-request overhead dominates wall time.** SQL work is
   100-220 ms of a 1.1-1.5 s wall; the "slowest query" everywhere is the
   FIRST query absorbing PostgreSQL **connection establishment (~78 ms)**,
   plus a DB-backed session read+write (~85 ms combined) on every request.
   These are environment/deployment costs (fresh connection per request,
   `SESSION_DRIVER=database`, `CACHE_STORE=database`, artisan-serve
   single-worker PHP), not application inefficiencies — production levers
   are listed in the report (pgbouncer/persistent connections, redis/file
   sessions, FPM+opcache).
2. **Caisses over-fetch (40 distinct queries, zero duplicates):** the
   controller computed all four tabs' datasets (both journal scopes ×
   the 4-table PHP merge, tills, transfers) on every load AND every tab
   switch. → Fixed (tab-gated props).
3. **Static 8.2 KB phone-country catalog** shipped as a prop by 4
   controllers; 3 of the 4 pages never read it, and an identical client
   catalog already existed. → Fixed (client-side only).
4. **One 566.91 KB JS bundle** carried every page eagerly. → Fixed
   (route-based code splitting; main entry now 322 KB).
5. **No render-level React problems found**: tables are 10-25 rows, no
   measured re-render hot spots, no expensive client calculations —
   `React.memo`/`useMemo` additions would be speculative and were not
   made (per the "only when measurable" rule).
6. **No N+1s** in list queries (eager loads verified); no duplicate SQL
   statements anywhere except the deliberate caisses multi-dataset case.
7. **ILIKE searches** seq-scan correctly at current volumes (12-145
   rows); trigram indexes remain a documented future tool, not a current
   need. **FK-index audit** (pg_constraint vs pg_index, whole schema)
   found exactly two unindexed FK columns on real query paths. → Fixed
   (migration).
8. Slow-query outliers (employees 220 ms, groups-historique 188 ms SQL)
   trace to the same connection-setup absorption on a cold pool, not to
   any individual heavy statement.
