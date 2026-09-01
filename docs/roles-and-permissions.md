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
- **Teams:** not enabled — center reach is the `employee_etablissement` pivot
  (« Centres affectés » on the Employees form, ≥ 1 center required), with
  `employees.etablissement_id` as the PRIMARY center (where the caisse lives).
- **Center scoping:** `App\Services\Authorization\CenterAccessService` used by policies —
  « Centres affectés » is the ONE authority: the user reaches exactly the
  centers assigned on their employee form. `centers.access-all` (super-admin
  bypass, or hand-granted to a rare truly-global account — NO role preset
  carries it, see §5b) ⇒ everything; NULL-center records are global; no
  employee profile ⇒ global records only.
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

- **`EmployeeObserver@created`** — when creating an employee auto-creates its
  login (user_id was null), the new user gets the default role for the job
  title immediately. Without it the account authenticates but every
  `permission:`-guarded page answers 403 — a dead end that looks like a
  broken deployment (this bit production on 21/08/2026). When `user_id` is
  passed explicitly the caller owns the account and its roles; the observer
  assigns nothing at creation.
- **`EmployeeObserver@updated`** — a catégorie change assigns the default
  role **only when the login still has no role at all**. This is the
  « Autre » escape hatch: an account seeded/created as « Autre » is unlocked
  by simply setting its real job title on the Employees screen — no separate
  Autorisations trip needed.
- **`GlsStaffSeeder`** — same default at seed time, and never overwrites a
  role already on the account (a promotion made on Autorisations survives a
  re-seed).

In every path the default only **fills a vacuum**: a user holding ANY role is
never touched, so `categorie` still never drives access at runtime and the
Autorisations screen remains the only way to *change* access. `Autre` maps to
no role — an employee with no defined post gets no access until one is
granted by hand (or their real catégorie is set).

For everything already in the database (a restored dump, an import, accounts
seeded before the auto-role fix), the bulk repair is:

```powershell
C:\php84\php.exe artisan auth:sync-default-roles            # assign
C:\php84\php.exe artisan auth:sync-default-roles --dry-run  # report only
```

Same vacuum-only rule, idempotent, audited (`authorization` log). It reports
« Autre » accounts separately — those still need a human to say what the
person's job is.

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
2. **The reserved abilities**: `system-settings.*`, `banks.*`,
   `cancellation-reasons.*`, `cash-accounts.create/update/delete` (writing an
   account by hand — the READ went to the five management roles on
   31/08/2026, §5b), `expenses.approve`, `groups.move-year`,
   `payments.reallocate`, **`payments.update-date`** (re-dating money —
   30/08/2026, §5b), **`groups.reopen`** (rouvrir un groupe terminé/annulé —
   01/09/2026, §5c) — and `centers.access-all`, so that « Centres affectés »
   on the employee form stays the one authority on center reach (§5b).

⚠ **`employees.create` is not in `superAdminOnly()` and does not need to be** —
it was simply removed from every preset on 30/08/2026, because hiring mints a
login (`EmployeeObserver`) and, for « Responsable de système », a super-admin.
It stays *grantable* on the Autorisations screen for a deliberate case, unlike
family 1 and 2 which are filtered out of presets by construction. A test
(`test_no_role_may_create_an_employee`) keeps it out of the presets.

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

> **Révision du 30–31/08/2026** — le périmètre des rôles a été refondu à la
> demande du métier. Les quatre règles nouvelles :
>
> 1. **Consultant = Assistante administrative**, à la permission près.
> 2. **Ce qu'un front-office MODIFIE se limite aux quatre objets
>    pédagogiques** : étudiants, inscriptions, groupes, séances (emploi du
>    temps). Tout le reste de la finance est **création seule** — on corrige
>    par une écriture compensatoire, jamais en réécrivant le document.
> 3. **La date d'un encaissement est réservée au super-admin**
>    (`payments.update-date`). Sa **méthode** est corrigeable par les cinq
>    rôles de direction (`payments.update-method`, 01/09/2026) — et cette
>    correction déplace l'argent entre les deux caisses concernées.
> 4. **Le stock physique appartient au seul `marketing-manager`** ; les
>    autres rôles gardent `stock.view`.

Presets live in `PermissionRegistry::presets()`; three shared building blocks
keep the roles from drifting apart:

