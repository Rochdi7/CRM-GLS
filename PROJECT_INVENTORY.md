# GLS CRM — Project Inventory

Ground-truth snapshot of the codebase: stack, directory layout, and every
implemented CRUD/module. Generated from a live scan of the repository.
For architecture rationale and invariants, see `CLAUDE.md`, `gls-crm-schema.md`,
and `gls-crm-laravel-structure.md`. This file is a map, not a rulebook.

> ⚠ **Frontend migration in progress** (branch `migration/inertia-react-preskool`,
> see `docs/inertia-react-migration-status.md` for the authoritative running
> log). CLAUDE.md's "Forbidden: React... Inertia" line below and §1's
> Livewire-only description are **stale** — both were written before this
> approved migration started and have not been rewritten yet (deliberately
> deferred, per `docs/inertia-react-migration-audit.md` §8 "Documentation
> debt"). Current real state as of Phase 6: **Inertia + React** now serve
> Permissions, Login, Forgot/Reset Password, Profile, Dashboard, the
> top-bar Context Switcher, the groups-historique index, the read-only
> show/detail pages for Students, Groups, Inscriptions, Caisses,
> Encaissements, Depenses, and Caisse Transfers, and the five simple CRUD
> modules (Établissements, Années scolaires, Salles, Frais, Types de
> dépenses — the Paramètres/Settings page and its own new Types de
> dépenses page). Every remaining CRUD index page with add/edit modals
> (Students, Employees, Users, Roles, Inscriptions, Groups, all Finance
> mutation screens — Depenses/Remboursements/Encaissements/Caisses/
> Transfers) and every other module listed in §4 below is still Livewire,
> unchanged. A permanent, read-only reference copy of the
> purchased PreSkool React theme also now lives at
> `resources/theme-reference/preskool-react/` (see
> `docs/preskool-react-reference-inventory.md`) — never a build input,
> never imported directly.

---

## 1. Stack

| Layer | Choice |
|---|---|
| Language | PHP **8.4** (`C:\php84\php.exe`) |
| Framework | Laravel **13** (`laravel/framework ^13.8`) |
| Frontend interactivity | Livewire **4.3** (bundled Alpine.js — no separate Alpine install) |\
| UI kit | Bootstrap 5 via **PreSkool v1.9.7** admin theme |
| Build tool | Vite 8 (own JS/SCSS only; PreSkool static assets are vendor/prebuilt) |
| Database | **PostgreSQL only** (`pgsql` connection, target PG17, min PG16) |
| Auth | Session-based `web` guard, backoffice-only login (email or username) |
| Roles/permissions | `spatie/laravel-permission ^8.3` (teams off) |
| Media | `spatie/laravel-medialibrary ^11.23` (`/media/<uuid8>/…` URLs) |
| Audit log | `spatie/laravel-activitylog ^5.0` |
| Architecture | Modular monolith — future business logic in `app/Domain/<Module>/` |

**Forbidden**: React, Vue, Angular, Inertia, Next.js, Tailwind, a second Alpine
instance, redesigning the PreSkool look, client-side DataTables for large lists,
SQLite/MySQL compatibility.

No frontend npm runtime dependencies — `package.json` devDependencies only:
`vite ^8`, `laravel-vite-plugin ^3.1`, `sass ^1.80`, `concurrently ^9.0.1`.

---

## 2. Backoffice / Frontoffice split

Everything lives in one of two areas (see `CLAUDE.md` §2 for the full mapping
table of routes/controllers/requests/views/JS/SCSS/tests per area).

**Frontoffice is currently a stub**: only `HomeController` + `/home` route +
`frontoffice.home`/`frontoffice.root` (redirects `/` → backoffice login).
No Livewire components, no Form Requests, no tests exist yet for Frontoffice —
it's reserved scaffolding (`.gitkeep` placeholders throughout), admin-first phase.

Almost the entire application described below is **Backoffice**.

---

## 3. Directory layout

```
app/
├── Console/Commands/
├── Domain/
│   ├── Expenses/Actions/          ← implemented
│   ├── Finance/Actions/           ← implemented
│   ├── Payments/Actions/          ← implemented
│   ├── Shared/Support/            ← implemented (ReferenceGenerator)
│   └── {Attendance,Centers,Employees,Groups,Registrations,Reports,
│        Settings,Stock,Students}/ ← reserved, .gitkeep only
│   └── Shared/{Actions,DTOs,Enums,Exceptions,Services}/ ← reserved
├── Http/
│   ├── Controllers/{Backoffice/{Auth,Concerns},Frontoffice/Auth}/
│   └── Requests/{Backoffice/<Module>,Frontoffice(empty)}/
├── Livewire/
│   └── Backoffice/{Students,Employees,Inscriptions,Groups,Encaissements,
│        Depenses,Remboursements,Caisses,CaisseTransfers,TypesDepenses,
│        Users,Roles,Settings,Context,Dashboard,Profile,Concerns}/
│   └── Frontoffice/{Home,Shared}/  ← reserved, .gitkeep only
├── Models/
├── Observers/                      ← EmployeeObserver
├── Policies/Concerns/
├── Providers/
├── Services/{Authorization,Context}/
└── Support/{Authorization,Media,Phone}/

resources/views/
├── backoffice/  (auth, caisse-transfers, caisses, dashboard, depenses,
│                 encaissements, groups, groups-historique, inscriptions,
│                 permissions, settings, students, pages(empty))
├── frontoffice/ (home; auth & pages reserved/empty)
├── components/  (backoffice/{layout,ui,forms}, frontoffice/layout, shared(empty))
├── livewire/backoffice/<module>/
├── theme-reference/preskool/       ← permanent reference copies, never edited
└── vendor/pagination

routes/        backoffice.php · frontoffice.php · web.php (requires only) · console.php
database/      migrations/ · seeders/ · factories/
config/        app · auth · cache · database · filesystems · logging · mail ·
               media-library · permission · queue · services · session
```

---

## 4. Livewire components (22 total, all Backoffice)

| Module | Class | Pattern |
|---|---|---|
| Students | `Students\StudentsIndex` | Full CRUD — modal add/edit, photo upload, niveau/center filters |
| Employees | `Employees\EmployeesIndex` | Full CRUD — modal add/edit, photo upload, auto-creates login |
| Inscriptions | `Inscriptions\InscriptionsIndex` | Full CRUD — modal add/edit with repeatable manual fee lines |
| Groups | `Groups\GroupsIndex` | Full CRUD — modal add/edit + fee assignment; **no destroy**, archive-only |
| Encaissements | `Encaissements\EncaissementsIndex` | Create/edit only — **no destroy** (money record) |
| Depenses | `Depenses\DepensesIndex` | Create/edit only — **no destroy**, receipt uploads |
| Remboursements | `Remboursements\RemboursementsIndex` | Create/edit only — **no destroy** |
| Caisses | `Caisses\CaissesIndex` | List/overview widget (till accounts) — tab of "Gestion de la caisse" |
| Caisses | `Caisses\CaisseJournal` | Widget/tab — transaction journal (`mount(scope: 'mine')`) |
| CaisseTransfers | `CaisseTransfers\CaisseTransfersIndex` | Two-step request/validate modal flow — **no destroy** |
| TypesDepenses | `TypesDepenses\TypesDepensesIndex` | Full CRUD — modal add/edit |
| Users | `Users\UsersIndex` | Edit-only modal — **no destroy**, `is_active` toggle, password reset |
| Users | `Users\ManageAuthorization` | Full-page widget — role/direct-permission assignment for one user |
| Roles | `Roles\RolesIndex` | List page → links to separate create/edit page |
| Roles | `Roles\RoleForm` | Full-page create/edit (not a modal) |
| Settings | `Settings\EtablissementsTab` | Tab, full CRUD (modal) |
| Settings | `Settings\AnneesScolairesTab` | Tab, full CRUD (modal) |
| Settings | `Settings\SallesTab` | Tab, full CRUD (modal) |
| Settings | `Settings\FraisTab` | Tab, full CRUD (modal) |
| Context | `Context\ContextSwitcher` | Widget — top-bar academic-year/center switcher |
| Dashboard | `Dashboard\DashboardStats` | Widget — stat cards, refreshes on `context-changed` |
| Profile | `Profile\ProfilePage` | Full-page — signed-in user's own profile edit |

Reusable traits: `Concerns\{WithCaisseSelection, WithCenterContext, WithPerPage, WithPhoneCountry}`.

**Money records never have a destroy route/action** (Encaissements, Depenses,
Remboursements, CaisseTransfers) — corrections use compensating entries.
**Groups never delete** — only `archiverCommeTermine()`.

---

## 5. Models (`app/Models/`, 18)

| Model | Represents | Notable traits |
|---|---|---|
| `Etablissement` | Branch/center — root of most scoping | — |
| `AnneeScolaire` | Academic year | — |
| `Salle` | Room/venue per branch | — |
| `Employee` | Staff record (teachers are employees) | `InteractsWithMedia` |
| `User` | Auth account, created only via credential service | `HasRoles`, `Notifiable` |
| `Student` | Student + inline guardian contact | `InteractsWithMedia`, `LogsActivity` |
| `Group` | Class/cohort — never deleted | — |
| `GroupHistorique` | Read-only "Fin de formation" snapshot | — |
| `Frais` | Fee catalog entry | — |
| `Inscription` | Enrollment (Student × Group) | `LogsActivity` |
| `InscriptionFee` | Fee line item on an enrollment | — |
| `Caisse` | Till/cash register, app-maintained `solde` | — |
| `Encaissement` | Payment received from a student | `LogsActivity` |
| `Depense` | Expense (cash outflow) | `InteractsWithMedia`, `LogsActivity` |
| `TypeDepense` | Expense type catalog (`is_system` locked rows) | — |
| `Remboursement` | Refund to a student | `LogsActivity` |
| `CaisseTransfer` | Till-to-till transfer (2-step) | `LogsActivity` |
| `Role` | Extends Spatie Role + French label | — |

---

## 6. Migrations (chronological summary)

1. Framework defaults: `users`, `cache`, `jobs`
2. `activity_log` (Spatie)
3. Core schema (2026-07-23, one batch): `etablissements` → `annees_scolaires` →
   user credential columns → `salles` → `employees` → `students` → `groups` →
   `groups_historique` → `inscriptions` → `inscription_fees` → `caisses` →
   `encaissements` → `types_depenses` → `depenses` → `remboursements` →
   `caisse_transfers`
4. `media` (Spatie Media Library), `permission_tables` + `label` column on roles
5. `users.is_active`, HR fields on `employees`
6. Fees module: `frais`, `group_frais` pivot, discount fields on
   `inscription_fees`, `date_echeance` on `group_frais`
7. Later refinements: parent details on `students`, fee classification,
   drop `montant_defaut` from `frais`, orientation fields on `students`,
   address/photo on `employees`, billing fields on `depenses`, performance
   indexes for finance/academic tables, missing FK indexes for Postgres

---

## 7. Routes

`routes/web.php` only `require`s the two area files — no routes declared directly.

### Frontoffice (`frontoffice.*`)
- `GET /` → redirect to backoffice login (`frontoffice.root`)
- `GET /home` → `frontoffice.home` (`HomeController`)

### Backoffice (prefix `/backoffice`, name prefix `backoffice.*`)

**Guest** (`guest` middleware): `login` (GET/POST), `login.store`,
`forgot-password` (GET/POST), `reset-password/{token}` (GET), `reset-password` (POST).

**Authenticated** (`auth` middleware), by module:

| Module | Routes |
|---|---|
| Dashboard / session | `dashboard`, `logout`, `profile` |
| Settings | `settings` (tabbed: centers/years/rooms, permission-gated per tab) |
| Référentiel | `etablissements.*`, `annees-scolaires.*` (no show), `salles.*` (no show) — policy-backed resources |
| Employees | `employees.index` (Livewire) |
| Students | `students.index` (Livewire), `students.{student}` show |
| Groups | `groups.index` (Livewire), `groups.{group}` show, `groups.{group}/archive` (POST) |
| Groups historique | `groups-historique.index` |
| Inscriptions | `inscriptions.index` (Livewire), `inscriptions.{inscription}` show, `inscription-fees.*` (store/update/destroy) |
| Caisses | `caisses.index` (tabbed: Ma caisse/journal/transferts/comptes), `caisses.{caisse}` show |
| Encaissements | `encaissements.index` (Livewire), `encaissements.{encaissement}` show |
| Dépenses | `depenses.index` (tabbed: dépenses/remboursements/types), `depenses.{depense}` show |
| Types dépenses / Remboursements / Transferts | thin redirects into the tabbed pages above, permission-gated |
| Roles | `roles.index` (Livewire), `roles.create`, `roles.{role}/edit` |
| Permissions | `permissions.index` (read-only) |
| Users | `users.index` (Livewire), `users.{user}/authorization` (Livewire) |

Money-record and Group routes deliberately have **no destroy** endpoint.

---

## 8. Controllers

**Backoffice**: `AnneeScolaireController`, `Auth/{ForgotPasswordController,
LoginController, LogoutController, ResetPasswordController}`,
`CaisseController`, `CaisseManagementController`, `CaisseTransferController`,
`Concerns/ResolvesActingEmployee` (trait), `DashboardController`,
`DepenseController`, `DepenseManagementController`, `EmployeeController`,
`EncaissementController`, `EtablissementController`, `GroupController`,
`GroupHistoriqueController`, `InscriptionController`, `InscriptionFeeController`,
`PermissionController`, `RemboursementController`, `SalleController`,
`SettingController`, `StudentController`, `TypeDepenseController`.

**Frontoffice**: `HomeController` only (`Auth/` reserved, empty).

---

## 9. Form Requests

Store/Update pairs under `App\Http\Requests\Backoffice\<Module>\` for:
`AnneesScolaires`, `Auth` (Login/Forgot/Reset), `Caisses`, `CaisseTransfers`,
`Depenses`, `Employees`, `Encaissements`, `Etablissements`, `Groups`,
`InscriptionFees`, `Inscriptions`, `Remboursements`, `Salles`, `Students`,
`TypesDepenses`. Frontoffice requests directory is empty.

---

## 10. Policies (`app/Policies/`, 15)

`AnneeScolairePolicy`, `CaissePolicy`, `CaisseTransferPolicy`, `DepensePolicy`,
`EmployeePolicy`, `EncaissementPolicy`, `EtablissementPolicy`, `FraisPolicy`,
`GroupPolicy`, `InscriptionFeePolicy`, `InscriptionPolicy`,
`RemboursementPolicy`, `SallePolicy`, `StudentPolicy`, `TypeDepensePolicy` —
all extend the shared `Policies\Concerns\ResourcePolicy` (permission +
center-scoping via `CenterAccessService`). No dedicated User/Role policy —
those are governed by permission middleware + `UserAuthorizationService`.

---

## 11. Domain layer (`app/Domain/`)

**Implemented actions** (single-transaction business operations):

| Action | Purpose |
|---|---|
| `Payments\Actions\EnregistrerEncaissement` | Records a payment; increments till balance; recomputes fee status |
| `Expenses\Actions\EnregistrerDepense` | Records an expense; decrements till balance |
| `Finance\Actions\EnregistrerRemboursement` | Records a refund; decrements till balance |
| `Finance\Actions\DemanderTransfertCaisse` | Till-transfer request step; balances untouched |
| `Finance\Actions\ValiderTransfertCaisse` | Till-transfer validation step; different-employee check; moves balances |
| `Shared\Support\ReferenceGenerator` | Sequential reference codes (EMP-/ETU-/INS-/ENC-/DEP-/RMB-/TRF-…) |

**Reserved** (`.gitkeep` only, future modules): `Attendance`, `Centers`,
`Employees`, `Groups`, `Registrations`, `Reports`, `Settings`, `Stock`,
`Students`, and `Shared\{Actions,DTOs,Enums,Exceptions,Services}`.

**`app/Services/`**: `Authorization\CenterAccessService`,
`Authorization\UserAuthorizationService`, `CaisseProvisioner`,
`Context\CurrentContext`, `EmployeeCredentialService`.

**`app/Support/`**: `Authorization\PermissionRegistry`,
`Media\ShortUuidPathGenerator`, `Phone\Countries`.

**`app/Observers/`**: `EmployeeObserver` → provisions a till on employee creation.

---

## 12. Seeders (`database/seeders/`)

`DatabaseSeeder` (orchestrator), `AdminUserSeeder`, `AnneeScolaireSeeder`,
`DemoDataSeeder`, `DemoFinanceSeeder`, `DemoRoleUsersSeeder`, `FraisSeeder`,
`ReferentialDataSeeder`, `RolesAndPermissionsSeeder`, `TypeDepenseSeeder`.

---

## 13. Tests (`tests/Feature/Backoffice/`)

```
AuthTest · PasswordResetTest · ProfileTest
Authorization/   CenterAccessTest · RoleManagementAuthorizationTest ·
                 RoleManagementLivewireTest · RolesAndPermissionsSeederTest ·
                 SuperAdminProtectionTest · UserAuthorizationTest
Context/         CenterScopingTest · CurrentContextTest · DashboardStatsTest
Finance/         CaisseManagementPageTest · CaisseTransfersTest ·
                 CaissesCrudTest · DepensesCrudTest · EncaissementsCrudTest ·
                 RemboursementsCrudTest · TypesDepensesCrudTest
Groups/          GroupsCrudTest · GroupsHistoriqueTest
Inscriptions/    InscriptionStudentFieldsTest · InscriptionsCrudTest
People/          EmployeeProfileFieldsTest · EmployeesCrudTest · UsersCrudTest
Settings/        SettingsTest
Students/        StudentOrientationTest · StudentsCrudTest
```
Plus `tests/Unit/Support/`. No `tests/Feature/Frontoffice/` yet.

---

## 14. Blade components

**`components/backoffice/`**
- *layout*: `app`, `breadcrumbs`, `footer`, `guest`, `head`, `header`,
  `page-header`, `print`, `scripts`, `sidebar`, `theme-settings`, `toasts`
- *ui*: `action-menu` (+ `action-menu/item`), `alert`, `badge`, `button`,
  `card`, `empty-state`, `filter-bar` (+ `filter-bar/date-field`), `modal`,
  `pagination`, `per-page-select`, `sexe-icon`, `table`
- *forms*: `error`, `input`, `phone-country`, `phone-input`, `select`,
  `select2`, `tags-input`, `textarea`

**`components/frontoffice/`**: *layout* only — `app`, `footer`, `guest`, `header`.

**`components/shared/`**: empty, reserved.

---

## 15. Permissions (`app/Support/Authorization/PermissionRegistry.php`)

**63 permissions** across **19 modules**, `module.action` naming, French labels:

| Module | Count | Actions |
|---|---|---|
| Tableau de bord | 1 | view |
| Centres | 5 | view/create/update/delete/access-all |
| Années scolaires | 4 | view/create/update/delete |
| Salles | 4 | view/create/update/delete |
| Frais | 4 | view/create/update/delete |
| Employés | 4 | view/create/update/delete |
| Utilisateurs | 3 | view/assign-roles/assign-permissions |
| Rôles | 4 | view/create/update/delete |
| Permissions | 1 | view |
| Étudiants | 4 | view/create/update/delete |
| Inscriptions | 5 | view/create/update/delete/manage-fees |
| Groupes | 4 | view/create/update/archive |
| Caisses | 4 | view/create/update/delete |
| Encaissements | 3 | view/create/update |
| Types de dépenses | 4 | view/create/update/delete |
| Dépenses | 3 | view/create/update |
| Remboursements | 3 | view/create/update |
| Transferts de caisse | 4 | view/create/update/validate |
| Journal d'audit | 1 | view |

**Roles (6)**: `super-admin` (bypasses all via `Gate::before`), `director`,
`operations-director`, `administrative-assistant`, `teacher`, `marketing-manager`.

---

## 16. What's NOT built yet

- Frontoffice (public/student-facing site) — only a home-page stub exists.
- Attendance, Reports, Stock domain modules — reserved directories only.
- Any Domain module besides Payments/Expenses/Finance actions.
