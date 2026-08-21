<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Student;

/**
 * Checks normalized row data against the same fixed-value lists the app's
 * own Form Requests validate against — exact case+accent match, never
 * case-folded. Pure validation: never mutates the row, never touches the
 * DB. Returns a list of {field, code, message} — empty means valid.
 */
final class ImportValidator
{
    /**
     * Inscription::STATUTS only lists the 3 UI-offered values; the
     * historical schema space also includes Expirée/Archivée (CLAUDE.md
     * §11) which legacy exports may legitimately carry.
     *
     * Note that InscriptionImporter::translateStatut() already rewrites the
     * old CRM's "Archivée" to this app's "Changement" before validation
     * runs — the two systems mean the same thing by different names, and
     * that is the ONLY vocabulary difference between them. Archivée stays
     * in this list anyway so a pre-existing row or another import path
     * carrying it is never rejected by the validator itself.
     */
    public const array INSCRIPTION_STATUTS_ACCEPTED = [
        Inscription::STATUT_ACTIVE,
        Inscription::STATUT_ANNULEE,
        Inscription::STATUT_CHANGEMENT,
        Inscription::STATUT_EXPIREE,
        Inscription::STATUT_ARCHIVEE,
    ];

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array{field: string, code: string, message: string}>
     */
    public function validateStudent(array $row): array
    {
        $errors = [];

        if (($row['prenom'] ?? '') === '') {
            $errors[] = $this->error('prenom', 'required', 'Le prénom est obligatoire.');
        }

        if (($row['nom'] ?? '') === '') {
            $errors[] = $this->error('nom', 'required', 'Le nom est obligatoire.');
        }

        $sexe = $row['sexe'] ?? null;

        if ($sexe !== null && $sexe !== '' && ! in_array($sexe, Student::SEXES, true)) {
            $errors[] = $this->error('sexe', 'invalid_enum', sprintf(
                'Sexe "%s" ne correspond à aucune valeur autorisée (%s).',
                $sexe,
                implode(', ', Student::SEXES)
            ));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array{field: string, code: string, message: string}>
     */
    public function validateInscription(array $row): array
    {
        $errors = [];

        if (empty($row['student_id'])) {
            $errors[] = $this->error('student_id', 'required', "L'étudiant n'a pas été résolu.");
        }

        if (empty($row['group_id'])) {
            $errors[] = $this->error('group_id', 'required', "Le groupe n'a pas été résolu.");
        }

        if (($row['date_inscription'] ?? null) === null) {
            $errors[] = $this->error('date_inscription', 'required', "La date d'inscription est obligatoire.");
        }

        $statut = $row['statut'] ?? null;

        if ($statut === null || $statut === '' || ! in_array($statut, self::INSCRIPTION_STATUTS_ACCEPTED, true)) {
            $errors[] = $this->error('statut', 'invalid_enum', sprintf(
                'Statut "%s" ne correspond à aucune valeur autorisée (%s).',
                (string) $statut,
                implode(', ', self::INSCRIPTION_STATUTS_ACCEPTED)
            ));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array{field: string, code: string, message: string}>
     */
    public function validateEncaissement(array $row): array
    {
        $errors = [];

        if (empty($row['student_id'])) {
            $errors[] = $this->error('student_id', 'required', "L'étudiant n'a pas été résolu.");
        }

        if (($row['montant'] ?? null) === null) {
            $errors[] = $this->error('montant', 'required', 'Le montant est obligatoire.');
        }

        if (($row['date_paiement'] ?? null) === null) {
            $errors[] = $this->error('date_paiement', 'required', 'La date de paiement est obligatoire.');
        }

        $methode = $row['methode'] ?? null;

        if ($methode === null || $methode === '' || ! in_array($methode, Encaissement::METHODES, true)) {
            $errors[] = $this->error('methode', 'invalid_enum', sprintf(
                'Méthode "%s" ne correspond à aucune valeur autorisée (%s).',
                (string) $methode,
                implode(', ', Encaissement::METHODES)
            ));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array{field: string, code: string, message: string}>
     */
    public function validatePresence(array $row): array
    {
        $errors = [];

        if (empty($row['student_id'])) {
            $errors[] = $this->error('student_id', 'required', "L'élève n'a pas été résolu.");
        }

        if (empty($row['group_id'])) {
            $errors[] = $this->error('group_id', 'required', "Le groupe n'a pas été résolu.");
        }

        if (($row['date_seance'] ?? null) === null) {
            $errors[] = $this->error('date_seance', 'required', 'La date de la séance est obligatoire.');
        }

        $statut = $row['statut'] ?? null;

        // The export writes a literal "-" when the roll call was never
        // filled in for that line. That is a missing value, not an unknown
        // one, so it gets its own message rather than the enum complaint.
        if ($statut === null || $statut === '' || $statut === '-') {
            $errors[] = $this->error('statut', 'required', 'Aucun statut de présence sur cette ligne.');

            return $errors;
        }

        if (! in_array($statut, Presence::STATUTS, true)) {
            $errors[] = $this->error('statut', 'invalid_enum', sprintf(
                'Statut "%s" ne correspond à aucune valeur autorisée (%s).',
                (string) $statut,
                implode(', ', Presence::STATUTS)
            ));
        }

        return $errors;
    }

    /**
     * @return array{field: string, code: string, message: string}
     */
    private function error(string $field, string $code, string $message): array
    {
        return ['field' => $field, 'code' => $code, 'message' => $message];
    }
}
