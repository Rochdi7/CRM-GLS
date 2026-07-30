# Phase 8 — Students & Groups Inventory

Audit of the two candidate modules before any implementation, mirroring the
Phase 6/7 inventory pattern. No field/behavior below is assumed — every row
was read from the Livewire component, model, policy, Form Request, Blade
view, and existing tests.

---

## 1. Students

| Aspect | Current behavior |
|---|---|
| Route names | `backoffice.students.index` (Livewire), `backoffice.students.show` (already Inertia — Phase 5) — **no store/update/destroy route exists** |
| Controller ownership | `StudentController::show()` only (Phase 5). All mutations run over Livewire's own AJAX protocol |
| Livewire ownership | `App\Livewire\Backoffice\Students\StudentsIndex` — list + modal add/edit + photo upload |
| Modal fields | `nom`, `prenom` (required); `sexe` (radio button pair); `date_naissance`; `cin`; phone/whatsapp (country-dial + national via `WithPhoneCountry`); `email`; `adresse`; `niveau` (CEFR + 3 German tracks); `domaine` (required only for Arbeit/Ausbildung); `examen_type` (required only for Studium); `etablissement_id` (hidden unless "Tous les centres" is the active context); `parent_nom`, `parent_relation`, `parent_sexe`, `parent_cin`, parent phone/whatsapp; `note`; `photo` (image upload, media library) |
| Validation rules | Livewire `rules()`, identical logic also duplicated in the **dead** `StoreStudentRequest`/`UpdateStudentRequest` (no route uses them today) — `domaine`/`examen_type` are conditionally required based on `niveau` via `Rule::excludeIf` |
| Policy permissions | `students.view/create/update/delete` (`StudentPolicy extends ResourcePolicy`, no override — standard center-scoped `withinCenter()` on view/update/delete) |
| Center scoping | Yes — `CenterAccessService::scopeAccessibleCenters()` (list) + `WithCenterContext::scopeToActiveCenter()` (top-bar center narrows further); new record's `etablissement_id` defaults to the active context center and the field is hidden unless "Tous les centres" is selected |
| Academic-year scoping | None — students have no `annee_scolaire_id` |
| Delete behavior | Guarded in Livewire: blocks if `inscriptions_count \|\| encaissements_count \|\| remboursements_count` > 0, `addError('delete', ...)` |
| Photo upload | `spatie/laravel-medialibrary`, single-file `photo` collection, `thumb` conversion (96×96, `nonQueued()`); `getFirstMediaUrl('photo')`/`getFirstMediaUrl('photo', 'thumb')` |
| Search | `nom`/`prenom`/`reference`/`cin`/`telephone`, all `ilike`, one search box |
| Filters | `niveauFilter` (select) |
| Sorting | `ageSort` toggle (asc/desc) on `date_naissance`, NULLs last always |
| Pagination | `WithPerPage` (10/25/50/100) |
| Guardian/contact fields | "Parent" tab: `parent_nom`, `parent_relation` (fixed list), `parent_sexe`, `parent_cin`, `parent_telephone`, `parent_whatsapp` — inline on the `students` table, no separate table (deliberate simplification per `gls-crm-schema.md`) |
| Relations | `belongsTo` etablissement; `hasMany` inscriptions, encaissements, remboursements |
| Eligible for Phase 8 | **Yes** |
| Migration risks | Low-medium. Photo upload (`Livewire\WithFileUploads` → Inertia `useForm` file upload) is the only non-trivial mechanical translation; phone-country combo field already has a reusable `PhoneField` component from Phase 6. |

---

## 2. Groups

