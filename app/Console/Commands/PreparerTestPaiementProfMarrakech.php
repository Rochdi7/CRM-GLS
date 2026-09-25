<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Seance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Prépare le centre GLS Marrakech pour TESTER « Calcul paiement prof » en
 * local (22/09/2026). Outil de mise au point, PAS un seeder : il ne s'exécute
 * que sur demande, dry-run par défaut, et refuse la production.
 *
 * Ce qu'il fait — et seulement ça :
 *   1. Affecte chaque groupe à l'enseignant que son NOM désigne
 *      (« Herr Driss 10H » → Kadiri Moulay Driss), via GroupEnseignant +
 *      groups.enseignant_id. Les groupes sans indice restent sans prof.
 *   2. Pose `date_debut_formation` = date de la première séance, pour que
 *      les mois de paie s'ancrent sur le vrai démarrage.
 *   3. Renseigne `seances.enseignant_id` là où il manque, avec le prof du
 *      groupe — les 573 séances orphelines paieraient sinon personne.
 *   4. Configure la paie des enseignants : Ouassima en horaire (140 DH/h),
 *      les autres alternés GLS (500 DH/étudiant) et win-win (400 DH en
 *      septembre 2025, +20 DH par mois).
 *
 * Il ne crée AUCUNE séance ni présence : 2 084 séances et 39 461 appels
 * réels existent déjà. Inventer des appels par-dessus fausserait le test.
 */
final class PreparerTestPaiementProfMarrakech extends Command
{
    protected $signature = 'paiement-prof:preparer-test-marrakech {--apply : Écrire réellement (sinon dry-run)}';

    protected $description = 'Prépare Marrakech (affectations, dates, paie des profs) pour tester le calcul paiement prof - LOCAL uniquement';

    /** Fragment de nom de groupe (insensible à la casse) → référence employé. */
    private const CORRESPONDANCES = [
        'abdelhadi' => 'EMP-035',
        'abdelah' => 'EMP-037',
        'abdellah' => 'EMP-037',
        'abdollah' => 'EMP-037',
        'abdessamad' => 'EMP-029',
        'adil' => 'EMP-030',
        'alaoui' => 'EMP-038',
        'driss' => 'EMP-039',
        'hanafi' => 'EMP-034',
        'jail' => 'EMP-033',
        'nizar' => 'EMP-036',
        'ouassima' => 'EMP-031',
    ];

    public function handle(): int
    {
        // Liste BLANCHE, pas liste noire : un environnement inattendu
        // (staging, un .env mal copié) est refusé au même titre que la
        // production. Cet outil réécrit des affectations et des taux de paie
        // en masse — il ne doit jamais tourner ailleurs que sur un poste
        // de développement.
        if (! app()->environment('local')) {
            $this->error('Refusé : outil de test réservé à APP_ENV=local (ici : '.app()->environment().').');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'MODE ÉCRITURE' : 'DRY-RUN - rien n\'est écrit (ajoutez --apply)');

        $centre = DB::table('etablissements')->where('nom_centre', 'like', '%arrakech%')->first();
        if ($centre === null) {
            $this->error('Centre Marrakech introuvable.');

            return self::FAILURE;
        }

        $profs = Employee::query()
            ->where('etablissement_id', $centre->id)
            ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
            ->get()
            ->keyBy('reference');

        $run = function () use ($apply, $centre, $profs): void {
            $this->affecterGroupes($centre->id, $profs, $apply);
            $this->configurerPaie($profs, $apply);
        };

        if ($apply) {
            DB::transaction($run);
            $this->newLine();
            $this->info('Terminé. Vérifiez : php artisan paiement-prof:preparer-test-marrakech (dry-run doit ne plus rien proposer).');
        } else {
            $run();
        }

        return self::SUCCESS;
    }

