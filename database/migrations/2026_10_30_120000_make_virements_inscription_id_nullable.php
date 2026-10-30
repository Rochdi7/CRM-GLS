<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Forward-only (30/10/2026) : une demande de virement DÉCIDÉE ne retient plus
 * son inscription.
 *
 * `virements.inscription_id` était NOT NULL + ON DELETE RESTRICT : une fois
 * validé, un virement empêchait à jamais la suppression de son dossier, même
 * quand l'encaissement qu'il a créé avait été converti en avance et ne
 * portait plus un dirham sur ce dossier (INS-8146, Rabat : VIR-201/VIR-202,
 * « Cette inscription a des paiements et ne peut pas être supprimée »).
 *
 * La colonne devient NULLABLE + ON DELETE SET NULL. La demande reste lisible
 * — étudiant, encaissement, montant, payeur, référence bancaire, centre —
 * seul le lien vers le dossier supprimé tombe. Une demande ENCORE en attente
 * bloque toujours la suppression, mais dans `SupprimerInscription`, avec un
 * message qui la nomme (la contrainte n'est plus qu'un filet).
 *
 * Idempotent : rejouable sur une base qui l'a déjà reçue.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE virements ALTER COLUMN inscription_id DROP NOT NULL');
        DB::statement('ALTER TABLE virements DROP CONSTRAINT IF EXISTS virements_inscription_id_foreign');
        DB::statement('ALTER TABLE virements ADD CONSTRAINT virements_inscription_id_foreign
            FOREIGN KEY (inscription_id) REFERENCES inscriptions(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        // La colonne reste nullable : des lignes détachées peuvent exister.
        DB::statement('ALTER TABLE virements DROP CONSTRAINT IF EXISTS virements_inscription_id_foreign');
        DB::statement('ALTER TABLE virements ADD CONSTRAINT virements_inscription_id_foreign
            FOREIGN KEY (inscription_id) REFERENCES inscriptions(id) ON DELETE RESTRICT');
    }
};
