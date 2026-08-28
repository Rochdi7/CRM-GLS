<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Inscription;
use Illuminate\Console\Command;
use OpenSpout\Reader\XLSX\Reader;

/**
 * READ-ONLY check: for every group matrix exported from the old CRM
 * (« Statistiques_Prof », one XLSX per group named after the group), compare
 * each fee COLUMN TOTAL of the file with what the CRM holds on that group's
 * inscriptions. Per-cell concordance once hid 137 300 DH; the file's own
 * Total row is the reference (28/08/2026).
 *
 * Money the CRM holds on the group for a student the file does not list is
 * reported apart (« hors fichier ») — it is not a mismatch, the old CRM just
 * dropped students who left.
 *
 * Usage:
 *   php artisan matrice:verifier --centre=1 --dossier="data test local/groups active/marrakech"
 *   php artisan matrice:verifier --centre=1 --dossier=... --detail
 */
final class VerifierMatriceGroupe extends Command
{
    protected $signature = 'matrice:verifier
        {--dossier= : Dossier des XLSX, un par groupe, nommés comme le groupe}
        {--centre= : Centre (id)}
        {--detail : Afficher chaque colonne, pas seulement les écarts}';

    protected $description = "Compare, colonne par colonne, les totaux de chaque matrice de l'ancien CRM avec les paiements du groupe (lecture seule).";

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

    public function handle(): int
    {
        $dossier = rtrim((string) $this->option('dossier'), '/\\');
        $centre = (int) $this->option('centre');

        if ($dossier === '' || ! is_dir($dossier) || $centre === 0) {
            $this->error('--dossier (existant) et --centre (id) sont obligatoires.');

            return self::FAILURE;
        }

        $groupes = Group::where('etablissement_id', $centre)->get();
        $fichiers = array_filter(glob($dossier.DIRECTORY_SEPARATOR.'*.xlsx') ?: [], fn (string $f): bool => ! str_starts_with(basename($f), '~$'));

        $totCols = 0;
        $totOk = 0;
        $totFichier = 0.0;
        $totCrm = 0.0;
        $totHors = 0.0;

        foreach ($fichiers as $fichier) {
            $nom = pathinfo($fichier, PATHINFO_FILENAME);
            $groupe = $groupes->first(fn (Group $g): bool => $g->nom === $nom)
                ?? $groupes->first(fn (Group $g): bool => $this->cle($g->nom) === $this->cle($nom));

            if ($groupe === null) {
                $this->warn(sprintf('  ?? %-30s groupe introuvable', $nom));

                continue;
            }

            [$colonnes, $etudiants] = $this->lireFichier($fichier);

            // CRM: per fee column, split between students the file lists and the others.
            $crm = [];
            $hors = [];

            foreach (Inscription::where('group_id', $groupe->id)->with(['student', 'fees.encaissements'])->get() as $i) {
                $cleEtu = $this->cle(($i->student->prenom ?? '').($i->student->nom ?? ''));
                $liste = isset($etudiants[$cleEtu]);

                foreach ($i->fees as $fee) {
                    $k = $this->cleFrais($fee->nom);

                    foreach ($fee->encaissements as $e) {
                        if ($liste) {
                            $crm[$k] = ($crm[$k] ?? 0.0) + (float) $e->montant;
                        } else {
                            $hors[$k] = ($hors[$k] ?? 0.0) + (float) $e->montant;
                        }
                    }
                }
            }

            $nb = count($colonnes);
            $ok = 0;
            $sf = 0.0;
            $sc = 0.0;
            $lignes = [];

            foreach ($colonnes as $k => ['nom' => $libelle, 'total' => $attendu]) {
                $reel = $crm[$k] ?? 0.0;
                $sf += $attendu;
                $sc += $reel;
                $egal = abs($reel - $attendu) < 0.01;
                $ok += $egal ? 1 : 0;

                if (! $egal || $this->option('detail')) {
                    $lignes[] = sprintf('        %-32s fichier %10s   crm %10s   %s', mb_substr($libelle, 0, 32), number_format($attendu, 0, '.', ' '), number_format($reel, 0, '.', ' '), $egal ? 'OK' : sprintf('ECART %+d', (int) round($reel - $attendu)));
                }
            }

            foreach ($crm as $k => $v) {
                if (! isset($colonnes[$k])) {
                    $sc += $v;
                    $lignes[] = sprintf('        %-32s fichier %10s   crm %10s   COLONNE ABSENTE DU FICHIER', $k, '-', number_format($v, 0, '.', ' '));
                }
            }

            $sh = array_sum($hors);
            $totCols += $nb;
            $totOk += $ok;
            $totFichier += $sf;
            $totCrm += $sc;
            $totHors += $sh;

            $this->line(sprintf(
                '  %-30s colonnes %2d/%2d   fichier %10s   crm %10s   %s%s',
                mb_substr($groupe->nom, 0, 30), $ok, $nb,
                number_format($sf, 0, '.', ' '), number_format($sc, 0, '.', ' '),
                $ok === $nb ? 'OK' : sprintf('ECART %+d', (int) round($sc - $sf)),
                $sh > 0 ? sprintf('   (+%s hors fichier : étudiants non listés)', number_format($sh, 0, '.', ' ')) : ''
            ));

            foreach ($lignes as $l) {
                $this->line($l);
            }
        }

        $this->line('');
        $this->info(sprintf(
            'TOTAL  colonnes %d/%d   fichier %s   crm %s   écart %+d   hors fichier %s',
            $totOk, $totCols, number_format($totFichier, 0, '.', ' '), number_format($totCrm, 0, '.', ' '),
            (int) round($totCrm - $totFichier), number_format($totHors, 0, '.', ' ')
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0: array<string, array{nom: string, total: float}>, 1: array<string, true>}
     */
    private function lireFichier(string $chemin): array
    {
        $reader = new Reader();
        $reader->open($chemin);
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = array_map(fn ($v) => trim((string) $v), $row->toArray());
                }

                break;
            }
        } finally {
            $reader->close();
        }

        $hi = null;

        foreach ($rows as $i => $c) {
            if (count(array_filter($c, fn ($v) => $v !== '')) > 3) {
                $hi = $i;

                break;
            }
        }

        if ($hi === null) {
            return [[], []];
        }

        $head = $rows[$hi];
        $colonnes = [];
        $etudiants = [];

        foreach (array_slice($rows, $hi + 1) as $c) {
            // Student rows start with "#n"; the trailing Total row does not.
            if (($c[1] ?? '') === '' || ! str_starts_with($c[0] ?? '', '#')) {
                continue;
            }

            $etudiants[$this->cle($c[1])] = true;

            foreach ($head as $j => $h) {
                if ($j < 2 || $h === '') {
                    continue;
                }

                $v = (float) str_replace([' ', ','], ['', '.'], $c[$j] ?? '0');

                if ($v <= 0) {
                    continue;
                }

                $k = $this->cleFrais($h);
                $colonnes[$k] ??= ['nom' => $h, 'total' => 0.0];
                $colonnes[$k]['total'] += $v;
            }
        }

        return [$colonnes, $etudiants];
    }

    private function cle(string $s): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtr(mb_strtolower($s), [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
            'ô' => 'o', 'ö' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c', 'ï' => 'i', 'î' => 'i',
        ]));
    }

    private function cleFrais(string $s): string
    {
        $k = $this->cle($s);

        return self::ALIAS_FRAIS[$k] ?? $k;
    }
}
