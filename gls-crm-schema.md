# GLS CRM — Starter Database Schema (v4, Approved)

> Historical note: this document's stack line (Laravel 11 + MySQL) reflects the
> plan at design time, before the project was actually built on Laravel 13 and
> before the PostgreSQL-only migration completed on July 29, 2026. The **table
> design, relationships, and invariants below are still the approved schema and
> remain current** — only the engine name is outdated. For the current database
> rules (PostgreSQL-only, `ILIKE`, FK indexing, JSONB, etc.), see `CLAUDE.md`
> § "Database Standard — PostgreSQL Only", `POSTGRES_AUDIT.md`, and
> `POSTGRES_MIGRATION_REPORT.md`.

This is the schema GLS decided to build with, after simplifying and adapting the WimSchool reverse-engineering research (see `architecture.md`, `database-schema.md`, `startup-mvp.md`) into something buildable from scratch on the existing Laravel 11 + MySQL stack.

**This is not the full WimSchool reconstruction.** It's a deliberately reduced, from-scratch design: 15 tables, no `wimschool_id` migration columns, no approval-workflow bloat beyond what's actually needed for cash-handling fraud traceability. If you're looking for "what does WimSchool itself do," read `database-schema.md` instead — this file is "what GLS is actually building."

**Stack this schema targets:** Laravel 11, MySQL, Eloquent migrations. Backoffice UI: Blade + Livewire (not a separate SPA/API — see the stack rationale at the end of this file). `spatie/laravel-activitylog` recommended for `audit_logs`.

**PlantUML source** for the diagram this document describes: see the `gls_full_v4` block in this project's conversation history, or regenerate from the table list below.

**Folder structure:** see `gls-crm-laravel-structure.md` for how these tables map to actual Laravel models, migrations, controllers, and the staff-only auto-credential login flow — this file explains *what* each table is, that one explains *where the code lives*.

---

## How to read this document

Each table has: its purpose, why it exists (what decision led to it), its columns with type/nullability, its relationships, and — where relevant — a **"Why this shape"** note explaining a non-obvious design choice made during this project's back-and-forth, so nobody has to re-derive it later.

Legend: `*` before a column name in the source diagram meant "required/NOT NULL" — reflected here as **Nullable: No**.

---

## 1. `etablissements` (Branches / Centers)

**Purpose:** One row per physical or virtual GLS branch (e.g. GLS Marrakech, GLS Online). Everything else in the system is scoped to a branch through a direct or indirect `etablissement_id`.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| nom_centre | VARCHAR(150) | No | |
| ville | VARCHAR(100) | No | |
| telephone | VARCHAR(20) | Yes | |
| email | VARCHAR(255) | Yes | |
| siege_social | BOOLEAN | No | flags the head-office branch |
| created_at / updated_at | DATETIME | No | |

**Why this shape:** Kept intentionally minimal — no bilingual (FR/AR) name fields, no address breakdown, unlike WimSchool's fuller `etablissements` table. Add those back only if GLS's own branches genuinely need Arabic-language paperwork.

---

## 2. `annees_scolaires` (Academic Years)

**Purpose:** Defines school-year periods (e.g. "2025/2026"). Groups and Inscriptions are scoped to a year so historical data from past years stays cleanly separated from the active year.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| nom | VARCHAR(20) | No | e.g. "2025/2026" |
| date_debut | DATE | No | |
| date_fin | DATE | No | |
| par_defaut | BOOLEAN | No | which year is "current" |
| inscription_ouverte | BOOLEAN | No | whether new enrollments are accepted this year |
| created_at / updated_at | DATETIME | No | |

---

## 3. `salles` (Rooms)

**Purpose:** Room/venue catalog, one branch's rooms clearly separated from another's. Added specifically because GLS confirmed every `etablissement` has its own physical or virtual rooms and groups need to be assignable to one.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| nom | VARCHAR(100) | No | e.g. "Salle 01", or a themed name for online rooms |
| etablissement_id | BIGINT FK → etablissements | No | every room belongs to exactly one branch |
| capacite | INT | Yes | seat count |
| statut | VARCHAR(20) | No | |
| created_at / updated_at | DATETIME | No | |

