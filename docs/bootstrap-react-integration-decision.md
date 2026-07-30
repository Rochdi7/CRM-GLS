# Bootstrap / React Integration Decision

Status: **Decided for Phase 2's scope (dropdowns, mobile sidebar). Modal
strategy formally revisited and decided in Phase 6 — see §"Phase 6 modal
decision" below.**

---

## Theme audit findings

- The PreSkool React theme (`react/src/main.tsx`) imports Bootstrap's CSS
  **and its full JS bundle** (`node_modules/bootstrap/dist/js/bootstrap.bundle.min.js`)
  directly, then relies on `data-bs-toggle`/`data-bs-dismiss` attributes for
  dropdowns/modals/offcanvas — i.e. **Bootstrap's own DOM-scanning JS owns
  interactive state**, the same model the current Blade/Livewire pages use.
- The theme also lists `react-bootstrap ^2.10.10` as a dependency, but a
  scan of `react/src/core/common/` and `react/src/core/modals/` shows no
  actual `react-bootstrap` component imports in the files inspected so far —
  it appears to be present in `package.json` but not the primary pattern
  used by the shell/layout components audited (header, sidebar, modals).
  Those all use raw `data-bs-toggle`/manual Bootstrap attributes on native
  elements instead.
- `jquery` is a theme dependency, but not used by the header/sidebar
  components themselves (jQuery shows up as a dependency for other
  demo widgets outside Phase 2's scope, not audited in depth here).

## Decision

**Package used**: none — no `bootstrap` npm package, no `react-bootstrap`
installed for the React entry. The static PreSkool Bootstrap CSS
(`public/assets/preskool/css/bootstrap.min.css`) is loaded once, from
`resources/views/app.blade.php`, exactly as the Blade/Livewire shell already
does. **Its Bootstrap JS bundle is deliberately NOT loaded on Inertia
pages** — `app.blade.php` does not include
`assets/preskool/js/bootstrap.bundle.min.js` or jQuery. React owns
interactive state instead (see below), so Bootstrap's JS has nothing to
scan for on this root view.

| Concern | Owner (Phase 2) |
|---|---|
| Dropdown state (user menu) | React (`useState` in `Header.tsx`, click-outside + Escape via `useEffect`) |
| Collapse state | Not used in Phase 2 — no collapsible sections shipped yet |
| Offcanvas / mobile sidebar state | React (`useState` in `BackofficeLayout.tsx`, toggled via `Header`'s mobile-menu button, closed on navigation/Escape/overlay-click) |
| Future modal state (Phase 6+) | **Decided in Phase 6** — hand-rolled `Modal.tsx`, no `react-bootstrap` (see "Phase 6 modal decision" below); reused as-is by Phase 7's Employees/Users add-edit modals |
| `bootstrap.bundle.js` imported on Inertia pages? | **No** |
| jQuery used on Inertia pages? | **No** |
| Duplicate-init prevention | N/A this phase — no Bootstrap JS runs on Inertia pages at all, so there is nothing to double-initialize. Existing Blade/Livewire pages keep loading the static Bootstrap JS bundle exactly as before (unchanged, still needed there) |

## Why not `react-bootstrap` yet

Phase 2's interactive surface is small (one dropdown, one mobile toggle) —
plain React state + CSS classes reproduces the theme's visual behavior
without a new dependency. Introducing `react-bootstrap` now, before a real
modal is needed, would be exactly the kind of "install a candidate
dependency you have not confirmed you need" the migration plan warns
against (plan doc §2.2).

## What happens for the same CSS classes on Blade/Livewire pages

Nothing changes there. Those pages continue to load
`assets/preskool/js/bootstrap.bundle.min.js` + jQuery exactly as before —
this decision only governs the Inertia/React root (`app.blade.php`) and its
component tree. The two stacks stay isolated (migration plan's "keep each
frontend root isolated" rule).

## Revisit trigger (resolved)

Before Phase 6 (first CRUD modal), re-examine this decision explicitly:
- If a modal needs complex focus-trapping/accessibility behavior beyond what
  a small hand-rolled `Modal` component provides, `react-bootstrap`'s
  `<Modal>` becomes the leading candidate — it wraps the same Bootstrap 5
  CSS already loaded, without requiring Bootstrap's own JS bundle.
- Whatever is chosen, it must be the **single** ownership model for every
  future Inertia modal — no mixing hand-rolled and `react-bootstrap` modals
  in the same page tree.

## Phase 6 modal decision

**Package used**: still none. `resources/js/Components/Modals/Modal.tsx` is a
small hand-rolled component — role="dialog", aria-modal="true", an
aria-labelledby'd title, a manual focus trap (Tab/Shift+Tab cycling within
the dialog's own focusable elements, computed fresh on every open), focus
restore to the triggering element on close, Escape-to-close (disabled while
`processing`), backdrop-click-to-close (also disabled while `processing`,
so a destructive request in flight cannot be dismissed mid-air), and a
body-scroll lock (`document.body.style.overflow`) for the duration it's
open. `ConfirmDialog.tsx` (delete confirmations) is built on top of it, not
a separate implementation.

**Why not `react-bootstrap` after all**: the focus-trap/ARIA requirements
turned out to be straightforward to hand-roll correctly (no nested-modal
scenarios, no complex composition needs) — introducing a ~30 KB dependency
for behavior that fits in under 150 lines wasn't justified. If a future
phase needs nested modals, nested focus traps, or nested transitions,
revisit this again; `Modal.tsx`'s single-modal-at-a-time assumption would
need to change or be replaced.

**Visuals**: the theme's own Bootstrap classes (`.modal`, `.modal-dialog`,
`.modal-dialog-centered`, `.modal-content`, `.modal-header`, `.modal-body`,
`.modal-footer`, `.modal-backdrop`) are reused as-is, matching the existing
Alpine-driven Livewire modals' markup exactly (`resources/views/livewire/
backoffice/settings/*.blade.php`) — only the interactivity model changed
(`x-data="{ show: @entangle(...) }"` → React `useState`).

**Row-action dropdowns** (`resources/js/Components/Tables/RowActions.tsx`)
follow the same pattern established in Phase 2's `Header.tsx` user-menu
dropdown: `useState` + click-outside/Escape listeners, no
`data-bs-toggle="dropdown"`.
