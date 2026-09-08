# Réparations de données EN ATTENTE — audit du 07/09/2026

> **AUCUNE de ces réparations n'a été exécutée.** Ce document ne contient que
> du **diagnostic en lecture seule**, la stratégie proposée, les risques et les
> lignes exactement concernées. Chaque point exige une **décision métier** et,
> pour les points financiers, une **validation de la direction financière**.
>
> Les requêtes ci-dessous sont **toutes des `SELECT`**. Ne jamais les
> transformer en `UPDATE`/`DELETE` sans autorisation écrite, et toujours avec
> un `pg_dump` préalable.

---

## C-7 — 4 707 829,00 MAD de paiements non-espèces logés dans une caisse physique

### Diagnostic (vérifié)

Le vérificateur du projet remonte **5 453 anomalies** :

| Méthode | Lignes | Montant |
|---|---:|---:|
| TPE | 3 218 | 3 297 109,00 MAD |
| Virement | 1 362 | 1 188 120,00 MAD |
| Chèque | 188 | 222 600,00 MAD |
| **Total** | **4 768** | **4 707 829,00 MAD** |

Ces montants sont dans la caisse **physique n°1** (solde 22 135 401,00 MAD)
au lieu du compte de méthode de leur centre (règle §11 : un encaissement
TPE/Chèque/Virement crédite le compte de méthode du centre, jamais le tiroir).

### Requête de contrôle (lecture seule)

```sql
SELECT e.methode, count(*) AS lignes, sum(e.montant) AS montant
FROM encaissements e
JOIN caisses c ON c.id = e.caisse_id
WHERE c.type IN ('Caissière', 'Externe')
  AND e.methode IN ('TPE', 'Chèque', 'Virement')
  AND e.applied_from_encaissement_id IS NULL
GROUP BY 1 ORDER BY 3 DESC;
```

### Pourquoi ce n'est PAS exécuté

`php artisan caisse:recalculer-soldes` **refuse déjà de tourner** :

> « 4155 ambiguous row(s) (student centre ≠ till centre) — nothing applied.
> Re-run with --ambiguous=caisse or --ambiguous=student. »

**4 155 des 4 768 lignes sont ambiguës** : le centre de l'étudiant diffère du
centre du tiroir. Trancher entre `--ambiguous=caisse` et `--ambiguous=student`
revient à décider, pour 4 155 paiements réels, **dans quel centre l'argent a
réellement été encaissé**. C'est une décision comptable, pas technique.

### Stratégie proposée (à valider)

1. `pg_dump` complet.
2. `php artisan caisse:verifier-coherence` — archiver la sortie AVANT.
3. Lire **intégralement** le dry-run de `caisse:recalculer-soldes`.
4. Faire trancher la direction financière sur le sens `--ambiguous`, idéalement
   sur un échantillon de 20 lignes recoupé avec les justificatifs papier.
5. Appliquer avec `--apply`, puis `caisse:verifier-coherence` APRÈS.

### Risques

- Les deux legs sont journalisés via `CaisseLedger`, donc traçables — mais
  **irréversibles sans restauration de sauvegarde**.
- Un mauvais sens `--ambiguous` déplace de l'argent vers le mauvais centre et
  fausse durablement la ventilation par centre.

---

## H-4 — 7 700,00 MAD d'argent fantôme dans la caisse n°1

### Diagnostic (cause tracée)

7 encaissements de 1 100 MAD (**ENC-26953 → ENC-26959**) ont été **crédités
puis supprimés physiquement** de `encaissements` **sans débit compensatoire**.

Journal d'audit : créés le **30/08/2026 à 18:07:24**, supprimés le
**30/08/2026 à 18:11:08**, **sans causer** (donc console/tinker, pas
l'interface).

**Le code applicatif est hors de cause** : `SupprimerEncaissement.php:54-68`
débite bien la caisse via `CaisseLedger` avant suppression, et aucun chemin de
code actuel ne supprime d'encaissements en masse (vérifié). Il s'agit d'un
**nettoyage manuel ponctuel**, pas d'un bug reproductible.

### Requête de contrôle (lecture seule)

```sql
-- Entrées du journal pointant vers un encaissement qui n'existe plus
SELECT al.properties->>'origine_reference' AS reference,
       al.properties->>'montant'          AS montant,
       al.properties->>'sens'             AS sens,
       al.created_at
FROM activity_log al
LEFT JOIN encaissements e
       ON e.id = (al.properties->>'origine_id')::bigint
WHERE al.log_name = 'caisse'
  AND al.event    = 'solde_movement'
  AND al.subject_id = 1
  AND al.properties->>'origine_type' = 'App\Models\Encaissement'
  AND e.id IS NULL
ORDER BY al.created_at;
```

### Stratégie proposée (à valider)

Une **écriture d'ajustement de −7 700,00 MAD** passée **exclusivement par
`CaisseLedger::debit()`**, avec un motif explicite référençant cet audit.

### Interdits

- ❌ Jamais un `UPDATE caisses SET solde = …` : invisible au journal, c'est
  précisément la faille que `CaisseLedger` a fermée.
- ❌ Ne pas recréer les 7 encaissements supprimés : les enregistrements
  monétaires sont *append-only*, les ressusciter fausserait le chiffre
  d'affaires.

### Risques

Faible techniquement, **mais l'écart doit d'abord être expliqué** : ces 7 700
MAD ont-ils été encaissés puis annulés à juste titre ? La réponse détermine si
l'ajustement est une correction ou un camouflage.

---

## D-1 — 4 dates de paiement aberrantes

### Diagnostic

| id | référence | date stockée | montant | lecture probable |
|---:|---|---|---:|---|
| 3950 | ENC-3950 | **0205-09-25** | 300,00 | 2025-09-25 |
| 22410 | ENC-22410 | **0020-03-27** | 200,00 | 2020-03-27 |
| 22411 | ENC-22411 | **0026-12-10** | 1 000,00 | 2026-12-10 |
| 22412 | ENC-22412 | **0225-09-10** | 1 000,00 | 2025-09-10 |

Fautes de frappe de l'import legacy. Les **montants sont justes** (2 500 MAD au
total) ; seule la date est fausse. Elles faussent tout `min(date)`, tout axe
temporel et toute fenêtre par défaut.

