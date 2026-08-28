<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Etablissement;
use App\Models\Inscription;
use App\Services\Import\SheetReader;
use App\Services\Import\Support\CellNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Back-fills inscriptions.date_debut / date_fin from the legacy CRM's own
 * INSCRIPTIONS exports (« Date de début » / « Date de fin »).
 *
 * Why this exists: InscriptionImporter copies the GROUP's
 * date_debut_formation / date_fin_formation onto every inscription it
 * creates. The legacy inscriptions export carried no group dates, so those
 * columns were NULL at import time and every imported inscription inherited
 * NULL — even though each export row carries the STUDENT's own start/end
 * dates, which differ inside one group (a student joining in February has a
 * different début from one who joined in December).
 *
 * What it touches: ONLY inscriptions.date_debut and inscriptions.date_fin,
 * and ONLY where the column is currently NULL. Not a statut, not a group,
 * not an année, not a centre, not one dirham. An inscription whose date was
 * already filled — by hand in the UI or by a previous run — is left exactly
 * as it is (--ecraser lifts that, off by default).
 *
 * Matching is EXACT, never a guess: the export's « Réf » is the inscription's
 * legacy_ref, and (etablissement_id, legacy_ref) is a unique index
 * (inscriptions_etab_legacy_ref_unique), so a row either matches one
 * inscription of that centre or is reported as absent. Refs are unique PER
 * CENTRE only — every centre numbers from scratch — so the lookup is always
 * scoped to the centre being processed (CLAUDE.md §11).
 *
 * Rows are saved one by one through Eloquent — NOT a mass update — so the
 * Auditable trait journals every change (avant/après) like any other edit.
 *
 * Reads both folders, exactly as `import:centre` does:
 *   <dossier>/active data/<centre>/liste-inscriptions_*.xlsx    (Active)
 *   <dossier>/old data/<centre>/liste-inscriptions_*.xlsx       (Annulé)
 *   <dossier>/old data/<centre>/liste-inscriptions_* (1).xlsx   (Archive)
 *
 * Usage:
 *   php artisan inscriptions:completer-dates --tous --dossier="/var/www/crm-gls/data" --dry-run
 *   php artisan inscriptions:completer-dates --tous --dossier="/var/www/crm-gls/data"
 *   php artisan inscriptions:completer-dates --centre=Rabat --dossier="/var/www/crm-gls/data"
 */
final class CompleterDatesInscriptions extends Command
{
    protected $signature = 'inscriptions:completer-dates
        {--centre= : Centre (id ou partie du nom, ex. "Rabat")}
        {--tous : Traiter les sept centres trouvés dans le dossier}
        {--dossier= : Dossier contenant "old data/" et "active data/"}
        {--ecraser : Écraser aussi les dates déjà renseignées (par défaut : seules les dates vides sont remplies)}
        {--dry-run : Afficher les changements sans rien écrire}';

    protected $description = "Complète date_debut / date_fin des inscriptions depuis les exports d'inscriptions de l'ancien CRM.";

    /** Sub-directory name in the export => how the centre is matched in DB. Same map as import:centre. */
    private const array DOSSIERS_CENTRES = [
        'marrakech' => 'Marrakech',
        'rabat' => 'Rabat',
        'casa' => 'Casablanca',
        'kenitra' => 'Kénitra',
        'agadir' => 'Agadir',
        'sale' => 'Salé',
        'online' => 'Online',
    ];

    /**
     * The two date columns are what this command is for, so they are
     * REQUIRED in the header — a file lacking them is the wrong file, not a
     * file to process silently. « Réf » is the join key.
     */
    private const array EXPECTED_COLUMNS = ['Réf', 'Date de début', 'Date de fin'];

