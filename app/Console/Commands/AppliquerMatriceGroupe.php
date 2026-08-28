<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
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
    /** Legacy label (normalized) => catalogue fee (normalized). */
    private const array ALIAS_FRAIS = [
        'fraisdinscription' => 'fraisdinscriptiona1a2b1',
        'fraisdinscription1' => 'fraisdinscriptiona1a2b1',
        'fraisdinscriptiona1' => 'fraisdinscriptiona1a2b1',
        'fraisdinscriptiona2' => 'fraisdinscriptiona1a2b1',
        'fraisdinscriptionb1' => 'fraisdinscriptiona1a2b1',
        'fraisdinscription2' => 'fraisdinscriptionb2',
        'osda1' => 'fraisdexamosda1',
        'osdb1' => 'fraisdexamosdb1',
        'osdb2' => 'fraisdexamosdb2',
        'examenosd' => 'fraisdexamosdb1',
    ];

    private int $lignesRetablies = 0;

    private int $montantsReleves = 0;

    /** @var array<string, list<string>> motif => cells */
    private array $ignorees = [];

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
            $listes = [];

            foreach ($this->lireMatrice($fichier) as [$nomEtudiant, $nomFrais, $montant]) {
                $student = $this->trouverEtudiant($groupe, $nomEtudiant);

                if ($student !== null) {
                    $listes[$student->id] = true;
                }

                $inscription = $student ? $this->inscriptionDans($groupe, $student) : null;
                $fee = $inscription ? $this->fraisDe($inscription, $nomFrais, $dry) : null;

                if ($student === null || $inscription === null || $fee === null) {
                    $introuvables++;
                    $motif = $student === null ? 'étudiant absent du centre' : ($inscription === null ? 'pas inscrit dans ce groupe' : 'frais hors catalogue « '.$nomFrais.' »');
                    $this->ignorees[$motif][] = $groupe->nom.' / '.$nomEtudiant;

                    continue;
                }

                $cellules[$student->id.'|'.$fee->id] = [
                    'student' => $student, 'inscription' => $inscription, 'fee' => $fee, 'montant' => $montant,
                ];
            }

            $matrices[$groupe->id] = ['groupe' => $groupe, 'cellules' => $cellules, 'listes' => $listes];
        }

        $this->info(sprintf('%d groupe(s) lu(s)%s.', count($matrices), $dry ? '   [DRY-RUN]' : ''));

        // ---- Pass 1: RELEASE what the matrix does not want in these groups.
        $liberes = 0;
        $montantLibere = 0.0;

        foreach ($matrices as ['groupe' => $groupe, 'cellules' => $cellules, 'listes' => $listes]) {
            $aLiberer = Encaissement::query()
                ->whereNotNull('inscription_fee_id')
                ->whereHas('fee.inscription', fn ($i) => $i->where('group_id', $groupe->id))
                ->with('fee.inscription')
                ->get()
                // ⚠ Only IMPORTED money. A payment cashed in the CRM since
                // the import (Rabat/Marrakech went live 2 days before this
                // run) is newer than the matrix, so its absence from the
                // file says nothing — releasing it would undo the cashier's
                // work (28/08/2026).
                // ⚠ And only for students the file LISTS. The old CRM drops a
                // student who left the group from its matrix, so their
                // absence says nothing about where their money is — 283
                // Agadir/Online rows were freed with nowhere to go (28/08/2026).
                ->filter(function (Encaissement $e) use ($listes, $cellules): bool {
                    if (! $this->estImporte($e) || ! isset($listes[$e->student_id])) {
                        return false;
                    }

                    $cle = $e->student_id.'|'.$e->inscription_fee_id;

                    if (! isset($cellules[$cle])) {
                        return true;
                    }

                    // Cell exists but holds MORE than the matrix shows
                    // (1 200 on a month wimschool shows at 600): free the
                    // whole cell, PLACE refills exactly the expected amount
                    // and the surplus goes where the matrix wants it.
                    // Kénitra AHMED 16h: Août +5 100 / Mai -3 300 (29/08/2026).
                    $fee = $cellules[$cle]['fee'];

                    return $fee->exists && $fee->montantPaye() > $cellules[$cle]['montant'] + 0.005;
                });

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
                $fee = $fee->exists ? $fee->fresh() : $fee;

                // The matrix shows what was PAID on this fee; a fee line
                // billed below that (a 78 DH inscription fee carrying 300 DH
                // in the old CRM) would make AppliquerAvance refuse the
                // surplus for ever — the plan re-listed the same 222 DH on
                // every pass (28/08/2026). Raise the amount due to what was
                // actually paid; montant_initial keeps the original.
                if ($attendu > (float) $fee->montant + 0.005) {
                    if (! $dry && $fee->exists) {
                        $fee->update(['montant' => $attendu]);
                    }

                    $fee->montant = $attendu;
                    $this->montantsReleves++;
                }

                $manque = round(min($attendu, (float) $fee->montant) - $fee->montantPaye(), 2);

                if ($manque <= 0.0) {
                    $dejaOk++;

                    continue;
                }

                // ⚠ Same pool in BOTH modes. The real run once drew from
                // avances only, so a payment still filed on the student's
                // previous-year inscription (Rachid 16H30 → HALA 16H30,
                // WASIM TARMAM, 28/08/2026) was never brought over: the matrix
                // showed it, the dry-run planned it, the apply skipped it.
                $avances = $this->avancesDe($student, $fee->nom, $groupe);

                foreach ($avances as $avance) {
                    if ($manque <= 0.0) {
                        break;
                    }

                    $part = min($manque, (float) ($avance->isAvance() ? $avance->montantRestant() : $avance->montant));

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
                    DB::transaction(function () use ($convertir, $appliquer, $avance, $fee, $part): void {
                        $avance = $avance->fresh();
                        $cible = $fee->fresh();

                        if ($avance === null || $cible === null) {
                            return;
                        }

                        // Still filed on another group's inscription: detach it
                        // first (row kept, old fee recomputed), then it is an
                        // avance like any other.
                        if (! $avance->isAvance()) {
                            $source = $avance->fee?->inscription;

                            if ($source === null) {
                                return;
                            }

                            $convertir->handle($source, [$avance->id]);
                            $avance = $avance->fresh();

                            if ($avance === null || ! $avance->isAvance()) {
                                return;
                            }
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

        // ---- Pass 3: REHOME what the matrix released but names nowhere.
        // A payment freed from a matrix group belongs to the student's OTHER
        // group — usually one « En formation » with no export yet (Agadir:
        // 13 running groups, 5 files; 385 avances / 265 250 DH stranded on
        // 28/08/2026). The import row still names the fee (« Frais de Mai »),
        // so it is settled on that fee of the student's inscription in a
        // group WITHOUT a matrix — never in one that has a file, or the
        // RELEASE pass would take it back on the next run.
        $rehomes = 0;
        $montantRehome = 0.0;
        $sansCible = 0;

        $centreIds = collect($matrices)->map(fn (array $m): int => (int) $m['groupe']->etablissement_id)->unique();

        $restantes = Encaissement::query()
            ->whereNull('inscription_fee_id')
            ->whereIn('etablissement_id', $centreIds)
            ->orderBy('date_paiement')
            ->get()
            ->filter(fn (Encaissement $e): bool => $this->estImporte($e) && $e->montantRestant() > 0.0);

        foreach ($restantes as $avance) {
            $label = $this->fraisDOrigine($avance);

            if ($label === null) {
                continue; // a genuine avance in the old CRM too
            }

            $inscription = Inscription::query()
                ->where('student_id', $avance->student_id)
                ->with('group')
                ->get()
                // A matrix group is off-limits only when the file lists this
                // student — then the matrix already decided their cells.
                ->reject(fn (Inscription $i): bool => isset($matrices[$i->group_id]['listes'][$i->student_id]))
                ->sortBy([
                    fn (Inscription $a, Inscription $b): int => ($a->statut === Inscription::STATUT_ACTIVE ? 0 : 1) <=> ($b->statut === Inscription::STATUT_ACTIVE ? 0 : 1),
                    fn (Inscription $a, Inscription $b): int => (($a->group?->statut ?? '') === Group::STATUT_EN_FORMATION ? 0 : 1) <=> (($b->group?->statut ?? '') === Group::STATUT_EN_FORMATION ? 0 : 1),
                    fn (Inscription $a, Inscription $b): int => strcmp((string) $b->date_inscription, (string) $a->date_inscription),
                ])
                ->first();

            if ($inscription === null) {
                $sansCible++;

                continue;
            }

            $fee = $this->fraisDe($inscription, $label, $dry);

            if ($fee === null) {
                $sansCible++;

                continue;
            }

            $fee = $fee->exists ? $fee->fresh() : $fee;
            $part = min($avance->montantRestant(), round((float) $fee->montant - $fee->montantPaye(), 2));

            if ($part <= 0.0) {
                $sansCible++;

                continue;
            }

            if ($rehomes === 0) {
                $this->line('');
                $this->line('  Replacés hors matrice (frais nommé par l’import, inscription dans un groupe sans fichier) :');
            }

            $this->line(sprintf('      %-11s %9s  %-26s -> %s', $avance->reference, number_format($part, 2, '.', ''), mb_substr($fee->nom, 0, 26), mb_substr((string) ($inscription->group?->nom ?? '?'), 0, 28)));

            if (! $dry) {
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

            $rehomes++;
            $montantRehome += $part;
        }

        $this->line('');
        $this->info(sprintf(
            '%sLibérés : %d (%s MAD)   Placés : %d (%s MAD)   Déjà corrects : %d',
            $dry ? '[DRY-RUN] ' : '',
            $liberes, number_format($montantLibere, 2, '.', ''),
            $places, number_format($montantPlace, 2, '.', ''),
            $dejaOk
        ));

        if ($rehomes > 0 || $sansCible > 0) {
            $this->info(sprintf('%sReplacés hors matrice : %d (%s MAD)   Sans cible : %d', $dry ? '[DRY-RUN] ' : '', $rehomes, number_format($montantRehome, 2, '.', ''), $sansCible));
        }

        if ($this->montantsReleves > 0) {
            $this->line(sprintf('%d frais dont le montant dû a été relevé au montant payé dans la matrice.', $this->montantsReleves));
        }

        if ($this->lignesRetablies > 0) {
            $this->line(sprintf('%d ligne(s) de frais rétablie(s) (démasquée(s) ou créée(s) depuis le catalogue) pour recevoir une cellule de la matrice.', $this->lignesRetablies));
        }

        foreach ($this->ignorees as $motif => $cells) {
            $this->warn(sprintf('  %3d × %s', count($cells), $motif));
            foreach (array_slice(array_unique($cells), 0, 8) as $c) {
                $this->line('        '.$c);
            }
        }

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
        // A detached application row IS an avance of its own: its parent
        // keeps counting it as used (Encaissement::montantUtilise sums
        // every child, attached or not), so the money lives exactly once
        // — here. Excluding it stranded 200 rows / 197 100 DH on
        // 28/08/2026 (on no fee, on no parent balance, in no list).
        $avances = Encaissement::query()
            ->where('student_id', $student->id)
            ->whereNull('inscription_fee_id')
            ->orderBy('date_paiement')
            ->get()
            // Imported money only: an avance received in the CRM after the
            // export must never be spent on a cell the old CRM had settled
            // with money we never got (see estImporte()).
            ->filter(fn (Encaissement $e): bool => $this->estImporte($e) && $e->montantRestant() > 0.0);

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
            ->filter(fn (Encaissement $e): bool => $this->estImporte($e)
                && $this->cleFrais((string) ($e->fee?->nom ?? '')) === $this->cleFrais($nomFrais));

        return $avances->concat($encoreRattaches);
    }

    /**
     * True when this money came from the legacy import: the row carries a
     * legacy_ref, or it is an application row whose ROOT avance does. A
     * payment cashed in the CRM itself has neither and is never touched.
     */
    /**
     * The fee the old CRM named for this money, read from the import row of
     * its ROOT payment (an application row inherits its parent's label).
     * Null = the source carried no fee: a real avance, left alone.
     */
    private function fraisDOrigine(Encaissement $e): ?string
    {
        for ($i = 0; $i < 10 && $e !== null && $e->legacy_ref === null; $i++) {
            $e = $e->applied_from_encaissement_id === null ? null : Encaissement::find($e->applied_from_encaissement_id);
        }

        if ($e === null || $e->legacy_ref === null) {
            return null;
        }

        $raw = ImportRow::query()
            ->whereHas('batch', fn ($q) => $q
                ->where('module', ImportBatch::MODULE_ENCAISSEMENTS)
                ->where('etablissement_id', $e->etablissement_id))
            ->where('legacy_ref', $e->legacy_ref)
            ->value('raw');

        $label = trim((string) ($raw['frais_label'] ?? ''));

        // A comma-separated label is a payment split across several fees —
        // the importer already allocated it; nothing to guess here.
        return $label === '' || $label === '-' || str_contains($label, ',') ? null : $label;
    }

    private function estImporte(Encaissement $e): bool
    {
        for ($i = 0; $i < 10 && $e !== null; $i++) {
            if ($e->legacy_ref !== null) {
                return true;
            }

            if ($e->applied_from_encaissement_id === null) {
                return false;
            }

            $e = Encaissement::find($e->applied_from_encaissement_id);
        }

        return false;
    }

    /**
     * The filename is the only link to the group, so the match must survive
     * what a Windows export puts in a name: a non-breaking space, a trailing
     * blank, an apostrophe variant, an accent typed differently. A strict
     * `where('nom', $nom)` silently ignored 12 Kénitra/Salé files whose group
     * existed under the very same visible name (28/08/2026) — compare on the
     * letters-and-digits key instead, exactly like the student lookup does.
     */
    private function trouverGroupe(string $nom): ?Group
    {
        $candidats = Group::query()
            ->when($this->option('centre'), function ($q, $centre): void {
                $q->whereHas('etablissement', fn ($e) => is_numeric($centre)
                    ? $e->whereKey((int) $centre)
                    : $e->where('nom_centre', 'ilike', '%'.$centre.'%'));
            })
            ->get();

        return $candidats->first(fn (Group $g): bool => $g->nom === $nom)
            ?? $candidats->first(fn (Group $g): bool => $this->cleNom($g->nom) === $this->cleNom($nom));
    }

    /** Letters and digits only, accents folded — for filename ↔ group matching. */
    private function cleNom(string $valeur): string
    {
        $valeur = strtr(mb_strtolower(trim($valeur)), [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
            'ô' => 'o', 'ö' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c', 'ï' => 'i', 'î' => 'i',
        ]);

        return (string) preg_replace('/[^a-z0-9]/', '', $valeur);
    }

    /**
     * ⚠ Homonyms are real: « HANANE SEBBAR » exists twice at Agadir. A bare
     * ->first() picked whichever row came back and, when that was the twin
     * NOT enrolled here, the whole student's column was dropped as
     * « introuvable » — 4 900 DH left in avance on Badr 10H (28/08/2026).
     * The matrix names a student INSIDE a group, so the one registered in
     * that group is always the right one; the plain lookup is only the
     * fallback when nobody of that name is enrolled.
     */
    private function trouverEtudiant(Group $groupe, string $nom): ?Student
    {
        $cle = $this->cle($nom);

        $homonymes = Student::query()
            ->where('etablissement_id', $groupe->etablissement_id)
            ->whereRaw("lower(trim(prenom||' '||nom)) = ? or lower(trim(nom||' '||prenom)) = ?", [$cle, $cle])
            ->get();

        if ($homonymes->count() <= 1) {
            return $homonymes->first();
        }

        $inscrits = Inscription::query()
            ->where('group_id', $groupe->id)
            ->whereIn('student_id', $homonymes->pluck('id'))
            ->pluck('student_id');

        return $homonymes->first(fn (Student $s): bool => $inscrits->contains($s->id))
            ?? $homonymes->first();
    }

    private function inscriptionDans(Group $groupe, Student $student): ?Inscription
    {
        return Inscription::query()
            ->where('group_id', $groupe->id)
            ->where('student_id', $student->id)
            ->orderByRaw('case statut when ? then 0 else 1 end', [Inscription::STATUT_ACTIVE])
            ->first();
    }

    /**
     * The fee line a matrix cell lands on. The matrix is the authority: when
     * it shows money under « Frais de Juillet » and the inscription has no
     * such line (hidden by groupes:nettoyer-frais, or never generated because
     * the group's window missed that month), the line is brought back — a
     * hidden one is un-hidden, a missing one is created from the catalogue
     * at the group's own amount. Dropping the cell as « introuvable » left the
     * payment in avance while the old CRM displayed it paid (28/08/2026).
     * Never in dry-run: an unsaved model stands in so the plan still prints.
     */
    private function fraisDe(Inscription $inscription, string $nomFrais, bool $dry): ?InscriptionFee
    {
        $lignes = InscriptionFee::query()
            ->where('inscription_id', $inscription->id)
            ->get()
            ->filter(fn (InscriptionFee $f): bool => $this->cleFrais($f->nom) === $this->cleFrais($nomFrais));

        $visible = $lignes->first(fn (InscriptionFee $f): bool => $f->masque_le === null);

        if ($visible !== null) {
            return $visible;
        }

        $masquee = $lignes->first();

        if ($masquee !== null) {
            if (! $dry) {
                $masquee->update(['masque_le' => null]);
            }

            $this->lignesRetablies++;

            return $masquee;
        }

        $frais = Frais::all()->first(fn (Frais $f): bool => $this->cleFrais($f->nom) === $this->cleFrais($nomFrais));

        if ($frais === null) {
            return null;
        }

        $montant = (float) (DB::table('group_frais')
            ->where('group_id', $inscription->group_id)
            ->where('frais_id', $frais->id)
            ->value('montant') ?? $frais->montant_defaut);

        $this->lignesRetablies++;

        if ($dry) {
            return new InscriptionFee(['id' => -$frais->id, 'inscription_id' => $inscription->id, 'nom' => $frais->nom, 'montant' => $montant]);
        }

        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $frais->id,
            'nom' => $frais->nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => $inscription->date_inscription,
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
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

        $cle = (string) preg_replace('/[^a-z0-9]/', '', $valeur);

        // The old CRM writes the two real inscription fees under four labels
        // (same map as EncaissementImporter::FRAIS_ALIASES and
        // CorrigerFraisPaiements::ALIAS) — 332 cells across Kénitra/Salé/
        // Online were dropped as « frais hors catalogue » (28/08/2026).
        return self::ALIAS_FRAIS[$cle] ?? $cle;
    }
}
