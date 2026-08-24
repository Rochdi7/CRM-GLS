# Audit financier complet — 24/08/2026

Audit du domaine finance (caisses, encaissements, dépenses, remboursements,
transferts, chèques, import, reporting) réalisé sur le code réel du dépôt,
après la refonte « un dirham = une ligne `caisses` »
(`docs/caisse-comptes-methode-architecture.md`). Ce document est le rapport
de référence ; la suite de non-régression est
`tests/Feature/Backoffice/Finance/FinancialInvariantsAuditTest.php`.

## A. Résumé

- **Périmètre audité** : 4 actions Domain paiements, 3 actions dépenses,
  3 actions finance, `CaisseLedger`, `CaisseResolver`, `CaisseProvisioner`,
  2 observers, 5 policies, 11 Form Requests, 6 contrôleurs, 12 read-models
  (listes, journal, comptes, globale, dashboard, résumé annuel, retards),
  l'importeur legacy, les 2 commandes artisan, les 8 migrations finance, les
  pages React (Caisses, Dépenses, Encaissements, Chèques, Comptes, Globale),
  et 199 tests existants.
- **Bugs confirmés : 9** (1 critique, 6 élevés, 2 moyens) — **9 corrigés**.
- **Nouveaux garde-fous** : migration `harden_caisses_integrity`
  (`solde NOT NULL`, une caisse « Caissière » par employé), commande
  lecture seule `caisse:verifier-coherence`, relation `Employee::till()`.
- **13 nouveaux tests**, suite complète verte (voir §H).
- **Points non tranchés** : voir §J (aucun n'a été modifié silencieusement).

## B. Bugs confirmés

| Sévérité | Bug | Fichier | Impact | Correction |
|---|---|---|---|---|
| **CRITIQUE** | `caisse:recalculer-soldes` re-homait les **dépenses** approuvées non-espèces de la caisse physique vers le compte TPE/Chèque/Virement du centre (crédit de la caisse, débit du compte de méthode, `caisse_id` déplacé). Contredit l'invariant 6 (une dépense se règle toujours depuis la caisse physique) : après `--apply` la caisse affichait un solde faux de la valeur de chaque dépense « Virement ». | `app/Console/Commands/RecalculerSoldesCaisses.php` | Soldes de caisse physique gonflés, comptes de méthode débités à tort, dépenses dont `caisse_id` pointe sur un compte sans responsable. | Branche dépenses supprimée ; garde `LogicException` si autre table ; docblock + doc §5 corrigés. Test `test_recalculer_soldes_never_moves_a_depense_out_of_the_till`. |
| ÉLEVÉ | `CaisseResolver::tillOf()`, `CaisseProvisioner::provisionFor()`, `CaisseTransferController@store`, `CaisseController@index` (`myCaisse`), `DepenseController@index` (`soldeActuel`) utilisaient `caisses()->first()` : sans filtre de type ni tri. Un employé désigné responsable d'un compte « Externe » (autorisé par `UpdateCaisseRequest`) voyait ses espèces, dépenses, remboursements et transferts routés vers le coffre au lieu de sa caisse ; deux caisses « Caissière » (données pré-provisionneur) étaient choisies au hasard. | 5 fichiers | Argent physique attribué au mauvais compte ; solde de caisse faux ; source de transfert erronée. | Relation `Employee::till()` (type Caissière, tri par id) ; tous les appels basculés ; le provisionneur teste `till()` et non « une caisse quelconque ». Test `test_an_externe_safe_assigned_to_the_cashier_never_receives_or_pays_their_cash` + `…_provisioner_still_creates_the_till…`. |
| ÉLEVÉ | Aucune contrainte « une caisse physique par employé » : `exists()` puis `create()` non atomique (observer + première visite « Ma caisse », commande rétro, double requête). | `database/migrations/2026_08_24_230000_harden_caisses_integrity.php`, `CaisseProvisioner` | Argent d'un employé réparti sur deux caisses. | Index unique partiel PostgreSQL `caisses_une_caissiere_par_employe` ; `provisionFor()` attrape la violation. Test `test_the_database_refuses_a_second_physical_till_for_the_same_employee`. |
| ÉLEVÉ | `CaisseProvisioner::compteMethodeFor()` attrapait la violation d'unicité **hors savepoint** : dans une transaction externe (celle d'`EncaissementController@store`), PostgreSQL abandonne la transaction et la relecture du gagnant échoue (« current transaction is aborted »). | `app/Services/CaisseProvisioner.php` | Paiement refusé avec erreur SQL lors d'une course de provisioning. | `Caisse::create()` dans un `DB::transaction` imbriqué (savepoint). Test `test_method_account_provisioning_survives_a_lost_race_inside_an_outer_transaction`. |
| ÉLEVÉ | `CaisseController@update` acceptait l'édition d'un compte de méthode (renommage, désactivation, `responsable_employee_id` → CHECK PostgreSQL → 500) et la **réattribution** de la caisse « Caissière » d'un employé à un autre (l'employé initial devient sans caisse, le provisionneur lui en crée une seconde vide, son historique part chez l'autre). L'UI masquait l'action ; le serveur ne la refusait pas. | `app/Http/Controllers/Backoffice/CaisseController.php` | Perte de traçabilité, comptes de méthode altérés. | Refus explicite (ValidationException) pour un compte de méthode ; `responsable_employee_id` gelé sur une Caissière (un « Externe » peut changer de mains). Test `test_a_method_account_cannot_be_edited_and_an_employee_till_cannot_change_hands`. |
| ÉLEVÉ | `EncaissementController@storeAvance` ne vérifiait pas le centre de l'étudiant (contrairement à `store()` qui vérifie l'inscription). | `EncaissementController.php` | Un caissier de A pouvait enregistrer une avance pour un étudiant de B (IDOR par `student_id`). | `assertCenterAccess()` sur le centre de l'étudiant → 403. Test `test_an_avance_for_a_student_of_another_centre_is_refused`. |
| ÉLEVÉ | `RemboursementController@store` ne vérifiait pas le centre du bénéficiaire (seule la lookup `studentPayments` le faisait). | `RemboursementController.php` | Remboursement à un étudiant d'un autre centre depuis sa caisse. | `assertCenterAccess()` sur le bénéficiaire → 403. Test `test_a_refund_to_a_student_of_another_centre_is_refused`. |
| MOYEN | `GetAnnualFraisSummary` série « encaissements » scopée par la caisse, alors que la liste, la policy et la carte du dashboard scopent par l'**étudiant**. Un opérateur multi-centres encaissant en espèces pour le centre B voyait l'argent attribué au centre A (où vit sa caisse) sur le graphique, mais à B sur la carte. | `app/Domain/Reports/Actions/GetAnnualFraisSummary.php` | Graphique et carte du même mois en désaccord. | Scope via `students.etablissement_id`. Test `test_the_annual_chart_attributes_a_payment_to_the_students_centre_like_the_dashboard`. |
| MOYEN | `caisses.solde` nullable : un NULL est lu `0` en silence et le prochain mouvement journalise un « avant » faux. | migration | Trace d'audit fausse. | `UPDATE … SET solde = 0 WHERE NULL` puis `SET NOT NULL`. Test `test_a_balance_can_no_longer_be_null`. |
| BAS | Formulaire Dépense React transportait un champ `caisse_id` jamais affiché ni accepté côté serveur (champ caché soumis). | `resources/js/Pages/Backoffice/Depenses/Index.tsx` | Aucun (ignoré serveur), bruit. | Supprimé de l'état du formulaire. |

