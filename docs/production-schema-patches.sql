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

COMMIT;
