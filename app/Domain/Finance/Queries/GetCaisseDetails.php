<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Domain\Finance\Support\VentilationCentre;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Services\Context\CurrentContext;

/**
 * Fiche d'une caisse — « Gestion de la caisse » → un compte.
 *
 * ⚠ Ventilée par centre actif comme les trois autres écrans de lecture
 * (`GetComptesCaisse`, `GetCaisseGlobale`, `GetCaisseJournal`), via la source
 * unique `Domain\Finance\Support\VentilationCentre` — corrigé le 09/09/2026.
 *
 * Cette page était la SEULE restée non ventilée : elle affichait le
 * `caisses.solde` ENTIER au-dessus de listes elles aussi entières. La caisse
 * de Yassine Ouled Laghzal, étiquetée GLS Kénitra, annonçait donc 72 740,00 DH
 * et listait des paiements GLS Casablanca / GLS Kénitra / GLS Online pêle-mêle,
 * pendant que l'onglet « Comptes de caisse » — ventilé, lui — donnait 1 300,00
 * DH pour le même compte sur le centre actif. Deux écrans du même argent qui
 * se contredisent : l'utilisateur ne peut plus savoir lequel croire.
 *
 * La règle de CLAUDE.md §11 s'applique ici comme ailleurs : un total se
 * calcule sur les MÊMES colonnes que les lignes qu'il chapeaute. Le solde
 * affiché est donc la part du centre actif, et les quatre listes ne montrent
 * que les mouvements de ce centre. Sur « Tous les centres » (super-admin),
 * rien n'est ventilé : le solde stocké est rendu tel quel — il reste
 * l'autorité (CaisseLedger), et la somme des parts y retombe.
 *
 * Les deux filtres de mouvement d'origine sont conservés, pour que la page
 * reconcilie avec le solde imprimé au-dessus :
 *  - une ligne d'application (`applied_from_encaissement_id`) ne fait que
 *    réallouer une avance déjà comptée une fois — `AppliquerAvance` ne crédite
 *    aucune caisse ;
 *  - une dépense en attente ou refusée n'a rien débité (flux d'approbation).
 *
 * Read-only — aucun create/update/delete dans cette classe.
 */
