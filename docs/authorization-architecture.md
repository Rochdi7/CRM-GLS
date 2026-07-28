# Authorization Architecture — GLS CRM

Decisions taken after the audit (`docs/authorization-audit.md`).

## Core decisions

| Question | Decision | Why |
|---|---|---|
| Package | `spatie/laravel-permission` (latest, Laravel-13-compatible) | Required; verified against installed metadata |
| Authenticatable model | **`App\Models\User`** gets `HasRoles` | `User` is the only login model; employees log in through `users` |
| Guard | **`web` only** | Single session guard exists; no multi-guard complexity |
| Role model | **`App\Models\Role` extends Spatie's Role** with an extra nullable **`label`** column | The UI needs French display labels ("Directeur") separate from stable machine names (`director`); custom roles created later from the UI also get labels |
| Permission model | Spatie default | No customization needed |
| Spatie **Teams** | **NOT enabled** | See below |
| Super-admin | `Gate::before` in `AppServiceProvider::boot()` returns `true` for role `super-admin` | Spatie-recommended; no `hasRole()` scattered in code |

## Why Teams is NOT enabled

Teams would give per-center role assignments (Director in center A, Teacher in center B).
The GLS schema **deliberately** dropped the employee↔center many-to-many
(`employees.etablissement_id` is a single nullable FK — see the Deliberate
Simplifications table in `gls-crm-schema.md`). An employee works in at most one
center, so per-center roles cannot even be expressed by the data model. Enabling
Teams would add a `team_id` to every permission check for zero benefit.

**Revisit trigger:** if GLS re-introduces the employee↔center pivot (staff shared
across branches) *and* needs different roles per branch, enable Teams then.

## Roles ↔ permissions ↔ centers

Two separate questions, two separate mechanisms:

1. **"What may this user do?"** → global roles & permissions (Spatie).
   Naming: `module.action` dot notation, kebab-case modules — e.g.
   `students.view`, `cash-transfers.validate`. Single source of truth:
   `App\Support\Authorization\PermissionRegistry` (machine names + French labels,
   grouped by module). Permissions exist **only for implemented modules**.

2. **"On which center's data?"** → `App\Services\Authorization\CenterAccessService`:
   - `centers.access-all` permission ⇒ every center;
   - otherwise the employee's own `etablissement_id` (one center, per schema);
   - records with `etablissement_id = NULL` are considered global (visible to any
     user holding the module permission) — matches the schema where the column is
     nullable everywhere;
   - a User with no Employee profile and no `centers.access-all` sees no
     center-scoped records.

   Enforced in **policies** (model-level `view/update/delete`) — never only in the UI.

## Enforcement layers

| Layer | Mechanism |
|---|---|
| Routes (role-management module) | `permission:` middleware aliases registered in `bootstrap/app.php` |
| Existing resource controllers | Policies (`app/Policies`, one per model, shared `ResourcePolicy` base mapping viewAny/view/create/update/delete → `module.*` permissions) + `AuthorizesRequests` on the base controller |
| Livewire components | `authorize()` in `mount()` **and again in every mutation method** |
| Sensitive role operations | `App\Services\Authorization\UserAuthorizationService` (transactions, super-admin invariants, activitylog) |
| Blade/menus | `@can` / `@canany` — UI convenience only, never the security boundary |
| Super-admin | `Gate::before`; role protected from rename/delete; last-super-admin lockout prevention |

## Role catalogue

`super-admin`, `director`, `operations-director`, `administrative-assistant`,
`teacher`, `marketing-manager` — machine names stable, French labels in the
`label` column. Employee `categorie` (free text on the staff record) is related
but **never used as an authorization check**; role assignment is explicit via
the user-authorization screen (no automatic category→role mapping).
