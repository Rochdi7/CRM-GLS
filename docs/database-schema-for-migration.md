# GLS CRM — Database Schema Reference (for old-CRM data migration)

Generated from the live PostgreSQL database (`gls_crm`) + Laravel model constants.
Use this as the target-schema reference when mapping/importing data from the old CRM.

General conventions used everywhere in this schema:

- Every table has `id` (bigint, auto-increment PK), `created_at`, `updated_at` (nullable timestamps, no timezone).
- `reference` columns (employees, students, inscriptions, encaissements, depenses, remboursements, caisse_transfers, stock_articles) are **system-generated** codes like `ETU-042`, `EMP-013`, `INS-007`, `ENC-118`, `DEP-045`, `RMB-009`, `TRF-003` — never import raw text into these, generate them the same way (prefix + zero-padded incrementing number) or leave blank and let the app assign them.
- Money columns are `numeric(12,2)` (or `numeric(10,2)` for a few smaller amounts) — always fixed-point decimals, never float.
- `statut` / `type` / `categorie` / `niveau` columns are plain VARCHAR validated against fixed PHP lists (documented per table below) — not DB-level enums or lookup tables. Import values must match these lists **exactly** (case + accents + spelling), or validation will reject them going forward.
- Booleans (`bool`/`boolean`) are Postgres `true`/`false`.
- All FKs are declared with actual Postgres foreign-key constraints (see the "Foreign keys" line per table).

---

## 1. `etablissements` (Centers / branches)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom_centre | varchar(150) | NO | | Center name |
| ville | varchar(100) | NO | | City |
| adresse | varchar(255) | YES | | |
| ice | varchar(30) | YES | | Moroccan tax ID (ICE) |
| telephone | varchar(20) | YES | | |
| email | varchar(255) | YES | | |
| siege_social | boolean | NO | false | Marks the head-office center |
| created_at / updated_at | timestamp | YES | | |

No FKs. Referenced by almost every other table via `etablissement_id`.

---

## 2. `annees_scolaires` (Academic years)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(20) | NO | | e.g. `2025/2026` |
| date_debut | date | NO | | |
| date_fin | date | NO | | |
| par_defaut | boolean | NO | false | Only one row should be true (default academic year) |
| inscription_ouverte | boolean | NO | true | Whether new inscriptions are allowed this year |
| created_at / updated_at | timestamp | YES | | |

---

## 3. `salles` (Rooms)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(100) | NO | | |
| etablissement_id | bigint FK→etablissements | NO | | |
| capacite | integer | YES | | |
| statut | varchar(20) | NO | 'Active' | `Active` / `Inactive` |
| created_at / updated_at | timestamp | YES | | |

---

## 4. `employees` (Staff / teachers)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated `EMP-###` |
| nom | varchar(100) | NO | | |
| prenom | varchar(100) | NO | | |
| sexe | varchar(10) | YES | | `Homme` / `Femme` |
| date_naissance | date | YES | | |
| date_embauche | date | YES | | Hire date |
| salaire | numeric(10,2) | YES | | |
| categorie | varchar(30) | NO | | See CATEGORIES list below |
| statut | varchar(20) | NO | 'Actif' | `Actif` / `Inactif` |
| telephone | varchar(20) | YES | | |
| whatsapp | varchar(20) | YES | | |
| email | varchar(255) | YES | | |
| note | text | YES | | |
| adresse | varchar(255) | YES | | |
| etablissement_id | bigint FK→etablissements | YES | | |
| user_id | bigint FK→users | YES | | Auto-created login (username + one-time password) on employee creation |
| created_at / updated_at | timestamp | YES | | |

**CATEGORIES** (exact strings): `Directeur`, `Commercial`, `Enseignant`, `Comptable`, `Responsable Marketing`, `Assistante administrative`, `Directeur des opérations`, `Directrice pédagogique`, `Directeur Qualité et Amélioration continue`, `Autre`.

Note: creating an employee auto-creates its `users` row via an observer — when migrating, decide whether to import employees through the app (so logins get created) or insert directly and create `users` rows yourself with `user_id` set.

---

