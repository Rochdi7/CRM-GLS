# PostgreSQL Documentation Update Report

Follow-up to `POSTGRES_AUDIT.md` and `POSTGRES_MIGRATION_REPORT.md` (the code
migration itself). This pass covers documentation only — no application code
was changed, since no documentation statement was found to contradict the
already-migrated, already-verified codebase.

## 1. Markdown files scanned

All 20 project-owned Markdown files (excluding `vendor/`, `node_modules/`):

```
CLAUDE.md
PERFORMANCE_AUDIT.md
PERFORMANCE_OPTIMIZATION_REPORT.md
POSTGRES_AUDIT.md
POSTGRES_MIGRATION_REPORT.md
README.md
docs/authorization-architecture.md
docs/authorization-audit.md
docs/backoffice-architecture.md
docs/center-scoping.md
docs/roles-and-permissions.md
gls-crm-laravel-structure.md
gls-crm-schema.md
resources/views/theme-reference/preskool/README.md
skills/CLAUDE-MD-SUGGESTIONS.md
skills/README.md
skills/architecture-reviewer/SKILL.md
skills/caveman.md
skills/code-review/SKILL.md
skills/database-designer/SKILL.md
skills/laravel-feature-generator/SKILL.md
skills/livewire-component-builder/SKILL.md
skills/preskool-theme-converter/SKILL.md
```

Case-insensitive search for `SQLite|MySQL|MariaDB|SQL Server|sqlsrv|:memory:`
matched 9 files. The other 11 (including `docs/authorization-architecture.md`,
`docs/center-scoping.md`, `docs/roles-and-permissions.md`, all skill files
except `architecture-reviewer`, and the theme-reference README) had no
database-engine mentions at all — read and confirmed clean, no edits needed.

## 2. Active files updated

| File | Change |
|---|---|
| `CLAUDE.md` | §1 stack line trimmed to a pointer at the new §17. Added full **§17 "Database Standard — PostgreSQL Only"** section: compatibility rules, search (`ILIKE`) rules with correct/incorrect code examples, FK index rules, JSON/JSONB rules, migration rules (never edit a production-applied migration, `migrate --force` only), query rules (no MySQL-only functions), money rules (fixed-precision decimals), date-query rules (sargable ranges), test rules (`gls_crm_test` isolation), environment defaults for local and production, production security (dedicated non-superuser role, localhost-only Postgres), performance rules (cites the real, documented `CaisseJournal` PHP-merge bottleneck), and a PostgreSQL-extensions section (`pg_trgm`/`unaccent`/`pgcrypto` — documented as future options, **not installed**, not added to any migration). |
| `README.md` | Added a **Requirements** section (PHP 8.4+, PostgreSQL 16+, Composer, Node/npm), a **required PHP extensions** check (`pdo_pgsql`, `pgsql`) with both Windows and Linux verification commands, a **local database** section creating a dedicated `gls_crm_app` role (not the `postgres` superuser) plus a separate `gls_crm_test_app`/`gls_crm_test` for the test database, and rewrote **Quick start** into Requirements → Local database → Setup. Added a pointer to `CLAUDE.md` §17. Fixed the stale test-count badge (289/1006 → 293/1014) and the `php artisan test` comment in §Tests, and added the "never point PHPUnit at `gls_crm`" warning there. No SQLite or MySQL setup instructions remain anywhere in the file. |
| `docs/backoffice-architecture.md` | Stack line: "MySQL target (SQLite in local dev)" → "PostgreSQL (local dev and production)". *(Done in the prior session as part of the code migration; re-verified clean in this pass.)* |
| `skills/architecture-reviewer/SKILL.md` | Stack list: "MySQL or PostgreSQL" / "Pest or PHPUnit" → "PostgreSQL (only supported database engine — see `CLAUDE.md` § Database Standard)" / "PHPUnit" (this project uses PHPUnit only; "Pest or" was inaccurate regardless of the DB question, corrected in the same edit since it was on the same line). The rest of this generic review skill (its "Database checks" section) was already engine-agnostic — no further edits needed. |

## 3. Historical files preserved

Original content kept **verbatim** in all four; only a notice block was
added, per the task's explicit instruction not to silently rewrite history:

