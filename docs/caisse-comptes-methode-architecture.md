# Comptes de paiement par centre et méthode — Architecture & Impact Report

> Phase 1 (audit) + Phase 2 (proposition) — 24/08/2026. **Aucun code modifié.**
> Complète `docs/caisse-solde-especes-analysis.md` (le constat) avec la cible.

---

## 1. Architecture actuelle (constats vérifiés dans le code)

| Constat | Preuve |
|---|---|
| `caisses.solde` est la seule balance stockée ; elle bouge **uniquement** par `CaisseLedger::move()` (lock FOR UPDATE, écriture via le modèle, entrée `activity('caisse')` avant/montant/après). Aucun `increment`/UPDATE brut ne subsiste dans `app/`. | `CaisseLedger.php:55-105` ; grep négatif confirmé |
| 7 appels au ledger dans 6 actions : `EnregistrerEncaissement:42` (credit inconditionnel), `SupprimerEncaissement:55`, `EnregistrerDepense:55` (si approbation OFF), `ApprouverDepense:41`, `EnregistrerRemboursement:62`, `ValiderTransfertCaisse:72/80`. | audit §1 |
| **`methode` n'est jamais consultée** avant de créditer/débiter : TPE/Chèque/Virement entrent dans la caisse physique. | `EnregistrerEncaissement.php:42` |
| `Caisse::TYPES = [Caissière, Externe]`. TPE/Chèque/Virement sont **dérivés** à la volée par `GetComptesCaisse::DERIVED_TYPES` (`Σ encaissements − Σ dépenses` par méthode, **sans centre**, **sans remboursements**). ⇒ le même dirham est compté deux fois (caisse + ligne dérivée). | `GetComptesCaisse.php:53-57, 181-208` |
| `caisse_id` n'est **jamais** choisi par le client : toujours `$agent->caisses()->first()` (+ auto-provision). Form Requests refusent `caisse_id`; `montant`/`caisse_id` immuables après création. | `EncaissementController:172,317`, `DepenseController:153`, `RemboursementController:63`, `EncaissementImporter:750` |
| **`methode` EST modifiable** après création (`UpdateEncaissementRequest:30`, `UpdateDepenseRequest:32`). Seule contrainte : un chèque suivi ne peut pas quitter `Chèque`. | asymétrie avec `montant`/`caisse_id` |
| **`remboursements` n'a aucun champ méthode** (migration, modèle, requests). Un remboursement d'un paiement TPE ne réduit donc jamais le compte TPE dérivé. | `create_remboursements_table.php:14-33` |
| 1 employé = 1 caisse `Caissière`, dans son centre **primaire** (`employees.etablissement_id`), créée par `CaisseProvisioner` ← `EmployeeObserver`. Pas d'unicité en base (`HasMany`, index simple). La caisse ne suit jamais un changement de centre primaire. | `CaisseProvisioner.php:27-37`, `Employee.php:273` |
| `caisses.etablissement_id` est **nullable** partout (Caisse, Student, Inscription, Employee, Cheque) ; NULL = « global, visible partout ». | migrations, idiome `whereNull()->orWhere()` |
| Centre d'un encaissement : **3 chemins non totaux qui se contredisent** — A `student.etablissement_id` (canonique : `EncaissementPolicy`, `GetEncaissementsList`), B `fee→inscription` (NULL pour toute avance), C `caisse.etablissement_id` (= centre primaire de l'opérateur ; utilisé par `GetDashboardStats:86`, `GetAnnualFraisSummary:86` ; **faux pour l'import legacy**). | audit §2 |
| Transferts : source = caisse de l'agent, destination choisie côté client, validée par le **propriétaire** de la destination. Rien ne vérifie le type des deux caisses. | `ValiderTransfertCaisse.php:39-51` |
| Chèques : cycle de vie **hors ledger** (En possession → Déposé → Encaissé/Rejeté), ne bouge aucun solde. | `Cheque.php:46-55`, `ChequesInertiaCrudTest:268` |
| Données locales : 29 caisses, 2 encaissements (TPE 1300 + Espèces 1300 ⇒ solde 2600 pour 1300 DH de cash réel), 0 dépense/remboursement/transfert. | tinker |

