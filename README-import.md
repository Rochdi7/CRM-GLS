# Import de données — ancien CRM

Module d'import Excel (`.xlsx`) permettant de migrer les données de
l'ancien CRM vers GLS CRM : Étudiants, Inscriptions, Encaissements,
Présences & séances.

Interface : `/backoffice/import` (permission `import.view` pour consulter,
`import.create` pour importer). Chaque module est un flux en plusieurs
étapes : téléverser → (associer les Groupes/Opérateurs si nécessaire) →
analyser → prévisualiser → insérer → résultat.

## Ordre d'import obligatoire

1. **Étudiants** (`/backoffice/import/students`)
2. **Inscriptions** (`/backoffice/import/inscriptions`) — nécessite que les
   étudiants référencés soient déjà importés (résolution automatique par
   nom complet normalisé, jamais par correspondance approximative).
3. **Encaissements** (`/backoffice/import/encaissements`) — nécessite que
   l'inscription active correspondante existe déjà pour le Centre + Année
   scolaire sélectionnés.
4. **Présences & séances** (`/backoffice/import/presences`) — nécessite que
   les élèves et les groupes référencés existent déjà (résolution par nom
   complet normalisé pour l'élève, association manuelle pour le groupe).

Respecter cet ordre à chaque fois. Un import d'Encaissements avant les
Inscriptions correspondantes se solde par des lignes ERREUR
(`no_active_inscription`), jamais par une insertion incorrecte.

## Centre + Année scolaire — portée obligatoire et immuable

Chaque lot d'import (`import_batches`) exige la sélection explicite d'un
**Centre** et d'une **Année scolaire** avant l'analyse. Cette portée :

- est validée côté serveur (le Centre choisi doit être accessible à
  l'utilisateur — `CenterAccessService`, sinon `403`) ;
- ne peut plus être modifiée une fois le lot analysé (aucune route ne le
  permet — créer un nouveau lot pour une autre portée) ;
- s'applique à **toute** la résolution/déduplication (étudiant, groupe,
  inscription, frais) : jamais de correspondance par nom seul, jamais de
  repli sur un autre centre ou une autre année.

## Règles importantes

- **Le catalogue `frais` n'est jamais modifié par l'import.** Les frais
  d'une inscription proviennent toujours des `group_frais` du groupe résolu
  (mêmes lignes que celles affichées par le formulaire d'inscription
  normal). Un libellé de frais introuvable sur l'inscription active
  aboutit à un statut **CONFLIT** avec un sélecteur manuel — jamais une
  création automatique de catalogue.
- **Aucune "Caisse" n'est choisie manuellement.** Elle est toujours dérivée
  de l'employé associé à l'Opérateur du fichier (comme le reste de
  l'application : `$employee->caisses()->first() ?? CaisseProvisioner`).
- **Les références (`reference`) sont toujours générées par le système**
  (`ETU-`, `INS-`, `ENC-`). L'ancien identifiant de l'ancien CRM est
  conservé dans `legacy_ref`/`legacy_source`, jamais écrasé dans
  `reference`.
- **La création d'un nouveau Groupe** (bouton "créer le groupe" dans le
  mapping Inscriptions) synchronise automatiquement **tous** les frais
  catalogue actifs sur ce groupe (montant 0 par défaut), exactement comme
  la création normale d'un groupe — jamais un sous-ensemble choisi à la
  main.
- **Chaque étudiant a une ligne distincte par Centre.** Un même nom dans
  deux centres différents n'est jamais fusionné.

## Présences & séances — le seul module qui écrit deux tables

Le « Registre des présences » n'a pas de colonne « séance » : chaque ligne
est un appel (`Élève | Groupe | Matière | Date | Horaire | Statut |
Enseignant`). Les séances sont donc **déduites** du fichier :

- un `Seance` par triplet distinct **(groupe, date, heure de début)** ;
- un `Presence` par ligne, sur cette séance.

Règles non négociables de ce module :

- **Une séance déjà enregistrée est réutilisée, jamais dupliquée ni
  réécrite.** Qu'elle ait été créée à la main ou générée depuis un créneau,
  ni son `statut`, ni ses horaires, ni son enseignant ne sont modifiés par
  l'import — c'est l'enregistrement de l'application, et l'écraser depuis un
  fichier ancien détruirait des corrections manuelles. Seul l'appel manquant
  est complété.
- **Une séance créée par l'import est marquée « Effectuée ».** Un registre
  ne liste que des séances qui ont réellement eu lieu ; les laisser
  « Prévue » afficherait des centaines de séances passées comme à venir.
- **Deux horaires le même jour = deux séances.** Un `heure_debut` vide est
  une identité à part entière, jamais un joker : le confondre avec n'importe
  quelle heure fusionnerait deux sessions réellement distinctes.
- **L'identité d'une ligne est (groupe, date, horaire, élève)** — la même clé
  que l'index `UNIQUE (seance_id, student_id)` de `presences`. Le fichier ne
  porte aucune référence propre : `legacy_ref` reste donc `null` sur ce
  module.
- **La colonne « Enseignant » est indicative.** Un nom introuvable ou
  ambigu ne bloque jamais la ligne : la séance retombe simplement sur
  l'enseignant du groupe. L'import ne crée jamais d'employé.
- **Un statut absent (le `-` du fichier) est une ERREUR visible**, jamais un
  « Présent » par défaut.
- L'import ne crée jamais d'élève ni d'inscription : un élève introuvable
  dans le centre est un **CONFLIT** à corriger, pas une création silencieuse.

## Réimporter le même fichier

Réimporter un fichier déjà traité (même Centre + Année) doit toujours
aboutir à **0 nouvelle ligne insérée** — chaque ligne apparaît en
**DOUBLON** dans l'aperçu. Ce comportement est testé automatiquement
(`tests/Feature/Backoffice/Import/*ImportTest.php`,
`test_reupload_is_idempotent`).

## Annulation / rollback

Il n'existe **pas** de bouton "annuler l'import" automatique — cohérent
avec la règle générale de l'application : les enregistrements financiers
(`encaissements`) ne sont jamais supprimés, et les groupes ne le sont
jamais non plus.

Pour annuler un lot après coup :

1. Retrouver ses lignes via `import_rows` (`import_batch_id`), qui
   contiennent `created_model_type`/`created_model_id` pour chaque ligne
   effectivement insérée (`status = INSERE`).
2. **Étudiants / Inscriptions créés par erreur** : supprimer les
   enregistrements correspondants via l'interface normale (Étudiants /
   Inscriptions), qui applique les mêmes garde-fous que d'habitude
   (blocage si des paiements/activité existent).
