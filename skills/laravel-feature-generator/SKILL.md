---
name: laravel-feature-generator
description: Generate complete Laravel 13 school CRM features from database to tests, following backoffice/frontoffice separation, multi-center authorization, Livewire 4, Blade, Bootstrap, and existing project conventions.
---

# Laravel Feature Generator

## Mission

Implement complete production-ready Laravel features, not isolated snippets.

Default stack:

- Laravel 13
- PHP 8.4+
- Livewire 4
- Blade
- Bootstrap
- PreSkool theme
- Eloquent
- Pest or PHPUnit

## First actions

Before modifying code:

1. Read `CLAUDE.md`.
2. Inspect relevant architecture documentation.
3. Inspect neighboring modules and copy established conventions.
4. Inspect route organization, authorization, testing style, and UI components.
5. Inspect the current database schema.
6. Confirm whether the feature belongs to backoffice, frontoffice, or both.

Do not blindly generate a generic Laravel structure.

## Mandatory implementation order

1. Understand the business rule.
2. Design or verify the schema.
3. Create migrations.
4. Update models, casts, relationships, scopes, and factories.
5. Create validation.
6. Create authorization policies.
7. Create actions/services for business workflows.
8. Create controllers and/or Livewire components.
9. Create Blade views and reusable components.
10. Add routes.
11. Add events, listeners, notifications, jobs, or exports when needed.
12. Add tests.
13. Run formatting, static analysis, and tests.
14. Summarize changed files and remaining risks.

## Project structure

Follow the existing project. When the project already separates surfaces, use patterns such as:

```text
app/
├── Actions/
│   └── Students/
├── Domain/
│   └── Students/
├── Http/
│   ├── Controllers/
│   │   ├── Backoffice/
│   │   └── Frontoffice/
│   └── Requests/
│       ├── Backoffice/
│       └── Frontoffice/
├── Livewire/
│   ├── Backoffice/
│   └── Frontoffice/
├── Policies/
└── Services/

resources/views/
├── backoffice/
├── frontoffice/
├── components/
│   ├── backoffice/
│   └── frontoffice/
└── theme-reference/

routes/
├── backoffice.php
└── frontoffice.php
```

Never move the project to this structure if it already uses another coherent structure.

## Feature completeness

A feature is not complete until relevant items are handled:

- migration
- model
- relationships
- casts
- factory
- seeder when useful
- validation
- authorization
- service/action
- controller or Livewire component
- Blade UI
- routes
- translations
- events
- notifications
- jobs
- audit trail
- tests
- documentation

Do not create empty layers just to satisfy a checklist.

## Database rules

- Use foreign keys and indexes.
- Use `decimal`, never float, for money.
- Define scale and precision intentionally.
- Store currency when multiple currencies are possible.
- Use explicit statuses.
- Prefer normalized relational tables over JSON when fields are queried or reported.
- Add composite unique constraints for business uniqueness.
- Select delete behavior intentionally.
- Preserve financial history.
- Add audit metadata to important financial and status changes.
- Make migrations reversible.

For center-scoped records, ensure the center relationship is explicit and protected.

## Multi-center rules

All center-owned operations must:

- resolve the current authorized center
- reject center IDs the user cannot access
- scope queries server-side
- scope dropdown options
- scope exports and reports
- test cross-center access
- include center context in jobs

Never trust a `center_id` submitted by the browser.

## Authorization

Use policies for resource actions:

- viewAny
- view
- create
- update
- delete
- restore
- forceDelete
- custom financial or workflow actions

Call authorization from controllers and Livewire actions.

Do not depend only on hidden buttons, route middleware, or role names in views.

## Validation

Use Form Requests or Livewire Form Objects.

Include:

- normalized input
- business validation
- scoped existence rules
- unique rules that match database constraints
- translated messages when the project supports localization
- authorization independent from validation

## Business logic

Use an Action or Service when logic:

- affects multiple models
- requires a transaction
- is reused
- has meaningful business rules
- triggers events or notifications
- integrates with external systems

Name actions after business operations:

- `RegisterStudent`
- `CreateInscription`
- `AllocatePayment`
- `MoveStudentToGroup`
- `RecordAttendance`
- `CloseCashRegister`

Avoid vague names such as `Helper`, `Manager`, or `Processor`.

## Transactions and concurrency

Use transactions when a workflow performs multiple dependent writes.

For financial, stock, capacity, or sequential-number operations, consider:

- row locking
- unique database constraints
- idempotency keys
- duplicate-submit protection
- recalculation from authoritative data

Never rely solely on a preliminary `exists()` check for concurrency-sensitive uniqueness.

## Livewire 4 rules

- Keep components focused.
- Use Form Objects for substantial forms.
- Authorize every mutation action.
- Validate on the server.
- Reset form and validation state when modals close.
- Use pagination for lists.
- Eager load relationships.
- Keep query-string state intentional.
- Do not store full Eloquent collections as long-lived public state.
- Use stable `wire:key`.
- Protect action parameters from tampering.

## PreSkool and Bootstrap rules

- Preserve Bootstrap markup and theme JS behavior.
- Reuse existing assets.
- Convert repeated theme structures to Blade components.
- Keep original theme files under the reference folder unchanged.
- Do not introduce Tailwind unless explicitly requested.
- Do not copy an entire HTML page when a layout or component can be reused.
- Preserve responsive behavior and accessibility.

## Tests

Create tests for:

- authorized happy path
- unauthorized action
- cross-center access
- validation failures
- database constraints
- relationships
- transactions
- duplicate submission
- events/notifications
- Livewire interaction
- filters, search, sorting, and pagination when relevant

Use factories rather than fragile manually constructed records.

## Quality commands

Run available project commands, for example:

```bash
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse
npm run build
```

Do not claim a command passed unless it was executed.

## Required final report

Provide:

1. Feature summary
2. Files created
3. Files changed
4. Database changes
5. Authorization decisions
6. Test coverage
7. Commands executed and results
8. Assumptions
9. Remaining risks or follow-up work

## Prohibited behavior

- Do not overwrite unrelated code.
- Do not edit vendor files.
- Do not edit original PreSkool reference files.
- Do not disable authorization to make tests pass.
- Do not remove constraints to avoid migration errors.
- Do not use raw SQL when Eloquent/query builder is clear and safe, unless performance requires it.
- Do not generate fake implementations or TODO-only methods.
- Do not silently change public routes or existing business behavior.
