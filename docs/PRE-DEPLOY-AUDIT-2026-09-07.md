# Audit PRÉ-DÉPLOIEMENT — correctifs QA du 07/09/2026

**Projet :** GLS CRM · **Cible :** `crm.gls-sprachzentrum.ma` (VPS Hostinger)
**Portée :** les 16 correctifs code-only issus de l'audit QA du 07/09/2026
**Statut :** ✅ **APTE AU DÉPLOIEMENT** — sous réserve des 3 points § 7

---

## 1. Verdict en une page

| Contrôle | Résultat | Bloquant ? |
|---|---|---|
| `npx tsc --noEmit` | ✅ **0 erreur** | — |
| `npm run build` | ✅ **succès** (1,16 s) | — |
| Migrations en attente | ✅ **0** | — |
| Migrations modifiées par ces correctifs | ✅ **0** | — |
| Routes chargées | ✅ **237**, tous les endpoints touchés résolvent | — |
| Pages testées sur données RÉELLES | ✅ **10/10** rendent (200/302) | — |
| Écritures de données dans le diff | ✅ **0** | — |
| `theme-reference/` intact | ✅ **0 fichier modifié** | — |
| Patch de schéma appliqué | ✅ **aucun** | — |
| Tests sur le périmètre touché | ✅ **812 passés** | — |
| Échecs de tests | ⚠️ **2, tous deux ANTÉRIEURS** | non (voir § 6) |
| Suite complète en un seul run | ⚠️ **impossible** (limite connue) | non (voir § 6) |

**Aucune migration, aucun DDL, aucune reprise de données.** Ce déploiement est
un **remplacement de code pur** : `git pull` + `composer install` +
`npm run build` + vidage des caches. Un `rollback` = `git revert` + redéploiement.

---

## 2. Ce qui part en production (16 correctifs)

### Sécurité / cloisonnement des centres — 4