---

## 2. Recommandation : étendre `Caisse` (Option A), pas de nouveau modèle

**Un compte de méthode = une ligne `caisses`** de type `TPE` / `Chèque` / `Virement`,
possédée par un **centre** (`etablissement_id` NOT NULL), sans `responsable_employee_id`.

```
caisses
├── Caissière   (1 par employé, centre primaire)      ← ESPÈCES physiques
├── Externe     (coffre / tiers, créé à la main)      ← espèces aussi
├── TPE         (1 par centre, auto-provisionné)
├── Chèque      (1 par centre, auto-provisionné)
└── Virement    (1 par centre, auto-provisionné)
```

Pourquoi c'est le choix le plus sûr :

1. **Zéro nouveau chemin d'argent.** `CaisseLedger`, l'audit `solde_movement`,
   `AuditValueResolver`, `GetCaisseDetails`/`Show.tsx`, les KPI, les policies
   fonctionnent déjà sur une ligne `caisses`. Un `PaymentAccount` séparé
   obligerait à dupliquer le ledger (ou à le rendre polymorphe) — c'est
   précisément « une deuxième représentation » que l'énoncé interdit.
2. **Le schéma le prévoyait** : le commentaire de la migration `caisses`
   parle des « standing bank / cheque / external accounts money also lands in ».
3. **`caisse_id` est déjà immuable et stocké sur chaque ligne d'argent** ⇒
   l'annulation (`SupprimerEncaissement`), l'approbation (`ApprouverDepense`)
   et l'application d'avance (`AppliquerAvance` copie `caisse_id`) reversent /
   suivent **automatiquement le bon compte** sans nouvelle information (§18).
4. **`DERIVED_TYPES` disparaît** : les 3 lignes dérivées deviennent des lignes
   stockées ⇒ plus de double comptage par construction (§6).
5. Pas de refonte du domaine Finance : 1 service de résolution + 6 appels
   inchangés (ils reçoivent juste un autre `caisse_id`).

### 2.1 La seule nouveauté : `CaisseResolver` (Domain\Finance\Support)

```php
resolveFor(Employee $agent, string $methode, int $centreId): Caisse
  Espèces  → $agent->caisses()->first() ?? CaisseProvisioner (inchangé)
  autre    → Caisse Active (type = $methode, etablissement_id = $centreId)
             ?? provision idempotente
```

Appelé par `EncaissementController@store/storeAvance`, `DepenseController@store`,
`RemboursementController@store`, `EncaissementImporter::resolveCaisseFor`.
Le `caisse_id` résolu est stocké sur la ligne, comme aujourd'hui.

### 2.2 Centre d'un compte de méthode (§7) — **décision à valider**

Pour un **nouveau** paiement non-espèces, centre = **le centre actif du
contexte** (`CurrentContext::etablissementId()`), règle §11 « creates inherit
from the active context », avec repli sur le centre primaire de l'agent quand
le contexte est « Tous les centres ». Justification physique : le TPE est
celui du centre où l'agent travaille ; le chèque est reçu au guichet de ce
centre. (Alternative : centre de l'étudiant — canonique pour la *visibilité*
du paiement, mais pas pour l'endroit où l'argent arrive.)

### 2.3 Caisse physique des employés (§8)

Inchangée : 1 employé = 1 `Caissière`, ne reçoit plus que des espèces.
Le provisioner employé n'est pas touché.

### 2.4 Invariants nouveaux (§19) — en base, PostgreSQL

- Index unique partiel : `UNIQUE (etablissement_id, type) WHERE type IN ('TPE','Chèque','Virement')`.
- `CHECK (type NOT IN ('TPE','Chèque','Virement') OR (etablissement_id IS NOT NULL AND responsable_employee_id IS NULL))`.
- **`methode` devient immuable** après création (encaissement) et dès qu'une
  dépense est créée (son `caisse_id` est résolu à la création) — même règle
  que `montant`/`caisse_id`. Sinon un simple edit Espèces→Virement désynchronise
  compte et ligne. `UpdateEncaissementRequest`/`UpdateDepenseRequest` : retirer
  la règle, ou valider `=== valeur actuelle`.
