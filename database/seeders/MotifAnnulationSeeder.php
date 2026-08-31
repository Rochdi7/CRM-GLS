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
        // portee decides which form offers the reason: an enrollment is not
        // cancelled for the reasons a class session is. PORTEE_TOUS stays on
        // both forms.
        $motifs = [
            'Autre' => MotifAnnulation::PORTEE_TOUS,

            // Inscriptions — why a student left.
            "Conflit d'horaires" => MotifAnnulation::PORTEE_INSCRIPTION,
            'Inactivité prolongée' => MotifAnnulation::PORTEE_INSCRIPTION,
            'Non-paiement' => MotifAnnulation::PORTEE_INSCRIPTION,
            "Transfert d'établissement" => MotifAnnulation::PORTEE_INSCRIPTION,
            'Problème du temps' => MotifAnnulation::PORTEE_INSCRIPTION,

            // Séances — why a class did not take place.
            'Malade' => MotifAnnulation::PORTEE_SEANCE,
            'Empêchement personnel' => MotifAnnulation::PORTEE_SEANCE,
            'Congé' => MotifAnnulation::PORTEE_SEANCE,
            'Jour férié' => MotifAnnulation::PORTEE_SEANCE,
            'Fin de formation' => MotifAnnulation::PORTEE_SEANCE,
        ];

        foreach ($motifs as $nom => $portee) {
            MotifAnnulation::query()->updateOrCreate(
                ['nom' => $nom],
                ['statut' => MotifAnnulation::STATUT_ACTIF, 'portee' => $portee],
            );
        }

        MotifAnnulation::query()->updateOrCreate(
            ['nom' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE],
            ['statut' => MotifAnnulation::STATUT_ACTIF, 'is_system' => true, 'portee' => MotifAnnulation::PORTEE_INSCRIPTION],
        );
    }
}