**Également :** 13 `students.date_naissance` et 2 `inscriptions.date_inscription`
hors de la plage [1900-2035].

### Requête de contrôle (lecture seule)

```sql
SELECT id, reference, date_paiement, montant, inscription_fee_id, etablissement_id
FROM encaissements
WHERE date_paiement < '2015-01-01' OR date_paiement > '2030-01-01'
ORDER BY id;
```

### Pourquoi ce n'est PAS exécuté

`date_paiement` **déplace un paiement dans le journal de caisse et dans le
récapitulatif annuel**, potentiellement vers un mois déjà rapproché — c'est
exactement pourquoi `payments.update-date` est réservé au super-admin (§16).
La date correcte doit être **lue sur le justificatif d'origine**, pas déduite
d'un motif de frappe.

### Stratégie proposée

Correction **une par une via l'interface**, par un super-admin, après
vérification du reçu papier — et non par script.

---

## C-3 (volet données) — Affectations `employee_etablissement` historiques

Le correctif de code est appliqué (refus au lieu de substitution). Mais des
lignes **déjà écrites** par l'ancien chemin peuvent être fausses.

### Requête de contrôle (lecture seule)

```sql
-- Les modifications d'employés tracées au journal : à recouper à la main
SELECT a.created_at, a.causer_label, a.subject_id,
       a.properties->'old'->>'etablissement_id' AS centre_avant,
       a.properties->'attributes'->>'etablissement_id' AS centre_apres
FROM activity_log a
WHERE a.log_name = 'employee'
  AND a.event = 'updated'
  AND a.properties->'attributes' ? 'etablissement_id'
ORDER BY a.created_at DESC;
```

### Interdit

❌ **Ne jamais re-dériver** les affectations par script : rien en base ne
distingue une affectation légitime d'une affectation écrasée. Seul le journal
d'audit, relu par un humain qui connaît l'organisation, peut trancher.

---

## H-11 (volet données) — Stock sous-évalué par des suppressions de groupes

`SupprimerGroupe` supprimait en cascade les `inscription_livres` **sans
mouvement « Entrée »** de compensation : les livres attribués à des groupes
déjà supprimés n'ont jamais été réintégrés au stock.

### Requête de réconciliation (lecture seule)

```sql
-- Stock théorique (somme des mouvements) vs quantité affichée
SELECT sa.id, sa.designation, sa.etablissement_id, sa.quantite AS quantite_affichee,
       COALESCE(SUM(CASE WHEN sm.type = 'Entrée' THEN sm.quantite
                         WHEN sm.type = 'Sortie' THEN -sm.quantite
                         ELSE 0 END), 0) AS quantite_theorique
FROM stock_articles sa
LEFT JOIN stock_mouvements sm ON sm.stock_article_id = sa.id
GROUP BY sa.id, sa.designation, sa.etablissement_id, sa.quantite
HAVING sa.quantite <> COALESCE(SUM(CASE WHEN sm.type = 'Entrée' THEN sm.quantite
                                        WHEN sm.type = 'Sortie' THEN -sm.quantite
                                        ELSE 0 END), 0);
```

### Interdit

❌ Ne pas reconstruire le stock automatiquement : l'écart peut aussi venir
d'un inventaire physique, d'une perte ou d'un don. La correction passe par un
**mouvement d'inventaire saisi dans l'application**, avec un motif — ce qui
laisse une trace, contrairement à un `UPDATE`.

---

## Récapitulatif

| Point | Nature | Argent concerné | Décideur |
|---|---|---:|---|
| C-7 | Ré-affectation de comptes | 4 707 829 MAD | Direction financière |
| H-4 | Écriture d'ajustement | 7 700 MAD | Direction financière |
| D-1 | Correction de dates | 2 500 MAD | Super-admin + justificatifs |
| C-3 | Affectations de centres | — | RH / direction |
| H-11 | Réconciliation de stock | — | Responsable marketing (stock) |
