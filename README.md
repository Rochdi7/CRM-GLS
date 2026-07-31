<div align="center">

# 🎓 GLS CRM

### School-management CRM for **GLS Sprachen Zentrum** — Global Language School

*Centers · Students · Enrollments · Groups & Fees · Payments · Expenses · Till Management · Roles & Permissions*

<br>

![Laravel](https://img.shields.io/badge/Laravel_13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP_8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Inertia](https://img.shields.io/badge/Inertia.js_3-9553E9?style=for-the-badge&logo=inertia&logoColor=white)
![React](https://img.shields.io/badge/React_19-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![TypeScript](https://img.shields.io/badge/TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap_5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white)

![Tests](https://img.shields.io/badge/tests-307_passed_·_1531_assertions-brightgreen?style=flat-square)
![Permissions](https://img.shields.io/badge/permissions-65_across_6_roles-blue?style=flat-square)
![Language](https://img.shields.io/badge/UI-Français_🇫🇷-informational?style=flat-square)

</div>

---

## ✨ What is this?

A complete **staff backoffice** for a multi-center German-language school in Morocco: from the first phone call to the last dirham — student records, enrollments with per-group fee schedules, cash collection, expense tracking and a fraud-resistant till system — all in a French UI on the PreSkool admin theme.

Built as a **modular monolith**: Laravel 13 API + an Inertia.js/React/TypeScript backoffice, business rules isolated in a Domain layer, and a strict Backoffice / Frontoffice separation (the public student portal is a future phase).

## 🧩 Modules

| | Module | Highlights |
|---|---|---|
| 👨‍🎓 | **Étudiants** | Photo upload, CEFR levels **+ German tracks** (Arbeit / Studium / Ausbildung with conditional Domaine / STK–DSH fields), CIN, parent/guardian block |
| 📝 | **Inscriptions** | Inline new-student creation, fee lines with % or DH discounts in one transaction, scoped to the active academic year |
| 👥 | **Groupes** | Catalog fees assigned per group (own amount + due date), archived — **never deleted** — with a history snapshot |
| 🧑‍🏫 | **Employés** | Login auto-provisioned on creation (one-time credentials), photo & address, 10 job categories |
| 💰 | **Paiements** | Fee-line settlement (full / partial), 4 payment methods, cheque details, printable receipt |
| 🧾 | **Gestion des dépenses** | Tabbed page: expenses (invoice ref, payment method, group link, live till balance) + refunds + expense types |
| 🏦 | **Gestion de la caisse** | Tabbed page: *Ma caisse*, unified transactions journal, **two-step till transfers** (requester ≠ validator), till accounts |
| 🔐 | **Rôles & permissions** | 65 `module.action` permissions, 6 seeded roles, center-scoped data access, super-admin safety rails |
| 🏢 | **Multi-centres** | Top-bar context switcher (academic year + center) — every screen follows it live |
| ⚙️ | **Paramètres** | Centers, academic years, rooms and the fee catalog in one tabbed page |

## 🛡️ Money invariants (the interesting part)

The finance layer is designed so that **fraud leaves a trail and mistakes can't be silent**:

- 💸 Every money movement runs through a **Domain action in one DB transaction** — `caisses.solde` is application-maintained, never touched by raw updates
- 🚫 Money records are **never deleted or re-amounted** — corrections are compensating entries; there are no destroy routes to begin with
- 🤝 Till transfers are **two-step**: request (balances untouched) → validation by a *different* employee, with before/after balance snapshots
- 🔢 All references (`ETU-`, `INS-`, `ENC-`, `DEP-`, `TRF-`…) are **system-generated**, never typed
- 📜 Fraud-relevant models carry a **full audit log** (spatie/activitylog)

```mermaid
flowchart LR
    A[Route<br><small>permission middleware</small>] --> B[Controller<br><small>authorize + validate, every mutation</small>]
    B --> C[Domain action<br><small>one transaction</small>]
    C --> D[(caisses.solde<br>+ audit log)]
```

## 🚀 Quick start

### Requirements

```text
PHP 8.4+
PostgreSQL 16+ (17 used in development)
Composer
Node.js and npm
```

**Required PHP extensions:** `pdo_pgsql`, `pgsql`. Verify they're enabled:

```powershell
# Windows
C:\php84\php.exe -m | findstr /I "pgsql pdo_pgsql"
```

```bash
# Linux / macOS
php -m | grep -E 'pgsql|pdo_pgsql'
```

### Local database

PostgreSQL is the **only** supported database — no SQLite, no MySQL. Create a
dedicated application role rather than using the `postgres` superuser:

```sql
CREATE ROLE gls_crm_app WITH LOGIN PASSWORD '<strong-local-password>';
CREATE DATABASE gls_crm OWNER gls_crm_app;

-- separate database for the test suite — never point tests at gls_crm
CREATE ROLE gls_crm_test_app WITH LOGIN PASSWORD '<strong-test-password>';
CREATE DATABASE gls_crm_test OWNER gls_crm_test_app;
```

### Setup

```bash
git clone https://github.com/Rochdi7/CRM-GLS.git && cd CRM-GLS
composer install && npm install
cp .env.example .env               # set DB_USERNAME/DB_PASSWORD to gls_crm_app
php artisan key:generate
php artisan migrate --seed        # full demo dataset, idempotent
php artisan storage:link
npm run build
php artisan test
php artisan serve                 # http://127.0.0.1:8000 → backoffice login
npm run dev                       # Vite watch (our JS/SCSS only)
```

> See `CLAUDE.md` § "Database Standard — PostgreSQL Only" for the full set of
> PostgreSQL rules (search must use `ILIKE`, FK columns need explicit indexes,
> `.env` conventions, etc.) and `POSTGRES_MIGRATION_REPORT.md` for deployment
> instructions.

### 🔑 Demo accounts (local only — password: `password`)

| Login | Role | Sees |
|---|---|---|
| `admin@gls.test` | Super-admin | everything, bypasses all gates |
| `directeur@gls.test` | Directeur | all centers, validates transfers |
| `operations@gls.test` | Dir. des opérations | all centers, finance read-only |
| `assistante@gls.test` | Assistante admin. | **one center**, records money, can't validate |
| `enseignant@gls.test` | Enseignant | groups & students only |
| `marketing@gls.test` | Resp. marketing | read-only funnel data |

The seeders also create 7 GLS branches with rooms, a fee catalog, students, groups, enrollments and real finance movements — every screen has data to click through.

## 🏗️ Architecture at a glance

```
routes/backoffice.php ── auth + permission middleware
   └─ Thin controller (authorize, validate via Form Request, call Domain)
         └─ Inertia::render('Backoffice/<Module>/Index', [...typed props])
            └─ resources/js/Pages/Backoffice/<Module>/Index.tsx (React)
               └─ BackofficeLayout.tsx shell (PreSkool theme, Bootstrap 5)
      app/Domain/<Module>/Actions ── the real business rules, called by the controller
```

- **Inertia + React on every backoffice screen** — no Livewire, no Alpine, no jQuery plugins anywhere in the app (fully removed, Phase 11)
- **Native `<select>` on every CRUD dropdown** (`resources/js/Components/Forms/SelectField.tsx`) — no Select2, no jQuery bridge
- **Center scoping is authorization**: policies combine the permission with `CenterAccessService`
- **French first**: every visible string goes through `__()` with translations in `lang/fr.json`
- **Uploads** via spatie/medialibrary on a dedicated disk, served from `/media/<uuid8>/…`

📚 Deep dives live in [`docs/`](docs/): [backoffice architecture](docs/backoffice-architecture.md) · [roles & permissions](docs/roles-and-permissions.md) · [center scoping](docs/center-scoping.md) — plus the full DB schema rationale in [`gls-crm-schema.md`](gls-crm-schema.md).

## 🧪 Tests

```bash
php artisan test        # 307 tests, 1531 assertions
```

Feature tests cover every module: allowed **and** denied (403) paths, center scoping, money invariants (balances move exactly once, self-validation refused), Inertia page/modal flows and upload rules.

Tests run against a real PostgreSQL database, `gls_crm_test` (`phpunit.xml`),
kept separate from the local dev database `gls_crm`. **Never point PHPUnit at
`gls_crm`** — `RefreshDatabase` and any destructive seeder must only ever run
against `gls_crm_test`.

---

<div align="center">

**GLS Sprachen Zentrum** · Backoffice CRM · Made with ❤️ and a healthy fear of untracked cash

</div>
