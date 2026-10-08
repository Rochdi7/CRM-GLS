<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Domain\Students\Actions\FusionnerEtudiants;
use App\Domain\Students\Support\FicheEtudiantVide;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Recherche des fiches étudiant pour l'écran de fusion (super-admin).
 *
 * ⚠ Délibérément NON scopée au centre actif ni à l'année : un doublon naît
 * très souvent parce que la personne a été ressaisie dans un AUTRE centre,
 * et c'est exactement la paire qu'il faut pouvoir rapprocher. Les écrans
 * ordinaires (GetStudentsList) gardent leur scoping — celui-ci est un outil
 * de réparation réservé au super-admin, qui voit tout le réseau de toute
 * façon (CLAUDE.md §11, « Deliberate exceptions »).
 *
 * Les fiches déjà fusionnées (suffixe « (doublon fusionné) ») sont exclues
 * de la recherche : elles sont vides et ne doivent plus être proposées.
 *
 * Chaque ligne dit si la fiche est VIDE (FicheEtudiantVide) : c'est elle
 * qu'on fusionne dans l'autre, et la fusion la supprime au lieu de la
 * renommer. `fichesVides()` détecte d'office ces coquilles quand une autre
 * fiche du même nom porte des inscriptions.
 */
final class GetFusionCandidates
{
    private const int LIMITE = 25;

    private const int LIMITE_VIDES = 50;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(string $search): Collection
    {
        $search = trim($search);

        if (mb_strlen($search) < 2) {
            return collect();
        }

        $etudiants = Student::query()
            ->with('etablissement:id,nom_centre')
            ->withCount(['inscriptions', 'encaissements'])
            ->where('nom', 'not ilike', '%'.FusionnerEtudiants::SUFFIXE_DOUBLON)
            ->where(function ($q) use ($search): void {
                // ILIKE : PostgreSQL LIKE est sensible à la casse (CLAUDE.md §17).
                $q->where('nom', 'ilike', "%{$search}%")
                    ->orWhere('prenom', 'ilike', "%{$search}%")
                    ->orWhere('reference', 'ilike', "%{$search}%")
                    ->orWhere('legacy_ref', 'ilike', "%{$search}%")
                    ->orWhere('telephone', 'ilike', "%{$search}%");
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->limit(self::LIMITE)
            ->get();

        // Une requête pour toute la page, jamais une par ligne (§17).
        $vides = FicheEtudiantVide::scope(Student::query()->whereKey($etudiants->modelKeys()))
            ->pluck('id')
            ->flip();

        return $etudiants->map(fn (Student $s): array => [
                'id' => $s->id,
                'reference' => $s->reference,
                'legacyRef' => $s->legacy_ref,
                'nom' => $s->nom,
                'prenom' => $s->prenom,
                'telephone' => $s->telephone,
                'dateNaissance' => $s->date_naissance?->format('d/m/Y'),
                'centre' => $s->etablissement?->nom_centre,
                'inscriptionsCount' => $s->inscriptions_count,
                'encaissementsCount' => $s->encaissements_count,
                'estVide' => $vides->has($s->id),
            ]);
    }

    /**
     * Fiches VIDES qui ont un jumeau du même nom (nom + prénom, casse et
     * espaces ignorés) portant au moins une inscription — la paire à
     * fusionner, la vide disparaissant.
     *
     * ⚠ Le nom seul, jamais le téléphone : des frères et sœurs partagent le
     * numéro du parent, et un rapprochement par téléphone proposerait de
     * fondre deux personnes. Le téléphone et la date de naissance sont
     * AFFICHÉS pour que l'opérateur confirme ; rien ne se fusionne seul.
     *
     * @return array{total: int, paires: list<array<string, mixed>>}
     */
    public function fichesVides(): array
    {
        $cle = fn (string $t): string => "lower(trim({$t}.nom)) || '|' || lower(trim({$t}.prenom))";

        $base = FicheEtudiantVide::scope(Student::query())
            ->where('students.nom', 'not ilike', '%'.FusionnerEtudiants::SUFFIXE_DOUBLON)
            ->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('students as jumeau')
                ->whereColumn('jumeau.id', '<>', 'students.id')
                ->whereRaw($cle('jumeau').' = '.$cle('students'))
                ->whereExists(fn ($i) => $i->selectRaw('1')
                    ->from('inscriptions')
                    ->whereColumn('inscriptions.student_id', 'jumeau.id')));

        $total = (clone $base)->count();

        $vides = $base->with('etablissement:id,nom_centre')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->limit(self::LIMITE_VIDES)
            ->get();

        if ($vides->isEmpty()) {
            return ['total' => $total, 'paires' => []];
        }

        // Les jumeaux de toute la page en UNE requête, rangés par clé de nom.
        $cles = $vides->map(fn (Student $s): string => $this->cleNom($s))->unique()->values();

        $jumeaux = Student::query()
            ->with('etablissement:id,nom_centre')
            ->withCount(['inscriptions', 'encaissements'])
            ->has('inscriptions')
            ->where('nom', 'not ilike', '%'.FusionnerEtudiants::SUFFIXE_DOUBLON)
            ->whereIn(DB::raw($cle('students')), $cles->all())
            ->get()
            ->groupBy(fn (Student $s): string => $this->cleNom($s));

        $paires = [];

        foreach ($vides as $vide) {
            // Le jumeau le plus fourni : c'est celui qu'on garde.
            $garde = ($jumeaux->get($this->cleNom($vide)) ?? collect())
                ->sortByDesc(fn (Student $s): int => $s->inscriptions_count + $s->encaissements_count)
                ->first();

            if ($garde === null) {
                continue;
            }

            $paires[] = [
                'vide' => $this->ligne($vide, 0, 0, true),
                'garde' => $this->ligne($garde, $garde->inscriptions_count, $garde->encaissements_count, false),
                'autresJumeaux' => max(0, ($jumeaux->get($this->cleNom($vide))?->count() ?? 1) - 1),
            ];
        }

        return ['total' => $total, 'paires' => $paires];
    }

    /** Même normalisation que la clé SQL de fichesVides(). */
    private function cleNom(Student $s): string
    {
        return mb_strtolower(trim((string) $s->nom)).'|'.mb_strtolower(trim((string) $s->prenom));
    }

    /**
     * @return array<string, mixed>
     */
    private function ligne(Student $s, int $inscriptions, int $encaissements, bool $estVide): array
    {
        return [
            'id' => $s->id,
            'reference' => $s->reference,
            'legacyRef' => $s->legacy_ref,
            'nom' => $s->nom,
            'prenom' => $s->prenom,
            'telephone' => $s->telephone,
            'dateNaissance' => $s->date_naissance?->format('d/m/Y'),
            'centre' => $s->etablissement?->nom_centre,
            'inscriptionsCount' => $inscriptions,
            'encaissementsCount' => $encaissements,
            'estVide' => $estVide,
        ];
    }
}
