<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Etablissement;
use App\Models\Encaissement;
use App\Models\Remboursement;
use Illuminate\Database\Eloquent\Builder;

/**
 * La part d'un CENTRE dans une caisse — source unique pour tous les écrans
 * finance (04/09/2026).
 *
 * Pourquoi cette classe existe : une caissière n'a qu'UNE caisse à vie
 * (CLAUDE.md §11, `caisses_une_caissiere_par_employe`, multi-caisses REJETÉ le
 * 01/09/2026) mais encaisse pour plusieurs centres. La caisse d'Hafssa
 * Elkhattabi est étiquetée GLS Rabat et porte pourtant des paiements GLS
 * Online. Trois écrans filtraient sur `caisses.etablissement_id`, ce qui fait
 * basculer la caisse EN BLOC : Rabat annonçait 112 550 DH encaissés et
 * 103 900 DH de solde — dont une part appartenant à Online — pendant qu'Online
 * n'en voyait rien.
 *
 * ⚠ Deux règles, apprises en corrigeant ce bug trois fois :
 *
 * 1. **Un total se calcule sur les MÊMES colonnes que les lignes qu'il
 *    chapeaute.** Une première version dérivait le solde du ledger
 *    (`properties->etablissement_id`) pendant que les lignes filtraient sur
 *    `encaissements.etablissement_id`. Sur la caisse #10, aucune des 154
 *    écritures ne portait le centre 7 alors que les encaissements le
 *    portaient : l'écran affichait « 2 transactions, 500 DH » au-dessus d'un
 *    solde à 0,00 DH. Deux moitiés d'écran qui lisent deux sources finiront
 *    toujours par se contredire.
 *
 * 2. **`caisses.solde` reste l'autorité.** La somme des parts de tous les
 *    centres doit y retomber ; les parts sont dérivées, jamais stockées.
 *
 * Sans centre actif (« Tous les centres », super-admin), rien n'est ventilé :
 * le solde stocké est rendu tel quel et sa somme est le total réseau.
 */
final class VentilationCentre
{
    /**
     * Part du centre dans le solde d'une caisse.
     *
     * @param  int|null  $centreId  null = « Tous les centres » ⇒ solde entier
     */
    public function soldeDuCentre(Caisse $caisse, ?int $centreId): float
    {
        if ($centreId === null) {
            return (float) $caisse->solde;
        }

        $entrees = $this->encaissementsDuCentre($caisse->id, $centreId);

        $sorties = (float) Remboursement::query()
            ->where('caisse_id', $caisse->id)
            ->where('etablissement_id', $centreId)
            ->sum('montant');

        $sorties += $this->depensesDuCentre($caisse->id, $centreId);
        $sorties += $this->transfertsDuCentre($caisse, $centreId);

        return round($entrees - $sorties, 2);
    }

    /**
     * Montant que le centre actif peut réellement SORTIR de ce tiroir.
     *
     * = part ventilée du centre + part du solde qu'AUCUN centre ne revendique.
     *
     * La seconde moitié est indispensable : `caisses.solde` est l'autorité
     * (CaisseLedger) et peut dépasser la somme des parts ventilables — solde
     * d'ouverture, reprise de l'ancien CRM, correction directe (18,4 M DH sont
     * dans ce cas au 09/09/2026). Cet argent existe physiquement dans le
     * tiroir : le refuser au transfert le gèlerait définitivement, ce qui
     * serait un bug pire que celui corrigé ici. On ne réserve donc que ce qui
     * est PROUVÉ appartenir à un autre centre.
     *
     * Source unique du plafond serveur (DemanderTransfertCaisse) ET du montant
     * affiché dans le modal : deux calculs séparés finiraient par proposer un
     * plafond que le serveur refuse ensuite.
     */
    public function plafondTransfert(Caisse $caisse, ?int $centreId): float
    {
        if ($centreId === null) {
            return (float) $caisse->solde;
        }

        $reserveAilleurs = 0.0;

        foreach (Etablissement::query()->whereKeyNot($centreId)->pluck('id') as $autreId) {
            // Une part négative (plus sorti qu'entré sur un centre) ne réserve
            // rien : max(0, …).
            $reserveAilleurs += max(0.0, $this->soldeDuCentre($caisse, (int) $autreId));
        }

        return round(max(
            $this->soldeDuCentre($caisse, $centreId),
            (float) $caisse->solde - $reserveAilleurs,
        ), 2);
    }

    /** Encaissements espèces du centre logés dans cette caisse. */
    public function encaissementsDuCentre(int $caisseId, int $centreId): float
    {
        return (float) Encaissement::query()
            ->where('caisse_id', $caisseId)
            ->whereNull('applied_from_encaissement_id')
            ->where('etablissement_id', $centreId)
            ->sum('montant');
    }

    /** Dépenses APPROUVÉES du centre réglées depuis cette caisse. */
    public function depensesDuCentre(int $caisseId, int $centreId): float
    {
        return (float) Depense::query()
            ->where('caisse_id', $caisseId)
            ->where('statut', Depense::STATUT_APPROUVEE)
            ->tap(fn ($q) => $this->scopeDepensesAuCentre($q, $centreId))
            ->sum('montant');
    }

