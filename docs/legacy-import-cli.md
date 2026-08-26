# Import de l'ancien CRM en ligne de commande

`php artisan import:centre` fait tourner les **mêmes importers** que les écrans
Backoffice → Import, sans navigateur. Écrit pour le VPS (PuTTY), où passer sept
centres dans l'assistant web est long et facile à rater.

## Arborescence attendue

Exactement telle qu'elle est téléchargée :

```
<dossier>/old data/<centre>/liste-etudiants_*.xlsx
<dossier>/old data/<centre>/liste-inscriptions_*.xlsx       (Annulé)
<dossier>/old data/<centre>/liste-inscriptions_* (1).xlsx   (Archive)
<dossier>/old data/<centre>/liste-paiements_*.xlsx
<dossier>/active data/<centre>/liste-inscriptions_*.xlsx    (Active)
```

Sous-dossiers reconnus : `marrakech`, `rabat`, `casa`, `kenitra`, `agadir`,
`sale`, `online`.

⚠ `liste-etudiants` et `liste-paiements` sont **identiques** dans les deux
dossiers — importés une seule fois. Le statut de chaque fichier
d'inscriptions est lu dans sa **colonne « Statut »**, jamais d'après son nom :
le suffixe « (1) » dépend du navigateur et, sur l'export réel, « (1) » est
*Archive* et non *Annulé*.

## Utilisation

```bash
cd /var/www/crm-gls

# 1. Toujours commencer par un essai à blanc — rien n'est écrit
php artisan import:centre --centre=Marrakech --dossier="/var/www/crm-gls/data" --dry-run

# 2. L'import réel
php artisan import:centre --centre=Marrakech --dossier="/var/www/crm-gls/data"

# 3. Les sept centres d'affilée
php artisan import:centre --tous --dossier="/var/www/crm-gls/data"
```

Options : `--annee-courante=` / `--annee-precedente=` (par défaut les deux
années les plus récentes), `--sans-paiements`, `--dry-run`.

## Ordre d'import (ne pas changer)

1. **étudiants**
2. **inscriptions Active** → année courante — *crée* les groupes encore en cours
3. **inscriptions Annulé / Archive** → année précédente — *réutilisent* ces groupes
4. **paiements** — un seul fichier, à cheval sur les deux années

Un groupe qui contient encore un étudiant actif est **en cours** : il est créé
dans l'année courante et les deux fichiers anciens s'y rattachent, au lieu de
couper la cohorte en deux. Voir `ReaffecterGroupeVersAnnee`.

## Relancer sans risque

Les index uniques `(etablissement_id, legacy_ref)` font que toute ligne déjà
importée revient en **DOUBLON** et n'est jamais réécrite : relancer la commande
ne peut qu'ajouter ce qui manquait. Sauvegarder tout de même avant :

```bash
sudo -u postgres pg_dump gls_crm | gzip > /root/gls_crm_$(date +%F_%H%M).sql.gz
```

## Résultat attendu (Marrakech, données du 24/08/2026)

| | |
|---|---|
| étudiants | 881 (4 doublons dans le fichier) |
| inscriptions | 1 103 — 169 Active + 40 Changement + 159 Annulée en 2026/2027 ; 254 Changement + 481 Annulée en 2025/2026 |
| groupes | 26, aucun éclaté sur deux années |
| encaissements | 3 949 pour **3 722 800,00 DH** |
| échecs | 4 (doublons d'étudiants dans le fichier source) |

Les lignes non résolues restent consultables dans **Import → Voir → « Lignes
non résolues »**.