| File | Why historical |
|---|---|
| `docs/authorization-audit.md` | Dated point-in-time audit (2026-07-24), predates the Postgres migration (2026-07-29); its "Environment" table row correctly recorded SQLite as the state *at that time*. |
| `gls-crm-schema.md` | Original design-decision document — explicitly states "Laravel 11 + MySQL," which was already stale relative to the actual build (Laravel 13) even before the Postgres migration. Rationale prose (`LIKE '%keyword%'` / `FULLTEXT`, the MySQL audit-log permission note) is preserved as-written; it's design reasoning, not current instructions. |
| `gls-crm-laravel-structure.md` | Companion build-plan document to the above, same Laravel-11-era origin. |
| `PERFORMANCE_AUDIT.md` | SQLite-era benchmark numbers (measured 2026-07-29, before the migration). Numbers preserved exactly; a notice clarifies they need re-measurement on Postgres. |
| `PERFORMANCE_OPTIMIZATION_REPORT.md` | Same — SQLite-era before/after timing comparison, preserved with the same notice pattern. |

## 4. Notices added to historical files

All four notices follow the task's suggested template, adapted per-file:

- `docs/authorization-audit.md` — links to `CLAUDE.md` §17, `POSTGRES_AUDIT.md`, `POSTGRES_MIGRATION_REPORT.md`; clarifies only the "Environment" DB row is outdated, the authorization findings remain current.
- `gls-crm-schema.md` — clarifies the *table design and invariants* are still the approved schema; only the engine name in the stack line is outdated.
- `gls-crm-laravel-structure.md` — clarifies MySQL mentions (the audit-log permission note) are historical design rationale, not current setup instructions.
- `PERFORMANCE_AUDIT.md` — exact wording from the task brief, plus an added clarification that the SQLite-vs-MySQL FK auto-index discussion in §3.x is superseded by `CLAUDE.md` §17 (PostgreSQL behaves like SQLite here, not MySQL) and that the index additions were carried forward into the Postgres migration.
- `PERFORMANCE_OPTIMIZATION_REPORT.md` — same template.

## 5. PostgreSQL rules added to CLAUDE.md