## 5. `users` (Login accounts)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| name | varchar(255) | NO | | |
| email | varchar(255) | NO | | |
| username | varchar(255) | YES | | Login can be email OR username |
| email_verified_at | timestamp | YES | | |
| password | varchar(255) | NO | | Hashed |
| must_change_password | boolean | NO | true | |
| is_active | boolean | NO | true | Deactivated accounts cannot log in |
| remember_token | varchar(100) | YES | | |
| created_at / updated_at | timestamp | YES | | |

Roles/permissions live in separate spatie/laravel-permission tables (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) — not usually part of a CRM data migration (recreate roles fresh instead of importing).

---

## 6. `students` (Étudiants)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated `ETU-###` |
| nom | varchar(100) | NO | | |
| prenom | varchar(100) | NO | | |
| sexe | varchar(10) | YES | | `Homme` / `Femme` |
| date_naissance | date | YES | | |
| cin | varchar(30) | YES | | National ID |
| telephone | varchar(20) | YES | | |
| whatsapp | varchar(20) | YES | | |
| email | varchar(255) | YES | | |
| adresse | varchar(255) | YES | | |
| niveau | varchar(20) | YES | | See NIVEAUX below |
| domaine | varchar(60) | YES | | Only for Arbeit/Ausbildung tracks — see DOMAINES |
| examen_type | varchar(10) | YES | | Only for Studium track — `STK` / `DSH` |
| etablissement_id | bigint FK→etablissements | YES | | |
| parent_nom | varchar(100) | YES | | Parent/guardian name (inline, no separate table) |
| parent_relation | varchar(50) | YES | | `Le père` / `La mère` / `Le parrain` |
| parent_sexe | varchar(10) | YES | | `Homme` / `Femme` |
| parent_cin | varchar(30) | YES | | |
| parent_telephone | varchar(20) | YES | | |
| parent_whatsapp | varchar(20) | YES | | |
| note | text | YES | | |
| created_at / updated_at | timestamp | YES | | |

Also has a `photo` (single file) and `documents` media collection (spatie/medialibrary) — not columns, stored in the `media` table.

**NIVEAUX** (full dropdown, `niveau` column) — CEFR sublevels **+** German-destination tracks:
- CEFR: `A1.1`, `A1.2`, `A2.1`, `A2.2`, `A2.3`, `B1.1`, `B1.2`, `B1.3`, `B2.1`, `B2.2`, `B2.3`
- Tracks: `Arbeit`, `Studium`, `Ausbildung`

**DOMAINES** (only when niveau = Arbeit or Ausbildung): `Santé et soins infirmiers`, `Hôtellerie`, `Cuisine`, `Chauffeur poids lourd`, `Mécanique automobile`, `Mécanique`, `Électricien`, `Autre`.

**EXAMEN_TYPES** (only when niveau = Studium): `STK`, `DSH`.

---

## 7. `groups` (Training groups / classes)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(150) | NO | | |
| niveau | varchar(10) | NO | | One of Student's CEFR levels (`Group::NIVEAUX = Student::NIVEAUX_CEFR`) |
| enseignant_id | bigint FK→employees | YES | | Teacher |
| salle_id | bigint FK→salles | YES | | |
| etablissement_id | bigint FK→etablissements | YES | | |
| annee_scolaire_id | bigint FK→annees_scolaires | YES | | |
| capacite_max | integer | YES | | |
| statut | varchar(20) | NO | 'En inscription' | See STATUTS below |
| date_debut_formation | date | YES | | |
| date_fin_formation | date | YES | | |
| created_at / updated_at | timestamp | YES | | |

**STATUTS**: `En inscription`, `En formation`, `Fin de formation`, `Annulée`.

