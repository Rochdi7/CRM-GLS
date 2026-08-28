<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Makes each group's « Détails paiement » match the old CRM's own matrix,
 * cell for cell — the matrix is the SOLE authority on which group a payment
 * belongs to.
 *
 * Input: one XLSX per group, exported from the old CRM's group statistics
 * screen and NAMED AFTER THE GROUP (« OUASSIMA 13H.xlsx »). The file carries
 * no group name, so the filename is the only link — matched against
 * groups.nom in the centre, never guessed.
 *
 * Two passes, in this order:
 *   1. RELEASE — every payment sitting on a matrix group's inscription in a
 *      cell the matrix does NOT show becomes an avance (detached, never
 *      deleted). Without this pass, money mis-filed by an earlier rule-based
 *      move stays put and inflates the group: 135 300 DH landed in Herr
 *      Driss 10H/13h that the matrix attributes elsewhere (28/08/2026).
 *   2. PLACE — every non-zero matrix cell is settled from the student's
 *      avances onto the same-named fee of his inscription IN THAT GROUP,
 *      oldest payment first, capped at the cell's amount.
 *
 * Both passes use the existing money actions, so every §11 invariant holds:
 * rows are never deleted, `caisses.solde` never moves, date_paiement is
 * preserved. Money never crosses students — AppliquerAvance refuses it.
 *
 * ⚠ Payments on groups NOT in the folder are never touched: the matrix says
 * nothing about them, so nothing is inferred.
 *
 * The file's trailing « Total » row is skipped explicitly — reading it as a
 * student silently doubled every column.
 *
 * Usage:
 *   php artisan matrice:appliquer --dossier="data test local/groups active/marrakech" --dry-run
 *   php artisan matrice:appliquer --dossier="data test local/groups active/marrakech"
 */
final class AppliquerMatriceGroupe extends Command
{
    protected $signature = 'matrice:appliquer
        {--dossier= : Dossier des XLSX, un par groupe, nommés comme le groupe}
        {--centre= : Centre (id ou partie du nom)}
        {--dry-run : Afficher ce qui serait fait sans rien modifier}';

    protected $description = "Aligne « Détails paiement » de chaque groupe sur la matrice de l'ancien CRM, cellule par cellule.";

