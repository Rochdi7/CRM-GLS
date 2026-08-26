<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Groups\Actions\ReaffecterGroupeVersAnnee;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\Import\Contracts\Importer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\EncaissementImporter;
use App\Services\Import\InscriptionImporter;
use App\Services\Import\SheetReader;
use App\Services\Import\StudentImporter;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

/**
 * Runs a whole centre's legacy import from the CLI — the same importers the
 * Backoffice → Import screens drive, without the browser. Written for the VPS
 * (PuTTY), where uploading seven centres' files through the UI one wizard step
 * at a time is slow and easy to get wrong.
 *
 * Expects the export laid out exactly as it is downloaded (see
 * docs/legacy-import.md):
 *
 *   <dossier>/old data/<centre>/liste-etudiants_*.xlsx
 *   <dossier>/old data/<centre>/liste-inscriptions_*.xlsx        (Annulé)
 *   <dossier>/old data/<centre>/liste-inscriptions_* (1).xlsx    (Archive)
 *   <dossier>/old data/<centre>/liste-paiements_*.xlsx
 *   <dossier>/active data/<centre>/liste-inscriptions_*.xlsx     (Active)
 *
 * `liste-etudiants` and `liste-paiements` are byte-identical in both folders
 * and are imported ONCE. Which file holds which statut is read from the
 * sheet's own « Statut » column, never from the filename — a browser names a
 * duplicate download "(1)" in whatever order it pleases.
 *
 * Order is deliberate and must not change:
 *   1. étudiants
 *   2. inscriptions ACTIVE  -> année courante  (creates still-running groups)
 *   3. inscriptions Annulé / Archive -> année précédente (reuse those groups)
 *   4. paiements (one file, spans both années)
 * A group holding an active student is still running, so it is created in the
 * current année and the two old files attach to it there instead of tearing
 * the cohort across two years.
 *
 * Usage:
 *   php artisan import:centre --centre=Marrakech --dossier="/var/www/crm-gls/data" --dry-run
 *   php artisan import:centre --centre=Marrakech --dossier="/var/www/crm-gls/data"
 *   php artisan import:centre --tous --dossier="/var/www/crm-gls/data"
 */
