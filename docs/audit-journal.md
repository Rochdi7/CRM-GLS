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
can audit. The six actions routed through it are `EnregistrerEncaissement`,
`SupprimerEncaissement`, `EnregistrerDepense`, `ApprouverDepense`,
`EnregistrerRemboursement` and `ValiderTransfertCaisse`.

⚠ **A dépense may debit the till in one of two places, never both.** With
Paramètres → Système « Validation des dépenses » **ON** (the default),
`EnregistrerDepense` records the expense `En attente` and moves **no money** —
`ApprouverDepense` is what debits, at the moment of the decision. With the
switch OFF, `EnregistrerDepense` debits immediately as before. So an expense
whose journal shows a `depense` creation but no matching `solde_movement` is
**not** a missing-movement anomaly: check its `statut` first. A refused expense
correctly has no movement at all, ever.

**Transfers journal BOTH legs** — the debit on the source and the credit on the
destination. A transfer logged on one side only would read as money vanishing.
Both legs carry `valide_par`, which under the recipient-only rule is always the
employee who owns the destination till (never the requester, never a
third-party validator, and never a super-admin acting for someone else — see
CLAUDE.md §11).

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

### Truthful « avant » values — model defaults must mirror column defaults

A column with a database-level default (`statut`, and every `statut`-like
column in this schema) is a trap for the journal. `create()` without that key
leaves the **PHP model holding NULL** while the **row holds the default**; the
next status change is then recorded as « avant : (vide) ».

That is not a display quirk — the journal *states* the record came from
nothing when it came from « Non payé ». A false before-value in an audit trail
is worse than a missing one.

Every affected model therefore declares `protected $attributes` mirroring its
column default (13 of them: Salle, Employee, Group, Frais, Inscription,
InscriptionFee, Cheque, Caisse, TypeDepense, StockArticle, Banque, StockType,
MotifAnnulation).

⚠ **When adding a column with a DB default to an audited model, mirror it in
`$attributes`.** `test_models_with_a_default_status_agree_with_the_database`
pins the existing set; extend it when a new default appears.

### Attribution — who set the value on BOTH sides

On the detail page every changed field names an author under each side:

```
Montant par défaut   AVANT 250,00  — Agent Un · 21/08/2026 14:02:11 · #412
                     APRÈS 999,00  — Agent Deux
```

The **APRÈS** author is this entry's own actor. The **AVANT** author is found by
walking back through the same record's history to the most recent earlier entry
that touched that same field (`GetActivityLogList::previousAuthors()`), and
links to that entry so the earlier change can be opened directly.

Why it exists: the journal already answered "who made THIS change". The harder
question when tracing a mistake is "who put the wrong value there in the first
place" — the person correcting an error is rarely the person who caused it.

Three rules that keep it honest:

- **No invented authors.** A field with no earlier entry shows
  « auteur inconnu (antérieur au journal) » rather than guessing.
- **Same record only.** The lookup is scoped to `subject_type` + `subject_id`,
  so two records of the same type never borrow each other's history.
- **Bounded.** The walk stops after 50 earlier entries, or as soon as every
  field has an answer — an unbounded scan would be a silent performance trap
  on a record edited hundreds of times.

Attribution runs on the **detail page only** (`find()` passes
`withAttribution: true`); the list page skips it, since one extra query per row
would be paid on every page load for information nobody is reading yet.

### The developer account — hidden, never unrecorded

`AuditLogRegistry::DEVELOPER_EMAIL` names the maintainer's login. Its entries
are **hidden from the journal page by default**, so routine maintenance does
not bury the entries that describe real school activity. The
« Inclure le compte technique » toggle brings them back.

⚠ **This is a display filter, not a recording bypass — keep it that way.**
The entries are written in full, exactly like everyone else's. An account whose
actions were never recorded would be a permanent blind spot on the most
privileged login in the system: money could move through any till with no
trace, which is precisely what this journal exists to prevent. The distinction
is pinned by `test_the_developer_account_is_still_fully_recorded`.

Two details that make the filter honest rather than cosmetic:

- **The detail page applies it too** (`find()`), so a hidden entry is not
  reachable by guessing its id.
- **The exclusion is causer-based and skips NULL causers**, so system and
  console entries — which have no causer at all — are never swept away with
  the developer's.

If the maintainer login does not exist in a given database, the toggle is not
rendered at all (`hasDeveloperAccount`).

### Console origins

An entry written from the CLI records **only the command name** —
`artisan:db:seed`, `artisan:tinker` — never its arguments
(`Activity::consoleOrigin()`).

This was a bug worth naming: the origin used to be built by joining the whole
of `$_SERVER['argv']`, so `artisan tinker --execute=<script>` wrote the entire
PHP payload into `url`. That rendered as a wall of code under « Requête »,
leaked internal code and absolute file paths into a page non-technical people
read, and reached 730 of the column's 1024 characters — a slightly longer
script would have thrown *while inserting the audit row*, losing the record
itself.

Arguments are dropped wholesale rather than filtered, because an option's value
can hold anything (a script, a password, a token). The command name is the part
that is always safe and is what an investigation actually needs.
`fillForensicContext()` also clamps every forensic column to its width, so no
input can ever break an audit write.

Existing rows were repaired by
`database/migrations/2026_08_20_190000_sanitize_console_urls_in_activity_log.php`
— a deliberate, documented, one-off exception to the append-only rule that
touches ONLY the `url` column (see the migration's docblock for why, and why it
is not a precedent).

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
