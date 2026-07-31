# Phase 10 — Finance Domain Audit

Scope: **Caisses, Encaissements, Dépenses, Remboursements, CaisseTransfer
(Transferts de caisse)**. Written entirely before any Phase 10 implementation
code, per the phase's own requirement. Types de dépenses (Phase 6) is
referenced only where it establishes a pattern this domain should follow.

All file paths are relative to the project root.

---

## 1. Routes (`routes/backoffice.php`)

All finance routes live inside the `auth` middleware group (lines ~193-239),
under the comment "Finance — money records are never deleted (audit trail)".

| Verb | URI | Name | Target | Middleware/Gate |
|---|---|---|---|---|
| GET | `backoffice/caisses` | `backoffice.caisses.index` | `CaisseManagementController` (invokable) | `abort_unless(canAny(['cash-registers.view','cash-transfers.view']))` |
| GET | `backoffice/caisses/{caisse}` | `backoffice.caisses.show` | `CaisseController@show` | `authorizeResource` in ctor → policy `view` |
| GET | `backoffice/encaissements` | `backoffice.encaissements.index` | `EncaissementsIndex` (Livewire, routed directly) | `permission:payments.view` |
| GET | `backoffice/encaissements/{encaissement}` | `backoffice.encaissements.show` | `EncaissementController@show` | `authorizeResource` in ctor |
| GET | `backoffice/depenses` | `backoffice.depenses.index` | `DepenseManagementController` (invokable) | `abort_unless(canAny(['expenses.view','refunds.view']))` |
| GET | `backoffice/depenses/{depense}` | `backoffice.depenses.show` | `DepenseController@show` | `authorizeResource` in ctor |
| GET | `backoffice/types-depenses` | `backoffice.types-depenses.index` | `TypeDepenseController@index` (Inertia, Phase 6) | `authorizeResource` |
| POST/PUT/DELETE | `backoffice/types-depenses/*` | `.store/.update/.destroy` | `TypeDepenseController` | `authorizeResource` + `abort_if($is_system)` |
| GET | `backoffice/remboursements` | `backoffice.remboursements.index` | closure → `redirect(route('backoffice.depenses.index', ['tab' => 'remboursements']))` | `permission:refunds.view` |
| GET | `backoffice/caisse-transfers` | `backoffice.caisse-transfers.index` | closure → `redirect(route('backoffice.caisses.index', ['tab' => 'transferts']))` | `permission:cash-transfers.view` |
| GET | `backoffice/caisse-transfers/{caisse_transfer}` | `backoffice.caisse-transfers.show` | `CaisseTransferController@show` | `authorizeResource` in ctor |

Confirmed via `artisan route:list`: **no other finance route names exist.**
`backoffice.caisses.{create,store,edit,update,destroy}`,
`backoffice.encaissements.{store,create,edit,update}`,
`backoffice.depenses.{store,create,edit,update}`,
`backoffice.remboursements.{index-real,store,create,edit,update,show}`,
`backoffice.caisse-transfers.{store,create,edit,update,validate}` are **not
registered at all**, even though the corresponding controller methods, Form
Requests, and (for CaisseTransfer) a full `validate()` approval action exist
in code.

### Dead/unreachable routes

- No Blade/Livewire/React code calls any of the unregistered route names
  above — confirmed by full `route:list` plus a repo-wide grep for
  `route('backoffice.{caisses,encaissements,depenses,remboursements,caisse-transfers}.{store,update,destroy,create,edit,validate}')`.
- `backoffice.remboursements.index` and `backoffice.caisse-transfers.index`
  are **intentional redirect stubs** — they exist only so a stale bookmark
  lands on the right tab of the tabbed page, and so an unauthorized visitor
  gets a clean 403 from the permission middleware instead of a redirect that
  then 403s. This is deliberate design, not leftover cruft — preserve it.
- Types de dépenses is the only one of these six modules with real, reachable
  mutation routes (Phase 6) — it uses `->except(['show','create','edit'])`
  since it's modal CRUD, not full pages.

---

## 2. Livewire components (source of truth for live behavior)

All under `app/Livewire/Backoffice/{Caisses,CaisseTransfers,Encaissements,Depenses,Remboursements}/`.

### 2.1 `Caisses\CaissesIndex`

Read-only list — no CRUD exists at all (a caisse is auto-created via
`EmployeeObserver` → `CaisseProvisioner`). Props: `search`,
`etablissementFilter`, `statutFilter`. `mount()` authorizes `viewAny`.
`render()`: scoped by `CenterAccessService::scopeAccessibleCenters` +
`scopeToActiveCenter`, filtered by center/status/search (`nom` ILIKE or
responsable `nom`/`prenom` ILIKE). **Zero write action methods** (confirmed:
`method_exists` false for create/edit/save/delete).

### 2.2 `Caisses\CaisseJournal`

Read-only unified journal — merges 4 movement types (encaissements,
dépenses, remboursements, transferts) into one chronological list with stat
tiles. Same class serves both "Ma caisse" (`scope='mine'`) and "Journal des
transactions" (`scope='all'`) as two separate Livewire instances on one page.

- `mount(scope)`: authorizes `cash-registers.view`; self-heals by calling
  `CaisseProvisioner::provisionFor($employee)` once for `scope='mine'`
  (moved here from `render()` in an earlier performance pass).
- `rows($ids)`: **4 independent unpaginated `->get()` calls**, each mapped to
  a normalized row (`sens` = +1/-1, detail URL per type). Remboursement rows
  always get `url: null` — **there is no remboursement detail page anywhere
  in the app.** Sorted in PHP (`sortByDesc`), manually paginated via
  `slice()` at `PER_PAGE = 10`.
- `render()`: computes **unfiltered** overall totals for 3 stat tiles, then
  filtered rows + per-type totals.
- **Confirmed, still-live performance bottleneck** — see §14.
- No mutation methods.

### 2.3 `CaisseTransfers\CaisseTransfersIndex`

List + modal request/edit, two-step request→validate workflow.

- Props: filters (`search`, `statutFilter` default `En attente`,
  `caisseFilter`); modal state; form fields `caisse_source_id`,
  `caisse_destination_id`, `montant`, `note`.
