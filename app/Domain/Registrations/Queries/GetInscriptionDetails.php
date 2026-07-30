<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Models\Inscription;
use App\Models\InscriptionFee;

/**
 * Extracted from resources/views/backoffice/inscriptions/show.blade.php +
 * InscriptionController::show()'s eager loads. All totals (due/paid/
 * remaining) are computed here in PHP from the same source data the Blade
 * view used (@php block) — never recalculated client-side.
 */
final class GetInscriptionDetails
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Inscription $inscription): array
    {
        $inscription->loadMissing(['student', 'group.enseignant', 'anneeScolaire', 'fees.encaissements']);

        $totalDu = (float) $inscription->fees->sum('montant');
        $totalPaye = (float) $inscription->fees->sum(fn ($fee) => $fee->encaissements->sum('montant'));
        $reste = max(0, $totalDu - $totalPaye);

        return [
            'id' => $inscription->id,
            'reference' => $inscription->reference,
            'student' => $inscription->student?->nomComplet(),
            'studentShowUrl' => $inscription->student ? route('backoffice.students.show', $inscription->student) : null,
            'groupe' => $inscription->group?->nom,
            'anneeScolaire' => $inscription->anneeScolaire?->nom,
            'date' => $inscription->date_inscription?->format('d/m/Y'),
            'statut' => $inscription->statut,
            'totalDu' => number_format($totalDu, 2, '.', ''),
            'totalPaye' => number_format($totalPaye, 2, '.', ''),
            'reste' => number_format($reste, 2, '.', ''),
            'fees' => $inscription->fees->map(function (InscriptionFee $fee): array {
                $feePaid = (float) $fee->encaissements->sum('montant');

                return [
                    'nom' => $fee->nom,
                    'montant' => number_format((float) $fee->montant, 2, '.', ''),
                    'paye' => number_format($feePaid, 2, '.', ''),
                    'dateEcheance' => $fee->date_echeance?->format('d/m/Y'),
                    'statut' => $fee->statut,
                ];
            })->values()->all(),
        ];
    }
}
