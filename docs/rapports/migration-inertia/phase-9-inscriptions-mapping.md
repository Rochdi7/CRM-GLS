# Phase 9 — Inscriptions Livewire → Inertia Mapping

Every value/behavior the Livewire `InscriptionsIndex` component computes or
enforces, mapped 1:1 to its new source. Companion to
`docs/phase-9-inscriptions-audit.md` (read that first for the full
narrative); this document is the terse field-by-field reference used
during implementation and review.

---

## List

| Livewire (`render()`) | New source | Notes |
|---|---|---|
| `$inscriptions` paginated query | `App\Domain\Registrations\Queries\GetInscriptionsList::__invoke()` | Same eager loads (`student`, `group`), `withCount('fees')`, same center/year scoping, same search columns, same `latest()` order, same per-page options |
| `$this->students()` (Computed) | `GetInscriptionFormOptions::students()` | Same center+active-center scoping, no year scoping, ordered by `nom` |
| `$this->groups()` (Computed) | `GetInscriptionFormOptions::groups()` | Same center+active-center+active-year scoping, ordered by `nom` |
| `Inscription::STATUTS` | passed as `statuts` prop | unchanged constant |
| `Student::NIVEAUX`/`DOMAINES`/`EXAMEN_TYPES`/`SEXES`/`PARENT_RELATIONS` | passed as props | unchanged constants, same as Students Phase 8 |