New §17 covers, in full: database compatibility (no SQLite/MySQL/MariaDB, no
driver-conditional branches), search (`ILIKE` mandatory, with a
correct/incorrect code pair), FK indexing (Postgres doesn't auto-index FKs),
JSON/JSONB defaults, migration rules (never edit a production-applied
migration; `migrate --force` in production, never `migrate:fresh`), query
rules (no MySQL-only SQL functions), money rules (fixed-precision decimals,
no floats), date-query rules (sargable ranges), test isolation
(`gls_crm_test`, never `gls_crm`), environment templates for local (current
actual values: `postgres`/`postgres`/`gls_crm`) and production (templated,
`gls_crm_app`, no real secret), production security (dedicated role,
localhost-only Postgres, no public 5432), performance rules (references the
real `CaisseJournal` bottleneck already documented in the performance
reports), and a PostgreSQL-extensions section documenting `pg_trgm`,
`unaccent`, `pgcrypto` as future-only options — explicitly not installed and
not added to any migration in this pass.

## 6. README setup changes

Full **Requirements → local database → Setup** flow, matching the task's
required structure: PHP/Postgres/Composer/Node prerequisites, extension
verification commands for both Windows and Linux, dedicated-role SQL for both
the dev and test databases (`gls_crm_app`/`gls_crm`,
`gls_crm_test_app`/`gls_crm_test`), and a setup command block that adds
`npm run build` + `php artisan test` before `serve`/`dev` (previously the
README's quick-start skipped straight to `serve` without verifying the build
or test suite). No SQLite or MySQL instructions remain.

## 7. Testing documentation changes

- README §Tests: test count corrected to the current 293/1014, added explicit
  "tests run against `gls_crm_test`, never point PHPUnit at `gls_crm`"
  warning.
- CLAUDE.md §17 "Test rules": same warning as a durable project rule, plus the
  `RefreshDatabase`/destructive-seeder constraint.

No committed file contains a real password — `.env`/`.env.example` (not
Markdown, out of this pass's scope) were already handled in the code
migration; all Markdown examples in this pass use `<strong-local-password>`,
`<strong-test-password>`, or `<secure-password>` placeholders.

## 8. Deployment documentation changes

No separate deployment guide exists outside `POSTGRES_MIGRATION_REPORT.md`
(already written in the prior session, already PostgreSQL-only, already
includes the Ubuntu `postgresql`/`php8.4-pgsql` package install, dedicated-role
creation, and the `migrate --force` / never-`migrate:fresh`-in-production
rule). Verified it needed no changes — re-read in full during this pass, no
MySQL deployment commands present.

## 9. Remaining SQLite/MySQL mentions and why each is valid

Full list of every remaining case-insensitive match, reviewed line-by-line:

- **`CLAUDE.md`** (6 mentions) — all explicit "do not use SQLite/MySQL"
  prohibition rules or comparative explanations of *why* Postgres needs
  different handling (e.g. "unlike MySQL's default collation"). Exactly the
  allowed "explicit do-not-use rules" category.
- **`docs/authorization-audit.md`** (3 mentions) — the historical notice
  itself, plus the original dated Environment-table row it's annotating.
  Allowed: historical record with notice.
- **`gls-crm-laravel-structure.md`** (2 mentions) — the historical notice,
  plus the original MySQL audit-log permission rationale. Allowed: historical
  design record with notice.
- **`gls-crm-schema.md`** (5 mentions) — the historical notice, plus four
  original design-rationale passages (stack line ×2, `LIKE`/`FULLTEXT`
  simplicity note, MySQL audit-log permission note, closing stack summary).
  Allowed: historical design record with notice; content preserved verbatim
  per instruction.
- **`PERFORMANCE_AUDIT.md`** (14 mentions) — the historical notice, plus
  original SQLite-baseline measurements and SQLite-vs-MySQL FK-index
  discussion throughout §2–3 and the closing summary. Allowed: historical
  benchmark record with notice, per the task's explicit `PERFORMANCE_AUDIT.md`
  example.
- **`PERFORMANCE_OPTIMIZATION_REPORT.md`** (5 mentions) — the historical
  notice, plus original SQLite-baseline before/after timing text. Allowed:
  same as above.
- **`POSTGRES_AUDIT.md`** (17 mentions) — this *is* the migration-report
  document explaining what was removed/changed; every mention is in the
  "what SQLite/MySQL behavior did we find and fix" category. Allowed by
  definition — this is the audit record itself.
- **`POSTGRES_MIGRATION_REPORT.md`** (13 mentions) — same category, the
  migration report explaining what was removed.
- **`README.md`** (1 mention) — "PostgreSQL is the **only** supported
  database — no SQLite, no MySQL" — an explicit current-state statement in the
  allowed "explicit do-not-use" category.

**No disallowed mentions found** — no current setup instructions reference
SQLite or MySQL, no test configuration defaults to SQLite, no deployment
guide recommends MySQL, and no database-neutral text contradicts the
PostgreSQL-only rule anywhere in the scanned set.

## 10. Commands executed

```powershell
Get-ChildItem -Recurse -Filter *.md   # (via find, POSIX-equivalent in this session)
grep -riln "sqlite|mysql|mariadb|sqlsrv|:memory:" <all .md files>
C:\php84\php.exe artisan about
C:\php84\php.exe artisan migrate:status
C:\php84\php.exe artisan tinker --execute="dump(DB::connection()->getDriverName(), DB::connection()->getDatabaseName());"
C:\php84\php.exe artisan test
npm run build
```

## 11. Verification results

```text
artisan about        → Database: pgsql
tinker driver/db      → "pgsql" / "gls_crm"
artisan migrate:status → all 36 migrations "Ran", including the two Postgres-
                          migration additions from the prior session
artisan test          → 293 passed, 1014 assertions, 0 failures
                          (phpunit.xml confirmed pointed at gls_crm_test,
                          not gls_crm, before this run)
npm run build          → succeeded, 637ms
```

No destructive command was run against `gls_crm` — `migrate:status` and
`about`/`tinker` are read-only; `artisan test` used the isolated
`gls_crm_test` database per `phpunit.xml`.

## 12. Contradictions found and resolved

One minor inaccuracy, unrelated to the database question but caught while
editing the same line: `skills/architecture-reviewer/SKILL.md` stated the
project uses "Pest or PHPUnit" — the project only uses PHPUnit (confirmed via
`composer.json` / `phpunit.xml`, no `pestphp/pest` dependency). Corrected in
the same edit as the database-engine fix on that line. No other
documentation-vs-reality contradictions were found; the codebase-level
PostgreSQL migration (prior session) had already been fully verified before
this documentation pass began, so this pass only needed to bring the
*documentation* in line with the *already-correct* code and config.
