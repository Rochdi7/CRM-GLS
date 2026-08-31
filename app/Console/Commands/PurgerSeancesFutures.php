<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Seance;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * One-off cleanup for the switch to DAY-BY-DAY séance generation
 * (31/08/2026, GenererSeancesDepuisCreneau) : the old generator bulk-created
 * the whole remaining period as "Prévue" rows the moment an emploi du temps
 * was saved. Those pre-generated FUTURE séances are now redundant — the
 * 08:00 job recreates each day's séance on its own morning — so this
 * removes them:
 *
 *  - séances « Prévue », datées APRÈS aujourd'hui, encore liées à un
 *    créneau (creneau_id non NULL) — les séances créées à la main puis
 *    détachées, et tout ce qui n'est plus « Prévue » (Effectuée/Annulée),
 *    survivent, même dans le futur ;
 *  - pour un groupe archivé (« Fin de formation » / « Annulée ») : aussi la
 *    séance « Prévue » du jour — le groupe ne se réunit plus.
 *
 * Dry-run par défaut ; --apply pour supprimer réellement. Idempotent — un
 * second passage ne trouve plus rien.
 */
final class PurgerSeancesFutures extends Command
{
    protected $signature = 'seances:purger-futures {--apply : Supprime réellement (sinon dry-run)}';

    protected $description = 'Supprime les séances « Prévue » futures pré-générées en masse par l\'ancien générateur (dry-run par défaut, --apply pour appliquer)';

    public function handle(): int
    {
        $query = Seance::query()
            ->where('statut', Seance::STATUT_PREVUE)
            ->whereNotNull('creneau_id')
            // L'appel se fait AVANT que le statut passe « Effectuée » : une
            // séance encore « Prévue » peut déjà porter des présences
            // (EnregistrerPresences ne touche pas le statut) — la supprimer
            // effacerait l'appel en cascade. On n'y touche jamais.
            ->whereDoesntHave('presences')
            ->where(function (Builder $q): void {
                $q->whereDate('date_seance', '>', now()->toDateString())
                    ->orWhere(function (Builder $q): void {
                        // Un groupe archivé ne se réunit plus : sa séance
                        // « Prévue » du jour part aussi.
                        $q->whereDate('date_seance', '>=', now()->toDateString())
                            ->whereHas('group', fn (Builder $g) => $g->whereIn('statut', Group::STATUTS_HISTORIQUE));
                    });
            });

        $parGroupe = (clone $query)
            ->selectRaw('group_id, count(*) as total, min(date_seance) as premiere, max(date_seance) as derniere')
            ->groupBy('group_id')
            ->get();

        $total = (int) $parGroupe->sum('total');

        if ($total === 0) {
            $this->info('Aucune séance « Prévue » future pré-générée à supprimer.');

            return self::SUCCESS;
        }

        $groupes = Group::whereIn('id', $parGroupe->pluck('group_id'))->get()->keyBy('id');

        $this->table(
            ['Groupe', 'Statut', 'Séances', 'De', 'À'],
            $parGroupe->map(fn ($row) => [
                $groupes[$row->group_id]?->nom ?? "#{$row->group_id}",
                $groupes[$row->group_id]?->statut ?? '?',
                $row->total,
                $row->premiere,
                $row->derniere,
            ])->all(),
        );

        if (! $this->option('apply')) {
            $this->warn("Dry-run : {$total} séance(s) seraient supprimée(s). Relancer avec --apply pour appliquer.");

            return self::SUCCESS;
        }

        $supprimees = $query->delete();
        $this->info("{$supprimees} séance(s) « Prévue » future(s) supprimée(s). Le job de 08:00 recrée désormais chaque jour ses séances.");

        return self::SUCCESS;
    }
}
