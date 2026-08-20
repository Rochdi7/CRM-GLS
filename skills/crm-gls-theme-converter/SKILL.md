---
name: crm-gls-theme-converter
description: Convert PreSkool Laravel or HTML theme pages into reusable Laravel Blade components while preserving Bootstrap, assets, JavaScript behavior, responsiveness, and a strict backoffice/frontoffice structure.
---

# PreSkool Theme Converter

## Mission

Use the purchased PreSkool theme as a visual and markup reference, then integrate it cleanly into the Laravel application.

The original reference files must remain unchanged.

## Source and destination

Typical source folders may include:

```text
resources/views/theme-reference/crm-gls/
documentation/
public/theme-reference/
```

Typical destination folders may include:

```text
resources/views/layouts/backoffice/
resources/views/layouts/frontoffice/
resources/views/components/backoffice/
resources/views/components/frontoffice/
resources/views/backoffice/
resources/views/frontoffice/
public/assets/crm-gls/
```

Use the repository's actual conventions.

## Non-negotiable rules

- Do not edit or delete original theme reference files.
- Do not redesign the page.
- Do not replace Bootstrap with Tailwind.
- Do not rewrite functioning theme JavaScript without need.
- Do not duplicate global CSS/JS on every page.
- Do not copy an entire page when reusable layout slots are appropriate.
- Do not mix backoffice and frontoffice views.
- Do not hardcode application data into converted views.
- Do not remove responsive wrappers or accessibility attributes.

## Conversion workflow

1. Read `CLAUDE.md`.
2. Read the theme documentation.
3. Identify the exact reference page.
4. Trace required CSS, JS, fonts, images, and plugins.
5. Identify shared regions:
   - HTML head
   - loader
   - header
   - sidebar
   - mobile menu
   - breadcrumb
   - content wrapper
   - footer
   - scripts
6. Compare with existing Blade layouts and components.
7. Reuse existing components before creating new ones.
8. Create or update the smallest set of components.
9. Replace static values with Blade props, slots, loops, routes, translations, and authorization.
10. Add Livewire only for dynamic behavior.
11. Verify responsive and JavaScript behavior.
12. Record asset dependencies and changed files.

## Recommended Blade layout

Example only:

```blade
{{-- resources/views/layouts/backoffice/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.backoffice.partials.head')
    @stack('styles')
</head>
<body>
    <div class="main-wrapper">
        @include('layouts.backoffice.partials.header')
        @include('layouts.backoffice.partials.sidebar')

        <div class="page-wrapper">
            <div class="content">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </div>
    </div>

    @include('layouts.backoffice.partials.scripts')
    @stack('scripts')
</body>
</html>
```

Follow the project's preferred layout style: Blade components or `@extends`, but do not mix styles inconsistently.

## Component candidates

Create reusable components only when repeated or semantically useful:

- page header
- breadcrumb
- stat card
- content card
- data table wrapper
- status badge
- form input
- select
- textarea
- validation error
- modal
- empty state
- alert
- dropdown action menu
- pagination wrapper
- profile image
- center selector

Avoid creating a component for every single `<div>`.

## Blade conversion rules

Replace:

```html
<a href="students.html">
```

with named routes:

```blade
<a href="{{ route('backoffice.students.index') }}">
```

Replace static active states with route-aware logic.

Replace repeated static rows with loops.

Use:

- `@can`
- `@canany`
- `@auth`
- `@foreach`
- `@forelse`
- translations
- escaped output

Use `{!! !!}` only for explicitly trusted sanitized HTML.

## Assets

- Keep one canonical asset location.
- Use Laravel helpers such as `asset()`, `Vite`, or the project's asset pipeline.
- Do not duplicate plugin files.
- Load page-specific plugins only on pages that use them when practical.
- Preserve dependency order.
- Avoid mixing multiple versions of Bootstrap, jQuery, DataTables, Select2, or date pickers.
- Document any asset copied from the source theme.

## JavaScript plugins

For each plugin:

1. Confirm it is already included.
2. Keep expected HTML structure and data attributes.
3. Initialize once.
4. Destroy/reinitialize safely when Livewire updates the DOM.
5. Use `wire:ignore` only around plugin-owned DOM.
6. Synchronize value changes back to Livewire.
7. Avoid broad global selectors that initialize hidden or unrelated elements.

Common plugins:

- Select2
- date picker
- DataTables
- charts
- file uploader
- rich-text editor
- tooltip/popover
- sidebar/menu plugins

## Livewire integration

Use Livewire only for:

- search and filters
- CRUD modals
- reactive dependent selects
- attendance entry
- payment allocation
- dashboards
- validation-heavy forms
- dynamic tables

Do not convert simple links, static cards, or normal forms to Livewire without reason.

## Backoffice/frontoffice isolation

Ensure:

- separate layouts
- separate route names
- separate components when UI behavior differs
- no accidental admin navigation in frontoffice
- no shared authorization assumptions
- no admin assets loaded publicly unless needed

Shared neutral components may live in a shared component namespace.

## Quality checklist

### Visual

- layout matches reference
- spacing preserved
- icons render
- typography preserved
- badges/buttons match
- table is responsive
- empty and error states fit the theme
- mobile sidebar works

### Functional

- routes work
- active menu works
- dropdowns work
- modals work
- validation errors render
- pagination fits
- plugins initialize once
- Livewire updates do not break plugins

### Technical

- no duplicated asset bundles
- no broken asset paths
- no static `.html` links
- no hardcoded user data
- no authorization only in UI
- no original theme edits
- no console errors
- build succeeds

## Required final report

Provide:

- source reference page
- converted destination page
- layouts/components created or reused
- assets added
- JavaScript plugins used
- Livewire integration
- responsive checks
- files intentionally left unchanged
- commands/tests executed
