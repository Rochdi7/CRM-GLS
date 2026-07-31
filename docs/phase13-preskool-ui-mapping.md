# Phase 13 — Current React UI vs PreSkool: Gap Map & Plan

Companion to `docs/phase13-preskool-ui-audit.md` (the theme's exact
patterns). Verdict up front: the migrated shell is already structurally
faithful (`main-wrapper`/`header`/`sidebar`/`page-wrapper`/`content`,
page-title flex block, breadcrumbs, `badge-soft-*`, kebab RowActions,
`modal-dialog-centered` + `custom-btn-close`, `form-label`/`form-control`)
— the gaps are concentrated in the card/toolbar composition, a few header
controls, badge/confirm-dialog styling, and cross-page inconsistency.

## Confirmed gaps (theme → current)

| # | Area | Theme | Current | Fix |
|---|---|---|---|---|
| G1 | Card toolbar | Filters/tools INSIDE `card-header` right side (each `mb-3 me-2`); `card-body p-0 py-3`; table edge-to-edge | Filters in a padded `gls-filter-bar` row inside card-body | New card-header tools composition + `Card` body-class support |
| G2 | Filter control | "Filter" dropdown panel (white-outline trigger, form rows, Reset/Apply) | Always-visible inline selects | New `FilterDropdown` component (same Inertia reload on Apply — behavior preserved, presentation batched) |
| G3 | Per-page + search row | "Row Per Page [select] Entries" left + search right, in one row above the table | Per-page at the bottom on 5 pages only, two label texts, two widths; search at top-left | New `TableLengthRow` used by every list page |
| G4 | Status badges | Dot badges: `badge badge-soft-* d-inline-flex align-items-center` + `i.ti.ti-circle-filled.fs-5.me-1` | Same soft classes but no dot; half the pages inline the class string instead of `StatusBadge` | Add dot to `StatusBadge`; use the component everywhere |
| G5 | Delete confirm | Centered icon modal (`delete-icon` + `ti-trash-x`, h4, p, centered Cancel/`btn-danger`) | Generic header/footer modal | Restyle `ConfirmDialog` to the theme pattern |
| G6 | Header right | Icon buttons `btn btn-outline-light bg-white btn-icon`; year pill `ti-calendar-due`; avatar w/ image; dark-mode toggle; desktop collapse `#toggle_btn` (`ti-menu-deep` → `body.mini-sidebar`) | Avatar hardcodes a theme demo JPG that 404s (renders as a grey square); no dark-mode toggle; no desktop collapse | Initials avatar; functional dark-mode toggle (`data-theme` + localStorage); mini-sidebar toggle |
| G7 | Add-button placement | Page-header right, `btn btn-primary d-flex align-items-center` + `ti-square-rounded-plus me-2` | 4 different placements (layout actions / card tools / toolbar actions / toolbar child) and 2 icons | Standardize: page-level add in page-header; per-tab add styled identically |
| G8 | Table head | `table.datatable` + `thead.thead-light`, 12px/20px cells | `table.table.table-hover` + `thead.thead-light` (close; sort arrows only on Âge) | Keep ours (no fake sort arrows on non-sortable columns — the theme's come from DataTables JS we don't use) |
| G9 | Pagination | `ul.pagination` right, info "X - Y of Z items" left | Same layout, French text, ‹/› controls | Keep (already matches layout); no change |

## Cross-page inconsistencies to erase (found in our own pages)

- Add-button icon/placement variants (G7).
- Per-page selector: present on 5 pages with 2 different labels/widths (G3).
- `Inscriptions` missing `showJumpToPage`; finance pages hand-roll edit
  `<li>` items instead of `RowActionItem`; Caisses journal uses custom
  Précédent/Suivant buttons instead of shared `Pagination` (kept — it is a
  JSON sub-fetch, not an Inertia page load, but restyled consistently).
- `StatusBadge` component vs inline `badge badge-soft-*` strings (G4).
- `FormField` lacks the required-`*` marker that Select/Textarea render
  (standardized: all field components support it; the theme itself shows
  no asterisks, ours are an accessibility+clarity addition kept on all).

## Deliberate differences kept (documented, not bugs)

- **EmptyState**: the theme has no no-data pattern at all; ours stays.
- **Sidebar school-picker card**: redundant with our header
  ContextSwitcher (year + center) — not duplicated.
- **Header search / notifications / flags / fullscreen**: decorative in
  the demo (no backend behind them here) — per phase rules, decorative
  controls with no real function are not displayed.
- **Checkbox select-all column**: no bulk actions exist in the app — a
  non-functional checkbox column would be decoration.
- **DataTables sort arrows on every column**: only real sortable columns
  (Students' Âge) show sort affordances.
- **Required asterisks**: theme shows none; we keep ours (a11y win).
- **French UI**: all labels through `t()` as before.

## Per-page adaptation plan

| Page | Work |
|---|---|
| Students, Employees, Users, Inscriptions, Groups | Convert to standard composition: card-header tools (FilterDropdown when filters exist), TableLengthRow, dot StatusBadge, standardized add button |
| Roles | Move "Nouveau rôle" to page-header actions; standard card/table |
| TypesDepenses | Add button → page-header; TableLengthRow; dot badges |
| Encaissements, Depenses (tabs) | Per-tab card gets standard card-header tools + TableLengthRow; fix hand-rolled edit items → RowActionItem |
| Caisses (tabs) | Same for comptes/transferts tabs; journal keeps its JSON sub-fetch pagination but restyled |
| Settings panels | Standard panel header (title + add btn styled as theme) |
| GroupsHistorique, Permissions | Read-only: dot badges + spacing only |
| Users/Authorization, Profile | Custom layouts — badges/buttons pass only |
| Dashboard | Already theme-derived (banner + stat cards) — no change |
