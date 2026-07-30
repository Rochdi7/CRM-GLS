# Phase 5 — Read-Only Backoffice Pages Inventory

Audit performed before any Phase 5 implementation. Every GET route in
`routes/backoffice.php` was checked against its controller/view to confirm
whether it is genuinely reachable and genuinely read-only. **A page is
eligible only if both are true.**

---

## Eligible pages (migrated this phase)

### 1. Groups historique index

| | |
|---|---|
| Route name | `backoffice.groups-historique.index` |
| URL | `GET /backoffice/groups-historique` |
| Controller/action | `GroupHistoriqueController::index()` |
| Current view | `resources/views/backoffice/groups-historique/index.blade.php` |
| Authorization | `$this->authorize('groups.view')` (explicit call, no policy binding) |
| Center-scoped? | ❌ No — `GroupHistorique::query()` has no center filter at all (verified: neither the controller nor the view scopes by `CurrentContext`) |
| Year-scoped? | ❌ No — same, no year filter |
| Pagination? | ✅ `->paginate(15)` |
| Filtering? | ❌ None currently |
| Media? | ❌ No |
| Financial data? | ❌ No |
| Mutation controls? | ❌ None — rows are inserted only by `Group::archiverCommeTermine()`, never from this page |
| **Phase 5 eligible** | **Yes** |
| Reason | Pure read, paginated, no mutation surface |

### 2. Student detail

