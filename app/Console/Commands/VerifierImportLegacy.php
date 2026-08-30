<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\Student;
use Illuminate\Console\Command;
use OpenSpout\Reader\XLSX\Reader;

/**
 * READ-ONLY reconciliation of the legacy exports against the database.
 *
 * For every centre it counts, per file, how many rows the export holds and
 * how many of them carry a matching `legacy_ref` in the CRM, plus the money
 * total on both sides. What the import could not commit shows up here as a
 * gap with its reference, so a re-export or a manual fix can be targeted
 * instead of guessed.
 *
 * Nothing is written. Deliberately keyed on `legacy_ref` (unique per centre,
 * CLAUDE.md §11) — the only stable link between an export row and its CRM
 * record.
 *
 * Usage:
 *   php artisan import:verifier --dossier="data test local"
 *   php artisan import:verifier --dossier="data test local" --centre=3 --detail
 */
final class VerifierImportLegacy extends Command
{
    protected $signature = 'import:verifier
        {--dossier=data test local : Dossier contenant "active data/" (et "old data/")}
        {--centre= : Limiter à un centre (id)}
        {--detail : Lister les références absentes de la base}
        {--depuis= : Ne compter que les lignes datées à partir de cette date (AAAA-MM-JJ)}';

    protected $description = 'Compare les exports de l\'ancien CRM avec la base : lignes et montants présents, manquants (lecture seule).';

    private const array DOSSIERS = [
        'marrakech' => 'Marrakech', 'rabat' => 'Rabat', 'casa' => 'Casablanca',
        'kenitra' => 'Kénitra', 'agadir' => 'Agadir', 'sale' => 'Salé', 'online' => 'Online',
    ];

    /** Rows older than this date are ignored entirely (option --depuis). */
    private ?string $depuis = null;

    public function handle(): int
    {
        $this->depuis = $this->option('depuis') ? substr((string) $this->option('depuis'), 0, 10) : null;

        if ($this->depuis !== null) {
            $this->line('');
            $this->comment('Seules les lignes datées à partir du '.$this->depuis.' sont comparées.');
        }

        $racine = rtrim((string) $this->option('dossier'), '/\\');

        if (! is_dir($racine)) {
            $this->error('Dossier introuvable : '.$racine);

            return self::FAILURE;
        }

        $tot = ['fl' => 0, 'bl' => 0, 'fm' => 0.0, 'bm' => 0.0, 'il' => 0, 'ib' => 0, 'el' => 0, 'eb' => 0];

        $this->line('');
        $this->line(sprintf('  %-11s %-14s %7s %14s %7s %14s %8s', 'CENTRE', 'FICHIER', 'lignes', 'montant', 'en base', 'montant base', 'manque'));
        $this->line('  '.str_repeat('─', 82));

        foreach (self::DOSSIERS as $sous => $recherche) {
            $centre = Etablissement::where('nom_centre', 'ilike', '%'.$recherche.'%')->first();

            if ($centre === null || ($this->option('centre') && (int) $this->option('centre') !== $centre->id)) {
                continue;
            }

            $dir = $this->dossierCentre($racine, $sous);

            if ($dir === null) {
                continue;
            }

            // ---- payments
            [$refs, $montants] = $this->lireRefs($dir, 'liste-paiements', 'montant');
            $enBase = Encaissement::where('etablissement_id', $centre->id)->whereIn('legacy_ref', $refs)->pluck('legacy_ref')->all();
            $manquants = array_values(array_diff($refs, $enBase));
            $montantFichier = array_sum($montants);
            $montantBase = (float) Encaissement::where('etablissement_id', $centre->id)->whereIn('legacy_ref', $enBase)->sum('montant');

            $this->ligne($centre->nom_centre, 'paiements', count($refs), $montantFichier, count($enBase), $montantBase, count($manquants));
            $tot['fl'] += count($refs);
            $tot['bl'] += count($enBase);
            $tot['fm'] += $montantFichier;
            $tot['bm'] += $montantBase;

            if ($this->option('detail') && $manquants !== []) {
                foreach ($manquants as $ref) {
                    $row = ImportRow::whereHas('batch', fn ($q) => $q->where('module', ImportBatch::MODULE_ENCAISSEMENTS)->where('etablissement_id', $centre->id))
                        ->where('legacy_ref', $ref)->first();
                    $this->line(sprintf('        %-10s %9s  %-28s %s', $ref, number_format((float) ($montants[array_search($ref, $refs, true)] ?? 0), 2, '.', ''), mb_substr((string) ($row->raw['payeur'] ?? '?'), 0, 28), $row->status ?? 'jamais lue'));
                }
            }

            // ---- registrations (every statut file of the centre)
            [$iRefs] = $this->lireRefs($dir, 'liste-inscriptions', null);
            $iBase = Inscription::where('etablissement_id', $centre->id)->whereIn('legacy_ref', $iRefs)->count();
            $this->ligne($centre->nom_centre, 'inscriptions', count($iRefs), null, $iBase, null, count($iRefs) - $iBase);
            $tot['il'] += count($iRefs);
            $tot['ib'] += $iBase;

            // ---- students
            [$eRefs] = $this->lireRefs($dir, 'liste-etudiants', null);
            $eBase = Student::where('etablissement_id', $centre->id)->whereIn('legacy_ref', $eRefs)->count();
            $this->ligne($centre->nom_centre, 'étudiants', count($eRefs), null, $eBase, null, count($eRefs) - $eBase);
            $tot['el'] += count($eRefs);
            $tot['eb'] += $eBase;

            $this->line('');
        }

        $this->line('  '.str_repeat('─', 82));
        $this->ligne('TOTAL', 'paiements', $tot['fl'], $tot['fm'], $tot['bl'], $tot['bm'], $tot['fl'] - $tot['bl']);
        $this->ligne('TOTAL', 'inscriptions', $tot['il'], null, $tot['ib'], null, $tot['il'] - $tot['ib']);
        $this->ligne('TOTAL', 'étudiants', $tot['el'], null, $tot['eb'], null, $tot['el'] - $tot['eb']);
        $this->line('');

        if ($tot['fl'] === $tot['bl'] && $tot['il'] === $tot['ib'] && $tot['el'] === $tot['eb']) {
            $this->info('La base contient exactement ce que les fichiers contiennent.');
        } else {
            $this->warn(sprintf('Écart paiements : %s DH. Relancez avec --detail --centre=<id> pour la liste des références.', number_format($tot['fm'] - $tot['bm'], 2, '.', ' ')));
        }

        return self::SUCCESS;
    }

