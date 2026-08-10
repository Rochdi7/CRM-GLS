<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Support\Facades\DB;

/**
 * Saves (or re-saves) the full roll call of a séance in ONE transaction.
 *
 * Only students actually enrolled in the séance's group (active inscription)
 * are accepted — submitted lines for anyone else are silently dropped, so a
 * stale or forged student_id can never attach a presence to the wrong group.
 * Saving a roll call marks the séance "Effectuée" unless it was cancelled.
 */
final class EnregistrerPresences
{
    /**
     * @param  array<int|string, array{statut: string, note?: ?string}>  $lignes  keyed by student_id
     */
    public function __invoke(Seance $seance, array $lignes): void
    {
        $enrolledIds = Inscription::query()
            ->where('group_id', $seance->group_id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        DB::transaction(function () use ($seance, $lignes, $enrolledIds): void {
            foreach ($lignes as $studentId => $ligne) {
                $studentId = (int) $studentId;

                if (! in_array($studentId, $enrolledIds, true)) {
                    continue;
                }

                if (! in_array($ligne['statut'] ?? '', Presence::STATUTS, true)) {
                    continue;
                }

                Presence::updateOrCreate(
                    ['seance_id' => $seance->id, 'student_id' => $studentId],
                    [
                        'statut' => $ligne['statut'],
                        'note' => ($ligne['note'] ?? '') !== '' ? $ligne['note'] : null,
                    ],
                );
            }

            if ($seance->statut !== Seance::STATUT_ANNULEE) {
                $seance->update(['statut' => Seance::STATUT_EFFECTUEE]);
            }
        });
    }
}