| | |
|---|---|
| Route name | `backoffice.students.show` |
| URL | `GET /backoffice/students/{student}` |
| Controller/action | `StudentController::show()` |
| Current view | `resources/views/backoffice/students/show.blade.php` |
| Authorization | `$this->authorize('view', $student)` → `StudentPolicy` (permission + center via `ResourcePolicy::withinCenter`) |
| Center-scoped? | ✅ Yes, via policy |
| Year-scoped? | ❌ N/A (students aren't year-scoped) |
| Pagination? | N/A (single record; related inscriptions/payments lists are unpaginated — same as current behavior, preserved) |
| Filtering? | ❌ No |
| Media? | ✅ Profile photo (`getFirstMediaUrl('photo')`) |
| Financial data? | ✅ Payments total + list (read-only) |
| Mutation controls? | ❌ None on this page |
| **Phase 5 eligible** | **Yes** |
| Reason | Pure read, real route, policy-enforced, no mutation |

### 3. Employee detail

| | |
|---|---|
| Route name | *(none registered)* |
| Controller/action | `EmployeeController::show()` exists in code |
| Current view | `resources/views/backoffice/employees/show.blade.php` — **does not exist** (not checked further once the route was confirmed absent) |
| **Phase 5 eligible** | **No** |
| Reason | **Not reachable.** `routes/backoffice.php` registers only `employees.index` (Livewire `EmployeesIndex`) via `Route::get('employees', EmployeesIndex::class)`. No `employees/{employee}` route exists. `EmployeeController::show()` is dead code with no route pointing to it. Per the explicit instruction "do not invent missing show routes or pages," this is excluded. |

### 4. Group detail

| | |
|---|---|
| Route name | `backoffice.groups.show` |
| URL | `GET /backoffice/groups/{group}` |
| Controller/action | `GroupController::show()` |
| Current view | `resources/views/backoffice/groups/show.blade.php` |
| Authorization | `$this->authorize('view', $group)` → `GroupPolicy` |
| Center-scoped? | ✅ Yes |
| Year-scoped? | N/A (a specific group already has one year) |
| Pagination? | N/A (single record; enrolled-students table unpaginated, preserved as-is) |
| Filtering? | ❌ No |
| Media? | ❌ No |
| Financial data? | ✅ Assigned fees (read-only) |
| Mutation controls? | ⚠️ **Yes** — an "End training" (`Fin de formation`) archive `<form>` in the page-header actions slot, gated by `@can('archive', $group)`, POSTing to `backoffice.groups.archive` |
| **Phase 5 eligible** | **Yes, with a carve-out** | The `show()` action itself is migrated; the archive **form is preserved as a plain HTML form** posting to the existing (unchanged) `backoffice.groups.archive` Blade/Livewire-era route — not converted to an Inertia/React mutation, exactly per the task's explicit instruction ("preserve its visibility and destination... do not migrate the mutation workflow in this phase") |

### 5. Inscription detail

| | |
|---|---|
| Route name | `backoffice.inscriptions.show` |
| URL | `GET /backoffice/inscriptions/{inscription}` |
| Controller/action | `InscriptionController::show()` |
| Current view | `resources/views/backoffice/inscriptions/show.blade.php` |
| Authorization | `$this->authorize('view', $inscription)` → `InscriptionPolicy` |
| Center-scoped? | ✅ Yes |
| Year-scoped? | N/A (a specific inscription already has one year) |
| Pagination? | N/A (fee-lines table unpaginated, preserved) |
| Filtering? | ❌ No |
| Media? | ❌ No |
| Financial data? | ✅ Fee lines, totals due/paid/remaining — all computed server-side (`$inscription->fees->sum(...)`), never recalculated in React |
| Mutation controls? | ❌ None on this page |
| **Phase 5 eligible** | **Yes** |
| Reason | Pure read, real route, policy-enforced |

### 6. Caisse detail

| | |
|---|---|
| Route name | `backoffice.caisses.show` |
| URL | `GET /backoffice/caisses/{caisse}` |
| Controller/action | `CaisseController::show()` (one action of a full resource controller — see note below) |
| Current view | `resources/views/backoffice/caisses/show.blade.php` |
| Authorization | Implicit via `$this->authorizeResource(Caisse::class, 'caisse')` in the constructor → Laravel resource-controller convention maps `show` to `CaissePolicy::view()` (permission + center) |
| Center-scoped? | ✅ Yes, via policy |
| Year-scoped? | N/A |
| Pagination? | ❌ No — the view's own inline queries (`$caisse->encaissements()->...->limit(10)->get()`, same for `depenses`/`remboursements`/transfers) are **hardcoded to the last 10 rows each**, not paginated. Preserved exactly (see note below) |
| Filtering? | ❌ No |
| Media? | ❌ No |
| Financial data? | ✅ Balance + 4 recent-movement lists (all read-only) |
| Mutation controls? | ❌ None on this page — `create`/`store`/`edit`/`update`/`destroy` exist on the controller but **have no registered routes** (dead code; the real Caisse CRUD is the Livewire tabbed "Gestion de la caisse" page, per CLAUDE.md §11) |
| **Phase 5 eligible** | **Yes** |
| Reason | Pure read, real reachable route; the sibling mutation methods on the same controller class are unreachable dead code, not part of this page |

**Note on the Blade view's own inline queries**: unusually, `caisses/show.blade.php` runs its own `@php` block querying `$caisse->encaissements()`/`depenses()`/`remboursements()`/`CaisseTransfer::query()` directly in the view (documented in the view's own comment: "the controller stays untouched"). This logic moves into the new read-model class (`GetCaisseDetails`), not the controller, preserving the "controller stays thin" intent while eliminating the Blade-embedded query.

### 7. Encaissement detail

| | |
|---|---|
| Route name | `backoffice.encaissements.show` |
| URL | `GET /backoffice/encaissements/{encaissement}` |
| Controller/action | `EncaissementController::show()` (one action of a full resource controller — same dead-code pattern as Caisse) |
| Current view | `resources/views/backoffice/encaissements/show.blade.php` |
| Authorization | Implicit via `authorizeResource(Encaissement::class, 'encaissement')` → `EncaissementPolicy::view()` (permission + center via the till) |
| Center-scoped? | ✅ Yes (via `caisse.etablissement_id`) |
| Financial data? | ✅ Amount, fee due/paid/remaining, cheque details when `methode = Chèque` |
| Mutation controls? | ❌ None on this page. No destroy route exists anywhere for Encaissement (money records are never deleted) |
| **Phase 5 eligible** | **Yes** |

### 8. Dépense detail

