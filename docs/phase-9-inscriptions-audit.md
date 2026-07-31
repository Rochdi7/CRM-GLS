# Phase 9 — Inscriptions Complete Audit

Full audit of the Inscriptions (registrations/enrollments) module before any
implementation, per the task's explicit "audit first, no code" requirement.
Every fact below was read directly from the current codebase — routes,
controllers, policies, Form Requests, models, Blade view, and existing
tests — not assumed or inferred from documentation alone.

This is the most business-critical module migrated so far: it creates
students inline, bills group fees with per-line discounts, and derives
financial totals. Every rule below must be preserved exactly.

---

## 1. Routes

```
GET|HEAD  backoffice/inscriptions               backoffice.inscriptions.index    → InscriptionsIndex (Livewire)
GET|HEAD  backoffice/inscriptions/{inscription}  backoffice.inscriptions.show     → InscriptionController@show (Inertia, Phase 5)
POST      backoffice/inscription-fees            backoffice.inscription-fees.store    → InscriptionFeeController@store
PUT|PATCH backoffice/inscription-fees/{id}        backoffice.inscription-fees.update   → InscriptionFeeController@update
DELETE    backoffice/inscription-fees/{id}        backoffice.inscription-fees.destroy  → InscriptionFeeController@destroy
```

**No `store`/`update`/`destroy` route exists for `inscriptions` itself** —
all Inscription mutations run over Livewire's own AJAX protocol, exactly
the pre-Phase-8 pattern for Students/Groups.

**⚠ `inscription-fees.*` routes are registered but genuinely dead code** —
confirmed via `grep` across `resources/views` and `resources/js`: zero
references anywhere. `InscriptionFeeController` exists, is fully wired with
`authorizeResource`, and its Form Requests validate real fields — but
nothing in the current UI ever calls these endpoints. Fee lines are
created/edited/deleted exclusively as part of the parent
`InscriptionsIndex::save()` transaction (create-only — **fee lines are
never editable when editing an existing inscription**, see §4). Left alone,
not touched by this phase unless a future decision explicitly re-purposes
them.

---

## 2. Controllers

- `InscriptionController::show()` — Phase 5, Inertia, read-only. Untouched
  by this phase.
- `InscriptionFeeController` — full CRUD (`store`/`update`/`destroy`),
  `authorizeResource(InscriptionFee::class, 'inscription_fee')` in
  constructor. Dead code (§1). Left alone.
- **No controller exists yet for Inscription list/create/update/delete** —
  Phase 9's job.

---

## 3. Policies

`InscriptionPolicy extends ResourcePolicy` — no overrides, standard
`registrations.{view,create,update,delete}` + `withinCenter()` center
scoping on view/update/delete (inherited, unchanged since Phase 0).

`InscriptionFeePolicy` — **not a `ResourcePolicy` subclass**, hand-written:
- `create(User $user): bool` → `$user->can('registrations.manage-fees')`
  (no center check — matches `ResourcePolicy::create()`'s own gap pattern
  found in Phase 6, not something to "fix" here since this policy's actual
  callers are the dead `InscriptionFeeController` routes, not the live
  fee-line flow)
- `update`/`delete` → same permission + `withinCenter()`, resolving the fee's
  center via `$fee->inscription?->etablissement_id` (indirect, through the
  parent inscription — not stored on the fee row itself)

