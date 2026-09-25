<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Support\StatutChequeSolde;
use App\Models\Cheque;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattrapage ponctuel de la règle StatutChequeSolde (23/09/2026) : les
 * chèques « À déposer » déjà entièrement consommés AVANT que la règle
 * existe restent « En possession » / « Déposé » avec un reste de 0,00 DH.
 * Cette commande leur applique exactement ce que fait désormais un
 * paiement : passage en « Encaissé », journalisé, aucun argent ne bouge.
 *
 * Un chèque « Rejeté » n'est jamais touché. Dry-run par défaut — lire la
 * sortie avant `--apply`. Idempotent : un chèque passé « Encaissé » n'est
 * plus candidat.
 */
final class MarquerChequesEncaisses extends Command
{
    protected $signature = 'cheques:marquer-encaisses
        {--apply : Écrire les changements (sans ce drapeau, simple simulation)}';

    protected $description = 'Passe « Encaissé » les chèques « À déposer » dont le reste est à 0,00 DH';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $candidats = Cheque::query()
            ->where('type', Cheque::TYPE_A_DEPOSER)
            ->whereIn('statut', [Cheque::STATUT_EN_POSSESSION, Cheque::STATUT_DEPOSE])
            ->withSum('encaissements', 'montant')
            ->with('etablissement')
            ->orderBy('id')
            ->get()
            ->filter(fn (Cheque $c) => (float) $c->encaissements_sum_montant > 0
                && round((float) $c->montant - (float) $c->encaissements_sum_montant, 2) <= 0.0);

        if ($candidats->isEmpty()) {
            $this->info('Aucun chèque à passer « Encaissé ».');

            return self::SUCCESS;
        }

        $this->table(
            ['Réf.', 'N°', 'Centre', 'Statut', 'Montant', 'Utilisé'],
            $candidats->map(fn (Cheque $c) => [
                $c->reference,
                $c->numero_cheque,
                $c->etablissement?->nom_centre ?? '-',
                $c->statut,
                number_format((float) $c->montant, 2, ',', ' '),
                number_format((float) $c->encaissements_sum_montant, 2, ',', ' '),
            ])->all(),
        );

        if (! $apply) {
            $this->warn("Simulation : {$candidats->count()} chèque(s) seraient passés « Encaissé ». Relancer avec --apply.");

            return self::SUCCESS;
        }

        $faits = 0;
        foreach ($candidats as $candidat) {
            DB::transaction(function () use ($candidat, &$faits): void {
                $cheque = Cheque::query()->whereKey($candidat->id)->lockForUpdate()->firstOrFail();
                if (StatutChequeSolde::synchroniser($cheque)) {
                    $faits++;
                }
            });
        }

        $this->info("{$faits} chèque(s) passés « Encaissé ».");

        return self::SUCCESS;
    }
}
