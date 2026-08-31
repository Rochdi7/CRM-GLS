<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Attendance\Actions\GenererSeancesDepuisCreneau;
use App\Models\Creneau;
use App\Models\Group;
use Illuminate\Console\Command;

/**
 * Runs the créneau → séances generator (GenererSeancesDepuisCreneau) for
 * every créneau of every active group, DAY BY DAY: each 08:00 run creates
 * only the CURRENT day's séances — starting the morning a group's
 * date_debut_formation arrives and stopping once its date_fin_formation is
 * past. Idempotent — a day already generated (whatever its statut) is
 * skipped, so re-running within the same day is safe (see withSchedule()
 * in bootstrap/app.php).
 */
final class GenerateSeances extends Command
{
    protected $signature = 'seances:generate';

    protected $description = "Génère les séances du jour depuis l'emploi du temps de chaque groupe actif (jour par jour, de date_debut_formation à date_fin_formation)";

    public function handle(GenererSeancesDepuisCreneau $generateur): int
    {
        $creneaux = Creneau::query()
            ->whereHas('group', fn ($q) => $q->whereIn('statut', [
                Group::STATUT_EN_INSCRIPTION,
                Group::STATUT_EN_FORMATION,
            ]))
            ->with('group')
            ->get();

        foreach ($creneaux as $creneau) {
            $generateur->generer($creneau);
        }

        $this->info("Séances générées pour {$creneaux->count()} créneau(x) actif(s).");

        return self::SUCCESS;
    }
}
