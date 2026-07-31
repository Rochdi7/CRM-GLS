# Phase 10 — Finance Migration Mapping

Field-by-field / behavior-by-behavior mapping from the live Livewire finance
domain (see `docs/phase-10-finance-audit.md`) to the planned Inertia+React
implementation. Written before implementation, per Phase 10's own
requirement. **Several items below are open questions requiring explicit
sign-off before code is written — they are marked ⚠ OPEN QUESTION and are
not decided in this document.**

---

## Module-by-module mapping

### 1. Caisses

| Livewire (source of truth) | Inertia plan |
|---|---|
| `CaissesIndex` — read-only list, no CRUD | `CaisseController@index` (Inertia) — read-only list, same query shape (`scopeAccessibleCenters` + `scopeToActiveCenter`, search on `nom`/responsable, status filter) via a new `GetCaissesList` read-model |
| `show()` (already Inertia) | Unchanged — already migrated, reused as-is |
| No create/edit/delete anywhere reachable | **Not added.** Caisses are auto-provisioned via `EmployeeObserver`/`CaisseProvisioner` — this phase does not introduce a manual create/edit/delete UI where none exists today. The dead `CaisseController` CRUD methods and `Store/UpdateCaisseRequest` are left in place, unrouted, exactly as Phase 9 left `InscriptionFeeController` |
| `CaisseJournal` (both `mine`/`all` scopes) | New `GetCaisseJournal` read-model + a dedicated controller action. ⚠ OPEN QUESTION (see below) on whether to preserve the exact PHP-merge/slice pagination or fix the confirmed performance bottleneck now |
| Tabbed page shell (`CaisseManagementController`) | One Inertia page `Backoffice/Caisses/Index.tsx` with client-side tabs (`ma-caisse`/`journal`/`transferts`/`comptes`), mirroring the permission-filtered-tabs + `?tab=` deep-link behavior exactly |

### 2. Encaissements

