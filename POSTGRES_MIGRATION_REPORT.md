# PostgreSQL Migration Report

GLS CRM is now PostgreSQL-only. SQLite and MySQL/MariaDB support has been removed
from configuration; no application code depended on either driver's specific
behavior, so this was a narrow, low-risk change. Full findings are in
`POSTGRES_AUDIT.md` (Phase 1) — this report covers what was actually changed and
the verification results.

## 1. Compatibility summary

The codebase was already close to driver-agnostic: no `DB::getDriverName()`
branches, no MySQL-only SQL functions (`GROUP_CONCAT`, `IFNULL`, `DATE_FORMAT`,
etc.), no `enum()` columns, no raw SQL beyond three simple/portable
`selectRaw`/`orderByRaw` calls. The real work was five targeted fixes, all applied
and verified:

1. Case-sensitive `LIKE` → `ILIKE` in every search query (Postgres regression risk).
2. Test suite repointed from SQLite `:memory:` to a real Postgres database.
3. Standalone indexes added for FK columns Postgres doesn't auto-index.
4. Config defaults (`database.php`, `queue.php`, `.env`) switched to `pgsql`.
5. Two JSON columns upgraded to `jsonb` in vendor-published migrations.

## 2. Files modified

**Environment / config**
- `.env` — `DB_CONNECTION=pgsql`, host/port/database/user/password for local
  PostgreSQL 17 (`gls_crm`).
- `.env.example` — same, password left blank as a template.
- `config/database.php` — default connection `pgsql`; removed the `sqlite`,
  `mysql`, `mariadb`, `sqlsrv` connection blocks and the now-unused
  `Pdo\Mysql` import. Only `pgsql` remains, with sensible defaults
  (`gls_crm`/`postgres`) instead of the Laravel-generic `laravel`/`root`.
- `config/queue.php` — the two `env('DB_CONNECTION', 'sqlite')` fallbacks
  (`batching.database`, `failed.database`) changed to `'pgsql'`.
- `phpunit.xml` — `DB_CONNECTION=pgsql`, `DB_DATABASE=gls_crm_test`,
  `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` added; the suite no longer
  runs against SQLite `:memory:`.
- `database/database.sqlite` — deleted (was gitignored, local-only artifact).

**Queries — `'like'` → `'ilike'` (13 files, ~35 call sites)**
- `app/Livewire/Backoffice/CaisseTransfers/CaisseTransfersIndex.php`
- `app/Livewire/Backoffice/Depenses/DepensesIndex.php`
- `app/Livewire/Backoffice/Caisses/CaissesIndex.php`
- `app/Livewire/Backoffice/Remboursements/RemboursementsIndex.php`
- `app/Livewire/Backoffice/Groups/GroupsIndex.php`
- `app/Livewire/Backoffice/Users/UsersIndex.php`
- `app/Livewire/Backoffice/Encaissements/EncaissementsIndex.php`
- `app/Livewire/Backoffice/Inscriptions/InscriptionsIndex.php`
- `app/Livewire/Backoffice/TypesDepenses/TypesDepensesIndex.php`
- `app/Livewire/Backoffice/Employees/EmployeesIndex.php`
- `app/Livewire/Backoffice/Roles/RolesIndex.php`
- `app/Livewire/Backoffice/Students/StudentsIndex.php`
- `database/seeders/DemoDataSeeder.php`

Every user-facing search box in the app (students, employees, users, roles,
groups, caisses, till transfers, encaissements, dépenses, remboursements, types
dépenses, inscriptions) now matches case-insensitively on Postgres, same as it
did on MySQL/SQLite before.

**Migrations**
- `database/migrations/2026_07_23_182743_create_activity_log_table.php` —
  `attribute_changes`/`properties` columns: `json()` → `jsonb()`.
- `database/migrations/2026_07_23_224946_create_media_table.php` —
  `manipulations`/`custom_properties`/`generated_conversions`/`responsive_images`:
  `json()` → `jsonb()`.
