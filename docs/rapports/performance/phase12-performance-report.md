# Phase 12 — Performance + Stabilization Report

Date: 2026-07-31 · Branch: `migration/inertia-react-preskool`
Baseline data: `docs/phase12-performance-audit.md`.

## Executive summary

Phase 12 profiled every backoffice page, then shipped targeted fixes:
route-based **code splitting cut the main JS bundle 43%** (566.91 →
322.11 KB; 152.76 → 101.35 KB gzip), the **Caisses page dropped from 40
SQL queries to 15-24 per visit** by computing only the active tab's
dataset, an **8.2 KB static prop was removed** from four pages, and two
genuinely missing **FK indexes** were added. A parallel security audit
led to **center-scoping validation on every money-store till id** plus
throttling of the password-reset and password-regenerate endpoints. A UX
audit surfaced — and this phase fixed — **five silently-broken modal save
buttons**, two classes of Inertia `transform()` leaks that corrupted
subsequent submissions, ambiguous label-based record matching on the
Inscriptions edit modal (plus two literal NUL bytes hiding the file from
grep), sticky flash-dismissal, and a missing loading state on the
Inscriptions list. Dead code was removed and the orphaned Groups-History
page is now reachable. Full suite: **308/308 tests, 1559 assertions**;
tsc clean; build clean. No architecture changes.

## Optimizations shipped (before → after)

| Change | Before | After | Commit |
|---|---|---|---|
| Route-based code splitting (lazy page glob) | 566.91 KB / 152.76 gzip, one bundle | **322.11 KB / 101.35 gzip** entry + 1-20 KB per-page chunks (i18n 35.7 KB, layout 12.3 KB shared) | `1413a2b` |
| Countries prop removed (client catalog reused) | 8.2 KB × 4 pages' props (Students 16.8 KB total) | Students props ≈ 8.5 KB; same cut on Inscriptions/Employees/Profile | `d5f8d9b` |
| Caisses tab-gated datasets | 40 queries every load & switch | ma-caisse 24 · journal 23 · comptes 17 · transferts 15 | `f499d86` |
| FK indexes (`group_frais.frais_id`, `groups_historique.group_id`) | seq-scan on real lookup paths | indexed (only 2 warranted repo-wide; nothing speculative added) | `ce68a3c` |

Initial-load JS (entry + layout + i18n + one page chunk) is now
**≈ 372-390 KB raw / ≈ 118 KB gzip** vs 566.91/152.76 before; subsequent
navigations fetch only a small page chunk. The <350 KB target is met for
the main entry (322 KB).

## SQL / PostgreSQL

- EXPLAIN ANALYZE on the structural queries (ILIKE search, list
  pagination, joins): all correct plans; at dev volume (≤145 rows) seq
  scans are optimal and `idx_scan=0` statistics are non-evidence. **No
  speculative indexes added.**
- FK-index audit: complete (2 added, 1 vendor-standard exception
  documented in the migration).
- COUNT/pagination: standard LengthAwarePaginator, sargable date ranges
  already in use; nothing to change.
- The known `GetCaisseJournal` PHP-merge bottleneck is unchanged
  (documented, deferred — CLAUDE.md §17); its per-request cost was halved
  anyway by tab gating (one scope instead of two).

## React