| Aspect | Current behavior |
|---|---|
| Route names | `backoffice.groups.index` (Livewire), `backoffice.groups.show` + `backoffice.groups.archive` (already Inertia — Phase 5), `backoffice.groups-historique.index` (already Inertia — Phase 5) — **no store/update/destroy route exists**; **no destroy route by design** (groups are never deleted, schema §6) |
| Controller ownership | `GroupController::show()`/`archive()` only (Phase 5). All create/update mutations run over Livewire's own AJAX protocol |
| Livewire ownership | `App\Livewire\Backoffice\Groups\GroupsIndex` — list + modal add/edit + per-group fee assignment |
| Modal fields (confirmed against the actual Blade view, not just the model) | `nom` (required), `niveau` (required, CEFR only — `Group::NIVEAUX` = `Student::NIVEAUX_CEFR`, no German tracks for groups), `enseignant_id` (nullable select, filtered to `Employee::CATEGORIE_ENSEIGNANT` + active-center scope), `statut` (create: Pré-inscription/En formation only — Fin de formation is archive-only; edit: any status **except** a raw transition into Fin de formation, which the component silently reverts), `date_debut_formation`, `date_fin_formation` (`after_or_equal` start), and a repeatable **fee-lines table** (see below) |
| **⚠ Rooms/Capacity/Schedules — DO NOT EXIST in the current UI** | `Group::$fillable` includes `salle_id`/`capacite_max`, and the dead (unrouted) `StoreGroupRequest`/`UpdateGroupRequest` validate them, but **`GroupsIndex`'s own `rules()` has neither field, and `groups-index.blade.php`'s modal has no room/capacity input anywhere.** `GetGroupDetails` (Phase 5 show page) eager-loads `salle` but never renders it. Per stakeholder decision (this phase): **migrate only what exists today — no room/capacity/schedule fields are added.** Adding them would be new feature work, not a like-for-like migration, and there is no "schedule" concept (column/table) in the schema at all to migrate in the first place. |
| Validation rules | Livewire `rules()` — `nom` required max150, `niveau` required in CEFR list, `enseignant_id` nullable exists, `statut` required in a fixed subset, dates, and per-line `fraisLignes.*.montant` (required numeric ≥0), `.date_echeance` (nullable date), `.classification` (nullable, must be a CEFR value) |
| Policy permissions | `groups.view/create/update` (`GroupPolicy extends ResourcePolicy`; `delete()` hard-overridden to always return `false` — groups are never deleted); separate `archive` ability checks `groups.archive` + `withinCenter()` |
| Center scoping | Yes — `CenterAccessService::scopeAccessibleCenters()` (list) + `WithCenterContext::scopeToActiveCenter()`; new record's `etablissement_id`/`annee_scolaire_id` are **always** inherited from `CurrentContext`, never form fields |
| Academic-year scoping | Yes — list additionally filters `annee_scolaire_id` to the active context year; new records get the active context year, never user-chosen |
| Delete behavior | **No delete at all** — `GroupPolicy::delete()` returns `false` unconditionally, no destroy route exists, confirmed by the existing test `test_groups_cannot_be_deleted_and_have_no_destroy_route` |
| Archive/restore | "Fin de formation" is reached only via `Group::archiverCommeTermine()` (writes a `groups_historique` snapshot in the same transaction) — already migrated to Inertia in Phase 5 (`GroupController::archive()`), **out of Phase 8 scope, left untouched**. No "restore" exists anywhere in the codebase (a `groups_historique` row is a permanent read-only archive snapshot, not a soft-delete) |
| Professor assignment | `enseignant_id` — nullable `belongsTo(Employee::class, 'enseignant_id')`, options filtered to `categorie = Enseignant` + scoped to the active top-bar center (global-center teachers stay visible everywhere) |
| Fee assignment (the actual "complex" part of this form) | Every **active** catalog `Frais` row gets one line, always assigned (no checkbox) — `montant` (required numeric, defaults "0"), `date_echeance` (optional per-group due date), `classification` (optional, must be a CEFR value — lets one fee type serve multiple levels differently). On save, `group->frais()->sync($sync)` replaces the full pivot set every time (matching "all-or-nothing" full-catalog assignment, not incremental) |
| Search | `nom` only, `ilike` |
| Filters | Status tabs (`En formation` default / `Pré-inscription` / `Fin de formation` history) — each tab shows a live count badge scoped to the same center/year filters, ignoring search |
| Pagination | `WithPerPage` (10/25/50/100) |
| Relations | `belongsTo` enseignant, salle (unused in UI), etablissement, anneeScolaire; `hasMany` inscriptions; `belongsToMany` frais (pivot: montant, date_echeance, classification); `hasOne` historique |
| Eligible for Phase 8 | **Yes**, scoped to exactly the current UI (no room/capacity/schedule) |
| Migration risks | Medium. The fee-lines sub-form (dynamic array keyed by `frais_id`, always-all-assigned, no add/remove) is the most complex UI piece in this phase — needs its own small reusable component (not a generic "repeater", since every row is fixed to one catalog fee, never user-added/removed). The create-vs-edit `statut` options differ (create: 2 options; edit: all 3, with a silent-revert guard on direct Fin-de-formation attempts) — must preserve that exact asymmetry. |

---

## Summary eligibility table

| Module | Eligible | Notes |
|---|---|---|
| Students | Yes | Photo upload is the only non-trivial translation; guardian/contact fields already inline, no separate table |
| Groups | Yes | Scoped to current UI only (no room/capacity/schedule — confirmed absent from the live form); fee-lines sub-form is the main complexity |

## Decision (stakeholder-confirmed before implementation)

**Rooms/Capacity/Schedules**: Not migrated — they do not exist in the current
Livewire UI (`groups-index.blade.php`'s modal has no such fields, despite
`Group::$fillable` and the dead `StoreGroupRequest` supporting `salle_id`/
`capacite_max`). Per the task's own top-level rule ("Everything currently
implemented in Livewire must behave identically") and stop condition
("business rules are unclear"), Phase 8 migrates Groups exactly as it exists
today: Name, Level, Teacher, Status, Start/End dates, and per-group fee
lines. No new fields are added.
