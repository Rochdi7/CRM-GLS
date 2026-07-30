# Bootstrap / React Integration Decision

Status: **Decided for Phase 2's scope (dropdowns, mobile sidebar). Modal
strategy formally revisited before the first real modal ships (Phase 6+).**

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
| Future modal state (Phase 6+) | **Not decided yet** — deferred (see below) |
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

## Revisit trigger

Before Phase 6 (first CRUD modal), re-examine this decision explicitly:
- If a modal needs complex focus-trapping/accessibility behavior beyond what
  a small hand-rolled `Modal` component provides, `react-bootstrap`'s
  `<Modal>` becomes the leading candidate — it wraps the same Bootstrap 5
  CSS already loaded, without requiring Bootstrap's own JS bundle.
- Whatever is chosen, it must be the **single** ownership model for every
  future Inertia modal — no mixing hand-rolled and `react-bootstrap` modals
  in the same page tree.
