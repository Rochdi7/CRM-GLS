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

| Machine name | Label (UI) |
|---|---|
| `super-admin` | Super administrateur |
| `director` | Directeur |
| `operations-director` | Directeur des opérations |
| `administrative-assistant` | Assistante administrative |
| `teacher` | Enseignant |
| `marketing-manager` | Responsable marketing |

Employee `categorie` is a related but separate concept — **no automatic
category→role mapping**; roles are assigned explicitly per user.

## 4. Permissions

Single source of truth: **`App\Support\Authorization\PermissionRegistry`**
(61 permissions, `module.action` naming, French labels, grouped by module).
Modules covered: dashboard, centers, academic-years, rooms, employees, users,
roles, permissions, students, registrations (+manage-fees), groups (+archive),
cash-registers, payments, expense-types, expenses, refunds, cash-transfers
(+validate), audit-logs. **Only implemented modules** — attendance/stock/reports/…
permissions will be added with their modules.

### Adding a new permission

1. Add it to `PermissionRegistry::grouped()` (with French label).
2. Add it to the relevant roles in `PermissionRegistry::matrix()`.
3. `C:\php84\php.exe artisan db:seed --class=RolesAndPermissionsSeeder`
4. Protect the route/controller/Livewire component with it.

## 5. Default matrix (summary)

- **super-admin** — everything via `Gate::before` (zero synced rows, by design).
- **director** — all operational modules incl. `centers.access-all`,
  `cash-transfers.validate`, `users.assign-roles`, `roles.view`, `audit-logs.view`;
  **not**: `roles.create/update/delete`, `users.assign-permissions`, `centers.create/update/delete`.
- **operations-director** — full academic/people management, `centers.access-all`;
  finance **view-only**.
- **administrative-assistant** — center-scoped day-to-day: students/registrations
  create+update, `payments.create`, `expenses.create`, `refunds.create`,
  `cash-transfers.create` (validation stays director-level); **no** `centers.access-all`.
- **teacher** — `dashboard.view`, `groups.view`, `students.view`. No finance.
- **marketing-manager** — dashboard, centers/students/registrations view.

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
