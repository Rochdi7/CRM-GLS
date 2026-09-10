<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\InscriptionFee;
use Illuminate\Support\Collection;

/**
 * « Ce student a-t-il un frais échu non soldé ? » — la MÊME définition que
 * « Gestion des recouvrements » (`GetRetardsList`), sortie ici pour qu'un
 * deuxième écran ne la redérive pas (CLAUDE.md §11, « un read-model ne
 * redérive jamais une règle métier »).
 *
 * Un frais est en retard quand : il n'est pas masqué, son `date_echeance`
 * est passée, son reste à payer est > 0, et son inscription est encore
 * `Active` — un dossier clos ne doit rien.
 *
 * Utilisé par la fiche de présence (`GetSeanceDetails`) pour signaler, au
 * moment de l'appel, l'étudiant à renvoyer vers l'administration. Le calcul
 * est fait EN LOT pour toute la liste d'étudiants — jamais une requête par
 * ligne (CLAUDE.md § perf).
 */
final class RetardPaiementEtudiant
{
    /** Au-delà de ce nombre de jours, le retard est signalé en rouge. */
    public const SEUIL_JOURS = 5;

    /**
     * @param  list<int>  $studentIds
     * @return array<int, array{jours:int, dateEcheance:string, moisCourant:bool, montant:string, grave:bool}>
     *                                                                                                         Indexé par student_id ; seuls les étudiants EN RETARD y figurent.
     *                                                                                                         La date retenue est celle de l'échéance impayée du MOIS EN COURS
     *                                                                                                         quand il en existe une, sinon la plus ancienne dette (`moisCourant`
     *                                                                                                         dit laquelle). `montant` cumule le reste dû de TOUTES les lignes
     *                                                                                                         échues, quelle que soit la date affichée.
     */
    public function pourEtudiants(array $studentIds, ?int $groupId = null): array
    {
        $studentIds = array_values(array_unique(array_filter($studentIds)));

        if ($studentIds === []) {
            return [];
        }

        $today = now()->toDateString();

        /** @var Collection<int, InscriptionFee> $fees */
        $fees = InscriptionFee::query()
            ->select('inscription_fees.*')
            ->addSelect('inscriptions.student_id as retard_student_id')
            ->withSum('encaissements', 'montant')
            ->join('inscriptions', 'inscriptions.id', '=', 'inscription_fees.inscription_id')
            ->whereIn('inscriptions.student_id', $studentIds)
            ->where('inscriptions.statut', \App\Models\Inscription::STATUT_ACTIVE)
            ->when($groupId !== null, fn ($q) => $q->where('inscriptions.group_id', $groupId))
            ->whereNull('inscription_fees.masque_le')
            ->whereNotNull('inscription_fees.date_echeance')
            ->where('inscription_fees.date_echeance', '<', $today)
            ->get();

        $retards = [];
        $moisCourant = now()->format('Y-m');

        foreach ($fees as $fee) {
            $paye = (float) ($fee->encaissements_sum_montant ?? 0);
            $reste = round(max(0, (float) $fee->montant - $paye), 2);

            if ($reste <= 0) {
                continue;
            }

            $studentId = (int) $fee->retard_student_id;
            $jours = (int) $fee->date_echeance->diffInDays(now());
            $duMoisCourant = $fee->date_echeance->format('Y-m') === $moisCourant;

            if (! isset($retards[$studentId])) {
                $retards[$studentId] = [
                    'jours' => -1,
                    'dateEcheance' => '',
                    'moisCourant' => false,
                    'montant' => 0.0,
                ];
            }

            // La date AFFICHÉE est celle de l'échéance du MOIS EN COURS quand
            // il en existe une impayée — c'est le rappel que l'étudiant
            // attend au moment de l'appel. À défaut (le mois courant est
            // soldé, ou aucune ligne n'y tombe), on retombe sur la plus
            // ancienne dette : elle reste due, et la masquer reviendrait à
            // faire disparaître de l'écran de l'argent que l'étudiant doit.
            $remplace = $retards[$studentId]['moisCourant']
                // Déjà une ligne du mois courant : seule une autre ligne du
                // mois courant, plus ancienne, peut la remplacer.
                ? ($duMoisCourant && $jours > $retards[$studentId]['jours'])
                // Sinon : une ligne du mois courant prime, à défaut la plus ancienne.
                : ($duMoisCourant || $jours > $retards[$studentId]['jours']);

            if ($remplace) {
                $retards[$studentId]['jours'] = $jours;
                $retards[$studentId]['dateEcheance'] = $fee->date_echeance->format('d/m/Y');
                $retards[$studentId]['moisCourant'] = $duMoisCourant;
            }

            // Le montant CUMULE toutes les lignes échues, quelle que soit
            // celle dont on montre la date.
            $retards[$studentId]['montant'] += $reste;
        }

        return array_map(fn (array $r): array => [
            'jours' => $r['jours'],
            'dateEcheance' => $r['dateEcheance'],
            'moisCourant' => $r['moisCourant'],
            'montant' => number_format($r['montant'], 2, '.', ''),
            'grave' => $r['jours'] > self::SEUIL_JOURS,
        ], $retards);
    }
}
