# Audit QA complet — CRUD, modèles & cohérence des données

**Projet :** GLS CRM (Laravel 13 · Inertia/React 19 · PostgreSQL 17)
**Date :** 07/09/2026 · **Base auditée :** `gls_crm` (données réelles de production)
**Méthode :** revue de code exhaustive (4 auditeurs parallèles) **+ vérification par requêtes SQL sur les données réelles**.
**Aucune correction n'a été appliquée.** Ce document est un constat.

---

## 0. Résumé exécutif

| | |
|---|---:|
| Modules CRUD audités | 30 |
| Modèles Eloquent audités | **34 / 34** |
| Migrations vérifiées | 41 |
| **Anomalies confirmées** | **42** |
| dont **CRITIQUES** | **7** |
| dont **HAUTES** | **13** |
| Correctifs sans risque production | 36 / 42 |
| Correctifs touchant des données | 6 |

### Volumétrie de production au moment de l'audit

| Table | Lignes | | Table | Lignes |
|---|---:|---|---|---:|
| students | 4 995 | | encaissements | 26 819 |
| inscriptions | 6 184 | | inscription_fees | 102 850 |
| groups | 189 | | seances | 12 089 |
| employees | 91 | | presences | 248 748 |
| users | 28 | | activity_log | 580 185 |
| caisses | 112 | | **Argent encaissé** | **22 128 259,00 MAD** |

### Ce qui est SAIN (vérifié par requête, pas supposé)

- **Intégrité référentielle : parfaite.** 9 sondes d'orphelins → **0 orphelin**.
- **Cohérence année ↔ centre : parfaite.** 0 inscription dont l'année diffère de son groupe, 0 séance divergente, 0 `annee_scolaire_id` NULL, 0 `etablissement_id` NULL.
- **Invariants monétaires respectés** : aucun `increment/decrement('solde')`, `CaisseLedger` seul chemin, aucune route `destroy` monétaire, `montant`/`caisse_id` non éditables.
- **Verrouillage transactionnel** : tout « lire un solde puis écrire » est dans `DB::transaction` + `lockForUpdate()`.
- **Conformité PostgreSQL (§17) : parfaite.** 0 `like` (tout en `ilike`), 0 `json()`, 0 colonne monétaire flottante, 0 branche `DB::getDriverName()`. Casts monétaires 100 % `decimal:2`.
- **Cœur d'autorisation solide** : invariants super-admin, verrouillage de `centers.access-all`, filtre `superAdminOnly()`.
- **Registre d'audit sans dérive** : 32 modèles audités ↔ 32 entrées dans `AuditLogRegistry::map()`.

### Réponse directe à votre question

> *« vérifier que les données en 2026/2027 ou une autre année ne posent pas de problèmes, comme le dropdown établissement »*

**Le modèle année/centre est sain : aucun module ne se vide au changement d'année.** Mais votre intuition sur les dropdowns était juste, et deux autres défauts du même genre existent :

1. **Les dropdowns « Établissement » ne sont pas filtrés** — **25 comptes réels** voient les 7 centres au lieu d'1 seul → **C-2**
2. **Effacer un filtre de date SUPPRIME des lignes** au lieu d'élargir — **26 035 → 0** sur le Journal de caisse → **H-1**
3. **9 groupes de 2026/2027 sont impossibles à enregistrer** → **C-1**

---

## 1. CRITIQUE

### C-1 — 9 groupes de **2026/2027** impossibles à enregistrer (`niveau = 'A1'`)

**Fichiers :** `app/Models/Group.php:61` · `StoreGroupRequest.php:32` · `UpdateGroupRequest.php:41`

`Group::NIVEAUX` n'accepte que les sous-niveaux CEFR (`A1.1`, `A1.2`, `A2.1`…). Or **9 groupes portent `niveau = 'A1'`** (écrit par l'import legacy, qui ne valide pas contre la constante).

**Preuve — les 9 sont TOUS en année 2 (2026/2027), 8 sont « En formation » :**

