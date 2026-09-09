<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Activity;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Annule une dépense APPROUVÉE par ÉCRITURE COMPENSATOIRE : la caisse qui
 * avait été débitée est recréditée du même montant, dans la même
 * transaction, la ligne passe « Annulée » et sa note explique pourquoi.
 *
 * La dépense n'est JAMAIS supprimée (§11 : les enregistrements monétaires
 * sont append-only) et le mouvement d'origine n'est jamais réécrit : le
 * journal montre la sortie, puis l'entrée qui la compense, chacune avec son
 * solde avant/après. Même mécanisme que AnnulerRemboursement.
 *
 * Écrit le 09/09/2026 pour DEP-014 — une dépense de 7 650 DH saisie deux
 * fois à onze minutes d'intervalle (DEP-012 / DEP-014, même montant, même
 * date, même description « * »), approuvée deux fois, qui a laissé la caisse
 * d'El Mehdi Bakhach à -7 250 DH. Aucun écran n'appelle cette action : elle
 * est exécutée par la commande `depenses:annuler-doublon`, qui vérifie les
 * preuves du doublon avant de l'invoquer.
 *
 * Idempotence : la ligne est relue sous verrou et refusée si elle est déjà
 * « Annulée » ; une dépense « En attente » ou « Refusée » est refusée aussi —
 * elle n'a jamais débité la caisse, il n'y a rien à rendre.
 *
 * Centre : le crédit est estampillé du centre porté par le DÉBIT d'origine
 * dans le journal (le contexte financier que l'on inverse), à défaut celui
 * de la caisse — jamais le contexte actif de l'opérateur.
 */
final class AnnulerDepense
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    /**
     * @param  string  $correction  référence STABLE de la correction, écrite
     *                              dans le journal et dans la note (ex.
     *                              CORRECTION-DEP-014-DUPLICATE) — c'est ce
     *                              qui permet de retrouver l'écriture et de
     *                              prouver qu'elle n'existe qu'une fois
     * @param  string  $motif       phrase française lue par un humain
     */
    public function handle(Depense $depense, string $correction, string $motif, ?Employee $annuleePar = null): Depense
    {
        return DB::transaction(function () use ($depense, $correction, $motif, $annuleePar): Depense {
            /** @var Depense $verrouillee */
            $verrouillee = Depense::query()
                ->whereKey($depense->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouillee->isAnnulee()) {
                throw ValidationException::withMessages([
                    'statut' => __('This expense has already been cancelled.'),
                ]);
            }

            if (! $verrouillee->isApprouvee()) {
                throw ValidationException::withMessages([
                    'statut' => __('Only an approved expense can be cancelled — a pending or refused one never debited the till.'),
                ]);
            }

            // Ceinture et bretelles : même si le statut avait été touché à la
            // main, une correction portant cette référence ne s'écrit qu'une
            // fois dans le journal.
            if (self::correctionExiste($verrouillee, $correction)) {
                throw ValidationException::withMessages([
                    'statut' => __('This expense has already been cancelled.'),
                ]);
            }

            $montant = (float) $verrouillee->montant;
            $caisseId = (int) $verrouillee->caisse_id;

            $this->ledger->credit(
                $caisseId,
                $montant,
                "Annulation de la dépense {$verrouillee->reference}",
                $verrouillee,
                [
                    'type_depense_id' => $verrouillee->type_depense_id,
                    'methode' => $verrouillee->methode_paiement,
                    'etablissement_id' => self::centreDuDebitOrigine($verrouillee)
                        ?? Caisse::query()->whereKey($caisseId)->value('etablissement_id'),
                    'correction' => $correction,
                    'motif_correction' => $motif,
                    'annulee_par' => $annuleePar?->nomComplet(),
                ],
            );

            $note = trim((string) $verrouillee->note);
            $suffixe = Depense::MARQUEUR_ANNULE.' le '.now()->format('d/m/Y')
                .' — '.$correction.' : '.rtrim($motif, '.')
                .', caisse recréditée de '.number_format($montant, 2, ',', ' ').' DH.';

            $verrouillee->update([
                'statut' => Depense::STATUT_ANNULEE,
                'note' => $note === '' ? $suffixe : $note."\n".$suffixe,
            ]);

            return $verrouillee;
        });
    }

    /** Le journal porte-t-il déjà l'écriture compensatoire de cette référence ? */
    public static function correctionExiste(Depense $depense, string $correction): bool
    {
        return self::mouvements($depense)
            ->where('properties->correction', $correction)
            ->exists();
    }

    /**
     * Le centre estampillé sur le débit d'origine (« Sortie ») de cette
     * dépense — null pour une entrée antérieure à la dimension centre.
     */
    private static function centreDuDebitOrigine(Depense $depense): ?int
    {
        $etab = self::mouvements($depense)
            ->where('properties->sens', 'Sortie')
            ->orderBy('id')
            ->value('properties->etablissement_id');

        return $etab === null || $etab === '' ? null : (int) $etab;
    }

    /**
     * Les mouvements de caisse dont cette dépense est l'origine.
     *
     * `origine_id` est stocké en NOMBRE jsonb, et la comparaison se fait ici
     * avec une CHAÎNE — c'est correct : Laravel compile
     * `where('properties->origine_id', …)` vers l'opérateur d'extraction
     * TEXTE de PostgreSQL (`properties->>'origine_id' = ?`), qui rend
     * « 14 » quel que soit le type jsonb sous-jacent. Vérifié le 09/09/2026
     * sur `gls_crm` (4 lignes retrouvées via `->>`, 0 via `->`). Ne pas
     * « corriger » ceci en filtrant côté PHP : le journal de production
     * compte 23 812 mouvements, les charger tous serait un incident de
     * performance pour une requête qui vise UNE ligne.
     */
    private static function mouvements(Depense $depense): Builder
    {
        return Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->where('properties->origine_type', Depense::class)
            ->where('properties->origine_id', (string) $depense->getKey());
    }
}
