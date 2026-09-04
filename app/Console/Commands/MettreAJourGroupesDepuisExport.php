<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Groups\Actions\SynchroniserDatesInscriptions;
use App\Models\Employee;
use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Back-fills the four descriptive fields the legacy INSCRIPTIONS export never
 * carried, from the old CRM's « groups classes » export:
 *
 *   CLASSIFICATION_NAME        -> groups.niveau                (A1.1 … B2.3)
 *   START_DATE / END_DATE      -> groups.date_debut_formation / _fin
 *   STATUS_NAME                -> groups.statut
 *   EMPLOYEE_TEACHER_FULL_NAME -> groups.enseignant_id         (matched by name)
 *
 * Nothing else is touched: not the group's centre, not its année, not a
 * single dirham. A group whose name is not in the file is left exactly as it
 * is, and a teacher who cannot be matched leaves enseignant_id untouched
 * rather than guessing.
 *
 * ⚠ Une exception, depuis le 04/09/2026 : quand les DATES du groupe changent,
 * elles redescendent sur ses inscriptions Actives
 * (Domain\Groups\Actions\SynchroniserDatesInscriptions), exactement comme
 * dans le modal « Modifier le groupe ». `inscriptions.date_debut` /
 * `date_fin` sont les dates du groupe recopiées, pas des données propres à
 * l'étudiant — seule `date_inscription` lui appartient, et elle n'est jamais
 * touchée. Sans cela, corriger un groupe ici laissait tous ses étudiants sur
 * l'ancienne copie.
 *
 * Rows are saved one by one through Eloquent — NOT a mass update — so the
 * Auditable trait journals every change (avant/après) like any other edit.
 *
 * ⚠ « Fin de formation » is never written: that transition owes a
 * groups_historique snapshot (Group::archiverCommeTermine, CLAUDE.md §11) and
 * this command cannot write an honest one. A group the export marks
 * « Terminé » still gets its dates and classification — only its statut is
 * left alone, to be archived from the UI. --sans-statut skips the column
 * entirely.
 *
 * Usage:
 *   php artisan groupes:mettre-a-jour --fichier="/var/www/crm-gls/groupsclasses.xlsm" --dry-run
 *   php artisan groupes:mettre-a-jour --fichier="/var/www/crm-gls/groupsclasses.xlsm"
 */