List row shape (`GetInscriptionsList`'s `->through()`):

| Field | Source |
|---|---|
| `id` | `$inscription->id` |
| `reference` | `$inscription->reference` |
| `student` | `$inscription->student?->nomComplet()` |
| `studentShowUrl` | `route('backoffice.students.show', $inscription->student)` if student exists |
| `groupe` | `$inscription->group?->nom` |
| `date` | `$inscription->date_inscription?->format('d/m/Y')` |
| `montantTotal` | `$inscription->montant_total !== null ? number_format(..., 2, '.', '') : null` — **string or null, never a float** |
| `feesCount` | `$inscription->fees_count` |
| `statut` | `$inscription->statut` |
| `showUrl` | `route('backoffice.inscriptions.show', $inscription)` |

---

## Create — existing student

| Livewire | New source |
|---|---|
| `inscriptionMode = 'existing'`, `student_id` (Select2, required) | Same field, same validation (`required, exists:students,id`) |
| `group_id` (Select2, live, required) | Same field; `groupId` change triggers the SAME server-side fee-line load as edit-time group inspection (see below) |
| `date_inscription` (required, date) | Same |
| `note` (nullable) | Same |

## Create — new student inline

Every `new_*` field maps 1:1 to the exact same validation as
`StudentsIndex`/Phase 8's `StudentController` (`docs/phase-8-students-groups-inventory.md`),
reused here rather than reinvented:

| Livewire field | Rule | New source |
|---|---|---|
| `new_nom` | required, string, max:100 | same |
| `new_prenom` | required, string, max:100 | same |
| `new_sexe` | nullable, in Student::SEXES | same |
| `new_date_naissance` | nullable, date, before:today | same |
| `new_cin` | nullable, string, max:30 | same |
| `new_niveau` | nullable, in Student::NIVEAUX | same |
| `new_domaine` | conditionally required (Arbeit/Ausbildung) | same `Student::niveauDemandeDomaine()` helper |
| `new_examen_type` | conditionally required (Studium) | same `Student::niveauDemandeExamen()` helper |
| `new_email`/`new_telephone`/`new_whatsapp`/`new_adresse` | nullable, standard | same |
| `new_parent_*` (6 fields) | same as Student's Parent tab | same |
| `phonePays` (Livewire) → `phone_pays` (Inertia) | required, in Countries::LIST keys | same rule, renamed field to match Phase 8's Student convention |

**Server-side creation** (`buildNewStudentPayload()`, new private method
mirroring `save()`'s inline student-creation block exactly):
- `reference` via `ReferenceGenerator::make('ETU', 'students')`
- Phone/whatsapp/parent-phone/parent-whatsapp combined via
  `Countries::join($phonePays, $national)` (same helper Phase 8's
  `StudentController` already uses — no new phone-combining logic)
- Only the orientation field matching the chosen track is stored (`domaine`
  XOR `examen_type`, never both)
- `etablissement_id` = **the group's center**, not the acting user's
  context — must NOT reuse Phase 8's own "lock to CurrentContext" logic,
  since this creation path always derives center from the group

## Create — fee lines

| Livewire | New source |
|---|---|
| `updatedGroupId()` → `loadGroupFees()` | `GET backoffice/groups/{group}/inscription-fees` — see "Group fee lookup" below (decision confirmed) |
| Active-only filter (`Frais::STATUT_ACTIF`) | Same `where('statut', Frais::STATUT_ACTIF)` filter, applied server-side, never client-filtered |
| `montant_initial` = group's per-fee pivot `montant` | Same — read from `group_frais.montant`, not the catalog's own amount |
| `date_echeance` default = group's per-fee pivot `date_echeance`, else today | Same fallback logic |
| Two-way pct↔DH sync (`updated()`) | **React-side UI convenience only** — recomputes for DISPLAY as the user types, using the exact same formula (`round(initial * pct / 100, 2)` and its inverse); the ACTUAL persisted `montant` is always recomputed server-side from whatever `montant_initial`/`remise_pct`/`remise_montant` the request contains, via the unchanged `InscriptionFee::computeMontant()` — client-side sync is never trusted as the source of truth |
| `lineMontant(int $index)` live preview | React computes the same formula client-side for display (`computeLineMontant()` helper in the page, mirroring `InscriptionFee::computeMontant()`'s exact logic) — **display only**; the server recomputes independently and that server value is what persists |
| `feeLines.*.montant_initial`/`.remise_pct`/`.remise_montant`/`.date_echeache` validation | Same rules, same conditional-only-on-create scoping |

**Group fee lookup — decision confirmed (stakeholder-approved before
implementation)**: a dedicated lightweight endpoint,
`GET backoffice/groups/{group}/inscription-fees`, returns just that one
group's active assigned fees (`frais_id`, `nom`, `montant`, `dateEcheance`)
on demand when the group select changes — one small request per
group-selection, mirroring the Livewire round-trip's own on-demand nature.
Rejected the alternative (embedding every group's fees in the initial
`groups` options payload) because payload size would scale with
groups × fees-per-group, unlike Phase 8's Groups-edit-modal case where only
ONE row's fees needed to travel. Authorization: same `registrations.create`
permission + center/year scoping as the parent inscription create action
(a user who can create a registration must be able to see a group's fees to
select them) — NOT gated by `registrations.manage-fees` (§ audit doc §12
point 1 — that permission is not part of the live create workflow).

## Create — server-side finalization (`save()`, `!$editing` branch)

| Step | New source |
|---|---|
| `$lines = $selected->map(...)` — recompute `montant` from `computeMontant()` | Identical — server NEVER trusts a client-sent final amount |
| `$total = $lines->sum('montant')` | Identical — `montant_total` has exactly one source |
| `$total > 0 ? $total : null` | Identical — zero-fee inscriptions store `null`, not `0` |
| `Inscription::create([...])` | Identical field set; `etablissement_id`/`annee_scolaire_id`/`date_debut`/`date_fin` ALL re-derived from the group, never from client input, even if the client sends them |
| `statut` hardcoded `Active` | Identical — server ignores any submitted `statut` on create |
| `created_by` = `auth()->user()->employee?->id` | Identical |
| `foreach ($lines as $line) { $inscription->fees()->create($line); }` | Identical — one `InscriptionFee` row per line, same transaction |

## Edit (`save()`, `$editing` branch)

| Field | Behavior |
|---|---|
| `student_id`, `group_id`, `statut`, `date_inscription`, `date_debut`, `date_fin`, `note` | The ONLY 6 columns ever updated |
| Fee lines | **Never touched on edit** — no fee-line UI renders in edit mode at all (§ audit doc §4.2) |
| `date_debut`/`date_fin` | Taken directly from submitted values, NOT re-derived from the group (unlike create) — audit doc §12 flags this asymmetry explicitly; must be preserved exactly, not "fixed" |

## Delete

| Livewire | New source |
|---|---|
| `try { $inscription->delete(); } catch (QueryException) { addError(...) }` | Identical mechanism — `try/catch (\Illuminate\Database\QueryException)`, converted to a `delete` field error via `ValidationException::withMessages` (matching Phase 6/7/8's 422-not-flash convention for delete refusals), message: "This registration has payments and cannot be deleted." |

**Do NOT replace with a pre-count guard** (audit doc §4.8) — the DB
constraint is the actual mechanism and must stay the actual mechanism.

---

## Money handling (CLAUDE.md §17 + this task's explicit rules)

Every amount (`montant_initial`, `remise_montant`, `montant`,
`montant_total`, per-fee `paye`, inscription `totalDu`/`totalPaye`/`reste`)
is `decimal(10,2)` in the database and MUST cross the wire as a
pre-formatted string (`number_format($value, 2, '.', '')`), matching every
prior phase's convention — the list page, the create-form group-fee
lookup response, and any error payload. React never performs the discount
calculation as the source of truth — only a mirrored display-only
computation for live UI feedback (`lineMontant`-equivalent), exactly as the
Livewire Blade view itself does via a `@php` call to the same PHP method
(`$this->lineMontant($i)`) — so even the ORIGINAL implementation recomputes
for display without persisting that computed value directly; Phase 9
preserves that exact pattern, just moving the display-recompute into
TypeScript instead of Blade, while the server remains the only writer.

---

## Permission map (unchanged names, per audit doc §3)

| Action | Permission checked |
|---|---|
| List / view | `registrations.view` |
| Create (existing or new student, incl. fee lines) | `registrations.create` |
| Update | `registrations.update` |
| Delete | `registrations.delete` |
| (Dead code only) Fee-line CRUD via `InscriptionFeeController` | `registrations.manage-fees` — NOT checked by the live create/update path, preserved as-is (audit doc §12 point 1) |

---

## Center/year scoping map

| Scope | Mechanism | Unchanged from |
|---|---|---|
| List | `CenterAccessService::scopeAccessibleCenters()` + `WithCenterContext::scopeToActiveCenter()`-equivalent + explicit `annee_scolaire_id` filter | Groups (Phase 8) |
| Students option list (existing-mode picker) | center + active-center scoped, NO year scoping | Students has no year column |
| Groups option list | center + active-center + active-year scoped | Groups (Phase 8) |
| New-student creation | `etablissement_id` = the group's center | New — Inscriptions-specific (neither Students nor Groups create a record scoped to "another record's center") |
