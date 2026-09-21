# Écrans hors de la barre latérale

Inventaire des pages du backoffice **atteignables par leur URL mais absentes
de la barre latérale** (`resources/js/Config/backofficeNavigation.ts`).

> ⚠ **Un écran absent du menu n'est pas un écran protégé.** L'absence d'entrée
> de menu est une décision d'**ergonomie**, jamais de sécurité : la route reste
> atteignable par son URL, donc elle porte son `permission:` / `can:` comme
> n'importe quelle autre, et le contrôleur revérifie (CLAUDE.md §5, §16).
> Ne jamais « sécuriser » un écran en le retirant du menu.

Relevé du 21/09/2026, dérivé de `artisan route:list` croisé avec
`backofficeNavigation.ts`. Seules les routes **GET sans paramètre** sont
listées — une page qu'on peut ouvrir en tapant son adresse.

---

## 1. Outils réservés au compte de maintenance

Identité, pas permission : l'ability est dans
`AppServiceProvider::MAINTAINER_ONLY_ABILITIES`, décidée **au-dessus** du
bypass super-admin — le CEO lui-même reçoit 403.

| Écran | URL | Garde |
|---|---|---|
| Gestion de la base de données | `/backoffice/database-management` | `can:database.manage` |
| Réconciliation des paiements importés | `/backoffice/reconciliation-paiements` | `can:legacy-payments.reconcile` |

Tous deux réécrivent des données en contournant les actions Domain : outils de
réparation, pas écrans d'exploitation.

## 2. Outils ponctuels (volontairement hors menu)

| Écran | URL | Permission |
|---|---|---|
| Échéances en masse | `/backoffice/bulk-echeance` | `fee-due-dates.bulk-update` (tous les rôles) |
| Calcul « Paiement prof » | `/backoffice/paiement-prof` | `prof-payments.calculate` |
| Fusion de fiches étudiant | `/backoffice/students/fusion` | `students.merge` (super-admin) |
| Déplacer des encaissements | `/backoffice/encaissements/reaffecter` | `payments.reallocate` (super-admin) |

`bulk-echeance` et `paiement-prof` n'écrivent aucun argent — le premier ne
touche que `inscription_fees.date_echeance`, le second est en **lecture seule**
(il propose un montant, le paiement reste une dépense ordinaire).

## 3. Pages masquées à la demande (entrée commentée dans le menu)

L'entrée existe dans `backofficeNavigation.ts` mais est mise en commentaire —
décommenter la remet dans la barre latérale.

| Écran | URL | Permission |
|---|---|---|
| Journal d'audit | `/backoffice/9wiwid` | `audit-logs.view` |
| Permissions (lecture seule) | `/backoffice/permissions` | `permissions.view` |
| Import de données | `/backoffice/import` | `import.view` |
| Historique des groupes | `/backoffice/groups-historique` | `auth` (+ `groups.view` au contrôleur) |

Sous-écrans de l'import, même permission `import.view` :
`/backoffice/import/{students,inscriptions,encaissements,presences,combine}`.

## 4. Pages atteintes par les onglets d'une page visible

Pas d'entrée propre : on y arrive par la barre d'onglets (`PageTabs`) de la page
parente. Elles restent ouvrables par leur URL.

| Écran | URL | Permission | Parent |
|---|---|---|---|
| Chèques | `/backoffice/cheques` | `cheques.view` | Gestion des paiements |
| Types de dépenses | `/backoffice/types-depenses` | `can:viewAny` | Gestion des dépenses |
| Absence par groupe | `/backoffice/seances/absence-par-groupe` | `attendance.view` | Séances |
| Saisir une absence | `/backoffice/seances/saisir-absence` | `attendance.view` | Séances |

## 5. Redirections vers l'onglet d'une page visible

Ces URLs ne rendent rien : elles redirigent. Listées pour qu'on ne les prenne
pas pour des pages perdues.

| URL | Redirige vers |
|---|---|
| `/backoffice/caisse-transfers` | `caisses.index?tab=transferts` |
| `/backoffice/remboursements` | `depenses.index?tab=remboursements` |

## 6. Panneaux de la page Paramètres

Les catalogues référentiels sont des onglets React de `/backoffice/settings`.
Leurs routes resource subsistent comme **points d'entrée protégés** que les
onglets appellent — chacune garde sa propre permission.

| URL | Garde |
|---|---|
| `/backoffice/etablissements` (+ `/create`) | `can:viewAny` / `can:create` |
| `/backoffice/annees-scolaires` (+ `/create`) | `can:viewAny` / `can:create` |
| `/backoffice/salles` (+ `/create`) | `can:viewAny` / `can:create` |
| `/backoffice/frais` (+ `/create`) | `can:viewAny` / `can:create` |
| `/backoffice/banques` (+ `/create`) | `can:viewAny` (super-admin) |
| `/backoffice/motifs-annulation` (+ `/create`) | `can:viewAny` (super-admin) |

## 7. Sorties de fichier et pages hors navigation

Pas des écrans : elles renvoient un fichier ou sont atteintes par un lien
contextuel.

| URL | Nature | Permission |
|---|---|---|
| `/backoffice/rapports/pdf` | Téléchargement PDF | `reports.view` |
| `/backoffice/rapports/excel` | Téléchargement Excel | `reports.view` |
| `/backoffice/seances/absence-par-groupe/export` | Export | `attendance.view` |
| `/backoffice/encaissements/recu-groupe` | Reçu groupé | `payments.view` |
| `/backoffice/encaissements/recu-groupe/whatsapp` | Lien WhatsApp | `payments.view` |
| `/backoffice/profile` | Profil du connecté | `auth` (aucune permission) |
| `/backoffice/roles/create` | Formulaire | `roles.create` |

## 8. Routes d'authentification (`guest`)

| URL | Route |
|---|---|
| `/backoffice/login` | `backoffice.login` |
| `/backoffice/forgot-password` | `backoffice.password.request` |

---

## Régénérer cet inventaire

```powershell
Set-Location "C:\Users\ASUS\Desktop\Projects\crm gls"
C:\php84\php.exe artisan route:list --json
```

Croiser les URLs `GET /backoffice/*` sans paramètre avec les `href` de
`resources/js/Config/backofficeNavigation.ts` : la différence est cette liste.
