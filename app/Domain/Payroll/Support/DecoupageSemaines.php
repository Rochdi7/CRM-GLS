<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Support;

use Illuminate\Support\Carbon;

/**
 * Répartit les JOURS DE COURS d'une période sur exactement 4 « semaines de
 * paie » (buckets 1..4).
 *
 * ⚠ Le découpage se fait sur les jours qui ONT EU COURS — jamais sur le
 * calendrier. C'est toute la subtilité, et elle est délibérée (portée depuis
 * le portail GLS, `ProfPaymentCalculationService::buildWeekMap()`) :
 *
 *   Un mois réel ne se divise pas en 4 semaines égales. Une semaine écourtée
 *   par un jour férié peut n'avoir qu'UN seul jour de cours ; si elle consomme
 *   l'un des 4 créneaux de paie, ce créneau devient MATHÉMATIQUEMENT
 *   INGAGNABLE (1 jour de cours < seuil de 3), et l'enseignant perd un quart
 *   de sa rémunération pour un férié dont il n'est pas responsable.
 *
 * La règle est donc : une semaine trop courte pour qualifier à elle seule
 * REJOINT le bucket courant au lieu d'en ouvrir un nouveau. Un nouveau bucket
 * ne s'ouvre que lorsque le bucket courant a déjà accumulé assez de jours de
 * cours pour être gagnable. Les semaines au-delà du 4e bucket sont repliées
 * dans le 4e, et un dernier bucket trop court fusionne avec le précédent.
 *
 * Cette classe est la SEULE définition du découpage : le calculateur et le
 * read-model qui dessine la grille la partagent, sinon l'écran montrerait des
 * colonnes que le total ne compte pas (CLAUDE.md §5).
 */
final class DecoupageSemaines
{
    /** Nombre de buckets de paie — toujours 4, jamais plus, jamais moins. */
    public const NOMBRE_SEMAINES = 4;

    /**
     * Plafond de jours comptés par semaine ISO : l'école n'ouvre pas le
     * week-end, une semaine ne peut donc pas légitimement porter plus de 5
     * présences. Protège aussi le 4e bucket, qui absorbe une éventuelle 5e
     * semaine ISO.
     */
    public const JOURS_MAX_PAR_SEMAINE = 5;

    /**
     * Associe chaque semaine ISO (« GGGG-WW ») à son bucket de paie (1..4).
     *
     * @param  list<Carbon|string>  $datesDeCours  dates ayant eu au moins un appel
     * @return array<string, int>
     */
    public static function construire(array $datesDeCours, int $seuil): array
    {
        // Jours de cours DISTINCTS par semaine ISO, en ordre chronologique.
        $joursParSemaine = [];

        $dates = array_map(
            static fn (Carbon|string $d): Carbon => $d instanceof Carbon ? $d : Carbon::parse($d),
            $datesDeCours,
        );
        usort($dates, static fn (Carbon $a, Carbon $b): int => $a->getTimestamp() <=> $b->getTimestamp());

        $vues = [];
        foreach ($dates as $date) {
            $jour = $date->toDateString();
            if (isset($vues[$jour])) {
                continue;
            }
            $vues[$jour] = true;

            $cle = $date->isoFormat('GGGG-WW');
            $joursParSemaine[$cle] = ($joursParSemaine[$cle] ?? 0) + 1;
        }

        $map = [];
        $bucket = 0;
        $joursOuverts = 0; // jours de cours accumulés dans le bucket courant

        foreach ($joursParSemaine as $cle => $nombre) {
            // On n'ouvre un nouveau bucket QUE si le courant est déjà
            // qualifiable ; sinon cette semaine le rejoint.
            if ($bucket === 0 || ($joursOuverts >= $seuil && $bucket < self::NOMBRE_SEMAINES)) {
                $bucket++;
                $joursOuverts = 0;
            }

            $map[$cle] = $bucket;
            $joursOuverts += $nombre;
        }

        // Un dernier bucket trop court pour jamais qualifier fusionne avec le
        // précédent — sinon la dernière semaine du mois est perdue d'avance.
        if ($bucket > 1 && $joursOuverts < $seuil) {
            foreach ($map as $cle => $b) {
                if ($b === $bucket) {
                    $map[$cle] = $bucket - 1;
                }
            }
        }

        return $map;
    }

    /**
     * Buckets ayant REÇU au moins un jour de cours.
     *
     * ⚠ Divergence ASSUMÉE d'avec le portail GLS (21/09/2026) — c'est un
     * correctif, pas un oubli de portage.
     *
     * Une semaine écourtée par un férié fusionne avec la précédente (voir
     * `construire()`). Mais la fusion laisse alors MOINS de 4 buckets
     * occupés : sur un mois à 1 jour férié + 3 semaines pleines, les jours
     * tiennent dans 3 buckets et le 4e reste VIDE. Le portail comptait
     * quand même ce bucket vide comme une semaine non qualifiée, si bien
     * qu'un étudiant présent à TOUS les cours du mois ne rapportait que
     * 375 DH sur 500 — l'enseignant perdait un quart de sa paie à cause
     * d'un férié dont il n'est pas responsable, et rien à l'écran ne
     * l'expliquait.
     *
     * Un bucket sans aucun jour de cours ne correspond à AUCUNE semaine
     * enseignée : il ne peut donc ni être gagné, ni être perdu. Seuls les
     * buckets réellement occupés entrent dans le calcul, et la part de
     * chacun est le montant par étudiant divisé par LEUR nombre — de sorte
     * qu'un étudiant présent partout vaut toujours exactement le montant
     * par étudiant, férié ou non.
     *
     * @param  array<string, int>  $map  semaine ISO => bucket
     * @return list<int>
     */
    public static function bucketsOccupes(array $map): array
    {
        $occupes = array_values(array_unique(array_values($map)));
        sort($occupes);

        return $occupes;
    }

    /**
     * Compte, par bucket, les jours retenus d'un étudiant — chaque semaine ISO
     * plafonnée à 5 jours.
     *
     * @param  array<string, int>  $map              semaine ISO => bucket
     * @param  array<string, int>  $joursParSemaine  semaine ISO => jours comptés
     * @return array<int, int>
     */
    public static function compter(array $map, array $joursParSemaine): array
    {
        $comptes = array_fill(1, self::NOMBRE_SEMAINES, 0);

        foreach ($map as $cle => $bucket) {
            if (! isset($joursParSemaine[$cle])) {
                continue;
            }

            // Le plafond s'applique PAR SEMAINE ISO, avant l'addition dans le
            // bucket : replier une 5e semaine dans le bucket 4 ne doit pas
            // permettre d'y compter 10 jours.
            $comptes[$bucket] += min($joursParSemaine[$cle], self::JOURS_MAX_PAR_SEMAINE);
        }

        return $comptes;
    }
}