- `Caisse::$fillable` perd `'solde'` (le ledger assigne l'attribut directement,
  il n'en a pas besoin).

### 2.5 Transferts (§11)

Un transfert reste un mouvement **d'espèces** : `DemanderTransfertCaisse` refuse
toute caisse source/destination dont le type ∉ {Caissière, Externe} ; les
`caisseOptions()` du modal ne listent que ces types. Les comptes de méthode
n'ont pas de propriétaire, donc personne ne pourrait de toute façon les
valider — la garde le rend explicite. **Aucun workflow « remise en banque »
n'est introduit** (les chèques gardent leur cycle hors ledger).

### 2.6 Remboursements (§10)

Ajout de `remboursements.methode` (varchar 30, nullable pour l'historique,
`required|in:METHODES` sur les nouveaux). Historique backfillé à `Espèces`
(c'est ce qui a réellement été débité). `StoreRemboursementRequest` + modal :
un select Méthode.

### 2.7 Écrans

- **Comptes de caisse** : plus de lignes `derived` ; regroupement par centre
  (`Caissière`/`Externe` du centre, puis TPE, Chèque, Virement, **Total
  centre**). Libellé de colonne « Compte » plutôt que « Caisse ». Les comptes
  de méthode : non éditables, non supprimables (comme aujourd'hui les dérivés),
  mais désormais **cliquables** (page Show + journal, ce sont de vraies lignes).
- **Ma caisse** : la carte « Solde » devient **« Solde espèces »** ; les KPI
  sont automatiquement purement espèces (la caisse `mine` ne reçoit plus que
  ça) — libellés « Encaissements espèces / Dépenses espèces / Remboursements
  espèces ». En dessous, bloc **« Autres encaissements du centre »** : solde
  TPE / Chèque / Virement du centre actif (même permission
  `cash-registers.view`, scope centre via `CenterAccessService`).
- Listes Encaissements/Dépenses : la colonne « Caisse » affiche le nom du
  compte (`TPE — Casablanca`), rien d'autre ne change.

---

## 3. Fichiers à modifier

**Domain / services**
- `app/Models/Caisse.php` — 3 types, `TYPES_METHODE`, `isCompteMethode()`, retirer `solde` du fillable.
- `app/Domain/Finance/Support/CaisseResolver.php` — **nouveau**.
- `app/Services/CaisseProvisioner.php` — `+ provisionMethodAccountsFor(Etablissement)`; `app/Observers/EtablissementObserver.php` — **nouveau** (auto-provision sur création de centre) + enregistrement dans `AppServiceProvider`.
- `app/Domain/Finance/Actions/DemanderTransfertCaisse.php`, `ValiderTransfertCaisse.php` — garde « espèces uniquement ».
- `app/Domain/Finance/Actions/EnregistrerRemboursement.php` — `methode` dans l'extra du ledger.
- `app/Domain/Finance/Queries/GetComptesCaisse.php` — supprimer `DERIVED_TYPES`, tri/regroupement par centre.
- `app/Domain/Finance/Queries/GetCaisseJournal.php` — `+ comptesMethodeDuCentre()`, libellés.
- `app/Domain/Finance/Queries/GetCaisseTransfersList.php` — `caisseOptions()` limité aux types espèces.
- `app/Console/Commands/RecalculerSoldesCaisses.php` — **nouveau** (`caisse:recalculer-soldes`, voir §5).

**HTTP**
- `EncaissementController` (store, storeAvance, update), `DepenseController` (store, update), `RemboursementController` (store), `CaisseController` (props `compteTypeFilters`, `comptesMethode`), `CaisseTransferController` (options).
- `app/Services/Import/EncaissementImporter.php` — `resolveCaisseFor($employeeId, $methode)`.
- Requests : `UpdateEncaissementRequest`, `UpdateDepenseRequest` (immutabilité), `StoreRemboursementRequest` (+methode), `StoreCaisseTransferRequest` (types), `StoreCaisseRequest` (inchangé : `Externe` seul).

**React**
- `Pages/Backoffice/Caisses/Index.tsx` (cartes + bloc centre), `ComptesPanel.tsx` (groupement centre, plus de `derived`), `Remboursements/Index.tsx` (select méthode), `Types/index.ts`, `lang/fr.json`.

**Docs** : `CLAUDE.md` §11 (invariants), `gls-crm-schema.md` §10, `docs/audit-journal.md` §5b.

---

## 4. Changements base de données (3 migrations, toutes additives)

1. `caisses` : index unique partiel + CHECK (§2.4). Pas de nouvelle colonne.
2. `remboursements.methode` varchar(30) nullable + backfill `Espèces`.
3. Provision des comptes de méthode pour chaque `etablissements` existant
   (INSERT idempotent, `solde = 0`) — data migration sûre en prod
   (`migrate --force`), rejouable.

Aucune table nouvelle, aucune colonne `solde_*`, aucun changement de type.

---

## 5. Migration des données historiques — `caisse:recalculer-soldes`

Principe : **re-homer** chaque ligne d'argent non-espèces encore logée dans
une caisse `Caissière`/`Externe`, en déplaçant le solde **par le ledger** (les
deux jambes journalisées, motif « Reclassement par méthode de paiement ») et en
mettant à jour `caisse_id` sur la ligne (changement tracé par `Auditable` :
avant/après). Rien n'est supprimé, l'historique du journal reste lisible.

| Table | Lignes concernées | Mouvement |
|---|---|---|
| `encaissements` | `methode ≠ Espèces` AND `applied_from_encaissement_id IS NULL` AND caisse.type ∈ espèces | debit till / credit compte méthode |
| `encaissements` (apply rows) | `applied_from_encaissement_id IS NOT NULL` | `caisse_id` suit l'avance parente, **aucun mouvement** |
| `depenses` | `methode_paiement ≠ Espèces`, caisse espèces | si `Approuvée` : credit till / debit compte ; si `En attente`/`Refusée` : `caisse_id` seulement |
| `remboursements` | backfill `methode = Espèces` | aucun mouvement |
| `caisse_transfers` | — | aucun (espèces par définition) |

Modes : **dry-run par défaut**, `--apply` pour exécuter ; idempotent (une ligne
déjà dans un compte de méthode est ignorée) ; tableau avant/après par caisse ;
transaction par ligne ; **refuse de s'exécuter en `--apply` s'il reste des
lignes ambiguës** (voir ci-dessous) sauf `--centre-rule=…` explicite.

### ⚠ Ambiguïté historique — à trancher (§13)

Le centre du compte de destination d'une ligne historique n'est stocké nulle
part. Deux candidats :

- **C — centre de la caisse actuelle** (= centre primaire de l'opérateur) :
  toujours résoluble (`caisse_id` NOT NULL, 0 caisse sans centre en local) ;
  **faux** pour l'import legacy (un opérateur de Marrakech a importé Agadir).
- **A — centre de l'étudiant** : canonique pour la visibilité, NULL pour les
  étudiants sans centre (1 en local), non défini pour les dépenses.

Proposition : dry-run classe chaque ligne en **non ambiguë** (A = C, ou A NULL)
ou **ambiguë** (A ≠ C, typiquement `legacy_source IS NOT NULL`) et imprime la
liste. En local : 1 seule ligne TPE, non ambiguë. **Je ne devine pas la règle
pour la production** — c'est à valider (recommandation : C, car c'est
physiquement là que le TPE/chèque a été traité ; A pour les lignes legacy).

---

## 6. Risques

| Risque | Mitigation |
|---|---|
| Soldes prod déjà pollués | commande dry-run + rapport avant/après, jamais d'UPDATE brut, jambes journalisées |
| Lignes legacy re-homées dans le mauvais centre | classification ambiguë + STOP tant que la règle n'est pas validée |
| Un edit `methode` désynchronise compte/ligne | immutabilité de `methode` (Request) + test |
| Transfert vers un compte de méthode | garde Domain + Request + options filtrées |
| Nouveau centre sans comptes | `EtablissementObserver` + self-heal dans le resolver |
| Contexte « Tous les centres » lors d'un paiement TPE | repli centre primaire de l'agent (documenté) |
| Tests existants sur les lignes dérivées (`ComptesCaisseTest` ×5, `CaisseAuditTrailTest`, `DepensesInertiaCrudTest` via `soldeActuel`) | réécrits pour la sémantique stockée |
| `GetDashboardStats:86` / `GetAnnualFraisSummary:86` scoping via caisse | inchangés, mais désormais cohérents pour les comptes de méthode (centre = contexte de création) — hors périmètre |
| Chèque rejeté après coup | reste hors ledger (comme aujourd'hui) — point ouvert, non traité |

---

## 7. Tests requis (nouveau `tests/Feature/Backoffice/Finance/ComptesMethodeTest.php` + mises à jour)

- Encaissement Espèces/TPE/Chèque/Virement → seul le compte correspondant bouge ; **négatifs** : TPE/Chèque/Virement ne touchent pas la caisse de l'agent.
- Dépense (approbation ON et OFF) et remboursement par méthode → seul le bon compte est débité.
- Annulation d'un encaissement TPE → débit du compte TPE, jamais de la caisse espèces (via `caisse_id` stocké, même si `methode` était tamperé).
- Avance TPE puis application → un seul crédit, sur le compte TPE.
- Isolation centre : Casablanca/TPE +1000 ne touche ni Rabat/TPE, ni Rabat/Espèces, ni Casablanca/Espèces.
- Double comptage : `GetComptesCaisse` ne contient plus aucune ligne `derived` ; Σ des soldes = Σ des mouvements ledger.
- Unicité : 2ᵉ compte TPE pour le même centre ⇒ exception PG ; création d'un centre ⇒ 3 comptes.
- Transfert vers/depuis un compte de méthode ⇒ refusé (Domain + HTTP).
- `methode` immuable en update (encaissement, dépense).
- Audit : chaque mouvement de compte de méthode a son `solde_movement`.
- Commande : dry-run ne modifie rien ; `--apply` re-home + journalise ; 2ᵉ run = no-op ; ligne ambiguë ⇒ refus.

---

## 8. `Caisse` étendu vs `PaymentAccount` séparé — verdict

**Étendre `Caisse`.** Un modèle séparé n'apporte qu'une chose (une séparation
nominale) et coûte : un second ledger ou un ledger polymorphe, un second audit,
un second Show/journal, une seconde policy, et une jointure conditionnelle sur
chaque ligne d'argent (`caisse_id` **ou** `payment_account_id`) — c'est-à-dire
exactement le risque de double représentation. Avec `Caisse` étendu, la
sémantique « un dirham = une ligne `caisses` » est portée par une colonne déjà
immuable et déjà auditée.

---

## Décisions prises (24/08/2026) et écarts par rapport à la proposition

1. **Option A (étendre `Caisse`)** — validée, implémentée.
2. **Centre d'un nouveau paiement non-espèces** = centre actif du contexte,
   repli centre primaire de l'agent ; l'import legacy passe le centre du lot
   (`CaisseResolver::resolveFor()`).
3. **Re-homing historique** : `caisse:recalculer-soldes` — règle C par défaut,
   lignes A ≠ C bloquantes tant que `--ambiguous=caisse|student` n'est pas
   passé, lignes sans centre toujours refusées. **Non exécuté en
   production** : lancer d'abord le dry-run sur le VPS et lire la sortie.
4. **⚠ Écart voulu par le métier — dépenses et remboursements** : ils
   débitent TOUJOURS la caisse physique de l'agent (`CaisseResolver::tillOf()`),
   quelle que soit la méthode indiquée. `remboursements.methode` n'a **pas**
   été ajouté (§2.6 et §5 « depenses » de ce document sont donc caducs) ; la
   commande de reclassement ne touche que les **encaissements**.
   `depenses.methode_paiement` reste un libellé descriptif, modifiable comme
   avant. **Une exception** (`CaisseResolver::forRemboursement()`) : le
   remboursement lié à un paiement financé par un chèque **rejeté** débite le
   compte Chèque du centre, pas la caisse — cet argent n'a jamais existé en
   espèces (constaté sur RMB-001 le 24/08/2026).
5. `encaissements.methode` est gelée après création (§2.4) — implémenté.

### Ce qui a été livré

- Modèle/DB : `Caisse::TYPES` (+3), `TYPES_ESPECES`, `TYPES_METHODE`,
  `isEspeces()`/`isCompteMethode()` (`solde` reste fillable pour les fixtures
  de test — les Requests ne l'acceptent pas) ; migration
  `2026_08_24_200000` (index unique partiel + CHECK + provision idempotente).
- Domain : `CaisseResolver`, `CaisseProvisioner::compteMethodeFor()`,
  `EtablissementObserver`, gardes espèces dans les 2 actions de transfert.
- HTTP : `EncaissementController` (store / storeAvance / update),
  `DepenseController@store`, `RemboursementController@store`,
  `EncaissementImporter`, `UpdateEncaissementRequest`, `CaisseController`.
- Lecture : `GetComptesCaisse` (plus de `DERIVED_TYPES`, tri par centre,
  `totauxParCentre()`), `GetCaisseJournal` (`comptesMethode`),
  `GetCaisseTransfersList::caisseOptions()` (espèces seulement).
- React : `Caisses/Index.tsx` (« Solde espèces » + nouvel onglet **« Caisse
  globale »** — `GlobalePanel.tsx`, `GetCaisseGlobale` : une carte par
  nature de compte — Caisse personnelle / TPE / bancaire / chèque / externe
  — et, sous la carte active, les comptes de cette nature avec leur solde ;
  même permission `cash-registers.view`, même périmètre que le journal
  « all »), `ComptesPanel.tsx` (groupes par centre + total),
  `Encaissements/Index.tsx` (méthode gelée en édition), `Types`.
- Liste des encaissements (`GetEncaissementsList`) : un étudiant **sans
  centre** (global) et le centre de l'inscription comptent désormais comme
  « dans le centre actif » — la page restait vide juste après avoir encaissé
  pour un tel étudiant ; tri = dernier enregistré en premier (`id desc`).
- Commande : `caisse:recalculer-soldes`.
- Tests : `ComptesMethodeTest` (nouveau), `ComptesCaisseTest` (lignes
  dérivées → comptes stockés).

---

## Procédure de déploiement (production — à faire À LA MAIN)

1. `deploy.sh` (snapshot pg_dump + `migrate --force`) : crée l'index unique,
   le CHECK et les 3 comptes de méthode par centre. Aucun solde ne bouge.
2. Lire le dry-run :
   ```bash
   sudo -u www-data php8.4 artisan caisse:recalculer-soldes
   ```
   Il liste les lignes à reclasser, les lignes **ambiguës** (centre de
   l'étudiant ≠ centre de la caisse) et celles sans centre.
3. Si la liste ambiguë n'est pas vide : décider de la règle avec GLS, puis
   `--ambiguous=caisse` ou `--ambiguous=student`. S'il reste des lignes sans
   centre : corriger les données d'abord — la commande refuse.
4. Appliquer : `... caisse:recalculer-soldes --apply [--ambiguous=…]`. Chaque
   ligne est une transaction, les deux jambes sont dans le Journal d'audit
   (motif « Reclassement par méthode de paiement »). Rejouable sans effet.
5. Vérifier « Comptes de caisse » : Σ par centre = espèces + TPE + Chèque +
   Virement, et « Ma caisse » n'affiche plus que les espèces.