| id | nom | statut | centre |
|---:|---|---|---:|
| 11 | b2 septembre | En inscription | 1 |
| 118 | NV SEPT 19H | En formation | 5 |
| 183 | le 7 Septembre 17h | En formation | 2 |
| 184 | OCTOBRE 19H | En formation | 3 |
| 185 | Shift 10h00→ Omar Oct | En formation | 4 |
| 186 | Yassine SEPT 19H | En formation | 5 |
| 187 | NV OCT 19H | En formation | 5 |
| 188 | NV Fin SEPT 16H | En formation | 5 |
| 189 | Nv grp 10h | En formation | 6 |

**Preuve d'échec (exécutée) :** `Rule::in(Group::NIVEAUX)` sur `'A1'` → *« Le champ niveau est invalide. »*

**Reproduction :** contexte 2026/2027 → Groupes → « NV SEPT 19H » → modifier **la capacité seulement** → Enregistrer → rejet sur `niveau`, champ non touché. Seule issue : reclasser le niveau pédagogique — **réécrire une donnée métier comme effet de bord**.

**Correctif — décision métier requise :**
- **(a)** ajouter `'A1'` à la liste acceptée → **pur code, sans risque, immédiat** ; ou
- **(b)** remapper les 9 lignes vers leur vrai sous-niveau → **réparation de données**, à confirmer avec les centres.

⚠ Ne **pas** relâcher la règle en `string` : cela rouvrirait l'écriture de n'importe quel niveau.
**Risque production :** (a) nul · (b) `groups.niveau` alimente la classification des frais.

---

### C-2 — Les dropdowns « Établissement » exposent les 7 centres à qui n'en gère qu'un

**Fichiers :** `StudentController.php:90` · `Employees/EmployeeController.php:70` · `CaisseController.php:162-166`

```php
'etablissements' => Etablissement::query()->orderBy('nom_centre')->get(['id','nom_centre']),
```

Aucun filtre de portée. Le funnel correct **existe déjà** (`GetAccessibleCenterOptions`, bien utilisé par Settings/Salles/Frais ; Stock et les 5 imports utilisent `scopeAccessibleCenters`). Ces trois écrans le contournent.

**Preuve mesurée sur les comptes réels — 25 utilisateurs concernés :**

| Utilisateur | Portée réelle | Voit | Fuite |
|---|---:|---:|---:|
| Amine Rafik | 3 | 7 | +4 |
| Latifa Abou Elfath | 3 | 7 | +4 |
| Ichrak Fakroune | 1 | 7 | **+6** |
| Mustapha Ben Lmekki | 1 | 7 | **+6** |
| Abderrahim Elmoulabbi | 1 | 7 | **+6** |
| … *(25 comptes au total)* | | | |

C'est **exactement le bug que vous décriviez**. Sur Employés c'est pire : la même liste alimente le MultiSelect « Centres affectés » (`Employees/Index.tsx:684`, dont le commentaire ligne 682 affirme à tort que la liste est restreinte) — ce qui ouvre **C-3**.

**Correctif :** router les 3 sources via `GetAccessibleCenterOptions`. **Pur code, sans risque.**

---

### C-3 — Modifier un employé peut **réécrire silencieusement ses centres affectés**

**Fichier :** `Employees/EmployeeController.php:296-298` (appelé par `store():91` et `update():127`)

```php
$narrowed = array_values(array_intersect($ids, $allowed));
$ids = $narrowed === [] ? $allowed : $narrowed;   // ← substitution silencieuse
```

Si l'intersection est vide, le code **ne refuse pas** : il substitue **tous les centres de l'acteur**, puis `syncEtablissements()` **écrase l'affectation réelle de l'employé**. Comme `CenterAccessService::accessibleCenterIds()` lit ce pivot, **la portée d'accès de la victime change** — sans erreur, avec un message de succès.

**Reproduction :** responsable affecté à {Marrakech, Rabat} → modifier un employé de Casablanca → ne cocher que Casablanca (proposé, cf. C-2) → enregistrer → l'employé devient Marrakech+Rabat.

**Correctif :** `ValidationException` si `$narrowed === []` ; en modification, préserver les centres hors portée déjà présents (motif déjà correct dans `FraisController::syncPayload():196-201`).

⚠ **Risque production :** le garde-fou est sûr, **mais des lignes `employee_etablissement` déjà écrites par ce chemin peuvent être fausses**. À **inspecter via le journal d'audit** (`Employee` est `Auditable`) — **ne jamais re-dériver** les affectations.

---

### C-4 — L'appel présences d'une séance est écrit sur **une autre séance**

