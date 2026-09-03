<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Support;

use App\Models\Group;
use Illuminate\Support\Carbon;

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

    /**
     * @return array{code: string, titre: string, message: string, action: string}|null
     *         null = le groupe génère normalement ses séances.
     */
    public function __invoke(Group $group, int $creneauxTotal, int $creneauxOuverts): ?array
    {
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

        return null;
    }
}
