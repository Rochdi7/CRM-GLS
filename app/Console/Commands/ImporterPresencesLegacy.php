<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\PresenceImporter;
use App\Services\Import\SheetReader;
use App\Services\Import\Support\CellNormalizer;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

/**
 * Imports the old CRM's « Registre des présences » exports from the CLI —
 * the same PresenceImporter the Backoffice → Import screen drives, without
 * clicking through seven centres' group-mapping wizards. Written to be run
 * locally first, then re-run identically on the VPS.
 *
 * Expects one file per centre, named after it:
 *
 *   <dossier>/marrakech.xlsx  rabat.xlsx  casablanca.xlsx  kenitra.xlsx
 *   <dossier>/agadir.xlsx     sale.xlsx   online.xlsx
 *
 * Columns: N° | Élève | Groupe | Matière | Date | Horaire | Statut | Enseignant.
 * « Matière » is deliberately ignored (this app has no such column) and
 * « Enseignant » is advisory only. The séances are DERIVED, not listed in
 * the file: every distinct (groupe, date, horaire) becomes ONE séance
 * carrying the file's teacher, and each line becomes one présence on it —
 * so a group's whole roll call of a given day merges into a single séance.
 *
 * Unlike import:centre this command NEVER creates or re-affects a group: a
 * « Groupe » label must match an existing group of the centre by name
 * (whatever its année — the séance inherits the GROUP's année), otherwise
 * its rows are reported as conflicts and skipped. Presences are history;
 * inventing structure from a register would be a bug.
 *
 * Usage:
 *   php artisan import:presences --dossier="C:\...\presence" --tous --dry-run
 *   php artisan import:presences --dossier="C:\...\presence" --centre=Marrakech
 *   php artisan import:presences --dossier="/var/www/crm-gls/data/presence" --tous
 */
final class ImporterPresencesLegacy extends Command
{
    protected $signature = 'import:presences
        {--centre= : Centre (id ou partie du nom, ex. "Marrakech")}
        {--tous : Importer tous les fichiers de centres trouvés dans le dossier}
        {--dossier= : Dossier contenant <centre>.xlsx par centre}
        {--retry-echecs : Re-tenter les lignes ECHEC_COMMIT des lots précédents (après correction de leur cause)}
        {--dry-run : Analyser sans rien écrire en base}';

    protected $description = "Importe le registre des présences de l'ancien CRM (séances + appel) depuis la ligne de commande.";

    /** File name in the export folder => how the centre is matched in DB. */
    private const FICHIERS_CENTRES = [
        'marrakech' => 'Marrakech',
        'rabat' => 'Rabat',
        'casablanca' => 'Casablanca',
        'kenitra' => 'Kénitra',
        'agadir' => 'Agadir',
        'sale' => 'Salé',
        'online' => 'Online',
    ];

