# Roles & Permissions — GLS CRM

Companion docs: `authorization-audit.md` (state before implementation),
`authorization-architecture.md` (decisions & rationale).

## 1. Package

- **spatie/laravel-permission ^8.3** (Laravel-13 compatible, verified from installed metadata).
- Config: `config/permission.php` — only change: `models.role` → `App\Models\Role`.
- Tables: `permissions`, `roles` (+ our `label` column), `model_has_permissions`,
  `model_has_roles`, `role_has_permissions` (bigint keys, `web` guard, **Teams OFF**).

## 2. Architecture

- **Authenticatable model:** `App\Models\User` (`HasRoles` trait). Employees log in through `users`.
- **Guard:** `web` only.
- **Role model:** `App\Models\Role` (Spatie Role + French `label`, `isProtected()`).
- **Teams:** not enabled — employees belong to at most ONE center
  (`employees.etablissement_id`, no pivot). See architecture doc for the revisit trigger.
- **Center scoping:** `App\Services\Authorization\CenterAccessService` used by policies —
  `centers.access-all` ⇒ everything; otherwise the employee's center; NULL-center
  records are global; no employee profile ⇒ global records only.
- **Super-admin:** `Gate::before` in `AppServiceProvider` (role `super-admin` ⇒ allow all).
  Never write `hasRole('super-admin')` checks in application code.

## 3. Roles

There is **one role per job title** offered by the Employees form
(`Employee::CATEGORIES`), so a newly hired employee always has a matching role
to grant on the Autorisations screen.

| Machine name | Label (UI) | Matching `categorie` |
|---|---|---|
| `super-admin` | Super administrateur | — (granted by hand) |
| `director` | Directeur | Directeur |
| `operations-director` | Directeur des opérations | Directeur des opérations |
| `financial-director` | Directeur financier | Directeur financier |
| `quality-director` | Directeur Qualité et Amélioration continue | idem |
| `pedagogical-director` | Directrice pédagogique | Directrice pédagogique |
| `accountant` | Comptable | Comptable |
| `consultant` | Consultant | Consultant |
| `hr-manager` | Responsable RH | Responsable RH |
| `marketing-manager` | Responsable marketing | Responsable Marketing |
| `administrative-manager` | Responsable administrative | Responsable administrative |
| `administrative-assistant` | Assistante administrative | Assistante administrative |
| `teacher` | Enseignant | Enseignant |

⚠ Employee `categorie` remains a **separate concept**: it is never used in an
authorization check, and changing an employee's job title does **not** change
their access. The names line up only so granting is obvious.

The catégorie→default-role map lives in ONE place:
**`PermissionRegistry::defaultRoleFor()`**, shared by both consumers:

- **`EmployeeObserver`** — when creating an employee auto-creates its login
  (user_id was null), the new user gets the default role for the job title
  immediately. Without it the account authenticates but every
  `permission:`-guarded page answers 403 — a dead end that looks like a
  broken deployment (this bit production on 21/08/2026). When `user_id` is
  passed explicitly the caller owns the account and its roles; the observer
  assigns nothing.
- **`GlsStaffSeeder`** — same default at seed time, and never overwrites a
  role already on the account (a promotion made on Autorisations survives a
  re-seed).

It is a **creation-time default only**: editing the catégorie later changes
nothing, and the Autorisations screen remains the way to change access.
`Autre` maps to no role — an employee with no defined post gets no access
until one is granted by hand.

## 4. Permissions

Single source of truth: **`App\Support\Authorization\PermissionRegistry`**
(102 permissions, `module.action` naming, French labels, grouped by module).
Modules covered: dashboard, centers, academic-years, rooms, employees, users,
roles, permissions, students, registrations (+manage-fees), groups (+archive),
cash-registers, payments, expense-types, expenses, refunds, cash-transfers
(+validate), audit-logs. **Only implemented modules** — attendance/stock/reports/…
permissions will be added with their modules.

### Adding a new permission

1. Add it to `PermissionRegistry::grouped()` (with French label).
2. Add it to the relevant roles in `PermissionRegistry::presets()`.
3. `C:\php84\php.exe artisan db:seed --class=RolesAndPermissionsSeeder`
4. Protect the route/controller with it.

⚠ A new `*.delete` permission is **automatically** locked to super-admin by
`superAdminOnly()` (see §5) — listing it in a preset has no effect. That is
deliberate: a forgotten delete can never leak into a role.