## C. Fichiers modifiés

| Fichier | Pourquoi |
|---|---|
| `app/Models/Employee.php` | `till()` : la caisse physique (Caissière, tri id). |
| `app/Services/CaisseProvisioner.php` | `provisionFor()` sur `till()`, savepoints + rattrapage d'unicité sur les deux créations. |
| `app/Domain/Finance/Support/CaisseResolver.php` | `tillOf()` via `till()`. |
| `app/Http/Controllers/Backoffice/CaisseTransferController.php` | Source du transfert = `CaisseResolver::tillOf()`. |
| `app/Http/Controllers/Backoffice/CaisseController.php` | `myCaisse` via `till()` ; refus d'éditer un compte de méthode ; responsable gelé sur une Caissière. |
| `app/Http/Controllers/Backoffice/DepenseController.php` | `soldeActuel` via `till()`. |
| `app/Http/Controllers/Backoffice/EncaissementController.php` | Contrôle de centre sur `storeAvance`. |
| `app/Http/Controllers/Backoffice/RemboursementController.php` | Contrôle de centre sur le bénéficiaire. |
| `app/Console/Commands/RecalculerSoldesCaisses.php` | Encaissements uniquement. |
| `app/Console/Commands/VerifierCoherenceCaisses.php` | **Nouveau** — auditeur lecture seule. |
| `app/Domain/Reports/Actions/GetAnnualFraisSummary.php` | Scope encaissements par étudiant. |
| `database/migrations/2026_08_24_230000_harden_caisses_integrity.php` | **Nouveau** — voir §D. |
| `resources/js/Pages/Backoffice/Depenses/Index.tsx` | Champ `caisse_id` fantôme retiré. |
| `lang/fr.json` | 2 messages. |
| `CLAUDE.md`, `docs/caisse-comptes-methode-architecture.md` | Règles mises à jour (commande = encaissements seuls ; `till()` ; auditeur). |
| `tests/Feature/Backoffice/Finance/FinancialInvariantsAuditTest.php` | **Nouveau** — 13 tests. |