    public function handle(SheetReader $reader): int
    {
        $dossier = rtrim((string) $this->option('dossier'), '/\\');

        if ($dossier === '' || ! is_dir($dossier)) {
            $this->error('--dossier est obligatoire et doit exister.');

            return self::FAILURE;
        }

        $cibles = $this->resolveCentres($dossier);

        if ($cibles === []) {
            return self::FAILURE;
        }

        $totaux = ['fichiers' => 0, 'lignes' => 0, 'modifiees' => 0, 'inchangees' => 0, 'absentes' => 0, 'sansDate' => 0];

        foreach ($cibles as [$etablissement, $sousDossier]) {
            $this->line('');
            $this->info(sprintf('=== %s ===', $etablissement->nom_centre));

            $fichiers = $this->fichiersInscriptions($dossier, $sousDossier);

            if ($fichiers === []) {
                $this->warn('  Aucun fichier liste-inscriptions_*.xlsx trouvé.');

                continue;
            }

            // Union of every file's rows FIRST, then one pass over the DB.
            // The same réf appears in at most one file (Active / Annulé /
            // Archive are disjoint statuts), but reading them all before
            // touching the database keeps the write pass single and its
            // report honest.
            $dates = [];

            foreach ($fichiers as $fichier) {
                $lues = $this->lireFichier($reader, $fichier);
                $totaux['fichiers']++;
                $totaux['lignes'] += $lues['lignes'];
                $totaux['sansDate'] += $lues['sansDate'];

                $this->line(sprintf(
                    '  %-46s %d ligne(s), %d avec date(s)',
                    basename($fichier),
                    $lues['lignes'],
                    count($lues['dates'])
                ));

                $dates += $lues['dates'];
            }

            $resultat = $this->appliquer($etablissement, $dates);

            $totaux['modifiees'] += $resultat['modifiees'];
            $totaux['inchangees'] += $resultat['inchangees'];
            $totaux['absentes'] += $resultat['absentes'];

            $this->line(sprintf(
                '  -> %d modifiée(s), %d inchangée(s), %d réf(s) sans inscription en base.',
                $resultat['modifiees'],
                $resultat['inchangees'],
                $resultat['absentes']
            ));
        }

        $this->line('');
        $this->info(sprintf(
            '%s%d fichier(s), %d ligne(s) lue(s) — %d inscription(s) modifiée(s), %d inchangée(s), %d réf(s) absente(s) de la base, %d ligne(s) sans date dans le fichier.',
            $this->option('dry-run') ? '[DRY-RUN] ' : '',
            $totaux['fichiers'],
            $totaux['lignes'],
            $totaux['modifiees'],
            $totaux['inchangees'],
            $totaux['absentes'],
            $totaux['sansDate']
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{debut: ?string, fin: ?string}>  $dates  legacy_ref => dates
     * @return array{modifiees: int, inchangees: int, absentes: int}
     */
    private function appliquer(Etablissement $etablissement, array $dates): array
    {
        if ($dates === []) {
            return ['modifiees' => 0, 'inchangees' => 0, 'absentes' => 0];
        }

        $modifiees = 0;
        $inchangees = 0;
        $vues = [];
        $ecraser = (bool) $this->option('ecraser');
        $dryRun = (bool) $this->option('dry-run');

        Inscription::query()
            ->where('etablissement_id', $etablissement->id)
            ->whereNotNull('legacy_ref')
            ->whereIn('legacy_ref', array_keys($dates))
            ->orderBy('id')
            ->chunkById(500, function ($lot) use ($dates, &$modifiees, &$inchangees, &$vues, $ecraser, $dryRun): void {
                foreach ($lot as $inscription) {
                    $vues[$inscription->legacy_ref] = true;
                    $ligne = $dates[$inscription->legacy_ref];

                    $changements = [];

                    foreach ([['debut', 'date_debut'], ['fin', 'date_fin']] as [$source, $colonne]) {
                        $nouvelle = $ligne[$source];

                        if ($nouvelle === null) {
                            continue;
                        }

                        $actuelle = $inscription->{$colonne}?->toDateString();

                        // Default: fill the void only. An inscription whose
                        // date was typed in the UI is a human decision and
                        // outranks a legacy file.
                        if ($actuelle !== null && ! $ecraser) {
                            continue;
                        }

                        if ($actuelle !== $nouvelle) {
                            $changements[$colonne] = $nouvelle;
                        }
                    }

                    if ($changements === []) {
                        $inchangees++;

                        continue;
                    }

                    if (! $dryRun) {
                        // One save per row, inside its own transaction: the
                        // Auditable trait writes an avant/après journal entry
                        // for each change, which a mass update would skip.
                        DB::transaction(function () use ($inscription, $changements): void {
                            foreach ($changements as $colonne => $valeur) {
                                $inscription->{$colonne} = $valeur;
                            }
                            $inscription->save();
                        });
                    }

                    $modifiees++;
                }
            });

        return [
            'modifiees' => $modifiees,
            'inchangees' => $inchangees,
            'absentes' => count($dates) - count($vues),
        ];
    }

    /**
     * @return array{dates: array<string, array{debut: ?string, fin: ?string}>, lignes: int, sansDate: int}
     */
    private function lireFichier(SheetReader $reader, string $chemin): array
    {
        $entete = $reader->detectHeader($chemin, self::EXPECTED_COLUMNS);

        $dates = [];
        $lignes = 0;
        $sansDate = 0;

        foreach ($reader->readDataRows($chemin, $entete['headerRowNumber'], $entete['headerMap']) as $brute) {
            $lignes++;

            $ref = CellNormalizer::text($brute['Réf'] ?? '');

            if ($ref === '' || $ref === '-') {
                continue;
            }

            $debut = $this->date($brute['Date de début'] ?? null);
            $fin = $this->date($brute['Date de fin'] ?? null);

            if ($debut === null && $fin === null) {
                $sansDate++;

                continue;
            }

            $dates[$ref] = ['debut' => $debut, 'fin' => $fin];
        }

        return ['dates' => $dates, 'lignes' => $lignes, 'sansDate' => $sansDate];
    }

    /**
     * An unparseable date is skipped, never guessed: this command's whole
     * point is to fill blanks with the truth, and a wrong date is worse than
     * a missing one.
     */
    private function date(mixed $brute): ?string
    {
        try {
            return CellNormalizer::parseDate($brute)?->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Both folders, Active + Annulé + Archive. Sorted so the report reads
     * the same way on every machine.
     *
     * @return array<int, string>
     */
    private function fichiersInscriptions(string $dossier, string $sousDossier): array
    {
        $fichiers = [];

        foreach (['active data', 'old data'] as $folder) {
            $trouves = glob($dossier.'/'.$folder.'/'.$sousDossier.'/liste-inscriptions_*.xlsx') ?: [];
            sort($trouves);
            $fichiers = [...$fichiers, ...$trouves];
        }

        return $fichiers;
    }

    /**
     * @return array<int, array{0: Etablissement, 1: string}>
     */
    private function resolveCentres(string $dossier): array
    {
        if ($this->option('tous')) {
            $cibles = [];

            foreach (self::DOSSIERS_CENTRES as $sousDossier => $nom) {
                if (! is_dir($dossier.'/active data/'.$sousDossier) && ! is_dir($dossier.'/old data/'.$sousDossier)) {
                    continue;
                }

                $etablissement = $this->trouverEtablissement($nom);

                if ($etablissement === null) {
                    $this->warn(sprintf('Centre « %s » introuvable en base — ignoré.', $nom));

                    continue;
                }

                $cibles[] = [$etablissement, $sousDossier];
            }

            if ($cibles === []) {
                $this->error('Aucun centre exploitable trouvé dans le dossier.');
            }

            return $cibles;
        }

        $centre = trim((string) $this->option('centre'));

        if ($centre === '') {
            $this->error('Préciser --centre=<nom|id> ou --tous.');

            return [];
        }

        $etablissement = $this->trouverEtablissement($centre);

        if ($etablissement === null) {
            $this->error(sprintf('Centre « %s » introuvable.', $centre));

            return [];
        }

        $sousDossier = null;

        foreach (self::DOSSIERS_CENTRES as $dir => $nom) {
            if (mb_stripos($etablissement->nom_centre, $nom) !== false) {
                $sousDossier = $dir;

                break;
            }
        }

        if ($sousDossier === null) {
            $this->error(sprintf('Aucun sous-dossier connu pour « %s ».', $etablissement->nom_centre));

            return [];
        }

        return [[$etablissement, $sousDossier]];
    }

    private function trouverEtablissement(string $centre): ?Etablissement
    {
        return Etablissement::query()
            ->when(
                is_numeric($centre),
                fn ($q) => $q->whereKey((int) $centre),
                fn ($q) => $q->where('nom_centre', 'ilike', '%'.$centre.'%')
            )
            ->first();
    }
}