final class GetCaisseDetails
{
    public function __construct(
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Caisse $caisse): array
    {
        $caisse->loadMissing(['etablissement', 'responsable']);

        $centreId = $this->context->etablissementId();

        // `etablissement` eager-loaded for the Centre column: ONE till per
        // employee, but a cashier working across centres books payments in
        // each of them, so the till's own centre is not the payment's
        // (CLAUDE.md §11, « Centre dimension on the ledger »).
        $encaissements = $caisse->encaissements()
            ->whereNull('applied_from_encaissement_id')
            ->when($centreId !== null, fn ($q) => $q->where('etablissement_id', $centreId))
            ->with(['student', 'etablissement'])->latest('date_paiement')->limit(10)->get();

        // `depenses` ne porte pas de colonne centre : sa résolution (groupe
        // pour un « Paiement prof », sinon la caisse qui a payé) vit dans
        // VentilationCentre, partagée avec le journal et les comptes.
        $depenses = $caisse->depenses()
            ->where('statut', Depense::STATUT_APPROUVEE)
            ->when($centreId !== null, fn ($q) => $this->ventilation->scopeDepensesAuCentre($q, $centreId))
            ->with('typeDepense')->latest('date_depense')->limit(10)->get();

        $remboursements = $caisse->remboursements()
            ->when($centreId !== null, fn ($q) => $q->where('etablissement_id', $centreId))
            ->with('beneficiaire')->latest('date_remboursement')->limit(10)->get();

        // ⚠ Le filtrage se fait LIGNE PAR LIGNE, jamais en bloc (09/09/2026).
        // Une première version masquait TOUS les transferts dès que le centre
        // de rattachement de la caisse différait du centre actif : la caisse de
        // Yassine Ouled Laghzal (rattachée Kénitra) n'affichait donc AUCUN
        // transfert sur Casablanca, alors que la sortie de 1 300,00 DH
        // appartient bien à Casablanca — et la même ligne était pourtant
        // visible chez Maria, la destinataire. Un mouvement doit apparaître des
        // DEUX côtés, sinon l'historique d'une caisse ment par omission.
        //
        // Même résolution que VentilationCentre::transfertsDuCentre(), pour que
        // les lignes affichées soient exactement celles que le solde compte :
        //  - SORTIE  → le centre du TRANSFERT (là où le caissier travaillait),
        //    avec repli sur le centre de la caisse pour les lignes antérieures
        //    à la colonne (jamais de backfill, §11) ;
        //  - ENTRÉE  → le centre de la caisse qui reçoit : les billets
        //    rejoignent ce tiroir.
        $transfers = CaisseTransfer::query()
            ->with(['caisseSource', 'caisseDestination'])
            ->where(fn ($q) => $q
                ->where('caisse_source_id', $caisse->id)
                ->orWhere('caisse_destination_id', $caisse->id))
            ->latest('date_transfert')
            ->get()
            ->filter(function (CaisseTransfer $transfert) use ($caisse, $centreId): bool {
                if ($centreId === null) {
                    return true;
                }

                $centreDuMouvement = (int) $transfert->caisse_source_id === $caisse->id
                    ? ($transfert->etablissement_id ?? $caisse->etablissement_id)
                    : $caisse->etablissement_id;

                return (int) $centreDuMouvement === $centreId;
            })
            ->take(10)
            ->values();

        return [
            'id' => $caisse->id,
            'nom' => $caisse->nom,
            'centre' => $caisse->etablissement?->nom_centre,
            'responsable' => $caisse->responsable?->nomComplet(),
            // La part du centre actif, jamais le solde entier — c'est
            // exactement ce que les quatre listes ci-dessous montrent.
            'solde' => number_format($this->ventilation->soldeDuCentre($caisse, $centreId), 2, '.', ''),
            // La page montre-t-elle tout le compte, ou la part de ce centre ?
            // Sans ce drapeau le lecteur ne peut pas savoir laquelle des deux
            // valeurs il regarde (le bandeau de la page le dit).
            'ventileParCentre' => $centreId !== null,
            'statut' => $caisse->statut,
            'encaissements' => $encaissements->map(fn ($enc): array => [
                'reference' => $enc->reference,
                'label' => $enc->student?->nomComplet() ?? '—',
                'date' => $enc->date_paiement?->format('d/m/Y'),
                'montant' => number_format((float) $enc->montant, 2, '.', ''),
                'extra' => $enc->methode,
                // The payment's OWN centre — where the money was taken in,
                // which may differ from the till's centre.
                'centre' => $enc->etablissement?->nom_centre,
            ])->values()->all(),
            'depenses' => $depenses->map(fn ($dep): array => [
                'reference' => $dep->reference,
                'label' => $dep->typeDepense?->nom ?? '—',
                'date' => $dep->date_depense?->format('d/m/Y'),
                'montant' => number_format((float) $dep->montant, 2, '.', ''),
            ])->values()->all(),
            'remboursements' => $remboursements->map(fn ($rmb): array => [
                'reference' => $rmb->reference,
                'label' => $rmb->beneficiaire?->nomComplet() ?? '—',
                'date' => $rmb->date_remboursement?->format('d/m/Y'),
                'montant' => number_format((float) $rmb->montant, 2, '.', ''),
            ])->values()->all(),
            'transfers' => $transfers->map(function (CaisseTransfer $transfer) use ($caisse): array {
                $sortant = $transfer->caisse_source_id === $caisse->id;

                return [
                    'reference' => $transfer->reference,
                    'label' => $sortant ? ($transfer->caisseDestination?->nom ?? '—') : ($transfer->caisseSource?->nom ?? '—'),
                    'date' => $transfer->date_transfert?->format('d/m/Y'),
                    'montant' => number_format((float) $transfer->montant, 2, '.', ''),
                    'direction' => $sortant ? 'out' : 'in',
                    'statut' => $transfer->statut,
                ];
            })->values()->all(),
        ];
    }
}
