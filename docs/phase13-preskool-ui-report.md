# Phase 13 — PreSkool UI Parity Report

## 1. Executive summary

The React backoffice now reproduces the PreSkool v1.9.7 theme's actual UI
patterns — extracted from the theme reference files themselves, not
approximated. The audit found the migrated shell already structurally
faithful; the real gaps were the card/toolbar composition, the filter
presentation, a few header controls, badge/confirm-dialog styling, and
cross-page inconsistency. All were closed: every CRUD list page now uses
one standard composition (theme card header with a Filter dropdown panel,
"Lignes par page [n] entrées" + search row, edge-to-edge table, dot status
badges, standardized add buttons, themed delete confirmation), and the
shell gained the theme's dark mode and collapsible mini-sidebar. All
behavior (search/filter semantics, Inertia partial reloads, permissions,
center scoping, money rules, tests) is unchanged — verified by the full
PHP suite and a Playwright walk.

## 2. Theme reference files inspected

`resources/views/theme-reference/preskool/**` (Blade copies — untouched,
verified via `git status`), plus the purchase archive's static-HTML build
extracted to a scratch directory as rendering ground truth (identical
markup; screenshots of `students.html`, `fees-type.html` + its modal, and
`index.html` were compared side-by-side against our pages). Files read
line-by-line: `students.html`, `fees-type.html`, `add-student.html`,
`index.html`, `layouts/partials/{header,sidebar}.blade.php`. Full pattern
extraction: `docs/phase13-preskool-ui-audit.md`; gap map:
`docs/phase13-preskool-ui-mapping.md`.

## 3. Shared components created/updated

- **NEW `Tables/FilterDropdown.tsx`** — the theme's filter panel
  (white-outline trigger, labeled select/date fields, Reset/Apply footer,
  active-count badge). Values draft locally; Apply fires the page's own
  Inertia reload — server-side filtering, query-string state, and
  back/forward behavior unchanged (browser-verified).
- **NEW `Tables/TableLengthRow.tsx`** — "Lignes par page [n] entrées" +
  right-aligned search, the row the theme's DataTables generates; per-page
  trio optional for pages whose backend has no perPage filter.
- **`Details/StatusBadge.tsx`** — theme dot badges
  (`badge-soft-* d-inline-flex` + `ti-circle-filled`), more variants.
- **`Modals/ConfirmDialog.tsx`** — rebuilt as the theme's headerless
  centered delete modal (`delete-icon` + `ti-trash-x`, centered
  Cancel/"Oui, supprimer"); `Modal.tsx` gained `hideHeader` (dialog stays
  aria-labeled).
- **`Shared/Card.tsx`** — `bodyClassName` pass-through for the theme's
  edge-to-edge table bodies (`p-0 py-3`); tools row spacing matches the
  theme's per-tool `mb-3 me-2` convention.

## 4. Layout / header / sidebar changes

- Header: functional **dark-mode toggle** (`html[data-theme]`, theme CSS
  ships the dark rules; persisted in localStorage) and the theme's
  **desktop collapse toggle** (`#toggle_btn` → `body.mini-sidebar`,
  persisted). Both styled as theme header tools.
- Sidebar: theme **hover-expand** while collapsed (`body.expand-menu`).
- Everything else already matched (verified against the reference markup):
  `main-wrapper`/`header-left`/`mobile_btn`/`header-user` structure,
  `submenu-hdr` sections, active states, mobile overlay.

## 5. Index / table / search / filter changes

All list pages standardized on the flagship (Students) composition:
filters moved from always-visible inline selects into the theme's Filter
dropdown panel; search + per-page moved into the theme's length row; card
bodies flush (`p-0 py-3`); the four competing add-button placements
reduced to one (page-header via layout `actions`, per-tab on tabbed
pages) with the theme's `ti-square-rounded-plus` icon; hand-rolled
finance dropdown items replaced with `RowActionItem`.

## 6. Modal / form-grid changes

Modals already matched the theme (`modal-dialog-centered`,
`custom-btn-close`, `h4.modal-title`, Cancel-then-primary footers,
`row/col-md-*` grids) — confirmed against `fees-type.html`'s rendered
modal and left alone except the delete confirmation, which now uses the
theme's centered icon pattern app-wide via the shared `ConfirmDialog`.

## 7. Pages updated