    /**
     * Solde net des transferts validés touchant cette caisse.
     *
     * Un transfert ne porte aucune dimension centre propre : il déplace de
     * l'argent PHYSIQUE entre deux caisses, donc il est imputé au centre de
     * rattachement de la caisse concernée. Les ignorer ferait diverger la
     * somme des parts du solde stocké.
     */
    private function transfertsDuCentre(Caisse $caisse, int $centreId): float
    {
        $net = 0.0;

        foreach (CaisseTransfer::query()
            ->where(fn ($q) => $q->where('caisse_source_id', $caisse->id)->orWhere('caisse_destination_id', $caisse->id))
            ->where('statut', CaisseTransfer::STATUT_VALIDE)
            ->get(['caisse_source_id', 'montant', 'etablissement_id']) as $transfert) {
            $sortant = (int) $transfert->caisse_source_id === $caisse->id;

            // ⚠ Le centre d'une SORTIE est celui du TRANSFERT (le centre où le
            // caissier travaillait), pas celui de rattachement de la caisse
            // (09/09/2026). L'ancienne version écartait le transfert dès que
            // les deux différaient : 1 300,00 DH encaissés à Casablanca puis
            // transférés laissaient Casablanca à 1 300,00 DH au lieu de 0,00 DH,
            // le -1 300 étant imputé à Kénitra où il n'avait jamais été.
            // Une ENTRÉE reste imputée au centre de la caisse qui reçoit : les
            // billets rejoignent ce tiroir, et son centre de rattachement est
            // la seule chose qu'on sache d'eux à l'arrivée.
            // Repli sur le centre de la caisse quand la colonne est absente
            // (transferts antérieurs — jamais de backfill, §11).
            $centreDuMouvement = $sortant
                ? ($transfert->etablissement_id ?? $caisse->etablissement_id)
                : $caisse->etablissement_id;

            if ((int) $centreDuMouvement !== $centreId) {
                continue;
            }

            $net += $sortant ? (float) $transfert->montant : -(float) $transfert->montant;
        }

        return $net;
    }

    /**
     * Restreint une requête de dépenses au centre donné.
     *
     * `depenses` ne porte PAS de colonne `etablissement_id` (vérifié en base
     * le 04/09/2026) : son centre est celui du GROUPE pour un « Paiement
     * prof », sinon celui de la caisse qui a payé — la même résolution que la
     * colonne Centre du journal, pour que les écrans ne divergent pas.
     *
     * @param  Builder<Depense>  $query
     */
    public function scopeDepensesAuCentre(Builder $query, int $centreId): void
    {
        $query->where(fn ($q) => $q
            ->whereHas('group', fn ($g) => $g->where('etablissement_id', $centreId))
            ->orWhere(fn ($w) => $w
                ->whereDoesntHave('group')
                ->whereHas('caisse', fn ($c) => $c->where('etablissement_id', $centreId))));
    }

    /**
     * Une caisse appartient-elle à l'écran d'un centre ?
     *
     * Trois façons, et il faut les trois — chacune a été le correctif d'un
     * bug signalé :
     *  1. la caisse est rattachée à ce centre ;
     *  2. son responsable y est AFFECTÉ (« Centres affectés » est la source
     *     de vérité de la portée, §16 — jamais le seul centre primaire) ;
     *  3. elle porte de l'argent de ce centre (filet pour une employée mutée
     *     ou partie : sinon son argent devient intransférable depuis le
     *     centre qui le possède).
     *
     * Une caisse sans centre (coffre « Externe ») est globale et reste
     * visible partout, comme sur tous les écrans finance.
     *
     * @param  Builder<Caisse>  $query
     */
    public function scopeCaissesDuCentre(Builder $query, ?int $centreId): void
    {
        if ($centreId === null) {
            return;
        }

        $query->where(fn ($w) => $w
            ->whereNull('etablissement_id')
            ->orWhere('etablissement_id', $centreId)
            // ⚠ withoutGlobalScopes() : Employee est
            // #[ScopedBy(HiddenAccountScope::class)] et un global scope
            // s'applique aussi dans un whereHas imbriqué (§11), ce qui
            // rétrécirait silencieusement l'ensemble que cette clause définit.
            // La caisse du mainteneur reste écartée par HiddenAccount, en
            // amont de la chaîne.
            ->orWhereHas('responsable', fn ($r) => $r
                ->withoutGlobalScopes()
                ->where(fn ($e) => $e
                    // La colonne PRIMAIRE fait partie de la réponse, pas d'un
                    // raccourci : CenterAccessService la tient pour
                    // autoritaire tant qu'aucune ligne de pivot n'existe
                    // (§16), et le personnel créé avant le pivot est
                    // exactement dans ce cas.
                    ->where('etablissement_id', $centreId)
                    ->orWhereHas('etablissements', fn ($p) => $p->where('etablissements.id', $centreId))))
            ->orWhereHas('encaissements', fn ($e) => $e->where('etablissement_id', $centreId))
            ->orWhereHas('remboursements', fn ($r) => $r->where('etablissement_id', $centreId)));
    }
}
