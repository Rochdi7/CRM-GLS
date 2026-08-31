<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\MotifAnnulation;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated cancellation-reason list for the Settings →
 * Raisons d'annulation tab — same shape/pagination recipe as GetBanquesList.
 */
final class GetMotifsAnnulationList
{
    public function __invoke(int $perPage = 10): LengthAwarePaginator
    {
        $motifs = MotifAnnulation::query()->orderBy('nom')->paginate($perPage)->withQueryString();

        $motifs->through(fn (MotifAnnulation $m): array => [
            'id' => $m->id,
            'nom' => $m->nom,
            'isSystem' => $m->is_system,
            'portee' => $m->portee,
            'statut' => $m->statut,
        ]);

        return $motifs;
    }

    /**
     * Active reason names for the annulation/archive forms — the columns
     * that consume this stay free-text (no FK), so callers just need the list
     * of valid names, like Banque::activeNames.
     *
     * $portee narrows the catalogue to the reasons that make sense on ONE
     * form: a séance is cancelled because a teacher was ill or the day was a
     * holiday, an inscription because the student did not pay or moved
     * centre. Reasons marked PORTEE_TOUS (the generic « Autre ») are always
     * included. Passing null returns the whole catalogue — what the Settings
     * tab lists.
     *
     * @return list<string>
     */
    public function activeNames(?string $portee = null): array
    {
        return MotifAnnulation::query()
            ->where('statut', MotifAnnulation::STATUT_ACTIF)
            ->when($portee !== null, fn ($q) => $q->whereIn(
                'portee',
                [$portee, MotifAnnulation::PORTEE_TOUS],
            ))
            ->orderBy('nom')
            ->pluck('nom')
            ->all();
    }
}
