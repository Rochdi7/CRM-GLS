# PreSkool React Theme — Reference Copy Inventory

Created: 2026-07-30 (Phase 4 of the Inertia/React migration).
Destination: `resources/theme-reference/preskool-react/`.
Original source: `C:\Users\ASUS\Downloads\themeforest-jeUxtzLq-preskool-bootstrap-admin-html-template\preskool-v1.9.7\react`.

---

## 1. Folders copied

| Source (relative to theme `react/`) | Destination | Notes |
|---|---|---|
| `src/core/` | `src/core/` | Full copy — header/sidebar/dataTable/modals/theme-settings/redux/data reference |
| `src/feature-module/` | `src/feature-module/` | Full copy — every demo page (auth, academic, accounts, hrm, peoples, settings, router, etc.) |
| `src/style/` | `src/style/` | Copied **except** `src/style/icon/tabler-icons/eps/` (see exclusions) |
| `src/types/` | `src/types/` | TypeScript interface reference |
| `src/main.tsx`, `src/environment.tsx`, `src/vite-env.d.ts`, `src/index.scss` | same paths | Entry-point/config reference only — never executed here |
| `package.json`, `package-lock.json` | same names | Dependency-version reference only — `npm install` never run here |
| `tsconfig.json`, `tsconfig.app.json`, `tsconfig.node.json` | same names | Config reference only — not part of this repo's TS project |
| `vite.config.ts`, `eslint.config.js`, `index.html` | same names | Config/entry reference only — not a Vite input in this repo |
| `README.md` | `README-original.md` | Preserved as-is (renamed to avoid colliding with the new `README-GLS.md`) |

## 2. Folders/files excluded

| Excluded | Size | Reason |
|---|---|---|
| `public/` (entire directory, effectively `public/assets/img/`) | ~69 MB | Demo marketing images / fake student-staff avatars / screenshots — not source code, not needed to adapt components |
| `src/style/icon/tabler-icons/eps/` | ~46 MB, 4,694 files | Illustrator vector *design source* for the icon set — never consumed at runtime by any web build (confirmed during the Phase 0 theme audit); the actual usable icon **fonts** for this same icon family were kept (`src/style/icon/tabler-icons/{fonts,webfont}/`) |
| `node_modules/`, `dist/`, `build/`, `.git/`, `coverage/` | 0 (none existed) | Verified absent in the original download — nothing to exclude, confirmed present-check below |
| `.env*` files | 0 (none existed) | Verified absent — no secrets found anywhere in the package |

## 3. File count and total size

| | Original theme (`react/`) | Reference copy |
|---|---|---|
| Total size | ~127 MB | **~37 MB** |
| Total files | 5,281 (per Phase 0 audit) | **596** |

## 4. Largest copied files

| File | Size |
|---|---|
| `src/style/icon/tabler-icons/webfont/fonts/tabler-icons.ttf` | 2.1 MB |
| `src/style/icon/tabler-icons/webfont/fonts/tabler-icons.eot` | 2.1 MB |
| `src/style/icon/tabler-icons/fonts/tabler-icons.ttf` | 2.1 MB |
| `src/style/icon/tabler-icons/fonts/tabler-icons.eot` | 2.1 MB |
| `src/style/icon/fontawesome/js/all.js` | 1.6 MB |
| `src/style/icon/fontawesome/js/all.min.js` | 1.5 MB |
| `src/style/icon/boxicons/boxicons/fonts/boxicons.svg` | 1.2 MB |
| `src/style/icon/tabler-icons/webfont/tabler-icons.html` (icon-reference demo page) | 1.1 MB |
| `src/style/icon/tabler-icons/{webfont,}/fonts/tabler-icons.woff` | 1.1 MB each |
| `src/style/css/style.css` | 704 KB |

All of the above are legitimate icon-font/demo-reference assets, not
accidental oversized files — no cleanup needed.

## 5. Dependency manifest summary

