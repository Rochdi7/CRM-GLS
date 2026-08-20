# Claude Code Skills for a Laravel School CRM

This package contains five project skills:

1. `architecture-reviewer`
2. `laravel-feature-generator`
3. `database-designer`
4. `crm-gls-theme-converter`
5. `code-review`

They are tailored to:

- Laravel 13
- PHP 8.4+
- Inertia.js + React + TypeScript (backoffice frontend)
- Bootstrap
- PreSkool
- Multi-center school management
- Students, inscriptions, groups, attendance, finance, employees, inventory, roles, permissions, and reports

> A sixth skill, `livewire-component-builder`, existed while the backoffice
> was Livewire-based. It was removed once the Livewire→Inertia+React
> migration completed (`docs/phase-11-final-verification.md`) — Livewire no
> longer exists anywhere in this codebase, so a skill instructing Claude to
> build new Livewire components would actively contradict the project.

## Installation

Copy the skill folders into your project's Claude skills directory:

```text
your-project/
└── .claude/
    └── skills/
        ├── architecture-reviewer/
        │   └── SKILL.md
        ├── laravel-feature-generator/
        │   └── SKILL.md
        ├── database-designer/
        │   └── SKILL.md
        ├── crm-gls-theme-converter/
        │   └── SKILL.md
        └── code-review/
            └── SKILL.md
```

On Windows PowerShell, from the project root:

```powershell
New-Item -ItemType Directory -Force .claude\skills | Out-Null
Copy-Item "C:\path\to\claude-school-crm-skills\*" ".claude\skills\" -Recurse -Force
```

## Recommended project documentation

These skills work best when your repository also contains a clear `CLAUDE.md` describing:

- project stack
- folder structure
- naming conventions
- backoffice/frontoffice separation
- center isolation
- permissions
- financial rules
- test commands
- PreSkool reference folder
- forbidden changes

## Usage examples

Ask Claude Code naturally:

```text
Use architecture-reviewer to review the proposed payment allocation module before implementation.
```

```text
Use database-designer to design students, inscriptions, guardians, and student accounts.
```

```text
Use laravel-feature-generator to implement employee management in the backoffice.
```

```text
Use crm-gls-theme-converter to convert the students list reference page into reusable Blade components.
```

```text
Use code-review to review all uncommitted changes and report findings by severity.
```

## Important

The skills intentionally instruct Claude to inspect and preserve your existing architecture. They do not force a generic folder structure onto an established project.
