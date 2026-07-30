# Dashboard — Livewire → Inertia Stat Mapping

Every value the Livewire `DashboardStats` component computed, mapped 1:1 to
its new source. Query logic is byte-for-byte identical — only the
transport changed (Blade view data → typed DTO → Inertia props).

Old source: `app/Livewire/Backoffice/Dashboard/DashboardStats.php::render()`
New source: `app/Domain/Reports/Actions/GetDashboardStats.php`

| Label (Blade) | Old property | Query | Center-scoped? | Year-scoped? | Permission-dependent? | Output type |
|---|---|---|---|---|---|---|
| Students | `studentsTotal` | `Student::query()->when($centreId, ...)->count()` | ✅ (`etablissement_id`) | ❌ (no academic-year FK on students) | ❌ | `int` |
| Employees | `employeesTotal` | `Employee::query()->when($centreId, ...)->count()` | ✅ | ❌ | ❌ | `int` |
| Employees → Active | `employeesActive` | same query + `->where('statut', Employee::STATUT_ACTIF)` | ✅ | ❌ | ❌ | `int` |
| Groups | `groupsTotal` | `Group::query()->when($anneeId,...)->when($centreId,...)->count()` | ✅ | ✅ | ❌ | `int` |
| Groups → En formation | `groupsEnFormation` | same query + `->where('statut', Group::STATUT_EN_FORMATION)` | ✅ | ✅ | ❌ | `int` |
| Active registrations (card shows `inscriptionsActives`) | `inscriptionsTotal` | `Inscription::query()->when($anneeId,...)->when($centreId,...)->count()` | ✅ | ✅ | ❌ | `int` (computed but not directly displayed as its own card — see note) |
| Active registrations | `inscriptionsActives` | same query + `->where('statut', Inscription::STATUT_ACTIVE)` | ✅ | ✅ | ❌ | `int` |
| This month (payments) | `paymentsMonth` | `Encaissement::query()->whereBetween('date_paiement', [startOfMonth, endOfMonth])->when($centreId, whereHas('caisse', ...))->sum('montant')` | ✅ (via `caisse.etablissement_id`) | ❌ (calendar-month range, not academic year) | ❌ | `float` in the DTO, **string (2-decimal)** in the Inertia prop — see Money handling below |
| Context banner — year | `anneeLabel` | `$context->anneeScolaire()?->nom` | — | — | ❌ | `?string` |
| Context banner — center | `centreLabel` | `$context->isAllCenters() ? __('All centers') : $context->etablissement()?->nom_centre` | — | — | ❌ | `?string` |

**Note on `inscriptionsTotal`**: the Livewire component computes it but the
Blade view (`dashboard-stats.blade.php`) never actually renders it as its
own card — only `inscriptionsActives` is displayed (labeled "Active
registrations"), with `inscriptionsTotal` unused dead data in the original
component. Preserved anyway in the DTO/props for parity — removing it would
be a scope decision beyond "preserve exact behavior," and it costs nothing
extra (same query, already computed).

## Permission / visibility behavior (preserved exactly)

- **No stat is individually permission-filtered.** The `dashboard` route
  itself currently has **no `permission:` middleware** at all (verified in
  `routes/backoffice.php` — only `auth`) — this is the actual existing
  behavior, not something this migration changed or should "fix". A
  `dashboard.view` permission exists in `PermissionRegistry` and is used by
  the Context test fixtures, but it is not wired to the route today.
- All 7 stats are visible to any authenticated user, scoped only by their
  center access (via `CurrentContext`/`CenterAccessService`), never by an
  individual permission check per card.

## Money handling

`Encaissement.montant` is `decimal(12,2)` (CLAUDE.md §17). The DTO keeps it
as PHP `float` internally (matching the Livewire component's own
`(float) $paymentsMonth` cast) but the **Inertia prop** sends it as a
**pre-formatted string** (`number_format($value, 2, '.', '')`, e.g.
`"1234.56"`), never a raw float over the wire — the React page parses it
only for display formatting (`Number(props.stats.paymentsMonth).toFixed(2)`
+ `" MAD"` suffix, matching the Blade's `number_format($paymentsMonth, 2)`
+ `" MAD"` exactly), never for arithmetic.

## Zero/default behavior

Every `->count()`/`->sum()` naturally returns `0` when no rows match — no
special-casing needed or added. `anneeLabel`/`centreLabel` are nullable
(`?string`) and rendered as `—` in the original Blade when null; the React
page preserves the same fallback.
