# Journal d'audit (audit trail)

The forensic record of everything that happens in the CRM. Built to answer one
question end-to-end: **who changed this, when exactly, from where, and what were
the values before and after** — without anyone having to ask an employee.

Companion docs: `roles-and-permissions.md` (who may read it),
`authorization-architecture.md` (how the permission is enforced).

---

## 1. What is recorded

| Dimension | Stored as | Notes |
|---|---|---|
| What changed | `attribute_changes` (jsonb) | `{old: {...}, attributes: {...}}` — **every** column, not an allowlist |
| Which record | `subject_type` + `subject_id` | Morph to the audited model |
| Which module | `log_name` | Stable name from `AuditLogRegistry` |
| What kind of action | `event` | `created` / `updated` / `deleted` / `login` / `login_failed` / … |
| Who | `causer_type` + `causer_id` | The `User`, via Spatie |
| Who (frozen) | `causer_label` | Name + username **as of that moment** |
| When | `created_at` | Date + time **to the second** |
| From where | `ip_address`, `user_agent` | |
| Through what | `method`, `url`, `route_name` | |

`created_at` keeps second precision on purpose: a fraud investigation orders
events *within* the same minute, so truncating to minutes would destroy the one
signal that matters most.

## 2. Coverage

**31 models** carry the trail — every model in the app that holds business data.
The list is in `App\Support\Audit\AuditLogRegistry::map()`, grouped as
finance / scolarité / RH & accès / stock / référentiel / import.

Two log names carry no Eloquent subject because they record events, not rows:

- `authentication` — login, logout, **failed logins**, lockouts, password resets
- `authorization` — role and permission grants/revocations

Failed logins are logged deliberately: a burst of failures from one IP shortly
before a suspicious encaissement is exactly the pattern an investigation looks
for, and it is invisible if only successes are recorded.

## 3. The three guarantees

**(a) Nothing is silently missed.** `App\Models\Concerns\Auditable` applies
`logAll()` — the previous per-model `logOnly([...])` recorded a handful of
columns, so an edit to a payment's date, note or student vanished. Adding
`use Auditable;` is now the whole contract; there is no per-model options block
to get wrong.

**(b) A super-admin is logged like everyone else.** `Gate::before` bypasses every
permission check, so authorization can never be what protects the journal.
Recording happens at the *model-event* layer instead, below any gate — a
super-admin bypasses permissions but cannot bypass an Eloquent observer.

**(c) The trail cannot be rewritten.** `App\Models\Activity` throws on
`updating` and `deleting`. Not a policy (a super-admin passes those) — a hard
model-level refusal, so no controller, console command, or future feature can
quietly amend history. There are no write routes: the module is
`Route::get` only, and adding a store/update/destroy route would be a bug.