3. **Encaissements importés par erreur** : ne jamais les supprimer
   directement. Créer un **Remboursement** compensatoire, exactement comme
   pour corriger un paiement enregistré manuellement par erreur.
4. **Groupes créés par le mapping "créer le groupe"** : s'ils n'ont plus
   d'inscriptions actives après nettoyage, ils peuvent être archivés
   normalement (`Group::archiverCommeTermine()`), jamais supprimés.

## Détails techniques (pour développement futur)

- `app/Services/Import/SheetReader.php` — détection d'en-tête (scan des 20
  premières lignes, recherche `N°`/`N° d'ordre` + `Réf`/`Réf.`/`Élève` — le
  registre des présences n'a aucune colonne `Réf`, c'est `Élève` qui ancre
  son en-tête) et coupure de pied de page (première ligne dont la colonne A
  n'est pas un entier positif).
- `app/Services/Import/Support/CellNormalizer.php` — fonctions pures
  (dates, plages horaires « 10:00 - 12:30 », montants, téléphones,
  dédoublement des noms payeurs).
- `app/Services/Import/{Student,Inscription,Encaissement,Presence}Importer.php` —
  un `analyze()` (lecture seule, calcule le statut par ligne) et un
  `commit()` (écrit uniquement les lignes sélectionnées et éligibles) par
  entité, implémentant `App\Services\Import\Contracts\Importer`.
- `import_batches`/`import_rows` — tables de staging ; `import_rows.raw`
  (jsonb) conserve les valeurs telles que lues du fichier, `resolution`
  (jsonb) les correspondances résolues (student_id, group_id, frais
  candidats, caisse…), `errors` (jsonb) les raisons de blocage/CONFLIT.
- Le commit reste **synchrone** (pas de file d'attente) — ce module est le
  premier de l'application à envisager une exécution en tâche de fond ; à
  ajouter uniquement si des fichiers réels dépassent ~500 lignes.