## 5. Deleting is super-admin-only

`PermissionRegistry::superAdminOnly()` lists the abilities **no role preset may
hold**; `matrix()` filters every preset through it, so the rule is enforced by
construction rather than by remembering to omit lines.

It covers two families:

1. **Every `*.delete` permission**, computed from `grouped()` — students,
   inscriptions, employés, salles, frais, stock, rôles, centres… Deleting is a
   super-admin act; other roles edit instead. `groups.archive` is **not** a
   delete: it is the sanctioned "Fin de formation" path that writes a
   `groups_historique` snapshot (CLAUDE.md §11), so it stays with the roles
   that run groups. Money records already have no delete route at all.
2. **The pre-existing super-admin-only abilities**: `system-settings.*`,
   `banks.*`, `cancellation-reasons.*`, `cash-accounts.*` (the global
   non-center-scoped view) and `expenses.approve`.

A super-admin can still grant any single one of these to one user by hand on
the Autorisations screen when a real case needs it — the filter constrains the
role **presets**, not what a super-admin may deliberately delegate.

### `payments.delete` — the one delete with a real code path

Money records are append-only, so most have no destroy route at all. The
exception is `backoffice.encaissements.destroy`, which exists deliberately and
is gated by `permission:payments.delete`. Behind it,
`Domain\Payments\Actions\SupprimerEncaissement` is the exact ledger-aware
inverse of `EnregistrerEncaissement`: it re-reads the row `lockForUpdate()`,
reverses the till through `CaisseLedger` (so the movement stays visible in the
audit journal), recomputes the fee statut, and refuses rows entangled with an
applied avance or a tracked chèque.

Since no preset may hold `payments.delete`, this path is reachable only by a
super-admin, or by a user a super-admin explicitly granted it to. Normal
corrections still use a compensating entry (remboursement), never a delete.

⚠ `ReadOnlyPagesInertiaTest` asserts this by exercising the **gate** — a user
with `payments.view/create/update` gets a 403 and the till is untouched — not
by asserting the route's absence. An earlier version of that test checked
`Route::has(...)` and broke the day the route landed; don't reintroduce that
form.

## 5b. Default matrix (summary)

Presets live in `PermissionRegistry::presets()`; two shared building blocks
keep the roles from drifting apart:

- **`$operations`** — the full center-scoped day-to-day scope: étudiants,
  inscriptions (+frais, +changement de groupe), groupes (+archive), séances
  et appel, caisse, encaissements, dépenses, remboursements, chèques,
  demandes de transfert, mouvements de stock. **No** `centers.access-all`.
- **`$financeReadOnly`** — read access to every finance screen, the baseline
  the accounting/oversight roles build on.

| Role | Scope |
|---|---|
| `super-admin` | Everything via `Gate::before` (zero synced rows, by design) — and the only role that deletes. |
| `director` | `$operations` + `centers.access-all`, catalogs (années, salles, frais, types), employés, `users.assign-roles`, `roles.view`, `cash-transfers.validate`, import, audit. |
| `operations-director` | `$operations` + `centers.access-all`, salles/frais, employés (view+update), stock catalog, import, audit. |
| `financial-director` | `$financeReadOnly` + all money create/update, caisses, `cash-transfers.validate`, `centers.access-all`, audit. |
| `accountant` | Same money scope minus `cash-transfers.validate` and the caisse catalog — books entries, does not arbitrate them. |
| `quality-director` | Read-only across every module, all centers, incl. `audit-logs.view`. Changes nothing. |
| `pedagogical-director` | Academic authority: groupes, séances, salles, frais, étudiants, inscriptions. No money. |
| `consultant` | `$operations` exactly — own center only. |
| `administrative-assistant` | `$operations` exactly — identical to `consultant` (asserted by a test). |
| `administrative-manager` | `$operations` + employés, `users.view`, audit. |
| `hr-manager` | Staff file across all centers only — no student, academic or money data. |
| `marketing-manager` | Dashboard + centres/étudiants/inscriptions/groupes, view-only. |
| `teacher` | `dashboard.view`, `groups.view`, `students.view`, séances + appel. No finance. |

Every non-super-admin role above holds **zero** delete permissions and cannot
approve a dépense or validate a transfer unless the row says so.

Exact lists: `PermissionRegistry::matrix()` (tested in `RolesAndPermissionsSeederTest`).

## 6. Seeding & first super-admin

