<?php

declare(strict_types=1);

namespace App\Domain\Students\Support;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Une fiche étudiant VIDE : rien ne la référence nulle part.
 *
 * Cas réel (08/10/2026) : la même personne saisie deux fois, une fiche porte
 * les inscriptions et les paiements, l'autre n'a jamais rien reçu. Fusionner
 * cette coquille ne doit pas laisser une « (doublon fusionné) » de plus dans
 * la base : elle n'a aucun historique à garder, donc elle se SUPPRIME.
 *
 * ⚠ UNE définition, deux formes — `scope()` pour la détection
 * (GetFusionCandidates) et `estVide()` pour la décision sous verrou
 * (FusionnerEtudiants). Une table qui pointe vers `students` et manque ici
 * ferait supprimer une fiche qui porte encore quelque chose : la FK
 * `restrictOnDelete` ferait alors échouer la fusion entière, et une FK en
 * cascade (inscriptions_historique) effacerait de l'historique en silence.
 */
final class FicheEtudiantVide
{
    /**
     * Chaque (table, colonne) qui référence `students`.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const array REFERENCES = [
        ['inscriptions', 'student_id'],
        ['encaissements', 'student_id'],
        ['cheques', 'student_id'],
        ['presences', 'student_id'],
        ['inscriptions_historique', 'student_id'],
        ['remboursements', 'beneficiaire_id'],
        ['virements', 'student_id'],
        ['student_transfers', 'student_id'],
        ['student_transfers', 'nouveau_student_id'],
        ['students', 'transfere_vers_student_id'],
        ['students', 'transfere_depuis_student_id'],
    ];

    /**
     * Restreint une requête `students` aux fiches vides.
     *
     * @param  Builder<Student>  $query
     * @return Builder<Student>
     */
    public static function scope(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        // Une fiche liée à un transfert d'un côté OU de l'autre n'est pas
        // une coquille : c'est une moitié de transfert.
        $query->whereNull("{$table}.transfere_vers_student_id")
            ->whereNull("{$table}.transfere_depuis_student_id");

        foreach (self::REFERENCES as $i => [$refTable, $colonne]) {
            $alias = "ref_vide_{$i}";
            $query->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from("{$refTable} as {$alias}")
                ->whereColumn("{$alias}.{$colonne}", "{$table}.id"));
        }

        return $query;
    }

    public static function estVide(Student $student): bool
    {
        if ($student->transfere_vers_student_id !== null || $student->transfere_depuis_student_id !== null) {
            return false;
        }

        foreach (self::REFERENCES as [$refTable, $colonne]) {
            if (DB::table($refTable)->where($colonne, $student->getKey())->exists()) {
                return false;
            }
        }

        return true;
    }
}
