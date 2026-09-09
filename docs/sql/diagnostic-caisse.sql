\echo '=== 1. LA CAISSE ==='
SELECT c.id, c.nom, c.type, c.etablissement_id, e.nom_centre AS centre_rattachement, c.solde AS solde_stocke
FROM caisses c LEFT JOIN etablissements e ON e.id = c.etablissement_id
WHERE c.nom ILIKE :'nom';

\echo ''
\echo '=== 2. ENCAISSEMENTS PAR CENTRE (hors lignes d application) ==='
SELECT e.nom_centre, count(*) AS nb, sum(enc.montant) AS total
FROM encaissements enc
JOIN caisses c ON c.id = enc.caisse_id
LEFT JOIN etablissements e ON e.id = enc.etablissement_id
WHERE c.nom ILIKE :'nom' AND enc.applied_from_encaissement_id IS NULL
GROUP BY e.nom_centre ORDER BY total DESC;

\echo ''
\echo '=== 3. DEPENSES (centre = groupe sinon centre de la caisse) ==='
SELECT d.reference, d.statut, d.montant, d.date_depense,
       COALESCE(eg.nom_centre, ec.nom_centre) AS centre_impute
FROM depenses d
JOIN caisses c ON c.id = d.caisse_id
LEFT JOIN groups g ON g.id = d.group_id
LEFT JOIN etablissements eg ON eg.id = g.etablissement_id
LEFT JOIN etablissements ec ON ec.id = c.etablissement_id
WHERE c.nom ILIKE :'nom' ORDER BY d.date_depense;

\echo ''
\echo '=== 4. TRANSFERTS (imputes au CENTRE DE RATTACHEMENT de la caisse) ==='
SELECT t.reference, t.date_transfert, t.montant, t.statut,
       cs.nom AS source, cd.nom AS destination,
       CASE WHEN t.caisse_source_id = c.id THEN 'SORTANT (-)' ELSE 'ENTRANT (+)' END AS sens
FROM caisse_transfers t
JOIN caisses c ON c.nom ILIKE :'nom'
LEFT JOIN caisses cs ON cs.id = t.caisse_source_id
LEFT JOIN caisses cd ON cd.id = t.caisse_destination_id
WHERE t.caisse_source_id = c.id OR t.caisse_destination_id = c.id
ORDER BY t.date_transfert;

\echo ''
\echo '=== 5. REMBOURSEMENTS ==='
SELECT r.reference, r.montant, r.date_remboursement, e.nom_centre
FROM remboursements r JOIN caisses c ON c.id = r.caisse_id
LEFT JOIN etablissements e ON e.id = r.etablissement_id
WHERE c.nom ILIKE :'nom';

\echo ''
\echo '=== 6. VERDICT : somme des mouvements vs solde stocke ==='
WITH cc AS (SELECT id, etablissement_id, solde FROM caisses WHERE nom ILIKE :'nom')
SELECT cc.solde AS solde_stocke,
  (SELECT COALESCE(sum(montant),0) FROM encaissements WHERE caisse_id=cc.id AND applied_from_encaissement_id IS NULL) AS enc,
  (SELECT COALESCE(sum(montant),0) FROM depenses WHERE caisse_id=cc.id AND statut='Approuvée') AS dep_approuvees,
  (SELECT COALESCE(sum(montant),0) FROM remboursements WHERE caisse_id=cc.id) AS remb,
  (SELECT COALESCE(sum(montant),0) FROM caisse_transfers WHERE caisse_destination_id=cc.id AND statut='Validé') AS trf_entrants,
  (SELECT COALESCE(sum(montant),0) FROM caisse_transfers WHERE caisse_source_id=cc.id AND statut='Validé') AS trf_sortants,
  (SELECT COALESCE(sum(montant),0) FROM encaissements WHERE caisse_id=cc.id AND applied_from_encaissement_id IS NULL)
  - (SELECT COALESCE(sum(montant),0) FROM depenses WHERE caisse_id=cc.id AND statut='Approuvée')
  - (SELECT COALESCE(sum(montant),0) FROM remboursements WHERE caisse_id=cc.id)
  + (SELECT COALESCE(sum(montant),0) FROM caisse_transfers WHERE caisse_destination_id=cc.id AND statut='Validé')
  - (SELECT COALESCE(sum(montant),0) FROM caisse_transfers WHERE caisse_source_id=cc.id AND statut='Validé') AS solde_calcule
FROM cc;
