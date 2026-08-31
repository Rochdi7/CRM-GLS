<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;

/**
 * Réparation ponctuelle : de l'argent accroché à un frais MASQUÉ.
 *
 * Jusqu'au 31/08/2026, masquer une ligne de frais depuis le modal
 * d'inscription (BasculerVisibiliteFraisInscription::hide) ne libérait PAS
 * ses paiements. L'encaissement restait rattaché à une ligne devenue
 * invisible : il ne comptait plus dans le dû de l'étudiant, n'apparaissait
 * pas dans l'onglet Avances, et ne pouvait donc plus être réappliqué — alors
 * que l'étudiant avait bel et bien payé et que l'argent était bien en caisse.
 * Signalé sur une inscription où 300 DH + 200 DH de frais d'inscription
 * avaient ainsi disparu de l'écran.
 *
 * L'action a été corrigée ; cette commande rattrape les lignes déjà masquées
 * AVANT le correctif. Elle fait exactement ce que fait désormais le masquage :
 * détacher l'encaissement du frais (ConvertirEncaissementsEnAvance), ce qui
 * en refait une avance réapplicable. Aucun encaissement n'est supprimé, aucun
 * solde de caisse ne bouge — seule l'affectation change (§11).
 *
 * Un paiement REMBOURSÉ est ignoré : son argent a déjà quitté la caisse, le
 * convertisseur le refuse à juste titre.
 *
 * Dry-run par défaut, comme caisse:recalculer-soldes — lire la sortie avant
 * d'exécuter avec --apply. Idempotent : une fois libéré, un encaissement n'a
 * plus de inscription_fee_id et n'est donc plus vu par la requête.
 */
final class LibererPaiementsFraisMasques extends Command
{
    protected $signature = 'inscriptions:liberer-paiements-frais-masques
        {--apply : Écrire les changements (sans ce drapeau, simple simulation)}';

    protected $description = 'Libère en avance les paiements restés accrochés à un frais masqué (avant le correctif du 31/08/2026)';

    public function __construct(private readonly ConvertirEncaissementsEnAvance $convertirEnAvance)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        // Les frais masqués qui portent encore de l'argent non remboursé.
        $fees = InscriptionFee::query()
            ->whereNotNull('masque_le')
            ->whereHas('encaissements', fn ($q) => $q->whereDoesntHave('remboursements'))
            ->with('inscription.student')
            ->orderBy('inscription_id')
            ->get();

        if ($fees->isEmpty()) {
            $this->info('Aucun paiement accroché à un frais masqué. Rien à faire.');

            return self::SUCCESS;
        }

        $totalLibere = 0.0;
        $lignes = 0;
        $ignores = 0;

        foreach ($fees as $fee) {
            $inscription = $fee->inscription;

            if ($inscription === null) {
                $this->warn("  ignoré : frais #{$fee->id} sans inscription");
                $ignores++;

                continue;
            }

            $encaissements = Encaissement::query()
                ->where('inscription_fee_id', $fee->id)
                ->whereDoesntHave('remboursements')
                ->get();

            if ($encaissements->isEmpty()) {
                continue;
            }

            $montant = round((float) $encaissements->sum('montant'), 2);
            $etudiant = trim(($inscription->student?->prenom ?? '').' '.($inscription->student?->nom ?? ''));

            $this->line(sprintf(
                '  %s — %s (%s) : %s DH sur %d encaissement(s)',
                $inscription->reference,
                $fee->nom,
                $etudiant !== '' ? $etudiant : 'étudiant inconnu',
                number_format($montant, 2, '.', ' '),
                $encaissements->count(),
            ));

            if ($apply) {
                $this->convertirEnAvance->handle($inscription, $encaissements->pluck('id')->all());
            }

            $totalLibere += $montant;
            $lignes++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d ligne(s) de frais, %s DH rendus disponibles en avance.',
            $apply ? 'Appliqué' : 'Simulation (aucune écriture)',
            $lignes,
            number_format($totalLibere, 2, '.', ' '),
        ));

        if ($ignores > 0) {
            $this->warn("{$ignores} ligne(s) ignorée(s) — voir ci-dessus.");
        }

        if (! $apply) {
            $this->comment('Relancer avec --apply pour écrire.');
        }

        return self::SUCCESS;
    }
}
