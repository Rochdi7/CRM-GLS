-- Empreinte comparable local <-> production.
-- Usage : sudo -u postgres psql -d gls_crm -P pager=off -f scripts/scan-data.sql
\pset footer off

\echo '=== 1. VOLUMES GLOBAUX ==='
SELECT 'etudiants' AS table, count(*) AS n FROM students
UNION ALL SELECT 'inscriptions', count(*) FROM inscriptions
UNION ALL SELECT 'groupes', count(*) FROM groups
UNION ALL SELECT 'lignes_frais', count(*) FROM inscription_fees
UNION ALL SELECT 'encaissements', count(*) FROM encaissements
UNION ALL SELECT 'avances', count(*) FROM encaissements WHERE inscription_fee_id IS NULL
UNION ALL SELECT 'cheques', count(*) FROM cheques
UNION ALL SELECT 'import_rows', count(*) FROM import_rows
ORDER BY 1;

\echo ''
\echo '=== 2. ARGENT ==='
SELECT round(sum(montant), 2) AS total_encaisse,
       round(sum(montant) FILTER (WHERE inscription_fee_id IS NULL), 2) AS dont_avances,
       count(*) AS nb
FROM encaissements;

SELECT round(sum(solde), 2) AS solde_caisses FROM caisses;

\echo ''
\echo '=== 3. PAR CENTRE ==='
SELECT e.id, e.nom_centre,
       (SELECT count(*) FROM students s WHERE s.etablissement_id = e.id) AS etudiants,
       (SELECT count(*) FROM inscriptions i WHERE i.etablissement_id = e.id) AS inscriptions,
       (SELECT count(*) FROM encaissements en WHERE en.etablissement_id = e.id) AS paiements,
       (SELECT round(coalesce(sum(en.montant),0),2) FROM encaissements en WHERE en.etablissement_id = e.id) AS montant
FROM etablissements e ORDER BY e.id;

\echo ''
\echo '=== 4. INSCRIPTIONS PAR ANNEE / STATUT ==='
SELECT a.nom AS annee, i.statut, count(*)
FROM inscriptions i JOIN annees_scolaires a ON a.id = i.annee_scolaire_id
GROUP BY 1, 2 ORDER BY 1, 2;

\echo ''
\echo '=== 5. LIGNES NON IMPORTEES ==='
SELECT r.status, count(*), round(coalesce(sum((r.raw->>'montant')::numeric),0),2) AS montant
FROM import_rows r JOIN import_batches b ON b.id = r.import_batch_id
WHERE b.module = 'encaissements' AND r.status NOT IN ('INSERE','DOUBLON')
GROUP BY 1 ORDER BY 2 DESC;

\echo ''
\echo '=== 6. CONTROLES D INTEGRITE (tout doit valoir 0) ==='
SELECT 'groupes_eclates_sur_2_annees' AS controle,
       (SELECT count(*) FROM (SELECT g.nom FROM inscriptions i JOIN groups g ON g.id = i.group_id
                              GROUP BY g.nom HAVING count(DISTINCT i.annee_scolaire_id) > 1) x) AS n
UNION ALL
SELECT 'paiement_sur_autre_etudiant',
       (SELECT count(*) FROM encaissements e JOIN inscription_fees f ON f.id = e.inscription_fee_id
        JOIN inscriptions i ON i.id = f.inscription_id WHERE i.student_id <> e.student_id)
UNION ALL
SELECT 'paiement_sur_autre_centre',
       (SELECT count(*) FROM encaissements e JOIN inscription_fees f ON f.id = e.inscription_fee_id
        JOIN inscriptions i ON i.id = f.inscription_id WHERE i.etablissement_id <> e.etablissement_id)
UNION ALL
SELECT 'legacy_ref_duplique_dans_un_centre',
       (SELECT count(*) FROM (SELECT etablissement_id, legacy_ref FROM encaissements
                              WHERE legacy_ref IS NOT NULL
                              GROUP BY 1,2 HAVING count(*) > 1) x)
UNION ALL
SELECT 'etudiants_dupliques_nom_et_tel',
       (SELECT count(*) FROM (SELECT etablissement_id, upper(prenom||' '||nom), telephone
                              FROM students GROUP BY 1,2,3 HAVING count(*) > 1) x)
ORDER BY 1;

\echo ''
\echo '=== 7. ECART SOLDE CAISSES vs ENCAISSE (doit valoir 0.00) ==='
SELECT round((SELECT coalesce(sum(solde),0) FROM caisses)
           - (SELECT coalesce(sum(montant),0) FROM encaissements), 2) AS ecart;
