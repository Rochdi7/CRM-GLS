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

---

# Réconcilier après coup — `paiements:reconcilier`

L'import fait de son mieux ligne par ligne ; il lui arrive de se tromper de
**frais**, ou de ne pas savoir rattacher un paiement du tout. Cette commande
relit l'export et remet chaque paiement sur le frais que **son fichier
source** nomme.

```bash
# 1. simulation (par défaut) — ne touche à rien
php artisan paiements:reconcilier --centre=Marrakech --dossier="/var/www/crm-gls/data"

# 2. un seul étudiant (réf. legacy, réf. CRM, ou id)
php artisan paiements:reconcilier --etudiant=E812 --dossier="/var/www/crm-gls/data"

# 3. écrire, après avoir LU la simulation
sudo -u postgres pg_dump gls_crm | gzip > /root/gls_crm_$(date +%F_%H%M).sql.gz
php artisan paiements:reconcilier --centre=Marrakech --dossier="..." --apply
```

## Ce qu'elle corrige

| Écart | Ce qu'elle fait |
|---|---|
| Le fichier nomme « Frais d'Octobre », la base montre « Inscription B2 » | détache et ré-applique sur Octobre — crée la ligne si le groupe la prévoit mais qu'elle manque |
| Le fichier ne nomme **aucun** frais (cellule « - ») | détache : l'argent redevient une **avance** |
| La ligne du fichier n'existe pas en base | **signalée**, jamais inventée — c'est `import:centre` qui importe |

## Ce qu'elle ne touche JAMAIS

`montant`, `date_paiement`, `methode`, `caisse_id`, `caisses.solde`. Un
paiement garde la date et la caisse avec lesquelles il a été enregistré — pour
les lignes legacy, la caisse de M. Rafik, exactement comme l'import les a
classées. Réaffecter de l'argent déjà dans la caisse n'est pas un mouvement de
caisse : le solde ne bouge pas, par construction. Aucun enregistrement
monétaire n'est supprimé (§11) : un mauvais rattachement est **détaché** puis
ré-appliqué.

## Année clôturée

Une année clôturée est **refusée**, jamais rouverte en douce :

```
P2868 : année 2025/2026 CLÔTURÉE — rouvrir dans Paramètres → Années
        scolaires, relancer, puis reclôturer.
```

Rouvrir est un geste explicite et audité, fait depuis l'interface — pas une
décision de traitement par lot. **Penser à reclôturer** juste après.

## Depuis l'interface (compte de maintenance uniquement)

