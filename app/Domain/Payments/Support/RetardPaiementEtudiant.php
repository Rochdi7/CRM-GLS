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
     * @return array<int, array{jours:int, dateEcheance:string, montant:string, grave:bool}>
     *                                                                                       Indexé par student_id ; seuls les étudiants EN RETARD y figurent.
     *                                                                                       La ligne retenue est la plus ANCIENNE échéance impayée (le
     *                                                                                       retard le plus long), et `montant` est le total encore dû sur
     *                                                                                       toutes les lignes échues de cet étudiant.
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

        foreach ($fees as $fee) {
            $paye = (float) ($fee->encaissements_sum_montant ?? 0);
            $reste = round(max(0, (float) $fee->montant - $paye), 2);

            if ($reste <= 0) {
                continue;
            }

            $studentId = (int) $fee->retard_student_id;
            $jours = (int) $fee->date_echeance->diffInDays(now());

            if (! isset($retards[$studentId])) {
                $retards[$studentId] = ['jours' => -1, 'dateEcheance' => '', 'montant' => 0.0];
            }

            // On retient l'échéance la PLUS ANCIENNE — c'est elle qui
            // qualifie la gravité — et on CUMULE le reste dû de toutes les
            // lignes échues de cet étudiant.
            if ($jours > $retards[$studentId]['jours']) {
                $retards[$studentId]['jours'] = $jours;
                $retards[$studentId]['dateEcheance'] = $fee->date_echeance->format('d/m/Y');
            }

            $retards[$studentId]['montant'] += $reste;
        }

        return array_map(fn (array $r): array => [
            'jours' => $r['jours'],
            'dateEcheance' => $r['dateEcheance'],
            'montant' => number_format($r['montant'], 2, '.', ''),
            'grave' => $r['jours'] > self::SEUIL_JOURS,
        ], $retards);
    }
}
