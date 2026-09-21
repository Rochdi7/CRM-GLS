<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (21/09/2026) : taux de rémunération de l'enseignant
 * POUR CE GROUPE, alimentant le calcul « Paiement prof »
 * (`App\Domain\Payroll`, écran /backoffice/paiement-prof).
 *
 * ⚠ Migration SÉPARÉE, et non une modification de `create_groups_table` :
 * la production tourne et porte déjà des données, donc le fichier de création
 * a déjà été joué là-bas et l'éditer n'y ajouterait rien (CLAUDE.md §17). Les
 * deux colonnes arrivent donc par une migration additive ordinaire, jouée par
 * `php artisan migrate --force` comme n'importe quelle autre.
 *
 * Le taux appartient au GROUPE et non à l'employé : un même enseignant est
 * payé différemment selon le groupe qu'il anime (niveau, volume, centre), et
 * un taux porté par `employees` réécrirait le passé de tous ses groupes d'un
 * seul coup.
 *
 * ⚠ Ces colonnes ne sont QU'UN DÉFAUT de formulaire. Le montant réellement
 * payé est FIGÉ sur la dépense (`depenses.montant`) au moment du paiement et
 * n'est jamais relu depuis le groupe : corriger un taux ne réécrit donc aucun
 * paiement déjà enregistré (§11 — les enregistrements monétaires sont
 * append-only).
 *
 * NULLABLES, aucun backfill : un groupe sans taux saisi laisse simplement le
 * champ du formulaire vide. Aucun montant, aucun `caisses.solde`, aucune
 * écriture de journal n'est touché — rien n'est supprimé ni réécrit.
 *
 * Idempotente (`hasColumn`) : sans danger si la colonne a déjà été posée à la
 * main sur une base (c'est le cas de la base de développement locale, et de
 * `docs/production-schema-patches.sql`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            if (! Schema::hasColumn('groups', 'montant_par_etudiant_prof')) {
                $table->decimal('montant_par_etudiant_prof', 10, 2)->nullable()->after('capacite_max');
            }

            if (! Schema::hasColumn('groups', 'taux_horaire_prof')) {
                $table->decimal('taux_horaire_prof', 10, 2)->nullable()->after('montant_par_etudiant_prof');
            }
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            foreach (['taux_horaire_prof', 'montant_par_etudiant_prof'] as $colonne) {
                if (Schema::hasColumn('groups', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
