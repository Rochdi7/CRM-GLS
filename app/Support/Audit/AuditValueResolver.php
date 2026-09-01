<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Models\AnneeScolaire;
use App\Models\Banque;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\MotifAnnulation;
use App\Models\Salle;
use App\Models\Seance;
use App\Models\StockArticle;
use App\Models\StockType;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns raw audited values into something a non-technical reader understands.
 *
 * The journal records what the database stores, which is correct but unreadable:
 * `ENSEIGNANT_ID: 11` tells a director nothing, and `CREATED_BY: 3` even less.
 * Nobody should have to look up an id in another table to follow a payment.
 * This class does that lookup once, at read time, so the page can show
 * "Enseignant : Karim Benali" instead of "11".
 *
 * Resolution is deliberately read-time rather than baked into the log row:
 * the journal itself must stay an immutable record of the literal values that
 * were written (App\Models\Activity), and a name that changes later must not
 * silently rewrite history. The id remains the stored truth; the name is only
 * a display aid, and is shown ALONGSIDE the id, never instead of it.
 *
 * Names are batch-loaded per request (see resolveMany) so rendering a page of
 * entries costs a handful of queries, not one per field.
 */
final class AuditValueResolver
{
    /**
     * Foreign-key column => the model it points at.
     *
     * Suffix-matched, so `enseignant_id`, `agent_id` etc. are matched exactly
     * while unknown `*_id` columns simply fall through and display raw.
     *
     * @var array<string, class-string<Model>>
     */
    private const FOREIGN_KEYS = [
        'etablissement_id' => Etablissement::class,
        'annee_scolaire_id' => AnneeScolaire::class,
        'salle_id' => Salle::class,
        'group_id' => Group::class,
        'student_id' => Student::class,
        'inscription_id' => Inscription::class,
        'inscription_fee_id' => InscriptionFee::class,
        'frais_id' => Frais::class,
        'caisse_id' => Caisse::class,
        'caisse_source_id' => Caisse::class,
        'caisse_destination_id' => Caisse::class,
        'type_depense_id' => TypeDepense::class,
        'stock_article_id' => StockArticle::class,
        'stock_type_id' => StockType::class,
        'banque_id' => Banque::class,
        'motif_annulation_id' => MotifAnnulation::class,
        'cheque_id' => Cheque::class,
        'encaissement_id' => Encaissement::class,
        'applied_from_encaissement_id' => Encaissement::class,
        'seance_id' => Seance::class,
        // People — an employee id can appear under several role-specific names.
        'employee_id' => Employee::class,
        'enseignant_id' => Employee::class,
        'agent_id' => Employee::class,
        'approved_by' => Employee::class,
        'beneficiaire_id' => Employee::class,
        'responsable_employee_id' => Employee::class,
        'retourne_par_id' => Employee::class,
        // Users (actors), not employees.
        'user_id' => User::class,
        'created_by' => User::class,
        'archived_by' => User::class,
        'assigned_by' => User::class,
        'validated_by' => User::class,
    ];

