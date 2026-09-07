<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Attendance\Support\DetecteurCreneauxDoubles;
use App\Models\Creneau;
use App\Models\Group;
use App\Models\Seance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rattrapage des emplois du temps saisis DEUX FOIS (signalé le 07/09/2026 sur
 * « Ilyass sept 19H » : cinq créneaux créés le 02/09, cinq identiques le
 * 04/09, d'où deux séances chaque soir et deux appels pour la même classe).
 *
 * La cause est fermée à la saisie (CreneauController refuse désormais une case
 * déjà occupée) et le générateur ne fabrique plus deux séances sur la même
 * case ; cette commande nettoie ce qui existe déjà.
 *
 * Ce qu'elle garde et ce qu'elle supprime :
 *  - de chaque paire de créneaux ouverts identiques (groupe + jour + heure de
 *    début), le PLUS ANCIEN est l'original et survit ;
 *  - les séances FUTURES encore « Prévue » et SANS présence des copies sont
 *    supprimées ; une séance déjà Effectuée/Annulée, passée, ou portant un
 *    appel, ne l'est jamais — elle enregistre une activité réelle. Elle est
 *    seulement DÉTACHÉE (creneau_id à null via la FK) et signalée, pour que la
 *    correction ne réécrive pas l'historique ;
 *  - un créneau copie dont il reste des séances non supprimables est CLÔTURÉ
 *    (date_fin = aujourd'hui) au lieu d'être supprimé : il cesse de générer
 *    sans que rien ne soit perdu.
 *
 * Dry-run par défaut, comme les autres commandes de rattrapage du projet.
 */
final class SupprimerCreneauxDoubles extends Command
{
    protected $signature = 'groupes:supprimer-creneaux-doubles
        {--group= : Limiter à un seul groupe (id)}
        {--apply : Écrit réellement les modifications}';

    protected $description = "Supprime les créneaux faisant double emploi et les séances dupliquées qu'ils ont générées";

    public function __construct(private readonly DetecteurCreneauxDoubles $doubles)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $groupOption = $this->option('group');

        $groupIds = Group::query()
            ->when($groupOption !== null, fn ($q) => $q->whereKey((int) $groupOption))
            ->whereHas('creneaux', fn ($q) => $q->whereNull('date_fin'))
            ->orderBy('id')
            ->pluck('id');

        $lignes = [];
        $creneauxTraites = 0;
        $seancesSupprimees = 0;
        $conserves = 0;

        foreach ($groupIds as $groupId) {
            $copies = $this->doubles->doublons((int) $groupId);

            if ($copies->isEmpty()) {
                continue;
            }

            $group = Group::find($groupId);

            foreach ($copies as $copie) {
                [$supprimables, $intouchables] = $this->partitionner($copie);

                $lignes[] = [
                    $group?->id,
                    $group?->nom ?? '—',
                    $copie->id,
                    Creneau::JOURS[(int) $copie->jour_semaine] ?? $copie->jour_semaine,
                    substr((string) $copie->heure_debut, 0, 5),
                    $supprimables,
                    $intouchables,
                    $intouchables > 0
                        ? ($apply ? 'CLÔTURÉ' : 'à clôturer')
                        : ($apply ? 'SUPPRIMÉ' : 'à supprimer'),
                ];

                $creneauxTraites++;
                $seancesSupprimees += $supprimables;
                $conserves += $intouchables;

                if (! $apply) {
                    continue;
                }

                DB::transaction(function () use ($copie, $intouchables): void {
                    $this->seancesSupprimables($copie)->delete();

                    if ($intouchables > 0) {
                        // Des séances réelles pendent encore à ce créneau :
                        // on l'arrête au lieu de l'effacer, pour ne rien
                        // détacher de leur origine.
                        $copie->update(['date_fin' => Carbon::today()->toDateString()]);

                        return;
                    }

                    $copie->delete();
                });
            }
        }

        if ($lignes === []) {
            $this->info('Aucun créneau en double — tous les emplois du temps sont sains.');

            return self::SUCCESS;
        }

        $this->table(
            ['Groupe', 'Nom', 'Créneau', 'Jour', 'Heure', 'Séances suppr.', 'Séances gardées', 'Action'],
            $lignes,
        );

        $this->info(sprintf(
            '%d créneau(x) en double %s, %d séance(s) dupliquée(s) %s, %d séance(s) conservée(s) (appel fait ou date passée).',
            $creneauxTraites,
            $apply ? 'traité(s)' : 'à traiter',
            $seancesSupprimees,
            $apply ? 'supprimée(s)' : 'à supprimer',
            $conserves,
        ));

        if (! $apply) {
            $this->warn('Simulation — relancez avec --apply pour écrire.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int} [séances supprimables, séances à conserver]
     */
    private function partitionner(Creneau $creneau): array
    {
        $supprimables = $this->seancesSupprimables($creneau)->count();
        $total = $creneau->seances()->count();

        return [$supprimables, $total - $supprimables];
    }

    private function seancesSupprimables(Creneau $creneau)
    {
        return $creneau->seances()
            ->where('statut', Seance::STATUT_PREVUE)
            // Une séance « Prévue » peut déjà porter des présences
            // (EnregistrerPresences ne change pas le statut) : la supprimer
            // effacerait l'appel en cascade.
            ->whereDoesntHave('presences')
            ->where('date_seance', '>=', Carbon::today()->toDateString());
    }
}