| | |
|---|---|
| Route name | `backoffice.depenses.show` |
| URL | `GET /backoffice/depenses/{depense}` |
| Controller/action | `DepenseController::show()` (same dead-code resource-controller pattern) |
| Current view | `resources/views/backoffice/depenses/show.blade.php` |
| Authorization | Implicit via `authorizeResource(Depense::class, 'depense')` → `DepensePolicy::view()` (permission + center via the till) |
| Center-scoped? | ✅ Yes |
| Media? | ✅ Receipts gallery (`$depense->getMedia('justificatifs')`) — images inline, PDFs as an icon link, both opening the real media URL in a new tab |
| Financial data? | ✅ Amount |
| Mutation controls? | ❌ None — the `@can('update', $depense)` block only renders a "Back to list" **link**, not an edit control |
| **Phase 5 eligible** | **Yes** |

### 9. Remboursement detail

| | |
|---|---|
| Route name | *(none registered)* |
| Controller/action | `RemboursementController::show()` exists in code |
| Current view | `resources/views/backoffice/remboursements/` — **directory does not exist** |
| **Phase 5 eligible** | **No** |
| Reason | **Not reachable.** No `remboursements/{remboursement}` route is registered (only the `remboursements.index` redirect into the Livewire tabbed Dépenses page exists). `RemboursementController` is not even referenced in `routes/backoffice.php`. Excluded per the same "don't invent missing pages" rule as Employee. |

### 10. Caisse transfer detail

| | |
|---|---|
| Route name | `backoffice.caisse-transfers.show` |
| URL | `GET /backoffice/caisse-transfers/{caisse_transfer}` |
| Controller/action | `CaisseTransferController::show()` (same dead-code resource-controller pattern — `store`/`update`/`validate` are separately reachable, see below) |
| Current view | `resources/views/backoffice/caisse-transfers/show.blade.php` |
| Authorization | Implicit via `authorizeResource(CaisseTransfer::class, 'caisse_transfer')` → `CaisseTransferPolicy::view()` (permission + center via the source till) |
| Center-scoped? | ✅ Yes |
| Financial data? | ✅ Amount, before/after balance snapshots for both tills |
| Mutation controls? | ❌ None **on this specific page** — no validate/cancel button in the show view; the two-step workflow's actual controls live on the Livewire `caisse-transfers.index` tab (per the view's own comment) |
| **Phase 5 eligible** | **Yes** |
| Reason | Pure read; `store`/`update`/`validate` are real, reachable, state-changing routes on the same controller class but are **separate URLs** (`POST caisse-transfers`, `PUT/PATCH .../{id}`, `POST .../{id}/validate`) — none are touched, only `show()` migrates |

---

## Pages audited but excluded

| Candidate | Reason excluded |
|---|---|
| Employee detail | No route exists (`EmployeeController::show()` is dead code) |
| Remboursement detail | No route exists, no view directory exists |
| Caisse index (`caisses.index`) | List page with a create-adjacent architecture (full resource controller); CLAUDE.md identifies the Livewire tabbed "Gestion de la caisse" page as the actual primary UI — this Blade `index()` is itself a secondary/legacy surface with no registered route (only `caisses.show`/`caisses.index`'s Livewire equivalent is reachable in practice per the route file) |
| Encaissements/Depenses/Remboursements/CaisseTransfers `index()` | Same — dead code on the resource controllers, real index pages are the Livewire tabbed pages, explicitly out of scope for Phase 5 (CRUD index pages excluded per the task) |
| Group **archive action** | Real, reachable, state-changing (`backoffice.groups.archive`) — explicitly out of scope; preserved as a plain form on the migrated `groups.show` page, not converted |
| CaisseTransfer **validate/update actions** | Real, reachable, state-changing — explicitly out of scope; no button for these exists on the `show` page itself, so no carve-out was even needed there |

---

## Summary: pages migrated this phase

1. `backoffice.groups-historique.index`
2. `backoffice.students.show`
3. `backoffice.groups.show` (archive form preserved as-is)
4. `backoffice.inscriptions.show`
5. `backoffice.caisses.show`
6. `backoffice.encaissements.show`
7. `backoffice.depenses.show`
8. `backoffice.caisse-transfers.show`

**8 pages migrated. 2 candidates excluded as unreachable** (Employee, Remboursement detail).
