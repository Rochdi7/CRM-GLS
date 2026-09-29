<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Actions;

use App\Models\Employee;
use App\Models\EnseignantTauxMensuel;
use Illuminate\Support\Carbon;

/**
 * Aligne `enseignant_taux_mensuels` sur la liste soumise par l'onglet
 * « Paiement prof » (mode win-win) : les mois présents sont créés ou mis à
 * jour, les mois absents sont supprimés.
 *
 * ⚠ Appelé DANS la transaction du contrôleur, jamais seul : un taux de paie
 * et la fiche qui le porte se sauvent ensemble ou pas du tout.
 *
 * Passe par Eloquent ligne par ligne (jamais un `insert`/`delete` de masse)
 * pour que `Auditable` journalise chaque montant ajouté, changé ou retiré —
 * un taux de paie qui bouge est exactement ce que le journal doit pouvoir
 * expliquer.
 *
 * Si le mode soumis n'est PAS win-win, la table du prof est vidée : des
 * lignes orphelines d'un ancien mode ressurgiraient le jour où il y
 * reviendrait, sur des chiffres que plus personne ne relit.
 */
final class SynchroniserTauxMensuels
{
    /**
     * La clé d'une ligne est le couple (groupe, mois) : `group_id` NULL =
     * « Tous les groupes », même convention que la table (29/09/2026).
     *
     * @param  list<array{mois: string, montant_par_etudiant: numeric, group_id?: int|string|null}>|null  $lignes
     */
    public function handle(Employee $employee, ?string $mode, ?array $lignes): void
    {
        if ($mode !== Employee::MODE_PAIEMENT_WIN_WIN) {
            $employee->tauxMensuels()->get()->each->delete();

            return;
        }

        $voulus = [];
        foreach ($lignes ?? [] as $ligne) {
            // « YYYY-MM » → 1er du mois ; + le groupe : la clé unique de la table.
            // « ! » remet le jour à 1 : sans lui, un 29–31 du mois courant
            // fait déborder « 2026-02 » sur mars.
            $mois = Carbon::createFromFormat('!Y-m', (string) $ligne['mois'])->startOfMonth()->toDateString();
            $groupId = filled($ligne['group_id'] ?? null) ? (int) $ligne['group_id'] : null;
            $voulus[self::cle($groupId, $mois)] = [
                'group_id' => $groupId,
                'mois' => $mois,
                'montant' => round((float) $ligne['montant_par_etudiant'], 2),
            ];
        }

        $existants = $employee->tauxMensuels()->get()->keyBy(
            fn (EnseignantTauxMensuel $t) => self::cle($t->group_id, $t->mois->toDateString()),
        );

        foreach ($existants as $cle => $ligne) {
            if (! array_key_exists($cle, $voulus)) {
                $ligne->delete();
            }
        }

        foreach ($voulus as $cle => ['group_id' => $groupId, 'mois' => $mois, 'montant' => $montant]) {
            $ligne = $existants->get($cle);

            if ($ligne === null) {
                $employee->tauxMensuels()->create([
                    'group_id' => $groupId,
                    'mois' => $mois,
                    'montant_par_etudiant' => $montant,
                ]);
            } elseif ((float) $ligne->montant_par_etudiant !== $montant) {
                $ligne->update(['montant_par_etudiant' => $montant]);
            }
        }
    }

    private static function cle(?int $groupId, string $mois): string
    {
        return ($groupId ?? 'tous').'|'.$mois;
    }
}
