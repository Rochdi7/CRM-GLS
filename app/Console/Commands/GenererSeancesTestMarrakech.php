<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Complète les séances MANQUANTES des groupes de Marrakech, de janvier 2026
 * à aujourd'hui, avec leurs appels — pour TESTER « Calcul paiement prof »
 * en local (22/09/2026).
 *
 * ⚠ Outil de mise au point, PAS un seeder. APP_ENV=local uniquement,
 * dry-run par défaut. Il INVENTE des présences : ne jamais l'exécuter sur
 * une base qui sert à autre chose qu'un test.
 *
 * Comment il devine le rythme d'un groupe : il n'existe AUCUN créneau
 * (`creneaux` est vide pour Marrakech), donc le rythme est déduit des
 * séances DÉJÀ enregistrées du groupe lui-même — jours de la semaine
 * réellement enseignés et horaire le plus fréquent. Un groupe qui n'a
 * jamais eu de séance est ignoré : rien ne dit quand il enseigne.
 *
 * Trois bornes :
 *  1. **Jamais de doublon** : une date qui a déjà une séance est sautée
 *     (contrainte métier §11 — deux séances le même jour feraient deux
 *     appels pour le même cours et doubleraient le diviseur de paie).
 *  2. **Les appels portent sur les étudiants du groupe** : ses inscriptions,
 *     ou à défaut les étudiants déjà appelés par le passé (15 groupes sur 21
 *     n'ont plus aucune inscription « Active »). ~80 % Présent / 20 % Absent.
 *  3. **Graine FIXE** (`mt_srand`) : deux exécutions produisent les mêmes
 *     tirages, donc un test qui échoue se rejoue à l'identique.
 */
final class GenererSeancesTestMarrakech extends Command
{
    protected $signature = 'paiement-prof:generer-seances-marrakech
        {--depuis=2026-01-01 : Date de début}
        {--jusqu-a= : Date de fin (défaut : aujourd hui)}
        {--presence=80 : Pourcentage de présents}
        {--apply : Écrire réellement (sinon dry-run)}';

    protected $description = 'Complète les séances manquantes des groupes de Marrakech avec leurs appels - LOCAL uniquement';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Refusé : outil de test réservé à APP_ENV=local (ici : '.app()->environment().').');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $depuis = Carbon::parse((string) $this->option('depuis'))->startOfDay();
        $jusqua = Carbon::parse((string) ($this->option('jusqu-a') ?: now()->toDateString()))->startOfDay();
        $tauxPresence = max(0, min(100, (int) $this->option('presence')));

        $this->info($apply ? 'MODE ÉCRITURE' : 'DRY-RUN - rien n’est écrit (ajoutez --apply)');
        $this->line(sprintf('Période : %s → %s · %d %% de présents',
            $depuis->format('d/m/Y'), $jusqua->format('d/m/Y'), $tauxPresence));

        // Graine fixe : les mêmes tirages d'une exécution à l'autre.
        mt_srand(20260922);

        $centre = DB::table('etablissements')->where('nom_centre', 'like', '%arrakech%')->first();
        if ($centre === null) {
            $this->error('Centre Marrakech introuvable.');

            return self::FAILURE;
        }

        $totalSeances = 0;
        $totalAppels = 0;

        $run = function () use ($centre, $depuis, $jusqua, $tauxPresence, $apply, &$totalSeances, &$totalAppels): void {
            $this->newLine();

            foreach (Group::query()->where('etablissement_id', $centre->id)->orderBy('nom')->get() as $group) {
                $rythme = $this->rythmeDuGroupe($group);

                if ($rythme === null) {
                    $this->line(sprintf('  %-28s <fg=yellow>ignoré - aucune séance existante, rythme inconnu</>', $group->nom));

                    continue;
                }

                $etudiants = $this->etudiantsDuGroupe($group);

                if ($etudiants === []) {
                    $this->line(sprintf('  %-28s <fg=yellow>ignoré - aucun étudiant rattaché</>', $group->nom));

                    continue;
                }

                // Dates déjà couvertes : on ne crée jamais un doublon.
                $existantes = Seance::query()
                    ->where('group_id', $group->id)
                    ->whereBetween('date_seance', [$depuis->toDateString(), $jusqua->toDateString()])
                    ->pluck('date_seance')
                    ->map(fn (Carbon $d): string => $d->toDateString())
                    ->flip();

                // Jamais de séance AVANT le démarrage du groupe : « GROUP 19H
                // LE 14 SEPT » ne peut pas avoir cours le 1er septembre, et
                // une séance antérieure au début fausserait la première
                // fenêtre de paie (MoisDeGroupe s'ancre sur cette date).
                $debutGroupe = $group->date_debut_formation !== null
                    && $group->date_debut_formation->greaterThan($depuis)
                        ? $group->date_debut_formation->copy()->startOfDay()
                        : $depuis->copy();

                $aCreer = [];
                for ($jour = $debutGroupe->copy(); $jour->lessThanOrEqualTo($jusqua); $jour->addDay()) {
                    if (! in_array($jour->isoWeekday(), $rythme['jours'], true)) {
                        continue;
                    }

                    if ($existantes->has($jour->toDateString())) {
                        continue;
                    }

                    $aCreer[] = $jour->toDateString();
                }

                $appels = count($aCreer) * count($etudiants);
                $totalSeances += count($aCreer);
                $totalAppels += $appels;

                $this->line(sprintf(
                    '  %-28s +%-4d séances  ×%-3d étudiants = %-6d appels  (%s %s)',
                    $group->nom,
                    count($aCreer),
                    count($etudiants),
                    $appels,
                    implode('/', array_map(fn (int $d): string => ['', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'][$d], $rythme['jours'])),
                    substr($rythme['heure_debut'], 0, 5),
                ));

                if (! $apply || $aCreer === []) {
                    continue;
                }

                foreach (array_chunk($aCreer, 40) as $lot) {
                    foreach ($lot as $date) {
                        $seance = Seance::create([
                            'group_id' => $group->id,
                            'date_seance' => $date,
                            'heure_debut' => $rythme['heure_debut'],
                            'heure_fin' => $rythme['heure_fin'],
                            'enseignant_id' => $group->enseignant_id,
                            'etablissement_id' => $group->etablissement_id,
                            'annee_scolaire_id' => $group->annee_scolaire_id,
                            'statut' => Seance::STATUT_EFFECTUEE,
                        ]);

                        $lignes = [];
                        foreach ($etudiants as $studentId) {
                            $lignes[] = [
                                'seance_id' => $seance->id,
                                'student_id' => $studentId,
                                'statut' => mt_rand(1, 100) <= $tauxPresence
                                    ? Presence::STATUT_PRESENT
                                    : Presence::STATUT_ABSENT,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        // Insertion de masse : des dizaines de milliers
                        // d'appels de TEST n'ont pas à passer par Auditable.
                        DB::table('presences')->insert($lignes);
                    }
                }
            }
        };

        if ($apply) {
            DB::transaction($run);
        } else {
            $run();
        }

        $this->newLine();
        $this->info(sprintf('%s : %d séances, %d appels.',
            $apply ? 'Créé' : 'À créer', $totalSeances, $totalAppels));

        return self::SUCCESS;
    }

    /**
     * Rythme déduit des séances existantes du groupe : jours de la semaine
     * réellement enseignés, et horaire le plus fréquent.
     *
     * Un jour n'est retenu que s'il pèse au moins 10 % des séances : une
     * séance de rattrapage isolée un samedi ne doit pas transformer le
     * groupe en cours du week-end.
     *
     * @return array{jours: list<int>, heure_debut: string, heure_fin: string}|null
     */
    private function rythmeDuGroupe(Group $group): ?array
    {
        $total = Seance::query()->where('group_id', $group->id)->count();

        if ($total === 0) {
            // Aucune séance passée : le rythme est inconnu. On ne peut le
            // supposer QUE si le groupe a une date de début explicite — un
            // groupe tout neuf démarre alors lundi→vendredi, le rythme de
            // tous les autres groupes de Marrakech. Sans date de début, on
            // refuse : inventer un calendrier pour un groupe dont on ignore
            // même le démarrage produirait des séances arbitraires.
            if ($group->date_debut_formation === null) {
                return null;
            }

            // L'horaire est LU DANS LE NOM (« GROUP 16H SEPTEMBRE » → 16:00) :
            // c'est la seule indication disponible, et elle est fiable ici —
            // tous les groupes de Marrakech portent leur heure dans leur nom.
            $heure = preg_match('/(\d{1,2})\s*H/i', $group->nom, $m) ? (int) $m[1] : 19;

            return [
                'jours' => [1, 2, 3, 4, 5],
                'heure_debut' => sprintf('%02d:00:00', $heure),
                'heure_fin' => sprintf('%02d:30:00', $heure + 2),
            ];
        }

        $parJour = DB::table('seances')
            ->where('group_id', $group->id)
            ->select(DB::raw('extract(isodow from date_seance)::int as d'), DB::raw('count(*) n'))
            ->groupBy('d')
            ->pluck('n', 'd');

        $jours = [];
        foreach ($parJour as $jour => $n) {
            if ($n / $total >= 0.10) {
                $jours[] = (int) $jour;
            }
        }

        sort($jours);

        if ($jours === []) {
            return null;
        }

        $horaire = DB::table('seances')
            ->where('group_id', $group->id)
            ->whereNotNull('heure_debut')
            ->select('heure_debut', 'heure_fin', DB::raw('count(*) n'))
            ->groupBy('heure_debut', 'heure_fin')
            ->orderByDesc('n')
            ->first();

        return [
            'jours' => $jours,
            'heure_debut' => $horaire->heure_debut ?? '19:00:00',
            'heure_fin' => $horaire->heure_fin ?? '21:30:00',
        ];
    }

    /**
     * Les étudiants à appeler : les inscriptions du groupe (TOUS statuts —
     * 15 groupes sur 21 n'ont plus aucune inscription « Active », leurs
     * dossiers ayant été clos), sinon les étudiants déjà appelés par le
     * passé dans ce groupe.
     *
     * @return list<int>
     */
    private function etudiantsDuGroupe(Group $group): array
    {
        $ids = DB::table('inscriptions')
            ->where('group_id', $group->id)
            ->distinct()
            ->pluck('student_id')
            ->all();

        if ($ids !== []) {
            return array_map('intval', $ids);
        }

        return array_map('intval', DB::table('presences')
            ->join('seances', 'seances.id', '=', 'presences.seance_id')
            ->where('seances.group_id', $group->id)
            ->distinct()
            ->pluck('presences.student_id')
            ->all());
    }
}
