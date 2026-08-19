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
    public function __invoke(int $perPage = 8): LengthAwarePaginator
    {
        $motifs = MotifAnnulation::query()->orderBy('nom')->paginate($perPage)->withQueryString();

        $motifs->through(fn (MotifAnnulation $m): array => [
            'id' => $m->id,
            'nom' => $m->nom,
            'isSystem' => $m->is_system,
            'statut' => $m->statut,
        ]);

        return $motifs;
    }

    /**
     * Active reason names for future annulation/archive forms (inscriptions,
     * séances) — the columns that will consume this stay free-text (no FK),
     * so callers just need the list of valid names, like Banque::activeNames.
     *
     * @return list<string>
     */
    public function activeNames(): array
    {
        return MotifAnnulation::query()
            ->where('statut', MotifAnnulation::STATUT_ACTIF)
            ->orderBy('nom')
            ->pluck('nom')
            ->all();
    }
}
