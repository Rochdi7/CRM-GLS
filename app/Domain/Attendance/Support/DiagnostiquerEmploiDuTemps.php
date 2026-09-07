<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Support;

use App\Models\Creneau;
use App\Models\Group;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Explique POURQUOI un groupe ne génère plus de séances automatiques.
 *
 * `seances:generate` (GenererSeancesDepuisCreneau) tourne chaque matin à 08:00
 * et refuse de produire une séance dans quatre cas — mais ce refus était
 * silencieux : le personnel ne voyait qu'un emploi du temps vide et ressaisissait
 * chaque séance à la main sans jamais savoir ce qui bloquait (signalé le
 * 03/09/2026 par Rabat, 9 groupes concernés sur 4 centres). Cette classe rend
 * ces quatre causes visibles à l'écran, avec la marche à suivre pour chacune.
 *
 * ⚠ C'est un READ-MODEL : il ne fait que REFLÉTER les conditions de l'action,
 * il n'en redéfinit aucune (CLAUDE.md — « une liste ne redérive jamais une
 * règle métier »). Toute modification des refus dans
 * GenererSeancesDepuisCreneau doit être répercutée ici, sinon l'écran ment.
 *
 * Les compteurs de créneaux sont passés en paramètres pour que la LISTE des
 * groupes puisse les fournir via withCount() — jamais une requête par ligne.
 */
final class DiagnostiquerEmploiDuTemps
{
    public const AUCUN_CRENEAU = 'aucun_creneau';

    public const CRENEAUX_FERMES = 'creneaux_fermes';

    public const DATE_DEBUT_MANQUANTE = 'date_debut_manquante';

    public const FORMATION_TERMINEE = 'formation_terminee';

    public const CRENEAUX_PARTIELS = 'creneaux_partiels';

    public const CRENEAUX_DOUBLES = 'creneaux_doubles';

    public function __construct(
        private readonly DetecteurCreneauxDoubles $doubles,
    ) {}

