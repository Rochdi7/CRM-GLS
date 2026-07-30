# Legacy Frontend (Livewire/Blade/Alpine) — Removal Plan

Status: **Not scheduled to run. This is Phase 10 only, and Phase 10 does not
begin until every module in the migration order (plan doc §5) has an Inertia
replacement that is built, tested, and confirmed in production use.**

This document exists now (Phase 0) purely so the *criteria* for safe removal
are agreed upfront — not because removal is imminent. Nothing in this file
authorizes deleting anything today.

---

## 0. Phase 3 additions to the retained-legacy list

Auth and Profile are now migrated (docs/inertia-react-migration-status.md,
Phase 3 entry). The following are **retained, unused by any route**, exactly
per this plan's rules — do not delete them yet:

| File | Why retained |
|---|---|
| `app/Livewire/Backoffice/Profile/ProfilePage.php` | No route points to it anymore (`backoffice.profile` now serves `ProfileController@show`); kept for rollback until Phase 10 |
| `resources/views/livewire/backoffice/profile/profile-page.blade.php` | Owning view of the above — removed together, never separately |
| `resources/views/backoffice/auth/login.blade.php` | No route points to it anymore (`backoffice.login` now serves `Inertia::render()`); kept for rollback |
| `resources/views/backoffice/auth/forgot-password.blade.php` | Same — `backoffice.password.request` now Inertia |
| `resources/views/backoffice/auth/reset-password.blade.php` | Same — `backoffice.password.reset` now Inertia |
| `resources/views/components/backoffice/layout/guest.blade.php` | No longer used by any of the three auth pages above (they used it before Phase 3); check for other callers before ever removing — do not assume it's fully orphaned without a fresh repo-wide grep at Phase 10 time |

None of these appear in any route file anymore — confirmed via
`artisan route:list` showing only the new controller actions for
`backoffice.login`, `backoffice.password.request`, `backoffice.password.reset`,
and `backoffice.profile`.

---

## 1. Hard precondition

Do not execute **any** step in this document until:

1. All 22 Livewire components listed in `PROJECT_INVENTORY.md` §4 have a
   merged, tested Inertia/React equivalent.
2. Every route currently pointing at a Livewire component
   (`routes/backoffice.php`) has been repointed to the Inertia-rendering
   controller and verified live.
3. The full test suite passes with the Inertia versions of every page.
4. A stakeholder (you) has explicitly signed off that no user-facing
   regression exists across desktop, mobile, dark mode, and — if still
   supported — RTL.

Until then, **Livewire, Alpine (bundled), and every current Blade view stay
exactly as they are.** Phases 1–9 are additive only: new Inertia routes/pages
are built alongside the existing ones, never replacing a route until its
replacement is proven.

---

## 2. What "removal" actually means, module by module

Removal is per-module, not a single big-bang deletion. For each of the 22
Livewire components, "safe to remove" means:

| Artifact type | Condition for removal |
|---|---|
| Livewire class (`app/Livewire/Backoffice/<Module>/*.php`) | Its route no longer references it (route now calls an Inertia-returning controller action) **and** no other Livewire component/view still nests or dispatches to it |
| Livewire view (`resources/views/livewire/backoffice/<module>/*.blade.php`) | Its owning class (above) is removed |
| Any Blade page wrapping it (`resources/views/backoffice/<module>/*.blade.php`, if any) | Same — only once nothing routes to it |
| Module-specific JS/Alpine glue (if any exists beyond the shared `app.js`) | Confirmed zero references via `grep` before deletion |

**Never remove**: `resources/views/theme-reference/preskool/` (permanent
reference copies per CLAUDE.md §3 — this rule is independent of the
Inertia migration and continues to apply). These are not "legacy frontend"
in the sense this document means; they are a permanent build-time reference
library and stay regardless of what serves production traffic.

---

## 3. Order of removal (mirrors the build order, reversed risk-first logic)

Removal should follow the **same phase order** the modules were converted
in (plan doc §5), so that the lowest-risk, most-recently-verified modules
are cleaned up first and the highest-risk finance modules' legacy code stays
available as a fallback the longest:

1. Permissions, groups-historique, other read-only/show pages
2. Simple CRUD (types-depenses, etablissements, annees-scolaires, salles, frais)
3. People (students, employees, users, roles, authorization)
4. Academic (groups, inscriptions, inscription-fees)
5. Finance (caisses, journal, encaissements, depenses, remboursements,
   transfers) — removed **last**, and only after an extended verification
   window given the financial invariants involved (till balances, two-step
   transfer validation, activity logging) — recommend the longest soak time
   here before deleting the Livewire fallback.

---

## 4. Package-level removal (only after every module above is gone)

| Package | Removal condition |
|---|---|
| `livewire/livewire` (composer) | Zero remaining `App\Livewire\*` classes referenced by any route, zero `<livewire:...>`/`@livewire` directives in any Blade view still being served |
| Bundled Alpine (comes with Livewire) | Removed automatically when Livewire is removed — verify nothing else added a standalone Alpine dependency in the interim (re-check CLAUDE.md §6's "zero results outside vendor" grep) |
| `resources/js/backoffice/app.js`'s `initializeBackofficePlugins()` Livewire-navigation hook (`livewire:navigated` listener) | Remove only the Livewire-specific event listener; the plugin-init logic itself may still be needed if any static Blade page (non-Livewire) remains |

## 5. Static asset removal (`public/assets/preskool/`)

Only remove a static PreSkool asset (CSS/JS/font/icon file) once:

1. A code search (`grep`/`Grep`) across the **entire** `resources/`,
   `app/`, and remaining `public/` tree shows zero references to that
   specific file path, **and**
2. The equivalent visual/behavioral need is fully covered by the new React
   theme's own asset (per `docs/react-theme-file-map.md`), **and**
3. A manual visual check confirms no regression (per the Quality Checks in
   CLAUDE.md §14 — theme rendering, dark mode, mobile, RTL).

Do not remove `public/assets/preskool/` wholesale — remove individual proven-
unused files/directories only, in small reviewable commits.

---

## 6. What never gets removed, regardless of migration progress

- `resources/views/theme-reference/preskool/` (252 permanent reference pages)
- Any Domain action (`EnregistrerEncaissement`, `EnregistrerDepense`,
  `EnregistrerRemboursement`, `DemanderTransfertCaisse`,
  `ValiderTransfertCaisse`, `ReferenceGenerator`)
- Any Policy, Form Request, Model, migration, or seeder
- The database schema itself (no schema changes are anticipated by this
  migration at all — it is a presentation-layer change only)
- Tests — existing Feature tests are adapted to assert against the new
  Inertia responses, never simply deleted because "the Livewire version is
  gone." Coverage must be equal or greater after each module's conversion.

---

## 7. Rollback within Phase 10 itself

Because removal happens in small per-module commits (§3), rolling back a
single over-eager removal is a single `git revert` of that module's removal
commit — it does not affect any other module's already-completed removal or
any not-yet-reached module still running on Livewire.

---

## 8. Explicit non-goals of this document

This plan does **not** authorize:

- Deleting any file today.
- Running `git clean`, `rm -rf`, or any bulk deletion command at any point —
  removal is always specific, named files, reviewed individually.
- Treating "the React page looks right" as sufficient proof of safety to
  delete the Livewire fallback — the conditions in §1 (tests, sign-off,
  cross-device/mode verification) must all be met first, every time.
