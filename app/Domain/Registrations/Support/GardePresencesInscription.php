<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Support;

use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Validation\ValidationException;

/**
 * « Ce dossier a-t-il vécu ? » — la garde qui autorise ou refuse de
 * transférer une inscription d'un étudiant vers un autre
 * (TransfererInscriptionVersEtudiant).
 *
 * Le cas métier (10/09/2026) : un étudiant s'inscrit, paie, puis ne vient
 * jamais ; sa sœur veut prendre sa place. Tant que RIEN n'a été consommé,
 * l'argent peut suivre le nouveau bénéficiaire. Dès que la place a été
 * occupée ne serait-ce qu'une fois, la prestation a commencé et le dossier
 * appartient définitivement à son étudiant : un transfert réécrirait alors
 * QUI a suivi le cours, ce que ni la pédagogie ni la comptabilité ne
 * peuvent tolérer.
 *
 * ⚠ TOUTE ligne d'appel bloque — Présent, Absent, Retard ET Justifié. Le
 * critère n'est pas « a-t-il assisté ? » mais « son nom a-t-il été appelé
 * dans ce groupe ? » : un « Absent » est une trace pédagogique du dossier
 * (il compte dans les absences du groupe, il a pu déclencher une relance),
 * et un enseignant qui a fait l'appel a constaté que la place était
 * attribuée à cette personne. Ne jamais assouplir vers
 * « Présent/Retard uniquement » : la règle deviendrait « transférable tant
 * que l'étudiant sèche », ce qui est exactement l'inverse de l'intention.
 *
 * Il n'existe aucune clé étrangère presences → inscriptions : une présence
 * porte (seance_id, student_id) et la séance porte group_id. Les présences
 * « de cette inscription » se dérivent donc du couple étudiant × groupe,
 * et c'est la SEULE définition retenue — la dupliquer ailleurs avec un
 * autre filtre (par dates, par statut de séance) ferait diverger l'écran
 * du refus.
 */
final class GardePresencesInscription
{
    /**
     * Le dossier n'a plus de groupe : on ne peut RIEN prouver sur ses
     * présences (les séances sont parties avec le groupe). Distinct de 0,
     * qui affirme « jamais consommé ».
     */
    public const INDETERMINE = -1;

    /**
     * Nombre de lignes d'appel portées par cette inscription, tous statuts
     * confondus. 0 = le dossier n'a jamais été consommé.
     *
     * Les séances ANNULÉES sont incluses délibérément : si une ligne
     * d'appel existe malgré l'annulation, c'est que quelqu'un a bel et bien
     * fait l'appel — la trace prime sur le statut de la séance.
     */
    public function compter(Inscription $inscription): int
    {
        // ⚠ `inscriptions.group_id` est NULLABLE (`nullOnDelete` : supprimer
        // un groupe détache ses inscriptions). Sans ce garde-fou, la
        // sous-requête `where('group_id', null)` ne trouverait aucune séance
        // et renverrait 0 — c'est-à-dire « jamais consommé » — pour un
        // dossier dont le groupe et ses séances viennent justement d'être
        // effacés. Le compte serait faux dans le sens le plus dangereux :
        // celui qui AUTORISE.
        //
        // On ne peut plus rien PROUVER sur un tel dossier, donc on ne
        // l'autorise pas : -1 signale « indéterminé » et
        // assurerAucunePresence() refuse.
        if ($inscription->group_id === null) {
            return self::INDETERMINE;
        }

        return Presence::query()
            ->where('student_id', $inscription->student_id)
            ->whereIn(
                'seance_id',
                Seance::query()->select('id')->where('group_id', $inscription->group_id),
            )
            ->count();
    }

    /**
     * ⚠ « Pas de présence prouvée » n'est PAS « zéro présence » : un dossier
     * INDETERMINE (groupe supprimé) répond true ici, parce que la seule
     * réponse sûre à « peut-on garantir qu'il n'a jamais été appelé ? » est
     * non. Utiliser `compter() > 0` à la place ferait passer ce cas pour un
     * dossier vierge.
     */
    public function aDesPresences(Inscription $inscription): bool
    {
        return $this->compter($inscription) !== 0;
    }

    /**
     * Refuse le transfert si la moindre ligne d'appel existe.
     *
     * ⚠ À appeler DANS la transaction du transfert, jamais avant : entre un
     * contrôle fait à l'extérieur et l'écriture, un enseignant peut valider
     * un appel (CLAUDE.md §11 — tout « je lis puis j'écris » se fait dans la
     * transaction). Le coût est une requête de plus, la garantie est qu'un
     * dossier ne peut pas être transféré et rempli d'appels dans le même
     * instant.
     *
     * @param  string  $champ  le champ de formulaire qui portera l'erreur
     */
    public function assurerAucunePresence(Inscription $inscription, string $champ = 'inscription'): void
    {
        $nombre = $this->compter($inscription);

        if ($nombre === 0) {
            return;
        }

        if ($nombre === self::INDETERMINE) {
            throw ValidationException::withMessages([
                $champ => __("This registration is no longer attached to a group: its attendance can no longer be verified, so the transfer cannot be allowed."),
            ]);
        }

        throw ValidationException::withMessages([
            $champ => __(
                'This registration cannot be transferred: :count attendance record(s) already exist for this student in this group. A registration that has been attended belongs to its student.',
                ['count' => $nombre],
            ),
        ]);
    }
}
