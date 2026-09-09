-- Toutes les caisses dont le solde STOCKÉ diverge de la somme de ses mouvements.
-- `caisses.solde` est l'autorité (CaisseLedger) : un écart = un mouvement passé
-- hors ledger, ou un solde d'ouverture jamais journalisé.
\echo '=== CAISSES DONT LE SOLDE NE COLLE PAS AUX MOUVEMENTS ==='
WITH m AS (
  SELECT c.id, c.nom, c.type, e.nom_centre AS centre, c.solde,
    (SELECT COALESCE(sum(montant),0) FROM encaissements
       WHERE caisse_id=c.id AND applied_from_encaissement_id IS NULL) AS enc,
    (SELECT COALESCE(sum(montant),0) FROM depenses
       WHERE caisse_id=c.id AND statut='Approuvée') AS dep,
    (SELECT COALESCE(sum(montant),0) FROM remboursements WHERE caisse_id=c.id) AS remb,
    (SELECT COALESCE(sum(montant),0) FROM caisse_transfers
       WHERE caisse_destination_id=c.id AND statut='Validé') AS trf_in,
    (SELECT COALESCE(sum(montant),0) FROM caisse_transfers
       WHERE caisse_source_id=c.id AND statut='Validé') AS trf_out
  FROM caisses c LEFT JOIN etablissements e ON e.id=c.etablissement_id
)
SELECT id, nom, type, centre, solde AS solde_stocke,
       enc, dep, remb, trf_in, trf_out,
       (enc - dep - remb + trf_in - trf_out) AS solde_calcule,
       (solde - (enc - dep - remb + trf_in - trf_out)) AS ecart
FROM m
WHERE abs(solde - (enc - dep - remb + trf_in - trf_out)) > 0.009
ORDER BY abs(solde - (enc - dep - remb + trf_in - trf_out)) DESC;

\echo ''
\echo '=== TOTAL RESEAU (doit rester coherent) ==='
SELECT count(*) AS nb_caisses, sum(solde) AS total_soldes_stockes FROM caisses;

\echo ''
\echo '=== TRANSFERTS VALIDES SANS ECRITURE DE LEDGER (les 2 jambes attendues) ==='
SELECT t.reference, t.montant, t.date_transfert, t.statut,
       (SELECT count(*) FROM activity_log a
         WHERE a.log_name='caisse' AND a.event='solde_movement'
           AND a.properties->>'source_type' LIKE '%CaisseTransfer%'
           AND a.properties->>'source_id' = t.id::text) AS jambes_journalisees
FROM caisse_transfers t
WHERE t.statut='Validé'
ORDER BY t.date_transfert DESC
LIMIT 30;