final class MettreAJourGroupesDepuisExport extends Command
{
    protected $signature = 'groupes:mettre-a-jour
        {--fichier= : Export « groups classes » de l\'ancien CRM (.xlsm/.xlsx)}
        {--centre= : Limiter à un centre (id ou partie du nom)}
        {--sans-statut : Ne pas toucher la colonne statut}
        {--sans-enseignant : Ne pas toucher l\'affectation enseignant}
        {--dry-run : Afficher les changements sans rien écrire}';

    protected $description = "Met à jour niveau, dates de formation, statut et enseignant des groupes depuis l'export de l'ancien CRM.";

    /** Export STATUS_NAME => Group::STATUTS. */
    private const array STATUTS = [
        'en préparation' => Group::STATUT_EN_INSCRIPTION,
        'en formation' => Group::STATUT_EN_FORMATION,
        'terminé' => Group::STATUT_FIN_FORMATION,
        'annulé' => Group::STATUT_ANNULEE,
    ];

    private const array COLONNES = [
        'NAME', 'START_DATE', 'END_DATE', 'CLASSIFICATION_NAME',
        'EMPLOYEE_TEACHER_FULL_NAME', 'STATUS_NAME',
    ];

    public function __construct(
        private readonly SynchroniserDatesInscriptions $synchroniserDates,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $fichier = (string) $this->option('fichier');

        if ($fichier === '' || ! is_file($fichier)) {
            $this->error('--fichier est obligatoire et doit exister.');

            return self::FAILURE;
        }

        // Read directly rather than through SheetReader: that class is tuned
        // for the legacy CRM's own exports (its header detection requires a
        // first cell of « N° » / « Réf »), while this file is a different
        // report starting at « ID ». Loosening the shared reader would weaken
        // the guard that protects the money imports.
        $lignes = $this->lireFichier($fichier);

        if ($lignes === []) {
            $this->error('Colonnes attendues introuvables : '.implode(', ', self::COLONNES));

            return self::FAILURE;
        }

        $this->info(sprintf('%d groupe(s) distinct(s) dans le fichier.', count($lignes)));

        $groupes = Group::query()
            ->when($this->option('centre'), function ($q, $centre): void {
                $q->whereHas('etablissement', fn ($e) => is_numeric($centre)
                    ? $e->whereKey((int) $centre)
                    : $e->where('nom_centre', 'ilike', '%'.$centre.'%'));
            })
            ->with('etablissement:id,nom_centre')
            ->get();

        $enseignants = $this->indexEnseignants();

        $modifies = 0;
        $inchanges = 0;
        $absents = [];
        $profsInconnus = [];
        $inscriptionsResynchronisees = 0;

        foreach ($groupes as $groupe) {
            $ligne = $lignes[$this->cle($groupe->nom)] ?? null;

            if ($ligne === null) {
                $absents[] = $groupe->nom;

                continue;
            }

            $changements = $this->changementsPour($groupe, $ligne, $enseignants, $profsInconnus);

            if ($changements === []) {
                $inchanges++;

                continue;
            }

            $this->line(sprintf(
                '  %-30s %s',
                mb_substr($groupe->nom, 0, 30),
                implode(', ', array_map(
                    static fn (string $k, array $v): string => sprintf('%s: %s -> %s', $k, $v[0] ?: '—', $v[1] ?: '—'),
                    array_keys($changements),
                    $changements
                ))
            ));

            if (! $this->option('dry-run')) {
                DB::transaction(function () use ($groupe, $changements, &$inscriptionsResynchronisees): void {
                    foreach ($changements as $colonne => [, $apres]) {
                        $groupe->{$colonne} = $apres;
                    }
                    $groupe->save();

                    // Les dates de formation appartiennent au GROUPE et sont
                    // recopiees sur ses inscriptions : les corriger ici sans
                    // les propager laisserait chaque etudiant sur l'ancienne
                    // copie. Meme action que le modal « Modifier le groupe »,
                    // pour que les deux chemins ne puissent pas diverger.
                    if (isset($changements['date_debut_formation']) || isset($changements['date_fin_formation'])) {
                        $inscriptionsResynchronisees += $this->synchroniserDates->handle($groupe);
                    }
                });
            }

            $modifies++;
        }

        if ($inscriptionsResynchronisees > 0) {
            $this->line('');
            $this->info(sprintf(
                '%d inscription(s) réalignée(s) sur les nouvelles dates de leur groupe.',
                $inscriptionsResynchronisees
            ));
        }

        $this->line('');
        $this->info(sprintf(
            '%s%d modifié(s), %d inchangé(s), %d absent(s) du fichier.',
            $this->option('dry-run') ? '[DRY-RUN] ' : '',
            $modifies,
            $inchanges,
            count($absents)
        ));

        if ($absents !== []) {
            $this->warn('Absents du fichier (laissés tels quels) : '.implode(', ', array_slice($absents, 0, 15))
                .(count($absents) > 15 ? ' …' : ''));
        }

        if ($profsInconnus !== []) {
            $this->warn('Enseignants non trouvés (affectation inchangée) : '.implode(', ', array_keys($profsInconnus)));
        }

        return self::SUCCESS;
    }

    /**
     * One row per group, keyed by normalized name — the export repeats a
     * group once per enrolled student.
     *
     * @return array<string, array<string, string>>
     */
    private function lireFichier(string $chemin): array
    {
        $reader = new Reader();
        $reader->open($chemin);
        $lignes = [];
        $entetes = null;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cellules = array_map(
                        static fn (mixed $v): string => trim((string) $v),
                        $row->toArray()
                    );

                    if ($entetes === null) {
                        $candidat = array_flip($cellules);

                        foreach (self::COLONNES as $colonne) {
                            if (! isset($candidat[$colonne])) {
                                return [];
                            }
                        }

                        $entetes = $candidat;

                        continue;
                    }

                    $nom = $this->texte($cellules[$entetes['NAME']] ?? '');

                    if ($nom === '' || isset($lignes[$this->cle($nom)])) {
                        continue;
                    }

                    $ligne = [];
                    foreach (self::COLONNES as $colonne) {
                        $ligne[$colonne] = $cellules[$entetes[$colonne]] ?? '';
                    }

                    $lignes[$this->cle($nom)] = $ligne;
                }

                break; // first sheet only
            }
        } finally {
            $reader->close();
        }

