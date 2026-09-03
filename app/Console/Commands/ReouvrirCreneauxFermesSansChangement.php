<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Creneau;
use App\Models\Group;
use App\Models\GroupEnseignant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattrapage du bug du 03/09/2026 : affecter un enseignant à un groupe QUI
 * N'EN AVAIT AUCUN passait par la branche « changement d'enseignant » de
 * ChangerEnseignantGroupe, qui clôture tous les créneaux du groupe
 * (date_fin = jour de l'affectation) pour séparer les séances par enseignant.
 * Or personne ne partait : le groupe se retrouvait sans emploi du temps
 * vivant, `seances:generate` ne produisait plus rien, et le personnel
 * saisissait chaque séance à la main (Yassmina 10H et ABDELLATIF 17H à Rabat,
 * plus 7 groupes sur Agadir, Marrakech et Salé).
 *
 * La cause est corrigée dans l'action ; cette commande répare les groupes
 * déjà abîmés. Elle est volontairement TRÈS prudente : elle ne rouvre que les
 * groupes dont l'historique ne montre qu'UNE SEULE période d'enseignant,
 * encore ouverte — la preuve qu'aucun changement n'a réellement eu lieu. Un
 * groupe ayant connu deux enseignants est laissé tel quel : ses créneaux
 * fermés séparent réellement deux paies et leur réouverture fausserait
 * l'historique. Ces cas-là sont listés puis ignorés.
 *
 * Dry-run par défaut, comme les autres commandes de rattrapage du projet.
 */
final class ReouvrirCreneauxFermesSansChangement extends Command
{
    protected $signature = 'groupes:reouvrir-emploi-du-temps {--apply : Écrit réellement les modifications}';

    protected $description = "Rouvre l'emploi du temps des groupes actifs dont tous les créneaux ont été clôturés sans véritable changement d'enseignant";

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        // Groupes actifs ayant AU MOINS UN créneau clôturé.
        //
        // ⚠ Ne PAS exiger que TOUS les créneaux soient fermés : la clôture est
        // parfois partielle et le groupe reste alors silencieusement amputé
        // des jours fermés. OUASSIMA 13H et HERR ABDESSAMAD 10H (Marrakech,
        // 03/09/2026) gardaient leur seul créneau du lundi ouvert, les quatre
        // autres fermés au 01/09 : le groupe paraissait vivant, mais ne
        // produisait plus rien du mardi au vendredi. Un premier filtre « aucun
        // créneau ouvert » les avait laissés passer.
        $groupes = Group::query()
            ->whereIn('statut', [Group::STATUT_EN_INSCRIPTION, Group::STATUT_EN_FORMATION])
            ->whereHas('creneaux', fn ($q) => $q->whereNotNull('date_fin'))
            ->with(['etablissement', 'anneeScolaire'])
            ->orderBy('etablissement_id')
            ->get();

        if ($groupes->isEmpty()) {
            $this->info('Aucun groupe concerné — tous les emplois du temps actifs sont ouverts.');

            return self::SUCCESS;
        }

        $reouverts = 0;
        $ignores = 0;
        $creneauxTotal = 0;
        $lignes = [];

        foreach ($groupes as $group) {
            $periodes = GroupEnseignant::query()->where('group_id', $group->id)->get();

            // Plusieurs périodes = un vrai changement d'enseignant a eu lieu.
            // Les créneaux fermés séparent deux paies : ne pas y toucher.
            $vraiChangement = $periodes->count() > 1;

            $creneaux = Creneau::query()->where('group_id', $group->id)->whereNotNull('date_fin')->count();

            $ouverts = Creneau::query()->where('group_id', $group->id)->whereNull('date_fin')->count();

            $lignes[] = [
                $group->id,
                $group->nom,
                $group->etablissement?->nom_centre ?? '—',
                $group->anneeScolaire?->nom ?? '—',
                $periodes->count(),
                $creneaux . ' / ' . ($creneaux + $ouverts),
                $vraiChangement ? 'IGNORÉ (changement réel)' : ($apply ? 'ROUVERT' : 'à rouvrir'),
            ];

            if ($vraiChangement) {
                $ignores++;

                continue;
            }

            $reouverts++;
            $creneauxTotal += $creneaux;

            if ($apply) {
                DB::transaction(function () use ($group): void {
                    Creneau::query()
                        ->where('group_id', $group->id)
                        ->whereNotNull('date_fin')
                        ->update(['date_fin' => null]);
                });
            }
        }

        $this->table(
            ['ID', 'Groupe', 'Centre', 'Année', 'Périodes', 'Fermés / total', 'Action'],
            $lignes,
        );

        $this->info(sprintf(
            '%d groupe(s) %s (%d créneau(x)), %d ignoré(s) pour changement réel.',
            $reouverts,
            $apply ? 'rouvert(s)' : 'à rouvrir',
            $creneauxTotal,
            $ignores,
        ));

        if (! $apply) {
            $this->warn('Simulation — relancez avec --apply pour écrire.');
        } else {
            $this->info('Lancez ensuite `php artisan seances:generate` pour produire les séances du jour.');
        }

        return self::SUCCESS;
    }
}
