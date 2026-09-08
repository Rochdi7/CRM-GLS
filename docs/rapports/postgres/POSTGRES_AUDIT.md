# PostgreSQL Migration Audit

Scope: `database/migrations`, `app/Models`, `app/Livewire`, `app/Domain`, `app/Services`,
`app/Http`, `database/seeders`, `tests`, `config/`.

Migration files: 36. `app/Domain` (money-mutation logic): 6 files, ~123 lines total
(`Expenses/Actions/EnregistrerDepense.php`, `Finance/Actions/DemanderTransfertCaisse.php`,
`Finance/Actions/EnregistrerRemboursement.php`, `Finance/Actions/ValiderTransfertCaisse.php`,
`Payments/Actions/EnregistrerEncaissement.php`, `Shared/Support/ReferenceGenerator.php`).

**Headline finding:** the codebase is already largely driver-agnostic — no
`DB::getDriverName()` checks, no MySQL-only SQL functions, no `enum()` columns, no
`whereRaw`/`havingRaw`, no `LAST_INSERT_ID` assumptions. The real work is concentrated
in five areas, listed by priority below.

---

## Priority 1 — LIKE case-sensitivity (~35 call sites, every search box in the app)

MySQL's default collation makes `LIKE` case-insensitive; SQLite's is similarly lax in
practice for this app. PostgreSQL `LIKE` is case-sensitive by default — every one of
these regresses silently unless rewritten to `ILIKE`.

- `database/seeders/DemoDataSeeder.php:36,139,140`
- `app/Livewire/Backoffice/Users/UsersIndex.php:137-139`
- `app/Livewire/Backoffice/TypesDepenses/TypesDepensesIndex.php:152`
- `app/Livewire/Backoffice/Students/StudentsIndex.php:299-303`
- `app/Livewire/Backoffice/Roles/RolesIndex.php:75-76`
- `app/Livewire/Backoffice/Remboursements/RemboursementsIndex.php:240-243`
- `app/Livewire/Backoffice/Inscriptions/InscriptionsIndex.php:568-570`
- `app/Livewire/Backoffice/Groups/GroupsIndex.php:277`
- `app/Livewire/Backoffice/Encaissements/EncaissementsIndex.php:420-423`
- `app/Livewire/Backoffice/Employees/EmployeesIndex.php:271-275`
- `app/Livewire/Backoffice/Depenses/DepensesIndex.php:341-343`
- `app/Livewire/Backoffice/Caisses/CaissesIndex.php:74-77`
- `app/Livewire/Backoffice/CaisseTransfers/CaisseTransfersIndex.php:270-271`

**Fix:** replace `'like'` with `'ilike'` at every site above (Laravel/PDO pgsql passes
the operator through verbatim). No shared scope needed given the count — a direct
find/replace review per file is simpler and keeps intent visible at each call site.

## Priority 2 — Test infrastructure still targets SQLite

