---
name: code-review
description: Review Laravel 13 and Livewire 4 changes for correctness, regressions, security, performance, database integrity, multi-center isolation, maintainability, and missing tests.
---

# Code Review

## Mission

Review changed code as a strict senior Laravel reviewer.

Focus on real defects and risks, not cosmetic preferences.

## Scope discovery

1. Read `CLAUDE.md`.
2. Inspect git status and diff.
3. Read all changed files completely.
4. Read related models, migrations, policies, routes, services, tests, and views.
5. Understand expected behavior before judging implementation.
6. Run relevant tests and quality tools when available.

Useful commands:

```bash
git status --short
git diff --stat
git diff
git diff --cached
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run build
```

Never claim a command passed unless it was run.

## Severity levels

### P0 — Critical

- data loss
- authentication bypass
- authorization bypass
- cross-center data exposure
- financial corruption
- remote code execution
- destructive migration defect
- production outage

### P1 — High

- incorrect business result
- serious security weakness
- broken primary workflow
- race condition
- duplicated payment/receipt
- missing transaction causing partial state
- major performance regression

### P2 — Medium

- edge-case bug
- incomplete validation
- N+1 query
- maintainability issue likely to cause defects
- inconsistent lifecycle handling
- missing important test

### P3 — Low

- small robustness issue
- minor inconsistency
- non-blocking cleanup

Do not inflate severity.

## Correctness review

Check:

- implementation matches requirements
- conditions and boundaries
- null handling
- date/time behavior
- localization
- status transitions
- calculations
- rounding
- duplicate submissions
- empty states
- soft-deleted records
- rollback behavior
- import/export consistency

## Laravel review

Check:

- route names and middleware
- route model binding
- Form Requests
- policies
- mass assignment
- casts
- model events
- scopes
- eager loading
- resources/serialization
- queues
- notifications
- cache invalidation
- transactions
- migration reversibility
- factories and tests

Flag unnecessary framework reinvention.

## Livewire review

Check:

- authorization for every action
- tampered public properties
- server-side validation
- stale modal state
- pagination reset
- query efficiency
- stable `wire:key`
- event naming
- third-party JS lifecycle
- sensitive IDs
- full collections stored as public state
- calculations trusted from browser

## Database review

Check:

- foreign keys
- indexes
- unique constraints
- decimal types
- nullable correctness
- delete behavior
- status representation
- historical integrity
- JSON misuse
- migration safety on existing data
- default values
- backfill strategy

For schema changes, consider production table size and locking.

## Multi-center review

Treat this as a security boundary.

Verify:

- all center-scoped queries are constrained
- related model IDs belong to the authorized center
- route binding cannot fetch another center's record
- exports and reports are scoped
- jobs receive and enforce center context
- cache keys include center context
- unique rules are center-aware
- tests attempt cross-center access

## Financial review

For fees, payments, advances, cheques, expenses, refunds, and cash registers, check:

- decimal arithmetic
- authoritative source of truth
- allocation totals
- overpayment handling
- advance balance
- reversal behavior
- immutable receipts
- audit trail
- transaction boundaries
- locking/idempotency
- recalculation
- cancellation permissions

A payment must not disappear because its related fee or inscription is edited or deleted.

## Security review

Check OWASP-relevant risks:

- IDOR
- XSS
- CSRF
- SQL injection
- unsafe raw queries
- file upload attacks
- path traversal
- sensitive information in logs
- leaked stack traces
- privilege escalation
- unvalidated redirects
- rate limiting
- brute force
- mass assignment
- insecure downloads

## Performance review

Check:

- N+1
- unbounded lists
- missing pagination
- missing indexes
- repeated aggregates
- `count()` in loops
- loading unnecessary columns
- large synchronous exports
- slow report queries
- query in Blade
- repeated filesystem access
- expensive Livewire renders
- cache stampede or stale cache

## Frontend/theme review

Check:

- Bootstrap compatibility
- duplicate CSS/JS
- broken theme asset paths
- repeated plugin initialization
- inaccessible controls
- responsive regressions
- unsafe raw HTML
- incorrect active navigation
- unauthorized actions still callable server-side

## Test review

Evaluate whether tests cover:

- happy path
- validation
- authorization
- cross-center access
- database constraint
- transaction rollback
- duplicate request
- concurrency-sensitive behavior
- Livewire state
- notifications/events
- soft deletes
- reporting totals

A passing test suite is not enough when tests assert the wrong behavior.

## Required output

Start directly with findings.

Use this format for each finding:

### [P1] Short title

**File:** `path/to/file.php:line`

**Problem:** Explain the defect.

**Impact:** Explain what can happen.

**Fix:** Give a concrete correction.

**Test:** State the test that should prove the fix.

Order findings by severity.

After findings include:

## Questions and assumptions

Only unresolved items that materially affect the review.

## Review summary

- Overall decision: APPROVE / REQUEST CHANGES / BLOCK
- Critical: number
- High: number
- Medium: number
- Low: number
- Tests run
- Quality commands run
- Areas not verified

## Review rules

- Findings first.
- Cite exact files and line numbers.
- Do not write a long praise section.
- Do not invent defects.
- Do not report style preferences as bugs.
- Do not approve untested financial or authorization changes.
- Mention when the diff is too incomplete to verify behavior.
- Provide patches only when requested.