- `database/migrations/2026_07_29_120000_add_missing_foreign_key_indexes_for_postgres.php`
  (new) — standalone single-column indexes on 26 FK columns across `salles`,
  `employees`, `students`, `groups`, `groups_historique`, `inscriptions`,
  `inscription_fees`, `caisses`, `encaissements`, `depenses`, `remboursements`,
  `caisse_transfers`, skipping columns already covered by an existing composite
  or unique index (documented in the migration's docblock).

**Documentation**
- `CLAUDE.md` §1 — stack line changed from "MySQL target / SQLite local dev" to
  PostgreSQL-only, with the `ILIKE` and FK-index rules captured as durable
  project rules for future work.
- `README.md` — MySQL badge → PostgreSQL badge, quick-start note updated, test
  count badge refreshed to the current 293/1014.
- `docs/backoffice-architecture.md` — stack line updated.
- `POSTGRES_AUDIT.md` (new) — Phase 1 findings.

**Not changed (dated historical documents, left as point-in-time record):**
`gls-crm-schema.md`, `gls-crm-laravel-structure.md`, `docs/authorization-audit.md`
— these describe decisions/environment *as they stood* on their stated dates and
are explicitly design-history documents, not living references.

## 3. Queries rewritten

Only the `LIKE`→`ILIKE` operator changes above. No `DB::raw`/`selectRaw`/
`orderByRaw` needed rewriting — the three existing raw-SQL call sites
(`StudentsIndex.php:310`, `CaisseTransfersIndex.php:283`, `GroupsIndex.php:286`)
were already portable ANSI SQL and required no change.

## 4. Migrations updated

36 pre-existing migrations ran against Postgres **unmodified** except the two
`json`→`jsonb` column-type edits above (both in vendor-published package
migrations, safe to edit since they're project-owned copies, not touched by
future package updates). One new migration added (FK indexes, see above).
Foreign keys, decimals (`numeric(N,2)`), booleans, timestamps, and the `id()`
bigint-identity primary keys all ported with zero changes — see
`POSTGRES_AUDIT.md` §"Everything else audited and found clean" for the full
per-category breakdown of why each was already Postgres-safe.

## 5. Index improvements

- **New composite/aggregate indexes**: already present from a prior performance
  pass (`2026_07_29_090000_...`), unaffected by this migration.
- **New standalone FK indexes**: 26 columns, added in
  `2026_07_29_120000_add_missing_foreign_key_indexes_for_postgres.php`. Postgres,
  like SQLite but unlike MySQL/InnoDB, does not auto-index a plain
  `foreignId()->constrained()` column — without this migration, every
  center-scoping filter (`etablissement_id`), eager-load `WHERE IN`, and
  direct filter (`caisse_source_id`, `type_depense_id`, `student_id`, etc.)
  would have silently lost its index on the Postgres cutover.
- Columns intentionally left uncovered by a *new* index because an existing
  composite/unique index already leads with them: `encaissements.caisse_id`,
  `depenses.caisse_id`, `remboursements.caisse_id`, `inscriptions.annee_scolaire_id`
  (with `statut`), `group_frais.group_id`/`frais_id` (unique composite).

## 6. JSONB improvements

`activity_log` (`attribute_changes`, `properties`) and `media`
(`manipulations`, `custom_properties`, `generated_conversions`,
`responsive_images`) now use `jsonb` instead of `json`. Both tables are read
far more than written (audit trail lookups, media metadata reads), and `jsonb`
gives binary storage, indexable content (GIN, if a future query needs to filter
by `custom_properties` or `properties` keys), and faster containment checks —
with no downside since neither table needs to preserve exact key ordering or
whitespace from the original JSON text. No app code queries these columns by
JSON path today, so this is a forward-looking improvement, not a fix for an
existing bug.

## 7. Search improvements

`LIKE` → `ILIKE` restores the case-insensitive search behavior the app already
had on MySQL/SQLite — this is a **parity fix**, not a new feature. No
`pg_trgm`/full-text search was introduced: the audit found no evidence of slow
search-as-you-type at current data volumes, and introducing trigram indexes
would be a genuine behavior change (fuzzy matching) beyond what was asked for
("preserve existing behaviour" was explicit in the brief). If search over
students/employees/payments becomes a measured bottleneck at higher record
counts, `pg_trgm` GIN indexes on `nom`/`prenom`/`reference` are the natural next
step — noted as a recommendation (§9), not implemented.

## 8. Performance

No `EXPLAIN ANALYZE` bottlenecks were found worth documenting at current (demo)
data volumes — the seeded dataset is too small to produce a meaningful query
plan comparison. The index work in §5 is the performance-relevant change; it
specifically targets the FK-lookup and center-scoping patterns that run on
every backoffice list screen (`CenterAccessService`, `caisseFilter`,
`typeFilter` in the Livewire index components).

## 9. Tests executed

```
php artisan migrate:fresh   → 36/36 migrations, 0 errors
php artisan db:seed         → 8/8 seeders, 0 errors (includes DemoFinanceSeeder,
                               which exercises every money-mutation Domain action:
                               EnregistrerEncaissement, EnregistrerDepense,
                               EnregistrerRemboursement, DemanderTransfertCaisse,
                               ValiderTransfertCaisse)
php artisan test             → 293 passed, 1014 assertions, 0 failures
npm run build                → succeeded, 567ms
php artisan route:list       → all routes resolve correctly
```

Manual smoke check (server started on :8123): `backoffice/login` → HTTP 200
with all PreSkool theme CSS/JS asset paths resolving (`assets/crm-gls/...`,
no 404s); `/` → HTTP 302 (expected admin-first redirect).

All 15 verification areas from the task brief are covered by the above: the
test suite exercises Students, Employees, Groups, Inscriptions, Encaissements,
Dépenses, Remboursements, Caisse, Transfers, Dashboard, Settings, Roles,
Permissions, Media Library, and Authentication/Authorization — all passing
against live PostgreSQL. Center scoping is covered by the `Context` test suite;
financial rules (till balance integrity, two-step transfer validation,
compensating-entry-only corrections) are covered by the Finance/Payments/
Expenses feature tests, all green.

## 10. Remaining recommendations

- **`pg_trgm` full-text/fuzzy search** — not implemented (see §7); revisit if
  search latency becomes measurable at production data volumes.
- **Partial indexes** — e.g. `WHERE deleted_at IS NULL`-style partial indexes
  aren't applicable (no soft-deletes in this schema), but a partial index on
  `caisse_transfers` where `validated_by IS NULL` (pending transfers) could help
  the till-transfer validation queue if that list grows large — not added
  since current volumes don't justify it.
- **`sslmode`** — `config/database.php` already defaults to `DB_SSLMODE=prefer`;
  set `DB_SSLMODE=require` (or `verify-full` with a CA cert) in the production
  `.env` once the VPS Postgres instance is reachable over TLS.
- **Connection pooling** — for production, consider PgBouncer in front of
  Postgres if concurrent connection count becomes a concern; not needed at
  current single-instance scale.
- **`EXPLAIN ANALYZE` pass at real data volume** — worth re-running the index
  review once production-scale data exists; current recommendations are based
  on query *shape* (from code), not measured plans.

## Deployment instructions

**Local (already done in this session, documented for reproducibility):**

```powershell
Set-Location "C:\Users\ASUS\Desktop\Projects\crm gls"

# Create databases (once)
& "C:\Program Files\PostgreSQL\17\bin\psql" -U postgres -h 127.0.0.1 -c "CREATE DATABASE gls_crm;"
& "C:\Program Files\PostgreSQL\17\bin\psql" -U postgres -h 127.0.0.1 -c "CREATE DATABASE gls_crm_test;"

# .env already updated to DB_CONNECTION=pgsql, DB_DATABASE=gls_crm
C:\php84\php.exe artisan migrate --seed
C:\php84\php.exe artisan test
npm run build
```

**VPS / production:**

1. Install PostgreSQL 16+ (17 recommended, matches local dev) and create the
   production database + a dedicated app role (not `postgres` superuser):
   ```sql
   CREATE ROLE gls_crm_app LOGIN PASSWORD '<strong-generated-password>';
   CREATE DATABASE gls_crm OWNER gls_crm_app;
   ```
2. Set production `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=<vps-postgres-host>
   DB_PORT=5432
   DB_DATABASE=gls_crm
   DB_USERNAME=gls_crm_app
   DB_PASSWORD=<strong-generated-password>
   DB_SSLMODE=require
   ```
3. Run `php artisan migrate --force` (never `migrate:fresh` on production —
   that drops all tables).
4. Per `gls-crm-schema.md`'s security note (still valid, engine-independent):
   consider a separate, more restricted DB role for the audit-log table if true
   tamper-evidence is required — Postgres supports column/table-level
   `REVOKE UPDATE, DELETE ON activity_log FROM gls_crm_app` the same way MySQL
   does.
5. `php artisan storage:link`, `npm run build`, then serve via your normal
   process manager (queue worker included — `QUEUE_CONNECTION=database` now
   resolves against `pgsql`).
6. Verify with `php artisan test` against a **staging** Postgres instance
   before the first production deploy, and `php artisan route:list` /
   a manual login smoke test after.
