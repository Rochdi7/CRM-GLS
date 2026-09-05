<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionHistorique;
use App\Models\Presence;
use App\Models\Remboursement;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fusionne DEUX fiches étudiant qui décrivent la même personne.
 *
 * L'ancien CRM (et une double saisie au comptoir) laisse la même personne
 * exister deux fois : deux `legacy_ref`, un nom orthographié « MOHAMED » ici
 * et « MOHAMMED » là. Ses inscriptions vivent alors sur une fiche et ses
 * paiements sur l'autre, si bien qu'AppliquerAvance refuse l'allocation —
 * un frais doit appartenir à l'étudiant de l'avance.
 *
 * ⚠ AUCUN MONTANT N'EST TOUCHÉ. Seule la colonne `student_id` est
 * réécrite sur les six tables qui pointent vers `students` :
 * `date_paiement`, `caisse_id`, `agent_id`, `montant`, `methode`,
 * `inscription_fee_id` et `caisses.solde` restent tels quels. Une fusion
 * ne déplace pas d'argent, elle recolle deux moitiés d'un même dossier.
 *
 * La fiche vidée n'est JAMAIS supprimée (piste d'audit, `legacy_ref`
 * unique par centre, CLAUDE.md §11) : elle est renommée
 * « … (doublon fusionné) » pour sortir des recherches et des listes
 * déroulantes.
 *
 * Réservée au super-admin (`students.merge`, cf.
 * PermissionRegistry::superAdminOnly()) : recoller deux dossiers réunit
 * l'historique financier de deux personnes si l'on se trompe de paire.
 */
final class FusionnerEtudiants
{
    /**
     * Les six FK vers `students`. Une table oubliée ici laisse des lignes
     * orphelines sur la fiche vidée — le remboursement porte
     * `beneficiaire_id`, pas `student_id` (piège vérifié en base).
     *
     * @var list<array{0: class-string<\Illuminate\Database\Eloquent\Model>, 1: string}>
     */
    private const array RELATIONS = [
        [Inscription::class, 'student_id'],
        [Encaissement::class, 'student_id'],
        [Cheque::class, 'student_id'],
        [Presence::class, 'student_id'],
        [InscriptionHistorique::class, 'student_id'],
        [Remboursement::class, 'beneficiaire_id'],
    ];

    public const string SUFFIXE_DOUBLON = ' (doublon fusionné)';

    /**
     * @return array{garde: Student, doublon: Student, lignes: array<string, int>}
     */
    public function handle(Student $garde, Student $doublon): array
    {
        if ($garde->getKey() === $doublon->getKey()) {
            throw ValidationException::withMessages([
                'doublon_id' => __('A student cannot be merged with themselves.'),
            ]);
        }

        return DB::transaction(function () use ($garde, $doublon): array {
            // Re-lecture sous verrou : deux fusions simultanées sur la même
            // paire (double-clic, deux onglets) déplaceraient chacune les
            // mêmes lignes et renommeraient deux fois la fiche vidée.
            $garde = Student::query()->whereKey($garde->getKey())->lockForUpdate()->firstOrFail();
            $doublon = Student::query()->whereKey($doublon->getKey())->lockForUpdate()->firstOrFail();

            if (str_ends_with($doublon->nom, self::SUFFIXE_DOUBLON)) {
                throw ValidationException::withMessages([
                    'doublon_id' => __('This student record has already been merged.'),
                ]);
            }

            if (str_ends_with($garde->nom, self::SUFFIXE_DOUBLON)) {
                throw ValidationException::withMessages([
                    'garde_id' => __('The record to keep has already been merged into another one.'),
                ]);
            }

            $lignes = [];

            foreach (self::RELATIONS as [$modele, $colonne]) {
                $n = $modele::query()->where($colonne, $doublon->getKey())->count();

                if ($n === 0) {
                    continue;
                }

                // update() direct : on ne réécrit qu'une FK sur des milliers
                // de lignes possibles (86 présences pour un seul étudiant).
                // Aucun événement modèle n'est nécessaire — rien d'autre ne
                // change, et l'activité est journalisée une fois ci-dessous
                // avec le détail par table.
                $modele::query()->where($colonne, $doublon->getKey())
                    ->update([$colonne => $garde->getKey()]);

                $lignes[class_basename($modele)] = $n;
            }

            $ancienNom = $doublon->nom;
            $doublon->update(['nom' => $doublon->nom.self::SUFFIXE_DOUBLON]);

            activity('student')
                ->performedOn($garde)
                ->event('students_merged')
                ->withProperties([
                    'garde_id' => $garde->getKey(),
                    'garde_reference' => $garde->reference,
                    'doublon_id' => $doublon->getKey(),
                    'doublon_reference' => $doublon->reference,
                    'doublon_nom' => $ancienNom.' '.$doublon->prenom,
                    'doublon_legacy_ref' => $doublon->legacy_ref,
                    'lignes' => $lignes,
                ])
                ->log("Fiche {$doublon->reference} fusionnée dans {$garde->reference}");

            return ['garde' => $garde, 'doublon' => $doublon, 'lignes' => $lignes];
        });
    }
}