| ID | Effet visible en production |
|---|---|
| **C-2** | Les dropdowns « Établissement » n'affichent plus que les centres réellement accessibles. **25 comptes réels** voyaient les 7 centres. |
| **C-3** | Modifier un employé ne peut plus **réécrire silencieusement ses centres affectés** (donc son périmètre d'accès). Refus en modification ; création inchangée. |
| **H-7** | Le **détail** du journal d'audit applique enfin le périmètre de la liste (IDOR fermé). |
| **H-6** | Les **deux** comptes masqués sont filtrés du journal (un seul l'était). |

### Intégrité fonctionnelle — 4

| ID | Effet visible |
|---|---|
| **C-4** | L'appel de présence n'est plus écrit sur **une autre séance** en changeant de date. |
| **C-5** | Restaurer un frais masqué ne peut plus le **supprimer** via une course d'auto-save. |
| **C-6** | La **note** d'une ligne de frais n'est plus perdue à la création. |
| **H-5** | Les avances détachées redeviennent remboursables — **258 lignes / 225 100 MAD** d'argent bloqué. |

### Exactitude d'affichage — 3

| ID | Effet visible |
|---|---|
| **H-1** | Effacer un filtre de date **élargit** au lieu de vider (Dépenses, Remboursements, Chèques). |
| **H-3** | Le tableau de bord ne sous-compte plus les employés (6 centres sur 7 étaient faux). |
| **M-13** | « Caisse globale » masque la colonne Centre quand un seul centre est actif. |

### Robustesse / performance — 5

| ID | Effet |
|---|---|
| **M-4** | 3 modèles reflètent le défaut SQL → le journal ne peut plus afficher un faux « avant ». |
| **M-5** | `DormantTill` ne tombe plus dans le piège de portée globale (§11). |
| **M-14** | Liste Chèques : **100 lignes en 11 requêtes** (au lieu de ~111). |
| **M-15** | Modal Inscriptions : **6 requêtes → 1**. |

---

## 3. Preuve de sécurité des données

### 3.1 Aucune écriture dans le code livré

Grep sur **chaque ligne ajoutée** des 18 fichiers PHP modifiés :

```
DB::(update|delete|statement|insert) | ->update( | ->delete( | ->save(
increment( | decrement( | truncate
→ 0 résultat
```

### 3.2 Aucun DDL

- Migrations en attente : **0**
- Fichiers de `database/migrations/` modifiés : **0**
- `docs/production-schema-patches.sql` : les correctifs H-2 / H-13 y sont
  écrits mais **explicitement marqués NON APPLIQUÉS**. Ils ne partent PAS
  avec ce déploiement.

### 3.3 Réparations de données volontairement NON incluses

| Point | Montant | Statut |
|---|---:|---|
| C-7 — paiements non-espèces en caisse physique | 4 707 829 MAD | **non touché** |
| H-4 — écart de caisse | 7 700 MAD | **non touché** |
| D-1 — 4 dates aberrantes | 2 500 MAD | **non touché** |
| C-3 — affectations historiques | — | **non touché** |
| H-11 — stock sous-évalué | — | **non touché** |

Diagnostic et requêtes en lecture seule : `docs/DATA-REPAIRS-PENDING-2026-09-07.md`.

---

## 4. Vérification fonctionnelle sur données réelles

10 pages rendues avec le compte super-admin sur la base `gls_crm` (production
locale, 26 819 encaissements / 22,1 M MAD) :

```
students.index      200      dashboard          200
employees.index     200      inscriptions.index 200
caisses.index       200      groups.index       200
cheques.index       200      recouvrement.index 302 *
depenses.index      200      9wiwid.index       200
```

\* 302 = redirection canonique de la fenêtre de dates (comportement documenté,
pas une erreur).

**C'est le contrôle qui attrape ce qu'une relecture de code ne voit pas :**
chaque écran modifié s'affiche réellement, avec les vraies données.

---

## 5. Résultats de tests

| Suite | Tests | Passés | Assertions | Échecs |
|---|---:|---:|---:|---:|
| Audit + régression H-7 | 81 | **80** | 336 | 1 (antérieur) |
| Finance + Inscriptions + Attendance | 575 | **574** | 3 248 | 1 (antérieur) |
| Access + People (C-3 dans les 2 sens) | 36 | **36** | 203 | 0 |
| Groups | 122 | **122** | 451 | 0 |
| **Total périmètre touché** | **814** | **812** | **4 238** | **2** |

**9 tests de régression ajoutés** : `CentreDropdownScopeTest` (6),
`AuditLogDetailScopeTest` (3).

---

## 6. Points connus — NON bloquants, à connaître avant de déployer

### 6.1 Deux tests rouges, tous deux ANTÉRIEURS aux correctifs

Vérifiés par la méthode forte : `git stash` de **la totalité** des
modifications, relance du test seul, **échec identique** sur code d'origine.

| Test | Ce qu'il révèle |
|---|---|
| `P0RemediationTest::test_a_changement_registration_cannot_be_reactivated` | Un dossier **Changement** peut repasser **Active** sans erreur → il revient dans les retards et le dû. |
| `ComptesMethodeTest::test_the_method_of_a_recorded_payment_is_frozen` | Le gel de `methode` sur un paiement enregistré ne se déclenche pas. **Invariant monétaire §11.** |

> ⚠️ Ces deux défauts **existent déjà en production aujourd'hui**. Déployer ne
> les aggrave pas — mais le second touche l'argent et mérite un ticket.

### 6.2 La suite complète ne peut pas tourner d'un bloc

`CombinedImportController.php:76` appelle `set_time_limit(300)`. Sous Windows,
PHP compte le temps **mural du processus PHPUnit entier** : dès qu'un test
Import s'exécute, tout ce qui suit partage ce budget et le run meurt.

C'est une **limite d'environnement déjà documentée** dans les notes du projet
(« run the suite in chunks with Import last »), pas une régression. Les suites
par lots ci-dessus couvrent l'intégralité du périmètre modifié.

---

## 7. Trois actions AVANT de lancer `deploy.sh`

### 7.1 ⚠️ L'arbre de travail contient du code qui n'est PAS de cet audit

`git status` mêle mes 23 fichiers à des modifications préexistantes
(réorganisation de `docs/`, fonctionnalité « Échéances en masse » non suivie
dans `resources/js/Pages/Backoffice/EcheancesEnMasse/`).

**À faire :** relire `git diff` et committer **délibérément**. Ne pas faire un
`git add -A` aveugle.

> Détail : `lang/fr.json` affiche +19/−1, mais **une seule ligne** vient de cet
> audit (le message de validation C-3). Les 17 autres clés appartiennent à
> « Échéances en masse ».

### 7.2 Vérifier la sauvegarde

`deploy.sh` fait déjà un `pg_dump` **avant** toute action (ajouté après
l'incident du 21/08/2026). **Confirmer que le dump s'écrit bien** dans
`/var/backups/crm-gls/` avant de continuer.

### 7.3 Ne pas laisser passer un patch de schéma par erreur

`deploy.sh` lance `artisan migrate --force`. C'est **sans effet ici** (0
migration en attente), mais s'assurer qu'aucun patch de
`production-schema-patches.sql` n'a été appliqué à la main entre-temps.

---

## 8. Procédure de déploiement

```bash
# 1. Local — committer délibérément (cf. § 7.1)
git status                 # relire ce qui part
git add <fichiers choisis>
git commit -m "QA 07/09/2026: cloisonnement centres, filtres, avances, journal"
git push origin main

# 2. Serveur
ssh <vps>
/var/www/crm-gls/deploy.sh     # dump + pull + build + caches + restart
```

### Contrôles POST-déploiement (5 minutes)

1. **C-2** — se connecter avec un compte mono-centre : le dropdown
   « Établissement » ne doit proposer **que son centre**.
2. **H-1** — Dépenses → saisir « Du », puis l'effacer : la liste doit
   **s'élargir**, jamais se vider.
3. **H-3** — Dashboard : le compteur d'employés doit **égaler** la liste Employés.
4. **H-5** — un étudiant avec avance détachée : elle apparaît dans le
   sélecteur de remboursement.
5. **C-4** — Séances : changer de date, vérifier que l'appel **se réinitialise**.
6. Journal d'audit : aucune erreur 500, le compte technique reste masqué.

### Rollback

```bash
git revert <sha> && /var/www/crm-gls/deploy.sh
```

Aucune migration à défaire, aucune donnée à restaurer — **le rollback est un
simple retour de code.**

---

## 9. Ce qui reste ouvert (hors de ce déploiement)

| # | Sujet | Enjeu |
|---|---|---|
| C-1 | Niveau `A1` | 8 classes actives non modifiables |
| C-7 | Sens `--ambiguous` | **4,7 M MAD** |
| H-4 | Ajustement caisse | **7 700 MAD** |
| D-1 | 4 dates aberrantes | via l'UI, sur justificatifs |
| H-8 / H-9 / H-12 | Import, transitions de statut, hiérarchie des rôles | trous réels, intacts |
| §5 bis | Réactivation d'un dossier `Changement` | antérieur |
| — | Gel de `methode` inopérant | antérieur, **monétaire** |
| H-2 / H-13 / M-2 | Fenêtre de maintenance schéma | 0 ligne impactée |