    /**
     * French labels for columns, so the page never shows a raw snake_case name.
     *
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'id' => 'Identifiant',
        'nom' => 'Nom',
        'prenom' => 'Prénom',
        'nom_centre' => 'Centre',
        'reference' => 'Référence',
        'montant' => 'Montant',
        'montant_total' => 'Montant total',
        'montant_initial' => 'Montant initial',
        'montant_defaut' => 'Montant par défaut',
        'remise_pct' => 'Remise (%)',
        'remise_montant' => 'Remise (DH)',
        'statut' => 'Statut',
        'methode' => 'Méthode de paiement',
        'methode_paiement' => 'Méthode de paiement',
        'date_paiement' => 'Date de paiement',
        'date_depense' => 'Date de la dépense',
        'date_echeance' => "Date d'échéance",
        'date_inscription' => "Date d'inscription",
        'date_debut' => 'Date de début',
        'date_fin' => 'Date de fin',
        'date_naissance' => 'Date de naissance',
        'date_embauche' => "Date d'embauche",
        'date_seance' => 'Date de la séance',
        'date_transfert' => 'Date du transfert',
        'date_remboursement' => 'Date du remboursement',
        'date_reception' => 'Date de réception',
        'date_debut_formation' => 'Début de formation',
        'date_fin_formation' => 'Fin de formation',
        'heure_debut' => 'Heure de début',
        'heure_fin' => 'Heure de fin',
        'jour_semaine' => 'Jour de la semaine',
        'niveau' => 'Niveau',
        'categorie' => 'Catégorie',
        'telephone' => 'Téléphone',
        'whatsapp' => 'WhatsApp',
        'email' => 'E-mail',
        'adresse' => 'Adresse',
        'note' => 'Note',
        'description' => 'Description',
        'mots_cles' => 'Mots-clés',
        'capacite' => 'Capacité',
        'capacite_max' => 'Capacité maximale',
        'quantite' => 'Quantité',
        'quantite_avant' => 'Quantité avant',
        'quantite_apres' => 'Quantité après',
        'seuil_alerte' => "Seuil d'alerte",
        'solde' => 'Solde',
        'salaire' => 'Salaire',
        'sexe' => 'Sexe',
        'cin' => 'CIN',
        'ice' => 'ICE',
        'username' => "Nom d'utilisateur",
        'name' => 'Nom',
        'is_active' => 'Compte actif',
        'is_system' => 'Élément système',
        'par_defaut' => 'Par défaut',
        'siege_social' => 'Siège social',
        'must_change_password' => 'Doit changer le mot de passe',
        'numero_cheque' => 'Numéro de chèque',
        'banque' => 'Banque',
        'motif' => 'Motif',
        'type' => 'Type',
        'created_at' => 'Créé le',
        'updated_at' => 'Modifié le',
        'archived_at' => 'Archivé le',
        'guard_name' => 'Garde',
        'label' => 'Libellé',
        // FK columns get a label WITHOUT the "_id" noise; the resolved name is
        // rendered next to the raw id by the page.
        'etablissement_id' => 'Centre',
        'annee_scolaire_id' => 'Année scolaire',
        'salle_id' => 'Salle',
        'group_id' => 'Groupe',
        'student_id' => 'Étudiant',
        'inscription_id' => 'Inscription',
        'inscription_fee_id' => "Frais d'inscription",
        'frais_id' => 'Frais',
        'caisse_id' => 'Caisse',
        'caisse_source_id' => 'Caisse source',
        'caisse_destination_id' => 'Caisse destination',
        'type_depense_id' => 'Type de dépense',
        'stock_article_id' => 'Article de stock',
        'stock_type_id' => 'Type de stock',
        'banque_id' => 'Banque',
        'motif_annulation_id' => "Motif d'annulation",
        'cheque_id' => 'Chèque',
        'encaissement_id' => 'Encaissement',
        'applied_from_encaissement_id' => 'Avance d’origine',
        'seance_id' => 'Séance',
        'employee_id' => 'Employé',
        'enseignant_id' => 'Enseignant',
        'agent_id' => 'Agent',
        'approved_by' => 'Approuvé par',
        'approved_at' => 'Date de décision',
        'motif_refus' => 'Motif du refus',
        'beneficiaire_id' => 'Bénéficiaire',
        'responsable_employee_id' => 'Responsable',
        'retourne_par_id' => 'Retourné par',
        'user_id' => 'Utilisateur',
        'created_by' => 'Créé par',
        // Journal property keys (finance context blocks) — same French-label
        // treatment as columns, so the detail page never shows snake_case.
        'avance_reference' => "Référence de l'avance",
        'avance_restant_avant' => "Reste de l'avance avant",
        'avance_restant_apres' => "Reste de l'avance après",
        'frais' => 'Frais concerné',
        'etudiant_id' => 'Étudiant',
        'statut_avant' => 'Statut avant',
        'statut_apres' => 'Statut après',
        'proprietaire' => 'Propriétaire du chèque',
        'solde_avant' => 'Solde avant',
        'solde_apres' => 'Solde après',
        'sens' => 'Sens du mouvement',
        'caisse' => 'Caisse',
        'caisse_source' => 'Caisse source',
        'caisse_destination' => 'Caisse destination',
        'valide_par' => 'Validé par',
        'origine_reference' => "Référence d'origine",
        'motif_detail' => 'Détail du motif',
        'archived_by' => 'Archivé par',
        'assigned_by' => 'Attribué par',
        'validated_by' => 'Validé par',
    ];

    /**
     * Relations to eager-load when resolving a model, so displayName() can
     * name the STUDENT behind a record without firing a query per row.
     *
     * A journal reader looking at a deleted payment must be able to tell whose
     * money it was. `Frais d'inscription A1/A2/B1` alone is the same label on
     * hundreds of registrations — useless for tracing. The student's name is
     * appended to every record that hangs off one (fee line, registration,
     * payment, cheque), so the entry reads "Frais d'inscription A1/A2/B1 —
     * ADAM ZARTANI" whatever the surrounding columns happen to carry.
     *
     * @var array<class-string<Model>, list<string>>
     */
    private const EAGER = [
        InscriptionFee::class => ['inscription.student'],
        Inscription::class => ['student'],
        Encaissement::class => ['student'],
        Cheque::class => ['student'],
    ];

