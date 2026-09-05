<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
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
        if ((int) $caisse->etablissement_id !== $centreId) {
            return 0.0;
        }

        $net = 0.0;

        foreach (CaisseTransfer::query()
            ->where(fn ($q) => $q->where('caisse_source_id', $caisse->id)->orWhere('caisse_destination_id', $caisse->id))
            ->where('statut', CaisseTransfer::STATUT_VALIDE)
            ->get(['caisse_source_id', 'montant']) as $transfert) {
            $net += (int) $transfert->caisse_source_id === $caisse->id
                ? (float) $transfert->montant
                : -(float) $transfert->montant;
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
