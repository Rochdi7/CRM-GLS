---
name: livewire-component-builder
description: Build secure, performant Livewire 4 components for a Laravel school CRM, using Form Objects, policies, pagination, search, filters, Bootstrap modals, PreSkool styling, and multi-center isolation.
---

# Livewire Component Builder

## Mission

Create production-ready Livewire 4 components that follow the existing Laravel project conventions.

Use Livewire only where server-driven interactivity provides clear value.

## Before coding

1. Read `CLAUDE.md`.
2. Inspect existing Livewire components.
3. Inspect existing Blade and PreSkool components.
4. Inspect policies and routes.
5. Inspect the related models and indexes.
6. Determine whether a simple Blade/controller page is sufficient.

Do not force Livewire into static pages.

## Component design

Prefer focused components:

- list/table
- create form
- edit form
- detail panel
- specialized modal
- dashboard widget

Split a component when it has unrelated responsibilities or excessive state.

Use Livewire Form Objects for substantial forms.

Example structure:

```text
app/Livewire/Backoffice/Students/
├── StudentIndex.php
├── StudentForm.php
├── StudentCreate.php
└── StudentEdit.php

resources/views/livewire/backoffice/students/
├── student-index.blade.php
├── student-create.blade.php
└── student-edit.blade.php
```

Follow the actual repository structure when different.

## Security

Every mutation method must authorize the action.

Examples:

```php
$this->authorize('create', Student::class);
$this->authorize('update', $student);
$this->authorize('delete', $student);
```

Never trust:

- public IDs
- `center_id`
- monetary values derived by the browser
- hidden inputs
- disabled inputs
- selected status
- route parameters

Resolve and verify records server-side with center scope.

## Multi-center isolation

- Load only centers available to the authenticated user.
- Scope every query by authorized center.
- Validate related records within the current center.
- Recheck authorization in every action.
- Test tampered record IDs.
- Never use an unrestricted `Model::find($id)` for center-owned records.

## State

- Keep public state minimal and serializable.
- Avoid exposing full models unnecessarily.
- Do not retain large collections in public properties.
- Reset state when a modal closes.
- Reset validation errors between operations.
- Use locked or protected mechanisms supported by the project for sensitive identifiers.
- Keep query-string state limited to useful filters.

## Forms and validation

Use a Form Object when there are many fields or reusable create/edit logic.

Validation must include:

- type and format
- scoped exists checks
- database-matching uniqueness
- business rules
- normalized strings
- date consistency
- authorized center relationships

Do not validate only on blur; always validate before persistence.

## Tables

List components should support only needed features:

- pagination
- search
- sorting
- filters
- date range
- center filter
- status filter
- bulk action
- export

Implementation rules:

- use `WithPagination`
- reset page after filters change
- whitelist sortable columns
- eager load relationships
- select required columns when useful
- debounce search
- avoid `%term%` scans on huge unindexed datasets
- preserve filters in query string only when useful
- do not load all rows for totals

## Bootstrap and PreSkool

- Preserve Bootstrap classes.
- Reuse PreSkool cards, tables, buttons, dropdowns, badges, and modals.
- Keep theme assets centralized.
- Use `wire:ignore` only around third-party JS widgets that require it.
- Synchronize `wire:ignore` widgets explicitly.
- Do not duplicate the full theme layout.
- Use accessible labels and error messages.
- Preserve responsive tables and mobile behavior.

## Modals

For Bootstrap modals:

- maintain a single clear source of truth
- reset form on close
- reset errors
- emit/dispatch a browser event to open and close
- avoid stale selected-record IDs
- authorize again on submit
- use stable modal IDs

## Events

Use explicit event names scoped to the domain.

Examples:

- `student-created`
- `payment-recorded`
- `group-enrollment-updated`

Avoid generic names such as `refresh` when multiple components may listen.

## File uploads

When uploads are needed:

- validate extension, MIME, size, dimensions, and count
- generate random filenames
- store outside public paths when files are private
- authorize downloads
- strip or re-encode risky images when required
- never trust the original filename
- remove temporary files
- test malicious and oversized uploads

## Transactions

Livewire components should call an Action or Service for multi-model workflows.

Do not place complex financial or enrollment transactions directly in the component.

## Performance

Check:

- N+1 queries
- repeated computed property queries
- large dropdowns
- aggregate queries on each render
- expensive counts
- loading state
- pagination
- caching for stable reference data

Use lazy loading only when it improves the experience and does not hide necessary authorization.

## Testing

Create Livewire tests for:

- component renders
- authorized user access
- unauthorized user denial
- cross-center record tampering
- validation
- create/update/delete actions
- modal reset
- search
- filters
- sorting whitelist
- pagination reset
- emitted events
- transaction failures

Example style:

```php
Livewire::actingAs($user)
    ->test(StudentCreate::class)
    ->set('form.first_name', 'Rochdi')
    ->call('save')
    ->assertHasNoErrors()
    ->assertDispatched('student-created');
```

Adapt to Livewire 4 and the project's installed testing API.

## Required implementation report

Include:

- component responsibilities
- public state
- authorization points
- query and eager-loading strategy
- files created/changed
- tests
- commands executed
- any third-party JS integration notes

## Prohibited behavior

- No authorization only in `mount()`.
- No unbounded `Model::all()` for operational tables.
- No direct trust of public model IDs.
- No business-critical calculations in JavaScript.
- No Tailwind conversion unless requested.
- No edits to original PreSkool reference files.
- No inline SQL in Blade.
- No large business workflow inside a Livewire action.
