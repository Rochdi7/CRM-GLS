<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (22/09/2026) : la configuration de PAIE d'un
 * enseignant vit désormais sur l'EMPLOYÉ, dans l'onglet « Paiement prof » de
 * sa fiche.
 *
 * Trois modes (`Employee::MODES_PAIEMENT_PROF`) :
 *   - `horaire`  : taux horaire × heures enseignées ;
 *   - `gls`      : montant par étudiant ÷ séances du mois × présences ;
 *   - `win_win`  : même formule que `gls`, mais le montant par étudiant
 *                  change CHAQUE MOIS (400, puis 420, 450… jusqu'à 600),
 *                  saisi mois par mois dans `enseignant_taux_mensuels`.
 *
 * Migration SÉPARÉE, jamais une édition de `create_employees_table` : la
 * production tourne et porte des données (§17). Nullables, aucun backfill :
 * un enseignant sans configuration reste calculable une fois sa fiche
 * remplie, et l'écran de calcul le DIT au lieu de deviner un taux.
 *
 * `groups.montant_par_etudiant_prof` / `taux_horaire_prof` (21/09) restent
 * en place mais ne sont plus lus : l'employé fait foi. Elles seront
 * retirées par une migration ultérieure une fois l'onglet en usage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'mode_paiement_prof')) {
                $table->string('mode_paiement_prof', 20)->nullable()->after('salaire');
            }

            if (! Schema::hasColumn('employees', 'taux_horaire_prof')) {
                $table->decimal('taux_horaire_prof', 10, 2)->nullable()->after('mode_paiement_prof');
            }

            if (! Schema::hasColumn('employees', 'montant_par_etudiant_prof')) {
                $table->decimal('montant_par_etudiant_prof', 10, 2)->nullable()->after('taux_horaire_prof');
            }
        });

        if (! Schema::hasTable('enseignant_taux_mensuels')) {
            Schema::create('enseignant_taux_mensuels', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                // Premier jour du mois civil concerné (toujours le 1er) : une
                // date plutôt qu'un couple (mois, année) pour trier et borner
                // en SQL sans reconstruire.
                $table->date('mois');
                $table->decimal('montant_par_etudiant', 10, 2);
                $table->timestamps();

                // Un seul montant par enseignant et par mois.
                $table->unique(['employee_id', 'mois'], 'enseignant_taux_mensuels_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignant_taux_mensuels');

        Schema::table('employees', function (Blueprint $table): void {
            foreach (['montant_par_etudiant_prof', 'taux_horaire_prof', 'mode_paiement_prof'] as $colonne) {
                if (Schema::hasColumn('employees', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