**Fichier :** `resources/js/Pages/Backoffice/Seances/Show.tsx:105`

`presences` est initialisé par un `useState` **paresseux**, et les sélecteurs de date/enseignant changent de séance avec `preserveState: true`. Le fichier ne contient que **2 `useEffect`**, dont aucun ne resynchronise l'état (vérifié) : **l'état de l'appel précédent survit au changement de séance**.

Pour un groupe qui se réunit lundi/mercredi/vendredi, les statuts obsolètes passent le filtre serveur (les étudiants sont bien inscrits au groupe) et **sont écrits sur la mauvaise séance**, écrasant un appel déjà fait.

**Gravité :** 248 748 présences en base ; corruption silencieuse d'un registre pédagogique.
**Correctif :** resynchroniser `presences` sur changement de `seance.id` (`useEffect` ou `key` sur le composant). **Pur code.**

---

### C-5 — La restauration d'un frais peut **supprimer une ligne portant de l'argent**

**Fichier :** `resources/js/Pages/Backoffice/Inscriptions/Index.tsx:1005`

`restoreHiddenFee` **ne purge pas** le debounce d'auto-sauvegarde de 600 ms (contrairement à `removeEditingLine`). La sauvegarde en attente part donc avec le tableau **d'avant** la restauration, et `MettreAJourFraisInscription` **supprime définitivement** la ligne restaurée.

**Correctif :** `clearTimeout` du timer avant restauration, comme dans `removeEditingLine`. **Pur code.**
⚠ Croise la règle §11 « retirer un frais payé libère son argent en avance » — un frais supprimé par cette course doit avoir libéré son paiement.

---

### C-6 — Le champ « note » d'un frais est **silencieusement perdu** à la création

**Fichiers :** `StoreInscriptionRequest.php:48-54` (**pas de règle `fee_lines.*.note`**) · `UpdateInscriptionFeesRequest.php:38` (règle présente) · `InscriptionController.php:550` (lit `$line['note']`)

Le modal envoie bien `note` (vérifié : `Index.tsx:140,326,344`), l'**édition** le valide et l'enregistre, mais à la **création** la règle manque → `validated()` ne contient pas la clé → `$line['note'] ?? null` vaut toujours `null`. **Aucune erreur affichée** : l'utilisateur croit avoir saisi une note.

**Correctif :** ajouter `'fee_lines.*.note' => ['nullable','string']`. **Pur code.**

---

### C-7 — 4,71 M MAD de paiements non-espèces logés dans une caisse physique

Le vérificateur du projet (`caisse:verifier-coherence`) remonte **5 453 anomalies** :

| Méthode | Lignes | Montant |
|---|---:|---:|
| TPE | 3 218 | 3 297 109,00 MAD |
| Virement | 1 362 | 1 188 120,00 MAD |
| Chèque | 188 | 222 600,00 MAD |
| **Total** | **4 768** | **4 707 829,00 MAD** |

Ces montants sont dans la caisse **physique** n°1 (solde 22 135 401 MAD) au lieu du compte de méthode du centre (§11).

⚠ **Ne PAS réparer à l'aveugle.** `caisse:recalculer-soldes` **refuse déjà de tourner** :
> *« 4155 ambiguous row(s) (student centre ≠ till centre) — nothing applied. »*

Un arbitrage explicite est requis (`--ambiguous=caisse` **ou** `--ambiguous=student`).
**Réparation de données : dry-run à lire intégralement + dump PostgreSQL préalable.**

---

## 2. HAUTE

### H-1 — Effacer un filtre de date **retire** des lignes (4 listes financières)

**Fichiers :** `GetCaisseJournal.php:81-83` · `GetDepensesList.php:95-98` · `GetRemboursementsList.php:102-105` · `GetChequesList.php:72-77`

La fenêtre-année s'arme sur `$dateFrom === '' && $dateTo === ''` : elle **se réarme dès qu'on efface le dernier champ**. Viole §5 (« effacer un filtre ne doit QUE élargir »). Même forme que le bug corrigé le 30/08/2026, **reproduite dans 4 fichiers**.

**Preuve mesurée (année active 2026/2027) :**