- `mount()`: authorizes `viewAny`.
- `rules()`: **asymmetric** — `note` always validated;
  `caisse_source_id`/`caisse_destination_id`/`montant` **only when
  `editingId === null`** (create only, never edit).
- `create()`: authorizes `create`, resets form, opens modal.
- `edit($id)`: authorizes `update`; **hard guard**: if `statut !==
  STATUT_EN_ATTENTE`, error toast + modal never opens.
- `save(DemanderTransfertCaisse $action)`:
  - Edit: re-checks pending status, updates **only `note`** — source/dest/
    amount are structurally never touched even if tampered client-side.
  - Create: resolves acting employee (soft error + toast if none, no
    exception), calls the Domain action.
- `validateTransfer($id, ValiderTransfertCaisse $action)`: authorizes
  `validate` (checks `cash-transfers.validate` + center); catches
  `ValidationException` from the Domain action and surfaces it as a form
  error under `'validate'` + toast — never a 500. This is how
  "requester ≠ validator" and "already processed" surface in the UI.
- `cancel($id)`: authorizes `update`; guards pending-only; sets
  `STATUT_ANNULE` directly — **no money moves** (a pending transfer never
  moved money to begin with).
- `render()`: scoped through the **source** till
  (`whereHas('caisseSource', ...)`, matching `CaisseTransferPolicy`), filtered
  by status/caisse/search; computes `statutCounts` for tab badges; passes
  `currentEmployeeId` (lets the Blade view hide the Validate action on your
  own request as a UI-layer defense, in addition to the policy and Domain
  action's own refusals).

### 2.4 `Encaissements\EncaissementsIndex` (most complex of the six)

List + modal add/edit with a cascading multi-row payment form
(student → inscription → one row per unpaid fee).

- Props: filters; modal state; cascade fields (`student_id`,
  `inscription_id`, `inscription_fee_id`); shared fields (`montant`,
  `methode` default Espèces, `date_paiement`, `caisse_id`, cheque fields,
  `note`); **`paymentLines`** keyed by `fee_id` (not 0-based), one entry per
  unpaid fee of the selected inscription.
- `mount()`: authorizes `viewAny`.
- `rules()`: **fully asymmetric**:
  - Edit: only `methode`, `date_paiement`, cheque-conditional fields, `note`
    — **no `montant`/`caisse_id` rule exists at all in edit mode.**
  - Create: `student_id`, `inscription_id`, `caisse_id`, `note`, plus a
    custom closure ensuring at least one `paymentLines` row has a non-empty
    `montant`. For each **touched** row: `montant` (`required|numeric|
    min:0.01|max:<reste-du-fee>`), `methode` (`required|in:METHODES`),
    `date_paiement` (`required|date`). Untouched rows get no rules at all.
    Cheque-detail block conditionally required if any touched row uses
    Chèque.
- `create()`: authorizes `create`; defaults `date_paiement` to today;
  **`caisse_id` is set server-side to the signed-in employee's own till** —
  **there is no till picker in the create modal at all.**
- `updatedStudentId()` clears `inscription_id`/`paymentLines`.
- `updatedInscriptionId()` → `loadPaymentLines()`: queries `InscriptionFee`
  for the inscription ordered by `date_echeance`, **filters to fees with
  `resteDuFeeById() > 0`** (fully-paid fees get no row).
- `resteDuFeeById()`: `round(max(0, montant - fee->montantPaye()), 2)`.
- `statutForFee()` (static): derives a live/local status purely for modal
  display — not read from the DB `statut` column, so the preview reacts to
  in-progress typing before any submit.
- `edit($id)`: amount/caisse/student/fee context frozen; only `methode`,
  `date_paiement`, cheque fields, `note` are live inputs.
- `save(EnregistrerEncaissement $action)`:
  - Edit: `montant`/`caisse_id` structurally absent from the update payload
    — tampering has zero effect.
  - Create: re-reads from the **live `$this->paymentLines` array**, not
    Livewire's own validated-data return value, with an explicit code
    comment explaining why (`rules()` only declares rules for touched rows,
    so Livewire's `validate()` return would have stripped the untouched
    `fee_id` key entirely, corrupting row/fee pairing). Filters to touched
    lines. Wraps the **entire multi-row submission in one
    `DB::transaction`**: for each touched line, re-fetches the
    `InscriptionFee` by id and **verifies `fee->inscription_id ===
    $data['inscription_id']`** — mismatch throws `ValidationException`,
    rolling back every row already processed in this submit. One
    `Encaissement` created per valid touched row via the Domain action.
- `render()`: builds `$caisses` (accessible active tills, filter-dropdown
  only), a **second, deliberately separate** `$accessibleCaisseIds` query
  that is **not** restricted to Active tills (a payment recorded while its
  till was active must remain visible after the till is later deactivated),
  the paginated list, and cascade view-data (`inscriptions`, `fees` with
  live-derived dû/payé/reste/statut).

### 2.5 `Depenses\DepensesIndex`

List + modal add/edit with justificatif (receipt) uploads.

- Constants: `JUSTIFICATIF_MIMES = ['jpeg','jpg','png','webp','pdf']`,
  `JUSTIFICATIF_MAX_KB = 5120`.
- Props: filters; modal state; form fields `type_depense_id`, `caisse_id`,
  `group_id` (optional class/group link), `montant`, `methode_paiement`,
  `date_depense`, `reference_facture`, `description`, `mots_cles`
  (comma-separated, no tags table), `note`; `justificatifs` (new uploads),
  `existingJustificatifs` (`[mediaId => {name, url}]`, edit mode only).
- `mount()`: authorizes `viewAny`.
- `rules()`: `type_depense_id`/`date_depense` required both modes;
  `reference_facture`/`group_id`/`description`/`mots_cles`/`note` nullable
  both modes; `methode_paiement` **required only on create** ("rows
  predating the field stay correctable"); `justificatifs.*` file rules both
  modes; **`caisse_id`/`montant` rules exist only on create** — same
  asymmetric pattern as CaisseTransfers/Encaissements.
- `create()`: authorizes `create`; defaults `date_depense` to today;
  **`caisse_id` locked to the employee's own till** (disabled select with a
  single pre-filtered option).
- `edit($id)`: loads with `caisse`/`media`; authorizes `update`; populates
  editable fields; `methode_paiement` **is** editable in edit mode (unlike
  `montant`/`caisse_id`); calls `loadExistingJustificatifs()`.
- `save()`: **stylistic inconsistency** — resolves
  `app(EnregistrerDepense::class)` inline rather than via method injection
  like the sibling components (no functional difference).
  - Edit: `$depense->update($payload)` — payload structurally lacks
    `montant`/`caisse_id`.
  - Create: resolves acting employee (soft error, no exception), calls the
    Domain action.
  - **Regardless of branch**: calls `storeJustificatifs($depense)`
    afterward — new receipts can be attached during an edit too, not only at
    creation.
- `removeJustificatif($mediaId)`: authorizes `update`; deletes the Spatie
  Media record directly (`$media->delete()`) — the expense row itself is
  untouched.
- `render()`: scoped via `whereHas('caisse', ...)` (an expense reaches its
  center only through its till — it has no own `etablissement_id`);
  computes `montantTotal` as `sum('montant')` over the **full filtered set**
  (not just the current page); passes `soldeCaisse()` and active
  `typesDepenses`.

### 2.6 `Remboursements\RemboursementsIndex` (structurally simplest)

List + modal add/edit — no cheque, no upload, no cascade.

- Props: filters; modal state; form fields `beneficiaire_id` (Student),
  `caisse_id`, `montant`, `date_remboursement`, `motif`, `note`.
- `mount()`: authorizes `viewAny`.
- `rules()`: `date_remboursement`/`motif`/`note` always;
  `beneficiaire_id`/`caisse_id`/`montant` only on create — same asymmetric
  pattern. **`montant`'s only numeric constraint is `min:0.01` — no maximum
  of any kind.**
- `create()`: authorizes `create`; defaults `date_remboursement` to today;
  `caisse_id` locked to the employee's own till.
- `edit($id)`: `beneficiaire_id`/`caisse_id`/`montant` shown but read-only,
  frozen after creation.
- `save(EnregistrerRemboursement $action)`: edit updates only
  `date_remboursement`/`motif`/`note`; create resolves acting employee, then
  calls the Domain action.
- `render()`: scoped via `whereHas('caisse', ...)` (same "no own center
  column" pattern as Depense); passes `soldeCaisse()` and `students()`.

---

## 3. Blade view behavior not obvious from the class alone

All modals use the established Alpine pattern
(`x-data="{ show: @entangle('showModal') }"`) — **not** Bootstrap JS. This
whole domain is still 100% Livewire/Blade except the 4 read-only Show pages
and Types de dépenses.

- **`caisses-index.blade.php`**: info banner explaining auto-creation;
  status badge success/secondary; **only a "View" button** — no edit/delete
  in any row.
- **`caisse-journal.blade.php`**: 3 unfiltered stat tiles (green/red/blue);
  type badge color map (paiement→success, depense→danger,
  remboursement→warning, transfert→info); manual Previous/Next pager (not
  Livewire's built-in paginator, since the data is a plain Collection).
- **`backoffice/caisses/index.blade.php`** (page shell): 4 tabs — `ma-caisse`,
  `journal`, `transferts`, `comptes`, each permission-gated, filtered to only
  allowed tabs; `?tab=` deep-linking (used by the `caisse-transfers.index`
  redirect stub).
- **`backoffice/depenses/index.blade.php`** (page shell): 2 tabs only
  (`depenses`, `remboursements`) — Types de dépenses is explicitly **not** a
  tab here anymore (moved to its own Inertia page in Phase 6); `?tab=`
  deep-linking used by the `remboursements.index` redirect.
- **`encaissements-index.blade.php`**: the only finance list view that
  renders its **own page header** inline (Payments is a standalone route,
  not a tabbed sub-page). Fee status badge: success/warning/danger. Modal
  step 1 (create only): cascading student+registration Select2s with
  `wire:key` on conditional siblings to prevent Livewire's DOM-morph from
  scrambling them; **no till picker or balance box at all**. Step 2: one row
  per unpaid fee, `max="{fee.reste}"` as a client-side hint only (server
  still enforces via `rules()`); fully-paid fees render no row.
- **`remboursements-index.blade.php`**: amount always shown red, prefixed
  `-`. **No per-row detail link anywhere in this view** — matches
  CaisseJournal's `url: null` for remboursement rows. Edit modal: beneficiary
  Select2 `disabled` even though it isn't editable server-side either
  (defense-in-depth/UX clarity, not functional necessity).
- **`caisse-transfers-index.blade.php`**: status tabs are custom
  `wire:click`-driven (not Bootstrap nav-tabs). Validate action shown only if
  `@can('validate', $transfer)` **and** `$transfer->requested_by !==
  $currentEmployeeId` — a **second, UI-layer** self-validation defense on top
  of the policy and Domain action's own refusals. Validate button carries
  `wire:confirm="..."` (native browser confirm). Both till Select2s show live
  balance inline in each option label.

---

## 4. Models (`app/Models/`)

### `Caisse`

Constants `STATUT_ACTIVE`/`STATUT_INACTIVE`/`STATUTS`. Fillable: `nom,
etablissement_id, responsable_employee_id, solde, statut`. Cast `solde =>
decimal:2`. Relations: `etablissement`, `responsable` (Employee),
`encaissements`/`depenses`/`remboursements` (HasMany),
`transfersSortants`/`transfersEntrants` (HasMany CaisseTransfer, keyed by
source/destination). **Does NOT use `LogsActivity`.** No scopes/observers of
its own (provisioning is driven by `EmployeeObserver`). Class docblock states
`solde` is application-maintained, not ledger-derived.

### `Encaissement`

Constants `METHODE_ESPECES/TPE/CHEQUE/VIREMENT`, `METHODES`. Fillable:
`reference, student_id, inscription_fee_id, montant, methode, date_paiement,
caisse_id, agent_id, numero_cheque, banque, date_echeance_cheque, note`.
Casts: `montant => decimal:2`, both date columns `=> date`. Uses
`LogsActivity` (`logOnly(['montant','methode','caisse_id',
'inscription_fee_id'])->logOnlyDirty()->useLogName('encaissement')`).
Relations: `student`, `fee` (InscriptionFee), `caisse`, `agent` (Employee).

### `Depense`

Implements `HasMedia`+`InteractsWithMedia`. Constant `METHODES =
Encaissement::METHODES`. Fillable: `reference, type_depense_id, caisse_id,
group_id, montant, methode_paiement, date_depense, reference_facture,
description, mots_cles, note, agent_id`. Casts: `montant => decimal:2`,
`date_depense => date`. `registerMediaCollections()`: single collection
`justificatifs`, `acceptsMimeTypes(['image/jpeg','image/png','image/webp',
'application/pdf'])`. Uses `LogsActivity`
(`logOnly(['montant','type_depense_id','caisse_id'])`). Relations:
`typeDepense`, `caisse`, `group` (optional), `agent`.

### `Remboursement`

Fillable: `reference, beneficiaire_id, caisse_id, montant,
date_remboursement, motif, note, agent_id`. Casts: `montant => decimal:2`,
`date_remboursement => date`. Uses `LogsActivity`
(`logOnly(['montant','beneficiaire_id','caisse_id'])`). Relations:
`beneficiaire` (Student), `caisse`, `agent`. **No media, no `HasMedia`.**

### `CaisseTransfer`

Constants `STATUT_EN_ATTENTE/VALIDE/ANNULE`, `STATUTS`. Fillable:
`reference, caisse_source_id, caisse_destination_id, montant,
date_transfert, solde_source_avant, solde_source_apres, solde_dest_avant,
solde_dest_apres, statut, note, requested_by, validated_by`. Casts: `montant
=> decimal:2`, **`date_transfert => datetime`** (not `date`, unlike the
other three), 4 solde columns `=> decimal:2`. Uses `LogsActivity`
(`logOnly(['montant','caisse_source_id','caisse_destination_id','statut',
'validated_by'])`). Relations: `caisseSource`, `caisseDestination`,
`requestedBy`/`validatedBy` (Employee).

### `TypeDepense` (Phase 6, reference only)

Table `types_depenses`. Constants: `SYSTEM_PAIEMENT_PROF`, `SYSTEM_SALAIRE`,
`SYSTEM_TRANSFERT_CAISSE` (seeded, locked). Fillable `nom, is_system,
statut`. Cast `is_system => boolean`. **No `LogsActivity`** (catalog, not a
money movement).

---

## 5. Domain layer (`app/Domain/`)

### `Payments\Actions\EnregistrerEncaissement`

```php
return DB::transaction(function () use ($data, $agent): Encaissement {
    $encaissement = Encaissement::create([...$data, 'reference' => ReferenceGenerator::make('ENC','encaissements'), 'agent_id' => $agent->id]);
    Caisse::query()->whereKey($data['caisse_id'])->increment('solde', (float) $data['montant']);
    $this->recalculerStatutFee($encaissement->fee);
    return $encaissement;
});
```

One transaction: create → atomic `increment('solde', ...)` → recompute the
fee's `statut` from a live `SUM()` over its encaissements. No
`lockForUpdate()` (atomic `increment()` is race-safe at the SQL level without
one). **No insufficient-balance check** (not applicable to a deposit path).

### `Expenses\Actions\EnregistrerDepense`

```php
return DB::transaction(function () use ($data, $agent): Depense {
    $depense = Depense::create([...$data, 'reference' => ReferenceGenerator::make('DEP','depenses'), 'agent_id' => $agent->id]);
    Caisse::query()->whereKey($data['caisse_id'])->decrement('solde', (float) $data['montant']);
    return $depense;
});
```

Same shape. **No insufficient-balance check at all — `solde` can go negative
via an expense with zero guard.** No `lockForUpdate()`.

### `Finance\Actions\EnregistrerRemboursement`

```php
return DB::transaction(function () use ($data, $agent): Remboursement {
    $remboursement = Remboursement::create([...$data, 'reference' => ReferenceGenerator::make('RMB','remboursements'), 'agent_id' => $agent->id]);
    Caisse::query()->whereKey($data['caisse_id'])->decrement('solde', (float) $data['montant']);
    return $remboursement;
});
```

Identical shape to `EnregistrerDepense`. **No insufficient-balance check, no
maximum-refund-amount check of any kind, no `lockForUpdate()`.** The only
numeric constraint anywhere in the stack (Domain, Form Request, Livewire
`rules()`) is `min:0.01`. **This is confirmed to be the true current live
business rule (or absence of one) — not a bug.**

### `Finance\Actions\DemanderTransfertCaisse`

```php
public function handle(array $data, Employee $requestedBy): CaisseTransfer
{
    return CaisseTransfer::create([
        ...$data,
        'reference' => ReferenceGenerator::make('TRF','caisse_transfers'),
        'date_transfert' => now(),
        'solde_source_avant' => Caisse::query()->whereKey($data['caisse_source_id'])->value('solde'),
        'solde_dest_avant' => Caisse::query()->whereKey($data['caisse_destination_id'])->value('solde'),
        'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
        'requested_by' => $requestedBy->id,
    ]);
}
```

No `DB::transaction()` (a single INSERT — no atomicity requirement at this
step). **Balances are deliberately not touched** — that's the entire point
of the two-step design. No insufficient-balance check at request time
either.

### `Finance\Actions\ValiderTransfertCaisse` — the core fraud-control action

```php
public function handle(CaisseTransfer $transfer, Employee $validatedBy): CaisseTransfer
{
    if ($transfer->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
        throw ValidationException::withMessages(['statut' => 'Ce transfert a déjà été traité.']);
    }
    if ($transfer->requested_by === $validatedBy->id) {
        throw ValidationException::withMessages(['validated_by' => 'Le demandeur ne peut pas valider son propre transfert.']);
    }
    return DB::transaction(function () use ($transfer, $validatedBy): CaisseTransfer {
        $source = Caisse::query()->lockForUpdate()->findOrFail($transfer->caisse_source_id);
        $destination = Caisse::query()->lockForUpdate()->findOrFail($transfer->caisse_destination_id);
        $source->decrement('solde', (float) $transfer->montant);
        $destination->increment('solde', (float) $transfer->montant);
        $transfer->update([
            'statut' => CaisseTransfer::STATUT_VALIDE,
            'validated_by' => $validatedBy->id,
            'solde_source_apres' => $source->fresh()->solde,
            'solde_dest_apres' => $destination->fresh()->solde,
        ]);
        return $transfer;
    });
}
```

**The only one of the five Domain actions using `lockForUpdate()`** — on both
Caisse rows (pessimistic locking, appropriate given the two-row balance
mutation and this being the highest fraud-risk operation). Guard order:
already-processed check, then self-validation check, **both before the
transaction opens**. Both throw `ValidationException` (never a 500). **Still
no insufficient-source-balance check** — `decrement()` can drive
`caisse_source.solde` negative with zero guard anywhere in this stack.
Hardcoded French exception strings, **not wrapped in `__()`** (inconsistent
with CLAUDE.md §12, low practical impact since French is the only fully
shipped locale today).

### Query classes (read-only, feed the already-Inertia'd Show pages)

- `Finance\Queries\GetCaisseDetails` — last 10 each of
  encaissements/dépenses/remboursements + last 10 transfers (either
  direction), `number_format(..., 2, '.', '')` money strings, `d/m/Y` dates.
- `Finance\Queries\GetCaisseTransferDetails` — includes `isPending` + all 4
  solde snapshots (each null-checked).
- `Payments\Queries\GetEncaissementDetails` — live dû/payé/reste for the
  linked fee, `studentShowUrl`/`inscriptionShowUrl` (null-safe), `cheque`
  sub-object only when `methode === Chèque`.
- `Expenses\Queries\GetDepenseDetails` — splits `mots_cles` CSV, exposes
  `canViewList` via `Gate::allows('update', $depense)`, `receipts` mapped to
  `{name,url,mimeType,size}` only — never raw Media/filesystem paths.
- `Expenses\Queries\GetTypesDepensesList` (Phase 6 reference).

---

## 6. Policies (`app/Policies/`)

All extend `ResourcePolicy` (default: `viewAny`/`view`/`create`/`update`/
`delete` = permission + `withinCenter()`).

- **`CaissePolicy`**: `module = 'cash-registers'`. No overrides — uses
  `Caisse.etablissement_id` directly. `delete`/`create`/`update` exist
  (inherited) but are **never invoked** (no route reaches them).
- **`EncaissementPolicy`**: `module = 'payments'`. Overrides `centerId()` →
  `$model->caisse?->etablissement_id` (a payment has no own center column).
- **`DepensePolicy`**: `module = 'expenses'`. Same override pattern.
- **`RemboursementPolicy`**: `module = 'refunds'`. Same override pattern.
- **`CaisseTransferPolicy`**: `module = 'cash-transfers'`. Overrides
  `centerId()` via **`caisseSource?->etablissement_id`** (source only,
  matching the Livewire component's scoping). One custom method:
  ```php
  public function validate(User $user, CaisseTransfer $transfer): bool
  {
      return $user->can('cash-transfers.validate') && $this->withinCenter($user, $transfer);
  }
  ```
  Does **not** check requester≠validator itself — that rule lives exclusively
  in `ValiderTransfertCaisse`, by explicit design (commented in both places).
- **`TypeDepensePolicy`** (reference): `centerId()` always `null` (global
  catalog); `update`/`delete` unconditionally refuse when `is_system`.

---

## 7. Form Requests (`app/Http/Requests/Backoffice/`)

**Critical finding: every Store/Update Form Request for these 5 modules is
dead code from the routing perspective.** None of the controllers that
reference them have their `store`/`update` actions actually routed. The
Livewire components implement their **own, independent, hand-written
`rules()`** that closely mirror these Form Requests (each component's
docblock says so) but never call/reuse them.

- **`Caisses\StoreCaisseRequest`**: `nom`, `etablissement_id` (nullable),
  `responsable_employee_id` (nullable), **`solde`** (nullable, numeric,
  min:0 — opening balance, creation only), `statut`.
- **`Caisses\UpdateCaisseRequest`**: same minus `solde` (deliberately
  excluded — "a till balance must never be edited by hand").
- **`Encaissements\StoreEncaissementRequest`**: `student_id`,
  `inscription_fee_id`, `montant` (min:0.01, **no max** — a real divergence
  from the Livewire component's dynamic `max:reste`, moot only because dead),
  `methode`, `date_paiement`, `caisse_id`, cheque fields
  `required_if:methode,Chèque`, `note`.
- **`Encaissements\UpdateEncaissementRequest`**: `methode`, `date_paiement`,
  cheque fields, `note` only.
- **`Depenses\StoreDepenseRequest`**: `type_depense_id`, `caisse_id`,
  `group_id` (nullable), `montant` (min:0.01), `methode_paiement`
  (**required**, unconditionally), `date_depense`, `reference_facture`,
  `description`, `mots_cles`, `note`.
- **`Depenses\UpdateDepenseRequest`**: drops `montant`/`caisse_id`;
  `methode_paiement` becomes nullable.
- **`Remboursements\StoreRemboursementRequest`**: `beneficiaire_id`,
  `caisse_id`, `montant` (min:0.01, **no max**), `date_remboursement`,
  `motif`, `note`.
- **`Remboursements\UpdateRemboursementRequest`**: `date_remboursement`,
  `motif`, `note` only.
- **`CaisseTransfers\StoreCaisseTransferRequest`**: `caisse_source_id`,
  `caisse_destination_id` (`different:caisse_source_id`), `montant`
  (min:0.01), `note`.
- **`CaisseTransfers\UpdateCaisseTransferRequest`**: `note`, `statut`
  (`sometimes`, `Rule::in([STATUT_ANNULE])` — client-driven update can only
  ever mean "cancel"; validation/approval is a separate action entirely).

All ten have `authorize(): bool { return true; }` — authorization is
delegated to `authorizeResource()` in the controller constructor.

---

## 8. Controllers (`app/Http/Controllers/Backoffice/`)

- **`CaisseController`**: `authorizeResource`. Full CRUD methods exist but
  **only `show()` is routed** (own docblock says so explicitly). `show()` is
  already `Inertia::render('Backoffice/Caisses/Show', ...)`. The other 5
  methods still return plain `View`/`RedirectResponse` against Blade view
  names that likely no longer exist as reachable pages.
- **`CaisseManagementController`**: single `__invoke`,
  `abort_unless(canAny(...))`, returns the tabbed Blade shell.
- **`CaisseTransferController`**: `authorizeResource`. Has a `validate()`
  action (approval step) that is **entirely unrouted**, carrying a live
  **TODO**: `"TODO(permissions phase): gate validate() to Directeur-level
  roles."` Only `show()` routed.
- **`EncaissementController`**: only `show()` routed — the live index is the
  Livewire component routed directly, bypassing this controller entirely for
  the list (a structurally different pattern than Caisses/Dépenses, which
  route their index through a tab-hosting controller).
- **`DepenseController`**: same dead-CRUD shape, only `show()` routed.
- **`DepenseManagementController`**: mirrors `CaisseManagementController`.
- **`RemboursementController`**: same dead-CRUD shape; **`show()` here is
  NOT even routed** — Remboursements is the only module with zero detail
  page anywhere in the live app.
- **`TypeDepenseController`** (Phase 6, reference): fully live; `is_system`
  guarded by `abort_if` **before** `$this->authorize()`; `destroy()` uses a
  **pre-count guard** (`loadCount('depenses')` then early return with
  errors), not try/catch on `QueryException`.
- **`ResolvesActingEmployee`** trait (shared by Encaissement/Depense/
  Remboursement/CaisseTransfer controllers): `abort_unless($employee !==
  null, 403, ...)` — a **hard 403**, whereas every live Livewire component
  does a **soft `addError()` + toast** for the identical rule. Since the
  controller paths are dead this divergence is currently invisible, but
  matters if a future implementation reuses this trait verbatim.

---

## 9. Migrations (`database/migrations/`)

| Table | Notable columns | FK ON DELETE | Precision |
|---|---|---|---|
| `caisses` | `nom` string(100), `etablissement_id`/`responsable_employee_id` nullable FK, `solde` decimal(12,2) default 0, `statut` string(20) default Active | both FKs → **nullOnDelete** | decimal(12,2) |
| `encaissements` | `reference` unique, `student_id`/`inscription_fee_id`/`caisse_id`/`agent_id` FK, `montant` decimal(12,2), `methode` string(30), `date_paiement` date, cheque fields nullable, `note` text | all 4 FKs → **restrictOnDelete** | decimal(12,2) |
| `types_depenses` | `nom`, `is_system` boolean, `statut` | n/a | n/a |
| `depenses` | `reference` unique, `type_depense_id`/`caisse_id`/`agent_id` FK, `montant` decimal(12,2), `date_depense` date, + later-added `methode_paiement`, `reference_facture`, `group_id` | `type_depense_id`/`caisse_id`/`agent_id` → **restrictOnDelete**; `group_id` → **nullOnDelete** | decimal(12,2) |
| `remboursements` | `reference` unique, `beneficiaire_id`/`caisse_id`/`agent_id` FK, `montant` decimal(12,2), `date_remboursement` date | all 3 → **restrictOnDelete** | decimal(12,2) |
| `caisse_transfers` | `reference` unique, `caisse_source_id`/`caisse_destination_id` FK, `montant` decimal(12,2), `date_transfert` **dateTime**, 4× `solde_*` decimal(12,2) nullable, `statut`, `requested_by`/`validated_by` FK | source/destination/`requested_by` → **restrictOnDelete**; `validated_by` → **nullOnDelete** | decimal(12,2) |

Additional migration `2026_07_29_090000_add_performance_indexes_to_finance_and_academic_tables.php`
adds composite indexes: `encaissements(caisse_id, date_paiement)`,
`depenses(caisse_id, date_depense)`, `remboursements(caisse_id,
date_remboursement)` — purely additive.

**FK-restrict risk (Phase-9-style bug potential)**: every money row linking
to `caisses`, `students`, `employees`, `types_depenses`, or `inscription_fees`
is `restrictOnDelete`. Since money rows are never deleted this is low risk
*for the money tables themselves*, but it is a live risk on the referenced
side (deleting a Caisse/Student/Employee/TypeDepense with financial history
hits a restrict violation). `TypeDepenseController::destroy()` uses the safe
pre-count-guard pattern; the (dead) `CaisseController::destroy()` does
**neither** a pre-count guard nor a try/catch — just a bare `$caisse->delete()`
with only a code comment. Currently inert (unrouted), but must not be copied
verbatim if a real Caisse-delete capability is ever added — it would
reproduce the exact Postgres-transaction-abort bug class Phase 9 found and
fixed in Inscriptions.

---

## 10. Media/uploads

- **Only `Depense` has media** — collection `justificatifs`, mime allowlist
  `image/jpeg, image/png, image/webp, application/pdf`. The 5MB size cap
  exists only in the Livewire component's validation rule
  (`max:5120` KB) — the dead Store/Update Form Requests don't even declare a
  `justificatifs` field.
- **Encaissement, Remboursement, Caisse, CaisseTransfer have zero media
  collections** (confirmed by grep).
- Upload: `$depense->addMedia(...)->usingFileName($depense->id.'-'.
  $file->getClientOriginalName())->toMediaCollection('justificatifs')`. URLs
  via the app-wide `ShortUuidPathGenerator` (`/media/<uuid8>/<file>`).
- `removeJustificatif` calls `$media->delete()` (Spatie deletion) — the
  expense row itself is untouched.

---

## 11. Activity log (spatie/laravel-activitylog v5)

- **Logged**: `Encaissement`, `Depense`, `Remboursement`, `CaisseTransfer` —
  each with its own `logOnly([...])->logOnlyDirty()->useLogName(...)`.
- **NOT logged**: `Caisse` itself (no direct "balance changed from X to Y"
  trail — only traceable indirectly through the movement rows that caused
  it) and `TypeDepense` (low-risk catalog).

---

## 12. CurrentContext / CenterAccessService interaction

- `CurrentContext` (session-backed singleton): `etablissementId()` returns
  `null` for "all centers" or the resolved id. Non-global users are always
  locked to their employee's own `etablissement_id` — `setEtablissement()` is
  a no-op for them.
- `CenterAccessService`: answers "which centers may this user see"
  (permission-based) — separate from `CurrentContext`'s "which one is
  currently selected." No employee↔center pivot exists by design.
- `WithCenterContext` trait: `scopeToActiveCenter()` narrows to `whereNull OR
  = activeCenterId`; listens for `context-changed` and resets pagination.
- Every finance list composes **both** layers (`scopeAccessibleCenters()` +
  `scopeToActiveCenter()`) — consistent across all 6 Livewire components.
- **Confirmed: `center_id`/`etablissement_id` is never read from raw user
  input anywhere in this domain.** Every center-scoping decision derives from
  the authenticated employee's own center, `CurrentContext`'s session state,
  or a Policy's own relation-based `centerId()` derivation — never a request
  parameter.
- `WithCaisseSelection` trait: `preselectCaisseParDefaut()` exists but
  **appears unused** — all three components that include the trait inline an
  equivalent one-liner (`auth()->user()?->employee?->caisses()->value('id')`)
  instead of calling it.

---

## 13. Existing tests (`tests/Feature/Backoffice/Finance/`)

- **`CaissesCrudTest.php`**: auto-provisioning (1 caisse per employee,
  idempotent), backfill command, index access/forbidden split, zero write
  actions, zero write routes, search/filter, center scoping, Show page.
- **`CaisseManagementPageTest.php`**: tabbed-page access via `canAny`,
  legacy redirect, `CaisseJournal` scope behavior (`mine` hides other tills
  even within the accessible set; `all` shows every accessible till),
  type/date filters, self-healing provisioning, zero write methods.
- **`CaisseTransfersTest.php`** (most rigorous of the six) — proves: request
  does not move balances; cross-employee validation moves both balances
  atomically; self-validation refused, balances untouched; permission-gated
  validation; a validated transfer is frozen (edit refuses to open, a second
  validate attempt errors without double-moving money); pending-only
  editability (only `note` sticks, tampered `montant` has zero effect);
  cancel moves no money; status-tab filtering (unknown status silently
  ignored); cross-center denial; no destroy route/method; Show page renders
  snapshots. **Coverage gap**: no test for insufficient balance at
  validation time (because no such guard exists to test).
- **`DepensesCrudTest.php`**: till locked to own caisse (regex-parsed
  `<select>` assertion); billing fields persist + till decrements;
  `methode_paiement` required on create; amount `> 0`; reference generation,
  agent stamping, exact balance decrement; receipt attach/mime-rejection/
  removal; immutability of `montant`/`caisse_id` on edit; no destroy
  route/method; search/filter/center-scoping.
- **`EncaissementsCrudTest.php`** (very thorough on the cascade): till
  locked, no till `<select>` rendered, no balance box shown; fee-selection
  prefill (one row per unpaid fee only); single-fee payment increments
  balance and flips fee statut correctly (partial vs full); amount above
  remaining balance rejected with **zero** side effects; multi-row single
  submit (2 touched + 1 skipped-blank → exactly 2 Encaissements, correct sum
  increment, independently correct per-fee statut); **an invalid row rolls
  back the entire multi-row submit** (zero persisted, proving the
  transaction wraps the whole loop, not per-row); edit immutability; no
  employee record → soft error, zero side effects; center scoping;
  **a fee belonging to a different registration is refused even when
  injected directly via a tampered `->set()`** — the strongest cross-check
  in the whole domain's test suite. Show page.
- **`RemboursementsCrudTest.php`**: till locked; access/forbidden split;
  amount+beneficiary required, amount positive; reference/decrement/agent
  stamping; immutability; no destroy route/method; search/filter/scoping.
  **No test checks a maximum refund amount** — accurately reflects the
  code's genuine absence of such a rule.
- **`TypesDepensesCrudTest.php`** (Phase 6, reference): `is_system`
  protections; **pre-count-guard delete** (`assertSessionHasErrors('delete')`,
  record still present when in use).

### Coverage gaps (confirmed to reflect real, not tested, absences)

1. No test for a caisse going negative via Dépense/Remboursement.
2. No test for `ValiderTransfertCaisse` with insufficient source balance.
3. No test for `CaisseTransferController::validate()`'s TODO'd
   Directeur-level gating (action is unrouted).
4. No test exercises any of the dead controller paths at all.
5. No test covers `WithCaisseSelection::preselectCaisseParDefaut()` directly.
6. No test asserts exact activity-log entries for any of the 4 logged
   models.

---

## 14. `CaisseJournal` performance characterization

Confirmed against `PERFORMANCE_AUDIT.md` / `PERFORMANCE_OPTIMIZATION_REPORT.md`:

- §3.4 of the audit doc calls it the "heaviest single component" — 4
  unpaginated collections merged/sorted/paginated in PHP, mounted **twice**
  per `/backoffice/caisses` page load (scope `mine` and `all`), directly
  causing the documented 53-query/6-duplicate measurement.
- The provisioning-in-render fix and date-filter debounce fix from the
  optimization report are **already applied** in current code.
- The proposed SQL `UNION` rewrite and `#[Lazy]` tab-deferral are **explicitly
  not done** — listed in the optimization report's "recommended future
  improvements," deliberately scoped out as "too large a behavior change" at
  the time.
- **Conclusion**: this bottleneck is real, confirmed, and still present.
  Phase 10 is a natural place to address it, but doing so (real DB-level
  pagination, a `UNION` query, or separate per-type endpoints) changes
  existing sort/pagination semantics of the merged journal and must be a
  deliberate, discussed decision — not a silent side-effect of "just porting
  to React."

---

## 15. Sidebar navigation

### React config (`resources/js/Config/backofficeNavigation.ts`, Finance group)

```ts
{ label: 'Cash management', href: '/backoffice/caisses', permissions: ['cash-registers.view','cash-transfers.view'], matchPaths: ['/backoffice/caisses','/backoffice/caisse-transfers'] },
{ label: 'Payments', href: '/backoffice/encaissements', permissions: ['payments.view'], matchPaths: ['/backoffice/encaissements'] },
{ label: 'Expense management', href: '/backoffice/depenses', permissions: ['expenses.view','refunds.view'], matchPaths: ['/backoffice/depenses','/backoffice/remboursements'] },
{ label: 'Expense types', href: '/backoffice/types-depenses', permissions: ['expense-types.view'], matchPaths: ['/backoffice/types-depenses'], inertia: true },
```

None of the three finance-CRUD items carry `inertia: true` yet (plain anchor
navigation, full page reload) — consistent with them still being
Livewire/Blade. Only "Expense types" is `inertia: true`.

### Blade sidebar (`resources/views/components/backoffice/layout/sidebar.blade.php`)

- Outer Finance-submenu gate includes `expense-types.view` even though it has
  no separate `<li>` — folded into "Expense management"'s own gate/active
  check.
- "Expense management" `<li>` gate is `@canany(['expenses.view','refunds.view','expense-types.view'])`
  (3 permissions, vs React's 2) and its active-state check includes
  `types-depenses.*`.
- **Confirmed divergence, already self-flagged in the React config's own file
  comment**: *"Mirrors sidebar.blade.php exactly... keep both in sync until
  the Blade sidebar is retired (Phase 10)"* — reconciling this is explicitly
  Phase 10's job.

---

## 16. Money/precision handling

- Every money column is `decimal(12,2)` — no floating-point columns anywhere.
- Every money attribute is cast `decimal:2` (returns a string) — several
  tests assert against string literals like `'500.00'`.
- Domain actions cast to `(float)` only for the `increment()`/`decrement()`
  query-builder call — no custom Money value object.
- **Established Inertia-layer convention (Phases 6-9)**: Domain Query classes
  emit `number_format($x, 2, '.', '')` (locale-independent decimal string)
  as JSON; React does `Number(value).toFixed(2)` for display. Still-Livewire
  Blade views instead call `number_format($value, 2)` (2-arg, locale-default
  separators) directly server-side.
- **Currency-suffix inconsistency, pre-existing, not introduced by any prior
  phase**: `Caisses/Show.tsx` and `CaisseTransfers/Show.tsx` render `"...
  DH"`; `Encaissements/Show.tsx` and `Depenses/Show.tsx` render `"... MAD"`.
  Blade views mix both too. This needs a deliberate decision, not a silent
  perpetuation or silent unification.

---

## Behavior worth flagging (stop-condition candidates)

1. **Massive dead-code surface** in controllers/Form Requests for all 5
   modules except Types de dépenses — same shape of finding as Phase 9's
   `inscription-fees.*`. The Livewire components' inline `rules()`/`save()`
   are the actual source of truth; the controllers subtly diverge in places
   (items 2, 8 below) and must not be assumed correct.

2. **`ResolvesActingEmployee`'s 403-abort vs. Livewire's soft-error
   asymmetry** for "no employee record" — a real UX-tightening risk if the
   dead controller trait is reused verbatim rather than adapted to match the
   live soft-error behavior.

3. **`CaisseTransferController::validate()` carries a live TODO**
   ("gate validate() to Directeur-level roles") never acted on even in
   Livewire (which only checks the generic `cash-transfers.validate`
   permission).

4. **No insufficient-balance / no maximum-refund-amount checks anywhere** —
   confirmed current, intentional (or at least long-standing and untouched)
   live behavior, not a bug to silently fix. **Requires an explicit decision
   from the user before Phase 10 proceeds** — see the mapping doc's open
   questions.

5. **`ValiderTransfertCaisse` is the only Domain action using
   `lockForUpdate()`** — a deliberate asymmetry (two-row mutation needs it
   more) to preserve exactly, not "improve" uniformly across all four.

6. **Hardcoded French exception strings not wrapped in `__()`** in
   `ValiderTransfertCaisse` — low-impact, flagged for completeness only.

7. **`WithCaisseSelection::preselectCaisseParDefaut()` is dead code** inside
   an otherwise-used trait — safe, small cleanup opportunity.

8. **`DepensesIndex::save()`'s inline `app()` resolution** vs. sibling
   components' method-injection — stylistic only, worth normalizing.

9. **Pre-count-guard vs. try/catch-QueryException**: present exactly once
   (Types de dépenses, safe pattern). The dead `CaisseController::destroy()`
   uses neither — must not be copied verbatim if a real Caisse-delete
   capability is ever added.

10. **`RemboursementController::show()` is not even routed** — Remboursements
    is the only module with zero detail page anywhere in the live app. A new
    Show page/route would be **net-new capability**, not "migrate as-is" —
    requires an explicit decision, not a silent addition or omission.

11. **Permission/capability mismatches**: `cash-registers.{create,update,
    delete}` exist in the registry with no reachable UI/route anywhere
    (vestigial). `payments.delete`, `expenses.delete`, `refunds.delete`
    **do not exist at all** in the registry — the "money records are never
    deleted" invariant is already encoded at the permission-registry level
    for those three.

12. **Sidebar Blade/React divergence on "Expense types"** — already
    self-flagged as a Phase 10 TODO in the React config's own comment.

13. **Currency-suffix inconsistency ("DH" vs "MAD")** — pre-existing across
    both the migrated Show pages and the Blade views; needs a deliberate
    single decision, not silent perpetuation.

14. **`CaisseJournal`'s confirmed, still-unaddressed performance
    bottleneck** — a framework migration is well-positioned to fix this, but
    doing so changes sort/pagination semantics and must be a deliberate,
    called-out design decision.

15. **No test coverage for any dead controller/Form-Request path** — their
    exact behavior is not safety-netted; direct source reading (as done
    here) is the only reliable source, not "the tests will tell us."

16. **Three-layer defense-in-depth for self-validation** (UI hide + policy
    gate + Domain action refusal) — each layer protects a different failure
    mode (stale UI state, insufficient permission, direct API/race-condition
    abuse) and must be preserved as three independent layers, not collapsed
    into "the API checks once."
