<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Validation\ValidationException;

/**
 * Efface les appels FANTÔMES d'une inscription — les lignes de présence
 * d'un étudiant qui n'a jamais mis les pieds dans la salle (21–23/09/2026).
 *
 * LE CAS RÉEL, trois fois en une semaine : un dossier saisi sur le mauvais
 * nom au guichet (NABILA FELLAHI pour NABILA ROCHD, MALAK pour AYA AGDALI,
 * MANAR RHAZALI pour YASSIR ABIH). L'enseignant fait l'appel sur la liste
 * qu'on lui donne, donc le nom fantôme reçoit un « Absent » à chaque
 * séance — 8, 2, puis 11 lignes. Ces lignes ne décrivent personne : elles
 * sont la MÊME erreur de saisie que l'inscription, répétée chaque jour.
 *
 * Elles bloquent pourtant `GardePresencesInscription`, à raison : « son
 * nom a-t-il été appelé dans ce groupe ? » est le bon critère pour un
 * transfert d'exploitation, et la garde ne doit jamais s'assouplir. C'est
 * donc CETTE action, réservée au compte de maintenance, qui retire les
 * lignes AVANT que le transfert ne s'exécute — et elle ne sait retirer
 * QUE des « Absent » :
 *
 *   ⚠ Une seule ligne « Présent », « Retard » ou « Justifié » refuse le LOT
 *   ENTIER. Elle prouve que la personne EST venue — et alors ce n'est pas
 *   un fantôme mais un vrai étudiant dont l'argent ne se déplace pas. Une
 *   ligne partiellement effacée serait pire qu'un refus : elle laisserait
 *   croire que le dossier a été assaini alors qu'il est simplement amputé.
 *
 * Le périmètre est EXACTEMENT celui que la garde compte (étudiant × séances
 * du groupe de l'inscription) — jamais « toutes les présences de
 * l'étudiant », qui emporterait ses appels dans un autre groupe.
 *
 * Chaque ligne passe par `delete()` sur son modèle (Auditable journalise
 * chaque suppression), et une entrée explicite sur l'inscription nomme le
 * lot, les dates et le motif : c'est la seule trace qui restera.
 *
 * S'appelle DANS la transaction de l'appelant, sous verrou.
 */
final class PurgerPresencesFantomes
{
    /**
     * @return int  le nombre de lignes effacées (0 quand il n'y en avait pas)
     */
    public function handle(Inscription $inscription, string $motif): int
    {
        // Sans groupe, aucune séance : rien à compter, rien à effacer — et
        // la garde refuse déjà ce dossier comme INDÉTERMINÉ.
        if ($inscription->group_id === null) {
            throw ValidationException::withMessages([
                'purger_presences' => __('This registration is no longer attached to a group: its attendance can no longer be verified, so the transfer cannot be allowed.'),
            ]);
        }

        $lignes = Presence::query()
            ->with('seance:id,date_seance')
            ->where('student_id', $inscription->student_id)
            ->whereIn(
                'seance_id',
                Seance::query()->select('id')->where('group_id', $inscription->group_id),
            )
            ->lockForUpdate()
            ->get();

        if ($lignes->isEmpty()) {
            return 0;
        }

        $reelles = $lignes->reject(fn (Presence $p): bool => $p->statut === Presence::STATUT_ABSENT);

        if ($reelles->isNotEmpty()) {
            throw ValidationException::withMessages([
                'purger_presences' => __(
                    ':count attendance line(s) are not « Absent » (:dates): this student was actually called in class, so these lines are real and cannot be erased.',
                    [
                        'count' => $reelles->count(),
                        'dates' => $reelles
                            ->map(fn (Presence $p): string => ($p->seance?->date_seance?->format('d/m/Y') ?? '?').' '.$p->statut)
                            ->implode(', '),
                    ],
                ),
            ]);
        }

        $detail = $lignes
            ->sortBy(fn (Presence $p) => $p->seance?->date_seance)
            ->values()
            ->map(fn (Presence $p): array => [
                'id' => $p->id,
                'seance_id' => $p->seance_id,
                'date' => $p->seance?->date_seance?->format('Y-m-d'),
                'statut' => $p->statut,
            ])
            ->all();

        activity('presence')
            ->performedOn($inscription)
            ->event('presences_fantomes_supprimees')
            ->withProperties([
                'student_id' => $inscription->student_id,
                'inscription' => $inscription->reference,
                'group_id' => $inscription->group_id,
                'lignes' => $detail,
                'motif' => $motif,
            ])
            ->log(sprintf(
                '%d appel(s) « Absent » fantôme(s) supprimé(s) sur %s (groupe #%d) — motif : %s',
                count($detail),
                $inscription->reference,
                $inscription->group_id,
                $motif,
            ));

        // Par le modèle, jamais un delete() de masse : Auditable journalise
        // chaque ligne effacée avec ses valeurs.
        $lignes->each(fn (Presence $p) => $p->delete());

        return count($detail);
    }
}
