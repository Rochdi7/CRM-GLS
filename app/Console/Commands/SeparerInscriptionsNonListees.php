<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AnneeScolaire;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Inscription;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Splits a group the legacy import MERGED out of two wimschool groups that
 * shared one name across two years.
 *
 * wimschool reuses a group name from one cohort to the next (« Hala 10H »
 * A1 in 2025, « Hala 10H » B2.3 in 2026). Both exports name the group the
 * same way, so InscriptionImporter filed every registration into ONE group:
 * Hala 10H showed 101 students where wimschool shows 71, its « Détails
 * paiement » carried 157 150 DH the old CRM's matrix does not list, and 211
 * avances of the earlier cohort had no group to land on (29/08/2026).
 *
 * The group's matrix export is the authority on who belongs to the CURRENT
 * cohort: every registration of a student the file does NOT list, and that
 * is not Active, is moved to a sibling « <nom> (ancien) » in the académic
 * year its date_inscription falls in, archived as « Fin de formation » via
 * Group::archiverCommeTermine() (groups_historique snapshot included).
 *
 * ONLY inscriptions.group_id / annee_scolaire_id change. Fees, payments,
 * avances, caisses are untouched — money stays on the same fee lines, they
 * simply belong to the right group now. Audit-logged through the models.
 *
 * Usage:
 *   php artisan groupes:separer-non-listes --centre=5 --dossier="data test local/groups active/agadir" --dry-run
 *   php artisan groupes:separer-non-listes --centre=5 --dossier="data test local/groups active/agadir"
 */
final class SeparerInscriptionsNonListees extends Command
{
    protected $signature = 'groupes:separer-non-listes
        {--centre= : Centre (id)}
        {--dossier= : Dossier des matrices XLSX du centre, une par groupe, nommée comme le groupe}
        {--minimum=3 : Ne séparer que si au moins N inscriptions non listées (évite de créer un groupe pour un cas isolé)}
        {--dry-run : Afficher sans modifier}';

    protected $description = "Déplace vers un groupe frère archivé les inscriptions qu'une matrice wimschool ne liste pas (cohorte précédente fusionnée sous le même nom).";

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $centre = (int) $this->option('centre');
        $dossier = rtrim((string) $this->option('dossier'), '/\\');
        $minimum = max(1, (int) $this->option('minimum'));

        if ($centre === 0 || $dossier === '' || ! is_dir($dossier)) {
            $this->error('--centre (id) et --dossier (existant) sont obligatoires.');

            return self::FAILURE;
        }

        $groupes = Group::where('etablissement_id', $centre)->get();
        $annees = AnneeScolaire::orderBy('date_debut')->get();
        $deplacees = 0;
        $crees = 0;

