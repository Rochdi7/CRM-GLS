-- Production schema patches — GLS CRM
--
-- Rule (CLAUDE.md §11/§17): every schema change is written INTO the create_*
-- migration of its table, so a fresh install / the test DB gets the final
-- schema from the create files alone. Production already ran those create
-- files, so each change is ALSO listed here as an idempotent SQL block to
-- apply once by hand on the VPS:
--
--     sudo -u postgres psql -v ON_ERROR_STOP=1 gls_crm -f docs/production-schema-patches.sql
--
-- Every block is safe to re-run (IF NOT EXISTS / guarded), so the whole file
-- can always be replayed. Append new blocks at the bottom, dated.

BEGIN;

-- ---------------------------------------------------------------------------
-- 24/08/2026 — caisses integrity (was migration harden_caisses_integrity)
-- ---------------------------------------------------------------------------
UPDATE caisses SET solde = 0 WHERE solde IS NULL;
ALTER TABLE caisses ALTER COLUMN solde SET NOT NULL;
ALTER TABLE caisses ALTER COLUMN solde SET DEFAULT 0;

-- Refuses to continue if an employee already holds two Caissière tills:
-- merging two money accounts is a business decision, not a script's.
DO $$
DECLARE dup text;
BEGIN
    SELECT string_agg(format('employé #%s (%s caisses)', responsable_employee_id, n), ', ')
      INTO dup
      FROM (SELECT responsable_employee_id, count(*) AS n FROM caisses
             WHERE type = 'Caissière' AND responsable_employee_id IS NOT NULL
             GROUP BY responsable_employee_id HAVING count(*) > 1) d;
    IF dup IS NOT NULL THEN
        RAISE EXCEPTION 'Plusieurs caisses Caissière pour %. Fusionnez-les à la main puis relancez.', dup;
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS caisses_une_caissiere_par_employe
    ON caisses (responsable_employee_id)
    WHERE type = 'Caissière' AND responsable_employee_id IS NOT NULL;

-- ---------------------------------------------------------------------------
-- 25/08/2026 — encaissements.legacy_ref unique PER CENTRE
-- (every centre's old CRM numbers payments from P1; the global unique made
-- the Rabat import skip 4 297 rows as « déjà importé »)
-- ---------------------------------------------------------------------------
ALTER TABLE encaissements
    ADD COLUMN IF NOT EXISTS etablissement_id bigint
        REFERENCES etablissements (id) ON DELETE SET NULL;

UPDATE encaissements e
   SET etablissement_id = s.etablissement_id
  FROM students s
 WHERE s.id = e.student_id
   AND e.etablissement_id IS NULL;

-- Constraint FIRST: Postgres refuses DROP INDEX on an index backing a
-- UNIQUE constraint, and inside this transaction that error aborts the
-- whole file. Dropping the constraint removes its index with it; the
-- DROP INDEX below only covers a plain (non-constraint) unique index.
ALTER TABLE encaissements DROP CONSTRAINT IF EXISTS encaissements_legacy_ref_unique;
DROP INDEX IF EXISTS encaissements_legacy_ref_unique;

CREATE UNIQUE INDEX IF NOT EXISTS encaissements_etab_legacy_ref_unique
    ON encaissements (etablissement_id, legacy_ref);

-- ---------------------------------------------------------------------------
-- 26/08/2026 — depenses.periode_debut / periode_fin
-- The teaching PERIOD a « Paiement prof » covers, as opposed to date_depense
-- (the day the money left the till). Nullable: an ordinary dépense has none.
-- ---------------------------------------------------------------------------
ALTER TABLE depenses ADD COLUMN IF NOT EXISTS periode_debut date;
ALTER TABLE depenses ADD COLUMN IF NOT EXISTS periode_fin date;

-- ---------------------------------------------------------------------------
-- 27/08/2026 — inscriptions.motif_annulation
-- Why an enrollment was cancelled. Free text validated against the ACTIVE
-- MotifAnnulation names at write time (no FK), exactly like
-- seances.motif_annulation, so deactivating or renaming a reason later never
-- rewrites what a past cancellation said. NULL on rows never cancelled.
-- ---------------------------------------------------------------------------
ALTER TABLE inscriptions ADD COLUMN IF NOT EXISTS motif_annulation varchar(120);

COMMIT;