Students, Employees, Users, Inscriptions, Groups, TypesDepenses,
Encaissements, Dépenses (both tabs), Caisses (comptes + transferts tabs),
Roles — plus every page implicitly via the shared shell/badge/dialog
components.

## 8. Pages deliberately unchanged and why

- **Dashboard** — already theme-derived (welcome banner + stat cards
  match `index.html`).
- **Caisses journal tabs** (ma-caisse/journal) — custom JSON sub-fetch
  with its own pagination; restyling risked functional regression for no
  structural gain; visually consistent already.
- **Settings panels** — already conformant (correct add-button markup; no
  filters/search to convert).
- **GroupsHistorique / Permissions** — read-only; their badges are
  levels/counts, not statuses (a dot would misread), no badges at all on
  Permissions.
- **Users/Authorization, Profile** — custom non-list layouts already
  using theme cards/forms.

## 9. Accessibility improvements

Kept while matching the theme: aria-labels on the new icon-only toggles
(dark mode, collapse), `aria-pressed` states, labeled filter fields
(`htmlFor`/id), the headerless delete modal keeps an accessible dialog
name via `aria-label`, decorative icons `aria-hidden`. Pre-existing a11y
(focus trap/restore, `role=status` spinners, `aria-invalid` fields,
required markers) untouched — note the theme itself shows no required
asterisks; ours are kept deliberately.

## 10. Responsive verification

Playwright at 1440/1280/1024/768/390/360 px on the converted Students
page: zero horizontal page overflow at every width; mobile sidebar
overlay opens at 390 px (screenshot captured). Toolbar tools wrap with
the theme's own `mb-3 me-2` spacing.

## 11. Playwright results

Super-admin walk: **27/27** — login; all 15 pages render hydrated with no
error text; Employees modal open/Escape-close/reopen; filter panel opens;
six responsive widths clean; mobile sidebar opens. Limited-role walk
(real seeded teacher account): **6/6** — login, sidebar scoping (Groupes
visible; Utilisateurs/Paiements hidden), Groups page loads, `/users`
returns a real 403. Filter apply/reset verified end-to-end (URL gains and
loses the filter param; row count changes). Zero unexpected console/page
errors, zero failed requests.

## 12–14. TypeScript / build / PHP tests

`npx tsc --noEmit` clean · `npm run build` clean (chunked bundle
unchanged from Phase 12) · full PHP suite **308/308 passing**
(1 559 assertions).

## 15. Screenshots produced

Scratch (session temp, not committed, per repo conventions):
theme ground truth (`theme-students`, `theme-modal`, `theme-dashboard`)
vs ours (`ours-students-v2`, `final-modal`, `final-students-1440`,
`final-mobile-390`, `final-mobile-sidebar`, `ours-dark`, `ours-mini`,
`ours-filterpanel`, `ours-confirm`).

## 16. Known visual differences still remaining

- **Dark-mode logo**: the CSS swap works (`.dark-logo` shown, others
  hidden — verified), but `gls-blanc.webp` is not actually a
  light-on-dark variant; supply a white logo file to complete dark mode.
- Theme demo extras not reproduced on purpose: global header search,
  notifications, flags, fullscreen (decorative here — no backend), the
  select-all checkbox column (no bulk actions), DataTables sort arrows on
  non-sortable columns, the sidebar school-picker card (redundant with
  the header context switcher), Export/print header tools (no feature
  behind them yet). EmptyState is our addition (theme has none).
- Info text stays French ("Affichage de X à Y sur Z résultats") rather
  than the theme's English "X - Y of Z items" format.

## 17. Rollback instructions

Each commit is independent and revertable with `git revert <hash>` in
reverse order; no migrations, no schema, no route, no dependency changes
were made in this phase. Reverting `7684971` alone restores the previous
header/sidebar/dialog behavior; reverting the page commits restores the
old toolbar layout (they only touch `.tsx` composition).

## 18. Phase 13 commit hashes

| Commit | Content |
|---|---|
| `f2c754c` | audit PreSkool reference and map React UI |
| `7684971` | align app shell and shared components with PreSkool |
| `b056d63` | Students index on the PreSkool card/toolbar pattern |
| `9148b89` | align people and academic pages with PreSkool |
| `a35515c` | align finance and roles pages with PreSkool |
| (this doc) | docs(phase13): complete PreSkool UI parity report |
