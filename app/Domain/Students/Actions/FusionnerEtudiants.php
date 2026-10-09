<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Domain\Students\Support\FicheEtudiantVide;
use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionHistorique;
use App\Models\Presence;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\Virement;
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
 * La fiche vidée n'est pas supprimée quand elle portait quelque chose
 * (piste d'audit, `legacy_ref` unique par centre, CLAUDE.md §11) : elle est
 * renommée « … (doublon fusionné) » pour sortir des recherches et des listes
 * déroulantes. EXCEPTION (08/10/2026) : une fiche qui était VIDE avant la
 * fusion (FicheEtudiantVide — aucune inscription, aucun paiement, rien) n'a
 * aucun historique à préserver ; elle est SUPPRIMÉE, ses médias passent sur
 * la fiche gardée et l'entrée de journal garde son identité.
 *
 * Réservée au super-admin (`students.merge`, cf.
 * PermissionRegistry::superAdminOnly()) : recoller deux dossiers réunit
 * l'historique financier de deux personnes si l'on se trompe de paire.
 */
final class FusionnerEtudiants
{
    /**
     * Les FK vers `students` qui suivent la personne. Une table oubliée ici laisse des lignes
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
        // Demande de virement (07/10/2026) : comme un paiement, elle suit la
        // personne. Oubliée, elle bloquait la suppression d'une fiche vide
        // et restait orpheline sur une fiche « (doublon fusionné) ».
        [Virement::class, 'student_id'],
    ];

    public const string SUFFIXE_DOUBLON = ' (doublon fusionné)';

    /**
     * @return array{garde: Student, doublon: Student, lignes: array<string, int>, supprime: bool}
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

            // Une moitié de transfert entre centres n'est pas un doublon : la
            // copie et l'original se pointent l'un l'autre et doivent rester
            // deux fiches (TransfertEtudiantCentreTest).
            if ($doublon->transfere_vers_student_id !== null || $doublon->transfere_depuis_student_id !== null
                || $doublon->transferts()->exists()) {
                throw ValidationException::withMessages([
                    'doublon_id' => __('This record is part of a centre transfer and cannot be merged.'),
                ]);
            }

            // Décidé AVANT de déplacer quoi que ce soit : après la boucle,
            // toute fiche fusionnée est vide.
            $etaitVide = FicheEtudiantVide::estVide($doublon);

            $lignes = [];

            // Avant le déplacement en masse : une séance où les DEUX fiches
            // ont été appelées violerait l'index unique (seance_id,
            // student_id) — production 08/10/2026, GLS-59456CE2.
            $conflitsPresence = $this->resoudreConflitsPresence($garde, $doublon);

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
            $identite = [
                'garde_id' => $garde->getKey(),
                'garde_reference' => $garde->reference,
                'doublon_id' => $doublon->getKey(),
                'doublon_reference' => $doublon->reference,
                'doublon_nom' => $ancienNom.' '.$doublon->prenom,
                'doublon_legacy_ref' => $doublon->legacy_ref,
                'doublon_telephone' => $doublon->telephone,
                'doublon_centre_id' => $doublon->etablissement_id,
            ];

            if ($etaitVide) {
                $medias = $this->recupererMedias($garde, $doublon);
                $doublon->delete();

                activity('student')
                    ->performedOn($garde)
                    ->event('students_merged')
                    ->withProperties($identite + ['lignes' => [], 'fiche_vide_supprimee' => true, 'medias' => $medias])
                    ->log("Fiche vide {$doublon->reference} supprimée (fusionnée dans {$garde->reference})");

                return ['garde' => $garde, 'doublon' => $doublon, 'lignes' => [], 'supprime' => true];
            }

            $doublon->update(['nom' => $doublon->nom.self::SUFFIXE_DOUBLON]);

            activity('student')
                ->performedOn($garde)
                ->event('students_merged')
                ->withProperties($identite + ['lignes' => $lignes, 'conflits_presence' => $conflitsPresence])
                ->log("Fiche {$doublon->reference} fusionnée dans {$garde->reference}");

            return ['garde' => $garde, 'doublon' => $doublon, 'lignes' => $lignes, 'supprime' => false];
        });
    }

    /**
     * Ordre de préférence quand les deux fiches ont été appelées sur la même
     * séance : c'est la même personne, donc si l'un des deux noms a été
     * coché « Présent », elle ÉTAIT là — l'« Absent » de l'autre nom décrit
     * seulement la fiche en double, pas la personne.
     */
    private const array RANG_PRESENCE = [
        Presence::STATUT_PRESENT => 4,
        Presence::STATUT_RETARD => 3,
        Presence::STATUT_JUSTIFIE => 2,
        Presence::STATUT_ABSENT => 1,
    ];

    /**
     * Une seule ligne d'appel par séance après la fusion. La ligne de la
     * fiche gardée reste ; elle reprend le statut (et la note) du doublon
     * quand celui-ci est plus favorable. La ligne du doublon est ensuite
     * supprimée via Eloquent, donc journalisée par Auditable.
     *
     * @return list<array{seance_id: int, garde: string, doublon: string, retenu: string}>
     */
    private function resoudreConflitsPresence(Student $garde, Student $doublon): array
    {
        $gardees = Presence::query()
            ->where('student_id', $garde->getKey())
            ->whereIn('seance_id', Presence::query()->select('seance_id')->where('student_id', $doublon->getKey()))
            ->lockForUpdate()
            ->get()
            ->keyBy('seance_id');

        if ($gardees->isEmpty()) {
            return [];
        }

        $doublons = Presence::query()
            ->where('student_id', $doublon->getKey())
            ->whereIn('seance_id', $gardees->keys())
            ->lockForUpdate()
            ->get();

        $conflits = [];

        foreach ($doublons as $ligneDoublon) {
            $ligneGarde = $gardees->get($ligneDoublon->seance_id);
            $avant = (string) $ligneGarde->statut;

            if ((self::RANG_PRESENCE[$ligneDoublon->statut] ?? 0) > (self::RANG_PRESENCE[$avant] ?? 0)) {
                $ligneGarde->statut = $ligneDoublon->statut;
                $ligneGarde->note = $ligneGarde->note ?: $ligneDoublon->note;
                $ligneGarde->save();
            }

            $conflits[] = [
                'seance_id' => (int) $ligneDoublon->seance_id,
                'garde' => $avant,
                'doublon' => (string) $ligneDoublon->statut,
                'retenu' => (string) $ligneGarde->statut,
            ];

            $ligneDoublon->delete();
        }

        return $conflits;
    }

    /**
     * Les documents de la fiche vide passent sur la fiche gardée ; sa photo
     * seulement si la fiche gardée n'en a pas (collection `photo` à fichier
     * unique : la déplacer remplacerait la bonne). Sans cela, supprimer la
     * fiche effacerait ses fichiers avec elle.
     *
     * @return list<string> noms des fichiers récupérés
     */
    private function recupererMedias(Student $garde, Student $doublon): array
    {
        $recuperes = [];

        foreach ($doublon->getMedia('documents') as $media) {
            $media->move($garde, 'documents');
            $recuperes[] = $media->file_name;
        }

        $photo = $doublon->getFirstMedia('photo');

        if ($photo !== null && ! $garde->hasMedia('photo')) {
            $photo->move($garde, 'photo');
            $recuperes[] = $photo->file_name;
        }

        return $recuperes;
    }
}