| Écran | Avec « Du = 01/09/2025 » | Après effacement | Effet |
|---|---:|---:|---|
| **Journal de caisse** | 26 035 | **0** | **−26 035** |
| Remboursements | 1 | 0 | −1 |
| Dépenses | 0 | 0 | *(table vide)* |
| Chèques | 0 | 166 | **sain** (`orWhereNull`) |

**Nuance :** sur **Chèques le bug ne mord pas** (166 échéances NULL rattrapées par `orWhereNull`). Le cas grave est le **Journal de caisse**.
**Correctif :** armer la fenêtre **par borne**, pas par paire. **Pur code.**

### H-2 — `employees.categorie` trop court pour une de ses propres valeurs

`Employee.php:74` définit `'Directeur Qualité et Amélioration continue'` (**44 caractères**) ; la colonne est `varchar(30)`.
**Preuve (INSERT réel exécuté puis annulé) :** `SQLSTATE[22001] value too long for type character varying(30)`.
PostgreSQL ne tronque jamais → **erreur 500** dès que ce poste est choisi. **0 ligne concernée** aujourd'hui (jamais utilisé) : latent mais certain.
**Correctif :** `varchar(60)`. Métadonnée seule, mais §17 : éditer la migration `create_` **ET** appliquer à la main `ALTER TABLE employees ALTER COLUMN categorie TYPE varchar(60);` **ET** l'ajouter à `docs/production-schema-patches.sql`.

### H-3 — Le tableau de bord **sous-compte les employés dans 6 centres sur 7**

`GetDashboardStats.php:44` filtre la colonne primaire, alors que §16 désigne le **pivot** comme source de vérité (ce que `GetEmployeesList.php:57-61` fait correctement).

| Centre | Dashboard | Liste Employés | Écart |
|---|---:|---:|---:|
| GLS Marrakech | 22 | 22 | — |
| GLS Rabat | 15 | 18 | **−3** |
| GLS Casablanca | 9 | 12 | **−3** |
| GLS Kénitra | 9 | 11 | **−2** |
| GLS Agadir | 9 | 13 | **−4** |
| GLS Salé | 14 | 17 | **−3** |
| GLS Online | 13 | 18 | **−5** |

Deux écrans du même produit donnent deux chiffres pour la même question. **Correctif :** `orWhereHas('etablissements', …)`. **Pur code.**

### H-4 — 7 700 MAD d'argent fantôme dans la caisse n°1

Reconciliation caisse par caisse : la caisse 92 tombe **juste au centime** (ce qui valide la méthode), mais la **caisse 1 présente +7 700,00 MAD**.