        foreach (glob($dossier.DIRECTORY_SEPARATOR.'*.xlsx') ?: [] as $fichier) {
            if (str_starts_with(basename($fichier), '~$')) {
                continue;
            }

            $nom = pathinfo($fichier, PATHINFO_FILENAME);
            $groupe = $groupes->first(fn (Group $g): bool => $g->nom === $nom)
                ?? $groupes->first(fn (Group $g): bool => $this->cle($g->nom) === $this->cle($nom));

            if ($groupe === null) {
                $this->warn(sprintf('  ?? %s : groupe introuvable — ignoré', $nom));

                continue;
            }

            $listes = $this->etudiantsListes($fichier, $groupe);

            $aDeplacer = Inscription::query()
                ->where('group_id', $groupe->id)
                ->where('statut', '!=', Inscription::STATUT_ACTIVE)
                ->with('student')
                ->get()
                ->reject(fn (Inscription $i): bool => isset($listes[$i->student_id]))
                ->sortBy('date_inscription')
                ->values();

            if ($aDeplacer->count() < $minimum) {
                continue;
            }

            $debut = $aDeplacer->min('date_inscription');
            $fin = $aDeplacer->max(fn (Inscription $i) => $i->date_fin ?? $i->date_inscription);
            $annee = $annees->first(fn (AnneeScolaire $a): bool => $a->date_debut->lte($debut) && $a->date_fin->gte($debut))
                ?? $annees->first();

            $nomFrere = $groupe->nom.' (ancien)';

            $this->line('');
            $this->info(sprintf(
                '%s : %d inscription(s) non listée(s) -> « %s » [%s, %s .. %s]',
                $groupe->nom, $aDeplacer->count(), $nomFrere, $annee->nom,
                substr((string) $debut, 0, 10), substr((string) $fin, 0, 10)
            ));

            foreach ($aDeplacer as $i) {
                $this->line(sprintf('      %-10s %-30s %-11s %s', $i->reference, mb_substr(trim($i->student->prenom.' '.$i->student->nom), 0, 30), $i->statut, substr((string) $i->date_inscription, 0, 10)));
            }

            $deplacees += $aDeplacer->count();

            if ($dry) {
                continue;
            }

            DB::transaction(function () use ($groupe, $groupes, $nomFrere, $annee, $debut, $fin, $aDeplacer, &$crees): void {
                $frere = $groupes->first(fn (Group $g): bool => $g->nom === $nomFrere);

                if ($frere === null) {
                    $frere = Group::create([
                        'nom' => $nomFrere,
                        'niveau' => $groupe->niveau,
                        'enseignant_id' => $groupe->enseignant_id,
                        'salle_id' => $groupe->salle_id,
                        'etablissement_id' => $groupe->etablissement_id,
                        'annee_scolaire_id' => $annee->id,
                        'capacite_max' => $groupe->capacite_max,
                        'statut' => Group::STATUT_EN_FORMATION,
                        'date_debut_formation' => $debut,
                        'date_fin_formation' => $fin,
                    ]);

                    foreach (GroupEnseignant::where('group_id', $groupe->id)->get() as $ge) {
                        GroupEnseignant::create([
                            'group_id' => $frere->id,
                            'enseignant_id' => $ge->enseignant_id,
                            'date_debut' => $ge->date_debut,
                            'date_fin' => $ge->date_fin,
                            'statut' => $ge->statut,
                            'motif' => $ge->motif,
                            'created_by' => $ge->created_by,
                        ]);
                    }

                    $crees++;
                }

                foreach ($aDeplacer as $i) {
                    $i->update(['group_id' => $frere->id, 'annee_scolaire_id' => $annee->id]);
                }

                // The earlier cohort is over: archive it the ONLY sanctioned
                // way, so it lands under the Historique tab with a snapshot.
                if ($frere->statut !== Group::STATUT_FIN_FORMATION) {
                    $frere->refresh()->archiverCommeTermine();
                }
            });
        }

        $this->line('');
        $this->info(sprintf('%s%d inscription(s) déplacée(s), %d groupe(s) frère(s) créé(s).', $dry ? '[DRY-RUN] ' : '', $deplacees, $crees));

        return self::SUCCESS;
    }

    /**
     * Student ids the matrix lists for this group (resolved like
     * AppliquerMatriceGroupe: full name, centre-scoped, enrolled twin first).
     *
     * @return array<int, true>
     */
    private function etudiantsListes(string $chemin, Group $groupe): array
    {
        $reader = new Reader();
        $reader->open($chemin);
        $noms = [];
        $entete = false;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $c = array_map(fn ($v) => trim((string) $v), $row->toArray());

                    if (! $entete) {
                        if (count(array_filter($c, fn ($v) => $v !== '')) > 3) {
                            $entete = true;
                        }

                        continue;
                    }

                    if (str_starts_with($c[0] ?? '', '#') && ($c[1] ?? '') !== '') {
                        $noms[] = $c[1];
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        $ids = [];

        foreach ($noms as $nom) {
            $cle = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $nom)));

            $homonymes = Student::query()
                ->where('etablissement_id', $groupe->etablissement_id)
                ->whereRaw("lower(trim(prenom||' '||nom)) = ? or lower(trim(nom||' '||prenom)) = ?", [$cle, $cle])
                ->get();

            if ($homonymes->count() > 1) {
                $inscrits = Inscription::where('group_id', $groupe->id)->whereIn('student_id', $homonymes->pluck('id'))->pluck('student_id');
                $homonymes = $homonymes->filter(fn (Student $s): bool => $inscrits->contains($s->id))->whenEmpty(fn () => $homonymes);
            }

            foreach ($homonymes as $s) {
                $ids[$s->id] = true;
            }
        }

        return $ids;
    }

    private function cle(string $s): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtr(mb_strtolower($s), [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
            'ô' => 'o', 'ö' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c', 'ï' => 'i', 'î' => 'i',
        ]));
    }
}
