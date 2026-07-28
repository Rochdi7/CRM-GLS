# Suggested CLAUDE.md additions

Add or adapt these rules in the project's root `CLAUDE.md`.

## Stack

- Laravel 13
- PHP 8.4+
- Livewire 4
- Blade
- Bootstrap
- PreSkool theme

## Architecture

- Keep backoffice and frontoffice separated in controllers, requests, Livewire components, routes, and views.
- Reuse the project's established structure.
- Use Actions or Services for multi-model business workflows.
- Use policies for resource authorization.
- Use Form Requests or Livewire Form Objects for validation.

## PreSkool

- Original theme files are read-only references.
- Preserve Bootstrap and theme behavior.
- Reuse layouts and Blade components.
- Do not introduce Tailwind unless explicitly approved.

## Multi-center security

- Treat center isolation as an authorization boundary.
- Never trust submitted center IDs.
- Scope queries, reports, exports, jobs, and related-record validation by authorized center.
- Test cross-center access.

## Finance

- Use decimal fields for money.
- Keep fees, payments, allocations, advances, refunds, cheques, expenses, and cash movements auditable.
- Never destructively edit financial history.
- Use transactions and duplicate-submit protection.

## Quality

Before completing a feature, run the available commands:

```bash
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse
npm run build
```

Never claim a command passed unless it was executed.