## D. Base de données

Migration `2026_08_24_230000_harden_caisses_integrity` (PostgreSQL, `migrate --force`) :

1. `UPDATE caisses SET solde = 0 WHERE solde IS NULL` puis
   `ALTER COLUMN solde SET NOT NULL` (+ `DEFAULT 0`). Sûr sur données
   existantes.
2. `CREATE UNIQUE INDEX caisses_une_caissiere_par_employe ON caisses
   (responsable_employee_id) WHERE type = 'Caissière' AND
   responsable_employee_id IS NOT NULL`. **Avant de le créer**, la migration
   compte les doublons existants et **s'arrête avec un message listant les
   employés concernés** plutôt que de deviner quelle caisse garder (fusionner
   deux comptes d'argent est une décision métier). `down()` retire l'index et
   la contrainte NOT NULL. Aucune ligne n'est supprimée.

Contraintes déjà en place et vérifiées : index unique partiel
`(etablissement_id, type)` pour TPE/Chèque/Virement ; CHECK « compte de
méthode ⇒ centre NOT NULL et responsable NULL » ; FK `restrictOnDelete` sur
`caisse_id` des 4 tables d'argent (un compte porteur de mouvements est
insupprimable ; `CaisseController@destroy` refuse aussi tout non-Externe).

## E. Vérification des flux