**Relationships:** belongs to `etablissements`; referenced by `groups.salle_id` (nullable — a group doesn't strictly require a room assigned).

---

## 4. `employees`

**Purpose:** Master staff record — **teachers are employees**, not a separate table (a deliberate decision carried over from the WimSchool research: `categorie` distinguishes "Enseignant" from "Directeur," "Assistante administrative," etc.).

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| reference | VARCHAR(20) | No | staff reference code |
| nom | VARCHAR(100) | No | |
| prenom | VARCHAR(100) | No | |
| categorie | VARCHAR(30) | No | role: Enseignant / Directeur / Assistante administrative / etc. |
| statut | VARCHAR(20) | No | Actif / Inactif |
| telephone | VARCHAR(20) | Yes | |
| whatsapp | VARCHAR(20) | Yes | |
| email | VARCHAR(255) | Yes | |
| etablissement_id | BIGINT FK → etablissements | Yes | primary branch |
| user_id | BIGINT FK → users | Yes | links to the Laravel login account, if the employee has one |
| created_at / updated_at | DATETIME | No | |

**Relationships:** belongs to `etablissements`; referenced everywhere as `agent_id`/`enseignant_id`/`created_by`/`requested_by`/etc. across nearly every other table.

**Why this shape:** `categorie` is a plain VARCHAR, not a lookup table — a handful of role names doesn't need its own table at this scale. Enforce the allowed values in a Laravel enum/validation rule, not the database.

---

## 5. `students`

**Purpose:** The person taking classes. Parent/guardian contact info lives directly on this table (no separate `parents` table) since GLS chose to merge that for simplicity.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| reference | VARCHAR(20) | No | |
| nom | VARCHAR(100) | No | |
| prenom | VARCHAR(100) | No | |
| sexe | VARCHAR(10) | Yes | |
| date_naissance | DATE | Yes | |
| telephone | VARCHAR(20) | Yes | |
| whatsapp | VARCHAR(20) | Yes | kept separate from telephone — WhatsApp is GLS's dominant communication channel |
| email | VARCHAR(255) | Yes | |
| adresse | VARCHAR(255) | Yes | |
| niveau | VARCHAR(10) | Yes | e.g. "A1.1", "B2.3" — see note below |
| etablissement_id | BIGINT FK → etablissements | Yes | branch the student registered at |
| parent_nom | VARCHAR(100) | Yes | |
| parent_relation | VARCHAR(50) | Yes | "Categorie" — Le père / La mère / Le parrain (`Student::PARENT_RELATIONS`) |
| parent_sexe | VARCHAR(10) | Yes | validated against `Student::SEXES` |
| parent_cin | VARCHAR(30) | Yes | national ID of the parent/guardian |
| parent_telephone | VARCHAR(20) | Yes | |
| parent_whatsapp | VARCHAR(20) | Yes | |
| note | TEXT | Yes | |
| created_at / updated_at | DATETIME | No | |

**Why `niveau` is a plain VARCHAR, not a lookup table (`niveaux` FK):** GLS's level list is a fixed, essentially-never-changing set of 11 values — A1.1, A1.2, A2.1, A2.2, A2.3, B1.1, B1.2, B1.3, B2.1, B2.2, B2.3 (standard CEFR German-language sublevels). A separate `niveaux` table + FK was in an earlier draft of this schema and was explicitly removed because it added a join for values that are effectively hardcoded. **Trade-off accepted deliberately:** nothing at the database level stops a typo (`"a1.1"` vs `"A1.1"`); enforce the 11 valid values via a Laravel model constant/validation rule (e.g. `const NIVEAUX = ['A1.1', 'A1.2', ...]`), not a database constraint.

**Why parent info is inline, not a separate table:** simplest option for a startup — GLS accepted losing "multiple guardians per student" and "who's authorized to pick up the student" (both real WimSchool features) in exchange for one less table and no join needed on every student read. Revisit only if a real operational need for multiple guardians per student comes up.

---

## 6. `groups`

**Purpose:** A class/cohort — students enroll into a group, taught by one employee, in one room, for one academic year.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| nom | VARCHAR(150) | No | |
| niveau | VARCHAR(10) | No | same plain-VARCHAR pattern as `students.niveau` |
| enseignant_id | BIGINT FK → employees | Yes | |
| salle_id | BIGINT FK → salles | Yes | |
| etablissement_id | BIGINT FK → etablissements | Yes | |
| annee_scolaire_id | BIGINT FK → annees_scolaires | Yes | |
| capacite_max | INT | Yes | |
| statut | VARCHAR(20) | No | **3-state workflow — see below** |
| date_debut_formation | DATE | Yes | when the group entered "En formation" |
| date_fin_formation | DATE | Yes | when the group entered "Fin de formation" |
| created_at / updated_at | DATETIME | No | |

**`statut` lifecycle (mirrors WimSchool's own confirmed 3-tab Group workflow — "En inscription" / "En formation" / "Historique"):**

1. **`"En inscription"`** — group created, students can enroll, class hasn't started yet.
2. **`"En formation"`** — class is actively running.
3. **`"Fin de formation"`** — class has finished. **The moment `statut` is set to this value, a snapshot row must be inserted into `groups_historique` in the same database transaction** (application-layer responsibility — not a database trigger). The `groups` row itself is **never deleted**.

**Why the group row is never deleted, even after "Fin de formation":** so `inscriptions.group_id` always stays a valid foreign key. Deleting or "moving" the row would orphan every historical enrollment record tied to it. `groups_historique` exists purely as an archive/reporting snapshot, not a replacement storage location.

---

## 7. `groups_historique` (Group Archive)

**Purpose:** A read-only snapshot taken automatically when a group finishes, so reporting on "every group that ever ran and its final headcount" doesn't have to worry about the live `groups` table changing later.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| group_id | BIGINT FK → groups | No | points back to the still-live group row |
| nom | VARCHAR(150) | No | copied at archive time |
| niveau | VARCHAR(10) | No | copied at archive time |
| enseignant_id | BIGINT FK → employees | Yes | copied at archive time |
| etablissement_id | BIGINT FK → etablissements | Yes | |
| annee_scolaire_id | BIGINT FK → annees_scolaires | Yes | |
| nombre_etudiants_final | INT | Yes | headcount frozen at the moment of archiving |
| date_debut_formation | DATE | Yes | |
| date_fin_formation | DATE | Yes | |
| archived_at | DATETIME | No | |
| archived_by | BIGINT FK → employees | Yes | who triggered the archive — ties into fraud/traceability |

**Why columns are copied here instead of just joining back to `groups`:** if `groups.nom` or `groups.enseignant_id` is ever edited after the group finished (e.g. a data-entry correction), the historical snapshot should still reflect what was true *at the time it finished*, not whatever the live row says today.

---

## 8. `inscriptions` (Enrollments)

**Purpose:** The central join between a Student and a Group — one paid enrollment with its own lifecycle.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| reference | VARCHAR(20) | No | |
| student_id | BIGINT FK → students | No | |
| group_id | BIGINT FK → groups | No | |
| etablissement_id | BIGINT FK → etablissements | Yes | |
| annee_scolaire_id | BIGINT FK → annees_scolaires | Yes | |
| statut | VARCHAR(30) | No | Active / Expirée / Archivée / Annulée |
| date_inscription | DATE | No | |
| date_debut | DATE | Yes | |
| date_fin | DATE | Yes | |
| montant_total | DECIMAL(10,2) | Yes | total value of the enrollment |
| note | TEXT | Yes | |
| created_by | BIGINT FK → employees | Yes | staff member who created the enrollment |
| created_at / updated_at | DATETIME | No | |

**Relationships:** belongs to `students`, belongs to `groups`, has many `inscription_fees`.

---

## 9. `inscription_fees`

**Purpose:** Individual fee line items owed for a given enrollment (e.g. "Frais d'inscription," "Frais de Juillet"). Each line has its own amount and due date, so payments can be allocated per-fee rather than as one lump sum.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| inscription_id | BIGINT FK → inscriptions | No | |
| nom | VARCHAR(150) | No | e.g. "Frais de Juillet" |
| montant | DECIMAL(10,2) | No | |
| date_echeance | DATE | No | due date |
| statut | VARCHAR(20) | No | e.g. Non payé / Payé partiellement / Payé |
| created_at / updated_at | DATETIME | No | |

**Relationships:** belongs to `inscriptions`; has many `encaissements` (a fee can be paid across multiple partial payments).

---

## 10. `caisses` (Cash Registers / Tills)

**Purpose:** One row per till — running cash balance, scoped to a branch and (optionally) a responsible employee.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| nom | VARCHAR(100) | No | |
| etablissement_id | BIGINT FK → etablissements | Yes | |
| responsable_employee_id | BIGINT FK → employees | Yes | assigned cashier |
| solde | DECIMAL(12,2) | Yes | current balance — kept as a stored/updated number, not computed live from a ledger table |
| statut | VARCHAR(20) | No | |
| created_at / updated_at | DATETIME | No | |

**Why `solde` is a plain stored column, not computed from a movements ledger:** GLS simplified away the more elaborate `caisse_movements` unified-ledger pattern (seen in an earlier, much larger draft of this schema). `solde` must be updated in application code every time an `encaissement`, `depense`, `remboursement`, or `caisse_transfer` touches this till. **This is a deliberate simplicity trade-off** — if `solde` ever drifts from reality due to a bug, there's no ledger to replay and recompute it from. If that becomes a real problem in production, that's the signal to reintroduce a movements table.

---

## 11. `encaissements` (Payments Received)

**Purpose:** Every payment collected from a student — cash, card, or cheque, tracked through one unified table.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| reference | VARCHAR(20) | No | |
| student_id | BIGINT FK → students | No | |
| inscription_fee_id | BIGINT FK → inscription_fees | No | which fee this payment applies to |
| montant | DECIMAL(12,2) | No | |
| methode | VARCHAR(30) | No | Espèces / TPE (card) / Chèque / Virement |
| date_paiement | DATE | No | |
| caisse_id | BIGINT FK → caisses | No | which till the money went into |
| agent_id | BIGINT FK → employees | No | who processed the payment — accountability trail |
| numero_cheque | VARCHAR(50) | Yes | only populated if methode = Chèque |
| banque | VARCHAR(100) | Yes | only populated if methode = Chèque |
| date_echeance_cheque | DATE | Yes | only populated if methode = Chèque |
| note | TEXT | Yes | |
| created_at / updated_at | DATETIME | No | |

**Why cheque data is inline here instead of a separate `cheques` table:** GLS simplified away WimSchool's full cheque lifecycle (deposit/encash/reject state machine with its own approval workflow). A cheque, in this schema, is just a payment method with 3 extra fields. **Trade-off accepted:** no dedicated "cheques to deposit at the bank" or "bounced cheque" tracking view — if that becomes a real operational need, it's the signal to reintroduce a proper `cheques` table with its own status lifecycle.

**How an "avance" (advance/unallocated prepayment) works in this schema:** there is no separate `avances` table (also simplified away). An advance payment is simply an `encaissement` row — the application layer would need to support creating one before a specific `inscription_fee_id` exists yet, or you allocate it to a placeholder/future fee. **Flag for whoever builds this:** decide explicitly how advances are represented before writing the payment-creation form, since `inscription_fee_id` is currently marked required (No) in this table — if you want true unallocated advances, that column needs to become nullable.

---

## 12. `types_depenses` (Expense Type Catalog)

**Purpose:** Configurable list of what an expense can be categorized as, with a system/custom split matching WimSchool's confirmed pattern.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| nom | VARCHAR(100) | No | e.g. "Paiement prof", "Salaire", "Produits consommables" |
| is_system | BOOLEAN | No | true = protected/reserved type tied to code logic, cannot be freely edited/deleted |
| statut | VARCHAR(20) | No | |
| created_at / updated_at | DATETIME | No | |

**Why `is_system` matters:** types like "Paiement prof" (teacher payment) and "Transfert à une autre caisse" should be locked from admin editing because other parts of the system (payroll automation, till transfers) depend on their exact name/id existing. Custom types (e.g. "Produits consommables") are freely admin-manageable.

**Direct link to GLS's own in-progress work:** "Paiement prof" being a first-class expense type here is what makes teacher-payment automation (calculated from attendance data, a project GLS already has underway) representable in this schema once attendance tracking is added back in a later phase.

---

## 13. `depenses` (Expenses)

**Purpose:** Every outflow of cash from a till.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| reference | VARCHAR(20) | No | |
| type_depense_id | BIGINT FK → types_depenses | No | |
| caisse_id | BIGINT FK → caisses | No | |
| montant | DECIMAL(12,2) | No | |
| date_depense | DATE | No | |
| description | VARCHAR(255) | Yes | |
| mots_cles | VARCHAR(255) | Yes | free-text tags for search/filtering — see note below |
| note | TEXT | Yes | |
| agent_id | BIGINT FK → employees | No | |
| created_at / updated_at | DATETIME | No | |

**Why `mots_cles` is one VARCHAR column, not a separate tags table:** GLS wanted the ability to attach multiple searchable keywords to an expense (e.g. `"urgent,fournisseur-x,q3"`) without building a full many-to-many tagging system. Store as comma-separated text (or a JSON array if preferred) and search with `LIKE '%keyword%'` or MySQL's `FULLTEXT` index. This is a deliberate simplicity choice — revisit only if GLS needs real tag autocomplete/tag management UI later.

---

## 14. `remboursements` (Refunds)

**Purpose:** A dedicated, simple audit trail for money refunded to a student — kept separate from generic `depenses` even though both drain a `caisse`.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| reference | VARCHAR(20) | No | |
| beneficiaire_id | BIGINT FK → students | No | |
| caisse_id | BIGINT FK → caisses | No | |
| montant | DECIMAL(12,2) | No | |
| date_remboursement | DATE | No | |
| motif | VARCHAR(255) | Yes | reason for the refund |
| note | TEXT | Yes | |
| agent_id | BIGINT FK → employees | No | |
| created_at / updated_at | DATETIME | No | |

---

## 15. `caisse_transfers` (Till-to-Till Transfers)

**Purpose:** Lets an employee move money from one till to another, with a full audit trail of both balances before and after, plus a request/approval split — this is the highest-fraud-risk operation in the whole system (moving physical cash between people), so it gets the most rigorous tracking of any table here.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| reference | VARCHAR(20) | No | |
| caisse_source_id | BIGINT FK → caisses | No | |
| caisse_destination_id | BIGINT FK → caisses | No | |
| montant | DECIMAL(12,2) | No | |
| date_transfert | DATETIME | No | |
| solde_source_avant | DECIMAL(12,2) | Yes | source till balance immediately before |
| solde_source_apres | DECIMAL(12,2) | Yes | source till balance immediately after |
| solde_dest_avant | DECIMAL(12,2) | Yes | destination till balance immediately before |
| solde_dest_apres | DECIMAL(12,2) | Yes | destination till balance immediately after |
| statut | VARCHAR(20) | No | e.g. En attente / Validé / Annulé |
| note | TEXT | Yes | |
| requested_by | BIGINT FK → employees | No | who initiated the transfer |
| validated_by | BIGINT FK → employees | Yes | who approved it — nullable until approved |
| created_at / updated_at | DATETIME | No | |

**Why `requested_by` and `validated_by` are two different, separately-tracked people:** this is the core fraud-prevention mechanism for cash handling — one employee can't both claim a transfer happened *and* be the sole confirmation that it did. `validated_by` stays NULL until a supervisor/director actually confirms the transfer, matching the approval-gated workflow GLS specifically asked for.

**Why the four `solde_*` snapshot columns exist:** if `caisses.solde` is ever found to be wrong later, these columns let you reconstruct what the balance *should* have been at the exact moment of each transfer — the safety net for the "CEO wants to detect fraud" requirement, specific to cash movement between employees.

---

## 16. `audit_logs` (Global Traceability / Fraud Detection)

**Purpose:** Answers GLS's explicit requirement: the CEO must be able to see every action taken in the CRM — creates, edits, and deletes — across every table, including who did it and what changed.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| id | BIGINT PK | No | |
| table_name | VARCHAR(100) | No | which table the action happened on |
| record_id | BIGINT | No | which row |
| action | VARCHAR(20) | No | "created" / "updated" / "deleted" |
| employee_id | BIGINT FK → employees | No | who did it |
| old_values | JSON | Yes | full previous state (for updates/deletes) |
| new_values | JSON | Yes | full new state (for creates/updates) |
| ip_address | VARCHAR(45) | Yes | flags edits from unexpected locations |
| created_at | DATETIME | No | |

**How this is actually wired up (application-layer, not a database relationship):** this table is **not** connected to every other table via individual foreign keys in the diagram — that would clutter the schema without adding real information. In the real Laravel build, every model that matters for fraud detection (`encaissements`, `depenses`, `remboursements`, `caisse_transfers`, `inscriptions`, `students`) should use an auditing trait/observer — the recommended package is **`spatie/laravel-activitylog`**, which implements exactly this pattern and writes to a table shaped like this one automatically on every model event.

**Security note for whoever deploys this:** for the audit log to be genuinely tamper-resistant (so even a compromised admin account can't quietly erase evidence), configure the production database user Laravel connects with to have **INSERT-only permission on `audit_logs`** — no `UPDATE` or `DELETE` grant — at the MySQL user-permission level. This is a deployment/ops decision, not something the schema itself enforces, but it's the difference between "we log actions" and actual tamper-evidence.

---

## Full Relationship Summary

```
etablissements ──1:M──> salles, employees, students, groups, groups_historique, inscriptions, caisses
annees_scolaires ──1:M──> groups, groups_historique, inscriptions
salles ──1:M──> groups (salle_id)
employees ──1:M──> groups (enseignant_id), groups_historique (enseignant_id, archived_by),
                    inscriptions (created_by), caisses (responsable_employee_id),
                    encaissements (agent_id), depenses (agent_id), remboursements (agent_id),
                    caisse_transfers (requested_by, validated_by), audit_logs (employee_id)
groups ──1:M──> groups_historique (group_id), inscriptions (group_id)
students ──1:M──> inscriptions (student_id), encaissements (student_id), remboursements (beneficiaire_id)
inscriptions ──1:M──> inscription_fees (inscription_id)
inscription_fees ──1:M──> encaissements (inscription_fee_id)
caisses ──1:M──> encaissements, depenses, remboursements, caisse_transfers (as source AND destination)
types_depenses ──1:M──> depenses (type_depense_id)
```

---

## Deliberate Simplifications Made During This Project (Read Before Extending)

Every one of these was a conscious trade-off during the design conversation, not an oversight. If you find yourself needing to "fix" one of these, it means GLS has outgrown the startup-scale assumption — treat it as a signal to extend the schema, not a bug in this document.

| Simplified from → to | What was lost | When to reconsider |
|---|---|---|
| `guardians` + `inscription_guardians` join tables → 3 plain columns on `students` | Multiple guardians per student, pickup-authorization tracking, split billing responsibility | A family situation genuinely needs more than one contact tracked |
| Full `cheques` table with deposit/encash/reject lifecycle → 3 columns inline on `encaissements` | Dedicated "cheques to deposit" / "bounced cheques" views, cheque-specific approval workflow | Cheque volume is high enough that manual tracking via the payments list isn't enough |
| `avances` / `avance_applications` / `avance_refunds` (3 tables) → implicit via `encaissements` | Clean advance-payment tracking with its own state | Advances become common enough to need their own reporting view (note: `inscription_fee_id` is currently required — must become nullable to truly support this) |
| `caisse_movements` unified ledger → `caisses.solde` as a directly-updated stored number | Ability to replay/recompute balance history from a ledger if it ever drifts wrong | A balance discrepancy actually happens in production and can't be traced |
| `niveaux` lookup table → plain VARCHAR on `students`/`groups` | Database-level protection against typos in level codes | Level list stops being a small, fixed, rarely-changing set |
| `employee_etablissement` many-to-many → single `employees.etablissement_id` | Teachers/staff working across multiple branches | GLS opens a second branch and shares staff between them |
| Approval workflow columns (`validated_by`, `validated_at`, `cancelled_by`) on most tables → kept only on `caisse_transfers` | Formal approval gates on expenses, refunds, enrollment changes | A specific operation (not just till transfers) becomes a proven fraud/error risk |

---

## Stack Recommendation (Reference)

- **Backend:** Laravel 11 (already the GLS stack — don't rewrite).
- **Database:** MySQL (already the GLS stack).
- **Admin/backoffice UI:** Blade + Livewire — every table above is a CRUD-heavy admin screen, not a candidate for a separate SPA.
- **Audit logging:** `spatie/laravel-activitylog`, wired to `audit_logs`.
- **Future student/parent portal (not in this schema's scope):** the one place a decoupled API + Next.js frontend would genuinely pay off — build only once/if that portal is actually scoped, per `startup-mvp.md`.

*End of gls-crm-schema.md.*
