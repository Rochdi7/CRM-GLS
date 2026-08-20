# GLS CRM — Audit complet du système

> Scan intégral du code au **20/08/2026**, branche `main`, dernier commit `2663164`.
> Objectif : comprendre **tout le chemin** — de l'URL au contrôleur, à la couche
> métier, à la base de données, et retour vers l'écran React.
>
> Ce document décrit **ce qui existe réellement dans le code**, vérifié par
> exécution (`route:list`, `information_schema`, `tsc`, `grep`) — pas ce que la
> documentation prétend. Les écarts entre doc et code sont signalés en §12.

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture des couches — le chemin d'une requête](#2-architecture-des-couches--le-chemin-dune-requête)
3. [Carte complète des routes (190 routes)](#3-carte-complète-des-routes)
4. [Modules fonctionnels — chemin détaillé par module](#4-modules-fonctionnels--chemin-détaillé)
5. [Base de données — 48 tables](#5-base-de-données--48-tables)
6. [Sécurité : rôles, permissions, portée par centre](#6-sécurité--rôles-permissions-portée-par-centre)
7. [Invariants financiers (le cœur critique)](#7-invariants-financiers)
8. [Journal d'audit](#8-journal-daudit)
9. [Contexte actif (année + centre)](#9-contexte-actif-année--centre)
10. [Frontend React/Inertia](#10-frontend-reactinertia)
11. [Import de données depuis l'ancien CRM](#11-import-de-données)
12. [Constats de l'audit](#12-constats-de-laudit)
13. [Annexe : commandes et fichiers clés](#13-annexe)

---

## 1. Vue d'ensemble

**GLS CRM** — système de gestion scolaire pour Global Language School
(7 centres au Maroc, école de langue allemande).

### Chiffres réels

| Mesure | Valeur |
|---|---|
| Routes nommées | **190** |
| Tables PostgreSQL | **48** (11,39 Mo) |
| Modèles Eloquent | **34** |
| Contrôleurs | **43** (42 backoffice + 1 frontoffice) |
| Policies | **23** (+ 1 concern `ResourcePolicy`) |
| Form Requests | **31 dossiers** de modules |
| Classes Domain | **70** (Actions / Queries / DTOs / Support) |
| Permissions | **95** (registre et base parfaitement synchronisés) |
| Rôles | **7 en base** (6 au registre + 1 rôle `test` résiduel) |
| Pages React | **53** fichiers `.tsx` de pages |
| Composants React | **53** partagés |
| Code PHP applicatif | 23 726 lignes / 274 fichiers |
| Code TypeScript | 26 306 lignes / 120 fichiers |
| Tests | 13 583 lignes / **49 fichiers** |
| Migrations | **43** |

### Stack vérifiée (`composer.json` / `package.json`)

```
PHP           ^8.3   (exécuté avec C:\php84\php.exe)
Laravel       ^13.8
PostgreSQL    17.10  (seul moteur supporté)
Inertia       ^3.2 (Laravel) + ^3.6.1 (React)
React         ^19.2.8 + TypeScript ^5.9.3
Vite          ^8.0.0
Bootstrap 5 via thème PreSkool v1.9.7 (assets statiques)

Packages métier :
  spatie/laravel-permission    ^8.3   → rôles & permissions
  spatie/laravel-activitylog   ^5.0   → journal d'audit
  spatie/laravel-medialibrary  ^11.23 → photos, justificatifs
  openspout/openspout          ^5.10  → lecture Excel (imports)
  mpdf/mpdf                    ^8.3   → reçus PDF
```

**Absents et interdits (vérifié) :** Livewire, Alpine.js, jQuery, Select2 (le
plugin), Vue, Tailwind. `grep` sur `resources/js/` ne remonte que des *noms de
classes CSS* `select2-*` réutilisés par des composants 100 % React
(`SelectField.tsx`, `MultiSelectField.tsx`) — aucun import JS.

### Volumétrie des données actuelles

```
students            219      encaissements        561
inscriptions        215      inscription_fees   2 852
groups               17      group_frais          250
employees            12      caisses               12
etablissements        7      activity_log       4 437
salles               49      import_rows        4 237
```

---

## 2. Architecture des couches — le chemin d'une requête

Le système est un **monolithe modulaire**. Toute requête suit exactement ce chemin :

```
   NAVIGATEUR
       │
       │  GET /backoffice/students?search=dupont&page=2
       ▼
┌──────────────────────────────────────────────────────────────────┐
│ 1. ROUTE            routes/backoffice.php                        │
│    Route::get('/students', [StudentController::class,'index'])   │
│         ->middleware('permission:students.view')                 │
│    → routes/web.php ne fait QUE `require` les 2 fichiers d'aire  │
└──────────────────────────────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────────────────┐
│ 2. MIDDLEWARE                                                    │
│    auth                       → session, guard `web`             │
│    permission:students.view   → spatie/laravel-permission        │
│    can:update,student         → Policy (ressources liées)        │
│    HandleInertiaRequests      → props partagées (auth, context,  │
│                                 flash, locale)                   │
└──────────────────────────────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────────────────┐
│ 3. FORM REQUEST (écritures uniquement)                           │
│    App\Http\Requests\Backoffice\Students\StoreStudentRequest     │
│    → authorize() + rules() ; JAMAIS de validation inline         │
└──────────────────────────────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────────────────┐
│ 4. CONTRÔLEUR (mince)  App\Http\Controllers\Backoffice\…         │
│    - autorise (policy / can)                                     │
│    - délègue au Domain                                           │
│    - retourne Inertia::render('Backoffice/Students/Index', …)    │
│    ⚠ AUCUNE logique métier ici                                   │
└──────────────────────────────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────────────────┐
│ 5. DOMAIN            app/Domain/<Module>/                        │
│                                                                  │
│    Queries/   lecture  → GetStudentsList, GetCaisseJournal…      │
│               (pagination/tri/filtre côté SQL)                   │
│    Actions/   écriture → EnregistrerEncaissement, …              │
│               (1 transaction, invariants métier)                 │
│    Support/   règles   → CaisseLedger, ReferenceGenerator        │
│    DTOs/      transfert typé                                     │
│                                                                  │
│    ⚠ Le Domain n'appelle JAMAIS la couche HTTP (sens unique)     │
└──────────────────────────────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────────────────┐
│ 6. MODÈLES + POSTGRESQL                                          │
│    Eloquent → pgsql (ILIKE pour recherches, decimal(12,2) argent)│
│    Auditable → activity_log (chaque écriture journalisée)        │
└──────────────────────────────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────────────────┐
│ 7. RÉPONSE INERTIA → PAGE REACT                                  │
│    resources/js/Pages/Backoffice/Students/Index.tsx              │
│    dans BackofficeLayout.tsx (header + sidebar + footer)         │
│    props typées : PaginatedData<Student>, CrudPermissions…       │
└──────────────────────────────────────────────────────────────────┘
```

### Séparation Backoffice / Frontoffice (non négociable)

| | Backoffice (admin) | Frontoffice (public) |
|---|---|---|
| Routes | `routes/backoffice.php` (588 l.) | `routes/frontoffice.php` (30 l.) |
| URL | `/backoffice/…` | `/…` |
| Noms | `backoffice.*` | `frontoffice.*` |
| Rendu | **React/Inertia** | **Blade** |
| Pages | `resources/js/Pages/Backoffice/` | `resources/views/frontoffice/` |
| Tests | `tests/Feature/Backoffice/` | `tests/Feature/Frontoffice/` (vide) |

**État réel du Frontoffice :** quasi inexistant — 2 routes seulement.
`/` **redirige vers `/backoffice/login`** (phase « admin d'abord »), et
`/home` sert une page d'accueil Blade statique. L'authentification étudiant/parent
n'est pas développée.

---

## 3. Carte complète des routes

**190 routes nommées.** Légende : `→` = permission requise ; `P` = policy.

### 3.1 Public / Frontoffice (2)

| Méthode | URL | Nom | Cible |
|---|---|---|---|
| ANY | `/` | `frontoffice.root` | redirection → `/backoffice/login` |
| GET | `/home` | `frontoffice.home` | `Frontoffice\HomeController` |

### 3.2 Authentification (8) — `guest` sauf logout

| Méthode | URL | Nom | Cible |
|---|---|---|---|
| GET | `/backoffice/login` | `backoffice.login` | `Auth\LoginController@show` |
| POST | `/backoffice/login` | `backoffice.login.store` | `Auth\LoginController@store` |
| POST | `/backoffice/logout` | `backoffice.logout` | `Auth\LogoutController` |
| GET | `/backoffice/forgot-password` | `backoffice.password.request` | `Auth\ForgotPasswordController@show` |
| POST | `/backoffice/forgot-password` | `backoffice.password.email` | `@store` · `throttle:5,1` |
| GET | `/backoffice/reset-password/{token}` | `backoffice.password.reset` | `Auth\ResetPasswordController@show` |
| POST | `/backoffice/reset-password` | `backoffice.password.update` | `@store` |

Connexion par **email OU username** (champ unique `login`) ; les comptes
`is_active = false` sont refusés dans `LoginRequest`. Toutes les pages sont
**React** (`Pages/Backoffice/Auth/{Login,ForgotPassword,ResetPassword}.tsx`).

### 3.3 Tableau de bord, profil, contexte (7)

| Méthode | URL | Nom | Permission |
|---|---|---|---|
| GET | `/backoffice/dashboard` | `backoffice.dashboard` | `auth` |
| POST | `/backoffice/context` | `backoffice.context.update` | `auth` |
| GET | `/backoffice/profile` | `backoffice.profile` | `auth` |
| POST | `/backoffice/profile` | `backoffice.profile.update` | `auth` |
| POST | `/backoffice/profile/password` | `backoffice.profile.password.update` | `auth` |
| POST/DELETE | `/backoffice/profile/photo` | `backoffice.profile.photo.*` | `auth` |

### 3.4 Étudiants (7)

| Méthode | URL | Nom | Permission |
|---|---|---|---|
| GET | `/backoffice/students` | `.index` | → `students.view` |
| POST | `/backoffice/students` | `.store` | → `students.create` |
| PUT | `/backoffice/students/{student}` | `.update` | → `students.update` |
| DELETE | `/backoffice/students/{student}` | `.destroy` | → `students.delete` |
| GET | `/backoffice/students/{student}` | `.show` | `auth` (P) |
| GET | `…/{student}/cheques` | `.cheques` | `auth` (JSON) |
| GET | `…/{student}/inscriptions-for-payment` | `.inscriptions-for-payment` | `auth` (JSON) |
| GET | `…/{student}/payments-for-refund` | `.payments-for-refund` | `auth` (JSON) |

### 3.5 Inscriptions (15) — le module le plus riche

| Méthode | URL | Nom | Permission |
|---|---|---|---|
| GET | `/backoffice/inscriptions` | `.index` | → `registrations.view` |
| POST | `/backoffice/inscriptions` | `.store` | → `registrations.create` |
| PUT | `…/{inscription}` | `.update` | → `registrations.update` |
| DELETE | `…/{inscription}` | `.destroy` | → `registrations.delete` |
| GET | `…/{inscription}` | `.show` | `auth` |
| PATCH | `…/{inscription}/statut` | `.update-statut` | → `registrations.update` |
| POST | `…/{inscription}/change-group` | `.change-group` | → `registrations.change-group` |
| GET/PUT | `…/{inscription}/fees` | `.fees` / `.fees.update` | → `registrations.manage-fees` |
| POST | `…/fees/{fee}/hide` | `.fees.hide` | → `registrations.manage-fees` |
| POST | `…/fees/{fee}/restore` | `.fees.restore` | → `registrations.manage-fees` |
| GET/PUT | `…/{inscription}/livres` | `.livres` / `.livres.update` | → `registrations.manage-fees` |
| GET | `…/{inscription}/payments` | `.payments` | `auth` (JSON) |
| GET | `…/{inscription}/unpaid-fees` | `.unpaid-fees` | `auth` (JSON) |

### 3.6 Groupes (15)

| Méthode | URL | Nom | Permission |
|---|---|---|---|
| GET | `/backoffice/groups` | `.index` | → `groups.view` |
| POST | `/backoffice/groups` | `.store` | → `groups.create` |
| PUT | `…/{group}` | `.update` | → `groups.update` |
| GET | `…/{group}` | `.show` | `auth` |
| POST | `…/{group}/archive` | `.archive` | `auth` (P `groups.archive`) |
| POST | `…/{group}/activer` | `.activer` | `auth` |
| POST | `…/{group}/annuler` | `.annuler` | `auth` |
| POST | `…/{group}/reactiver` | `.reactiver` | `auth` |
| POST | `…/{group}/retourner-en-inscription` | `.retourner-en-inscription` | `auth` |
| POST | `…/{group}/changer-enseignant` | `.changer-enseignant` | → `groups.update` |
| PUT | `…/{group}/affectations/{affectation}` | `.affectations.update` | → `groups.update` |
| GET | `…/{group}/students-by-segment` | `.students-by-segment` | `auth` (JSON) |
| GET | `…/{group}/inscription-fees` | `.inscription-fees` | `auth` (JSON) |
| GET | `…/{group}/inscription-livres` | `.inscription-livres` | `auth` (JSON) |
| GET | `/backoffice/groups-historique` | `groups-historique.index` | `auth` |

> **Aucune route `DELETE` sur les groupes** — invariant respecté : un groupe
> n'est jamais supprimé, seulement archivé via `Group::archiverCommeTermine()`.

### 3.7 Finance (35 routes) — zone critique

**Encaissements (7) + Avances (3)**

| Méthode | URL | Nom | Permission |
|---|---|---|---|
| GET | `/backoffice/encaissements` | `.index` | → `payments.view` + P |
| POST | `/backoffice/encaissements` | `.store` | → `payments.create` + P |
| PUT | `…/{encaissement}` | `.update` | → `payments.update` + P |
| DELETE | `…/{encaissement}` | `.destroy` | → `payments.delete` + P ⚠ |
| GET | `…/{encaissement}` | `.show` | `auth` + P |
| GET | `…/{encaissement}/recu` | `.recu` | → `payments.view` (PDF) |
| POST | `…/{encaissement}/recu/email` | `.recu.email` | → `payments.view` |
| POST | `/backoffice/avances` | `avances.store` | → `payments.create` |
| POST | `/backoffice/avances/convert` | `avances.convert` | → `payments.create` |
| POST | `/backoffice/avances/{encaissement}/apply` | `avances.apply` | → `payments.create` |

> ⚠ `payments.delete` existe mais **n'est attribué à AUCUN rôle** dans
> `PermissionRegistry::matrix()` — décision délibérée : seul un super-admin peut
> l'accorder à la main pour un cas de correction réel.

**Caisses (3) — aucune route d'écriture directe**

| GET | `/backoffice/caisses` | `.index` | `auth` |
| GET | `/backoffice/caisses/{caisse}` | `.show` | `auth` (P) |
| GET | `/backoffice/caisses/journal/{scope}` | `.journal` | `auth` (JSON) |

**Transferts de caisse (5) — flux à 2 étapes**

| POST | `/backoffice/caisse-transfers` | `.store` | → `cash-transfers.create` |
| PUT | `…/{transfer}` | `.update` | → `cash-transfers.update` |
| PUT | `…/{transfer}/validate` | `.validate` | → `cash-transfers.validate` |
| GET | `…/{transfer}` | `.show` | `auth` |
| GET | `/backoffice/caisse-transfers` | `.index` | → `cash-transfers.view` |

**Dépenses (5), Remboursements (3), Chèques (5)**

| GET/POST/PUT | `/backoffice/depenses…` | `.index/.store/.update/.show` | → `expenses.*` |
| DELETE | `…/{depense}/justificatifs/{media}` | `.justificatifs.destroy` | → `expenses.update` |
| GET/POST/PUT | `/backoffice/remboursements…` | `.index/.store/.update` | → `refunds.*` |
| GET/POST/PUT | `/backoffice/cheques…` | `.index/.store/.update` | → `cheques.*` |
| PATCH | `…/{cheque}/retour` · `…/{cheque}/statut` | `.retour` / `.update-statut` | → `cheques.update` |

**Recouvrement (1)** — `GET /backoffice/recouvrement` → `collections.view`

> **Vérifié : aucune route `DELETE` sur dépenses, remboursements, chèques ou
> transferts.** Les écritures d'argent sont append-only par construction — la
> seule exception étant `encaissements.destroy`, encadrée (voir §7.2).

### 3.8 Présences / Séances / Emploi du temps (12)

| GET | `/backoffice/seances` | `.index` | → `attendance.view` |
| POST/PUT/DELETE | `/backoffice/seances…` | `.store/.update/.destroy` | → `attendance.create/update/delete` |
| GET | `…/{seance}` | `.show` | `auth` |
| GET | `/backoffice/seances/saisir-absence` | `.presences` | → `attendance.view` |
| PUT | `…/{seance}/presences` | `.presences.update` | → `attendance.mark` |
| POST | `…/{seance}/valider` · `…/{seance}/annuler` | `.valider` / `.annuler` | → `attendance.mark` |
| GET | `/backoffice/emploi-du-temps` | `emploi-du-temps.index` | → `attendance.view` |
| POST/PUT/DELETE | `/backoffice/creneaux…` | `creneaux.*` | → `attendance.create/update/delete` |

### 3.9 Stock (7), RH (4), Import (21)

| GET | `/backoffice/stock` | `stock.index` | → `stock.view` |
| POST/PUT/DELETE | `/backoffice/stock-articles…` | `stock-articles.*` | → `stock.create/update/delete` |
| POST | `/backoffice/stock-mouvements` | `stock-mouvements.store` | → `stock.move` |
| POST/PUT/DELETE | `/backoffice/stock-types…` | `stock-types.*` | P `StockTypePolicy` |
| GET/POST/PUT/DELETE | `/backoffice/employees…` | `employees.*` | → `employees.*` |

**Import — 3 modules × 7 étapes (21 routes)** : `students`, `inscriptions`,
`encaissements`, chacun avec `create → analyze → preview → commit → result →
retry-failed` (+ `peek-*` pour inscriptions/encaissements).

### 3.10 Configuration (30)

| GET | `/backoffice/settings` | `backoffice.settings` | `auth` (page à onglets) |
| resource | `/backoffice/etablissements` (7) | `etablissements.*` | P `EtablissementPolicy` |
| resource | `/backoffice/annees-scolaires` (6) | `annees-scolaires.*` | P |
| resource | `/backoffice/salles` (6) | `salles.*` | P |
| resource | `/backoffice/frais` (6) | `frais.*` | P (⚠ param `{frai}`) |
| resource | `/backoffice/banques` (6) | `banques.*` | P |
| resource | `/backoffice/motifs-annulation` (6) | `motifs-annulation.*` | P |
| resource | `/backoffice/types-depenses` (4) | `types-depenses.*` | P |
| GET/POST/PUT/DELETE | `/backoffice/roles…` (6) | `roles.*` | → `roles.*` |
| GET | `/backoffice/permissions` | `permissions.index` | → `permissions.view` (lecture seule) |
| GET/PUT | `/backoffice/users…` (4) | `users.*` | → `users.view` / `users.assign-roles` |
| POST | `…/{user}/regenerate-password` | `.regenerate-password` | → `users.assign-roles` · `throttle:10,1` |
| GET | `/backoffice/audit-logs` · `/{activity}` | `audit-logs.index/.show` | → `audit-logs.view` |

> **Le journal d'audit n'a QUE des routes GET** — invariant append-only respecté.
> Les utilisateurs ne sont **jamais créés** ici (ils naissent d'un employé).

---

## 4. Modules fonctionnels — chemin détaillé

### 4.1 Chaîne pédagogique : Frais → Groupe → Inscription → Paiement

C'est le flux métier central. Voici le chemin complet des données :

```
┌─────────────┐   catalogue géré dans Paramètres → onglet Frais
│  frais      │   (nom, montant_defaut, statut)
│  18 lignes  │   FraisController · FraisPolicy · fees.*
└──────┬──────┘
       │  assignation par groupe (pivot)
       ▼
┌─────────────────────────────────────────────────────────┐
│  group_frais   250 lignes                               │
│  group_id + frais_id + montant + date_echeance          │
│  → le MÊME frais peut avoir un montant ET une échéance  │
│    différents selon le groupe                           │
└──────┬──────────────────────────────────────────────────┘
       │  à l'inscription, le groupe choisi charge SES frais
       │  comme « Frais disponibles » (case à cocher)
       ▼
┌─────────────────────────────────────────────────────────┐
│  inscription_fees   2 852 lignes                        │
│  montant_initial + remise_pct | remise_montant + note   │
│  → InscriptionFee::computeMontant() calcule `montant`   │
│    (le % d'abord, sinon la remise fixe en DH)           │
│  statut : Non payé | Payé partiellement | Payé          │
│  masque_le : masquage réversible (hide/restore)         │
└──────┬──────────────────────────────────────────────────┘
       │  chaque paiement cible UNE ligne de frais
       ▼
┌─────────────────────────────────────────────────────────┐
│  encaissements   561 lignes                             │
│  inscription_fee_id  → NULL = « avance » non affectée   │
│  applied_from_encaissement_id → trace l'avance d'origine│
│  caisse_id → mouvement via CaisseLedger                 │
└─────────────────────────────────────────────────────────┘
```

**Le mécanisme d'avance** (`app/Domain/Payments/Actions/`) :

- `EnregistrerEncaissement` — paiement normal, crédite la caisse.
- Une **avance** = un encaissement avec `inscription_fee_id = NULL`.
- `AppliquerAvance` — affecte tout ou partie d'une avance à un frais précis.
  Crée une **seconde ligne** `Encaissement` avec
  `applied_from_encaissement_id` pointant vers l'avance ; **l'avance elle-même
  n'est jamais modifiée** (append-only). La caisse **ne bouge pas** ici —
  l'argent était déjà entré ; seule l'*affectation* change. L'action écrit
  quand même une entrée d'audit `avance_applied` (avec restant avant/après).
- `ConvertirEncaissementsEnAvance` — détache un paiement de son frais
  (`inscription_fee_id` → NULL), il redevient allouable.

**Statuts d'inscription** : `Active`, `Annulée`, `Changement`, `Expirée`, `Archivée`.
**Statuts de groupe** : `En inscription` → `En formation` → `Fin de formation`
(+ `Annulée`), avec transitions dédiées (`activer`, `annuler`, `reactiver`,
`retourner-en-inscription`, `archive`).

### 4.2 Chaîne financière : Caisse

```
                    ┌──────────────────────────┐
                    │  caisses  (12 lignes)    │
                    │  solde numeric(12,2)     │
                    │  ⚠ maintenu par l'appli  │
                    │    (pas de table ledger) │
                    └───────────▲──────────────┘
                                │
                 LE SEUL point d'écriture du solde
                                │
            ┌───────────────────┴────────────────────┐
            │  Domain\Finance\Support\CaisseLedger   │
            │    credit()  → argent entrant          │
            │    debit()   → argent sortant          │
            │  • lockForUpdate() dans la transaction │
            │  • écrit solde_avant / montant /       │
            │    solde_apres + record d'origine      │
            │    dans activity_log (log « caisse »)  │
            └───────────────────▲────────────────────┘
                                │  7 appelants, tous des Actions Domain
     ┌──────────────┬───────────┼───────────┬─────────────────┐
     │              │           │           │                 │
Enregistrer   Enregistrer  Enregistrer  Supprimer     ValiderTransfert
Encaissement    Depense    Remboursement Encaissement   Caisse (2 legs)
   crédit        débit        débit       débit       débit+crédit
```

**Transfert de caisse à 2 étapes** (anti-fraude) :

1. `DemanderTransfertCaisse` — crée la demande, statut `En attente`.
   **Les soldes ne bougent pas.**
2. `ValiderTransfertCaisse` — par un employé **différent**
   (l'auto-validation est refusée), statut `Validé`. Les deux soldes bougent,
   **les deux jambes sont journalisées** (`solde_source_avant/apres` et
   `solde_dest_avant/apres` sont stockés dans la table).

### 4.3 Chaîne pédagogique : Présences

```
Group ──┬─→ creneaux    (récurrence : jour_semaine, heure, salle, enseignant,
        │                date_debut/date_fin)
        │        │
        │        │  GenererSeancesDepuisCreneau
        │        │  (+ commande artisan `seances:generate`)
        │        ▼
        └─→ seances     (date_seance, statut : Prévue → Effectuée | Annulée)
                 │
                 │  EnregistrerPresences
                 ▼
             presences  (statut : Présent | Absent | Retard | Justifié)
```

### 4.4 RH : création d'employé → compte utilisateur automatique

```
EmployeeController@store
   → Employee::create(...)
        → EmployeeObserver (created)
             → EmployeeCredentialService
                  → crée le User (username auto + mot de passe unique)
                  → flash session : new_employee_username / _password
                       ↓
             HandleInertiaRequests partage ces clés avec pull()
             (lecture destructive : le modal ne peut s'ouvrir qu'UNE fois)
                       ↓
             Modal React affiche les identifiants — jamais loggés
```

Un employé est **rattaché à ≥ 1 centre** via le pivot `employee_etablissement`
(source de vérité pour l'**accès**), tandis que `employees.etablissement_id`
reste son centre **principal** (là où vit sa caisse). Les deux se modifient
uniquement par `Employee::syncEtablissements()`.

Il n'y a **aucune inscription publique** — tout compte naît d'un employé.

---

## 5. Base de données — 48 tables

PostgreSQL **17.10**, base `gls_crm`, 11,39 Mo. **Seul moteur supporté** : ni
SQLite, ni MySQL, ni branche conditionnelle sur le driver.

### 5.1 Carte relationnelle

```
                          etablissements (7)
                                  │
      ┌───────────┬───────────┬───┴────┬──────────┬─────────────┐
      │           │           │        │          │             │
   salles(49)  employees(12) students groups   caisses(12)  stock_articles
      │           │  │        (219)    (17)        │            (56)
      │           │  │          │       │          │             │
      │           │  └──────────┼───────┼──────────┘             │
      │           │  employee_  │       │                  stock_mouvements
      │           │  etablisse- │       │                        (58)
      │           │  ment (3)   │       │
      │           │             │       ├── group_frais (250) ─── frais (18)
      │           │             │       ├── group_enseignants (3)
      │           │             │       ├── creneaux (0)
      │           │             │       ├── seances (0) ─── presences (0)
      │           │             │       └── groups_historique (0)
      │           │             │       │
      │           │             ▼       ▼
      │           │        inscriptions (215)
      │           │             │
      │           │      ┌──────┼──────────────┬──────────────┐
      │           │      ▼      ▼              ▼              ▼
      │           │  inscription  inscription  inscriptions   (montant_total)
      │           │  _fees(2852)  _livres(0)   _historique(0)
      │           │      │
      │           │      ▼
      │           └─→ encaissements (561) ──┬── cheques (1)
      │                     │               └── caisses
      │                     ▼
      │              remboursements (1)
      │
      └─→ depenses (17) ─── types_depenses (10)
          caisse_transfers (4)  [source ↔ destination]

  Transverses : annees_scolaires(2) · banques(8) · motifs_annulation(8)
                stock_types(6) · media(1) · activity_log(4437)
                import_batches(3) → import_rows(4237)
  Auth        : users(8) · roles(7) · permissions(95)
                model_has_roles(6) · role_has_permissions(176)
```

### 5.2 Règles PostgreSQL appliquées (vérifiées)

| Règle | État |
|---|---|
| Recherche insensible à la casse via **`ILIKE`** | ✅ (`LIKE` serait sensible à la casse en PG) |
| Argent en **`numeric(12,2)`** — jamais de flottant | ✅ toutes les colonnes `montant` |
| **`jsonb`** et non `json` | ✅ `import_batches.context`, `import_rows.raw/errors/resolution`, `media.*` |
| Index sur le côté référençant des FK | ✅ (PG ne les crée pas automatiquement) |
| Aucune branche `DB::getDriverName()` | ✅ |
| Tests sur base **séparée** `gls_crm_test` | ✅ `phpunit.xml` |

### 5.3 Politiques de suppression (`ON DELETE`) — lecture des invariants

Les FK racontent la politique de conservation :

- **`RESTRICT`** sur tout ce qui touche l'argent et l'identité :
  `encaissements.student_id`, `.caisse_id`, `.agent_id`, `.inscription_fee_id`,
  `depenses.caisse_id`, `.type_depense_id`, `.agent_id`,
  `remboursements.beneficiaire_id`, `.caisse_id`,
  `caisse_transfers.caisse_source_id`, `.caisse_destination_id`, `.requested_by`,
  `inscriptions.student_id`, `.group_id`, `presences.student_id`,
  `seances.group_id`, `stock_mouvements.stock_article_id`.
  → **On ne peut pas supprimer une entité dont dépend une écriture d'argent.**
- **`SET NULL`** sur les rattachements optionnels (centre, salle, enseignant,
  année scolaire, `validated_by`).
- **`CASCADE`** seulement sur les enfants réellement possédés :
  `inscription_fees`, `inscription_livres`, `creneaux`, `group_frais`,
  `group_enseignants`, `import_rows`, `employee_etablissement`.

### 5.4 Simplifications délibérées (ne pas « corriger »)

| Choix | Raison |
|---|---|
| `niveau`, `categorie`, tous les `statut` = **VARCHAR** validés contre des constantes de modèle | Évite 10 tables de référence pour des listes fixes |
| `caisses.solde` **stocké**, pas de table de mouvements | Compensé par `CaisseLedger` + journal d'audit intégral |
| Références (`ENC-`, `ETU-`…) générées par `ReferenceGenerator` | Jamais saisies par l'utilisateur |

---

## 6. Sécurité : rôles, permissions, portée par centre

L'autorisation répond à **deux questions distinctes** :

| Question | Mécanisme |
|---|---|
| **QUOI** ai-je le droit de faire ? | `spatie/laravel-permission` — permissions `module.action` |
| **SUR QUEL CENTRE** ai-je le droit d'agir ? | `CenterAccessService` — pivot `employee_etablissement` |

Les policies (`app/Policies/Concerns/ResourcePolicy.php`) **combinent les deux**.

### 6.1 Source unique de vérité

`app/Support/Authorization/PermissionRegistry.php` — 26 groupes de permissions.
Ajouter un module = ajouter les permissions **ici**, rejouer
`db:seed --class=RolesAndPermissionsSeeder` (idempotent), protéger les routes,
écrire les tests autorisé + refusé.

### 6.2 Matrice des rôles

| Module | super-admin | director | operations-director | administrative-assistant | teacher | marketing |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Tableau de bord | ∞ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Centres — *accès tous centres* | ∞ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Années scolaires | ∞ | CUD | R | R | — | — |
| Salles / Frais | ∞ | CRUD | CRUD | R | — | — |
| Employés | ∞ | CRUD | RU | — | — | — |
| Utilisateurs / Rôles | ∞ | R + rôles | — | — | — | — |
| Étudiants | ∞ | CRUD | CRUD | CRUD | R | R |
| Inscriptions | ∞ | CRUD+frais+groupe | CRUD+frais+groupe | CRUD+frais+groupe | — | R |
| Groupes | ∞ | CRU+archive | CRU+archive | CRU | R | — |
| Présences | ∞ | tout | CRU+appel | CR+appel | CR+appel | — |
| **Encaissements** | ∞ | R C U | **R** | **R C U** | ❌ | ❌ |
| **Caisses** | ∞ | CRUD | R | R | ❌ | ❌ |
| **Dépenses** | ∞ | R C U | R | R C | ❌ | ❌ |
| **Remboursements** | ∞ | R C U | R | R C | ❌ | ❌ |
| **Chèques** | ∞ | R C U | R | R C | ❌ | ❌ |
| **Transferts caisse** | ∞ | C R U + **validate** | R | C R (pas validate) | ❌ | ❌ |
| Recouvrement | ∞ | R | R | R | ❌ | ❌ |
| Stock | ∞ | CRUD+move | CRUD+move | R+move | — | — |
| **Journal d'audit** | ∞ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Import de données | ∞ | ✅ | ✅ | ❌ | ❌ | ❌ |

`∞` = bypass total par `Gate::before`. Le rôle `super-admin` a
**volontairement zéro permission en base** — il ne dépend d'aucune ligne
synchronisée.

**Séparation des pouvoirs visible dans la matrice :**
- L'assistante administrative **encaisse et demande** un transfert, mais ne le
  **valide** jamais (`cash-transfers.validate` réservé au directeur).
- Le directeur des opérations **voit** toute la finance mais n'y **écrit** rien.
- L'enseignant et le marketing n'ont **aucun accès financier**.
- `payments.delete` et `banks.*` / `cancellation-reasons.*` ne sont dans **aucun**
  rôle — super-admin uniquement.

### 6.3 Protections du super-admin

- Protégé : ni renommage, ni édition, ni suppression du rôle.
- Seuls les super-admins peuvent l'accorder ou le retirer.
- **Le dernier super-admin ne peut jamais perdre son rôle.**
- Première attribution : `artisan auth:assign-super-admin <email>`.
- Les seuls `hasRole()` autorisés du code : le bypass `Gate::before` et ces
  invariants dans `UserAuthorizationService`.

### 6.4 Portée par centre

```php
// CenterAccessService::accessibleCenterIds()
centers.access-all (ou super-admin)  →  TOUS les centres
sinon                                →  centres du pivot employee_etablissement
                                        + etablissement_id (repli hérité)
aucun profil employé                 →  uniquement les enregistrements globaux
enregistrement à centre NULL         →  global, visible de tous
```

Les requêtes de liste doivent filtrer **sur le pivot**, pas seulement sur la
colonne principale (voir `GetEmployeesList`, `GetUsersList`).

### 6.5 L'autorisation côté client est cosmétique

Les props Inertia `auth.permissions`, `auth.isSuperAdmin`, `CrudPermissions`
servent **uniquement** à cacher un bouton ou un item de menu.
**La seule vraie barrière est le contrôle serveur** (middleware `permission:`,
policies, `$user->can()`), systématiquement revérifié dans chaque méthode
de mutation.

---

## 7. Invariants financiers

Ce sont les règles non négociables du système. **Toutes vérifiées par scan.**

### ✅ 1. Le solde ne bouge que par `CaisseLedger`

```bash
grep -rn "increment('solde'|decrement('solde'|->solde =" app/ --exclude=CaisseLedger.php
→ AUCUN RÉSULTAT
```

**Pourquoi c'est critique** (documenté dans le fichier lui-même) : les actions
utilisaient auparavant `Caisse::query()->increment('solde', …)`, qui est du SQL
brut — le modèle n'est jamais chargé, Eloquent n'émet aucun événement, et le
journal d'audit **n'enregistrait rien**. La ligne de paiement était tracée, mais
le mouvement de solde qu'elle causait était **invisible**. Pour un CRM où
« l'argent est tout », c'était précisément la faille par laquelle une fraude
passait : ajuster une caisse, et la trace ne montrait que l'existence d'un
paiement, jamais que le solde avait bougé ni de combien.

Aujourd'hui chaque mouvement écrit **l'arithmétique complète** — solde avant,
delta, solde après, et l'enregistrement d'origine — donc une caisse se vérifie
ligne par ligne sans rien recalculer.

Détails techniques : `lockForUpdate()` + relecture dans la transaction de
l'appelant (les valeurs enregistrées sont les vraies valeurs encadrant le
changement, pas une lecture périmée) ; montant strictement positif obligatoire
(la direction vient de `$sens`, un montant négatif l'inverserait silencieusement).

### ✅ 2. Les écritures d'argent ne sont jamais supprimées

Aucune route `destroy` sur `depenses`, `remboursements`, `cheques`,
`caisse-transfers`. Les corrections passent par des écritures compensatoires.

**Seule exception, volontairement encadrée** — `encaissements.destroy`,
ajoutée au commit `2663164` derrière `permission:payments.delete`, permission
attribuée à **aucun rôle** (un super-admin l'accorde à la main pour un cas de
correction réel). L'action `SupprimerEncaissement` est l'**inverse exact** de
`EnregistrerEncaissement`, en une transaction : relecture sous verrou de ligne,
débit via `CaisseLedger` (sans quoi supprimer la ligne corromprait
silencieusement la caisse), suppression, puis recalcul du statut du frais.
Elle **refuse** les cas non dénouables (avance déjà appliquée, paiement par
chèque suivi) et ne débite pas une ligne « apply », qui n'avait rien crédité.

⚠ Le test `ReadOnlyPagesInertiaTest::test_encaissement_show_has_no_delete_route`
n'a pas suivi cette évolution et fait échouer la suite — voir 12.3 #1.

### ✅ 3. Les transferts sont à deux étapes, sans auto-validation

Demande (soldes intacts) → validation par un employé **différent** (soldes
déplacés). Les deux jambes journalisées.

### ✅ 4. `montant` / `caisse_id` non modifiables après création

### ✅ 5. Un groupe n'est jamais supprimé

Aucune route `groups.destroy`. Transition uniquement par
`Group::archiverCommeTermine()`, qui écrit l'instantané `groups_historique`
**dans la même transaction**.

### ✅ 6. Les références sont générées par le système

`Domain\Shared\Support\ReferenceGenerator` — `EMP-`, `ETU-`, `INS-`, `ENC-`,
`DEP-`, `RMB-`, `TRF-`. Jamais saisies par l'utilisateur.

---

## 8. Journal d'audit

`spatie/laravel-activitylog` **v5**. 4 437 entrées. Route de lecture **seule**.

```
Modèle métier
   │  use App\Models\Concerns\Auditable;   ← JAMAIS LogsActivity à la main
   ▼
Auditable applique logAll()               ← TOUTES les colonnes, pas d'allowlist
   │  (un logOnly([...]) écrit à la main perdrait silencieusement des champs —
   │   c'est exactement le bug que ce trait remplace)
   ▼
log_name lu depuis AuditLogRegistry::map()
   │  (31 modèles enregistrés — filtres, libellés et périmètre finance
   │   lisent tous ce registre, donc ils ne peuvent jamais diverger)
   ▼
Activity::creating() estampille automatiquement :
   IP · user-agent · méthode HTTP · URL · nom de route · causer_label figé
   ▼
activity_log (append-only)
   │  App\Models\Activity lève une exception sur update ET delete —
   │  au niveau MODÈLE, donc sous tous les Gates : même un super-admin
   │  ne peut pas réécrire l'histoire.
   ▼
Page de lecture : AuditValueResolver résout les ids en noms AU MOMENT DE LA
LECTURE (FK → nom, libellés FR, dates 19/08/2026). Jamais dans la ligne
stockée — sinon un renommage ultérieur réécrirait l'historique.
```

**Périmètre finance** (vue « suivi / fraude ») : `encaissement`, `depense`,
`remboursement`, `caisse_transfer`, `cheque`, `caisse`, `inscription_fee`.

**Événements d'authentification** — `App\Listeners\LogAuthenticationActivity`
couvre connexion, déconnexion, **échecs de connexion**, verrouillage, reset de
mot de passe. Lié par la découverte automatique de listeners Laravel :
⚠ ne **jamais** l'enregistrer aussi via `Event::subscribe()` ou un tableau
`$listen`, sinon chaque événement est écrit **deux fois**.

**Purge** : `activitylog:clean` uniquement.

---

## 9. Contexte actif (année + centre)

Chaque écran est cadré par `App\Services\Context\CurrentContext` — un
singleton **par requête**, adossé à la session.

```
Barre supérieure : ContextSwitcher.tsx
        │  POST backoffice.context.update
        ▼
ContextController@update → CurrentContext (session) → redirect back
        │
        ▼
HandleInertiaRequests partage `context` en prop LAZY
   (résolue seulement si la page la demande — jamais pour un invité,
    car les requêtes année/centre sont de vrais allers-retours SQL)
        │
        ▼
Toute page se re-render à la navigation suivante — le contexte vit
côté SERVEUR, pas dans un état client.
```

**Le changement de centre est soumis aux permissions :**
- `centers.access-all` → tous les centres + « Tous les centres ».
- Employé mono-centre → **verrouillé**, aucun changement possible.
- Employé multi-centres → bascule entre **ses** centres, et « Tous les centres »
  signifie alors « tous les miens ».

**Règle du filtre Centre** (toutes pages de liste, présentes et futures) : la
liste déroulante « Centre » **et sa colonne** doivent être enveloppées dans
`{!centerLocked && (…)}`, le contrôleur passant
`'centerLocked' => ! $context->isAllCenters()`.
Raison : `CurrentContext` cadre déjà toutes les requêtes côté serveur ; afficher
un filtre Centre redondant une fois l'utilisateur passé sur Marrakech est
trompeur. **Ne jamais** conditionner sur un test de rôle dans le composant —
réutiliser `centerLocked` pour rester synchronisé automatiquement.
Les pages de détail sont exemptées (le centre y est un attribut de la fiche).

---

## 10. Frontend React/Inertia

### 10.1 Chemin d'un fichier de page

```
resources/js/
├── app.tsx                          point d'entrée Vite
├── Layouts/
│   ├── BackofficeLayout.tsx         shell admin (header+sidebar+footer+thème)
│   └── GuestLayout.tsx              écrans d'auth
├── Pages/Backoffice/<Module>/       53 pages
│   ├── Index.tsx                    liste + modal ajout/édition
│   └── Show.tsx                     détail lecture seule
├── Components/                      53 composants partagés
│   ├── Tables/    DataTable · SearchInput · TableToolbar · Pagination · RowActions
│   ├── Modals/    Modal · ConfirmDialog
│   ├── Forms/     SelectField · MultiSelectField · PhoneField · DateField · …
│   ├── Theme/     Header · Sidebar · Footer · Breadcrumbs · PageHeader
│   ├── Context/   ContextSwitcher
│   ├── Feedback/  FlashMessages · Toast · ToastContainer
│   └── Import/    CommitProgressBar · ImportRowReasonTable
├── Config/        backofficeNavigation.ts · pageTabs.ts
├── Hooks/         useInertiaLoading · useTranslation · useCommitProgress · useActivePath
├── Lib/           i18n.ts · forms.ts · money.ts
└── Types/         index.ts · import.ts
```

### 10.2 Le modèle CRUD standard

**Chaque module CRUD est une page de liste Inertia avec un modal d'ajout/édition**
— pas de pages `create`/`edit` séparées. Certaines routes `create`/`edit`
existent encore (héritage des `Route::resource`) mais ne sont pas le chemin
utilisé par l'interface.

Pagination, recherche, tri et filtres sont **toujours côté serveur** :
`SearchInput` débounce (~400 ms) puis appelle le `reload(filters)` de la page,
qui fait
`router.get(url, filters, { preserveState: true, preserveScroll: true, replace: true })`.
Jamais de jeu de données client, jamais de DataTables jQuery.

### 10.3 Architecture des modals

**Tous les modals sont pilotés par l'état React** — seule architecture de modal
de l'application. `Modal.tsx` possède : ouverture/fermeture, Échap, clic sur le
fond, piège + restauration du focus, verrouillage du scroll body.
Le visuel réutilise le markup Bootstrap `.modal` / `.modal-dialog` /
`.modal-backdrop` ; **seule la couche comportement est React**, jamais le JS de
Bootstrap. Ni `bootstrap.bundle.js`, ni `data-bs-toggle`, ni `data-bs-dismiss`.

### 10.4 Internationalisation

`t()` depuis `resources/js/Lib/i18n.ts` lit **directement `lang/fr.json`** — le
même dictionnaire clé-anglaise/valeur-française que `__()` côté Laravel, donc
les deux côtés sont toujours d'accord. Chaque chaîne visible passe par
`t('English key')`, avec la valeur française ajoutée dans le même changement
(une clé manquante retombe sur la clé anglaise, sans jamais planter).

**Le français est la langue par défaut** (`APP_LOCALE=fr`). AR / EN / DE sont
préparés dans `lang/*.json` pour un futur sélecteur.

### 10.5 Pièges du thème PreSkool

⚠ **`fs-*` est une échelle en PIXELS, pas l'échelle de titres Bootstrap.**
Le `style.css` de PreSkool se charge **après** `bootstrap.min.css` et
redéfinit toute la plage `.fs-*` : **le nombre est la taille en pixels**.
`fs-24` = 24 px, `fs-18` = 18 px. Donc `fs-1`…`fs-6` de Bootstrap deviennent
1 px…6 px — **`fs-4` rend du texte de 4 px**. Utiliser toujours la valeur en
pixels voulue (`fs-24`, `fs-20`, `fs-16`, `fs-14`, `fs-13`). Seul usage
légitime d'un chiffre unique : `ti-circle-filled fs-5` du thème (pastille de
statut de 5 px, volontaire).

⚠ **Les tableaux s'affichent en MAJUSCULES.** `.table thead th` et
`.table tbody td` portent `text-transform: uppercase` dans `app.css`.
C'est une transformation **CSS d'affichage uniquement** — la valeur stockée et
la recherche/tri serveur gardent leur casse d'origine. **Ne jamais** mettre en
majuscules dans une requête, une action Domain ou une prop React : cela
corrompt la donnée. Les cellules devant garder leur casse exacte (emails,
usernames, références brutes) reçoivent `className="text-normal-case"`.

### 10.6 Navigation (sidebar)

6 groupes : Principal · Gestion des inscriptions · Suivi pédagogique ·
Gestion financière · Ressources humaines · Suivi établissement · Configuration.

Chaque item filtre sur la prop partagée `auth.permissions`, avec
`auth.isSuperAdmin` en court-circuit. Quelques pages sont **volontairement hors
sidebar** mais restent accessibles : `groups-historique` (masqué par décision
produit), `cheques` et `types-depenses` (atteints par les `PageTabs` des pages
finance).

---

## 11. Import de données

Pipeline en **2 phases** depuis les fichiers Excel de l'ancien CRM
(`openspout`), pour 3 modules : `students`, `inscriptions`, `encaissements`.

```
1. UPLOAD    → SheetReader lit l'Excel, localise la ligne d'en-tête
                  │
2. ANALYZE   → une ligne ImportRow par ligne de données, avec son statut :
                  NOUVEAU · DOUBLON · ERREUR · CONFLIT
               ⚠ LECTURE SEULE sur les tables cibles — n'écrit jamais dans
                 students/inscriptions/encaissements à cette phase
                  │
3. PREVIEW   → l'utilisateur voit chaque ligne, ses motifs, et sélectionne
                  │
4. COMMIT    → écrit par tranches (chunk) : seules les lignes NOUVEAU ou
               CONFLIT résolu passent. DOUBLON/ERREUR toujours ignorés,
               **revérifiés côté serveur** quel que soit le POST reçu.
               ImportResult::$remaining pilote la progression incrémentale
                  │
5. RESULT    → bilan ; RETRY-FAILED rejoue les lignes ECHEC_COMMIT
```

**Traçabilité :** `students`, `inscriptions` et `encaissements` portent
`legacy_ref` + `legacy_source` (`'ancien-crm'`), et `import_rows` conserve la
ligne brute (`jsonb`), les erreurs, la résolution et `created_model_type/id`.
On peut donc remonter de n'importe quelle donnée importée jusqu'à sa ligne
Excel d'origine. `import_batches` est audité (`ImportBatch` a le trait
`Auditable`).

État actuel : 3 lots, 4 237 lignes traitées.

---

## 12. Constats de l'audit

### 12.1 Vérifications passées ✅

| Contrôle | Résultat |
|---|---|
| **`artisan test`** | ⚠ **612 / 613** — 1 échec, test périmé (voir 12.3 #1) |
| **`npm run build`** | ✅ succès (442 ms) |
| **`npx tsc --noEmit`** | ✅ **0 erreur** |
| Invariant `CaisseLedger` (aucune écriture brute de `solde`) | ✅ 0 violation |
| Écritures d'argent append-only (dépenses/remb./chèques/transferts) | ✅ aucune route destroy |
| Groupes jamais supprimés (aucune route destroy) | ✅ |
| Journal d'audit en lecture seule (GET uniquement) | ✅ |
| Aucun import Alpine / jQuery / Select2 dans `resources/js/` | ✅ (noms de classes CSS uniquement) |
| Aucun `data-bs-toggle="modal"` / `data-bs-dismiss` actif | ✅ (uniquement des commentaires) |
| `theme-reference/` intact | ✅ `git status` propre |
| 31 modèles métier audités via `Auditable` | ✅ |
| Une seule connexion DB (`pgsql`), aucune branche driver | ✅ |
| Séparation Backoffice/Frontoffice respectée | ✅ |

> Détail de la suite : **613 tests, 612 passés, 3 110 assertions, ~15 min.**

### 12.2 Écarts entre la documentation et le code 📋

| # | Constat | Détail |
|---|---|---|
| 1 | **CLAUDE.md §15 est périmé** | Il décrit les vues d'auth comme `backoffice/auth/login.blade.php` sur `<x-backoffice.layout.guest>`. En réalité `resources/views/backoffice/auth/` **n'existe plus** : les 3 contrôleurs font `Inertia::render('Backoffice/Auth/…')` vers des pages React. À corriger dans CLAUDE.md. |
| 2 | **Décompte des permissions périmé** | CLAUDE.md §16 annonce « 61 permissions ». Le registre en déclare **95** et la base en contient **95** — parfaitement synchronisés. C'est uniquement le **chiffre de la documentation** qui a dérivé au fil des modules livrés depuis (import, recouvrement, chèques, banques, motifs d'annulation…). |
| 3 | **Un rôle `test` en base** | 7 rôles en base pour 6 au registre : un rôle `test` supplémentaire subsiste (issu d'une exécution de test ou d'un essai manuel). Inoffensif, mais à supprimer de la base de développement. |

### 12.3 Points d'attention techniques ⚠

| # | Point | Emplacement | Analyse |
|---|---|---|---|
| 1 | **Test périmé — suite rouge** | `ReadOnlyPagesInertiaTest.php:227` | `test_encaissement_show_has_no_delete_route` affirme `Route::has('backoffice.encaissements.destroy') === false`. **Cette route existe désormais** (ajoutée au commit `2663164`, derrière `permission:payments.delete`). Le test date d'avant cette évolution. **Ce n'est PAS une régression d'invariant** : la suppression passe par `SupprimerEncaissement`, qui est l'inverse exact de `EnregistrerEncaissement` — verrou de ligne, débit via `CaisseLedger`, recalcul du statut du frais, le tout en une transaction ; elle refuse les cas non dénouables (avance déjà appliquée, paiement par chèque suivi) et ne débite pas pour une ligne « apply » (qui n'avait rien crédité). **Action : mettre le test à jour** pour refléter la décision — la route existe mais n'est accessible à aucun rôle. |
| 2 | **Goulot d'étranglement connu — journal de caisse** | `GetCaisseJournal.php` (238 l.) | Fusionne 4 tables (encaissements, dépenses, remboursements, transferts) **en PHP** avec `collect()` + tri + `slice()`, sans pagination SQL. Porté **volontairement à l'identique** depuis le composant Livewire (phase 10) pour préserver la sémantique exacte. Devrait migrer vers un `UNION ALL` PostgreSQL avec pagination base. Documenté dans `PERFORMANCE_AUDIT.md` et CLAUDE.md §17. **Faible impact actuel** (561 encaissements), à traiter avant montée en charge. |
| 3 | **Un module livré sans aucun test** | `tests/Feature/Backoffice/` | **Recouvrement** (`RecouvrementController`, `GetRetardsList`) n'apparaît dans aucun fichier de test — `grep -rli recouvrement tests/` ne remonte rien. Banques et Motifs d'annulation, eux, sont bien couverts par `Settings/SettingsTest.php`. Les 49 fichiers de tests couvrent solidement le reste (finance, imports, autorisations, inscriptions, groupes, présences, stock). |
| 4 | **Tables pédagogiques vides** | `creneaux`, `seances`, `presences`, `inscription_livres`, `groups_historique`, `inscriptions_historique` = **0 ligne** | Le code est écrit et testé, mais le module n'a **jamais tourné sur des données réelles**. Le comportement en production reste à valider. |
| 5 | **`activity_log` = 4,82 Mo pour 11,39 Mo de base** | `activity_log` (4 437 lignes) | Le journal représente **42 % du volume**. C'est le prix normal de `logAll()` sur 31 modèles, mais la purge (`activitylog:clean`) doit être planifiée avant que ce ratio ne devienne problématique. |
| 6 | **Route `frais` mal singularisée** | `routes/backoffice.php` | Le paramètre est `{frai}` (Laravel singularise « frais » → « frai »). Fonctionne, mais mérite un `->parameters(['frais' => 'frais'])` comme cela a été fait pour `caisses`. |
| 7 | **Modification non commitée** | `AppliquerAvance.php` | Une ligne : ajout de `$restant` dans le `use()` de la closure de transaction. **Correctif nécessaire** — `$restant` est utilisé dans les propriétés du journal (`avance_restant_avant/apres`) et aurait été indéfini sans lui. À committer. |
| 8 | **Fichiers parasites à la racine** | `t.txt`, `liste-paiements_19_20260819.xlsx`, `old crm data exemple/` | Données de travail dans le dépôt. À déplacer hors du projet ou ajouter au `.gitignore` — l'export de paiements peut contenir des données personnelles. |
| 9 | **Identifiants de développement** | `AdminUserSeeder` | `admin@gls.test` / `password` — **local uniquement, à remplacer avant tout déploiement**. Un `DemoRoleUsersSeeder` existe aussi : ne jamais l'exécuter en production. |

### 12.4 Points forts remarquables 💪

1. **`CaisseLedger` est un modèle du genre.** Un seul point d'écriture,
   verrouillage de ligne, arithmétique complète journalisée, et une
   documentation dans le code qui explique *la faille de fraude qu'il ferme*.
   Cette traçabilité est meilleure que ce qu'on trouve dans la plupart des CRM.

2. **Le journal d'audit est infalsifiable au bon niveau.** Le refus
   d'`update`/`delete` est implémenté dans le **modèle**, donc sous tous les
   Gates — même un super-admin ne peut pas réécrire l'histoire. Et la
   résolution des noms à la *lecture* (jamais à l'écriture) fait qu'un renommage
   ultérieur ne réécrit pas le passé. Ces deux décisions sont subtiles et justes.

3. **La séparation des pouvoirs financiers est réelle**, pas décorative : elle
   est visible dans la matrice des rôles (demander ≠ valider), dans le refus
   d'auto-validation des transferts, et dans le fait que `payments.delete`
   n'est attribué à personne.

4. **Le pipeline d'import est prudent** : phase d'analyse en lecture seule,
   revérification serveur au commit, traçabilité complète jusqu'à la ligne
   Excel d'origine.

5. **La discipline architecturale tient.** 190 routes, 274 fichiers PHP, et
   toujours zéro logique métier dans un contrôleur, zéro validation inline,
   zéro branche driver de base de données, zéro import interdit côté frontend.

---

## 13. Annexe

### 13.1 Commandes (toujours depuis la racine, toujours PHP 8.4)

```powershell
Set-Location "C:\Users\ASUS\Desktop\Projects\crm gls"

C:\php84\php.exe C:\composer\composer.phar install
npm install ; npm run dev ; npm run build

C:\php84\php.exe artisan serve
C:\php84\php.exe artisan test
C:\php84\php.exe artisan route:list
C:\php84\php.exe artisan optimize:clear
npx tsc --noEmit
```

⚠ Plusieurs PHP coexistent sur la machine (XAMPP 8.2 dans le PATH, 8.3
préfixé par le profil PowerShell). **Dans tout script, utiliser
`C:\php84\php.exe` explicitement.**

### 13.2 Commandes artisan métier

| Commande | Rôle |
|---|---|
| `auth:assign-super-admin <email>` | Première attribution du super-admin |
| `seances:generate` | Génère les séances depuis les créneaux |
| `caisses:provision` | Provisionne les caisses des employés |
| `inscription-fees:repair-zero` | Répare les frais d'inscription à montant nul |
| `activitylog:clean` | **Seule** purge autorisée du journal |

### 13.3 Fichiers à lire en premier pour comprendre le système

| Ordre | Fichier | Pourquoi |
|---|---|---|
| 1 | `CLAUDE.md` | Les règles du projet (⚠ §15 périmé, voir 12.2) |
| 2 | `routes/backoffice.php` | Toute la surface de l'application (588 l.) |
| 3 | `app/Support/Authorization/PermissionRegistry.php` | Source unique de l'autorisation |
| 4 | `app/Domain/Finance/Support/CaisseLedger.php` | Le cœur financier |
| 5 | `app/Support/Audit/AuditLogRegistry.php` | Périmètre du journal |
| 6 | `app/Services/Context/CurrentContext.php` | Cadrage année + centre |
| 7 | `app/Services/Authorization/CenterAccessService.php` | Portée par centre |
| 8 | `app/Http/Middleware/HandleInertiaRequests.php` | Props partagées à chaque page |
| 9 | `resources/js/Layouts/BackofficeLayout.tsx` | Le shell de toutes les pages |
| 10 | `gls-crm-schema.md` | Rationnel du schéma de base |

### 13.4 Contrôle qualité avant de déclarer un travail terminé

1. `C:\php84\php.exe artisan test` passe
2. `npm run build` réussit
3. `npx tsc --noEmit` — 0 erreur
4. `artisan route:list` montre les routes correctement nommées
5. Les pages concernées s'affichent (desktop + mobile + mode sombre + RTL)
6. Aucune erreur console React
7. Fichiers dans la bonne aire (Backoffice vs Frontoffice) et le bon namespace
8. `theme-reference/` intact (`git status`)
9. Chaînes visibles passées par `__()` / `t()`, valeur FR ajoutée dans `lang/fr.json`

### 13.5 Documentation existante (`docs/`, 37 fichiers)

| Thème | Fichiers |
|---|---|
| Autorisation | `roles-and-permissions.md`, `authorization-architecture.md`, `authorization-audit.md`, `center-scoping.md` |
| Audit | `audit-journal.md` |
| Migration React | `inertia-react-migration-{plan,status,audit}.md`, `phase-5` → `phase-13`, `bootstrap-react-integration-decision.md` |
| Finance | `phase-10-finance-{audit,mapping}.md` |
| Performance | `phase-11-performance-baseline.md`, `phase12-performance-{audit,report}.md` |
| Thème | `phase13-preskool-ui-{audit,mapping,report}.md`, `react-theme-file-map.md` |
| Déploiement | `vps-deployment.md` |
| Racine | `gls-crm-schema.md`, `gls-crm-laravel-structure.md`, `POSTGRES_*.md`, `PERFORMANCE_*.md`, `PROJECT_INVENTORY.md` |

---

*Audit produit par scan intégral du dépôt — routes vérifiées par
`artisan route:list`, schéma par `information_schema`, invariants par `grep`,
typage par `tsc --noEmit`.*