    public function handle(SheetReader $reader, PresenceImporter $importer): int
    {
        $dossier = rtrim((string) $this->option('dossier'), '/\\');

        if ($dossier === '' || ! is_dir($dossier)) {
            $this->error('--dossier est obligatoire et doit exister.');

            return self::FAILURE;
        }

        $admin = Employee::query()->orderBy('id')->first();

        if ($admin === null) {
            $this->error('Aucun employé en base — lancer les seeders avant un import.');

            return self::FAILURE;
        }

        // The batch needs an année label; séances take their GROUP's année,
        // so this is bookkeeping only, never scoping.
        $anneeDefaut = AnneeScolaire::query()->where('par_defaut', true)->first()
            ?? AnneeScolaire::query()->orderByDesc('date_debut')->first();

        if ($anneeDefaut === null) {
            $this->error('Aucune année scolaire en base.');

            return self::FAILURE;
        }

        $cibles = $this->resolveCentres($dossier);

        if ($cibles === []) {
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Analyse seule — rien ne sera écrit en base.');
        }

        $echecs = 0;

        foreach ($cibles as $fichier => $centre) {
            $this->line('');
            $this->line(str_repeat('─', 64));
            $this->info(sprintf('%s  (fichier « %s.xlsx »)', $centre->nom_centre, $fichier));
            $this->line(str_repeat('─', 64));

            $path = $dossier.DIRECTORY_SEPARATOR.$fichier.'.xlsx';

            if (! $this->importerCentre($reader, $importer, $path, $centre, $anneeDefaut, $admin)) {
                $echecs++;
            }
        }

        $this->line('');

        return $echecs === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function importerCentre(
        SheetReader $reader,
        PresenceImporter $importer,
        string $path,
        Etablissement $centre,
        AnneeScolaire $anneeDefaut,
        Employee $admin,
    ): bool {
        $mapping = $this->mapperGroupes($reader, $path, $centre);

        if ($mapping === []) {
            $this->error('  Aucun groupe du fichier ne correspond à un groupe du centre.');

            return false;
        }

        return $this->executer(
            'présences',
            $importer,
            $path,
            $admin,
            new ImportContext(
                etablissementId: $centre->id,
                anneeScolaireId: $anneeDefaut->id,
                groupeMapping: $mapping,
            ),
        );
    }

    /**
     * Maps every « Groupe » label of the file onto an EXISTING group of the
     * centre by normalized name — both années included, since a register
     * spans still-running groups (current année) and terminated ones
     * (previous année). Never creates a group, never moves one between
     * années: an unmatched or ambiguous label is reported and its rows come
     * out as conflicts, visible in the résumé instead of silently dropped.
     *
     * @return array<string, array{action: string, group_id: int}>
     */
    private function mapperGroupes(SheetReader $reader, string $path, Etablissement $centre): array
    {
        $parNomExact = [];
        $parNomInsensible = [];

        Group::query()
            ->where('etablissement_id', $centre->id)
            ->get(['id', 'nom'])
            ->each(function (Group $group) use (&$parNomExact, &$parNomInsensible): void {
                $nom = CellNormalizer::text($group->nom);
                $parNomExact[$nom][] = $group->id;
                $parNomInsensible[mb_strtolower($nom)][] = $group->id;
            });

        $map = [];
        $introuvables = [];
        $ambigus = [];

        foreach ($reader->distinctColumnValues($path, 'Groupe') as $brut) {
            // The mapping is looked up by the importer AFTER CellNormalizer
            // normalization, so the key must be the normalized form too
            // (the export pads labels with stray spaces: "Hajib 10H ").
            $label = CellNormalizer::text((string) $brut);

            if ($label === '' || $label === '-' || isset($map[$label])) {
                continue;
            }

            // Exact (case-sensitive) first — that is how import:centre
            // created the groups, and the old CRM really does hold two
            // distinct cohorts apart only by case (« Saad 10H » /
            // « Saad 10h » in Casablanca). Case-insensitive is only the
            // fallback for a label whose exact form matches nothing.
            $ids = array_unique($parNomExact[$label] ?? []);

            if ($ids === []) {
                $ids = array_unique($parNomInsensible[mb_strtolower($label)] ?? []);
            }

            if ($ids === []) {
                $introuvables[] = $label;

                continue;
            }

            if (count($ids) > 1) {
                $ambigus[] = $label;

                continue;
            }

            $map[$label] = ['action' => 'map', 'group_id' => (int) $ids[0]];
        }

        if ($introuvables !== []) {
            $this->warn(sprintf(
                '  %d groupe(s) introuvable(s) dans ce centre (lignes en conflit) : %s',
                count($introuvables),
                implode(', ', array_map(fn (string $l): string => '« '.$l.' »', $introuvables))
            ));
        }

        if ($ambigus !== []) {
            $this->warn(sprintf(
                '  %d groupe(s) ambigu(s) — plusieurs groupes du même nom (lignes en conflit) : %s',
                count($ambigus),
                implode(', ', array_map(fn (string $l): string => '« '.$l.' »', $ambigus))
            ));
        }

        return $map;
    }

    /**
     * Analyzes, then commits every selectable row. commit() deliberately
     * drains ONE chunk per call (the web UI polls it for a progress bar), so
     * the CLI loops it to the end — same shape as import:centre.
     */
    private function executer(string $libelle, PresenceImporter $importer, string $path, Employee $admin, ImportContext $context): bool
    {
        if ($this->option('retry-echecs') && ! $this->option('dry-run')) {
            $requeued = ImportRow::query()
                ->whereHas('batch', fn ($q) => $q
                    ->where('module', $importer->module())
                    ->where('etablissement_id', $context->etablissementId))
                ->whereIn('status', ImportRow::RETRYABLE_STATUTS)
                ->update(['status' => ImportRow::STATUT_CONFLIT, 'errors' => null]);

            if ($requeued > 0) {
                $this->line(sprintf('  %-42s %d ligne(s) en échec re-tentée(s)', '', $requeued));
            }
        }

        try {
            $batch = $importer->analyze($this->upload($path), $context, $admin);
        } catch (\Throwable $e) {
            $this->error(sprintf('  %-42s ÉCHEC — %s', $libelle, $e->getMessage()));

            return false;
        }

        if ($this->option('dry-run')) {
            $this->line(sprintf('  %-42s %s', $libelle, $this->resume($batch)));
            $batch->rows()->delete();
            $batch->delete();

            return true;
        }

        // NOUVEAU only — a CONFLIT (élève ambigu, groupe non associé) has no
        // operator to resolve it here; left as-is it stays visible and
        // resolvable on the web Preview screen instead of being burned into
        // an ECHEC_COMMIT the CLI can never fix. (No `selected` update
        // needed: analyze() creates every row selected already.)
        $ids = $batch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->pluck('id')->all();

        $barre = $this->output->createProgressBar(count($ids));
        $barre->setFormat('  %message:-42s% [%bar%] %current%/%max%');
        $barre->setMessage($libelle);
        $barre->start();

        // commit() puts the whole id list into ONE `whereIn` — PostgreSQL
        // caps a statement at 65 535 bind parameters, and Rabat's register
        // alone holds 65k+ lines. Feed it blocks that stay under the cap.
        $faits = 0;

        foreach (array_chunk($ids, 50000) as $bloc) {
            do {
                $resultat = $importer->commit($batch, $bloc, $admin, 200);
                $barre->setProgress(min(count($ids), $faits + count($bloc) - $resultat->remaining));
            } while ($resultat->remaining > 0);

            $faits += count($bloc);
        }

        $barre->finish();
        $this->line('');
        $this->line(sprintf('  %-42s %s', '', $this->resume($batch->fresh())));

        return true;
    }

    private function resume(ImportBatch $batch): string
    {
        $counts = $batch->rows()
            ->selectRaw('status, count(*) c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $parts = [];

        foreach (['INSERE' => 'insérées', 'NOUVEAU' => 'à insérer', 'DOUBLON' => 'doublons', 'CONFLIT' => 'conflits', 'ERREUR' => 'erreurs', 'ECHEC_COMMIT' => 'échecs'] as $statut => $mot) {
            if (($counts[$statut] ?? 0) > 0) {
                $parts[] = $counts[$statut].' '.$mot;
            }
        }

        return sprintf('%d lignes — %s', $batch->total_rows, $parts === [] ? 'rien' : implode(', ', $parts));
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile($path, basename($path), null, null, true);
    }

    /**
     * @return array<string, Etablissement> file basename (without .xlsx) => centre
     */
    private function resolveCentres(string $dossier): array
    {
        $cibles = [];

        foreach (self::FICHIERS_CENTRES as $fichier => $recherche) {
            if (! is_file($dossier.DIRECTORY_SEPARATOR.$fichier.'.xlsx')) {
                continue;
            }

            $centre = Etablissement::query()->where('nom_centre', 'ilike', '%'.$recherche.'%')->first();

            if ($centre !== null) {
                $cibles[$fichier] = $centre;
            }
        }

        if ($this->option('tous')) {
            if ($cibles === []) {
                $this->error('Aucun fichier de centre reconnu dans '.$dossier);
            }

            return $cibles;
        }

        $demande = trim((string) $this->option('centre'));

        if ($demande === '') {
            $this->error('Préciser --centre=<nom|id> ou --tous.');

            return [];
        }

        foreach ($cibles as $fichier => $centre) {
            if (mb_stripos($fichier, $demande) !== false
                || mb_stripos($centre->nom_centre, $demande) !== false
                || (string) $centre->id === $demande) {
                return [$fichier => $centre];
            }
        }

        $this->error(sprintf('Centre « %s » introuvable parmi les fichiers du dossier.', $demande));

        return [];
    }
}