- **`$operations`** — the center-scoped front-desk scope: étudiants,
  inscriptions (+frais, +changement de groupe), groupes (+archive), séances
  et appel, caisse, encaissements, avances, chèques, dépenses,
  remboursements, demandes de transfert. Les opérations de paiement
  essentielles (enregistrer une avance, la convertir/l'appliquer, saisir un
  chèque) **restent dedans** : elles créent des lignes, elles n'en réécrivent
  aucune. Stock en **lecture seule**. **No** `centers.access-all`.
- **`$managementEdits`** — les corrections qu'un cadre arbitre :
  `expenses.update`, `refunds.update`, `cheques.update`,
  `cash-transfers.update`, **`payments.update-method`**, plus la **lecture**
  de « Comptes de caisse » (`cash-accounts.view`). Ajouté aux cinq rôles de
  direction.

  ⚠ **`payments.update-method` (01/09/2026) n'est pas une correction
  d'étiquette.** La méthode a décidé dans quelle `caisses` l'argent est tombé
  (`CaisseResolver` : Espèces → la caisse physique de l'agent ; TPE / Chèque /
  Virement → le compte du CENTRE pour cette méthode), donc la corriger
  **déplace l'argent** : `RequalifierMethodeEncaissement` débite l'ancienne
  caisse, crédite la nouvelle et met à jour `caisse_id` sur la ligne, le tout
  dans UNE transaction avec les **deux jambes journalisées** par
  `CaisseLedger` (jamais une écriture directe sur la colonne, qui laisserait
  l'argent dans un compte et le libellé sur un autre). `montant` ne bouge
  jamais : on range l'argent ailleurs, on n'en crée pas.

  Le centre de la nouvelle caisse est celui de l'ENCAISSEMENT, pas le contexte
  actif de celui qui corrige — même raisonnement que pour un remboursement lié
  (CLAUDE.md §11, dimension centre du journal). L'action refuse trois cas, et
  le read-model porte la même règle jusqu'à l'UI via
  `GetEncaissementsList` → `methodeRequalifiable` : une ligne « application »
  d'avance (elle n'a crédité aucune caisse), un paiement lié à un chèque suivi
  (cycle de vie possédé par le module Chèques), un paiement déjà remboursé
  (l'argent est déjà sorti). Le front-office reste en **création seule** : il
  garde `payments.update` pour la note et l'identité du chèque, mais ne
  déplace pas d'argent. Tests :
  `tests/Feature/Backoffice/Finance/EncaissementsInertiaCrudTest.php`.
- **`$financeReadOnly`** — read access to every finance screen, the baseline
  the accounting/oversight roles build on.

⚠ **`centers.access-all` sits in `superAdminOnly()` — NO role preset may
carry it.** « Centres affectés » on the employee form (the
`employee_etablissement` pivot) is the ONE authority on which centers a user
reaches, whatever their role. Cela vaut aussi pour « Comptes de caisse » :
l'onglet suit le sélecteur de centre de la barre du haut, et « Tous les
centres » n'est offert qu'aux super-admins — un directeur y voit donc
exactement ses centres affectés, jamais le réseau entier.
Super-admins see everything via `Gate::before` regardless.

| Role | Scope (always confined to the centers assigned on the employee form) |
|---|---|
| `super-admin` | Everything via `Gate::before` (zero synced rows, by design) — the only role that deletes, re-dates a payment, approves a dépense, or writes a compte de caisse. |
| `director` | `$operations` + `$managementEdits` + catalogs (années, salles, frais, types), employés (view+update), `users.assign-roles`, `roles.view`, `cash-transfers.validate`, import, audit. |
| `operations-director` | `$operations` + `$managementEdits` + salles/frais, employés (view+update), import, audit. |
| `financial-director` | `$operations` + `$managementEdits` + caisses, types de dépenses, `cash-transfers.validate`, audit. |
| `pedagogical-director` | `$operations` + `$managementEdits` + salles/frais, `employees.view`, audit. |
| `hr-manager` | `$operations` + `$managementEdits` + employés (view+update), `users.view`, audit. |
| `accountant` | `$financeReadOnly` + toutes les écritures financières (create+update) — inchangé le 30/08/2026, sa fiche de poste correspondait déjà. |
| `quality-director` | Read-only across every module, incl. `audit-logs.view`. Changes nothing. |
| `consultant` | `$operations` exactly. |
| `administrative-assistant` | `$operations` exactly — identical to `consultant` (asserted by a test). |
| `administrative-manager` | `$operations` + employés (view+update), `users.view`, audit. |
| `marketing-manager` | Dashboard + centres/étudiants/inscriptions/groupes en lecture, **+ la gestion complète du stock** (articles, mouvements, catalogue des types) — le seul rôle qui la porte. |
| `teacher` | `dashboard.view`, `groups.view`, `students.view`, séances + appel. No finance. |

Ce qu'**aucun** rôle ne porte (Gate::before uniquement) : n'importe quel
`*.delete`, `employees.create`, `payments.update-date`, `expenses.approve`,
`system-settings.*`, `banks.*`, `cancellation-reasons.*`,
`cash-accounts.create/update/delete`, `groups.move-year`, `groups.reopen`,
`payments.reallocate`, `centers.access-all`.

### Rouvrir un groupe terminé ou annulé (`groups.reopen`)

Jusqu'au 01/09/2026 « Fin de formation » était **strictement irréversible** :
ni l'écran de détail, ni le formulaire d'édition, ni `transitionnerStatut()`
ne laissaient sortir de ce statut. Un « Terminer la formation » cliqué par
erreur sur un groupe de 26 étudiants n'avait donc qu'une seule issue — un
`UPDATE groups SET statut = …` en production, c'est-à-dire une modification
qui **échappe au journal d'audit** (raw SQL, aucun événement Eloquent).
Une correction intraçable est un plus mauvais compromis que la réouverture
elle-même : d'où `groups.reopen`.

La réouverture **ne touche que `statut`** (`Group::rouvrir()`), et la cible
ne peut être qu'un statut actif (« En formation » / « En inscription ») —
jamais l'autre statut terminal. Sont explicitement préservés :

- **l'argent** : aucun encaissement, aucune avance, aucun frais d'inscription
  n'est lu ni écrit (les enregistrements monétaires restent append-only,
  CLAUDE.md §11) ;
