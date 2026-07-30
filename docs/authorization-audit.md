# Authorization Audit — GLS CRM

Date: 2026-07-24 · Audited before implementing roles & permissions.

> Historical note: This document describes the project before the PostgreSQL-only
> migration completed on July 29, 2026 (it was audited while the app still ran on
> SQLite locally). For the current database rules, see `CLAUDE.md`
> § "Database Standard — PostgreSQL Only", `POSTGRES_AUDIT.md`, and
> `POSTGRES_MIGRATION_REPORT.md`. The authorization/permissions findings below
> remain current — only the "Environment" row about the database is outdated.

## Environment

| Item | Finding |
|---|---|
| Laravel | **13.21.1** (`laravel/framework ^13.8`) |
| PHP | **8.4.23** (`C:\php84\php.exe` — see CLAUDE.md §1) |
| Livewire | **v4.3.3** (installed, not yet used by any screen) |
| Testing | **PHPUnit 12.5** (`tests/Feature`, `tests/Unit`), `RefreshDatabase` available, tests run on **sqlite :memory:**, `CACHE_STORE=array` |
| Formatter | Laravel **Pint** (`^1.27`) |
| Database | **SQLite** local dev (MySQL targeted for production) |
| Git | **Not a git repository** → the requested pre-implementation git checkpoint is impossible; skipped and reported |

## Authentication (existing — must not be replaced)

- **Guard: single `web` session guard**, provider `users` → **authenticatable model: `App\Models\User`** (integer auto-increment IDs everywhere, no UUIDs).
- Backoffice login implemented: `backoffice.login` (GET/POST), `backoffice.logout`, rate-limited `LoginRequest`. Root `/` redirects to the login.
- Users table fields: `name`, `email`, `username` (nullable unique), `password`, `must_change_password` — the two extra columns come from the auto-credential flow.
- **Employees authenticate through `users`**: `employees.user_id` FK; creating an Employee auto-creates its User via `EmployeeObserver` → `EmployeeCredentialService`. `User::employee()` (HasOne) / `Employee::user()` (BelongsTo).
- No public registration. Local admin seeded: `admin@gls.test` linked to Employee `EMP-000001` (catégorie Directeur).

## Multi-center reality

- `employees.etablissement_id` is a **single nullable FK** — there is **no employee↔center pivot**. This is a documented deliberate simplification (gls-crm-schema.md, "Deliberate Simplifications": `employee_etablissement` M2M was explicitly dropped). An employee belongs to **at most one** center today.
- Center-bearing tables: `salles`, `employees`, `students`, `groups`, `groups_historique`, `inscriptions`, `caisses` (all via `etablissement_id`, mostly nullable).

## Routes / structure

- Modular routes already in place: `routes/backoffice.php` (`/backoffice` prefix, `backoffice.` names, 91 routes) + `routes/frontoffice.php`; `web.php` only requires them.
- 15 thin resource controllers under `App\Http\Controllers\Backoffice`, Form Requests under `App\Http\Requests\Backoffice\<Module>\…`.
- **No authorization anywhere yet** — routes are behind `auth` middleware only; controllers never call `authorize()`. `app/Policies` does not exist. Base `App\Http\Controllers\Controller` is an empty abstract class (no `AuthorizesRequests` trait).
- CRUD Blade views for resources **do not exist yet** (next phase) — only dashboard, login, frontoffice home render.

## Modules actually implemented (permission scope)

dashboard · etablissements (centers) · annees-scolaires · salles · employees · users (auto-created logins) · students · groups (+ read-only historique, archive transition) · inscriptions (+ fees) · caisses · encaissements (payments) · types-depenses · depenses · remboursements · caisse-transfers (two-step validate) · audit log (spatie/laravel-activitylog v5, `activity_log` table).

**Not implemented** (in the generic spec but absent here — no permissions will be created for them): attendance, advances*, cheques*, stock, books, recoveries, prospects, reports, settings. (*advances/cheques are deliberately folded into `encaissements` per schema v4.)

## Spatie Permission status

- **Not installed** — no `spatie/permission` in vendor, no `config/permission.php`, no permission tables in `migrate:status`, no middleware aliases in `bootstrap/app.php`. Clean slate; no duplicate-artifact risk.

## Existing seeders & data-safety notes

- `DatabaseSeeder` calls `AdminUserSeeder`, `AnneeScolaireSeeder`, `TypeDepenseSeeder` (all idempotent). The new `RolesAndPermissionsSeeder` will be added to this list and must remain runnable standalone.
- Dev database contains seeded reference data — **no `migrate:fresh`** during this task (`php artisan migrate` only).

## Conventions to preserve

- French UI (`APP_LOCALE=fr`), every visible string `__('…')` + `lang/fr.json` entry.
- PreSkool markup, anonymous Blade components (`x-backoffice.layout.app`), Livewire only where server-driven dynamics are needed (CLAUDE.md §5) — role management screens qualify.
- Money/audit invariants (CLAUDE.md §11): never delete money records, activitylog on fraud-relevant models.
