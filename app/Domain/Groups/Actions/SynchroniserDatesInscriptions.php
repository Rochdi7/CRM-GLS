<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

use App\Models\Group;
use App\Models\Inscription;
use Illuminate\Support\Facades\DB;

/**
 * Propage les dates de formation d'un groupe sur ses inscriptions.
 *
 * ⚠ `inscriptions.date_debut` / `date_fin` NE SONT PAS des données propres à
 * l'étudiant : ce sont les dates DU GROUPE, recopiées sur chaque inscription.
 * La seule date qui appartient à l'étudiant est `date_inscription` — le jour
 * où il s'est inscrit — et elle n'est JAMAIS touchée ici.
 *
 * Pourquoi cette action existe (décidé le 04/09/2026) : le modal « Modifier
 * le groupe » écrivait `date_debut_formation` / `date_fin_formation` sur la
 * seule ligne `groups`, et les inscriptions gardaient la copie faite le jour
 * de leur création. Corriger les dates d'un groupe ne corrigeait donc rien
 * pour les étudiants qu'il contient. Mesuré sur la base réelle avant le
 * correctif : 1 206 des 1 306 inscriptions actives portaient une date_debut
 * différente de celle de leur groupe — dont 1 043 égales à leur
 * `date_inscription`, parce que l'ancien CRM nommait « Date de début » la
 * colonne qui contient en fait le jour de l'inscription (un étudiant inscrit
 * le 28/08 pour un groupe démarrant le 01/09). Ce ne sont donc pas des
 * saisies manuelles à préserver, mais des valeurs héritées à réaligner.
 *
 * Périmètre volontairement étroit :
 *
 *  - SEULES les inscriptions `Active` suivent le groupe. Une inscription
 *    Annulée / Archivée / Expirée / Changement est de l'histoire figée, au
 *    même titre que ses lignes de frais (voir RetirerFraisGroupe, même règle).
 *  - SEULES les lignes réellement divergentes sont écrites. Une inscription
 *    déjà alignée n'est pas sauvegardée du tout : pas de requête UPDATE, et
 *    surtout pas d'entrée « avant/après » identique dans le journal d'audit.
 *  - AUCUN autre champ n'est touché : ni statut, ni groupe, ni frais, ni
 *    montant, ni un dirham.
 *
 * Les lignes sont enregistrées une par une via Eloquent (jamais un mass
 * update), pour que le trait Auditable journalise chaque changement comme
 * n'importe quelle autre modification.
 */
final class SynchroniserDatesInscriptions
{
    /**
     * @return int le nombre d'inscriptions réalignées (0 quand tout l'était
     *             déjà) — remonté à l'utilisateur, une modification en masse
     *             ne doit jamais être silencieuse.
     */
    public function handle(Group $group): int
    {
        $debut = $group->date_debut_formation?->toDateString();
        $fin = $group->date_fin_formation?->toDateString();

        // Un groupe sans dates n'efface rien : il n'a simplement aucune date
        // à propager. Vider les inscriptions serait une perte d'information,
        // pas une synchronisation.
        if ($debut === null && $fin === null) {
            return 0;
        }

        return DB::transaction(function () use ($group, $debut, $fin): int {
            $modifiees = 0;

            $group->inscriptions()
                ->where('statut', Inscription::STATUT_ACTIVE)
                ->orderBy('id')
                ->chunkById(500, function ($lot) use ($debut, $fin, &$modifiees): void {
                    foreach ($lot as $inscription) {
                        $changements = [];

                        if ($debut !== null && $inscription->date_debut?->toDateString() !== $debut) {
                            $changements['date_debut'] = $debut;
                        }

                        if ($fin !== null && $inscription->date_fin?->toDateString() !== $fin) {
                            $changements['date_fin'] = $fin;
                        }

                        if ($changements === []) {
                            continue;
                        }

                        $inscription->update($changements);
                        $modifiees++;
                    }
                });

            return $modifiees;
        });
    }
}
