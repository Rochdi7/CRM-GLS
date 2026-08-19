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
| Controller (read-only) | `app/Http/Controllers/Backoffice/AuditLogController.php` |
| Page | `resources/js/Pages/Backoffice/AuditLogs/Index.tsx` |
| Schema | `database/migrations/2026_08_19_210000_add_forensic_context_to_activity_log_table.php` |
| Tests | `tests/Feature/Backoffice/Audit/AuditLogTest.php` |

## 5. Using it

`/backoffice/audit-logs`, sidebar **Configuration → Journal d'audit**, gated on
`audit-logs.view` (held by `director`; super-admins reach it through
`Gate::before`).

Filters: free-text search (including *inside* the recorded diff, so a reference
like `ENC-2026-0042` or an amount finds its own history), module, action, actor,
record type, date range, IP, and an **« Argent uniquement »** toggle that narrows
to the money-touching logs (encaissement, dépense, remboursement, transfert,
chèque, caisse, frais d'inscription).

Each row expands in place — rather than opening a detail page — because an
investigation reads a *sequence* of entries around a suspicious payment, and
navigating away for each one loses that context.

### Following a suspected encaissement fraud

1. Filter **Module = Encaissement** (or toggle « Argent uniquement »).
2. Set the date range around the disputed period.
3. Read the `Modification` rows: each expands to the exact before → after of
   every changed column.
4. The row names the actor, their IP, and the endpoint used.
5. Filter by that actor (or that IP) to see everything else they touched.
6. Cross-check `authentication` entries for the same IP — who was signed in,
   and whether failed attempts preceded it.

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
