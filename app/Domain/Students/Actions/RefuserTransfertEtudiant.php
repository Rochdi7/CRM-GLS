<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Models\StudentTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * REFUS d'une demande de transfert (super-admin). Rien ne bouge : la ligne
 * passe « Refusé » avec le motif — jamais supprimée, c'est ce qui explique
 * ensuite pourquoi l'étudiant est resté dans son centre.
 */
final class RefuserTransfertEtudiant
{
    public function handle(StudentTransfer $transfert, string $motif, User $decidedBy): StudentTransfer
    {
        $motif = trim($motif);

        if ($motif === '') {
            throw ValidationException::withMessages([
                'motif_decision' => __('A reason is required to refuse a transfer.'),
            ]);
        }

        return DB::transaction(function () use ($transfert, $motif, $decidedBy): StudentTransfer {
            $transfert = StudentTransfer::query()->whereKey($transfert->getKey())->lockForUpdate()->firstOrFail();

            if (! $transfert->estEnAttente()) {
                throw ValidationException::withMessages([
                    'statut' => __('This transfer request has already been processed.'),
                ]);
            }

            $transfert->update([
                'statut' => StudentTransfer::STATUT_REFUSE,
                'motif_decision' => $motif,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
            ]);

            return $transfert;
        });
    }
}