final class ImporterCentreLegacy extends Command
{
    protected $signature = 'import:centre
        {--centre= : Centre (id ou partie du nom, ex. "Marrakech")}
        {--tous : Importer les sept centres trouvés dans le dossier}
        {--dossier= : Dossier contenant "old data/" et "active data/"}
        {--annee-courante= : Année des inscriptions actives (défaut : l\'année par défaut)}
        {--annee-precedente= : Année des inscriptions annulées/archivées}
        {--caisse= : E-mail/identifiant de l\'employé dont la caisse reçoit TOUS les paiements importés (ex. rafik@glszentrum.com)}
        {--retry-echecs : Re-tenter les lignes ECHEC_COMMIT des lots précédents (après correction de leur cause)}
        {--sans-paiements : Importer étudiants + inscriptions seulement}
        {--dry-run : Analyser sans rien écrire en base}';

    protected $description = "Importe l'export de l'ancien CRM d'un centre (étudiants, inscriptions, paiements) depuis la ligne de commande.";

    /** Sub-directory name in the export => how the centre is matched in DB. */
    private const DOSSIERS_CENTRES = [
        'marrakech' => 'Marrakech',
        'rabat' => 'Rabat',
        'casa' => 'Casablanca',
        'kenitra' => 'Kénitra',
        'agadir' => 'Agadir',
        'sale' => 'Salé',
        'online' => 'Online',
    ];

    public function handle(SheetReader $reader): int
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

        $cibles = $this->resolveCentres($dossier);

        if ($cibles === []) {
            return self::FAILURE;
        }

        $caisseForcee = null;

        if (trim((string) $this->option('caisse')) !== '') {
            $caisseForcee = $this->resolveCaisseForcee(trim((string) $this->option('caisse')));

            if ($caisseForcee === null) {
                return self::FAILURE;
            }
        }

        $courante = $this->resolveAnnee((string) $this->option('annee-courante'), true);
        $precedente = $this->resolveAnnee((string) $this->option('annee-precedente'), false);

        if ($courante === null || $precedente === null) {
            return self::FAILURE;
        }

        $this->line('');
        $this->info(sprintf(
            'Actives → %s   |   Annulées + Archivées → %s%s',
            $courante->nom,
            $precedente->nom,
            $this->option('dry-run') ? '   [DRY-RUN]' : ''
        ));

        $echecs = 0;

        foreach ($cibles as $sousDossier => $centre) {
            $this->line('');
            $this->line(str_repeat('─', 64));
            $this->info(sprintf('%s  (dossier « %s »)', $centre->nom_centre, $sousDossier));
            $this->line(str_repeat('─', 64));

            if (! $this->importerCentre($reader, $dossier, $sousDossier, $centre, $courante, $precedente, $admin, $caisseForcee)) {
                $echecs++;
            }
        }

        $this->line('');

        return $echecs === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function importerCentre(
        SheetReader $reader,
        string $racine,
        string $sousDossier,
        Etablissement $centre,
        AnneeScolaire $courante,
        AnneeScolaire $precedente,
        Employee $admin,
        ?int $caisseForcee = null,
    ): bool {
        $old = $racine.DIRECTORY_SEPARATOR.'old data'.DIRECTORY_SEPARATOR.$sousDossier;
        $active = $racine.DIRECTORY_SEPARATOR.'active data'.DIRECTORY_SEPARATOR.$sousDossier;

        $etudiants = $this->fichier($active, 'liste-etudiants') ?? $this->fichier($old, 'liste-etudiants');
        $paiements = $this->fichier($active, 'liste-paiements') ?? $this->fichier($old, 'liste-paiements');
        $inscriptions = $this->fichiersInscriptions($reader, $old, $active);

        if ($etudiants === null || $inscriptions === []) {
            $this->error('  Fichiers introuvables (étudiants et/ou inscriptions).');

            return false;
        }

        // 1. Étudiants — no année of their own; the batch just records one.
        if (! $this->executer('étudiants', app(StudentImporter::class), $etudiants, $admin,
            new ImportContext($centre->id, $precedente->id))) {
            return false;
        }

        // 2/3. Inscriptions — ACTIVE first so a still-running group is created
        // in the current année; the Annulé/Archive files then reuse it there.
        foreach ($inscriptions as ['path' => $path, 'statut' => $statut]) {
            $annee = $statut === 'Active' ? $courante : $precedente;

            if (! $this->executer(
                sprintf('inscriptions %s → %s', $statut, $annee->nom),
                app(InscriptionImporter::class),
                $path,
                $admin,
                new ImportContext(
                    etablissementId: $centre->id,
                    anneeScolaireId: $annee->id,
                    groupeMapping: $this->mapperGroupes($reader, $path, $centre, $annee),
                ),
            )) {
                return false;
            }
        }

        if ($this->option('sans-paiements') || $paiements === null) {
            return true;
        }

        // 4. Paiements — ONE file spanning both années. The importer resolves
        // each row against every inscription of the CENTRE, so the année
        // passed here is only the batch's label.
        return $this->executer(
            'paiements',
            app(EncaissementImporter::class),
            $paiements,
            $admin,
            new ImportContext(
                etablissementId: $centre->id,
                anneeScolaireId: $precedente->id,
                operateurMapping: $this->mapperOperateurs($reader, $paiements, $admin),
                includeInactiveInscriptions: true,
                caisseForceeId: $caisseForcee,
            ),
        );
    }

    /**
     * Analyzes, then commits every selectable row. commit() deliberately
     * drains ONE chunk per call (the web UI polls it for a progress bar), so
     * the CLI loops it to the end.
     */
    private function executer(string $libelle, Importer $importer, string $path, Employee $admin, ImportContext $context): bool
    {
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

        // A row that failed a previous commit keeps ECHEC_COMMIT and is
        // deliberately NOT commit-eligible (that made the progress loop spin
        // forever). Once its cause is fixed — a code fix, a missing student
        // added — --retry-echecs re-queues it as CONFLIT, exactly what the
        // Preview screen's "relancer" button does.
        if ($this->option('retry-echecs')) {
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

        $ids = $batch->rows()->whereIn('status', ImportRow::SELECTABLE_STATUTS)->pluck('id')->all();
        $batch->rows()->whereIn('id', $ids)->update(['selected' => true]);

        $barre = $this->output->createProgressBar(count($ids));
        $barre->setFormat('  %message:-42s% [%bar%] %current%/%max%');
        $barre->setMessage($libelle);
        $barre->start();

        do {
            $resultat = $importer->commit($batch, $ids, $admin, 200);
            $barre->setProgress(max(0, count($ids) - $resultat->remaining));
        } while ($resultat->remaining > 0);

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

    /**
     * Pre-creates every group the file mentions, exactly as the web
     * group-mapping step does — a group of the SAME NAME already present in
     * the centre is REUSED whatever its année, so one cohort is never
     * duplicated a year apart.
     *
     * @return array<string, array{action: string, group_id: int}>
     */
    private function mapperGroupes(SheetReader $reader, string $path, Etablissement $centre, AnneeScolaire $annee): array
    {
        $importer = app(InscriptionImporter::class);
        $reaffecter = app(ReaffecterGroupeVersAnnee::class);
        $map = [];

        foreach ($reader->distinctColumnValues($path, 'Groupe') as $label) {
            $label = trim((string) $label);

            if ($label === '' || $label === '-') {
                continue;
            }

            $group = Group::query()
                ->where('etablissement_id', $centre->id)
                ->where('nom', $label)
                ->first();

            if ($group === null) {
                $group = $importer->createGroupWithFullCatalogSync([
                    'nom' => $label,
                    'niveau' => 'A1',
                    'statut' => Group::STATUT_EN_FORMATION,
                    'etablissement_id' => $centre->id,
                    'annee_scolaire_id' => $annee->id,
                ]);
            } else {
                // Same rule as the web mapping step: it refuses to drag a
                // group holding an active student back into an older année.
                $reaffecter->handle($group, $annee->id);
            }

            $map[$label] = ['action' => 'map', 'group_id' => $group->id];
        }

        return $map;
    }

    /**
     * Every « Opérateur » in the file, matched to an employee by name when
     * possible, otherwise to the importing admin — the CLI has nobody to ask,
     * and an unmapped opérateur turns every one of its rows into an ERREUR.
     *
     * @return array<string, int>
     */
    private function mapperOperateurs(SheetReader $reader, string $path, Employee $admin): array
    {
        $employees = Employee::query()->get(['id', 'nom', 'prenom']);
        $map = [];

        foreach ($reader->distinctColumnValues($path, 'Opérateur') as $label) {
            $label = trim((string) $label);

            if ($label === '') {
                continue;
            }

            $cible = mb_strtolower($label);
            $match = $employees->first(fn (Employee $e): bool => mb_strtolower($e->prenom ?? '') === $cible
                || mb_strtolower($e->nom ?? '') === $cible
                || mb_strtolower(trim(($e->prenom ?? '').' '.($e->nom ?? ''))) === $cible);

            $map[$label] = $match?->id ?? $admin->id;
        }

        return $map;
    }

    /**
     * The inscriptions files, ACTIVE first, each labelled by the statut its
     * own sheet declares — never by its filename.
     *
     * @return list<array{path: string, statut: string}>
     */
    private function fichiersInscriptions(SheetReader $reader, string $old, string $active): array
    {
        $trouves = [];

        foreach ([$active, $old] as $dossier) {
            foreach (glob($dossier.DIRECTORY_SEPARATOR.'liste-inscriptions*.xlsx') ?: [] as $path) {
                $statut = $this->statutDuFichier($reader, $path);

                if ($statut !== null) {
                    $trouves[] = ['path' => $path, 'statut' => $statut];
                }
            }
        }

        usort($trouves, fn (array $a, array $b): int => ($a['statut'] === 'Active' ? 0 : 1) <=> ($b['statut'] === 'Active' ? 0 : 1));

        return $trouves;
    }

    /** Reads the statut the sheet's own « Statut » column carries. */
    private function statutDuFichier(SheetReader $reader, string $path): ?string
    {
        try {
            foreach ($reader->distinctColumnValues($path, 'Statut') as $valeur) {
                $valeur = trim((string) $valeur);

                if ($valeur !== '' && $valeur !== '-') {
                    return $valeur;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function fichier(string $dossier, string $prefixe): ?string
    {
        $matches = glob($dossier.DIRECTORY_SEPARATOR.$prefixe.'*.xlsx') ?: [];
        sort($matches);

        return $matches[0] ?? null;
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile($path, basename($path), null, null, true);
    }

    /**
     * @return array<string, Etablissement> export sub-directory => centre
     */
    private function resolveCentres(string $dossier): array
    {
        $cibles = [];

        foreach (self::DOSSIERS_CENTRES as $sousDossier => $recherche) {
            if (! is_dir($dossier.DIRECTORY_SEPARATOR.'old data'.DIRECTORY_SEPARATOR.$sousDossier)
                && ! is_dir($dossier.DIRECTORY_SEPARATOR.'active data'.DIRECTORY_SEPARATOR.$sousDossier)) {
                continue;
            }

            $centre = Etablissement::query()->where('nom_centre', 'ilike', '%'.$recherche.'%')->first();

            if ($centre !== null) {
                $cibles[$sousDossier] = $centre;
            }
        }

        if ($this->option('tous')) {
            if ($cibles === []) {
                $this->error('Aucun sous-dossier de centre reconnu dans '.$dossier);
            }

            return $cibles;
        }

        $demande = trim((string) $this->option('centre'));

        if ($demande === '') {
            $this->error('Préciser --centre=<nom|id> ou --tous.');

            return [];
        }

        foreach ($cibles as $sousDossier => $centre) {
            if (mb_stripos($sousDossier, $demande) !== false
                || mb_stripos($centre->nom_centre, $demande) !== false
                || (string) $centre->id === $demande) {
                return [$sousDossier => $centre];
            }
        }

        $this->error(sprintf('Centre « %s » introuvable (dossiers disponibles : %s).', $demande, implode(', ', array_keys($cibles))));

        return [];
    }

    /**
     * The caisse EVERY imported payment lands in when --caisse is given.
     *
     * Identified by the employee's login (e-mail or username), because that
     * is what an operator knows; the account used is that employee's physical
     * till. Import-only — see ImportContext::$caisseForceeId for why this
     * override exists and why normal payment entry must never use it.
     */
    private function resolveCaisseForcee(string $identifiant): ?int
    {
        $employee = Employee::query()
            ->whereHas('user', fn ($q) => $q->where('email', $identifiant)->orWhere('username', $identifiant))
            ->first();

        if ($employee === null) {
            $this->error(sprintf('Aucun employé pour « %s » (e-mail ou identifiant de connexion attendu).', $identifiant));

            return null;
        }

        $till = $employee->till;

        if ($till === null) {
            $this->error(sprintf(
                '%s %s n\'a pas de caisse « Caissière » — la provisionner avant l\'import.',
                $employee->prenom,
                $employee->nom
            ));

            return null;
        }

        $this->warn(sprintf(
            'Caisse forcée : TOUS les paiements importés (espèces, TPE, chèque, virement) iront dans « %s » (%s %s).',
            $till->nom,
            $employee->prenom,
            $employee->nom
        ));

        return (int) $till->id;
    }

    private function resolveAnnee(string $demande, bool $courante): ?AnneeScolaire
    {
        if ($demande !== '') {
            $annee = AnneeScolaire::query()->where('nom', $demande)->first();

            if ($annee === null) {
                $this->error(sprintf('Année scolaire « %s » introuvable.', $demande));
            }

            return $annee;
        }

        $annees = AnneeScolaire::query()->orderByDesc('date_debut')->get();

        if ($annees->count() < 2) {
            $this->error('Il faut au moins deux années scolaires en base (courante + précédente).');

            return null;
        }

        return $courante ? $annees->first() : $annees->get(1);
    }
}