Chaque ligne est couverte par un test HTTP de bout en bout (contrôleur →
Request → action → ledger → journal d'audit → base) :

| Flux | Compte touché | Test |
|---|---|---|
| Espèces | caisse Caissière de l'agent, rien d'autre | `ComptesMethodeTest::test_a_cash_payment_credits_the_cashiers_till_only` |
| TPE / Chèque / Virement | compte du centre **actif** pour cette méthode ; caisse physique inchangée ; autres comptes inchangés | `…::test_a_non_cash_payment_credits_only_the_centres_account_for_that_method` (×3) |
| Casablanca TPE ⟂ Rabat TPE ⟂ Casablanca espèces | isolation | `…::test_a_non_cash_payment_goes_to_the_active_centre_and_never_to_another_centres_account`, `FinancialInvariantsAuditTest::test_a_single_centre_cashier_cannot_credit_another_centres_method_account_through_the_session` |
| Avance TPE puis application | +1000 une fois, `caisse_id` hérité | `…::test_a_non_cash_avance_credits_the_method_account_and_applying_it_credits_nothing_twice` |
| Annulation (destroy) | inverse le compte **stocké** même si `methode` est altéré | `…::test_cancelling_a_non_cash_payment_reverses_the_same_account_even_if_the_method_is_tampered` |
| Dépense (toute méthode) | caisse physique, une seule fois (ON : à l'approbation ; OFF : à la création) | `…::test_an_expense_always_debits_the_cashiers_till_whatever_its_label`, `DepenseOperationTrailTest`, `DepensesInertiaCrudTest` |
| Remboursement | caisse physique (exception : chèque rejeté → compte Chèque) | `…::test_a_refund_always_debits_the_cashiers_till`, `…rejected_cheque…`, `…not_rejected…` |
| Transfert | espèces ↔ espèces seulement ; destinataire valide ; double validation refusée | `…::test_a_transfer_towards_a_method_account_is_refused…`, `CaisseTransfersInertiaCrudTest` (×24) |
| Coffre Externe attribué à l'agent | jamais utilisé comme caisse physique | `FinancialInvariantsAuditTest::test_an_externe_safe_assigned_to_the_cashier_never_receives_or_pays_their_cash` |

## F. Sécurité

- **`caisse_id` client** : aucune Request finance ne l'accepte ; tests
  `test_a_remboursement_can_be_created_with_no_caisse_id_in_the_payload`,
  `test_a_tampered_caisse_source_id_is_ignored`, `test_montant_and_caisse_are_frozen_on_update_even_when_tampered`.
- **`methode`** : gelée après création (`test_the_method_of_a_recorded_payment_is_frozen`).
- **`montant` / `agent_id` / `reference`** : absents des Update Requests ;
  générés serveur.
- **Isolation centre** : `CenterAccessService` dans chaque policy
  (`ResourcePolicy::withinCenter`), dans les listes (`scopeAccessibleCenters`),
  dans `AccessibleCaisse` (destination de transfert), dans les lookups et
  désormais dans `storeAvance` / `remboursements.store`. `CurrentContext`
  ignore une valeur de session hors des centres affectés (test session
  altérée ci-dessus).
- **Mass assignment** : `Caisse::$fillable` contient `solde` pour les
  fixtures uniquement — `store()` force 0, `update()` n'a pas de règle
  `solde`/`type` ; comptes de méthode désormais non éditables.
- **IDOR** : `applyAvance` (policy + étudiant du frais), `convertAvance`
  (inscription), `store` (inscription ↔ étudiant), `storeAvance` (étudiant),
  remboursement (bénéficiaire ↔ paiement).
- **Super-admin** : `Gate::before` exclut `CaisseTransfer@validate`
  (`test_a_super_admin_cannot_validate_someone_elses_transfer`).

## G. Audit

- `CaisseLedger` est l'unique écrivain de `caisses.solde` (7 appelants, tous
  Domain + commande de reclassement) ; aucun `increment/decrement('solde')`,
  aucun `DB::table('caisses')->update`.
- Chaque mouvement journalise `solde_avant`, `montant`, `sens`,
  `solde_apres`, motif, origine (type/id/référence), causer, IP/UA
  (`Activity::creating`).
- **Invariant prouvé** par `test_every_balance_equals_the_sum_of_its_journaled_movements` :
  pour chaque compte après paiement espèces + TPE, dépense, remboursement,
  transfert validé : `solde_avant(n) = solde_apres(n-1)`,
  `solde_apres = solde_avant ± montant`, et `solde = Σ entrées − Σ sorties`.
  `caisse:verifier-coherence --strict` le vérifie sur une base réelle.
- Reversals : `SupprimerEncaissement` débite le `caisse_id` stocké ;
  `RecalculerSoldesCaisses` journalise les deux jambes ; les transferts
  journalisent les deux caisses.

## H. Tests

Voir la section finale du rapport de session (chiffres exacts) — suite
complète exécutée sur la base isolée `gls_crm_audit` ; `npx tsc --noEmit`
et `npm run build` verts ; Pint : aucun nouveau signalement (les
signalements restants — `ordered_imports`, `not_operator_with_successor_space`,
`line_ending` — préexistent dans les mêmes fichiers à HEAD).

## I. Risques résiduels

1. **Migration bloquante si doublons de caisses en production** — voulu
   (voir §D) ; lancer `caisse:verifier-coherence` sur la prod **avant** le
   déploiement pour le savoir à l'avance.
2. `GetCaisseJournal` reste en fusion PHP sans pagination SQL (bottleneck
   connu, hors périmètre).
3. Concurrence : tous les « lire un reste puis écrire » sont sous
   `lockForUpdate()` dans la même transaction (frais, chèque, avance,
   transfert, dépense, caisse). Les tests PHPUnit sont séquentiels ; la
   preuve de sérialisation repose sur les verrous PostgreSQL, pas sur un test
   multi-processus.
4. `cash-accounts.view` accordé à la main à un utilisateur non global expose
   « Comptes de caisse » (non scopé par centre, par conception super-admin).

## J. Décisions métier requises (rien n'a été changé)

1. **Soldes négatifs** : dépenses, remboursements et transferts ne vérifient
   pas le solde disponible (le solde peut devenir négatif ; un transfert
   d'espèces inexistantes est physiquement impossible). L'auditeur les liste
   en avertissement. Faut-il refuser (`montant > solde`) — et pour quelles
   opérations ?
2. **Montant d'un remboursement** non plafonné par le paiement lié
   (`docs/phase-10-finance-mapping.md` Q1, test explicite
   `test_no_maximum_refund_amount_check_exists`).
3. **KPI « Encaissements » de Ma caisse** : inclut TPE/Chèque/Virement
   encaissés par l'agent (décision du 24/08/2026 : « leur travail ») alors que
   le tableau et le « Solde espèces » ne montrent que les espèces ; le
   détail par méthode a été retiré de l'écran dans le diff non commité. Soit
   remettre le détail, soit restreindre le KPI aux espèces.
4. **Caisse « Inactive »** : rien n'empêche un mouvement vers/depuis une
   caisse désactivée.
5. **Changement de centre primaire d'un employé** : sa caisse reste dans
   l'ancien centre (`syncEtablissements` ne la déplace pas, volontairement) —
   ses dépenses/remboursements restent donc scopés sur l'ancien centre.
6. **Recipient d'un autre centre** ne peut pas *annuler* (`update`) un
   transfert en attente vers sa caisse (`CaisseTransferPolicy::update` ne
   regarde que le centre source) alors qu'il peut le valider/voir.
