# Phase 11J — Manual Browser Checklist

**Status: SMOKE-VERIFIED via headless Chromium (Playwright); visual/theme
items remain manual.** A real-browser smoke pass was executed against
`artisan serve` + the production build (2026-07-31, final Phase 11
wrap-up):

- **Super-admin walk (25/25 checks passing, 0 console errors, 0 failed
  requests):** login → dashboard redirect; all 13 module pages load
  (Dashboard, Students, Inscriptions, Groups, Employees, Users, Settings,
  Roles, Permissions, Caisses, Encaissements, Dépenses, Types de
  dépenses) with hydrated React content and no error text; all 3
  newly-exposed Finance sidebar items visible; sidebar SPA navigation
  confirmed (in-page JS state survives a nav — no full reload); Students
  debounced search reaches the server and renders the empty-result state;
  Students create modal opens, closes on Escape, and reopens; direct
  refresh on a deep page re-hydrates; browser back/forward restores the
  right pages; the legacy `/backoffice/caisse-transfers` URL lands on
  `/backoffice/caisses?tab=transferts`.
- **Limited-role walk (teacher account, 7/7 checks passing):** login
  works; sidebar hides Roles and the entire Finance group; sidebar shows
  Groups; direct navigation to `/backoffice/roles` and
  `/backoffice/caisses` returns real 403s; Groups page loads normally.
  (The only console entries were the browser logging those two
  deliberately-triggered 403 responses — expected denial behavior.)

**Still genuinely manual (not smoke-covered):** dark-mode rendering, RTL
(Arabic) layout, mobile-width sidebar behavior, and pixel-level visual
regressions — a headless functional pass does not judge visuals. The
checklist below remains the reference for that human pass; the functional
rows it contains are now additionally backed by the automated smoke run
described above.

## How to run this checklist

```powershell
Set-Location "C:\Users\ASUS\Desktop\Projects\crm gls"
C:\php84\php.exe artisan serve
npm run dev   # separate terminal, or `npm run build` + serve the built assets
```

Sign in at `/backoffice/login` with the local seeded admin
(`admin@gls.test` / `password`), then walk every module below in:

1. **Desktop** width (sidebar expanded)
2. **Mobile** width (collapsed/off-canvas sidebar menu)
3. **Dark mode** (header theme toggle)
4. **RTL** (switch locale to Arabic, confirm `bootstrap.rtl.min.css` layout)

## Modules to verify

For each module: list loads, search/filter/pagination works, create modal
opens and submits, edit modal opens pre-filled and submits, delete/guard
behavior matches the documented invariants (money records never deletable,
groups never deletable, etc.), and no console errors appear.

- [ ] **Dashboard** — stat cards render, context switcher (year/center) updates them live
- [ ] **Students** — list, filters (niveau/center), create/edit modal incl. photo upload, detail page
- [ ] **Inscriptions** — list, create modal (student+group select, fee lines), detail page (fee lines + payment summary)
- [ ] **Groups** — list, tab counts per status, create/edit modal, detail page, archive action (`archiverCommeTermine`), groups-historique
- [ ] **Employees** — list, create/edit modal incl. photo upload, one-time credentials display after creation
- [ ] **Users** — list (edit-only modal: name/email/username, `is_active` toggle, password regeneration)
- [ ] **User authorization** — role assignment page, permission checkboxes, save
- [ ] **Settings — Établissements tab** — CRUD, delete guard when center has activity
- [ ] **Settings — Années scolaires tab** — CRUD, delete guard when year has activity, `par_defaut` toggle
- [ ] **Settings — Salles tab** — CRUD, center-scoped list
- [ ] **Settings — Frais tab** — CRUD catalog, default amount
- [ ] **Roles** — list, create/edit, permission matrix, super-admin protection (cannot rename/delete/lose-last-admin)
- [ ] **Permissions** — read-only list page
- [ ] **Caisses** — "Ma caisse"/"Toutes les caisses" tabs, self-heal missing till, journal pagination
- [ ] **Caisse transfers** — request (two-step), validation by a different employee, self-validation refused
- [ ] **Encaissements** — list, create (multi-row), no destroy route
- [ ] **Dépenses** — list, create incl. justificatifs upload, no destroy route
- [ ] **Types de dépenses** — CRUD, `is_system` types locked (no edit/delete on seeded types)
- [ ] **Remboursements** — list, create, no destroy route
- [ ] **Profile** — own-info edit, password change

## Cross-cutting checks

- [ ] Top-bar context switcher (year + center) persists across navigation and correctly locks non-`centers.access-all` users to their own center
- [ ] Every modal: Escape closes it, backdrop click closes it, focus trap/restore works, body scroll locks while open (per `resources/js/Components/Modals/Modal.tsx`)
- [ ] No Bootstrap modal JS / jQuery modal init / `data-bs-toggle`/`data-bs-dismiss` / `wire:*` attributes fire anywhere (should be structurally impossible now — Livewire and its Blade views are deleted — but worth a console/network-tab spot check)
- [ ] No 404s in the Network tab for `build/…`, `assets/crm-gls/…`, or `/media/…` asset paths
- [ ] No duplicate-Select2/Alpine console errors (moot now since Alpine/Select2 no longer ship on backoffice pages at all — confirm no leftover references trigger errors)
- [ ] French is the default displayed language across every page visited

## Why this is deferred, not skipped

Claiming this checklist was executed without an actual browser would be a
false verification — the project's own quality-check rules require
affected pages to "render (serve + open, or HTTP-request the route)" and
explicitly forbid claiming UI success without testing the golden path in a
browser. Every module above already has HTTP-level Inertia test coverage
(asserting page component names, props, validation errors, and side
effects — see `docs/phase-11-test-coverage-mapping.md`), which is real and
already verified. What remains here is purely the visual/interactive layer
that only a rendered browser can confirm.
