<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Queries;

use App\Models\Depense;
use Illuminate\Support\Facades\Gate;

/**
 * Extracted from resources/views/backoffice/depenses/show.blade.php +
 * DepenseController::show()'s eager loads. An expense is never deleted —
 * no destroy/correction workflow anywhere in this class. Media is exposed
 * only as safe {name,url,mimeType,size} fields, never the full Spatie
 * Media object or a filesystem path.
 */
final class GetDepenseDetails
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Depense $depense): array
    {
        $depense->loadMissing(['typeDepense', 'caisse.etablissement', 'agent']);

        $motsCles = $depense->mots_cles
            ? array_values(array_filter(array_map('trim', explode(',', $depense->mots_cles))))
            : [];

        return [
            'id' => $depense->id,
            'reference' => $depense->reference,
            'montant' => number_format((float) $depense->montant, 2, '.', ''),
            'typeDepense' => $depense->typeDepense?->nom,
            'dateDepense' => $depense->date_depense?->format('d/m/Y'),
            'caisse' => $depense->caisse?->nom,
            'centre' => $depense->caisse?->etablissement?->nom_centre,
            'agent' => $depense->agent?->nomComplet(),
            'recordedAt' => $depense->created_at?->format('d/m/Y H:i'),
            'description' => $depense->description,
            'motsCles' => $motsCles,
            'note' => $depense->note,
            // Matches the Blade's own @can('update', $depense) gate around the
            // "Back to list" link — read-only visibility check, no edit action.
            'canViewList' => Gate::allows('update', $depense),
            'receipts' => $depense->getMedia('justificatifs')->map(fn ($media): array => [
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'mimeType' => (string) $media->mime_type,
                'size' => (int) $media->size,
            ])->values()->all(),
        ];
    }
}