```powershell
C:\php84\php.exe artisan db:seed --class=RolesAndPermissionsSeeder   # idempotent, safe to re-run
C:\php84\php.exe artisan auth:assign-super-admin admin@gls.test      # explicit, audited, confirms in production
```

The seeder never touches users. Local dev: `admin@gls.test` has been granted
super-admin via the command.

## 7. Protecting things

```php
// Route (bootstrap/app.php aliases: role / permission / role_or_permission)
Route::get('roles', RolesIndex::class)->middleware('permission:roles.view');

// Controller — resource controllers use policies via authorizeResource:
public function __construct() { $this->authorizeResource(Student::class, 'student'); }
// custom actions:
$this->authorize('validate', $caisse_transfer);

// Livewire — authorize in mount() AND in every mutation method:
public function mount(): void { $this->authorize('roles.view'); }
public function deleteRole(int $id): void { $this->authorize('roles.delete'); … }

// Blade (UI convenience only — server side stays authoritative):
@can('roles.view') … @endcan
@canany(['users.view', 'roles.view']) … @endcanany
```

Policies live in `app/Policies` — subclass `Policies\Concerns\ResourcePolicy`
(sets `$module`, override `centerId()` for indirect center links like
encaissement → caisse → center). Money-record policies expose no `delete`
(schema invariants); `GroupPolicy::delete` always returns false;
`TypeDepensePolicy` locks `is_system` rows.

## 8. Super-admin protection

- `Gate::before` grants everything; role has zero synced permissions.
- Role is `Role::PROTECTED`: not editable/deletable in the UI (server-enforced).
- Machine name immutable (role names are immutable after creation in general).
- Only a super-admin may grant/remove `super-admin`
  (`UserAuthorizationService::guardSuperAdminRules`).
- The **last** super-admin can never lose the role (lockout prevention).
- Every authorization change is written to the activity log
  (`log_name = authorization`, actor + old/new assignments).

## 9. UI (Backoffice, PreSkool design, French)

| Page | Route | Permission |
|---|---|---|
| Roles list | `backoffice.roles.index` | `roles.view` |
| Create role | `backoffice.roles.create` | `roles.create` |
| Edit role | `backoffice.roles.edit` | `roles.update` |
| Permissions catalogue (read-only) | `backoffice.permissions.index` | `permissions.view` |
| Users list | `backoffice.users.index` | `users.view` |
| User authorization | `backoffice.users.authorization.edit` | `users.assign-roles` |

Livewire 4 full-page components (`App\Livewire\Backoffice\{Roles,Users}`);
permissions page is plain Blade (static). Direct permissions live in an
"advanced" section with a warning and require `users.assign-permissions`.
Sidebar "Administration" section is `@can`-gated.

## 10. Tests

`tests/Feature/Backoffice/Authorization/` — 44 tests:
seeder (idempotency, matrix, labels), HTTP route protection (guest redirect,
403s, per-role access), Livewire (mount + mutation authorization, validation,
protected role), user authorization (persistence, super-admin rules, direct
permissions, audit log), super-admin safety (Gate::before, last-admin lockout,
command), center scoping (service, query scope, policies, 403 on foreign IDs).

```powershell
C:\php84\php.exe artisan test                                      # full suite
C:\php84\php.exe artisan test tests/Feature/Backoffice/Authorization
C:\php84\php.exe artisan test --filter=CenterAccess
```

## 11. Troubleshooting the permission cache

Spatie caches permissions (24 h). After manual DB changes or deployments:

```powershell
C:\php84\php.exe artisan permission:cache-reset
```

The seeder, the role form, the user-authorization service and role deletion all
reset it automatically. In tests, `CACHE_STORE=array` isolates it per process.

## 12. Security rules (non-negotiable)

1. Authorization is enforced **server-side** (routes + policies + Livewire
   methods). Menu/`@can` visibility is convenience, never the boundary.
2. Check **permissions** (`students.view`), not role names. Roles are
   permission collections; `hasRole()` appears nowhere but `Gate::before`
   and the super-admin invariants.
3. Never trust browser-submitted role/permission names — everything is
   validated against the DB/registry (`UserAuthorizationService`, `RoleForm`).
4. Center scoping is part of authorization: policies must combine permission
   + `CenterAccessService` for every center-bearing model.
5. New protected module ⇒ new permissions in the registry ⇒ tests for both
   allowed and denied paths.
