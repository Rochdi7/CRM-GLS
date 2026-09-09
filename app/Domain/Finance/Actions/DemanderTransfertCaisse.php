<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Services\Context\CurrentContext;
use Illuminate\Validation\ValidationException;

/**
 * REQUEST step of the two-step till transfer (structure doc §7).
 * Captures the *_avant balance snapshots; caisses.solde is NOT touched —
 * a requested-but-unapproved transfer must not move real money.
 *
 * ⚠ Depuis le 09/09/2026 le transfert porte le CENTRE ACTIF du caissier et
 * ne peut emporter que l'argent de CE centre — voir handle().
 */
final class DemanderTransfertCaisse
{
    public function __construct(
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /**
     * @param array<string, mixed> $data validated StoreCaisseTransferRequest data
     */
    public function handle(array $data, Employee $requestedBy): CaisseTransfer
    {
        // A transfer is PHYSICAL cash changing hands: both ends must be cash
        // accounts (Caissière / Externe). The centre's TPE/Chèque/Virement
        // accounts have no owner to validate a receipt and hold no banknotes
        // — money reaches them only through a payment's method.
        foreach (['caisse_source_id', 'caisse_destination_id'] as $field) {
            $caisse = Caisse::query()->findOrFail((int) $data[$field]);

            if (! $caisse->isEspeces()) {
                throw ValidationException::withMessages([
                    $field => __('A till transfer can only move cash between cash accounts.'),
                ]);
            }
        }

        // ⚠ Le transfert appartient au CENTRE ACTIF du caissier, jamais au
        // centre de rattachement de sa caisse (09/09/2026). Une caisse n'a
        // qu'UN rattachement mais encaisse pour plusieurs centres (§11) : sans
        // cette colonne la ventilation retombait sur
        // `caisses.etablissement_id` et imputait la sortie au mauvais centre —
        // 1 300,00 DH encaissés à Casablanca puis transférés en sortaient
        // comptablement à Kénitra, laissant Casablanca à 1 300,00 DH au lieu
        // de 0,00 DH.
        $centreId = $this->context->etablissementId();

        // ⚠ On ne transfère que l'argent DU CENTRE ACTIF. Le tiroir est
        // physiquement unique, mais chaque centre doit pouvoir solder le sien
        // sans emporter celui d'un autre : autrement un caissier vide
        // Casablanca en puisant dans l'argent de Kénitra, et la part d'un
        // centre peut devenir négative — ce qu'aucune caisse ne peut tenir.
        // Sur « Tous les centres » (super-admin) rien n'est ventilé : le
        // tiroir entier reste disponible, comme avant.
        //
        // ⚠ Le plafond est la part du centre, MAIS jamais moins que la part
        // NON ATTRIBUABLE du tiroir. Un solde peut exister sans mouvement
        // ventilable derrière lui — solde d'ouverture, reprise de l'ancien
        // CRM, correction directe : 18,4 M DH sont dans ce cas au 09/09/2026.
        // Plafonner sur la seule part ventilée rendrait cet argent
        // INTRANSFÉRABLE à vie, ce qui serait un bug bien pire que celui
        // corrigé ici. On ne bloque donc que ce qu'on sait appartenir à un
        // AUTRE centre — jamais ce qu'on ne sait pas rattacher.
        if ($centreId !== null) {
            $source = Caisse::query()->findOrFail((int) $data['caisse_source_id']);
            $disponible = $this->ventilation->plafondTransfert($source, $centreId);

            if ((float) $data['montant'] > $disponible) {
                throw ValidationException::withMessages([
                    'montant' => __(
                        'This center only holds :solde MAD in this till — the rest belongs to other centers.',
                        ['solde' => number_format($disponible, 2, ',', ' ')],
                    ),
                ]);
            }
        }

        return CaisseTransfer::create([
            ...$data,
            'reference' => ReferenceGenerator::make('TRF', 'caisse_transfers'),
            'date_transfert' => now(),
            'etablissement_id' => $centreId,
            'solde_source_avant' => Caisse::query()->whereKey($data['caisse_source_id'])->value('solde'),
            'solde_dest_avant' => Caisse::query()->whereKey($data['caisse_destination_id'])->value('solde'),
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'requested_by' => $requestedBy->id,
        ]);
    }
}
