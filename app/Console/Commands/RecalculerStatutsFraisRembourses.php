<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattrapage du 24/09/2026 : recalcule le statut STOCKÉ des frais dont un
 * paiement a été remboursé.
 *
 * Jusqu'ici « payé » ne soustrayait aucun remboursement, et un remboursement
 * ne touchait jamais le frais : ENC-26191 (1 200 DH) remboursé par RMB-003
 * laissait le « Frais de Septembre » de LOUBNA SOUILH en « Payé ». Désormais
 * payé = encaissé − remboursé (`InscriptionFee::montantPaye()`) et les
 * actions de remboursement rafraîchissent le statut — cette commande corrige
 * les lignes écrites AVANT.
 *
 * Simulation par défaut ; `--apply` écrit. Chaque ligne passe par le modèle
 * (Auditable journalise l'ancien et le nouveau statut). Aucun montant,
 * aucun encaissement, aucune caisse n'est touché : seul `statut` change.
 */
final class RecalculerStatutsFraisRembourses extends Command
{
    protected $signature = 'frais:recalculer-statuts-rembourses {--apply : Écrire les corrections (sinon simulation)}';

    protected $description = 'Recalcule le statut des frais dont un paiement a été remboursé (payé = encaissé − remboursé).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $fees = InscriptionFee::query()
            ->whereHas('remboursements')
            ->with('inscription.student')
            ->orderBy('id')
            ->get();

        $corriges = 0;

        foreach ($fees as $fee) {
            $paye = $fee->montantPaye();
            $attendu = match (true) {
                $paye >= (float) $fee->montant => InscriptionFee::STATUT_PAYE,
                $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
                default => InscriptionFee::STATUT_NON_PAYE,
            };

            if ($attendu === $fee->statut) {
                continue;
            }

            $this->line(sprintf(
                '  fee #%d  %s — %s (%s) : %s → %s  (dû %s, payé net %s)',
                $fee->id,
                $fee->inscription?->reference ?? '?',
                $fee->nom,
                $fee->inscription?->student?->nomComplet() ?? '?',
                $fee->statut,
                $attendu,
                number_format((float) $fee->montant, 2, '.', ' '),
                number_format($paye, 2, '.', ' '),
            ));

            if ($apply) {
                DB::transaction(fn () => $fee->rafraichirStatut());
            }

            $corriges++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d frais à statut faux sur %d frais portant un remboursement.',
            $apply ? 'Appliqué' : 'Simulation (aucune écriture)',
            $corriges,
            $fees->count(),
        ));

        return self::SUCCESS;
    }
}