    /**
     * Columns that are pure plumbing — hidden from the "champs modifiés" table
     * by default because they say nothing about what a user actually did.
     * `id`/`created_at` on a creation are the clearest case: they are noise
     * that pushes the meaningful fields off the top of the list.
     *
     * @var list<string>
     */
    private const NOISE_ON_CREATE = ['id', 'created_at', 'updated_at'];

    /** Resolved display names, keyed "Model|id". @var array<string, ?string> */
    private array $cache = [];

    /**
     * Pre-load every name referenced by the given entries, in as few queries as
     * possible — one per model class, not one per row.
     *
     * @param  iterable<array{0: string, 1: mixed}>  $pairs  [column, value]
     */
    public function warm(iterable $pairs): void
    {
        $wanted = [];

        foreach ($pairs as [$column, $value]) {
            $class = self::FOREIGN_KEYS[$column] ?? null;

            if ($class === null || ! is_numeric($value)) {
                continue;
            }

            $key = $class.'|'.(int) $value;

            if (! array_key_exists($key, $this->cache)) {
                $wanted[$class][] = (int) $value;
            }
        }

        foreach ($wanted as $class => $ids) {
            /** @var class-string<Model> $class */
            $models = $class::query()
                ->with(self::EAGER[$class] ?? [])
                ->whereIn('id', array_unique($ids))
                ->get();

            foreach ($models as $model) {
                $this->cache[$class.'|'.$model->getKey()] = $this->displayName($model);
            }

            // Ids with no row (deleted since) are cached as null so a second
            // pass does not re-query them.
            foreach ($ids as $id) {
                $this->cache[$class.'|'.$id] ??= null;
            }
        }
    }

    /**
     * Human name behind a foreign-key value, or null when the column is not a
     * known FK / the row no longer exists.
     */
    public function resolve(string $column, mixed $value): ?string
    {
        $class = self::FOREIGN_KEYS[$column] ?? null;

        if ($class === null || ! is_numeric($value)) {
            return null;
        }

        $key = $class.'|'.(int) $value;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $model = $class::query()->with(self::EAGER[$class] ?? [])->find((int) $value);

        return $this->cache[$key] = $model === null ? null : $this->displayName($model);
    }

    /** French label for a column, falling back to a de-underscored title. */
    public static function label(string $column): string
    {
        if (isset(self::FIELD_LABELS[$column])) {
            return self::FIELD_LABELS[$column];
        }

        return ucfirst(str_replace('_', ' ', $column));
    }

    /** True when a column is plumbing noise on a creation entry. */
    public static function isNoise(string $column, ?string $event): bool
    {
        return $event === 'created' && in_array($column, self::NOISE_ON_CREATE, true);
    }

    /**
     * The best human handle a model can offer, suffixed with the student it
     * belongs to when there is one.
     */
    private function displayName(Model $model): string
    {
        $base = $this->baseName($model);
        $student = $this->studentNameOf($model);

        return $student === null ? $base : $base.' — '.$student;
    }

    /** The record's own label, before any student suffix. */
    private function baseName(Model $model): string
    {
        // Employee/Student expose a first+last name helper.
        if (method_exists($model, 'nomComplet')) {
            $full = trim((string) $model->nomComplet());

            if ($full !== '') {
                return $full;
            }
        }

        foreach (['nom_centre', 'nom', 'name', 'reference'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '#'.$model->getKey();
    }

    /**
     * Name of the student a record hangs off, or null when it has none (or IS
     * a student — no point repeating the name twice).
     *
     * Relations are already eager-loaded by warm()/resolve() via self::EAGER,
     * so this costs no extra query; a record whose student row was deleted
     * since simply yields null and the label stays as it was.
     */
    private function studentNameOf(Model $model): ?string
    {
        if ($model instanceof Student) {
            return null;
        }

        $student = match (true) {
            $model instanceof InscriptionFee => $model->inscription?->student,
            $model instanceof Inscription => $model->student,
            $model instanceof Encaissement => $model->student,
            $model instanceof Cheque => $model->student,
            default => null,
        };

        if ($student === null) {
            return null;
        }

        $name = trim((string) $student->nomComplet());

        return $name === '' ? null : $name;
    }
}
