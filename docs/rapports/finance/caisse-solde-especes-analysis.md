# Encaissements & Caisse — état actuel, problème du « Solde », et solutions

> Document d'analyse rédigé le 24/08/2026 pour discussion externe (ChatGPT).
> **État au moment de l'analyse — depuis corrigé** : voir
> `docs/caisse-comptes-methode-architecture.md` (décisions + livraison). Il décrit le fonctionnement **actuel exact**,
> puis le défaut relevé (« le solde doit être l'espèce uniquement ») et les
> options de correction avec leurs conséquences.

---

## 1. Vue d'ensemble du modèle financier

Le CRM GLS gère l'argent avec **4 flux** et **une seule colonne de solde** :

| Flux | Table | Sens | Action Domain |
|---|---|---|---|
| Encaissement (paiement étudiant) | `encaissements` | + | `EnregistrerEncaissement` |
| Dépense | `depenses` | − | `EnregistrerDepense` / `ApprouverDepense` |
| Remboursement | `remboursements` | − | `EnregistrerRemboursement` |
| Transfert entre caisses | `caisse_transfers` | − source / + destination | `DemanderTransfertCaisse` → `ValiderTransfertCaisse` |

### 1.1 `caisses.solde` — un nombre stocké, pas un calcul

`caisses.solde decimal(12,2)` est **maintenu par l'application** ; il n'existe
pas de table de grand-livre (choix délibéré, `gls-crm-schema.md` §10).

Le solde ne bouge **que** par `App\Domain\Finance\Support\CaisseLedger` :

```php
$ledger->credit($caisseId, $montant, "Encaissement {$ref}", $encaissement, ['methode' => ...]);
$ledger->debit(...);
```

`CaisseLedger::move()` :

1. ouvre une transaction,
2. `lockForUpdate()` sur la ligne `caisses`,
3. lit `solde_avant`, applique le delta, écrit `solde_apres` **via le modèle**
   (jamais `increment()` : cela ne déclenche aucun événement Eloquent, donc
   aucun audit),
4. écrit une entrée `activity('caisse')` avec
   `solde_avant / montant / solde_apres / motif / origine`.

⚠ Règle absolue du projet : `increment('solde')`, `decrement('solde')` ou un
UPDATE brut sur cette colonne sont interdits — c'était le trou d'audit que
`CaisseLedger` a comblé.

### 1.2 Types de caisses

`Caisse::TYPES` ne contient que **2 valeurs stockées** :

- **`Caissière`** — la caisse personnelle d'un employé, créée automatiquement
  par `CaisseProvisioner` (via `EmployeeObserver`), jamais à la main.
  1 employé = 1 caisse.
- **`Externe`** — un coffre / un tiers ; le seul type créable depuis l'écran
  « Comptes de caisse » (super-admin uniquement).

**TPE / Chèque / Virement ne sont PAS des caisses.** Ce sont des *méthodes de
paiement*, agrégées **à la volée** par `GetComptesCaisse::DERIVED_TYPES`
(`derived: true`, pas d'`id`, aucune ligne en base) :

```
solde_dérivé(X) = SUM(encaissements.montant WHERE methode = X)
                − SUM(depenses.montant     WHERE methode_paiement = X)
```

Le docblock de `Caisse` le dit explicitement : les ajouter comme lignes
stockées créerait **une deuxième copie divergente du même argent**.

---

## 2. Le chemin d'un encaissement (code réel)

`EncaissementController@store` → `EnregistrerEncaissement::handle()` :

```php
DB::transaction(function () {
    $encaissement = Encaissement::create([... 'reference' => 'ENC-xxx', 'agent_id' => ...]);

    $this->ledger->credit(                    // <-- LE PROBLÈME EST ICI
        (int) $data['caisse_id'],
        (float) $data['montant'],
        "Encaissement {$encaissement->reference}",
        $encaissement,
        ['methode' => $encaissement->methode, ...],
    );

    if ($encaissement->fee !== null) {
        $this->recalculerStatutFee($encaissement->fee);   // Payé / Payé partiellement / Non payé
    }
});
```

`Encaissement::METHODES = ['Espèces', 'TPE', 'Chèque', 'Virement']`.

**Aucun test sur `methode` n'est fait avant le `credit()`.** Un virement
bancaire crédite la caisse physique de l'employé exactement comme un billet de
100 DH.

Autres particularités du modèle `Encaissement` :

- `inscription_fee_id` nullable ⇒ ligne d'**avance** (argent reçu, non affecté).
- Appliquer une avance à un frais crée une **2ᵉ ligne** portant
  `applied_from_encaissement_id` ; `AppliquerAvance` **ne crédite rien**
  (l'argent a déjà été crédité par l'avance d'origine).
- Les enregistrements d'argent ne sont **jamais supprimés** ; `montant` et
  `caisse_id` ne sont plus modifiables après création.

---

## 3. L'écran « Gestion de la caisse » (capture d'écran)

Route `backoffice.caisses.index`, onglet **Ma caisse**, alimenté par
`App\Domain\Finance\Queries\GetCaisseJournal`.

### 3.1 Les 4 cartes du haut

```php
$ids = caisses de l'employé connecté;

$totalEncaissements  = Encaissement::whereIn('caisse_id', $ids)
                         ->whereNull('applied_from_encaissement_id')->sum('montant');
$totalDepenses       = Depense::whereIn('caisse_id', $ids)
                         ->where('statut', 'Approuvée')->sum('montant');
$totalRemboursements = Remboursement::whereIn('caisse_id', $ids)->sum('montant');
$solde               = Caisse::whereIn('id', $ids)->sum('solde');   // colonne stockée
```

Remarques importantes :

- Les 4 chiffres d'en-tête sont **tous temps** (non filtrés par année) — c'est
  volontaire : ils doivent réconcilier avec `caisses.solde`, qui traverse les
  années. Seules les **lignes** du journal suivent la fenêtre de l'année active
  (`CurrentContext::anneeDateRange()`).
- Le journal n'affiche que ce qui a **réellement bougé la caisse** : ni les
  lignes « apply » d'avance, ni les dépenses en attente/refusées, ni les
  transferts non validés.

### 3.2 Ce que montre la capture

| Carte | Valeur | Origine |
|---|---|---|
| Encaissments | 2 600,00 DH | somme des 2 lignes ENC-001 + ENC-002 |
| Dépenses | 0,00 DH | aucune dépense approuvée |
| Remboursements | 0,00 DH | aucun |
| **Solde** | **2 600,00 DH** | `caisses.solde`, crédité par `CaisseLedger` |

Si ENC-001 était un **virement** et ENC-002 un **chèque**, la caisse physique
contient en réalité **0 DH**, alors que l'écran annonce 2 600 DH. C'est
exactement le bug signalé.

---

## 4. Le problème, formulé précisément

> **`caisses.solde` mélange l'argent physique (Espèces) et l'argent scriptural
> (TPE / Chèque / Virement).**

Conséquences concrètes :

1. **Impossible de faire un comptage de caisse.** La caissière compte les
   billets, l'écran affiche un autre nombre. Aucun contrôle possible.
2. **Le transfert de caisse est faussé.** `ValiderTransfertCaisse` déplace du
   `solde` — or on ne transfère physiquement que des espèces. Une caisse peut
   « transférer » 5 000 DH qu'elle n'a jamais eus en billets.
3. **Double comptage dans « Comptes de caisse ».** Un chèque de 1 000 DH
   apparaît à la fois :
   - dans la ligne dérivée `Chèque` (1 000 DH), **et**
   - dans le solde de la caisse `Caissière` (1 000 DH).

   Le total global de l'écran compte donc 2 000 DH pour 1 000 DH réels.
   Le docblock de `GetComptesCaisse` documente ce risque pour Espèces
   (« exclue volontairement pour ne pas double-compter ») — mais le
   raisonnement inverse n'a jamais été appliqué : la caisse, elle, compte bien
   les 3 autres méthodes.
4. **Les dépenses aggravent l'écart.** `EnregistrerDepense` /
   `ApprouverDepense` débitent `caisses.solde` quelle que soit
   `methode_paiement` : une dépense payée par virement bancaire vide la caisse
   physique dans les chiffres.

### 4.1 Point de vérité : où est exactement la décision ?

Un seul endroit décide de créditer/débiter la caisse, par flux :

| Fichier | Ligne (≈) | Ce qu'il fait |
|---|---|---|
| `app/Domain/Payments/Actions/EnregistrerEncaissement.php` | 42 | `credit()` **inconditionnel** |
| `app/Domain/Payments/Actions/SupprimerEncaissement.php` | 55 | `debit()` (annulation) |
| `app/Domain/Expenses/Actions/EnregistrerDepense.php` | 55 | `debit()` si approbation OFF |
| `app/Domain/Expenses/Actions/ApprouverDepense.php` | 41 | `debit()` à l'approbation |
| `app/Domain/Finance/Actions/EnregistrerRemboursement.php` | 62 | `debit()` |
| `app/Domain/Finance/Actions/ValiderTransfertCaisse.php` | 72 / 80 | `debit()` source + `credit()` destination |

Bonne nouvelle : la correction se concentre sur **6 appels**, pas sur tout le
code.

---

## 5. Solutions envisageables

### Option A — Filtrer par méthode au moment du mouvement (recommandée)

**Principe :** `caisses.solde` redevient ce que son nom promet — **la caisse
physique**. Seuls les mouvements en **Espèces** touchent `CaisseLedger`.

```php
// EnregistrerEncaissement
if ($encaissement->methode === Encaissement::METHODE_ESPECES) {
    $this->ledger->credit(...);
}
```

Symétriquement dans les 5 autres points d'appel.

**Résultat :**

- Solde caisse = billets réellement présents ⇒ comptage possible.
- TPE / Chèque / Virement restent des lignes **dérivées** dans « Comptes de
  caisse » ⇒ plus de double comptage : chaque dirham est compté **une seule
  fois**, soit dans une caisse, soit dans une méthode dérivée.
- Les transferts de caisse redeviennent cohérents (on ne transfère que du cash).

**Avantages**

- Cohérent avec l'architecture déjà en place (`DERIVED_TYPES` existe déjà).
- Modification très localisée : 6 appels + les KPI du journal.
- Aucune migration de schéma.

**Inconvénients / points d'attention**

1. **Les données existantes sont fausses** et le resteront : les soldes actuels
   incluent déjà du TPE/chèque/virement. Il faut donc une **commande de
   recalcul** (`php artisan caisse:recalculer-soldes`) qui reconstruit chaque
   `solde` à partir des seuls mouvements espèces, et qui **journalise**
   l'ajustement via `CaisseLedger` (jamais un UPDATE brut).
2. Les cartes du journal (`totalEncaissements`, `totalDepenses`,
   `totalRemboursements`) doivent elles aussi filtrer sur Espèces, sinon
   l'écran se contredit à nouveau (Encaissements 2 600 / Solde 0).
   Meilleure UX : afficher **Encaissements espèces** et, à côté, un détail par
   méthode.
3. **Chèques déposés en banque plus tard** : le chèque est reçu (dérivé
   « Chèque »), puis encaissé en banque. Aucun flux ne modélise ce passage
   aujourd'hui — à traiter séparément si nécessaire.

### Option B — Colonnes multiples par méthode sur `caisses`

Ajouter `solde_especes`, `solde_tpe`, `solde_cheque`, `solde_virement`.

- **Avantages** : tout reste dans une table ; réconciliation par méthode.
- **Inconvénients** : contredit `GetComptesCaisse` (qui dérive déjà ces 3
  méthodes) ⇒ recrée la « deuxième copie divergente » que le docblock de
  `Caisse` interdit explicitement ; 4 colonnes à maintenir dans 6 actions ;
  migration sur une table de production. **Non recommandée.**

### Option C — Vrai grand-livre (table `caisse_mouvements`)

Une table de mouvements (`caisse_id`, `methode`, `sens`, `montant`, `source`) ;
le solde devient un `SUM()` filtrable par méthode et par période.

- **Avantages** : la solution comptable correcte ; solde par méthode, par
  période, par centre, gratuitement ; audit intégré ; supprime le risque de
  dérive du nombre stocké.
- **Inconvénients** : refonte du cœur financier (les 6 actions + `CaisseLedger`
  + tous les écrans qui lisent `solde`), migration + backfill depuis les 4
  tables existantes, tests financiers à réécrire. Bon point d'arrivée à long
  terme, pas un correctif ponctuel.

### Recommandation

**Option A maintenant** (correctif ciblé, cohérent avec l'existant), en gardant
**l'Option C comme cible d'évolution** si un besoin de soldes par
méthode/période apparaît. L'Option A ne ferme pas la porte à C : elle nettoie
justement la sémantique de `solde` avant une éventuelle migration vers un
grand-livre.

---

## 6. Questions ouvertes à trancher avant implémentation

1. **Espèces = seule méthode en caisse ?** Confirmé, ou bien le TPE est-il
   rattaché à un centre (donc à une caisse) plutôt qu'agrégé globalement ?
2. **Que faire des soldes existants ?** Recalcul complet, ou écriture d'un
   mouvement d'ajustement daté pour préserver l'historique ?
3. **Dépenses non-espèces** : rester visibles dans le journal de caisse (en
   information, sans effet sur le solde) ou disparaître de cet écran ?
4. **Transferts** : interdire un transfert supérieur au solde espèces ?
5. **Chèques encaissés en banque** : besoin d'un statut / d'un mouvement
   « remise en banque » ?
6. **Cartes de l'écran** : garder 4 cartes (avec Encaissements = espèces
   seulement) ou passer à un affichage « Espèces / TPE / Chèque / Virement » ?

---

## 7. Fichiers concernés (référence rapide)

```
app/Models/Caisse.php                                   <- types stockés vs dérivés
app/Models/Encaissement.php                             <- METHODES
app/Domain/Finance/Support/CaisseLedger.php             <- seul point de mouvement
app/Domain/Finance/Queries/GetCaisseJournal.php         <- KPI + lignes de l'écran
app/Domain/Finance/Queries/GetComptesCaisse.php         <- lignes dérivées TPE/Chèque/Virement
app/Domain/Payments/Actions/EnregistrerEncaissement.php <- credit() à conditionner
app/Domain/Payments/Actions/SupprimerEncaissement.php
app/Domain/Payments/Actions/AppliquerAvance.php         <- ne crédite pas (OK)
app/Domain/Expenses/Actions/EnregistrerDepense.php
app/Domain/Expenses/Actions/ApprouverDepense.php
app/Domain/Finance/Actions/EnregistrerRemboursement.php
app/Domain/Finance/Actions/ValiderTransfertCaisse.php
app/Http/Controllers/Backoffice/CaisseController.php    <- page Inertia
resources/js/Pages/Backoffice/Caisses/Index.tsx         <- les 4 cartes
```
