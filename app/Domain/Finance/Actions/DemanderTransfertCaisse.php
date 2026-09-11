<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Support\GardeSoldeCaisse;
use App\Domain\Finance\Support\ReservationTransferts;
use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Facades\DB;
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
        private readonly ReservationTransferts $reservations,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated StoreCaisseTransferRequest data
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

        // ⚠ La colonne n'est JAMAIS laissée NULL (11/09/2026). Sur « Tous les
        // centres » (super-admin), `etablissementId()` ne résout rien : les
        // 37 transferts d'avant cette date sont dans ce cas, et la
        // ventilation les replie alors sur `caisses.etablissement_id` À
        // CHAQUE LECTURE. Ce repli tombe juste tant qu'un tiroir ne sert
        // qu'un centre — puis se trompe. Cas réel : la caisse d'Ahmed
        // Khadimerrahman (rattachée à GLS Online) encaisse 500 DH pour Online
        // et 1 800 DH pour Kénitra ; TRF-028 sort les 1 800 DH de Kénitra
        // mais en débite Online, qui affiche -1 800,00 DH pendant que Kénitra
        // garde un argent qui n'y est plus.
        //
        // On FIGE donc la même valeur à l'écriture plutôt que de la
        // re-dériver : le comportement est identique, mais l'imputation
        // devient une donnée écrite une fois, auditée et stable — au lieu
        // d'un résultat qui change si la caisse est un jour re-rattachée.
        // Refuser le transfert serait plus strict, mais bloquerait un geste
        // légitime du super-admin depuis la vue réseau.
        // ⚠ Le PLAFOND reste conditionné au centre ACTIF, pas au repli : sur
        // « Tous les centres » rien n'est ventilé et le tiroir entier reste
        // disponible, comme avant. Appliquer le plafond au centre replié
        // changerait une règle métier au passage.
        $centreActif = $centreId;

        $centreId ??= Caisse::query()
            ->whereKey((int) $data['caisse_source_id'])
            ->value('etablissement_id');

        // ⚠ On ne transfère que l'argent DU CENTRE ACTIF. Le tiroir est
        // physiquement unique, mais chaque centre doit pouvoir solder le sien
        // sans emporter celui d'un autre : autrement un caissier vide
        // Casablanca en puisant dans l'argent de Kénitra, et la part d'un
        // centre peut devenir négative — ce qu'aucune caisse ne peut tenir.
        //
        // ⚠ Le plafond est la part du centre, MAIS jamais moins que la part
        // NON ATTRIBUABLE du tiroir. Un solde peut exister sans mouvement
        // ventilable derrière lui — solde d'ouverture, reprise de l'ancien
        // CRM, correction directe : 18,4 M DH sont dans ce cas au 09/09/2026.
        // Plafonner sur la seule part ventilée rendrait cet argent
        // INTRANSFÉRABLE à vie, ce qui serait un bug bien pire que celui
        // corrigé ici. On ne bloque donc que ce qu'on sait appartenir à un
        // AUTRE centre — jamais ce qu'on ne sait pas rattacher.
        //
        // ⚠ Et une demande RÉSERVE (11/09/2026) : le plafond déduit les
        // transferts déjà « En attente » sur ce tiroir (ReservationTransferts),
        // et sur « Tous les centres » — où rien n'est ventilé — le tiroir
        // physique moins ce qui est réservé reste la borne. Lu sous verrou
        // FOR UPDATE dans une transaction : deux demandes simultanées sont
        // sérialisées, la seconde relit un plafond déjà diminué (§11).
        return DB::transaction(function () use ($data, $requestedBy, $centreId, $centreActif): CaisseTransfer {
            $source = Caisse::query()->whereKey((int) $data['caisse_source_id'])->lockForUpdate()->firstOrFail();
            $disponible = $this->ventilation->plafondTransfert($source, $centreActif);

            if ((float) $data['montant'] > $disponible) {
                $message = $centreActif !== null
                    ? __(
                        'This center only holds :solde MAD in this till — the rest belongs to other centers.',
                        ['solde' => number_format(max(0.0, $disponible), 2, ',', ' ')],
                    )
                    : __(
                        'The till only has :solde MAD available for a transfer.',
                        ['solde' => number_format(max(0.0, $disponible), 2, ',', ' ')],
                    );

                $reserve = $this->reservations->enAttente($source->id);

                if ($reserve > 0) {
                    $message .= ' '.GardeSoldeCaisse::messageReserve((float) $source->solde, $reserve, $this->reservations->references($source->id));
                }

                throw ValidationException::withMessages(['montant' => $message]);
            }

            return CaisseTransfer::create([
                ...$data,
                'reference' => ReferenceGenerator::make('TRF', 'caisse_transfers'),
                'date_transfert' => now(),
                'etablissement_id' => $centreId,
                'solde_source_avant' => $source->solde,
                'solde_dest_avant' => Caisse::query()->whereKey($data['caisse_destination_id'])->value('solde'),
                'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
                'requested_by' => $requestedBy->id,
            ]);
        });
    }
}
