# PreSkool Theme Reference — READ BEFORE USING

This directory contains **preserved PreSkool v1.9.7 (Laravel variant) source views**
for design and structure reference.

**Source:** `themeforest preskool-v1.9.7/laravel/resources/views` (do not modify the
original download either).

## Hard rules

1. **Do NOT route to these views.** They are not production pages and reference
   theme routes/assets that do not exist in this application.
2. **Do NOT use them as active production pages.**
3. **Do NOT delete a reference file after copying/using it.** They stay forever.
4. **Do NOT edit these files** unless intentionally re-synchronizing with a newer
   theme release.
5. When a page is needed:
   - Find it here (categories below),
   - **Copy** it into `resources/views/backoffice/…` or `resources/views/frontoffice/…`,
   - Adapt the **copy** (asset paths → `asset('assets/preskool/…')`, layout →
     `<x-backoffice.layout.app>`, strings → `__()`),
   - Leave the original untouched.

## Things to change in every adapted copy

| Original theme code | Production replacement |
|---|---|
| `@extends('layout.mainlayout')` + `@section('content')` | `<x-backoffice.layout.app :title="…">` |
| `{{ URL::asset('build/…') }}` / `url('build/…')` / `src="build/…"` | `{{ asset('assets/preskool/…') }}` |
| `Route::is([...])` conditional asset loading | `@push('styles')` / `@push('scripts')` |
| `{{ url('some-page') }}` links | `route('backoffice.…')` named routes |
| `<x-breadcrumb>` (theme component) | `<x-backoffice.layout.page-header>` |
| Hard-coded English text | `{{ __('…') }}` |
| Client-side DataTables on big lists | Livewire server-side pagination |

## Directory map

| Folder | Contents |
|---|---|
| `layouts/` | `mainlayout.blade.php`, `partials/` (head, header, sidebar, footer-scripts), `layout-*.blade.php` demos |
| `dashboards/` | Admin (`index.blade.php`), teacher, student, parent dashboards, activities |
| `authentication/` | login/register/forgot/reset/verification/lock-screen variants |
| `students/` | student lists, grid, details, add/edit, promotion, guardians, parents |
| `teachers/` | teacher lists, grid, details, add/edit, routines |
| `hrm/` | staff, departments, designations, payroll, leaves, holidays |
| `academic/` | classes, sections, subjects, syllabus, time tables, exams, grades |
| `finance/` | fees, collect fees, expenses, accounts, invoices, tax rates |
| `attendance/` | attendance sheets and types |
| `reports/` | report pages |
| `applications/` | chat, calls, calendar, email, todo, notes, file manager, events, notice board |
| `content/` | blog, pages, testimonials, FAQ, countries/states/cities |
| `facilities/` | library, hostel, transport, sports, players |
| `membership/` | membership plans/addons/transactions |
| `settings/` | all settings screens, localization, email templates |
| `users/` | users, roles & permissions, profile |
| `forms/`, `tables/`, `charts/`, `icons/`, `ui/` | component demo pages — the fastest way to find markup for a widget |
| `errors/` | 404/500, coming soon, maintenance, blank page |
| `components/` | theme's original `breadcrumb` and `modal-popup` Blade components |
| `other/` | anything uncategorized |

## Related reference material

- Static theme assets (CSS/JS/img/fonts/plugins): `public/assets/preskool/`
- Theme SCSS source (reference only, not compiled): `resources/scss/preskool/`
- Official docs: original download → `preskool-v1.9.7/documentation/laravel.html`
