<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Models\AnneeScolaire;
use App\Models\AppSetting;
use App\Models\Banque;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Cheque;
use App\Models\Creneau;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\GroupHistorique;
use App\Models\ImportBatch;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\InscriptionHistorique;
use App\Models\InscriptionLivre;
use App\Models\MotifAnnulation;
use App\Models\Presence;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Salle;
use App\Models\Seance;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Support\Access\HiddenAccount;

/**
 * Single source of truth for the audit journal (CLAUDE.md §11 "Audit log").
 *
 * Every audited model maps to a stable `log_name` and a French label. Adding a
 * new module means adding ONE line here plus the `Auditable` trait on the
 * model — the journal page's filters, the finance view and the subject labels
 * all read from this map, so they can never drift apart from what is actually
 * being recorded.
 *
 * Log names are deliberately the ones already in use by the eight models that
 * carried LogsActivity before this journal existed (encaissement, depense,
 * remboursement, caisse_transfer, cheque, inscription, student, stock) —
 * renaming them would orphan existing rows from their filters.
 */
final class AuditLogRegistry
{
    /**
     * The developer/maintainer account.
     *
     * Its activity is still RECORDED in full — nothing about the trail's
     * completeness changes — but the journal PAGE hides it by default, so
     * maintenance work does not drown the entries that describe real school
     * activity. Anyone reading the journal can bring it back with the
     * « Inclure le compte technique » toggle.
     *
     * ⚠ This is a display filter, never a recording bypass. An account whose
     * actions were not written at all would be a permanent blind spot on the
     * most privileged login in the system — money could move with no trace —
     * which is exactly what this audit trail exists to prevent. Keep the
     * distinction: hidden by default, never absent.
     *
     * The address itself now lives on App\Support\Access\HiddenAccount, which
     * applies the same "invisible in the UI, fully recorded" rule to the rest
     * of the app (Employés, Utilisateurs, Caisses). This is an ALIAS so the
     * journal keeps its own vocabulary while the value is written down once.
     */
    public const DEVELOPER_EMAIL = HiddenAccount::EMAIL;

    /**
     * Money-touching logs — the "suivi des encaissements / fraude" view.
     *
     * @var list<string>
     */
    private const FINANCE = [
        'encaissement', 'depense', 'remboursement', 'caisse_transfer',
        'cheque', 'caisse', 'inscription_fee',
    ];

    /**
     * [model FQCN => [log name, French label]].
     *
     * @return array<class-string, array{0: string, 1: string}>
     */
    public static function map(): array
    {
        return [
            // ── Finance (fraud-relevant) ────────────────────────────────
            Encaissement::class => ['encaissement', 'Encaissement'],
            Depense::class => ['depense', 'Dépense'],
            Remboursement::class => ['remboursement', 'Remboursement'],
            CaisseTransfer::class => ['caisse_transfer', 'Transfert de caisse'],
            Cheque::class => ['cheque', 'Chèque'],
            Caisse::class => ['caisse', 'Caisse'],
            InscriptionFee::class => ['inscription_fee', "Frais d'inscription"],

            // ── Scolarité ──────────────────────────────────────────────
            Student::class => ['student', 'Étudiant'],
            Inscription::class => ['inscription', 'Inscription'],
            InscriptionHistorique::class => ['inscription_historique', "Historique d'inscription"],
            InscriptionLivre::class => ['inscription_livre', 'Livre remis'],
            Group::class => ['group', 'Groupe'],
            GroupEnseignant::class => ['group_enseignant', 'Enseignant du groupe'],
            GroupHistorique::class => ['group_historique', 'Historique de groupe'],
            Creneau::class => ['creneau', 'Créneau'],
            Seance::class => ['seance', 'Séance'],
            Presence::class => ['presence', 'Présence'],

            // ── RH & accès ─────────────────────────────────────────────
            Employee::class => ['employee', 'Employé'],
            User::class => ['user', 'Utilisateur'],
            Role::class => ['role', 'Rôle'],

            // ── Stock ──────────────────────────────────────────────────
            StockArticle::class => ['stock_article', 'Article de stock'],
            StockMouvement::class => ['stock', 'Mouvement de stock'],
            StockType::class => ['stock_type', 'Type de stock'],

            // ── Référentiel / paramètres ───────────────────────────────
            Etablissement::class => ['etablissement', 'Centre'],
            AnneeScolaire::class => ['annee_scolaire', 'Année scolaire'],
            Salle::class => ['salle', 'Salle'],
            Frais::class => ['frais', 'Frais (catalogue)'],
            Banque::class => ['banque', 'Banque'],
            TypeDepense::class => ['type_depense', 'Type de dépense'],
            MotifAnnulation::class => ['motif_annulation', "Motif d'annulation"],
            AppSetting::class => ['app_setting', 'Paramètre système'],

            // ── Import ─────────────────────────────────────────────────
            ImportBatch::class => ['import_batch', 'Import de données'],
        ];
    }

    /**
     * Log names that carry no Eloquent subject — written by services, not by
     * the Auditable trait, so they are not in map() above.
     *
     * @return array<string, string>
     */
    public static function systemLogNames(): array
    {
        return [
            'authentication' => 'Connexion / déconnexion',
            'authorization' => 'Rôles et permissions',
            // Written by SystemSettingController, not by the Auditable trait.
            'system-settings' => 'Paramètres système',
        ];
    }

    /** @return list<string> */
    public static function financeLogNames(): array
    {
        return self::FINANCE;
    }

    /**
     * Every log name the journal knows about, with its French label.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::map() as [$logName, $label]) {
            $labels[$logName] = $label;
        }

        return $labels + self::systemLogNames();
    }

    /**
     * French label for a subject class, falling back to the bare class name so
     * an un-registered model still renders something meaningful.
     */
    public static function labelForSubjectType(?string $subjectType): ?string
    {
        if ($subjectType === null) {
            return null;
        }

        return self::map()[$subjectType][1] ?? class_basename($subjectType);
    }

    /**
     * Subject-type options for the journal filter, French-label sorted.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function subjectTypeOptions(): array
    {
        $options = [];

        foreach (self::map() as $class => [, $label]) {
            $options[] = ['value' => $class, 'label' => $label];
        }

        usort($options, fn (array $a, array $b): int => strcoll($a['label'], $b['label']));

        return $options;
    }
}