Retention pruning is the single legitimate removal path, via the package's own
`activitylog:clean` command (`ACTIVITYLOG_CLEAN_AFTER_DAYS`, default **3650
days / 10 years** — deliberately long; the default of 365 would erase last
year's evidence).

## 4. Where the pieces live

| Concern | File |
|---|---|
| Entry model + forensic stamping + immutability | `app/Models/Activity.php` |
| Per-model logging defaults | `app/Models/Concerns/Auditable.php` |
| Model → log name/label map | `app/Support/Audit/AuditLogRegistry.php` |
| Auth event capture | `app/Listeners/LogAuthenticationActivity.php` (auto-discovered — never also subscribe it) |
| Read model (filters, diff shaping) | `app/Domain/Audit/Queries/GetActivityLogList.php` |
| Id → name resolution + French field labels | `app/Support/Audit/AuditValueResolver.php` |
| **Till balance movements (the money trail)** | `app/Domain/Finance/Support/CaisseLedger.php` |
| Controller (read-only) | `app/Http/Controllers/Backoffice/AuditLogController.php` |
| List page | `resources/js/Pages/Backoffice/AuditLogs/Index.tsx` |
| Detail page | `resources/js/Pages/Backoffice/AuditLogs/Show.tsx` |
| Schema | `database/migrations/2026_08_19_210000_add_forensic_context_to_activity_log_table.php` |
| Tests | `tests/Feature/Backoffice/Audit/AuditLogTest.php` |
| Money-trail tests | `tests/Feature/Backoffice/Audit/CaisseAuditTrailTest.php` |

## 5. Using it

`/backoffice/audit-logs`, sidebar **Configuration → Journal d'audit**, gated on
`audit-logs.view` (held by `director`; super-admins reach it through
`Gate::before`).

Filters: free-text search (including *inside* the recorded diff, so a reference
like `ENC-2026-0042` or an amount finds its own history), module, action, actor,
record type, date range, IP, and an **« Argent uniquement »** toggle that narrows
to the money-touching logs (encaissement, dépense, remboursement, transfert,
chèque, caisse, frais d'inscription).

The list stays scannable (when / who / what / how many fields moved) and each
row opens its own **detail page** (`backoffice.audit-logs.show`) with the full
breakdown: a shareable URL, the back button, and room to spell every value out.

### Readability — why the detail page shows names, not ids

The journal records what the database stores, which is correct but unreadable:
`ENSEIGNANT_ID: 11` tells a director nothing. `AuditValueResolver` fixes this at
**read time**:

- **Foreign keys resolve to names** — `enseignant_id: 11` renders as
  « Enseignant : Karim Fassi » with `#11` underneath. The id is still shown,
  because it is what was actually written; the name is a display aid layered on
  top, never a replacement.
- **Columns get French labels** — `montant_defaut` → « Montant par défaut ».
- **Dates are humanised** — `2026-08-19T00:00:00.000000Z` → `19/08/2026`
  (midnight is dropped; a real time is kept).
- **Plumbing is hidden on creations** — `id`, `created_at`, `updated_at` add
  nothing to “what did this person do” and pushed the meaningful fields out of
  view.

Resolution is deliberately NOT baked into the stored row: the journal must stay
an immutable record of the literal values written, and a name that changes later
must not silently rewrite history. Names are batch-loaded per page (a few
queries), not one lookup per field.

### Following a suspected encaissement fraud

1. Filter **Module = Encaissement** (or toggle « Argent uniquement »).
2. Set the date range around the disputed period.
3. Read the `Modification` rows: each expands to the exact before → after of
   every changed column.
4. The row names the actor, their IP, and the endpoint used.
5. Filter by that actor (or that IP) to see everything else they touched.
6. Cross-check `authentication` entries for the same IP — who was signed in,
   and whether failed attempts preceded it.

## 5b. The money trail — verifying a caisse

**`caisses.solde` is a stored number with no ledger table behind it**
(gls-crm-schema.md §10, a deliberate trade-off). Every money action used to
move it with `Caisse::query()->increment('solde', …)` — raw SQL that never
loads the model, so **Eloquent fired no events and the journal recorded
nothing**. The payment row was logged; the cash it moved was invisible. In a
CRM where money is everything, that was the hole a fraud would slip through.

Every movement now goes through **`CaisseLedger`**, which writes a
`solde_movement` entry carrying the complete arithmetic:

| Property | Meaning |
|---|---|
| `caisse` | which till |
| `sens` | Entrée / Sortie |
| `montant` | the amount moved |
| `solde_avant` | balance **before** |
| `solde_apres` | balance **after** |
| `motif` | « Encaissement ENC-2026-0042 » |
| `origine_type` / `origine_id` / `origine_reference` | the record that caused it |

⚠ **Never call `increment('solde')` / `decrement('solde')` or a raw update on
that column again.** A movement that skips `CaisseLedger` is a movement nobody
can audit. The five actions routed through it are `EnregistrerEncaissement`,
`SupprimerEncaissement`, `EnregistrerDepense`, `EnregistrerRemboursement` and
`ValiderTransfertCaisse`.

**Transfers journal BOTH legs** — the debit on the source and the credit on the
destination. A transfer logged on one side only would read as money vanishing.

**Coherence check.** The page recomputes `solde_apres - solde_avant` and
compares it with the recorded `montant`. When they disagree the entry is
flagged « Incohérent » in red — that mismatch is itself the finding.

### Verifying one till, step by step

1. Open **Journal d'audit** and pick the till in the **« Caisse »** filter.
2. Set the date range for the period under review.
3. Read the movements in order: each line shows *solde avant → montant →
   solde après*, so the running balance is checkable without recomputing
   anything from the encaissements/dépenses tables.
4. Any line marked « Incohérent » is a balance that does not add up.
5. Each movement names the actor, the IP and the source record (ENC-…, DEP-…),
   so a suspicious line leads straight to the payment and the person.

## 6. Adding a new module

1. `use Auditable;` on the model.
2. One line in `AuditLogRegistry::map()` — log name + French label.

That is the whole change. Filters, labels and the finance scope all read from
the registry, so they cannot drift from what is actually recorded.

## 7. Deliberate scope decisions

- **Not center-scoped.** Every other list query is scoped to the active center;
  this one is not, because scoping would hide exactly the cross-center activity
  an investigation needs. Access is controlled at the door instead.
- **Pivot/relation changes are not logged.** Attaching a fee to a group
  (`group_frais`) or a permission to a role still changes silently at the pivot
  level. Role/permission changes *are* covered separately by the
  `authorization` log via `UserAuthorizationService`. Logging arbitrary pivot
  attach/detach was considered and left out of this pass.
- **Password values are never stored** (`password`, `remember_token`,
  `two_factor_*`, `api_token` are globally excluded). That a password changed is
  still recorded — only the value is withheld, since it is never what an audit
  needs and a readable copy would be a liability.