| Livewire | Inertia plan |
|---|---|
| `EncaissementsIndex` list+modal | `EncaissementController@index/store/update` (extending the existing `show()`-only controller) + `Backoffice/Encaissements/Index.tsx` |
| Cascading student→inscription→fee-lines form | Reuse the exact cascade: a `GET` lookup endpoint (new, mirroring Phase 9's `groups.inscription-fees` pattern) returning unpaid fee lines for a chosen inscription, called from React on inscription selection |
| No till picker; `caisse_id` server-derived from acting employee | Preserved exactly — server resolves the acting employee's own till, never accepts `caisse_id` from the client on create |
| Asymmetric `rules()` (edit: methode/date/cheque/note only; create: full cascade + per-row rules) | Two Form Requests, `StoreEncaissementRequest`/`UpdateEncaissementRequest`, **rewritten** (not reused as-is, since the existing ones have the `montant` max-cap divergence flagged in the audit §7) to match the Livewire `rules()` exactly, including the `max:reste` per-row rule |
| Multi-row single-submit wrapped in one `DB::transaction`, cross-checking `fee->inscription_id` per row | Preserved exactly in the controller's `store()` — same transaction boundary, same per-row inscription-ownership check, same rollback-the-whole-submit-on-any-invalid-row behavior |
| Edit: `montant`/`caisse_id` structurally absent from update payload | Preserved — `UpdateEncaissementRequest` has no such fields, `update()` only writes `methode`/`date_paiement`/cheque fields/`note` |
| No employee record → soft form error, no exception | Controller returns a 422 with the error on the relevant field (matching Livewire's soft-error UX), **not** `ResolvesActingEmployee`'s hard 403 — this is a deliberate divergence from the dead controller trait, per audit flag #2 |

### 3. Dépenses

| Livewire | Inertia plan |
|---|---|
| `DepensesIndex` list+modal+uploads | `DepenseController@index/store/update` + `Backoffice/Depenses/Index.tsx` |
| Till locked to employee's own caisse on create | Preserved exactly |
| Asymmetric rules (methode_paiement required only on create; montant/caisse_id create-only) | Rewritten Form Requests matching Livewire exactly |
| Justificatif upload (5MB, jpeg/jpg/png/webp/pdf), addable on both create and edit | Preserved exactly; upload validated server-side with the same mime/size rules; media prop shape is `{name, url, mimeType, size}` only — never a raw Spatie Media model, per the task's explicit media-prop rule |
| `removeJustificatif` — deletes only the Media record, not the expense | New `DELETE`-style action (or a dedicated route) preserving this exact scope |
| `montantTotal` computed over the full filtered set, not just the current page | Preserved — the read-model computes this as a separate aggregate query, not by summing the paginated page client-side |

### 4. Remboursements

| Livewire | Inertia plan |
|---|---|
| `RemboursementsIndex` list+modal | `RemboursementController@store/update` (new — currently unrouted) + a real `index` action (currently a redirect stub) |
| ⚠ OPEN QUESTION: keep the `remboursements.index` redirect-to-`depenses?tab=remboursements` pattern, or give it its own standalone Inertia page? | Not decided — see below |
| No max-refund check anywhere | ⚠ OPEN QUESTION — preserve the absence exactly, or ask whether to add one now, since a naive rewrite risks silently "fixing" this |
| Till locked, beneficiary/caisse/montant frozen after creation | Preserved exactly |
| **No detail/show page anywhere in the live app** | ⚠ OPEN QUESTION — this would be net-new capability, not a like-for-like migration, since `RemboursementController::show()` isn't even routed today |

### 5. Transferts de caisse

| Livewire | Inertia plan |
|---|---|
| `CaisseTransfersIndex` request+validate two-step workflow | `CaisseTransferController@store/update/validate` (the `validate` action currently exists but is fully unrouted — this phase would be the first time it's ever reachable) + `Backoffice/CaisseTransfers/Index.tsx` |
| Request creates row with `*_avant` snapshots, balances untouched | Preserved exactly — no `DB::transaction` needed at request time (matches the Domain action, which has none either) |
| Validate: `lockForUpdate()` on both tills inside one transaction, atomic decrement/increment, self-validation refused, already-processed refused | Preserved exactly — this is the highest-stakes money-moving path in the whole domain; no behavior change |
| Edit: only `note` changes; pending-only guard | Preserved exactly |
| Cancel: `STATUT_ANNULE`, no money movement | Preserved exactly |
| Three-layer self-validation defense (UI hide, policy gate, Domain refusal) | All three layers preserved independently in the React page + policy + Domain action — not collapsed to one check |
| `CaisseTransferController::validate()`'s TODO ("gate to Directeur-level roles") | ⚠ OPEN QUESTION — leave the TODO as unaddressed technical debt (matching current Livewire behavior, which also never acted on it), or address it now since this phase makes the action reachable for the first time |

---

## Open questions requiring sign-off before implementation

These are the audit's stop-condition candidates. Per the task's own
instructions ("Ask before changing business or accounting semantics," "Stop
immediately if... a business rule must be invented"), none of these are
decided in this document — implementation will not proceed on them until you
answer.

**Q1 — Insufficient-balance / maximum-refund checks.** Today, nothing in the
Domain layer prevents a Dépense, Remboursement, or validated Transfer from
driving a till's `solde` negative, and nothing caps a refund's amount against
what the beneficiary previously paid. This is confirmed current, real,
long-standing behavior — not a bug introduced anywhere. Should Phase 10:
(a) preserve this exactly (no new checks, matching "migrate as-is"), or
(b) add one or both checks as new, explicitly-requested business rules?

**Q2 — Remboursements detail/show page.** No Remboursement show page exists
anywhere today (no route, no view). Should Phase 10:
(a) leave this exactly as-is (no detail page, matching CaisseJournal's
`url: null` for these rows), or (b) add one as net-new capability for parity
with the other four modules?

**Q3 — `CaisseTransferController::validate()`'s Directeur-level TODO.** This
action has never been reachable by any route until Phase 10 makes it live
for the first time. Should the implementation:
(a) leave the TODO exactly as unaddressed technical debt (ship with only the
existing `cash-transfers.validate` permission check, matching current
Livewire behavior), or (b) act on the TODO now since it's finally being
wired up?

**Q4 — `CaisseJournal` performance bottleneck.** Confirmed real and still
unaddressed (4 unpaginated collections merged/sorted in PHP, mounted twice
per page load). Should Phase 10:
(a) port the existing PHP-merge/slice logic as-is (preserves exact current
sort/pagination semantics, keeps the known bottleneck), or (b) rewrite it as
a real paginated query (e.g., SQL `UNION` or per-type endpoints with
server-side pagination) as part of this migration — a deliberate behavior
change to sort/pagination semantics, not a silent side effect?

**Q5 — Currency suffix ("DH" vs "MAD").** Pre-existing inconsistency across
both already-migrated Show pages and current Blade views. Should the new
Encaissements/Dépenses/Remboursements/CaisseTransfers pages standardize on
one suffix (and if so, which), or preserve the exact per-page suffix each
already-migrated Show page currently uses?

**Q6 — Sidebar Blade/React reconciliation for "Expense types."** The React
nav config already gives Types de dépenses its own item; the Blade sidebar
still folds it into "Expense management." Since Phase 10 migrates the
remaining Livewire finance pages to Inertia, should the Blade sidebar
component simply stop being rendered at all once every finance page is
Inertia (i.e., is the unified `<x-backoffice.layout.app>` shell + React nav
config already the sole sidebar for migrated pages, making this divergence
moot), or is there a transitional state this phase needs to handle?

---

## Non-negotiable preservation list (no sign-off needed — already confirmed correct by the audit)

- All server-side money math stays in Laravel; React previews are always
  non-authoritative and recomputed server-side on save.
- `decimal(12,2)` columns, `decimal:2` casts — no floats anywhere in new code.
- Money over the wire: `number_format($x, 2, '.', '')` strings, matching the
  Phase 6-9 `MoneyDisplay` convention already used by the existing Show
  pages.
- Every existing transaction boundary (single `DB::transaction` per
  create/validate action, `lockForUpdate()` only in `ValiderTransfertCaisse`)
  preserved exactly — not "improved" to add locking elsewhere.
- Every create-vs-edit field asymmetry (Encaissements/Dépenses/
  Remboursements/CaisseTransfers all lock certain fields to create-only)
  preserved exactly.
- No physical deletion of any money record anywhere — no destroy routes
  added for Encaissements/Dépenses/Remboursements/CaisseTransfers.
- Center scoping: every list/mutation continues to resolve center access
  server-side (employee's own center, `CurrentContext`, or a Policy's
  relation-based `centerId()`) — never trusting a client-supplied
  `caisse_id`/`etablissement_id` for authorization purposes. All related IDs
  (`caisse_id`, `inscription_id`, `student_id`, `source_caisse_id`,
  `destination_caisse_id`) re-resolved and re-authorized server-side, never
  trusted from the request body for anything but "which record to load."
  All finance FK-restrict-on-delete behavior triggers a `DB::transaction()`-
  wrapped try/catch (per Phase 9's fix) wherever a delete-adjacent guard is
  ever needed — though per the preservation list above, no new delete
  capability is being added for the four money-movement modules in this
  phase, so this mainly applies if Q1/Q2 decisions introduce one.
- Existing permission names used exactly as found:
  `cash-registers.{view,create,update,delete}`, `payments.{view,create,
  update}` (no `.delete` — doesn't exist in the registry), `expenses.{view,
  create,update}` (no `.delete`), `refunds.{view,create}` (no `.update`/
  `.delete` beyond what's found — confirm exact set during implementation),
  `cash-transfers.{view,create,validate}`.
- `Depense` media collection name `justificatifs` unchanged; mime/size rules
  unchanged; media props sent to React are always the explicit
  `{name, url, mimeType, size}` shape, never a raw Spatie Media model.
- Activity logging preserved exactly for the 4 already-logged models; `Caisse`
  and `TypeDepense` remain unlogged (not newly added).
