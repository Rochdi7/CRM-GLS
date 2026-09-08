# Phase 6 — Simple CRUD Modules Inventory

Audit of the five candidate modules before any implementation, per the Phase 6
task. Source of truth for what actually exists — no field/behavior below is
assumed; every row was read from the model, policy, Form Request, Livewire
component, and (where one exists) the route file / resource controller.

---

## 1. Établissements (Centers)

| Aspect | Current behavior |
|---|---|
| Route names | `backoffice.etablissements.{index,create,store,show,edit,update,destroy}` — full `Route::resource`, all 7 actions registered |
| Controller ownership | `EtablissementController` — `index`/`create`/`show`/`edit` all redirect to `backoffice.settings` (no view of their own); `store`/`update`/`destroy` are live, policy-gated via `authorizeResource` |
| Livewire ownership | `App\Livewire\Backoffice\Settings\EtablissementsTab` — the actual UI (inline modal, not a plugin modal), paginated 8/page, full CRUD |
| Modal fields | `nom_centre` (string, required, max150), `ville` (string, required, max100), `telephone` (via `WithPhoneCountry` — national part + shared `phonePays` selector, combined to `+212...` on save), `email` (nullable email max255), `siege_social` (bool) |
| Validation rules | `#[Validate]` attributes + `WithPhoneCountry::phonePaysRules()`; no uniqueness constraint on `nom_centre` today |
| Policy permissions | `centers.view/create/update/delete` (`EtablissementPolicy` — no `access-all` distinction at policy level; `centers.access-all` only gates directory-wide data elsewhere, not this CRUD) |
| Center scoping | None — `EtablissementPolicy::centerId()` returns `null` always; centers are the scoping unit itself, not scoped by it |
| Academic-year scoping | None |
| Delete behavior | Guarded in Livewire (not DB-first): blocks if `salles_count \|\| employees_count \|\| students_count` > 0, adds a form error; DB-level restrict FKs are the backstop |
| Protected rows | None — no "siège social can't be deleted" rule found; any center can be deleted once empty |
| Relations | `hasMany` salles/employees/students/groups/inscriptions/caisses |
| Filters | None — no search box, no filter-bar |
| Pagination | `paginate(8)`, ordered `siege_social DESC, nom_centre ASC` (head office pinned first) |
| Eligible for Phase 6 | **Yes** |
| Migration risks | Low. Phone-country combo field needs a dedicated `PhoneField` component (not in the task's suggested component list) — new, small, reusable. |

---

## 2. Années scolaires (Academic Years)

| Aspect | Current behavior |
|---|---|
| Route names | `backoffice.annees-scolaires.{index,create,store,edit,update,destroy}` — **no `show`** (`->except(['show'])` in routes/backoffice.php) |
| Controller ownership | `AnneeScolaireController` — `index`/`create`/`edit` redirect to Settings; `store`/`update`/`destroy` live, policy-gated; `store`/`update` wrap the single-default invariant in `DB::transaction` |
| Livewire ownership | `App\Livewire\Backoffice\Settings\AnneesScolairesTab` — the actual UI |
| Modal fields | `nom` (string, required, max20, unique ignoring self), `date_debut` (date, required), `date_fin` (date, required, `after:date_debut`), `par_defaut` (bool), `inscription_ouverte` (bool, defaults true on new) |
| Validation rules | Livewire `rules()` — uniqueness on `nom`, `date_fin` must be after `date_debut` |
| Policy permissions | `academic-years.view/create/update/delete` (no delete found on the Livewire component's own gating beyond policy — `delete()` method calls `$this->authorize('academic-years.delete')`) |
| Center scoping | None — `AnneeScolairePolicy::centerId()` returns `null` (global reference data) |
| Academic-year scoping | N/A — this IS the academic-year catalog |
| Delete behavior | Guarded in Livewire: blocks if `groups_count \|\| inscriptions_count` > 0 |
| Protected rows | **Single-default invariant**: setting `par_defaut = true` on any row unsets it on every other row, inside one `DB::transaction` (both in the controller's `persist()` helper AND independently in the Livewire `save()` — same logic duplicated, not shared) |
| Relations | `hasMany` groups, inscriptions (`annee_scolaire_id`) |
| Filters | None |
| Pagination | `paginate(8)`, ordered `date_debut DESC` |
| Eligible for Phase 6 | **Yes** |
| Migration risks | Medium-low. The single-default transaction must move to the Inertia controller's `store`/`update` — must not be reimplemented in React. `CurrentContext` (session-backed active year) is read elsewhere in the app; changing which year is `par_defaut` does not itself change a user's *selected* year, but worth a manual check that switching defaults doesn't surprise the context switcher. |

---

## 3. Salles (Rooms)

| Aspect | Current behavior |
|---|---|
| Route names | `backoffice.salles.{index,create,store,edit,update,destroy}` (`->except(['show'])`) |
| Controller ownership | `SalleController` — `index`/`create`/`edit` redirect to Settings; `store`/`update`/`destroy` live, policy-gated |
| Livewire ownership | `App\Livewire\Backoffice\Settings\SallesTab` — the actual UI, uses `WithCenterContext` to narrow the list to the active top-bar center |
| Modal fields | `nom` (string, required, max100), `etablissement_id` (required, `exists:etablissements,id`), `capacite` (nullable int, min1), `statut` (required, in `Salle::STATUTS` = Active/Inactive) |
| Validation rules | Livewire `rules()` — no per-center uniqueness on room name (two centers can both have "Salle 01"; a center itself can also have duplicate room names — no uniqueness constraint at all) |
| Policy permissions | `rooms.view/create/update/delete` (`SallePolicy` extends `ResourcePolicy` with **no override** — inherits center scoping via `etablissement_id`) |
| Center scoping | **Yes, on view/update/delete** — `ResourcePolicy::withinCenter()` checks `CenterAccessService::canAccessCenter($user, $salle->etablissement_id)`. **Gap found**: `ResourcePolicy::create()` has NO center check at all (only `$user->can('rooms.create')`) — a center-limited user could submit a forged `etablissement_id` for a center they don't have access to and `StoreSalleRequest`/the Livewire `save()` would accept it (only `exists:etablissements,id` is validated, not "is this MY center"). **This gap already exists today, pre-migration** — not something Phase 6 introduces, but worth flagging since Phase 6 must decide whether to preserve or fix it (see Question 3 below). |
| Academic-year scoping | None |
| Delete behavior | Guarded in Livewire: blocks if `groups_count` > 0 |
| Protected rows | None |
| Relations | `belongsTo` etablissement; `hasMany` groups |
| Filters | Implicit center filter via `WithCenterContext::scopeToActiveCenter()` (top-bar switcher, not a page-level filter control) — narrows to selected center + NULL-center rows (Salle rows are never NULL-center in practice since `etablissement_id` is required) |
| Pagination | `paginate(8)`, ordered `nom ASC`, eager-loads `etablissement` |
| Eligible for Phase 6 | **Yes**, with the center-authorization gap flagged for a decision (§ Questions) |
| Migration risks | Medium. The "narrow to active context center" behavior must be reproduced server-side in the Inertia controller (read `CurrentContext` there, not trust a client-sent center id for the *filter*), while `etablissement_id` in the **create/update payload** still needs the create-time center check decided. |

---

## 4. Frais (Fee catalog)

| Aspect | Current behavior |
|---|---|
| Route names | **None** — no `Route::resource`, no controller, no Form Requests exist for Frais at all |
| Controller ownership | **None** |
| Livewire ownership | `App\Livewire\Backoffice\Settings\FraisTab` — 100% of CRUD lives here; `save()`/`delete()` call `Frais::updateOrCreate`/`delete()` directly, no domain action |
| Modal fields | `nom` (string, required, max150, unique ignoring self), `statut` (required, in `Frais::STATUTS` = Actif/Inactif) |
| Validation rules | Livewire `rules()` |
| Policy permissions | `fees.view/create/update/delete` via `Gate`-style `authorize('viewAny'/'create'/'update'/'delete', Frais::class \| $frais)` — note this tab uses **policy ability names** (`viewAny`/`update`) directly rather than permission strings like the other four tabs, but `FraisPolicy extends ResourcePolicy` so it resolves to the same `fees.*` permissions underneath |
| Center scoping | None — `FraisPolicy::centerId()` returns `null` (global catalog) |
| Academic-year scoping | None |
| Delete behavior | Guarded in Livewire: blocks if `groups_count` (via `group_frais` pivot) > 0 |
| Protected rows | None found — no `is_system`-style lock on any Frais row |
| Relations | `belongsToMany` Group via `group_frais` pivot, `withPivot('montant', 'date_echeance', 'classification')` |
| Filters | None |
| Pagination | `paginate(8)`, ordered `nom ASC`, `withCount('groups')` |
| Eligible for Phase 6 | **Yes**, but see Question 1 — this module currently has zero HTTP-layer surface (no routes/controllers/Form Requests) to "preserve"; Phase 6 must **create** that surface from scratch (route names, controller, Store/Update Requests), which is a bigger lift than the other four modules where the resource routes/controllers already exist and only need a new Inertia-returning `index` action. |
| Migration risks | Medium — this is the module most explicitly flagged by the task's own stop-condition list ("Frais behavior is more financially sensitive than expected"). Per `CLAUDE.md` §11, Frais feeds the Groups → Inscriptions fee chain (`group_frais` pivot, `InscriptionFee::computeMontant()`); this CRUD tab itself only touches the catalog (name/status), never amounts/due-dates/enrollment math — those live on `group_frais` (Groups module, out of scope) and `inscription_fees` (Registrations module, out of scope). Confirmed the catalog CRUD itself is financially inert (no montant field on `Frais` — `montant_defaut` was already removed per CLAUDE.md's explicit note not to reintroduce it). |

---

## 5. Types de dépenses (Expense Types)

| Aspect | Current behavior |
|---|---|
| Route names | **`backoffice.types-depenses.index` is a redirect closure** to `backoffice.depenses.index?tab=types` (routes/backoffice.php:172) — confirmed via `artisan route:list --name=types-depenses` showing only 1 route, GET, no store/update/destroy routes registered anywhere |
| Controller ownership | `TypeDepenseController` exists (`index`/`create`/`store`/`edit`/`update`/`destroy`, full `authorizeResource`) but **is entirely dead code — zero routes reference it**. Confirmed via `route:list`. |
| Livewire ownership | `App\Livewire\Backoffice\TypesDepenses\TypesDepensesIndex` — rendered as the **third tab** of `backoffice/depenses/index.blade.php` (`DepenseManagementController`), alongside the `depenses` tab (Livewire `DepensesIndex`) and `remboursements` tab (Livewire `RemboursementsIndex`) — **both explicitly out of scope for Phase 6** |
| Modal fields | `nom` (string, required, max100, unique ignoring self), `statut` (required, in `TypeDepense::STATUTS`) |
| Validation rules | Livewire `rules()`; `is_system` is never a form field — hardcoded `false` on create |
| Policy permissions | `expense-types.view/create/update/delete` |
| Center scoping | None — `TypeDepensePolicy::centerId()` returns `null` (global catalog) |
| Academic-year scoping | None |
| Delete behavior | Guarded: blocks if `depenses_count` > 0 |
| Protected rows | **`is_system` rows are locked** — `TypeDepensePolicy::update()`/`delete()` explicitly return `false` when `is_system` is true (even for super-admin-adjacent permission holders — though `Gate::before` still bypasses for actual super-admins, tested explicitly in `test_a_system_expense_type_cannot_be_edited_even_by_a_super_admin`... **wait, re-reading**: that test name says super-admin CANNOT edit it, meaning `Gate::before`'s super-admin bypass must NOT apply here, OR the component's own `guardSystemType()` `abort_if` runs before the policy and blocks even super-admin. Confirmed: `TypesDepensesIndex::guardSystemType()` is an unconditional `abort_if($type->is_system, 403, ...)` called BEFORE `$this->authorize(...)` in `edit`/`save`/`delete` — this explicit guard is what stops super-admin, not the policy. This must be preserved exactly: an Inertia controller must call the equivalent unconditional guard before any policy check, not rely on the policy alone. |
| Relations | `hasMany` depenses (`type_depense_id`) |
| Filters | **Search box** (`search` property, debounced via `updatingSearch()` → `resetPage()`), `ilike` on `nom` |
| Pagination | `paginate($this->perPage)` via `WithPerPage` trait (10/25/50/100 selectable), ordered `is_system DESC, nom ASC` (system rows pinned first) |
| Eligible for Phase 6 | **Conditionally — see Question 2.** The component itself is a clean, self-contained CRUD list with search+pagination+lock-badge, structurally ready to migrate. But its current *URL and embedding* (tab 3 of a 3-tab Livewire page that also hosts two out-of-scope Finance mutation modules) does not cleanly separate from Depenses/Remboursements without either (a) changing its route/URL, which the task's own goal #2 says to avoid "wherever practical," or (b) migrating it in place inside a page where the other two tabs stay Livewire — which the task's Settings-page precedent (`?tab=` pattern, React owns tab state, Laravel returns only that tab's data) actually already describes as the *preferred* solution, just applied to a page it didn't explicitly scope out. |
| Migration risks | Low on the component itself; the only open question is architectural (see below), not behavioral. |

---

## Questions surfaced by the audit (stop-and-report, per task's own stop conditions)

**Question 1 — Frais has no HTTP-layer surface today.** Store/Update Form
Requests, a controller, and route names must all be *created* net-new (not
"preserved") to give it an Inertia-compatible mutation surface. This is more
scaffolding than Établissements/Années/Salles (which already have working
resource controllers to convert). Proceeding under the assumption this is
still in-scope — the task listed Frais as module #4 explicitly — but flagging
that "preserve existing route names" doesn't apply here; new names must be
chosen (proposed: `backoffice.frais.{index,store,update,destroy}`, mirroring
the others, with `index` folded into the Settings `?tab=frais` panel like the
other three, not a standalone page).

**Question 2 — Types de dépenses is embedded inside the Depenses tabbed
page, whose other two tabs (Depenses, Remboursements) are explicitly
out-of-scope for Phase 6.** Three options, in order of preference given the
task's own precedent for Settings:
  - **(A)** Treat `backoffice.depenses.index` the same way as Settings:
    ONE Inertia page, `?tab=depenses|remboursements|types`, but only the
    `types` tab's *content* becomes a real React CRUD panel this phase —
    the other two tabs render a thin "still on Livewire" placeholder... this
    is awkward because Inertia and Livewire cannot easily coexist as sibling
    tab panes of the same page render without one of them being an iframe or
    a second page.
  - **(B)** Give Types de dépenses its own new URL
    (e.g. `backoffice.types-depenses.index` stops being a redirect and
    becomes a real Inertia page) and leave the legacy
    `backoffice.depenses.index?tab=types` deep-link redirecting to the NEW
    page instead of rendering the Livewire tab — the two other tabs
    (Depenses, Remboursements) keep their current 2-tab-only Livewire page,
    dropping the third tab entirely from that view.
  - **(C)** Exclude Types de dépenses from Phase 6 entirely and revisit it
    together with Depenses/Remboursements in the Finance phase, since its
    current architecture is physically joined to two out-of-scope modules.

This wasn't discoverable before opening the actual view file — recommend
**(B)** as the least architecturally disruptive (one page gets a clean new
identity, the other two keep working exactly as today, no mixed
Inertia/Livewire tab panes), but this changes a route's meaning
(`backoffice.types-depenses.index` stops redirecting) and removes a tab from
`backoffice.depenses.index`, which is a user-visible URL/behavior change
worth explicit confirmation before writing any code.

**Question 3 — Pre-existing `Salle` create-time center-authorization gap.**
`ResourcePolicy::create()` never checks `withinCenter()` — only
`view`/`update`/`delete` do. A center-limited user (no `centers.access-all`)
holding `rooms.create` could submit a `store` request with an
`etablissement_id` for a center they cannot otherwise access, and neither the
Form Request nor the policy would reject it (only `exists:etablissements,id`
is checked). This is **pre-existing behavior, not introduced by Phase 6** —
confirmed present in today's Livewire `SallesTab::create()`/`save()` too (no
extra check there either). Options: (a) preserve exactly as-is (silently
carry the same gap into the Inertia version, since "fixing" it is arguably
out of this migration phase's scope), or (b) fix it as a small, clearly-
labeled security improvement alongside the migration (restrict the
`etablissement_id` options list server-side to centers the user can access,
and re-validate server-side on submit). Recommend **(b)** since Phase 6 is
already building the options-list prop for the create/edit form fresh — it
costs nothing extra to scope it correctly from day one, whereas preserving a
known gap on a brand-new code path feels wrong to knowingly ship. Awaiting
direction before choosing.

---

## Decisions (confirmed by stakeholder before implementation)

- **Q1 (Frais routes)**: Create new `backoffice.frais.{index,store,update,destroy}`
  routes/controller/Form Requests. `index` folds into the Settings `?tab=frais`
  panel like the other three referential modules, not a standalone page.
- **Q2 (Types de dépenses)**: Give it its own new Inertia page.
  `backoffice.types-depenses.index` stops being a redirect closure and becomes
  a real Inertia CRUD page (own controller, own routes). The legacy
  `backoffice.depenses.index?tab=types` deep-link now redirects to
  `backoffice.types-depenses.index` instead of rendering the Livewire tab.
  `backoffice/depenses/index.blade.php` drops its third tab and becomes a
  2-tab-only page (Depenses, Remboursements) — both stay Livewire, untouched
  this phase.
- **Q3 (Salle center gap)**: Fix it now. The Inertia `Salle` create/update
  path restricts the `etablissement_id` options list server-side to centers
  the acting user can access (via `CenterAccessService`), and the
  controller/Form Request re-validates the submitted `etablissement_id`
  against that same allowed set on every store/update — not just
  `exists:etablissements,id`. This is a small, intentional security
  improvement scoped to the new code path only; the old Livewire `SallesTab`
  is left exactly as it was (not retroactively patched, since it's being
  retired, not maintained).

## Summary eligibility table

| Module | Eligible | Resolution |
|---|---|---|
| Établissements | Yes | No open question |
| Années scolaires | Yes | No open question |
| Salles | Yes | Q3 — center-access check added to create/update |
| Frais | Yes | Q1 — new `backoffice.frais.*` routes created |
| Types de dépenses | Yes | Q2 — own new Inertia page, drops out of the Depenses tab set |