    private function ligne(string $centre, string $fichier, int $lignes, ?float $montant, int $base, ?float $montantBase, int $manque): void
    {
        $this->line(sprintf(
            '  %-11s %-14s %7d %14s %7d %14s %8s',
            mb_substr(str_replace('GLS ', '', $centre), 0, 11), $fichier, $lignes,
            $montant === null ? '—' : number_format($montant, 0, '.', ' '),
            $base,
            $montantBase === null ? '—' : number_format($montantBase, 0, '.', ' '),
            $manque === 0 ? 'ok' : (string) $manque
        ));
    }

    /** Prefer the freshest export folder available for this centre. */
    private function dossierCentre(string $racine, string $sous): ?string
    {
        foreach (['active data', 'old data'] as $bucket) {
            $dir = $racine.DIRECTORY_SEPARATOR.$bucket.DIRECTORY_SEPARATOR.$sous;

            if (is_dir($dir)) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * Legacy references of every matching file, plus the amount column when asked.
     *
     * @return array{0: list<string>, 1: list<float>}
     */
    private function lireRefs(string $dir, string $prefixe, ?string $colonneMontant): array
    {
        $refs = [];
        $montants = [];

        foreach (glob($dir.DIRECTORY_SEPARATOR.$prefixe.'*.xlsx') ?: [] as $fichier) {
            if (str_starts_with(basename($fichier), '~$')) {
                continue;
            }

            $reader = new Reader();
            $reader->open($fichier);
            $entetes = null;
            $colonneRef = 0;

            try {
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $c = array_map(fn ($v) => trim((string) $v), $row->toArray());

                        // The export opens with 4-6 letterhead lines (company
                        // name, address, print date) before the real header —
                        // « N° d'ordre | Réf. | … ». Anchor on the Réf. column,
                        // never on "the first row with a few cells".
                        if ($entetes === null) {
                            $bas = array_map('mb_strtolower', $c);

                            foreach ($bas as $j => $h) {
                                if (str_starts_with($h, 'réf') || str_starts_with($h, 'ref')) {
                                    $entetes = $bas;
                                    $colonneRef = $j;

                                    break;
                                }
                            }

                            continue;
                        }

                        $ref = $c[$colonneRef] ?? '';

                        // Every legacy reference is a letter followed by digits
                        // (P1234, E286, 202SL125…); the footer rows are not.
                        if ($ref === '' || preg_match('/^[A-Za-z0-9]{2,12}$/', $ref) !== 1 || preg_match('/\d/', $ref) !== 1) {
                            continue;
                        }

                        // A date filter (--depuis) drops everything the export
                        // dates before it: the older rows were reconciled in a
                        // previous pass and their known conflicts must not keep
                        // showing up as gaps (30/08/2026).
                        if ($this->depuis !== null && ! $this->apresDepuis($entetes, $c)) {
                            continue;
                        }

                        $refs[] = $ref;
                        $montant = 0.0;

                        if ($colonneMontant !== null) {
                            foreach ($entetes as $j => $h) {
                                if (str_contains($h, $colonneMontant)) {
                                    $montant = (float) str_replace([' ', ','], ['', '.'], (string) preg_replace('/[^0-9.,]/', '', $c[$j] ?? '0'));

                                    break;
                                }
                            }
                        }

                        $montants[] = $montant;
                    }

                    break;
                }
            } finally {
                $reader->close();
            }
        }

        return [$refs, $montants];
    }
    /**
     * True when the row's date column is on or after --depuis (a row with no
     * readable date is kept: dropping it would hide a real gap).
     *
     * @param  list<string>  $entetes
     * @param  list<string>  $c
     */
    private function apresDepuis(array $entetes, array $c): bool
    {
        foreach ($entetes as $j => $h) {
            if (! str_contains($h, 'date')) {
                continue;
            }

            $v = trim($c[$j] ?? '');

            if ($v === '' || $v === '-') {
                continue;
            }

            // The export writes JJ/MM/AAAA; OpenSpout may hand back a DateTime.
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})#', $v, $m) === 1) {
                return $m[3].'-'.$m[2].'-'.$m[1] >= $this->depuis;
            }

            if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $v, $m) === 1) {
                return $m[0] >= $this->depuis;
            }
        }

        return true;
    }

}
