<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL, unlike MySQL/InnoDB, never auto-indexes a plain
 * `foreignId()->constrained()` column — only SQLite shared that gap, and
 * migration 2026_07_29_090000 already covers the composite/aggregate cases
 * found there. This migration adds the remaining standalone single-column
 * indexes for every FK column not already covered by a composite or unique
 * index, so eager-load `WHERE IN` and direct filters (center scoping via
 * etablissement_id, caisse/type/annee filters, etc.) stay indexed on Postgres.
 *
 * Columns intentionally skipped (already covered by an existing index):
 * encaissements.caisse_id, depenses.caisse_id, remboursements.caisse_id
 * (composite date indexes), inscriptions.annee_scolaire_id (composite with
 * statut), group_frais.group_id/frais_id (unique composite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salles', function (Blueprint $table): void {
            $table->index('etablissement_id', 'salles_etablissement_id_idx');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->index('etablissement_id', 'employees_etablissement_id_idx');
            $table->index('user_id', 'employees_user_id_idx');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->index('etablissement_id', 'students_etablissement_id_idx');
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->index('enseignant_id', 'groups_enseignant_id_idx');
            $table->index('salle_id', 'groups_salle_id_idx');
            $table->index('etablissement_id', 'groups_etablissement_id_idx');
            $table->index('annee_scolaire_id', 'groups_annee_scolaire_id_idx');
        });

        Schema::table('groups_historique', function (Blueprint $table): void {
            $table->index('enseignant_id', 'groups_historique_enseignant_id_idx');
            $table->index('etablissement_id', 'groups_historique_etablissement_id_idx');
            $table->index('annee_scolaire_id', 'groups_historique_annee_scolaire_id_idx');
            $table->index('archived_by', 'groups_historique_archived_by_idx');
        });

        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->index('student_id', 'inscriptions_student_id_idx');
            $table->index('group_id', 'inscriptions_group_id_idx');
            $table->index('etablissement_id', 'inscriptions_etablissement_id_idx');
            $table->index('created_by', 'inscriptions_created_by_idx');
        });

        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->index('inscription_id', 'inscription_fees_inscription_id_idx');
            $table->index('frais_id', 'inscription_fees_frais_id_idx');
        });

        Schema::table('caisses', function (Blueprint $table): void {
            $table->index('etablissement_id', 'caisses_etablissement_id_idx');
            $table->index('responsable_employee_id', 'caisses_responsable_employee_id_idx');
        });

        Schema::table('encaissements', function (Blueprint $table): void {
            $table->index('student_id', 'encaissements_student_id_idx');
            $table->index('inscription_fee_id', 'encaissements_inscription_fee_id_idx');
            $table->index('agent_id', 'encaissements_agent_id_idx');
        });

        Schema::table('depenses', function (Blueprint $table): void {
            $table->index('type_depense_id', 'depenses_type_depense_id_idx');
            $table->index('agent_id', 'depenses_agent_id_idx');
            $table->index('group_id', 'depenses_group_id_idx');
        });

        Schema::table('remboursements', function (Blueprint $table): void {
            $table->index('beneficiaire_id', 'remboursements_beneficiaire_id_idx');
            $table->index('agent_id', 'remboursements_agent_id_idx');
        });

        Schema::table('caisse_transfers', function (Blueprint $table): void {
            $table->index('caisse_source_id', 'caisse_transfers_caisse_source_id_idx');
            $table->index('caisse_destination_id', 'caisse_transfers_caisse_destination_id_idx');
            $table->index('requested_by', 'caisse_transfers_requested_by_idx');
            $table->index('validated_by', 'caisse_transfers_validated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('salles', function (Blueprint $table): void {
            $table->dropIndex('salles_etablissement_id_idx');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex('employees_etablissement_id_idx');
            $table->dropIndex('employees_user_id_idx');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropIndex('students_etablissement_id_idx');
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropIndex('groups_enseignant_id_idx');
            $table->dropIndex('groups_salle_id_idx');
            $table->dropIndex('groups_etablissement_id_idx');
            $table->dropIndex('groups_annee_scolaire_id_idx');
        });

        Schema::table('groups_historique', function (Blueprint $table): void {
            $table->dropIndex('groups_historique_enseignant_id_idx');
            $table->dropIndex('groups_historique_etablissement_id_idx');
            $table->dropIndex('groups_historique_annee_scolaire_id_idx');
            $table->dropIndex('groups_historique_archived_by_idx');
        });

        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->dropIndex('inscriptions_student_id_idx');
            $table->dropIndex('inscriptions_group_id_idx');
            $table->dropIndex('inscriptions_etablissement_id_idx');
            $table->dropIndex('inscriptions_created_by_idx');
        });

        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->dropIndex('inscription_fees_inscription_id_idx');
            $table->dropIndex('inscription_fees_frais_id_idx');
        });

        Schema::table('caisses', function (Blueprint $table): void {
            $table->dropIndex('caisses_etablissement_id_idx');
            $table->dropIndex('caisses_responsable_employee_id_idx');
        });

        Schema::table('encaissements', function (Blueprint $table): void {
            $table->dropIndex('encaissements_student_id_idx');
            $table->dropIndex('encaissements_inscription_fee_id_idx');
            $table->dropIndex('encaissements_agent_id_idx');
        });

        Schema::table('depenses', function (Blueprint $table): void {
            $table->dropIndex('depenses_type_depense_id_idx');
            $table->dropIndex('depenses_agent_id_idx');
            $table->dropIndex('depenses_group_id_idx');
        });

        Schema::table('remboursements', function (Blueprint $table): void {
            $table->dropIndex('remboursements_beneficiaire_id_idx');
            $table->dropIndex('remboursements_agent_id_idx');
        });

        Schema::table('caisse_transfers', function (Blueprint $table): void {
            $table->dropIndex('caisse_transfers_caisse_source_id_idx');
            $table->dropIndex('caisse_transfers_caisse_destination_id_idx');
            $table->dropIndex('caisse_transfers_requested_by_idx');
            $table->dropIndex('caisse_transfers_validated_by_idx');
        });
    }
};
