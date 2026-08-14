<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MotifAnnulation;
use Illuminate\Database\Seeder;

/**
 * Starter cancellation/archival reason catalog (idempotent). Super-admins
 * manage the rest under Paramètres → Raisons d'annulation.
 * "Changement de groupe" is a system reason (written by the group-change
 * flow) — locked from edit/delete like system expense types.
 */
final class MotifAnnulationSeeder extends Seeder
{
    public function run(): void
    {
        $motifs = [
            'Autre',
            "Conflit d'horaires",
            'Inactivité prolongée',
            'Non-paiement',
            "Transfert d'établissement",
            'Problème du temps',
            'Ar',
        ];

        foreach ($motifs as $nom) {
            MotifAnnulation::query()->updateOrCreate(
                ['nom' => $nom],
                ['statut' => MotifAnnulation::STATUT_ACTIF],
            );
        }

        MotifAnnulation::query()->updateOrCreate(
            ['nom' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE],
            ['statut' => MotifAnnulation::STATUT_ACTIF, 'is_system' => true],
        );
    }
}