    public function handle(ConvertirEncaissementsEnAvance $convertir, AppliquerAvance $appliquer): int
    {
        $dossier = rtrim((string) $this->option('dossier'), '/\\');

        if ($dossier === '' || ! is_dir($dossier)) {
            $this->error('--dossier est obligatoire et doit exister.');

            return self::FAILURE;
        }

        // Excel's lock files (~$name.xlsx) are not workbooks.
        $fichiers = array_values(array_filter(
            glob($dossier.DIRECTORY_SEPARATOR.'*.xlsx') ?: [],
            static fn (string $f): bool => ! str_starts_with(basename($f), '~$')
        ));

        if ($fichiers === []) {
            $this->error('Aucun .xlsx dans '.$dossier);

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        // ---- Read every matrix first: the RELEASE pass needs the full picture.
        /** @var array<int, array{groupe: Group, cellules: array<string, array{student: Student, inscription: Inscription, fee: InscriptionFee, montant: float}>}> */
        $matrices = [];
        $introuvables = 0;

        foreach ($fichiers as $fichier) {
            $nomGroupe = pathinfo($fichier, PATHINFO_FILENAME);
            $groupe = $this->trouverGroupe($nomGroupe);

            if ($groupe === null) {
                $this->warn(sprintf('  Groupe « %s » introuvable — fichier ignoré.', $nomGroupe));

                continue;
            }

            $cellules = [];

            foreach ($this->lireMatrice($fichier) as [$nomEtudiant, $nomFrais, $montant]) {
                $student = $this->trouverEtudiant($groupe, $nomEtudiant);
                $inscription = $student ? $this->inscriptionDans($groupe, $student) : null;
                $fee = $inscription ? $this->fraisDe($inscription, $nomFrais) : null;

                if ($student === null || $inscription === null || $fee === null) {
                    $introuvables++;

                    continue;
                }

                $cellules[$student->id.'|'.$fee->id] = [
                    'student' => $student, 'inscription' => $inscription, 'fee' => $fee, 'montant' => $montant,
                ];
            }

            $matrices[$groupe->id] = ['groupe' => $groupe, 'cellules' => $cellules];
        }

        $this->info(sprintf('%d groupe(s) lu(s)%s.', count($matrices), $dry ? '   [DRY-RUN]' : ''));

        // ---- Pass 1: RELEASE what the matrix does not want in these groups.
        $liberes = 0;
        $montantLibere = 0.0;

        foreach ($matrices as ['groupe' => $groupe, 'cellules' => $cellules]) {
            $aLiberer = Encaissement::query()
                ->whereNotNull('inscription_fee_id')
                ->whereHas('fee.inscription', fn ($i) => $i->where('group_id', $groupe->id))
                ->with('fee.inscription')
                ->get()
                ->filter(fn (Encaissement $e): bool => ! isset($cellules[$e->student_id.'|'.$e->inscription_fee_id]));

            if ($aLiberer->isEmpty()) {
                continue;
            }

            $this->line(sprintf('  %-28s libère %3d paiement(s)  %10s MAD', $groupe->nom, $aLiberer->count(), number_format((float) $aLiberer->sum('montant'), 2, '.', '')));

            if (! $dry) {
                foreach ($aLiberer->groupBy(fn (Encaissement $e): int => (int) $e->fee->inscription_id) as $inscriptionId => $lot) {
                    DB::transaction(fn () => $convertir->handle(Inscription::findOrFail($inscriptionId), $lot->pluck('id')->map('intval')->all()));
                }
            }

            $liberes += $aLiberer->count();
            $montantLibere += (float) $aLiberer->sum('montant');
        }

        // ---- Pass 2: PLACE every expected cell from the student's avances.
        $places = 0;
        $montantPlace = 0.0;
        $dejaOk = 0;
        $sansFonds = [];

        foreach ($matrices as ['groupe' => $groupe, 'cellules' => $cellules]) {
            $lignes = [];

            foreach ($cellules as ['student' => $student, 'fee' => $fee, 'montant' => $attendu]) {
                // What the cell still needs = expected − what is ALREADY on
                // it. Money already sitting on this very cell is correct by
                // definition (the RELEASE pass only frees rows the matrix does
                // not want), so it counts in both modes.
                $fee = $fee->fresh();
                $manque = round($attendu - $fee->montantPaye(), 2);

                if ($manque <= 0.0) {
                    $dejaOk++;

                    continue;
                }

                $avances = $this->avancesDe($student, $fee->nom, $dry ? $groupe : null);

                foreach ($avances as $avance) {
                    if ($manque <= 0.0) {
                        break;
                    }

                    $part = min($manque, (float) ($dry ? $avance->montant : $avance->montantRestant()));

                    if ($part <= 0.0) {
                        continue;
                    }

                    $lignes[] = [$avance, $fee, $part];
                    $manque = round($manque - $part, 2);
                }

                if ($manque > 0.0) {
                    $sansFonds[] = sprintf('%s — %s : manque %s', trim($student->prenom.' '.$student->nom), $fee->nom, number_format($manque, 2, '.', ''));
                }
            }

            if ($lignes === []) {
                continue;
            }

            $this->line(sprintf('  %-28s place  %3d paiement(s)  %10s MAD', $groupe->nom, count($lignes), number_format(array_sum(array_column($lignes, 2)), 2, '.', '')));

            if (! $dry) {
                foreach ($lignes as [$avance, $fee, $part]) {
                    DB::transaction(function () use ($appliquer, $avance, $fee, $part): void {
                        $avance = $avance->fresh();
                        $cible = $fee->fresh();

                        if ($avance === null || $cible === null || ! $avance->isAvance()) {
                            return;
                        }

                        $part = min($part, $avance->montantRestant(), round((float) $cible->montant - $cible->montantPaye(), 2));

                        if ($part > 0.0) {
                            $appliquer->handle($avance, $cible, $part);
                        }
                    });
                }
            }

            $places += count($lignes);
            $montantPlace += array_sum(array_column($lignes, 2));
        }

        $this->line('');
        $this->info(sprintf(
            '%sLibérés : %d (%s MAD)   Placés : %d (%s MAD)   Déjà corrects : %d',
            $dry ? '[DRY-RUN] ' : '',
            $liberes, number_format($montantLibere, 2, '.', ''),
            $places, number_format($montantPlace, 2, '.', ''),
            $dejaOk
        ));

        if ($introuvables > 0) {
            $this->warn(sprintf('  %d cellule(s) ignorée(s) : étudiant, inscription ou frais introuvable.', $introuvables));
        }

        if ($sansFonds !== []) {
            $this->warn(sprintf('  %d cellule(s) que l\'étudiant n\'a pas les fonds pour couvrir :', count($sansFonds)));

            foreach (array_slice($sansFonds, 0, 10) as $s) {
                $this->line('      '.$s);
            }
        }

        return self::SUCCESS;
    }

    /**
     * The student's money available to settle this fee name: his avances
     * (fee-less rows with a remaining balance), oldest first.
     *
     * In dry-run the RELEASE pass has not run yet, so the rows it WOULD free
     * — his payments on this same fee name sitting in a matrix group — are
     * counted as available too, otherwise the preview under-reports.
     *
     * @return Collection<int, Encaissement>
     */
    private function avancesDe(Student $student, string $nomFrais, ?Group $groupeCourant): Collection
    {
        $avances = Encaissement::query()
            ->where('student_id', $student->id)
            ->whereNull('inscription_fee_id')
            ->orderBy('date_paiement')
            ->get()
            ->filter(fn (Encaissement $e): bool => $e->montantRestant() > 0.0);

        if ($groupeCourant === null) {
            return $avances;
        }

        $encoreRattaches = Encaissement::query()
            ->where('student_id', $student->id)
            ->whereNotNull('inscription_fee_id')
            ->whereHas('fee.inscription', fn ($i) => $i->where('group_id', '!=', $groupeCourant->id))
            ->with('fee')
            ->orderBy('date_paiement')
            ->get()
            ->filter(fn (Encaissement $e): bool => $this->cleFrais((string) ($e->fee?->nom ?? '')) === $this->cleFrais($nomFrais));

        return $avances->concat($encoreRattaches);
    }

    private function trouverGroupe(string $nom): ?Group
    {
        return Group::query()
            ->where('nom', $nom)
            ->when($this->option('centre'), function ($q, $centre): void {
                $q->whereHas('etablissement', fn ($e) => is_numeric($centre)
                    ? $e->whereKey((int) $centre)
                    : $e->where('nom_centre', 'ilike', '%'.$centre.'%'));
            })
            ->first();
    }

    private function trouverEtudiant(Group $groupe, string $nom): ?Student
    {
        $cle = $this->cle($nom);

        return Student::query()
            ->where('etablissement_id', $groupe->etablissement_id)
            ->whereRaw("lower(trim(prenom||' '||nom)) = ? or lower(trim(nom||' '||prenom)) = ?", [$cle, $cle])
            ->first();
    }

    private function inscriptionDans(Group $groupe, Student $student): ?Inscription
    {
        return Inscription::query()
            ->where('group_id', $groupe->id)
            ->where('student_id', $student->id)
            ->orderByRaw('case statut when ? then 0 else 1 end', [Inscription::STATUT_ACTIVE])
            ->first();
    }

    private function fraisDe(Inscription $inscription, string $nomFrais): ?InscriptionFee
    {
        return InscriptionFee::query()
            ->where('inscription_id', $inscription->id)
            ->whereNull('masque_le')
            ->get()
            ->first(fn (InscriptionFee $f): bool => $this->cleFrais($f->nom) === $this->cleFrais($nomFrais));
    }

    /**
     * The matrix as (student, fee, amount) triples — zeros dropped, the
     * trailing « Total » row skipped.
     *
     * @return list<array{0: string, 1: string, 2: float}>
     */
    private function lireMatrice(string $chemin): array
    {
        $reader = new Reader();
        $reader->open($chemin);
        $entetes = null;
        $cellules = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $c = array_map(static fn (mixed $v): string => trim((string) $v), $row->toArray());

                    if ($entetes === null) {
                        if (($c[0] ?? '') === '' && ($c[1] ?? '') === '') {
                            continue;
                        }

                        $entetes = $c;

                        continue;
                    }

                    $etudiant = $c[1] ?? '';

                    // Only student rows carry a « #n » in the first cell — the
                    // closing « Total » row does not, and is not a student.
                    if ($etudiant === '' || ! str_starts_with($c[0] ?? '', '#')) {
                        continue;
                    }

                    foreach ($entetes as $i => $entete) {
                        if (! str_starts_with($entete, 'Frais')) {
                            continue;
                        }

                        if (preg_match('/([\d.]+)/', $c[$i] ?? '', $m) === 1 && (float) $m[1] > 0) {
                            $cellules[] = [$etudiant, $entete, (float) $m[1]];
                        }
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $cellules;
    }

    private function cle(string $valeur): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim(mb_strtolower($valeur)));
    }

    /** Fee names, accent- and space-insensitive (the export mangles both). */
    private function cleFrais(string $valeur): string
    {
        $valeur = mb_strtolower($valeur);
        $valeur = strtr($valeur, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ô' => 'o', 'û' => 'u',
            'ù' => 'u', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'ï' => 'i',
        ]);

        return (string) preg_replace('/[^a-z0-9]/', '', $valeur);
    }
}
