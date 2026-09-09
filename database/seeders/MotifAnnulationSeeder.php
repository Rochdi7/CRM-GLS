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

        // firstOrCreate pour les motifs ORDINAIRES (07/09/2026) : `db:seed`
        // est rejouable sur la production, et réécrire `statut` y
        // ressuscitait à chaque déploiement un motif qu'un administrateur
        // avait archivé. `portee` reste corrigée sur une ligne existante,
        // elle : c'est une donnée structurelle (elle décide dans QUEL
        // sélecteur le motif apparaît, séance ou inscription), jamais une
        // décision de l'utilisateur — l'écran ne l'expose pas.
        foreach ($motifs as $nom => $portee) {
            $motif = MotifAnnulation::query()->firstOrCreate(
                ['nom' => $nom],
                ['statut' => MotifAnnulation::STATUT_ACTIF, 'portee' => $portee],
            );

            if ($motif->portee !== $portee) {
                $motif->update(['portee' => $portee]);
            }
        }

        // Le motif SYSTÈME « Changement de groupe » garde updateOrCreate :
        // il est posé par le code (ChangerGroupeInscription le cherche par
        // son nom), donc il doit rester actif et is_system quoi qu'il arrive.
        // Même contrat pour « Clôture du groupe » (09/09/2026) : le motif
        // que CloturerInscriptionsGroupe pose sur chaque inscription annulée
        // en cascade quand son groupe passe « Fin de formation » / « Annulée ».
        foreach ([MotifAnnulation::MOTIF_CHANGEMENT_GROUPE, MotifAnnulation::MOTIF_CLOTURE_GROUPE] as $systeme) {
            MotifAnnulation::query()->updateOrCreate(
                ['nom' => $systeme],
                ['statut' => MotifAnnulation::STATUT_ACTIF, 'is_system' => true, 'portee' => MotifAnnulation::PORTEE_INSCRIPTION],
            );
        }
    }
}