⚠ **Groups are never deleted.** Transition to `Fin de formation` only through the app's archive action, which snapshots into `groups_historique` in the same transaction. If importing already-finished groups from the old CRM, decide per-group whether it's still "live" (import into `groups` as-is) or purely historical (import into both `groups` + a matching `groups_historique` row, or just skip if history isn't needed).

---

## 8. `groups_historique` (Archived-group snapshots)

Written automatically when a group is archived — one snapshot row per archive event.

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| group_id | bigint FK→groups | NO | | |
| nom | varchar(150) | NO | | Snapshot of the group's name at archive time |
| niveau | varchar(10) | NO | | |
| enseignant_id | bigint FK→employees | YES | | |
| etablissement_id | bigint FK→etablissements | YES | | |
| annee_scolaire_id | bigint FK→annees_scolaires | YES | | |
| nombre_etudiants_final | integer | YES | | Final headcount |
| date_debut_formation | date | YES | | |
| date_fin_formation | date | YES | | |
| archived_at | timestamp | NO | | |
| archived_by | bigint FK→employees | YES | | |

(`Group::STATUTS_HISTORIQUE` also exists for filtering — check `app/Models/Group.php` if needed.)

---

## 9. `frais` (Fee catalog)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(150) | NO | | Fee name, e.g. "Frais d'inscription" |
| statut | varchar(20) | NO | 'Actif' | `Actif` / `Inactif` |
| created_at / updated_at | timestamp | YES | | |

---

## 10. `group_frais` (Fees assigned to a group — pivot with extra data)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| group_id | bigint FK→groups | NO | | |
| frais_id | bigint FK→frais | NO | | |
| montant | numeric(10,2) | NO | | Amount for **this** group (can differ from catalog default) |
| date_echeance | date | YES | | Due date for this group's instance of the fee |
| classification | varchar(10) | YES | | |
| created_at / updated_at | timestamp | YES | | |

---

## 11. `inscriptions` (Enrollments)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated `INS-###` |
| student_id | bigint FK→students | NO | | |
| group_id | bigint FK→groups | NO | | |
| etablissement_id | bigint FK→etablissements | YES | | Inherited from the group |
| annee_scolaire_id | bigint FK→annees_scolaires | YES | | Inherited from the group |
| statut | varchar(30) | NO | 'Active' | See STATUTS below |
| date_inscription | date | NO | | |
| date_debut | date | YES | | |
| date_fin | date | YES | | |
| montant_total | numeric(10,2) | YES | | Sum of its fee lines |
| note | text | YES | | |
| created_by | bigint FK→employees | YES | | |
| created_at / updated_at | timestamp | YES | | |

**STATUTS**: `Active`, `Annulée`, `Changement`, `Expirée`, `Archivée`.

---

## 12. `inscription_fees` (Fee lines on an inscription)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| inscription_id | bigint FK→inscriptions | NO | | |
| nom | varchar(150) | NO | | Fee label (copied at enrollment time) |
| montant_initial | numeric(10,2) | YES | | Before discount |
| remise_pct | numeric(5,2) | YES | | Discount % |
| remise_montant | numeric(10,2) | YES | | Discount fixed amount (percent takes priority if both present) |
| frais_id | bigint FK→frais | YES | | Source catalog fee (nullable — can be a one-off fee) |
| montant | numeric(10,2) | NO | | Final amount after discount |
| date_echeance | date | NO | | Due date |
| note | varchar(255) | YES | | |
| statut | varchar(20) | NO | 'Non payé' | See STATUTS below |
| masque_le | timestamp | YES | | Soft-hide timestamp (not a real delete) |
| created_at / updated_at | timestamp | YES | | |

**STATUTS**: `Non payé`, `Payé partiellement`, `Payé`.

---

## 13. `inscriptions_historique` (Inscription change/archive log)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| inscription_id | bigint FK→inscriptions | NO | | The inscription being closed/replaced |
| new_inscription_id | bigint FK→inscriptions | YES | | If it was replaced by a new inscription (e.g. group change) |
| student_id | bigint FK→students | NO | | |
| group_id | bigint FK→groups | NO | | |
| montant_paye | numeric(12,2) | NO | 0 | Amount paid at the time of archiving |
| date_fin | date | NO | | |
| note | text | YES | | |
| archived_at | timestamp | NO | | |
| archived_by | bigint FK→employees | YES | | |

---

## 14. `inscription_livres` (Books assigned to an inscription)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| inscription_id | bigint FK→inscriptions | NO | | |
| stock_article_id | bigint FK→stock_articles | NO | | The book (a stock article whose `stock_type` is the system "Livre" type) |
| assigned_by | bigint FK→employees | YES | | |
| created_at / updated_at | timestamp | YES | | |

---

## 15. `cheques` (Checks — deposit/guarantee tracking)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated |
| source | varchar(20) | NO | | `Étudiant` / `Parents` |
| student_id | bigint FK→students | YES | | |
| proprietaire_nom | varchar(150) | YES | | Owner name if not linked to a student record |
| numero_cheque | varchar(50) | NO | | Check number |
| montant | numeric(12,2) | NO | | |
| banque | varchar(100) | YES | | Free-text bank name on the check (see also the separate `banques` lookup table) |
| date_reception | date | NO | | |
| type | varchar(30) | NO | | `Garantie (À encaisser)` / `À déposer` |
| date_echeance | date | YES | | |
| statut | varchar(20) | NO | 'En possession' | See STATUTS below |
| retourne_le | timestamp | YES | | When a check bounced/was returned |
| retourne_par_id | bigint FK→employees | YES | | |
| note | text | YES | | |
| etablissement_id | bigint FK→etablissements | YES | | |
| agent_id | bigint FK→employees | NO | | Employee who registered the check |
| created_at / updated_at | timestamp | YES | | |

**SOURCES**: `Étudiant`, `Parents`.
**TYPES**: `Garantie (À encaisser)`, `À déposer`.
**STATUTS**: `En possession`, `Déposé`, `Encaissé`, `Rejeté`.

---

## 16. `banques` (Bank name lookup)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(150) | NO | | |
| statut | varchar(20) | NO | 'Actif' | `Actif` / `Inactif` |
| created_at / updated_at | timestamp | YES | | |

---

## 17. `caisses` (Cash registers / tills)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(100) | NO | | |
| etablissement_id | bigint FK→etablissements | YES | | |
| responsable_employee_id | bigint FK→employees | YES | | |
| solde | numeric(12,2) | YES | 0 | **Application-maintained running balance — not a ledger.** Every money movement adjusts this in the same DB transaction as the money-record insert. |
| statut | varchar(20) | NO | 'Active' | `Active` / `Inactive` |
| created_at / updated_at | timestamp | YES | | |

⚠ When migrating historical money records (encaissements/depenses/remboursements/transfers), you must recompute `solde` yourself by replaying all imported transactions in date order — don't just import a snapshot balance without matching transaction history, or future money actions will drift from reality.

---

## 18. `encaissements` (Payments received)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated `ENC-###` |
| student_id | bigint FK→students | NO | | |
| inscription_fee_id | bigint FK→inscription_fees | YES | | Which fee line this payment applies to |
| applied_from_encaissement_id | bigint FK→encaissements (self) | YES | | Links a re-applied/carried-over payment to its origin |
| cheque_id | bigint FK→cheques | YES | | If paid by check |
| montant | numeric(12,2) | NO | | |
| methode | varchar(30) | NO | | See METHODES below |
| date_paiement | date | NO | | |
| caisse_id | bigint FK→caisses | NO | | |
| agent_id | bigint FK→employees | NO | | |
| numero_cheque | varchar(50) | YES | | Denormalized check number (also on `cheques`) |
| banque | varchar(100) | YES | | |
| date_echeance_cheque | date | YES | | |
| note | text | YES | | |
| created_at / updated_at | timestamp | YES | | |

**METHODES**: `Espèces`, `TPE`, `Chèque`, `Virement`.

⚠ Never deleted; corrections are compensating entries, not edits to `montant`/`caisse_id`.

---

## 19. `types_depenses` (Expense categories)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(100) | NO | | |
| is_system | boolean | NO | false | Seeded/locked system types — see below |
| statut | varchar(20) | NO | 'Actif' | `Actif` / `Inactif` |
| created_at / updated_at | timestamp | YES | | |

System (`is_system = true`) types: `Paiement prof`, `Salaire`, `Transfert à une autre caisse`.

---

## 20. `depenses` (Expenses)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated `DEP-###` |
| type_depense_id | bigint FK→types_depenses | NO | | |
| caisse_id | bigint FK→caisses | NO | | |
| montant | numeric(12,2) | NO | | |
| methode_paiement | varchar(20) | YES | | |
| date_depense | date | NO | | |
| reference_facture | varchar(100) | YES | | Invoice reference |
| group_id | bigint FK→groups | YES | | Optional link (e.g. teacher payments) |
| description | varchar(255) | YES | | |
| mots_cles | varchar(255) | YES | | Keywords/tags |
| note | text | YES | | |
| agent_id | bigint FK→employees | NO | | |
| created_at / updated_at | timestamp | YES | | |

Also has a `justificatifs` media collection (receipt uploads) via `media` table.

---

## 21. `remboursements` (Refunds)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated `RMB-###` |
| beneficiaire_id | bigint FK→students | NO | | |
| encaissement_id | bigint FK→encaissements | YES | | Original payment being refunded |
| caisse_id | bigint FK→caisses | NO | | |
| montant | numeric(12,2) | NO | | |
| date_remboursement | date | NO | | |
| motif | varchar(255) | YES | | Free-text reason (see also `motifs_annulation` lookup) |
| note | text | YES | | |
| agent_id | bigint FK→employees | NO | | |
| created_at / updated_at | timestamp | YES | | |

---

## 22. `caisse_transfers` (Till-to-till transfers)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated `TRF-###` |
| caisse_source_id | bigint FK→caisses | NO | | |
| caisse_destination_id | bigint FK→caisses | NO | | |
| montant | numeric(12,2) | NO | | |
| date_transfert | timestamp | NO | | |
| solde_source_avant | numeric(12,2) | YES | | Balance snapshots for audit |
| solde_source_apres | numeric(12,2) | YES | | |
| solde_dest_avant | numeric(12,2) | YES | | |
| solde_dest_apres | numeric(12,2) | YES | | |
| statut | varchar(20) | NO | 'En attente' | See STATUTS below |
| note | text | YES | | |
| requested_by | bigint FK→employees | NO | | |
| validated_by | bigint FK→employees | YES | | Must be a **different** employee than `requested_by` |
| created_at / updated_at | timestamp | YES | | |

**STATUTS**: `En attente`, `Validé`, `Annulé`.

⚠ Two-step: on request, balances are untouched (`statut = En attente`); only on validation by a different employee do `caisses.solde` values actually move.

---

## 23. `motifs_annulation` (Cancellation-reason lookup)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(150) | NO | | |
| is_system | boolean | NO | false | |
| statut | varchar(20) | NO | 'Actif' | `Actif` / `Inactif` |
| created_at / updated_at | timestamp | YES | | |

System value includes at least: `Changement de groupe`.

---

## 24. `creneaux` (Weekly time slots for a group)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| group_id | bigint FK→groups | NO | | |
| jour_semaine | smallint | NO | | 1=Lundi … 7=Dimanche (see JOURS below) |
| heure_debut | time | NO | | |
| heure_fin | time | NO | | |
| enseignant_id | bigint FK→employees | YES | | |
| salle_id | bigint FK→salles | YES | | |
| created_at / updated_at | timestamp | YES | | |

**JOURS**: `1=Lundi, 2=Mardi, 3=Mercredi, 4=Jeudi, 5=Vendredi, 6=Samedi, 7=Dimanche`.

---

## 25. `seances` (Individual class sessions, generated from `creneaux`)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| group_id | bigint FK→groups | NO | | |
| creneau_id | bigint FK→creneaux | YES | | Originating slot (nullable — a session can be ad hoc) |
| date_seance | date | NO | | |
| heure_debut | time | YES | | |
| heure_fin | time | YES | | |
| enseignant_id | bigint FK→employees | YES | | |
| etablissement_id | bigint FK→etablissements | YES | | |
| annee_scolaire_id | bigint FK→annees_scolaires | YES | | |
| statut | varchar(20) | NO | 'Prévue' | See STATUTS below |
| note | text | YES | | |
| motif_annulation | text | YES | | |
| created_by | bigint FK→employees | YES | | |
| created_at / updated_at | timestamp | YES | | |

**STATUTS**: `Prévue`, `Effectuée`, `Annulée`.

---

## 26. `presences` (Attendance per session per student)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| seance_id | bigint FK→seances | NO | | |
| student_id | bigint FK→students | NO | | |
| statut | varchar(20) | NO | | See STATUTS below |
| note | varchar(255) | YES | | |
| created_at / updated_at | timestamp | YES | | |

**STATUTS**: `Présent`, `Absent`, `Retard`, `Justifié`.

---

## 27. `stock_types` (Stock category lookup)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| nom | varchar(100) | NO | | |
| is_system | boolean | NO | false | System value: `Livre` (Books) |
| statut | varchar(20) | NO | 'Actif' | `Actif` / `Inactif` |
| created_at / updated_at | timestamp | YES | | |

---

## 28. `stock_articles` (Stock items)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| reference | varchar(20) | NO | | System-generated |
| nom | varchar(150) | NO | | |
| stock_type_id | bigint FK→stock_types | NO | | |
| quantite | integer | NO | 0 | Current quantity on hand |
| seuil_alerte | integer | YES | | Low-stock alert threshold |
| etablissement_id | bigint FK→etablissements | YES | | |
| statut | varchar(20) | NO | 'Actif' | `Actif` / `Inactif` |
| note | text | YES | | |
| created_at / updated_at | timestamp | YES | | |

---

## 29. `stock_mouvements` (Stock movement log)

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint PK | | | |
| stock_article_id | bigint FK→stock_articles | NO | | |
| type | varchar(20) | NO | | See TYPES below |
| quantite | integer | NO | | Quantity moved (this movement) |
| quantite_avant | integer | NO | | Snapshot before |
| quantite_apres | integer | NO | | Snapshot after |
| note | varchar(255) | YES | | |
| created_by | bigint FK→employees | YES | | |
| created_at / updated_at | timestamp | YES | | |

**TYPES**: `Entrée`, `Sortie`, `Ajustement`.

---

## 30. `media` (spatie/medialibrary — file attachments)

Standard medialibrary table — attached to `students` (`photo`, `documents` collections) and `depenses` (`justificatifs` collection). Public URLs are served as `/media/<8-char-uuid>/<file>`. Not usually migrated as rows directly — old-CRM files should be re-uploaded through the app so URLs/uuids regenerate correctly, or imported by copying files into `storage/app/media` and inserting matching rows (advanced — coordinate carefully with the `ShortUuidPathGenerator` scheme).

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| model_type / model_id | varchar / bigint | Polymorphic owner (e.g. `App\Models\Student`) |
| uuid | uuid | Used to build the public URL |
| collection_name | varchar(255) | `photo`, `documents`, `justificatifs` |
| name / file_name | varchar(255) | |
| mime_type | varchar(255) | |
| disk | varchar(255) | `media` |
| size | bigint | bytes |
| manipulations / custom_properties / generated_conversions / responsive_images | jsonb | |
| order_column | integer | |

---

## 31. Permission tables (spatie/laravel-permission) — usually NOT migrated

`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` are recreated by `RolesAndPermissionsSeeder`, not imported from the old CRM. `roles` additionally has a French `label` column.

---

## 32. Framework/system tables — never migrated

`activity_log` (audit trail, regenerates going forward), `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `password_reset_tokens`.

---

## Suggested migration order (respects FK dependencies)

1. `etablissements`
2. `annees_scolaires`, `salles` (needs etablissements)
3. `employees` (needs etablissements; creates `users` rows)
4. `students` (needs etablissements)
5. `frais`, `types_depenses`, `motifs_annulation`, `stock_types`, `banques` (lookups, no deps)
6. `groups` (needs employees/salles/etablissements/annees_scolaires) → `group_frais`
7. `creneaux` (needs groups/employees/salles) → `seances` → `presences`
8. `caisses` (needs etablissements/employees)
9. `inscriptions` (needs students/groups) → `inscription_fees`
10. `stock_articles` (needs stock_types/etablissements) → `inscription_livres`
11. `cheques` (needs students/employees/etablissements)
12. `encaissements` (needs students/inscription_fees/cheques/caisses/employees) — **replay in date order to build `caisses.solde`**
13. `depenses`, `remboursements`, `caisse_transfers` (same balance-replay caveat)
14. `groups_historique`, `inscriptions_historique` (only if importing closed/archived history)
15. `media` (file re-upload, last)

For every VARCHAR "status/type/categorie/niveau" field, validate old-CRM values against the fixed lists in this document *before* import — mismatches will pass a raw DB insert but fail the app's own validation on next edit, and may break UI filters that compare against these exact constants.