`registrations.manage-fees` is a distinct permission from
`registrations.{view,create,update,delete}` (confirmed in
`PermissionRegistry`) — **but the live `InscriptionsIndex::save()` fee-line
creation path (the one thing that actually persists fee lines today) never
checks `registrations.manage-fees` at all** — it only checks
`registrations.create`/`registrations.update` (the parent Inscription's own
gate). `registrations.manage-fees` is currently enforced ONLY by the dead
`InscriptionFeeController`/`InscriptionFeePolicy` path. This is existing
behavior, not a gap to close in this phase (see §12 "Behavior worth
flagging").

---

## 4. Livewire component (`InscriptionsIndex`) — full behavior map

### 4.1 List (`render()`)

- Eager loads: `student`, `group`; `withCount('fees')`.
- Center scoping: `CenterAccessService::scopeAccessibleCenters()` +
  `WithCenterContext::scopeToActiveCenter()` (top-bar center).
- **Year scoping**: `when($context->anneeScolaireId(), ...)` — narrows to
  the active top-bar academic year. (Students has no year scoping; Groups
  does; Inscriptions does too, inherited from its `annee_scolaire_id`
  column.)
- Status filter (`statutFilter`, select2, all `Inscription::STATUTS`).
- Search: `reference` ilike OR `student.nom`/`student.prenom` ilike
  (via `orWhereHas`).
- Ordering: `latest()`.
- Pagination: `WithPerPage` (10/25/50/100).
- Columns: Reference, Student (name, link via `nomComplet()`), Group
  (`nom`), Date (`date_inscription`, d/m/Y), Total (`montant_total`,
  `number_format(...,2).' MAD'` or `—`), Fees (count badge), Status badge,
  Action menu (view→show page, edit if `can('update')`, delete if
  `can('delete')`).

### 4.2 Modal — two structurally different flows: **create** vs **edit**

**On edit** (`edit(int $id)`): loads `student_id`, `group_id`, `statut`,
`date_inscription`, `date_debut`, `date_fin`, `note` only. **Fee lines are
NEVER loaded or shown when editing** — confirmed in the Blade view,
`@unless ($editingId)` wraps the entire "Frais disponibles" block. Editing
an inscription can only change: student (via select, if in edit mode — see
below), group, status, registration date, note. It **cannot** touch fees,
totals, or the group-derived date_debut/date_fin (those inputs are
`readonly` and, more importantly, re-derived server-side on save
regardless of what's submitted — see §4.4).

**On create**: two sub-modes via `inscriptionMode` (`'new'` default vs
`'existing'`, a plain native `<select>`, not Select2 — a documented,
deliberate choice per the Blade comment: "Kept plain and Livewire-owned...
to avoid the jQuery-Select2-vs-Livewire-morph DOM-ownership conflicts").

- **`'existing'`**: `student_id` required, picked via Select2
  (`search="always"`), options = `students()` computed property (center +
  active-center scoped, no year scoping — students have no
  `annee_scolaire_id`).
- **`'new'`**: no `student_id` — instead a full inline student form:
  `new_nom`/`new_prenom` (required), `new_sexe` (radio), `new_date_naissance`
  (`before:today`), `new_cin`, `new_niveau` (CEFR + German tracks, live —
  drives conditional `new_domaine`/`new_examen_type` exactly like
  `StudentsIndex`), Contact tab (`new_email`, `new_telephone`/`new_whatsapp`
  via shared `WithPhoneCountry`, `new_adresse`), Parent tab (`new_parent_*`
  — same 6-field set as the Student modal).

Both modes share: Group (`group_id`, Select2, `live`, required, options =
`groups()` computed — center + active-center + **active academic year**
scoped), registration date (`date_inscription`, required).

**Status field**: only rendered/editable in edit mode (`@if ($editingId)`).
New registrations always start `Active` — enforced BOTH by hiding the field
AND by the server hardcoding `'statut' => Inscription::STATUT_ACTIVE` on
create regardless of any tampered client value (confirmed by
`test_a_registration_bills_the_selected_group_fees_with_discount`, which
explicitly sets `statut = 'Annulée'` on create and asserts the persisted
row is still `Active`).

**Date fields on create**: `date_debut`/`date_fin` inputs are
`readonly` and populated from `updatedGroupId()` (see §4.3) purely for
display — the actual values used on `save()` are **re-derived from the
group directly** (`$group?->date_debut_formation?->toDateString()`), never
taken from the submitted (`$this->date_debut`/`$this->date_fin`) values —
confirmed by `test_a_tampered_date_is_ignored_and_the_group_date_wins`.

### 4.3 Group selection → fee-line loading (`updatedGroupId()` → `loadGroupFees()`)

Triggered live when `group_id` changes (create mode only — the Blade block
that renders this is `@unless ($editingId)`).

1. Resets `feeLines = []`, `date_debut = null`, `date_fin = null`.
2. If no group selected, stop (empty fee lines).
3. Loads the group with **only ACTIVE catalog fees**
   (`Group::with(['frais' => fn ($q) => $q->where('statut', Frais::STATUT_ACTIF)])`)
   — an inactive catalog fee assigned to the group is silently excluded
   from "Frais disponibles", confirmed by
   `test_only_active_catalog_fees_are_available`.
4. Sets `date_debut`/`date_fin` from the group's own training dates
   (display only, re-derived again on save — see §4.2).
5. Builds one `feeLines[]` entry per active assigned fee — **no checkbox,
   every fee the group carries becomes a line**:
   - `frais_id`, `nom` (copied from the catalog fee)
   - `montant_initial` = the group's **per-group** pivot `montant` (not the
     catalog's own amount — group_frais.montant, confirmed
     `test_selecting_a_group_loads_its_assigned_fees`)
   - `remise_pct` / `remise_montant` = both empty strings (no discount by
     default)
   - `note` = empty string
   - `date_echeance` = the group's per-fee pivot `date_echeance`, **falling
     back to today's date** if the group didn't set one
     (`$frais->pivot->date_echeance ?: now()->toDateString()`)

### 4.4 Live discount two-way sync (`updated(string $property)`)

Regex-matched on any `feeLines.{i}.{remise_pct|remise_montant|montant_initial}`
property change (Livewire's generic `updated()` hook, debounced 400ms in
the Blade via `wire:model.live.debounce.400ms`):

- Editing **`remise_pct`**: clamped to `[0, 100]`, then
  `remise_montant = round(initial * pct / 100, 2)` — unless `remise_pct`
  was cleared to `''`, in which case `remise_montant` is also cleared to
  `''` (not recomputed to 0).
- Editing **`remise_montant`**: clamped to `[0, initial]`, then
  `remise_pct = round(dh / initial * 100, 2)` — same empty-string
  passthrough.
- Editing **`montant_initial`**: if a `remise_pct` is currently set,
  `remise_montant` is recomputed from the NEW initial amount at the SAME
  percentage (percentage is "sticky" across initial-amount edits; a fixed
  DH discount is NOT recomputed when the initial amount changes — it stays
  whatever DH value was typed).
- No-op entirely if `$initial <= 0`.

**Live display-only final amount**: `lineMontant(int $index)` — calls
`InscriptionFee::computeMontant()` (see §5) purely for the Blade's live
"Amount" column and running total footer; never persisted directly,
recomputed again at `save()` time from the same inputs.

### 4.5 Final calculation (`InscriptionFee::computeMontant()`)

```php
public static function computeMontant(float $initial, ?float $remisePct, ?float $remiseMontant): float
{
    if ($remisePct !== null && $remisePct > 0) {
        return round($initial * (1 - min($remisePct, 100) / 100), 2);
    }
    return round(max(0, $initial - (float) ($remiseMontant ?? 0)), 2);
}
```

**Percentage discount takes priority over fixed-DH discount** — if
`remisePct` is set AND `> 0`, the fixed `remiseMontant` is ignored entirely
for the *final amount calculation* (even though the UI's two-way sync keeps
both fields populated in tandem — see §4.4, they're kept in sync for
display/UX but only one path is actually used at compute time). Floors at
0 (`max(0, ...)`), rounds to 2 decimals. **This is the single source of
truth for "montant" on every fee line, called identically in the live
Blade preview (`lineMontant()`) and in the real `save()` transaction** — no
drift between preview and persisted value.

### 4.6 Save — create (`save()`, `!$editing` branch)

Runs entirely inside one `DB::transaction()`:

1. If `inscriptionMode === 'new'`: creates a `Student` inline —
   `reference` via `ReferenceGenerator::make('ETU', 'students')`, all
   `new_*` fields mapped 1:1 (empty strings → `null`), phone/whatsapp/
   parent-phone/parent-whatsapp combined via `WithPhoneCountry::phoneValue()`
   (same shared country-dial mechanism as Students), **only the orientation
   field matching the chosen track is stored** (`domaine` for
   Arbeit/Ausbildung, `examen_type` for Studium — the other is forced
   `null` regardless of what was typed before a level switch, a second
   belt-and-braces check beyond the client-side `updatedNewNiveau()`
   reset), `etablissement_id` = **the group's center**, not the form's
   context (`$group?->etablissement_id`).
   Else (`'existing'`): `studentId = $data['student_id']` (already
   validated `exists:students,id`).
2. Maps `feeLines` → real fee-line arrays: recomputes `montant` via
   `InscriptionFee::computeMontant()` fresh (not trusting any client-sent
   final amount — none is even sent, only initial/pct/DH), preserves
   `frais_id`/`nom`/`montant_initial`/`remise_pct`/`remise_montant`/`note`,
   `date_echeance` (falls back to today if empty — same as §4.3's initial
   fallback, re-applied defensively), `statut` hardcoded
   `InscriptionFee::STATUT_NON_PAYE` for every new line (a brand-new
   registration's fees always start unpaid — no line can be created
   pre-marked paid).
3. `$total = $lines->sum('montant')` — **the ONLY source of
   `montant_total`**, computed server-side from the just-recomputed
   `montant` values, `> 0 ? $total : null` (an inscription with zero total —
   e.g. no fee lines at all — stores `null`, not `0`, matching the list's
   `—` display for null).
4. Creates the `Inscription` row: `reference` via
   `ReferenceGenerator::make('INS', 'inscriptions')`, `student_id`,
   `group_id`, `etablissement_id`/`annee_scolaire_id` **both re-derived from
   the group** (`$group?->etablissement_id`, `$group?->annee_scolaire_id`;
   the year falls back to `CurrentContext::anneeScolaireId()` only if the
   group itself has none — defensive, groups always have a year in
   practice), `statut` hardcoded `Active`, `date_inscription` from
   validated input, `date_debut`/`date_fin` re-derived from the group
   (never the submitted value — §4.2), `montant_total` from step 3, `note`,
   `created_by` = **the acting user's own employee id**
   (`auth()->user()->employee?->id`) — null if the acting user has no
   linked employee record.
5. `foreach ($lines as $line) { $inscription->fees()->create($line); }` —
   one `INSERT` per fee line, same transaction.

### 4.7 Save — edit (`save()`, `$editing` branch)

Drastically simpler — **only 6 columns are ever updated, never fees or
totals**: `student_id`, `group_id`, `statut`, `date_inscription`,
`date_debut`/`date_fin` (taken directly from `$this->date_debut`/
`$this->date_fin` here — **not** re-derived from the group on edit, unlike
create; but since these fields are hidden as `readonly` in edit mode's own
form section (only rendered when `$group_id` truthy, which it always is on
edit) and the group itself can be changed via the group select, this is
existing behavior worth flagging (§12) — not something to silently
"improve"), `note`. No transaction wrapper (single `update()` call, no
child rows touched).

### 4.8 Delete (`delete(int $id)`)

- `authorize('delete', $inscription)` — standard `ResourcePolicy` check.
- Wraps `$inscription->delete()` in `try/catch (QueryException)` — the DB's
  own `cascadeOnDelete()` on `inscription_fees.inscription_id` attempts to
  cascade-delete fee lines, which is itself blocked by
  `encaissements.inscription_fee_id`'s `restrictOnDelete()` if any fee has
  a payment. On catch: `addError('delete', 'This registration has payments
  and cannot be deleted.')` — a soft Livewire form error, not a 500.
- **No pre-check query** (unlike Students'/Employees'
  `loadCount()`-then-compare pattern) — this module relies entirely on the
  database constraint + catching the resulting exception. This is the
  ONE module in the whole migration so far that does it this way; every
  other Phase 6/7/8 delete guard pre-counts related rows in PHP first.
  Preserve this exact mechanism — do not "upgrade" it to a pre-count style
  guard, since that would be a silent behavior change in a way an auditor
  could reasonably call a business-rule alteration (e.g. a pre-count
  couldn't atomically guarantee no payment was inserted between the count
  and the delete, however vanishingly unlikely in practice — the DB
  constraint is strictly safer and must not be replaced).

### 4.9 Validation rules — full map (`rules()`)

Conditionally built based on `editingId`/`inscriptionMode`:

| Field | Always present? | Rule |
|---|---|---|
| `group_id` | always | `required, exists:groups,id` |
| `date_inscription` | always | `required, date` |
| `date_debut` | always | `nullable, date` |
| `date_fin` | always | `nullable, date, after_or_equal:date_debut` |
| `note` | always | `nullable, string` |
| `statut` | only if editing | `required, in:Inscription::STATUTS` |
| `student_id` | if editing OR mode=existing | `required, exists:students,id` |
| `new_nom` | if creating AND mode=new | `required, string, max:100` |
| `new_prenom` | if creating AND mode=new | `required, string, max:100` |
| `new_sexe` | " | `nullable, in:Student::SEXES` |
| `new_date_naissance` | " | `nullable, date, before:today` |
| `new_cin` | " | `nullable, string, max:30` |
| `new_niveau` | " | `nullable, in:Student::NIVEAUX` |
| `new_domaine` | " | conditionally required (§ same logic as StudentsIndex) |
| `new_examen_type` | " | conditionally required (§ same logic as StudentsIndex) |
| `new_email`/`new_telephone`/`new_whatsapp`/`new_adresse` | " | nullable, standard |
| `new_parent_*` (6 fields) | " | same rules as the Student modal's Parent tab |
| `phonePays` | if creating AND mode=new | `required, in:array_keys(Countries::LIST)` |
| `feeLines.*.montant_initial` | only if creating | `nullable, numeric, min:0` |
| `feeLines.*.remise_pct` | only if creating | `nullable, numeric, min:0, max:100` |
| `feeLines.*.remise_montant` | only if creating | `nullable, numeric, min:0` |
| `feeLines.*.date_echeance` | only if creating | `nullable, date` |

**No `feeLines.*.nom`/`.frais_id` validation** — those are never
user-editable (copied verbatim from the group's assigned fees, not typed).

### 4.10 Computed properties (`students()`, `groups()`)

Both `#[Computed]` (memoized per request). `students()`: center +
active-center scoped, no year scoping, ordered by `nom`. `groups()`:
center + active-center + **active academic year** scoped, ordered by
`nom`.

---

## 5. Existing Form Requests (dead code, for reference only)

`StoreInscriptionRequest`/`UpdateInscriptionRequest` validate a materially
DIFFERENT field set than the live component: they accept `etablissement_id`,
`annee_scolaire_id`, and `montant_total` directly from the client (the live
component NEVER accepts any of these from the client — all three are
always server-derived from the group). They also have no `new_*` fields,
no `feeLines.*` fields, no `inscriptionMode` concept at all. **These
Requests cannot simply be "wired up" as-is** — they would need a full
rewrite to match the live component's actual rules() (mirroring the
Phase 6/7/8 pattern of correcting dead-code Form Requests to match the real
UI, not the other way around).

`StoreInscriptionFeeRequest`/`UpdateInscriptionFeeRequest` validate
`inscription_id`, `nom`, `montant`, `date_echeance`, `statut` — no
`montant_initial`/`remise_pct`/`remise_montant`/`frais_id`/`note` at all
(added by the later discount migration, never back-filled into these
Requests). Also dead code (§1).

---

## 6. Domain / read-model classes

- `App\Domain\Registrations\Queries\GetInscriptionDetails` — Phase 5, Show
  page read-model. Computes `totalDu`/`totalPaye`/`reste` server-side from
  `fees.encaissements` (loaded relation), formats every money value as a
  fixed 2-decimal string. **Untouched by this phase** — Phase 9 will need
  an equivalent `GetInscriptionsList` (+ possibly a
  `GetInscriptionFormOptions` for students/groups/niveaux/etc., following
  the exact Phase 8 `GetGroupFormOptions` pattern) but must not alter this
  existing class.
- No other Domain classes reference Inscriptions today (`app/Domain/Registrations/`
  currently holds only this one Queries class — no Actions, no other
  Queries).

---

## 7. CurrentContext / CenterAccessService interaction

Same pattern as Groups (Phase 8): `etablissement_id`/`annee_scolaire_id`
are NEVER form inputs on create — always inherited (here, from the
selected **group**, not directly from `CurrentContext` — though the group
itself was scoped to the active context when it was created, so this is
consistent). `annee_scolaire_id` has one defensive fallback to
`CurrentContext::anneeScolaireId()` if the group somehow has none. List
scoping combines `CenterAccessService::scopeAccessibleCenters()` +
`WithCenterContext::scopeToActiveCenter()` + an explicit year filter — the
year filter is NOT part of `WithCenterContext`, it's inline in `render()`
(`when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))`),
identical to how Groups' own `render()` does it.

---

## 8. Spatie Media

**Inscriptions/InscriptionFee have no media collections at all** — no
photo, no document uploads anywhere in this module. Confirmed via
`grep` — neither model implements `HasMedia`.

---

## 9. Activity log

`Inscription` uses `LogsActivity`, logging only
`['student_id', 'group_id', 'statut', 'montant_total']`, `logOnlyDirty()`,
log name `inscription`. `InscriptionFee` does **not** use `LogsActivity` at
all (fee-line creation/edits are not individually audit-logged — only the
parent inscription's own dirty-column changes are).

---

## 10. Existing tests (must all keep passing)

- `tests/Feature/Backoffice/Inscriptions/InscriptionsCrudTest.php` — 18
  tests: page access, fee-line loading on group select, discount
  calculation + persistence, "all fees billed, no checkbox", due-date
  prefill from group, active-only catalog fees, live pct↔DH sync (both
  directions), date auto-fill + tamper-resistance, new-student-mode
  creation (+ contact/parent field persistence), validation (new-mode name
  required not student_id; existing-mode student_id+group_id required),
  edit (statut-only change), center scoping, detail-page fee/payment
  summary rendering.
- `tests/Feature/Backoffice/Inscriptions/InscriptionStudentFieldsTest.php`
  — 9 tests: the inline "Nouvel étudiant" block's exact DOM/Select2
  wiring (mode-select stays a single plain native select across re-renders,
  group select stays a Select2/`wire:ignore` island), CIN + professional
  field / entrance-exam persistence, orientation-clearing on level switch,
  full parent-block persistence, unknown parent-relation rejection, CEFR
  level needing no orientation.

Both files are 100% Livewire-component tests (`Livewire::test(...)`) — none
of them exercise a real HTTP request to a controller (since none exists
yet for mutations). They must continue to pass unmodified; Phase 9 adds a
parallel `InscriptionsInertiaCrudTest.php` (+ possibly a dedicated fee-line
test file) covering the same rules through real HTTP requests, following
the exact Phase 6/7/8 pattern.

---

## 11. Security / prop-shape concerns for the new list page

Per the task's explicit list, the new `GetInscriptionsList` (list page)
props must never expose: hidden model attributes, passwords/tokens (N/A —
no user data on this model), internal pivots (`group_frais`/`inscription_fees`
raw pivot rows), raw financial internals beyond what's already
intentionally shown (montant_total as a formatted string, never a float),
or another center's/year's enrollments (must respect the same
center+year+search+status scoping the Livewire list already enforces).
The **fee-line options** exposed for the create form (a group's assigned
fees) must only ever include ACTIVE catalog fees, matching
`loadGroupFees()`'s own `where('statut', Frais::STATUT_ACTIF)` filter
exactly — the biggest single behavior to get precisely right, since it's
directly financial (a stale/inactive fee showing up as billable would be a
real invoicing bug).

---

## 12. Behavior worth flagging (not necessarily bugs — observations for the stakeholder)

1. **`registrations.manage-fees` permission is currently unenforced on the
   live fee-line-creation path** (§3) — only the dead
   `InscriptionFeeController` checks it. The live path only checks
   `registrations.create`/`registrations.update`. Preserving this exactly
   means Phase 9's new Inscription create/update controller should
   likewise NOT require `registrations.manage-fees` for fee-line handling
   on create (only `registrations.create`) — introducing that check now
   would be a genuine authorization behavior change, not a preservation.
2. **Edit-mode `date_debut`/`date_fin` are taken directly from client input**
   (§4.7), unlike create-mode where they're always re-derived from the
   group server-side (§4.6). Since the edit form's date fields are
   `readonly` and the group can be changed via the group select without
   re-deriving dates, an edit *could* theoretically leave stale dates from
   the *previous* group after a group change + save, without an
   intervening full page reload that would re-trigger `updatedGroupId()`.
   This is existing Livewire behavior (not introduced by this audit) —
   Phase 9 must reproduce it exactly (i.e. accept whatever
   `date_debut`/`date_fin` the edit request sends, do NOT re-derive from
   the group on update), per the task's explicit "preserve every
   calculation exactly, no redesign" instruction. Flagging this only so
   the eventual Inertia edit form is built with the same readonly-display
   convention, not because it should be "fixed."
3. **`InscriptionFeeController`/`inscription-fees.*` routes and their Form
   Requests are dead code today.** Phase 9 will build fee-line handling
   entirely inside the new Inscription create endpoint (mirroring
   `save()`'s transaction), NOT by wiring up these existing dead routes —
   doing so would diverge from the "the Livewire implementation is the
   source of truth" instruction, since the live component never calls them
   either.

---

## Summary eligibility

| Aspect | Status |
|---|---|
| List (search/filter/sort/pagination/center+year scoping) | Ready to migrate — behavior fully mapped |
| Create — existing student | Ready — behavior fully mapped |
| Create — new student inline | Ready — behavior fully mapped, shares logic with Students Phase 8 |
| Create — fee-line loading + discount calculation | Ready — `InscriptionFee::computeMontant()` is the single source of truth, must be called server-side only |
| Edit | Ready — narrow scope (6 fields, no fees) confirmed |
| Delete | Ready — DB-constraint-catch mechanism must be preserved exactly, not replaced with a pre-count guard |
| Fee-line CRUD as a standalone feature | **Out of scope** — dead code today, not part of the live workflow |