    /**
     * @return array{code: string, titre: string, message: string, action: string}|null
     *         null = le groupe génère normalement ses séances.
     */
    public function __invoke(
        Group $group,
        int $creneauxTotal,
        int $creneauxOuverts,
        ?Collection $doublonsPrecalcules = null,
    ): ?array {
        // Un groupe terminé ou annulé n'est PAS censé générer des séances :
        // c'est l'état normal de fin de vie, pas une anomalie à signaler.
        if (in_array($group->statut, Group::STATUTS_HISTORIQUE, true)) {
            return null;
        }

        // 1. Aucune date de début : le générateur n'a rien sur quoi s'ancrer,
        //    il refuse plutôt que de créer des séances avant le vrai début.
        if ($group->date_debut_formation === null) {
            return [
                'code' => self::DATE_DEBUT_MANQUANTE,
                'titre' => "Aucune séance n'est générée pour ce groupe.",
                'message' => "Le groupe n'a pas de date de début de formation. Sans elle, "
                    . "impossible de savoir à partir de quel jour créer les séances.",
                'action' => "Renseignez la date de début du groupe (bouton « Modifier »).",
            ];
        }

        // 2. Fin de formation dépassée : la génération s'arrête à cette date.
        if ($group->date_fin_formation !== null && $group->date_fin_formation->lt(Carbon::today())) {
            return [
                'code' => self::FORMATION_TERMINEE,
                'titre' => "Aucune séance n'est générée pour ce groupe.",
                'message' => sprintf(
                    "La date de fin de formation (%s) est dépassée. La génération s'arrête à cette date.",
                    $group->date_fin_formation->format('d/m/Y'),
                ),
                'action' => "Prolongez la date de fin du groupe si la formation continue, "
                    . "sinon terminez la formation.",
            ];
        }

        // 3. Aucun créneau : l'emploi du temps n'a jamais été saisi.
        if ($creneauxTotal === 0) {
            return [
                'code' => self::AUCUN_CRENEAU,
                'titre' => "Ce groupe n'a pas d'emploi du temps.",
                'message' => "Aucun créneau n'a été saisi : les séances ne peuvent pas être générées "
                    . "automatiquement et doivent être créées une par une à la main.",
                'action' => "Saisissez les créneaux hebdomadaires du groupe (jour, horaire, salle).",
            ];
        }

        // 4. Tous les créneaux clôturés — la régression du 03/09/2026 (voir
        //    ChangerEnseignantGroupe) et, légitimement, un changement
        //    d'enseignant dont le nouvel emploi du temps n'a pas encore été saisi.
        if ($creneauxOuverts === 0) {
            // ⚠ Ne PAS annoncer un changement d'enseignant qui n'a pas eu lieu.
            // Le groupe n'a qu'une seule période d'affectation : personne n'est
            // parti, ses créneaux ont été clôturés à tort par l'ancienne
            // version de ChangerEnseignantGroupe, qui traitait la PREMIÈRE
            // affectation comme un changement (corrigé le 03/09/2026). Écrire
            // « lors d'un changement d'enseignant » sur cette fiche
            // contredirait l'historique affiché juste en dessous et enverrait
            // l'utilisateur chercher un changement inexistant.
            $plusieursPeriodes = $group->enseignants()->count() > 1;

            return [
                'code' => self::CRENEAUX_FERMES,
                'titre' => "Ce groupe n'a plus d'emploi du temps actif.",
                'message' => $plusieursPeriodes
                    ? "Tous ses créneaux ont été clôturés lors d'un changement d'enseignant. "
                        . "Tant qu'un nouvel emploi du temps n'a pas été saisi, aucune séance n'est "
                        . "générée automatiquement."
                    : "Tous ses créneaux ont été clôturés alors qu'aucun changement d'enseignant n'a "
                        . "eu lieu — un défaut corrigé depuis. Tant que l'emploi du temps n'a pas été "
                        . "rouvert, aucune séance n'est générée automatiquement.",
                'action' => $plusieursPeriodes
                    ? "Supprimez les anciens créneaux et saisissez ceux de l'enseignant actuel."
                    : "Rouvrez les créneaux existants, ou faites-les rouvrir en masse avec la commande "
                        . "« groupes:reouvrir-emploi-du-temps ».",
            ];
        }

        // 5. Clôture PARTIELLE — le piège le plus discret : il reste des
        //    créneaux ouverts, donc le groupe génère bien des séances… mais
        //    seulement certains jours. OUASSIMA 13H et HERR ABDESSAMAD 10H
        //    (Marrakech, 03/09/2026) avaient le lundi ouvert et le mardi au
        //    vendredi fermés au 01/09 : rien ne signalait les quatre jours
        //    manquants, ni à l'écran ni dans le premier balayage.
        //
        //    ⚠ Ce qui manque se compte en JOURS DÉCOUVERTS, jamais en créneaux
        //    clôturés (signalé le 07/09/2026 sur « Yassine SEPT 10H »). Après un
        //    changement d'enseignant, le sortant laisse un créneau clos et
        //    l'entrant en a saisi un ouvert le MÊME jour : 5 clos sur 10 alors
        //    que les cinq jours sont couverts et que rien ne manque. Comparer
        //    les deux compteurs annonçait « emploi du temps incomplet » sur un
        //    groupe parfaitement à jour, et envoyait rouvrir des créneaux
        //    périmés — soit exactement le doublon que le détecteur signale
        //    juste après. Seul un jour SANS aucun créneau ouvert est un trou.
        $joursDecouverts = $this->joursSansCreneauOuvert($group);

        if ($joursDecouverts !== []) {
            $noms = array_map(
                static fn (int $jour): string => Creneau::JOURS[$jour] ?? (string) $jour,
                $joursDecouverts,
            );

            return [
                'code' => self::CRENEAUX_PARTIELS,
                'titre' => "L'emploi du temps de ce groupe est incomplet.",
                'message' => sprintf(
                    "Aucune séance n'est générée le %s : le ou les créneaux de ce jour sont clôturés "
                        . "et rien ne les remplace, alors que les autres jours fonctionnent normalement.",
                    implode(', ', $noms),
                ),
                'action' => "Saisissez le créneau qui remplace celui de ce jour dans l'emploi du temps, "
                    . "ou rouvrez l'ancien s'il est toujours d'actualité.",
            ];
        }

        // 6. Créneaux EN DOUBLE — le groupe génère bien ses séances, mais deux
        //    fois par jour. Contrairement aux cinq cas ci-dessus, rien n'est
        //    « manquant » : l'écran a l'air correct, seul l'appel se présente
        //    en double, ce qui se remarque tard et fausse les présences. C'est
        //    donc le dernier test — un vrai blocage prime toujours — mais il
        //    doit exister, sinon le doublon reste invisible sur la fiche du
        //    groupe (signalé le 07/09/2026 sur « Ilyass sept 19H » : cinq
        //    créneaux saisis le 02/09, cinq identiques le 04/09).
        // La LISTE passe ses doublons déjà calculés en une seule requête
        // (doublonsParGroupe) ; la FICHE, qui n'affiche qu'un groupe, laisse
        // le détecteur les chercher. Jamais une requête par ligne de liste.
        $doublons = $doublonsPrecalcules ?? $this->doubles->doublons($group->id);

        if ($doublons->isNotEmpty()) {
            $jours = $doublons
                ->map(fn ($c): string => sprintf(
                    '%s %s',
                    Creneau::JOURS[(int) $c->jour_semaine] ?? (string) $c->jour_semaine,
                    substr((string) $c->heure_debut, 0, 5),
                ))
                ->unique()
                ->implode(', ');

            return [
                'code' => self::CRENEAUX_DOUBLES,
                'titre' => "L'emploi du temps de ce groupe est saisi en double.",
                'message' => sprintf(
                    "%d créneau(x) font double emploi (%s) : la même classe est planifiée deux fois, "
                        . "donc deux séances sont créées chaque jour concerné et l'appel apparaît en double.",
                    $doublons->count(),
                    $jours,
                ),
                'action' => "Supprimez les créneaux en trop dans l'emploi du temps du groupe "
                    . "(les séances futures encore « Prévue » partiront avec eux).",
            ];
        }

        return null;
    }

    /**
     * Les jours de la semaine où ce groupe a un créneau CLÔTURÉ sans aucun
     * créneau ouvert pour le remplacer — les seuls jours réellement muets.
     *
     * @return array<int, int> numéros de jour, dans l'ordre de la semaine
     */
    private function joursSansCreneauOuvert(Group $group): array
    {
        $parJour = $group->creneaux()
            ->get(['jour_semaine', 'date_fin'])
            ->groupBy('jour_semaine');

        $decouverts = [];

        foreach ($parJour as $jour => $creneaux) {
            if ($creneaux->every(fn ($creneau): bool => $creneau->date_fin !== null)) {
                $decouverts[] = (int) $jour;
            }
        }

        sort($decouverts);

        return $decouverts;
    }
}
