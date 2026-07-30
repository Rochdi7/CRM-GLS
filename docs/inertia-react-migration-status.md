# Inertia + React Migration — Status Log

Running log of verified milestones. Append one entry per phase; do not rewrite history.

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