`package.json` (`name: "template-new"`, `version: "0.0.0"` — confirms this
is an unmodified template export, per the Phase 0 finding) lists ~46
dependencies. Full per-package classification (required/optional/demo-only/
replace/remove) already exists in `docs/inertia-react-migration-plan.md`
§2.2 — that classification remains authoritative; this reference copy does
not change which packages this project actually installs.

## 6. Useful component categories

| Category | Location |
|---|---|
| Layout shell (header, sidebar, breadcrumb patterns) | `src/core/common/{header,sidebar}/` |
| Reusable presentational components | `src/core/common/{loader,imageWithBasePath,Taginput,selectoption,theme-settings}/` |
| Modal wiring patterns (structural only, all demo-domain) | `src/core/modals/` |
| Page-level examples by module | `src/feature-module/{auth,academic,accounts,hrm,peoples,settings,management,membership,content,announcements,application,report,support,uiInterface,userManagement,mainMenu,pages}/` |
| SCSS source | `src/style/scss/` |
| TypeScript interface conventions | `src/types/`, `src/core/data/interface/` |

## 7. Risky / demo-only areas (do not adapt directly)

- `src/feature-module/router/` — full React Router route table for the
  demo app; replaced entirely by Laravel routing + Inertia page resolution
- `src/core/data/json/` — ~150+ mock-data files, never a real data source
- `src/core/data/redux/` — Redux store/slices; this project uses no Redux
- `src/feature-module/auth/{register,emailVerification,twoStepVerification,lockScreen}/`
  — flows GLS CRM doesn't have and isn't planning (no public registration,
  no email verification, no 2FA)
- Any page under `src/feature-module/` outside academic/accounts/peoples/hrm
  — multi-vertical demo content (blog, deals, campaigns, FAQ, support
  tickets) with no GLS equivalent

## 8. Reference-directory verification (performed 2026-07-30)

| Check | Result |
|---|---|
| `node_modules/` exists in the copy | ❌ No (never existed in source either) |
| `dist/`/`build/` exists in the copy | ❌ No |
| `.env`/secrets copied | ❌ No |
| Nested `.git/` exists | ❌ No |
| Unexpectedly huge/binary files | ❌ No — largest file is a legitimate 2.1 MB icon-font `.ttf` |
| `npx tsc --noEmit` type-checks the reference copy | ❌ No — `tsconfig.json`'s `include` is `["resources/js"]`, does not reach `resources/theme-reference/` |
| `npm run build` bundles/transforms reference files | ❌ No — production bundle is byte-identical to the pre-copy baseline (`app-CNxd8NcD.js`, 340.92 kB / 104.85 kB gzip, unchanged) |
| Existing npm scripts execute inside the reference copy | ❌ No — `npm run dev`/`build` run from the project root only, `vite.config.js` has no input pointing into `resources/theme-reference/` |
| `git status` shows only intended reference files | ✅ Confirmed — see commit for this phase |
| License documentation found in the purchased package | ❌ None found (README.md is the generic Vite/React boilerplate template README, not a license) — see `README-GLS.md`'s license note for the handling decision |

## 9. Instructions for future agents

1. **Read `README-GLS.md` first** — it has the full usage rules (never
   import directly, never `npm install` here, adapt-and-document only).
2. **Use `docs/react-theme-file-map.md`** to check whether a given theme
   component has already been adapted (and where) before starting fresh.
3. **When adapting a new component**, add a row to
   `docs/react-theme-file-map.md` with the exact
   `resources/theme-reference/preskool-react/src/...` source path.
4. **Never re-run the copy script wholesale** without re-checking this
   inventory's exclusion list still makes sense (e.g. if the theme is ever
   updated to a newer version).
5. **If disk space or repo size ever becomes a concern**, the icon-font
   files listed in §4 are the safest candidates to trim further (e.g. keep
   only `woff2` and drop legacy `eot`/`ttf` — those still exist for the
   *actual* production assets in `public/assets/preskool/`, this reference
   copy's fonts are read-only comparison material, not something the build
   depends on).