    /** @param  Collection<string, Employee>  $profs  indexée par référence */
    private function affecterGroupes(int $centreId, Collection $profs, bool $apply): void
    {
        $this->newLine();
        $this->line('<comment>1–3. Groupes : enseignant, date de début, séances orphelines</comment>');

        foreach (Group::query()->where('etablissement_id', $centreId)->orderBy('nom')->get() as $group) {
            $ref = $this->profPourNom($group->nom);
            $prof = $ref !== null ? $profs->get($ref) : null;

            $premiere = Seance::query()->where('group_id', $group->id)->min('date_seance');
            $orphelines = Seance::query()->where('group_id', $group->id)->whereNull('enseignant_id')->count();

            if ($prof === null) {
                $this->line(sprintf('  %-34s → <fg=yellow>aucun prof reconnu dans le nom</> (séances : %d)', $group->nom, Seance::where('group_id', $group->id)->count()));

                continue;
            }

            $this->line(sprintf(
                '  %-34s → %-30s début=%s  orphelines=%d',
                $group->nom,
                $prof->nomComplet(),
                $premiere ?? 'aucune séance',
                $orphelines,
            ));

            if (! $apply) {
                continue;
            }

            $debut = $premiere !== null ? Carbon::parse($premiere)->toDateString() : null;

            $group->update([
                'enseignant_id' => $prof->id,
                'date_debut_formation' => $group->date_debut_formation ?? $debut,
            ]);

            GroupEnseignant::query()->firstOrCreate(
                ['group_id' => $group->id, 'enseignant_id' => $prof->id, 'statut' => 'Actif'],
                ['date_debut' => $debut ?? now()->toDateString()],
            );

            // Eloquent ligne par ligne serait 573 updates auditées pour une
            // mise au point locale ; ici un update de masse suffit et n'a
            // pas à laisser de trace dans le journal.
            Seance::query()
                ->where('group_id', $group->id)
                ->whereNull('enseignant_id')
                ->update(['enseignant_id' => $prof->id]);
        }
    }

    /** @param  Collection<string, Employee>  $profs  indexée par référence */
    private function configurerPaie(Collection $profs, bool $apply): void
    {
        $this->newLine();
        $this->line('<comment>4. Paie des enseignants</comment>');

        $i = 0;
        foreach ($profs->sortBy('nom') as $prof) {
            if ($prof->reference === 'EMP-031') {
                $mode = Employee::MODE_PAIEMENT_HORAIRE;
                $detail = '140 DH/h';
            } elseif ($i++ % 2 === 0) {
                $mode = Employee::MODE_PAIEMENT_GLS;
                $detail = '500 DH/étudiant';
            } else {
                $mode = Employee::MODE_PAIEMENT_WIN_WIN;
                $detail = '400 → +20/mois (sept. 2025 → août 2026)';
            }

            $this->line(sprintf('  %-32s %-8s %s', $prof->nomComplet(), $mode, $detail));

            if (! $apply) {
                continue;
            }

            $prof->update([
                'mode_paiement_prof' => $mode,
                'taux_horaire_prof' => $mode === Employee::MODE_PAIEMENT_HORAIRE ? 140 : null,
                'montant_par_etudiant_prof' => $mode === Employee::MODE_PAIEMENT_GLS ? 500 : null,
            ]);

            $prof->tauxMensuels()->delete();

            if ($mode === Employee::MODE_PAIEMENT_WIN_WIN) {
                // 12 mois couvrant toute la plage de séances (oct. 2025 → août 2026).
                $mois = Carbon::create(2025, 9, 1);
                for ($m = 0; $m < 12; $m++) {
                    $prof->tauxMensuels()->create([
                        'mois' => $mois->copy()->addMonths($m)->toDateString(),
                        'montant_par_etudiant' => 400 + 20 * $m,
                    ]);
                }
            }
        }
    }

    private function profPourNom(string $nom): ?string
    {
        $bas = mb_strtolower($nom);

        // Les fragments les plus longs d'abord : « abdellah » avant « adil »
        // ne pose pas de problème, mais « abdelhadi » doit primer sur tout
        // ce qui commence par « abdel ».
        $fragments = array_keys(self::CORRESPONDANCES);
        usort($fragments, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($fragments as $fragment) {
            if (str_contains($bas, $fragment)) {
                return self::CORRESPONDANCES[$fragment];
            }
        }

        return null;
    }
}
