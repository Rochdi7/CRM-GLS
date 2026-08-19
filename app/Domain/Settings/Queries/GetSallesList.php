<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\Salle;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated rooms list for the Settings → Salles tab — narrowed
 * to the active top-bar context center (+ global NULL-center rows), same
 * behavior as the Livewire SallesTab's WithCenterContext::scopeToActiveCenter().
 */
final class GetSallesList
{
    public function __construct(private readonly CurrentContext $context) {}

    public function __invoke(int $perPage = 8): LengthAwarePaginator
    {
        $centerId = $this->context->etablissementId();

        $salles = Salle::query()
            ->with('etablissement')
            ->when($centerId !== null, fn ($q) => $q->where(
                fn ($q2) => $q2->whereNull('etablissement_id')->orWhere('etablissement_id', $centerId),
            ))
            ->orderBy('nom')
            ->paginate($perPage)->withQueryString();

        $salles->through(fn (Salle $s): array => [
            'id' => $s->id,
            'nom' => $s->nom,
            'centre' => $s->etablissement?->nom_centre,
            'capacite' => $s->capacite,
            'statut' => $s->statut,
        ]);

        return $salles;
    }
}