- les **inscriptions** et les **séances**, historique de présence compris ;
- `date_fin_formation`, qui reste la date de fin **prévue** du groupe : elle
  ne dépend pas du statut, et l'effacer réécrirait une donnée pédagogique ;
- le snapshot **`groups_historique`**, conservé tel quel — c'est la trace que
  la clôture a bien eu lieu, et `writeHistoriqueSnapshot()` le rafraîchira
  (`updateOrCreate`) à la prochaine clôture réelle.

« Annulée » se rouvre par le même chemin. Deux gardes en cohérence :
`GroupPolicy@reopen` et `GroupController@rouvrir`, qui refuse un groupe non
terminal.

⚠ La réouverture ne passe **que** par sa propre route. `transitionnerStatut()`
— le changement de statut incident de « Déplacer vers une autre année » —
continue de refuser toute sortie de « Fin de formation », pour tout le monde
y compris le super-admin : ce formulaire déplace des inscriptions, des séances
et des paiements, et rouvrir un groupe au passage serait une décision majeure
prise dans un écran qui parle d'autre chose. Rouvrir d'abord, déplacer
ensuite. Tests : `tests/Feature/Backoffice/Groups/GroupReopenTest.php` et
`GroupMoveYearTest::test_a_finished_group_never_leaves_fin_de_formation`.

### La date d'un encaissement (`payments.update-date`)

Déplacer `date_paiement` déplace la ligne dans le journal de caisse **et**
dans le récapitulatif annuel — potentiellement vers un mois déjà rapproché.
C'est le risque que l'audit du 27/08/2026 avait signalé sans le fermer ; il
l'est depuis le 30/08/2026, comme règle métier :

- `payments.update-date` est dans `superAdminOnly()` — aucun preset ne l'a.
- `EncaissementController@update` retire le champ de la requête sans la
  rejeter (le modal désactive l'input : une valeur qui arrive est un
  formulaire périmé ou une requête forgée).
- `UpdateEncaissementRequest` ne rend la date `required` que pour qui peut
  l'écrire.
- Tout titulaire de `payments.update` corrige toujours la note et l'identité
  du chèque.

⚠ `montant`, `caisse_id` et `methode` restent **gelés pour tout le monde,
super-admin compris** : ce sont des invariants monétaires (CLAUDE.md §11 —
la méthode a décidé quel compte a été crédité). Corriger une méthode = un
remboursement + un nouvel encaissement, jamais une édition.

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