        return $lignes;
    }

    /**
     * @param  array<string, mixed>  $ligne
     * @param  array<string, int>  $enseignants
     * @param  array<string, true>  $profsInconnus
     * @return array<string, array{0: ?string, 1: ?string}> colonne => [avant, après]
     */
    private function changementsPour(Group $groupe, array $ligne, array $enseignants, array &$profsInconnus): array
    {
        $changements = [];

        $niveau = $this->texte($ligne['CLASSIFICATION_NAME'] ?? '');
        if ($niveau !== '' && in_array($niveau, Group::NIVEAUX, true) && $niveau !== $groupe->niveau) {
            $changements['niveau'] = [$groupe->niveau, $niveau];
        }

        foreach ([['START_DATE', 'date_debut_formation'], ['END_DATE', 'date_fin_formation']] as [$source, $colonne]) {
            $date = $this->date($ligne[$source] ?? '');
            $actuelle = $groupe->{$colonne}?->toDateString();

            if ($date !== null && $date !== $actuelle) {
                $changements[$colonne] = [$actuelle, $date];
            }
        }

        if (! $this->option('sans-statut')) {
            $statut = self::STATUTS[mb_strtolower($this->texte($ligne['STATUS_NAME'] ?? ''))] ?? null;

            // ⚠ « Fin de formation » is NEVER written here. Ending a group is
            // the one transition that owes a groups_historique snapshot
            // (Group::archiverCommeTermine, CLAUDE.md §11), and this command
            // cannot produce an honest one — the snapshot would describe today
            // rather than the day the group actually ended. Those 95 groups
            // keep their current statut and are archived from the UI, which
            // writes the snapshot properly (decision 27/08/2026).
            if ($statut === Group::STATUT_FIN_FORMATION) {
                $statut = null;
            }

            if ($statut !== null && $statut !== $groupe->statut) {
                $changements['statut'] = [$groupe->statut, $statut];
            }
        }

        if (! $this->option('sans-enseignant')) {
            $nom = $this->texte($ligne['EMPLOYEE_TEACHER_FULL_NAME'] ?? '');

            if ($nom !== '') {
                $id = $enseignants[$this->cle($nom)] ?? $enseignants[$this->lettres($nom)] ?? null;

                if ($id === null) {
                    $profsInconnus[$nom] = true;
                } elseif ($id !== $groupe->enseignant_id) {
                    $changements['enseignant_id'] = [(string) $groupe->enseignant_id, (string) $id];
                }
            }
        }

        return $changements;
    }

    /**
     * Teachers by normalized name, both orders. The export sometimes drops the
     * space between first and last name ("RhassaneRharss"), so a space-free
     * key is indexed too.
     *
     * @return array<string, int>
     */
    private function indexEnseignants(): array
    {
        $index = [];

        foreach (Employee::query()->where('categorie', Employee::CATEGORIE_ENSEIGNANT)->get(['id', 'nom', 'prenom']) as $e) {
            foreach ([$e->prenom.' '.$e->nom, $e->nom.' '.$e->prenom] as $variante) {
                $index[$this->cle($variante)] = $e->id;
                // Letters only: the export drops spaces at random
                // ("MoulayDriss Kadiri" for « Moulay Driss Kadiri »,
                // "RhassaneRharss"), so a space-insensitive key is the only
                // one that matches both spellings.
                $index[$this->lettres($variante)] = $e->id;
            }
        }

        return $index;
    }

    /** Letters and digits only, lower-cased — spacing and punctuation dropped. */
    private function lettres(string $valeur): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($valeur));
    }

    private function cle(string $valeur): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim(mb_strtolower($valeur)));
    }

    private function texte(mixed $valeur): string
    {
        $valeur = (string) preg_replace('/\s+/u', ' ', trim((string) ($valeur ?? '')));

        return in_array($valeur, ['-', 'NaN'], true) ? '' : $valeur;
    }

    /** The export writes ISO datetimes ("2025-09-14 00:00:00"); only the day matters. */
    private function date(mixed $valeur): ?string
    {
        $texte = $this->texte($valeur);

        if ($texte === '') {
            return null;
        }

        return preg_match('/^(\d{4}-\d{2}-\d{2})/', $texte, $m) === 1 ? $m[1] : null;
    }
}