**Cause tracée :** 7 encaissements de 1 100 MAD (**ENC-26953 → ENC-26959**) **crédités puis supprimés physiquement** sans débit compensatoire. Journal : créés le **30/08/2026 18:07:24**, supprimés le **30/08/2026 18:11:08**, **sans causer** (console/tinker, pas l'interface).

**Le code applicatif est innocent :** `SupprimerEncaissement.php:54-68` débite bien via `CaisseLedger` avant suppression, et **aucun chemin de code actuel** ne supprime d'encaissements en masse (vérifié). Incident **ponctuel** de nettoyage manuel.
**Correctif :** écriture d'ajustement de −7 700 MAD **via `CaisseLedger`** (jamais un UPDATE direct). **Réparation de données, validation direction financière.**

### H-5 — Une avance « détachée » ne peut pas être remboursée

`GetStudentPaymentsForRefund.php:53` — `->whereNull('applied_from_encaissement_id')`.
Une ligne d'application reconvertie garde son lien `applied_from` avec `inscription_fee_id = NULL` : `isAvance()` vaut **true**, l'action d'écriture sait la traiter (`EnregistrerRemboursement:56`) — **mais le sélecteur ne la propose pas**. L'argent dû devient **inatteignable**. Contredit la règle « une ligne d'application détachée EST une avance » (respectée, elle, par `GetEncaissementsList:176`).
**Correctif :** n'exclure que les lignes encore rattachées à un frais. **Pur code, aucun solde faux.**

### H-6 — Le journal d'audit laisse fuiter le compte mainteneur

`GetActivityLogList.php:571,610` filtrent sur `DEVELOPER_EMAIL` (**une** adresse) alors que `HiddenAccount::emails()` en compte **deux**. Vérifié en base : le compte du domaine GLS (user 4) **apparaît dans le dropdown des auteurs**, alors que les listes Employés/Utilisateurs le masquent correctement.
§11 interdit explicitement de coder une adresse à la main ; `DEVELOPER_EMAIL` doit rester **singulier** (sens du bouton « Inclure le compte technique »), donc le correctif va dans **les deux filtres d'affichage**. **Pur code.**

### H-7 — Page de détail du journal : lecture hors périmètre (IDOR)

`index()` transmet `causerScope()`, **`find()` n'a pas ce paramètre** : un lecteur limité à un centre peut lire **n'importe quelle entrée par son id**. **Pur code.**

### H-8 — Le mapping des opérateurs d'import n'est pas contrôlé par centre

L'endpoint de prévisualisation restreint bien le mapping, **`analyze()` non**. Un caissier d'un autre centre atteint `CaisseResolver::tillOf()` → **l'argent atterrit dans la caisse d'un autre centre**, corrigeable seulement par transfert compensatoire.
**Connexe :** la liste des batches d'import n'est pas restreinte (les **7 centres** apparaissent), et le masquage de la colonne Centre par `centerLocked` **cache visuellement** la fuite pendant que les lignes étrangères occupent les places.

### H-9 — Séances : `valider` / `annuler` sans garde de transition

Écritures inconditionnelles : une séance **Annulée** peut passer **Effectuée** en gardant son `motif_annulation`, et une séance annulée **accepte encore un appel** (`savePresences` vérifie la permission et le contexte, **jamais le statut**). **Pur code.**

### H-10 — `exists:` non restreint sur des FK inter-centres

`livre_ids` (déplace le stock d'un autre centre via un `findOrFail` nu), `enseignant_id` sur groupes/séances/créneaux (alimente la paie « Paiement prof »), ids de fusion d'étudiants. **Les dropdowns sont restreints, les chemins d'écriture non.** **Pur code.**

### H-11 — `SupprimerGroupe` orpheline les livres attribués

Cascade sur `inscription_livres` **sans mouvement « Entrée »** → le stock reste **durablement sous-évalué**. `InscriptionController@destroy` avait été corrigé pour exactement ce cas ; **le chemin groupe ne l'a pas été**.
**Correctif pur code**, mais les groupes déjà supprimés ont laissé un écart : **réconciliation en lecture seule**, jamais une correction automatique.

### H-12 — Escalade de privilèges (étroite mais réelle)

Le rôle `director` détient `users.assign-roles` **sans** `roles.*` ; l'écran d'autorisation valide les noms de rôle **par simple existence** → un directeur peut attribuer `marketing-manager` et conférer **5 permissions stock qu'il ne détient pas**. **Pur code.**

### H-13 — 13 clés étrangères sans index (PostgreSQL n'indexe pas le côté référençant)

| Table | Colonnes FK non indexées |
|---|---|
| `cheques` | `agent_id`, `etablissement_id`, `retourne_par_id` |
| `import_batches` | `etablissement_id`, `annee_scolaire_id`, `created_by` |
| `inscription_livres` | `stock_article_id`, `assigned_by` |
| `inscriptions_historique` | `inscription_id`, `new_inscription_id`, `student_id`, `group_id`, `archived_by` |

`inscriptions_historique` ne déclare **aucun index** alors que `inscription_id` est sa clé de lecture. La liste Chèques filtre `etablissement_id` **sans index**.
**Correctif :** `CREATE INDEX CONCURRENTLY` (hors migration standard) + ajout aux migrations `create_` et au fichier de patches. **Aucune donnée modifiée.**

---

## 3. MOYENNE

| # | Anomalie | Fichier | Risque |
|---|---|---|---|
| M-1 | **27 redirections qui perdent les filtres** dans 7 contrôleurs non-financiers. La règle §5 (née d'une plainte caissier réelle) n'a été appliquée qu'aux 2 contrôleurs financiers, alors que `RedirectsPreservingFilters` est générique. | 7 contrôleurs | Pur code |
| M-2 | **6 catalogues sans index unique en base** derrière leur `Rule::unique` → course *check-then-insert*. Les 7 index proposés **s'appliqueraient proprement aujourd'hui** (vérifié : `salles` a des noms dupliqués mais **0** doublon `(etablissement_id, nom)`). | migrations | DDL, sans perte |
| M-3 | **Le plus gros batch d'import fait 65 908 lignes** contre la limite de **65 535 paramètres** de PostgreSQL → **son re-commit échoue déjà**. | Import | Pur code (chunking) |
| M-4 | 3 modèles avec défaut SQL non reflété dans `$attributes` → journal pouvant afficher un **faux « avant »** : `Depense.statut`, `CaisseTransfer.statut`, `GroupEnseignant.statut` (les 2 premiers sont **monétaires**). *(Correction : `Seance` a été écarté — ses 3 appelants passent `statut` explicitement, donc gap de convention latent, pas bug actif.)* | `app/Models/*` | Pur code |
| M-5 | `DormantTill::hide():54` — `whereHas('responsable')` **sans `withoutGlobalScopes()`** : 3ᵉ occurrence du piège §11. Ici il **sur-montre**. Neutralisé aujourd'hui car les 3 appelants passent `hideCaisses()` avant. | `DormantTill.php` | Pur code |
| M-6 | Compte mainteneur non filtré : `GetDepensesList` (**0** occurrence de `HiddenAccount`) et `GetRemboursementsList` (dropdown filtré ligne 194, **lignes et totaux non**). Latent (dépenses = 0). | 2 read-models | Pur code |
| M-7 | Groupe créé sous « Tous les centres » → `etablissement_id = NULL` → visible dans **tous** les centres, propagé aux inscriptions. **0 ligne aujourd'hui.** | `GroupController.php:190` | Pur code |
| M-8 | 6 read-models cassent si `etablissementId()` est NULL sans `isAllCenters()` → `WHERE = NULL` = 0 ligne. `GetEmployeesList:66` se protège, pas les autres. **0 user sans employee aujourd'hui.** | 6 fichiers | Pur code |
| M-9 | Validation d'un transfert **hors verrou d'année clôturée** (`store`/`update` l'appliquent, `validateAction():181` non). ⚠ Ajouter le verrou **bloquerait les transferts en attente** à la clôture — **arbitrage requis**. | `CaisseTransferController` | Décision |
| M-10 | Mutations Stock/Salles/Frais/Établissements/Motifs **sans verrou d'année**, alors que `storeMouvement:243` l'applique → deux chemins du **même écran** divergent. | 5 contrôleurs | Décision |
| M-11 | Paramètres → Établissements et Frais exposent **tout le réseau** (`ice`, `adresse`, `telephone`, tarifs) : l'écriture est restreinte, **pas la lecture**. | 2 read-models | Pur code |
| M-12 | Réaffectation : `group_id` validé en existence seule, **sans contrôle de centre** (cross-année volontaire, cross-centre non). L'action Domain vérifie → défense en profondeur. Super-admin seul. | `EncaissementReallocationController:83` | Pur code |
| M-13 | « Caisse globale » : colonne Centre **en dur** + `colSpan={3}` figé ; seul panneau non alimenté en `centerLocked` (pourtant déjà calculé `CaisseController:114`). | `GlobalePanel.tsx:120,137` | Pur code |
| M-14 | **N+1** : `Cheque::montantRestant()` appelé **par ligne** alors que la relation est déjà eager-loadée (viole §17). | `GetChequesList.php:100` | Perf |
| M-15 | **N+1** : `InscriptionFee::montantPaye()` par ligne à chaque ouverture du modal. | `InscriptionController:181,281` | Perf |
| M-16 | Mouvements de stock : colonne « Par » affiche `—` pour le compte masqué. Signature de l'incident du 30/08. | `GetStockMouvementsList:136` | Libellé de repli. **Ne jamais backfiller `created_by`.** |

---

## 4. BASSE

- **B-1** `'audit-logs.view' => 'Consulter 9wiwid'` (`PermissionRegistry:339`) — le slug volontairement obscurci **fuite dans un libellé visible** sur 3 écrans.
- **B-2** i18n : une seule clé réellement manquante, `t('Nom technique')` (`Permissions/Index.tsx:47`) — chaîne française utilisée comme clé alors que `"Machine name"` existe déjà. 6 clés backend StudentMerge s'affichent en anglais.
- **B-3** `GetRetardsList::fraisOptions():224` filtre `statut = ACTIF` → un frais archivé devient **infiltrable** ; contredit `GetEncaissementsList::fraisOptions()`.
- **B-4** `GetUsersList` s'appuie sur le contexte sans filet `CenterAccessService` (sûr uniquement parce que `setEtablissement(null)` est refusé aux non-super-admins).
- **B-5** `Caisse::$fillable` contient `solde` — non exploitable, mais seule colonne monétaire *fillable* : protégée par convention, pas par garde.
- **B-6** Dropdown groupe « Paiement prof » se vide au changement d'année — **correct**, mais indiscernable d'un contrôle cassé. Ajouter un libellé, **ne pas élargir la requête**.
- **B-7** `GetReallocatablePayments:39` filtre `encaissements.etablissement_id` alors que la règle est « le centre d'un paiement est celui de l'étudiant ». *0 ligne divergente — latent.*
- **B-8** `GetCaissesList.php` est du **code mort** contenant justement l'`etablissementOptions()` correctement filtré dont **C-2** a besoin.
- **B-9** 4 995 étudiants ont `niveau` NULL — **légitime** (colonne nullable, imports legacy). Consigné pour éviter une requalification en bug.

---

## 5. Qualité des données de production

### D-1 — 4 dates de paiement aberrantes (faute de frappe à l'import)

| id | référence | date stockée | montant | lecture probable |
|---:|---|---|---:|---|
| 3950 | ENC-3950 | **0205-09-25** | 300,00 | 2025-09-25 |
| 22410 | ENC-22410 | **0020-03-27** | 200,00 | 2020-03-27 |
| 22411 | ENC-22411 | **0026-12-10** | 1 000,00 | 2026-12-10 |
| 22412 | ENC-22412 | **0225-09-10** | 1 000,00 | 2025-09-10 |

Ces lignes faussent tout `min(date)`, tout axe temporel et toute fenêtre par défaut.
**Également :** 13 `students.date_naissance` et 2 `inscriptions.date_inscription` hors plage [1900-2035].
**Réparation de données** (2 500 MAD au total — les **montants sont justes**, seule la date est fausse).

### D-2 — 6 972 paiements hors de la fenêtre de leur année → **ce n'est PAS un bug**

| Année de l'inscription | Année de la date | Lignes | Montant |
|---|---|---:|---:|
| 2025/2026 | 2025/2026 | 16 920 | 16 830 645 MAD |
| **2026/2027** | **2025/2026** | **6 264** | **4 953 364 MAD** |
| 2025/2026 | hors année | 667 | 290 500 MAD |
| 2026/2027 | hors année | 41 | 12 900 MAD |

**Interprétation métier : normal.** Un étudiant paie en juin 2026 son inscription 2026/2027. C'est précisément pourquoi la règle « l'année d'un paiement vient de son **inscription**, jamais de `date_paiement` » existe — et `GetEncaissementsList` comme `GetDashboardStats` l'appliquent correctement (vérifié).

> ⚠ **Piège n°1 du domaine :** tout nouvel écran qui fenêtrerait les paiements **par date** classerait **4,95 M MAD** dans la mauvaise année.

### D-3 — Contexte à connaître avant de tester 2025/2026

2025/2026 ne contient plus qu'**1 inscription Active** sur 3 938 (1 882 Annulées, 2 055 Changement). Une vue quasi vide en basculant sur l'année précédente est donc **attendue**, pas un bug.
Le filtre « inscriptions Actives seulement » du recouvrement est bien présent dans **les deux** chemins de requête (`GetRetardsList:91` et `:176`) — il protège contre **110 M MAD** de dettes sur dossiers clos.

---

## 5 bis. Test déjà rouge AVANT toute correction (découvert le 07/09/2026)

`Tests\Feature\Backoffice\Audit\P0RemediationTest::test_a_changement_registration_cannot_be_reactivated`
**échoue sur le code d'origine** — vérifié en remisant (`git stash`) la
totalité des correctifs puis en relançant le test seul : échec identique
(« Session is missing expected key [errors] »).

Ce que le test attend : une inscription au statut **Changement** ne doit pas
pouvoir repasser **Active** via `inscriptions.update-statut`. Aujourd'hui la
requête passe **sans erreur de validation** — le garde-fou est absent ou
inopérant.

C'est le pendant, côté écriture, du filtre « inscriptions Actives seulement »
du recouvrement (§ D-3) : ressusciter un dossier clos le remet dans les
retards et dans le dû. **Non corrigé ici** — c'est une règle de transition de
statut (même famille que H-9), qui doit être arbitrée avec la règle métier,
pas devinée.

---

## 6. Plan d'action recommandé

### Étape 1 — Corriger maintenant (pur code, aucun risque production)

1. **C-1(a)** accepter `'A1'` → débloque 8 classes actives *(ou trancher pour le remapping)*
2. **C-2** router les 3 dropdowns via `GetAccessibleCenterOptions`
3. **C-3** refuser au lieu de substituer *(à faire **avec** C-2)*
4. **C-4** resynchroniser l'état des présences
5. **C-5** purger le debounce · **C-6** ajouter la règle `note`
6. **H-1** armer les fenêtres de date **par borne**
7. **H-3** dashboard employés → pivot · **H-5** avance détachée remboursable
8. **H-6 / H-7** filtres du journal d'audit · **H-9** gardes de transition séances
9. **H-10 / H-11 / H-12** portée des `exists:`, livres, escalade de rôle
10. **M-4 / M-5 / M-6 / M-13 / M-14 / M-15**

### Étape 2 — Décisions métier (ne pas coder avant arbitrage)

- **C-7** sens de la réparation caisse (`--ambiguous=caisse` vs `student`) — **4,7 M MAD**
- **H-4** écriture d'ajustement **−7 700 MAD** via `CaisseLedger`
- **M-9 / M-10** portée du verrou d'année clôturée
- **D-1** correction des 4 dates aberrantes
- **C-3 (volet données)** audit des `employee_etablissement` **via le journal**, sans re-dérivation
- **H-11 (volet données)** réconciliation du stock sous-évalué, **en lecture seule**

### Étape 3 — Schéma (fenêtre de maintenance)

- **H-2** `categorie` → `varchar(60)` + patch manuel
- **H-13** 13 index FK en `CREATE INDEX CONCURRENTLY`
- **M-2** 7 index uniques de catalogue

### Protocole obligatoire pour chaque correctif

1. `C:\php84\php.exe artisan test` vert · `npx tsc --noEmit` · `npm run build`
2. Tout changement de schéma : éditer la migration `create_` **ET** appliquer le SQL idempotent à `gls_crm` **ET** l'ajouter à `docs/production-schema-patches.sql` — *§17 : éditer une migration ne change **aucune** base existante*
3. Avant/après toute réparation monétaire : `caisse:verifier-coherence`, dry-run lu **intégralement**, **dump PostgreSQL préalable**
4. Prouver chaque correctif **par une requête sur les données réelles**, pas en relisant le code

---

## 7. Couverture de l'audit

**Modules (30) :** Students · StudentMerge · Inscriptions · Groups · GroupHistorique · Seances · Creneaux · Employees · Users · Roles · Permissions · Profile · Etablissements · AnneesScolaires · Salles · Frais · MotifAnnulation · TypeDepense · StockType · Stock · Settings · SystemSettings · AuditLog · Import · Dashboard · Encaissements · Depenses · Remboursements · Cheques · Caisse/Transfers · Recouvrement · Rapports

**Modèles (34/34) :** tous, plus `Concerns/Auditable` et `Scopes/HiddenAccountScope`.
Registre d'audit : **32 modèles audités ↔ 32 entrées** — **aucune dérive**.
Conformité des données vérifiée pour **18 colonnes** statut/type/méthode contre leurs constantes → **seul `groups.niveau` dévie**.

**Ce qui n'a PAS été fait :** la suite de tests complète **n'a pas produit de résultat exploitable** pendant cet audit (le premier lancement n'a rien écrit en sortie). **Aucune ligne de baseline verte n'est donc affirmée ici.** Les constats de ce rapport ne dépendent pas des tests : ils reposent sur la lecture du code et sur des **requêtes exécutées contre `gls_crm`**. Lancer `artisan test` reste le premier gate avant tout correctif (§6).

**Note de méthode :** deux affirmations d'auditeurs ont été **corrigées après vérification** — le bug « filtre effacé » ne mord **pas** sur Chèques (protégé par `orWhereNull`), et `Seance` n'a **pas** de bug actif de `$attributes` (ses appelants passent `statut` explicitement). Elles figurent ici sous leur forme corrigée.
