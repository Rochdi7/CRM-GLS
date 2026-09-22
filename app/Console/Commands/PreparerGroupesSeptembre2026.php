<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Group;
use App\Models\GroupEnseignant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Met en service les 5 groupes « SEPTEMBRE » de Marrakech, qui appartiennent
 * à l'année 2026/2027 mais n'avaient ni enseignant, ni date de début, ni
 * séance — donc rien à calculer sur l'année courante.
 *
 * ⚠ Outil de mise au point local (22/09/2026), APP_ENV=local uniquement,
 * dry-run par défaut. Il affecte des enseignants de façon ARBITRAIRE : c'est
 * du test, pas une décision pédagogique.
 *
 * Les enseignants sont choisis pour couvrir LES TROIS MODES de paie sur
 * l'année 2026/2027 — sinon un mode resterait intestable faute de groupe
 * actif. Les séances elles-mêmes sont ensuite créées par
 * `paiement-prof:generer-seances-marrakech --depuis=2026-09-01`.
 */
final class PreparerGroupesSeptembre2026 extends Command
{
    protected $signature = 'paiement-prof:preparer-groupes-septembre {--apply : Écrire réellement (sinon dry-run)}';

    protected $description = 'Affecte enseignant + date de début aux groupes SEPTEMBRE 2026/2027 de Marrakech — LOCAL uniquement';

    /**
     * Groupe → [référence employé, jour de démarrage, horaire].
     *
     * Le jour de démarrage est choisi pour EXERCER l'ancrage des mois de
     * paie : « GROUP 19H LE 14 SEPT » démarre le 14, donc son mois de
     * septembre court du 14/09 au 13/10 — exactement le cas que le nom
     * annonce, et le plus susceptible de révéler un bug de fenêtre.
     */
    private const PLAN = [
        'GROUP 10H SEPTEMBRE' => ['EMP-032', '2026-09-01', '10:00:00', '12:30:00'], // Samih — gls (seul prof sans groupe)
        'GROUP 16H SEPTEMBRE' => ['EMP-033', '2026-09-01', '16:00:00', '18:30:00'], // Jail — win_win
        'GROUP 19H SEPTEMBRE' => ['EMP-031', '2026-09-01', '19:00:00', '21:30:00'], // Ouassima — horaire
        'GROUP 19H LE 14 SEPT' => ['EMP-030', '2026-09-14', '19:00:00', '21:30:00'], // Adil — gls, démarrage décalé
        'b2 septembre' => ['EMP-029', '2026-09-07', '19:00:00', '21:30:00'], // Maali — win_win, démarrage décalé
    ];

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Refusé : outil de test réservé à APP_ENV=local (ici : '.app()->environment().').');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'MODE ÉCRITURE' : 'DRY-RUN — rien n’est écrit (ajoutez --apply)');
        $this->newLine();

        $profs = Employee::query()
            ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
            ->get()
            ->keyBy('reference');

        $run = function () use ($profs, $apply): void {
            foreach (self::PLAN as $nomGroupe => [$ref, $debut, $hDebut, $hFin]) {
                $group = Group::query()->where('nom', $nomGroupe)->first();
                $prof = $profs->get($ref);

                if ($group === null || $prof === null) {
                    $this->line(sprintf('  %-24s <fg=yellow>introuvable (groupe ou prof)</>', $nomGroupe));

                    continue;
                }

                $inscriptions = DB::table('inscriptions')->where('group_id', $group->id)->count();

                $this->line(sprintf(
                    '  %-24s → %-28s %s %s  (%d inscriptions, mode %s)',
                    $group->nom,
                    $prof->nomComplet(),
                    Carbon::parse($debut)->format('d/m/Y'),
                    substr($hDebut, 0, 5),
                    $inscriptions,
                    $prof->mode_paiement_prof ?? '-',
                ));

                if (! $apply) {
                    continue;
                }

                $group->update([
                    'enseignant_id' => $prof->id,
                    'date_debut_formation' => $debut,
                ]);

                GroupEnseignant::query()->firstOrCreate(
                    ['group_id' => $group->id, 'enseignant_id' => $prof->id, 'statut' => 'Actif'],
                    ['date_debut' => $debut],
                );
            }
        };

        if ($apply) {
            DB::transaction($run);
            $this->newLine();
            $this->info('Fait. Générez maintenant les séances :');
            $this->line('  php artisan paiement-prof:generer-seances-marrakech --depuis=2026-09-01 --apply');
        } else {
            $run();
        }

        return self::SUCCESS;
    }
}
