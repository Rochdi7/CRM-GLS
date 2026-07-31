# Phase 13 — PreSkool Theme Reference UI Audit

Source of truth inspected (read-only):
- `resources/views/theme-reference/preskool/**` (Blade copies, 252 views)
- The theme's own static-HTML build (extracted from the original purchase
  archive to a scratch directory for rendering/screenshot ground truth —
  byte-for-byte the same markup as the Blade copies; only asset paths differ).

Key pages read line-by-line: `students.html` (canonical list page),
`fees-type.html` (list + modal CRUD — closest analog to our modules),
`add-student.html` (multi-column form), `index.html` (dashboard),
plus `layouts/partials/{header,sidebar}.blade.php`.

## Extracted theme patterns (exact classes)

### 1. Page header
No `.page-header` wrapper — a flex utility row:
`div.d-md-flex.d-block.align-items-center.justify-content-between.mb-3`
→ left `div.my-auto.mb-2` with `h3.page-title.mb-1` + `nav > ol.breadcrumb.mb-0`
(`li.breadcrumb-item`, last `.active[aria-current=page]`);
→ right `div.d-flex.my-xl-auto.right-content.align-items-center.flex-wrap`,
each tool in `div.pe-1.mb-2`/`div.mb-2`. Primary action:
`a.btn.btn-primary.d-flex.align-items-center > i.ti.ti-square-rounded-plus.me-2`
(modal-opening pages use `ti-square-rounded-plus-filled`). Neutral tools:
`btn btn-outline-light bg-white btn-icon`.

### 2. Content card
`div.card > div.card-header.d-flex.align-items-center.justify-content-between.flex-wrap.pb-0`
with `h4.mb-3` title + right-aligned tools (each `mb-3 me-2`, last `mb-3`) —
**all filter tools live inside card-header**. Then
`div.card-body.p-0.py-3` so the table runs edge-to-edge inside
`div.custom-datatable-filter.table-responsive`.

### 3. List table
`table.table.datatable > thead.thead-light`. Cells:
- id/reference: `a.link-primary`
- avatar+name: `div.d-flex.align-items-center > a.avatar.avatar-md > img.img-fluid.rounded-circle`
  + `div.ms-2 > p.text-dark.mb-0 > a`
- status: `span.badge.badge-soft-success.d-inline-flex.align-items-center >
  i.ti.ti-circle-filled.fs-5.me-1` + text (soft set:
  `badge-soft-{primary,secondary,success,info,warning,danger,dark,…}`)
- actions: `div.dropdown > a.btn.btn-white.btn-icon.btn-sm.d-flex.align-items-center.justify-content-center.rounded-circle.p-0 > i.ti.ti-dots-vertical.fs-14`
  → `ul.dropdown-menu.dropdown-menu-right.p-3 > li > a.dropdown-item.rounded-1`
  with `ti-edit-circle` (edit), `ti-trash-x` (delete), `ti-menu` (view).

### 4. Search & filter toolbar
The theme's card-header tools row contains (left→right): date-range input
(`div.input-icon-start.mb-3.me-2.position-relative` + `i.ti.ti-calendar`),
**Filter dropdown** (`a.btn.btn-outline-light.bg-white.dropdown-toggle`
[data-bs-auto-close=outside] → `div.dropdown-menu.drop-width` containing a
form: title row `d-flex align-items-center border-bottom p-3 > h4`, body
`p-3 pb-0 border-bottom > .row > .col-md-6 > .mb-3` (form-label + select),
footer `p-3 d-flex align-items-center justify-content-end` with
`a.btn.btn-light.me-3` Reset + `button.btn.btn-primary` Apply), view toggle,
sort dropdown. The **search input and per-page control are
DataTables-generated**, not hand-written: "Row Per Page [select ~61px]
Entries" left + search right, in a row above the table; pagination
`ul.pagination > li.page-item > a.page-link` right-aligned with
"X - Y of Z items" info text left.

### 5. Modal
`div.modal.fade > div.modal-dialog.modal-dialog-centered[.modal-lg]` →
`.modal-content > .modal-header` (`h4.modal-title` +
`button.btn-close.custom-btn-close > i.ti.ti-x`) → `<form>` wrapping
`.modal-body` (grid `row > col-md-*` of `div.mb-3 > label.form-label +
input.form-control`) and `.modal-footer` (**Cancel first**:
`btn btn-light me-2`, then `button.btn.btn-primary`).
Delete confirmation: no header/footer — `.modal-body.text-center` with
`span.delete-icon > i.ti.ti-trash-x`, `h4`, `p`, then
`div.d-flex.justify-content-center` with `btn btn-light me-3` Cancel +
`btn btn-danger` "Yes, Delete".

### 6. Form grid (full pages)
Card-per-section: `div.card > div.card-header.bg-light` with
`span.bg-white.avatar.avatar-sm.me-2.text-gray-7` icon + `h4.text-dark`
title; body `card-body pb-1` with `row.row-cols-xxl-5.row-cols-md-6` /
cells `col-xxl.col-xl-3.col-md-6`. Modals use plain `col-md-6`/`col-md-12`.
No required-asterisk markers anywhere in the demo. Footer:
`div.text-end > btn btn-light me-3 + btn btn-primary`.

### 7. Header (top bar)
`div.header > div.header-left.active` (logo anchors + **desktop collapse
toggle `a#toggle_btn > i.ti.ti-menu-deep`** → `body.mini-sidebar`) +
`a#mobile_btn.mobile_btn > span.bar-icon > span×3` + `div.header-user >
div.nav.user-menu` (search block `me-auto`, right side icon buttons each
`btn btn-outline-light bg-white btn-icon me-1` in `div.pe-1`; academic-year
pill `btn btn-outline-light fw-normal bg-white d-flex align-items-center
p-2` + `ti ti-calendar-due me-1`; dark-mode toggles
`#dark-mode-toggle`/`#light-mode-toggle` with `ti-moon`/`ti-brightness-up`;
avatar dropdown `span.avatar.avatar-md.rounded > img` with identity block +
`dropdown-item d-inline-flex align-items-center p-2` items).

### 8. Sidebar
`div.sidebar#sidebar > div.sidebar-inner.slimscroll > div#sidebar-menu.sidebar-menu`
→ `ul > li > h6.submenu-hdr > span` (section) + `ul > li[.active] > a >
i.ti.* + span`. Parents with children: `li.submenu > a.subdrop.active` +
`span.menu-arrow`. Collapsed mode = `body.mini-sidebar`. Sidebar top:
school-picker card (`a.d-flex.align-items-center.border.bg-white.rounded.p-2.mb-4`).

### 9. Icons & buttons
Tabler (`ti ti-*`) everywhere; FontAwesome only in header mobile bits +
DataTables sort arrows. Buttons: `btn-primary` (with leading icon `me-2`),
`btn-light` (cancel/neutral), `btn-outline-light bg-white` (tools),
`btn-white btn-icon btn-sm` (row kebab), `btn-danger` (confirm delete).

### 10. Empty state
**The theme has none** (no no-data pattern in all 262 demo pages) — our
EmptyState component is an addition, kept deliberately.

See `docs/phase13-preskool-ui-mapping.md` for the current-React-vs-theme
gap list and the per-page adaptation plan.
