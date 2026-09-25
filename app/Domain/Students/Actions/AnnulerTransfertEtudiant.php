<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Models\StudentTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ANNULATION d'une demande encore « En attente » — par son demandeur (il
 * s'est trompé de centre ou de groupe) ou par un super-admin. Une demande
 * déjà décidée ne s'annule pas : un transfert validé a déjà créé la copie
 * et déplacé l'argent, il se défait par un transfert retour, jamais en
 * effaçant la trace.
 */
final class AnnulerTransfertEtudiant
{
    public function handle(StudentTransfer $transfert, User $par, ?string $motif = null): StudentTransfer
    {
        return DB::transaction(function () use ($transfert, $par, $motif): StudentTransfer {
            $transfert = StudentTransfer::query()->whereKey($transfert->getKey())->lockForUpdate()->firstOrFail();

            if (! $transfert->estEnAttente()) {
                throw ValidationException::withMessages([
                    'statut' => __('This transfer request has already been processed.'),
                ]);
            }

            $transfert->update([
                'statut' => StudentTransfer::STATUT_ANNULE,
                'motif_decision' => $motif !== null && trim($motif) !== '' ? trim($motif) : null,
                'decided_by' => $par->id,
                'decided_at' => now(),
            ]);

            return $transfert;
        });
    }
}
