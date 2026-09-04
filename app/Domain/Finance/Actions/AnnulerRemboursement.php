<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Remboursement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Annule un remboursement déjà payé par ÉCRITURE COMPENSATOIRE : la caisse
 * qui avait été débitée est recréditée du même montant, dans la même
 * transaction, et la ligne est annotée.
 *
 * Le remboursement n'est JAMAIS supprimé (§11 : les enregistrements
 * monétaires sont append-only). Il reste listé, barré et badgé « Annulé »,
 * exclu des totaux et du journal de caisse — la trace explique pourquoi il a
 * existé, sans plus compter comme de l'argent sorti.
 *
 * Écrit après l'incident du 03/09/2026 (ELWARDI) : un remboursement saisi
 * deux fois n'avait aucun moyen d'être corrigé depuis l'écran, il a fallu
 * une commande artisan. Réservé au super-admin (`refunds.cancel`,
 * PermissionRegistry::superAdminOnly()) : recréditer une caisse est un
 * mouvement d'argent, pas une correction de libellé.
 */
final class AnnulerRemboursement
{
    public function __construct(
        private readonly CaisseLedger $ledger,
    ) {}

    public function handle(Remboursement $remboursement, ?string $motif = null): Remboursement
    {
        return DB::transaction(function () use ($remboursement, $motif): Remboursement {
            // Relu et VERROUILLÉ dans la transaction : deux clics sur
            // « Annuler » recréditeraient sinon la caisse deux fois pour une
            // seule sortie (même règle que tout contrôle « lire un solde puis
            // écrire », CLAUDE.md §11).
            /** @var Remboursement $verrouille */
            $verrouille = Remboursement::query()
                ->whereKey($remboursement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (Remboursement::estAnnule($verrouille)) {
                throw ValidationException::withMessages([
                    'remboursement' => __('This refund has already been cancelled.'),
                ]);
            }

            if ($verrouille->caisse_id === null) {
                throw ValidationException::withMessages([
                    'remboursement' => __('This refund has no till to credit back.'),
                ]);
            }

            $montant = (float) $verrouille->montant;

            // L'argent revient dans la caisse qui l'avait sorti — celle
            // stockée sur la ligne, jamais une caisse re-dérivée : c'est
            // exactement le mouvement inverse de celui qui a été journalisé.
            $this->ledger->credit(
                (int) $verrouille->caisse_id,
                $montant,
                "Annulation du remboursement {$verrouille->reference}",
                $verrouille,
                [
                    'beneficiaire_id' => $verrouille->beneficiaire_id,
                    'etablissement_id' => $verrouille->etablissement_id,
                    'correction' => 'annulation',
                ],
            );

            $note = trim((string) $verrouille->note);
            $suffixe = Remboursement::MARQUEUR_ANNULE.' le '.now()->format('d/m/Y')
                .' — caisse recréditée de '.number_format($montant, 2, ',', ' ').' DH.'
                .($motif !== null && trim($motif) !== '' ? ' Motif : '.trim($motif) : '');

            $verrouille->update([
                'note' => $note === '' ? $suffixe : $note."\n".$suffixe,
            ]);

            return $verrouille;
        });
    }
}
