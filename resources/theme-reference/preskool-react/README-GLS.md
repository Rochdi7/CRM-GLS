# PreSkool React Theme — Reference Copy

## What this is

A permanent, read-only reference copy of the **React variant** of the
purchased PreSkool v1.9.7 admin theme, kept inside this repository so its
components/patterns can be inspected during the Inertia + React migration
(see `docs/inertia-react-migration-plan.md`) without depending on an
external download path that only exists on one machine.

| | |
|---|---|
| Original source path | `C:\Users\ASUS\Downloads\themeforest-jeUxtzLq-preskool-bootstrap-admin-html-template\preskool-v1.9.7\react` |
| Theme version | PreSkool v1.9.7 (React variant) |
| Date copied | 2026-07-30 (Phase 4 of the Inertia/React migration) |
| Purpose | Read-only design/component reference — nothing else |
| License documentation found | **None** — no `LICENSE`/license text exists anywhere in the purchased package or its parent folders (verified during Phase 4's audit). This copy is kept strictly as a **private, internal reference inside this project's own repository** — it is not redistributed, published, or shared outside this codebase. If the project is ever made public or transferred, review the original ThemeForest purchase/license terms before doing so. |

## What was copied and what was excluded

Full inventory: `docs/preskool-react-reference-inventory.md`.

**Copied**: `src/core/`, `src/feature-module/`, `src/style/` (minus one
folder — see below), `src/types/`, `src/main.tsx`, `src/environment.tsx`,
`src/vite-env.d.ts`, `src/index.scss`, plus the theme's own
`package.json`/`package-lock.json`/`tsconfig*.json`/`vite.config.ts`/
`eslint.config.js`/`index.html` (kept for dependency/config reference only,
see rules below).

**Excluded**:
- `public/` (69 MB of demo marketing images/avatars — not source code)
- `src/style/icon/tabler-icons/eps/` (4,694 Illustrator `.eps` icon-source
  files — never used at runtime by any web build; the icon *fonts*
  themselves, in the sibling `webfont/`/`fonts/` folders, were kept)
- `node_modules/`, `dist/`, `.git/` — none existed in the source to begin
  with (verified)

Net result: **596 files, ~37 MB** (down from the original ~127 MB).

## Rules for using this directory (binding — see CLAUDE.md)

1. **Never import production components directly from this folder.**
   Everything here is for reading and comparison only.
2. **Adapt, don't copy-paste.** When a component here looks useful, copy the
   *idea* (markup structure, class names, prop shape) into a fresh file
   under `resources/js/`, rewritten for Inertia (no React Router, no Redux,
   no localStorage-based auth/demo state, no mock APIs).
3. **Document every adaptation** in `docs/react-theme-file-map.md`, with the
   exact source-relative path inside this reference copy (e.g.
   `resources/theme-reference/preskool-react/src/core/common/header/index.tsx`)
   and its real destination under `resources/js/`.
4. **Never run `npm install` inside this directory.** It has its own
   `package.json`/`package-lock.json` kept only so a future agent can read
   what dependency versions the theme itself used — installing them here
   would create a second, disconnected `node_modules` tree inside the
   Laravel repo.
5. **Never use its React Router, its demo/mock auth, its fake API calls, or
   its `localStorage` demo state directly** — every one of those was
   already screened out during Phase 0–3 of this migration (see
   `docs/inertia-react-migration-audit.md`).
6. **Never load its full CSS/JS bundle.** The Inertia app's root view
   (`resources/views/app.blade.php`) loads only the project's own static
   PreSkool assets, already served from `public/assets/preskool/` — this
   reference copy's `src/style/` is for reading Sass source and class names,
   not for direct `@import`.
7. **Review a component's own dependencies before adapting it.** Several
   theme components pull in packages this project has deliberately not
   installed (Redux, Ant Design, PrimeReact, React Bootstrap, jQuery-backed
   plugins) — see the dependency classification in
   `docs/inertia-react-migration-plan.md` §2.2 before assuming a component's
   npm dependencies are available.
8. **Never edit files in this reference copy** unless documenting a
   specific, deliberate reason (e.g. a note-to-self comment) — this is a
   frozen snapshot, not a working copy. If the purchased theme is ever
   re-downloaded/updated, re-sync this folder wholesale rather than
   hand-patching it.
9. This directory is **not a Vite input, not a TypeScript project
   reference, and not scanned by any build tool** in this repository. See
   `docs/preskool-react-reference-inventory.md` for the build-isolation
   verification performed when this copy was created.

## Where to look for what

| Looking for… | Check |
|---|---|
| Header/sidebar/breadcrumb markup patterns | `src/core/common/{header,sidebar,dataTable,loader,imageWithBasePath,Taginput,theme-settings,selectoption}/` |
| Modal wiring patterns (structural reference only — every one is demo-domain) | `src/core/modals/` |
| Page-level examples by module (auth, academic, accounts/finance, HR/peoples, settings, etc.) | `src/feature-module/` |
| Redux slices (theme-settings state — reference only, this project doesn't use Redux) | `src/core/data/redux/` |
| Mock/demo data (never a real source of anything) | `src/core/data/json/` |
| SCSS source | `src/style/scss/` |
| Icon fonts actually usable at runtime | `src/style/icon/*/` (webfont/fonts/css subfolders — NOT the removed `eps/` folder) |
| Theme's own dependency versions | `package.json` at the root of this reference copy |