L'écran **`/backoffice/reconciliation-paiements`** fait la même chose sans
PuTTY : deux boutons, « Simuler » (n'écrit jamais rien) et « Appliquer »
(avec confirmation), et la sortie de la commande affichée telle quelle.

⚠ **C'est une IDENTITÉ, pas une permission.** L'écran appartient au seul
compte de maintenance (`HiddenAccount::EMAIL`) : **pas même un super-admin
ne l'atteint**, le CEO compris. La décision est prise dans `Gate::before`,
AU-DESSUS du bypass super-admin
(`AppServiceProvider::MAINTAINER_ONLY_ABILITIES`) — sans cela `Gate::before`
l'accorderait à tous les super-admins, exactement comme pour
`GroupPolicy@updateClosed` (§16). L'ability `legacy-payments.reconcile`
n'est dans aucun preset et n'est accordable à personne.

Comme « Échéances en masse », la page est **hors de la barre latérale** et
atteinte par son lien direct — et, comme elle, ce n'est **pas** ce qui la
protège : le gate décide, le contrôleur revérifie, le Form Request rejoue
l'identité.

Le champ « Dossier de l'export » est borné par `config('gls.legacy_import_path')`
(`GLS_LEGACY_IMPORT_PATH` dans `.env`, défaut `base_path('data')`) : un
chemin hors de cette racine est refusé, `../..` compris. Élargir cette racine
à `/` donnerait à l'écran la lecture de tout le disque.

Tests : `tests/Feature/Backoffice/Access/LegacyReconciliationAccessTest.php`.

## Origine

Trois réparations manuelles des 07–08/09/2026, même problème sous trois
formes :

- **ISMAIL AMARIR** (Kénitra) — la cellule payeur contient ÉTUDIANT + PAYEUR
  (« ISMAIL AMARIR ZAKARIA AMARIR », son frère payait) : l'import trouvait
  deux vrais étudiants et refusait de deviner. 10 paiements / 10 100 DH
  jamais arrivés.
- **RAJA & CHAIMA EL ABLAOUI** (Casablanca) — le groupe tarifie 12 frais mais
  l'import ne crée que les lignes qu'un paiement touche : « Frais d'Octobre »
  n'existait pas, le repli approximatif a posé 1 300 DH sur « Frais
  d'inscription B2 ».
- **HAMZA LACHKAR** (Marrakech) — une avance éclatée sur les mauvais frais de
  la mauvaise inscription.

Tests : `tests/Feature/Backoffice/Finance/ReconcilierPaiementsLegacyTest.php`
(8 cas, sur un vrai .xlsx au format de l'export — fixture
`tests/Fixtures/legacy/`).

---

# L'agent d'un encaissement — `encaissements:reattribuer-agent`

## Le problème (audit 09/09/2026)

La table « Opérateur → Employé » de l'import proposait **tous** les employés
du centre. Un enseignant pouvait donc être enregistré comme l'agent qui a
encaissé — ce qui n'a aucun sens métier :

| Agent | Catégorie | Paiements importés | Montant |
|---|---|---:|---:|
| Oumnya Salim (25) | Autre | 3 114 | 3 078 490 DH |
| Aya Figar (72) | Enseignant | 52 | 22 050 DH |

Aya Figar n'a **aucun login** et ne s'est jamais connectée : elle n'a pas pu
encaisser un dirham. Les 16 lignes non importées à son nom ont été créées par
d'autres personnes.

## La prévention (déjà en place)

- `Employee::CATEGORIES_NON_ENCAISSEUSES` = « Enseignant » + « Autre ».
- `Employee::scopeCanCollectPayments()` — la source unique.
- L'écran d'import ne les propose plus, **et** les deux Form Requests
  (`AnalyzeEncaissementImportRequest`, `AnalyzeCombinedImportRequest`) les
  refusent côté serveur : une liste cliente n'est qu'un confort (§5).

⚠ Ceci borne un **CHOIX**, jamais une autorisation. `categorie` n'est jamais
lu dans un contrôle d'accès (§16) — l'accès reste décidé par les rôles et les
« Centres affectés ».

⚠ **La saisie normale n'a jamais eu ce problème** : `EnregistrerEncaissement`
dérive `agent_id` de l'employé CONNECTÉ, il n'est pas choisissable. Seul
l'import injectait un agent arbitraire.

## L'audit — voir TOUS les cas d'abord

```bash
php artisan encaissements:reattribuer-agent --auditer
```

Lecture seule. Une ligne par employé concerné, avec la colonne qui décide :

| id | employé | catégorie | total | importés | saisis | montant | connecté |
|---:|---|---|---:|---:|---:|---:|---|
| 25 | Oumnya Salim | Autre | 3 359 | 3 114 | 245 | 3 219 590 | jamais |
| 72 | Aya Figar | Enseignant | 71 | 58 | 13 | 32 600 | AUCUN |

**La colonne « connecté » tranche :**

- **AUCUN / jamais** — le nom a seulement été CHOISI dans le mapping
  d'import. Cette personne n'a pas de login, ou ne s'est jamais connectée :
  elle n'a pas pu encaisser un dirham. Réattribution sûre.
- **oui** — quelqu'un a réellement travaillé au guichet sous cette fiche. Le
  correctif est sa **catégorie dans sa fiche employé** (lui donner son vrai
  poste), PAS une réécriture de l'historique : `agent_id` est une trace
  d'audit, pas un libellé (§11).

## La réparation

```bash
# 1. simulation
php artisan encaissements:reattribuer-agent --de=72 --vers=1 --dry-run

# 2. sauvegarde, puis application
sudo -u postgres pg_dump gls_crm | gzip > /root/gls_crm_$(date +%F_%H%M).sql.gz
php artisan encaissements:reattribuer-agent --de=72 --vers=1

# variante : ne toucher que les lignes importées
php artisan encaissements:reattribuer-agent --de=72 --vers=1 --importes-seulement
```

Ce qu'elle ne touche **jamais** : `montant`, `date_paiement`, `methode`,
`caisse_id`, `caisses.solde`, `inscription_fee_id`. Seul le nom de l'agent
change. Chaque ligne passe par `save()` — jamais un `update()` de masse —
donc `Auditable` journalise « avant → après » (§11).

⚠ **`agent_id` est une trace d'audit, pas un libellé.** La commande est
volontairement ciblée par employé (`--de=`) plutôt qu'un balayage
« tous les non-encaisseurs » : réécrire l'agent d'un paiement que quelqu'un a
réellement saisi effacerait qui l'a fait. Chaque cas se juge.

Tests : `tests/Feature/Backoffice/Finance/AgentEncaisseurTest.php`.
