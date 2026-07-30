# Inertia + React Migration — Status Log

Running log of verified milestones. Append one entry per phase; do not rewrite history.

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

### Routes still served by Blade/Livewire (unchanged)
Every POST auth action (`login.store`, `password.email`, `password.update`,
`logout`) was already a plain controller action, not Blade — no change
needed there. All Dashboard/Settings/Students/Employees/Groups/Inscriptions/
Finance/Users/Roles modules remain Livewire, untouched.

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
requests like `/assets/preskool/css/bootstrap.min.css` — through Laravel's
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