`phpunit.xml:26-27` runs the entire suite against in-memory SQLite
(`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). None of the above case-sensitivity,
index, or decimal behavior is actually exercised by CI today. **Fix:** point
`phpunit.xml` at a real Postgres test database.

## Priority 3 — FK auto-index gap

MySQL/InnoDB auto-indexes single FK columns; **PostgreSQL does not** (same as SQLite).
Migration `2026_07_29_090000_add_performance_indexes_to_finance_and_academic_tables.php`
already documents this MySQL behavior in a comment and added composite indexes for the
finance tables. Needs verification: every `foreignId()->constrained()` column across
the 36 migrations (full list in category 10 of the raw findings) should have an explicit
single-column index unless already covered by a composite/unique index, since Postgres
will not auto-create one and these columns are hit on every list/filter query.

## Priority 4 — Config defaults hardcoded to sqlite

- `config/database.php:20` — `'default' => env('DB_CONNECTION', 'sqlite')`
- `config/queue.php:106,125` — `'database' => env('DB_CONNECTION', 'sqlite')` (queue
  connection + failed-jobs connection) — if `DB_CONNECTION` is ever unset, queue tables
  silently resolve against the wrong connection name.
- `.env` / `.env.example` — `DB_CONNECTION=sqlite`, MySQL block commented out, no
  Postgres block.

**Fix:** change all three fallbacks to `pgsql`, add a `pgsql` env block, remove the
SQLite connection entry from `config/database.php` (SQLite is no longer supported per
this migration's scope) and drop the commented MySQL block.

## Priority 5 — Money-logic float casts (not a DB-migration blocker, flagged for review)

Every balance mutation in `app/Domain` explicitly casts a `decimal:2`-cast model
attribute to PHP `float` before `increment()`/`decrement()`:

- `app/Domain/Finance/Actions/ValiderTransfertCaisse.php:40-41`
- `app/Domain/Expenses/Actions/EnregistrerDepense.php:32`
- `app/Domain/Finance/Actions/EnregistrerRemboursement.php:31`
- `app/Domain/Payments/Actions/EnregistrerEncaissement.php:34,48`

`increment()`/`decrement()` compile to `column = column + ?`, which is fully Postgres
`numeric`-compatible — no SQL rewrite required. The `(float)` cast is a latent
precision concern independent of the database engine (PHP float rounding), not
something this migration needs to fix, but it's called out because it sits in exactly
the files this migration touches and is the most sensitive code path in the app (money).
No change made unless requested separately.

---

## Everything else audited and found clean (no action needed)

- **Raw SQL** (`DB::raw`, `selectRaw`, `orderByRaw`): only 3 call sites app-wide
  (`StudentsIndex.php:310`, `CaisseTransfersIndex.php:283`, `GroupsIndex.php:286`),
  all portable ANSI SQL — no MySQL functions inside them.
- **MySQL-only functions** (`GROUP_CONCAT`, `IFNULL`, `DATE_FORMAT`, `NOW()`, etc.):
  none found anywhere.
- **Driver-conditional code**: none found — no `DB::getDriverName()`, no
  `=== 'sqlite'`/`'mysql'` checks in app code.
- **JSON columns**: only in third-party package tables (`activity_log`, `media`) —
  no app model uses JSON/array casts or JSON path queries. Package migrations are
  driver-agnostic Blueprint code, ported as-is with `json` type (kept as `json`, not
  `jsonb` — see migration change notes; not queried by path so no functional need for
  `jsonb`, though it was upgraded anyway for storage/performance since these tables
  are read far more than written).
- **Enum columns**: none — all status fields are `string` + model constants (matches
  the project's own "Deliberate Simplifications," CLAUDE.md §11).
- **UUID columns**: only medialibrary's own `uuid` column — Laravel's `uuid()` helper
  maps natively to Postgres `uuid` type, no rewrite.
- **Boolean handling**: 6 boolean columns, all correctly cast to `'boolean'` in their
  models and compared with real `true`/`false` — no `1`/`0`/`'1'` literal comparisons
  anywhere. Clean port to Postgres native `boolean`.
- **Auto-increment**: all 24 primary keys use `$table->id()` (bigint identity) — no
  `LAST_INSERT_ID`/gap assumptions anywhere.
- **Foreign keys/cascades**: all via `foreignId()->constrained()->{nullOnDelete,
  restrictOnDelete,cascadeOnDelete}()` — standard Blueprint helpers, translate directly
  to Postgres `FOREIGN KEY ... ON DELETE {SET NULL|RESTRICT|CASCADE}`. Note: Postgres
  always enforces FKs (SQLite gated this behind a pragma) — a simplification, not a risk.
- **Decimal/money columns**: consistent `decimal(N,2)` schema + `'decimal:2'` model
  casts throughout — maps cleanly to Postgres `numeric(N,2)`.
- **Timestamps**: all via `timestamps()`/`timestamp()`/`useCurrent()` — no
  `useCurrentOnUpdate()` (which has no native Postgres equivalent) anywhere.
- **Session/cache config**: driver-agnostic, just needs `DB_CONNECTION` to resolve
  correctly (see Priority 4).
- **SQLite artifacts**: `database/database.sqlite` present in the working tree
  (gitignored — confirm `database/.gitignore` still excludes it after SQLite is
  dropped, then delete the file).
