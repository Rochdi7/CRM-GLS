<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\Banque;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated bank-catalog list for the Settings → Banques tab —
 * same shape/pagination recipe as GetFraisList.
 */
final class GetBanquesList
{
    public function __invoke(int $perPage = 8): LengthAwarePaginator
    {
        $banques = Banque::query()->orderBy('nom')->paginate($perPage)->withQueryString();

        $banques->through(fn (Banque $b): array => [
            'id' => $b->id,
            'nom' => $b->nom,
            'statut' => $b->statut,
        ]);

        return $banques;
    }

    /**
     * Active bank names for the chèque payment form's dropdown
     * (EncaissementController) — Encaissement::banque stores the name
     * itself (free-text column, no FK), so the frontend just needs the
     * list of valid names, not id/label pairs.
     *
     * @return list<string>
     */
    public function activeNames(): array
    {
        return Banque::query()
            ->where('statut', Banque::STATUT_ACTIF)
            ->orderBy('nom')
            ->pluck('nom')
            ->all();
    }
}