-- ---------------------------------------------------------------------------
-- 27/08/2026 — DATA repair (no DDL): monthly fee due dates with the wrong year
--
-- FraisEcheanceResolver used to take the YEAR from now(), so all twelve
-- monthly fees of a group landed in the same calendar year. A school year
-- straddles two: a 2025/2026 group owes Septembre–Décembre in 2025 and
-- Janvier–Août in 2026. With one year for all of them, « Frais de Septembre »
-- sorted nine months AFTER « Frais de Janvier », and every screen ordering
-- fees by échéance — the group fee table, the inscription form,
-- « Statistique de groupe » — opened the school year on Janvier instead of on
-- the group's first month.
--
-- The resolver now anchors on the group's date_debut_formation, falling back
-- to its académic year's start. The stored dates are re-derived by an
-- artisan command, NOT by SQL — it only rewrites rows whose day+month still
-- match the old default (a due date edited by hand is left alone) and is
-- idempotent:
--
--   php artisan frais:repair-echeance-annee            # dry-run, read it first
--   php artisan frais:repair-echeance-annee --apply
--
-- Touches group_frais.date_echeance and inscription_fees.date_echeance.
-- Local run 27/08/2026: 1 016 group fees, 31 948 inscription lines.
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- 31/08/2026 — motifs_annulation.portee : séparer les motifs de séance des
-- motifs d'inscription.
--
-- Un même catalogue servait les deux formulaires : les raisons ajoutées pour
-- les séances (« Malade », « jour férié », « Match maroc », « Fin de
-- formation ») apparaissaient aussi à l'annulation d'une INSCRIPTION, et
-- inversement (« Non-paiement » proposé pour annuler un cours). La colonne
-- décide quel formulaire propose quelle raison ; 'tous' reste sur les deux.
--
-- Idempotent : la colonne est ajoutée si absente, puis chaque motif connu est
-- classé. Les motifs saisis à la main NON listés ici gardent 'tous' — donc
-- rien ne disparaît d'un formulaire tant qu'un admin ne les a pas classés
-- dans Paramètres → Raisons d'annulation (nouvelle colonne « Portée »).
ALTER TABLE motifs_annulation
    ADD COLUMN IF NOT EXISTS portee varchar(20) NOT NULL DEFAULT 'tous';

UPDATE motifs_annulation SET portee = 'seance'
 WHERE portee = 'tous'
   AND lower(nom) IN (
        'malade', 'empêchement personnel', 'empechement personnel',
        'congé', 'congee', 'congée', 'jour férié', 'jour ferie',
        'match maroc', 'fin de formation'
   );

UPDATE motifs_annulation SET portee = 'inscription'
 WHERE portee = 'tous'
   AND lower(nom) IN (
        'conflit d''horaires', 'inactivité prolongée', 'non-paiement',
        'transfert d''établissement', 'problème du temps',
        'changement de groupe'
   );
-- « Autre » reste délibérément 'tous' : c'est la raison générique des deux
-- formulaires.
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- 31/08/2026 — Type de dépense manquant : « Produits consommables »
-- Ajouté au catalogue de départ de TypeDepenseSeeder (is_system = false).
-- Équivalent SQL idempotent si le seeder n'est pas relancé en production.
INSERT INTO types_depenses (nom, is_system, statut, created_at, updated_at)
SELECT 'Produits consommables', false, 'Actif', now(), now()
 WHERE NOT EXISTS (SELECT 1 FROM types_depenses WHERE nom = 'Produits consommables');
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- 31/08/2026 — Permission « groups.delete » (suppression définitive d'un
-- groupe, super-admin uniquement). Aucune modification de schéma : la
-- permission est créée par le seeder idempotent, à lancer en production :
--     php artisan db:seed --class=RolesAndPermissionsSeeder --force
-- Elle est dans PermissionRegistry::superAdminOnly(), donc matrix() la retire
-- de tous les presets : aucun rôle ne la porte, seul Gate::before (super-admin)
-- l'accorde. Rien à exécuter en SQL ici.
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- 02/09/2026 — Verrou « Année clôturée » (annees_scolaires.cloturee)
-- Ajouté au create_annees_scolaires_table ; à appliquer tel quel sur toute
-- base déjà migrée. Une année cochée « clôturée » n'accepte plus AUCUNE
-- écriture (création ou modification) — super-admin compris ; le verrou se
-- lève uniquement en décochant la case dans Paramètres → Années scolaires.
-- Motif : une dépense a été saisie par erreur sur une année passée restée
-- sélectionnée dans le sélecteur du haut.
ALTER TABLE annees_scolaires ADD COLUMN IF NOT EXISTS cloturee boolean NOT NULL DEFAULT false;
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- 03/09/2026 — Centre propre au remboursement (remboursements.etablissement_id)
-- Ajouté au create_remboursements_table ; à appliquer tel quel sur toute base
-- déjà migrée.
--
-- Motif : la liste des remboursements déduisait le centre de la CAISSE
-- débitée. Or la caisse d'une caissière est rattachée à son centre primaire,
-- qui n'est pas forcément celui de l'étudiant remboursé. Un étudiant du
-- centre 4 remboursé depuis une caisse du centre 1 n'apparaissait donc sur
-- AUCUN des deux centres : la caissière, ne voyant rien enregistré, a saisi
-- le même remboursement de 300 DH une seconde fois (RMB-001 et RMB-002).
--
-- Le centre stocké suit la règle du ledger (CLAUDE.md §11 « Centre
-- dimension ») : celui du paiement d'origine, sinon celui de l'étudiant.
ALTER TABLE remboursements ADD COLUMN IF NOT EXISTS etablissement_id bigint NULL
    REFERENCES etablissements(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS remboursements_centre_date_idx
    ON remboursements (etablissement_id, date_remboursement);

-- Rattrapage des lignes existantes : centre du paiement d'origine, sinon
-- centre de l'étudiant bénéficiaire. Sans cela les remboursements déjà
-- enregistrés restent invisibles (etablissement_id NULL est traité comme
-- « global » par le scoping, donc ils s'afficheraient partout).
UPDATE remboursements r
SET etablissement_id = COALESCE(
        (SELECT e.etablissement_id FROM encaissements e WHERE e.id = r.encaissement_id),
        (SELECT s.etablissement_id FROM students s WHERE s.id = r.beneficiaire_id)
    )
WHERE r.etablissement_id IS NULL;
-- ---------------------------------------------------------------------------
