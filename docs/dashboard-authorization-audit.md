# Dashboard Authorization — Narrow Audit

Status: **Audited during Phase 5. No change made — documented and left as-is, per the Phase 5 instructions' explicit conditions for changing it (none are met).**

## Question

`backoffice.dashboard` (GET) has **no `permission:` route middleware** —
only `auth`. A `dashboard.view` permission exists in `PermissionRegistry`.
Is the missing gate a bug, or intentional?

## Findings

### 1. Is `dashboard.view` defined in `PermissionRegistry`?

Yes — `app/Support/Authorization/PermissionRegistry.php:31`:
```php
'dashboard.view' => 'Consulter le tableau de bord',
```

### 2. Which roles get it in the default matrix (`PermissionRegistry::matrix()`)?

**All 5 non-super-admin roles**: `director`, `operations-director`,
`administrative-assistant`, `teacher`, `marketing-manager` — every seeded
role except `super-admin` (which bypasses every check via `Gate::before`
and is intentionally excluded from the matrix, per the class's own
docblock). In practice, **every seeded user already has `dashboard.view`**
regardless of whether the route enforces it.

### 3. Does `DashboardController` authorize it elsewhere?

No. `DashboardController::__invoke()` (both before and after Phase 4's
Inertia conversion) contains no `$this->authorize(...)` call, and the route
has no `permission:` middleware. The only gate is `auth` (must be logged
in at all).

### 4. What do existing tests expect?

`tests/Feature/Backoffice/AuthTest.php::test_authenticated_users_can_view_dashboard`
(predates this migration, unchanged through Phases 1–4):

```php
public function test_authenticated_users_can_view_dashboard(): void
{
    $user = User::factory()->create(); // NO role assigned at all
    $this->actingAs($user)->get(route('backoffice.dashboard'))->assertOk();
}
```

This test explicitly proves the **intended, tested behavior**: a bare
authenticated user with **zero roles and zero permissions** can currently
view the dashboard. Adding `permission:dashboard.view` middleware to the
route would break this test today — it is not an oversight the test failed
to catch, it is the documented, asserted contract.

## Conclusion

**Intentional, not a bug** — or at minimum, a state the project's own test
suite has explicitly locked in as correct behavior, which is the closest
thing to "intentional" this audit can establish without asking the project
owner directly. The `dashboard.view` permission exists and is assigned to
every real role for forward-compatibility (e.g. if the route is ever
gated later, or if a future per-widget permission check is added inside
the page), but the route itself does not currently enforce it.

### Conditions for changing this (per the Phase 5 task instructions) — none met

1. ~~Existing architecture documentation explicitly requires `dashboard.view`~~
   — no such documentation exists; `PermissionRegistry`'s own comments don't
   claim the route should be gated.
2. ~~Existing role assignments support it~~ — they don't contradict it
   either way (every role has the permission, so gating wouldn't change
   behavior for *seeded* roles), but this alone doesn't establish intent.
3. **Existing tests do NOT establish "gate it" as intended behavior — they
   establish the opposite.** `test_authenticated_users_can_view_dashboard`
   explicitly uses a permission-less user and asserts success.
4. N/A (no change being made).
5. N/A (no change being made).

Since condition 3 fails outright (the test suite affirmatively expects
today's ungated behavior, not a gated one), this audit does **not**
recommend adding `permission:dashboard.view` during Phase 5, and no such
change was made.

## Recommended future correction (not performed now)

If the project owner later decides the dashboard should require
`dashboard.view` explicitly (e.g. to support a future role with zero
permissions who should see nothing, not even the shell), the correct,
isolated change would be:

1. Add `->middleware('permission:dashboard.view')` to the
   `backoffice.dashboard` route in its own commit.
2. Update `test_authenticated_users_can_view_dashboard` to assign a role
   (or `dashboard.view` directly) to its test user, since a bare
   `User::factory()->create()` would then correctly receive a 403.
3. Add a new test asserting a permission-less user is denied, to lock in
   the new intended behavior going forward.
4. Confirm no other test (e.g. `DashboardInertiaTest`,
   `DashboardStatsTest`) relies on a permission-less user reaching the
   dashboard.

This is out of scope for Phase 5 and was not performed.
