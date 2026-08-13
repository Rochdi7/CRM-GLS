<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Attendance\Actions\GenererSeancesDepuisCreneau;
use App\Models\Creneau;
use App\Models\Group;
use Illuminate\Console\Command;

/**
 * Runs the créneau → séances generator (GenererSeancesDepuisCreneau) for
 * every créneau of every active group, so séances are kept generated ahead
 * of time without staff manually creating them. Idempotent — generer()
 * already skips dates it has generated before, so this is safe to run daily
 * (see withSchedule() in bootstrap/app.php).
 */
final class GenerateSeances extends Command
{
    protected $signature = 'seances:generate';

    protected $description = 'Generate upcoming séances from every active group\'s emploi du temps (créneaux)';

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