No memoization added: profiling found no render hot spots (10-25-row
tables, cheap renders, no expensive client computation). The one
module-scope derivation added (Profile's iso-keyed country record)
computes once. `React.memo`/`useMemo` without a measured problem would be
speculative complexity — deliberately skipped.

## Inertia

- Tab-gated props on Caisses (above) — the biggest payload/query win.
- Partial-reload pattern (`preserveState/preserveScroll/replace`) was
  already standard on every list; verified, unchanged.
- Payload review: all other pages carry 1-5 KB of props after the
  countries removal; row shapes are lean (`through()` mappings). The
  Inscriptions row gained 2 int fields (`studentId`/`groupId`) to fix a
  correctness bug — negligible size, removed the ambiguous label matching.

## Security (audit + fixes)

Fixed this phase (`79eb0e8`):
- **`App\Rules\AccessibleCaisse`** on `caisse_id` of Store{Encaissement,
  Depense,Remboursement}Request and both ends of StoreCaisseTransferRequest
  — a center-locked user can no longer move money through another
  center's till via a tampered request (regression-tested).
- `throttle:5,1` on `password.email`; `throttle:10,1` on
  `users.regenerate-password`.

Verified solid: full permission-middleware + policy coverage on every
mutation route; no mass assignment; no raw-input SQL; zero
`dangerouslySetInnerHTML`/`{!! !!}`; CSRF intact; no committed secrets;
money `montant` bounds validated; center scoping enforced in policies.

**Top remaining finding (HIGH, deliberately not fixed in this phase):**
media files (student/employee photos, expense justificatifs) are served
as public static files at `/media/<8-hex>/<predictable-name>` with **no
authorization on delivery** — an obtained or guessed URL bypasses center
isolation for exactly this data class. The fix (policy-gated media
controller or signed URLs + private disk) changes media delivery
app-wide and deserves its own dedicated pass. **Recommended as the first
item of the next phase.**

## UX (audit + fixes)

Fixed this phase (`ff8f241`, `1becd0b`):
- Five footer save buttons that submitted nothing (FormActions now takes
  a `form` attribute; Playwright-verified the submits reach the server).
- `transform()` leaks: edit's `_method: put` bleeding into creates
  (silent 405s) and delete's empty-payload transform bleeding into later
  saves (spurious "required" on every field) — 7 files, reset in
  `onFinish`.
- Inscriptions edit now preselects student/group **by id**; NUL-byte
  sentinels removed (file is greppable again).
- Flash dismissal resets when a new flash arrives.
- Inscriptions list gained the standard loading state.

Verified good: empty states everywhere, per-field errors with
aria-invalid/describedby, universal double-submit protection, modal
focus trap + Escape, accessible table spinner.

## Code quality

Removed: `InscriptionFeeController` + its 2 Form Requests (zero routes/
references), 3 dead TS interfaces. Deduplicated: `UpdateStudentRequest`
now extends the byte-identical `StoreStudentRequest`. Recovered: the
fully-built but unlinked Groups-History page is now in the sidebar
(`groups.view`). Verified clean: zero unused imports repo-wide; no god
classes.

## Remaining technical debt (prioritized)

1. **Media delivery authorization** (security HIGH — see above).
2. `useFilterReload` hook: the per-page `reload(filters)` function is
   re-implemented ~8× byte-identically; extract to `resources/js/Hooks/`.
3. `scopeToActiveCenter()` duplicated across 7 Domain query classes —
   extract a `app/Domain/Shared` trait.
4. `InscriptionController::store()` inlines student creation that
   `StudentController::buildPayload()` centralizes — extract a
   `CreateStudent` Domain action (drift risk in enrollment logic).
5. Tabbed modals (Students, Inscriptions) hide validation errors on
   inactive tabs — add per-tab error badges or a summary above the tabs.
6. No user-facing handler for non-validation failures (500s): register
   `router.on('invalid'/'exception')` + `.catch` on the 4 raw `fetch()`
   calls.
7. `Inscriptions/Index.tsx` (985 lines) → extract the modal;
   `Types/index.ts` (~1000 lines) → per-module files.
8. i18n: most pages hardcode French instead of `t()` — convert
   incrementally for consistency with CLAUDE.md §5.
9. `window.confirm` on transfer validation / group archive vs
   ConfirmDialog everywhere else.
10. RelatedRecordsTable doesn't forward `loading`; minor a11y items
    (icon-button labels, `role="tab"`, arrow-key menu nav, jump-input
    blur-commit).
11. Deployment levers for the measured fixed overhead: persistent DB
    connections or pgbouncer, redis/file sessions+cache, PHP-FPM +
    opcache (the ~1 s dev wall floor is artisan-serve, not the app).
12. i18n dictionary (35.7 KB chunk) could split out of the initial load
    if it ever matters.

## Business feature roadmap (Step 10 — suggested order)

1. **Dashboard KPIs** — highest visibility, data already exists
   (GetDashboardStats), small scope; do together with a date-range
   filter.
2. **Exports (CSV/PDF)** — finance/registrations lists; frequently the
   first real-world back-office demand; server-side, low UI risk.
3. **Attendance** — schema module already planned (app/Domain/Attendance
   placeholder); core daily-operations feature; touches groups/students
   only.
4. **Student history timeline** — read-only aggregation over existing
   activitylog + inscriptions + payments; low risk, high support value.
5. **Notifications** — in-app first (transfer-pending, payment-received);
   builds on existing flash/toast infrastructure.
6. **Reports/Analytics** — revenue by center/period, enrollment funnels;
   needs the export foundation (2) and benefits from attendance (3).
7. **Audit-log viewer** — surface the existing activitylog to
   super-admins; near-zero backend work.
8. **Scheduling** (rooms × groups timetable) — salles already exist;
   moderate complexity.
9. **Stock/Book inventory** — planned Domain module; independent of the
   above, schedule by business priority.
10. **Accounting improvements** (closing periods, till reconciliation
    reports) — after exports + reports mature.

Rationale: ship visible value first (1-2), then daily-ops depth (3-5),
then analysis layers (6-7), then the larger independent modules (8-10).

## Commits

| Hash | Subject |
|---|---|
| `1413a2b` | perf(bundle): route-based code splitting via lazy page resolution |
| `d5f8d9b` | perf(inertia): stop shipping the 8 KB phone-country catalog as a prop |
| `f499d86` | perf(caisses): compute only the active tab's dataset per request |
| `ce68a3c` | perf(db): index group_frais.frais_id and groups_historique.group_id |
| `79eb0e8` | security: center-scope caisse ids on money stores + throttle sensitive routes |
| `ff8f241` | fix(ux): dead footer save buttons, transform leaks, inscription edit matching |
| `010f905` | chore(quality): remove dead code, link orphaned history page, dedup student requests |
| `7ed4502` | chore(quality): dead types, history nav link, student request dedup (part 2) |
| `1becd0b` | fix(ux): busy feedback on the Inscriptions list |
| *(this doc)* | phase12: performance audit + report |

## Final verification

- `npx tsc --noEmit`: clean.
- `npm run build`: clean — entry 322.11 KB / 101.35 KB gzip.
- `php artisan route:list --path=backoffice`: 97 routes (+0 net; the
  groups-historique route existed, it's just linked now).
- Full test suite, single process: **308/308 passing, 1559 assertions**
  (+1 test vs Phase 11: the cross-center caisse rejection regression
  test).
- Playwright: 25-check smoke suite green post-code-splitting; caisses
  4-tab switch/deep-link/refresh green post-tab-gating; the 3 repaired
  modal saves verified reaching the server.
- Working tree clean; temporary profiler hook and env flag fully
  reverted before the final commits.
