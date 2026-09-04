<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Encaissement;
use App\Models\InscriptionFee;

/**
 * « Payé / Reste » d'un reçu (04/09/2026) — la SEULE définition de la
 * situation du frais telle qu'elle est imprimée, et donc partagée par les
 * QUATRE reçus : imprimé (recu.blade), PDF/WhatsApp (recu-pdf.blade),
 * l'email récapitulatif (recu-email.blade) et le reçu groupé.
 *
 * Le reçu disait seulement « Montant : 500 DH » ; l'étudiant qui règle 500 DH
 * sur 1 500 DH repartait sans savoir ce qu'il doit encore. On ajoute donc
 * trois lignes en dessous du montant :
 *
 *     Total du frais    → InscriptionFee::montant (le dû, remise déduite)
 *     Total payé        → SOMME de TOUS les encaissements du frais, celui-ci
 *                         compris — pas seulement la ligne du reçu
 *     Reste à payer     → le solde, jamais négatif
 *
 * ⚠ « Total payé » est cumulatif, jamais `$encaissement->montant` : un reçu
 * de la 2ᵉ tranche doit lire 1 000 / 1 500, pas 500 / 1 500, sinon le
 * document contredit la caisse. Et le reste est calculé au niveau du FRAIS,
 * pas de l'inscription : c'est la ligne que l'étudiant vient de régler.
 *
 * Un encaissement peut couvrir PLUSIEURS frais (une avance appliquée à deux
 * lignes) : les totaux sont alors la somme des frais concernés, exactement
 * l'ensemble que `Encaissement::libelleFrais()` nomme — les deux lectures ne
 * peuvent pas se contredire.
 *
 * Une avance encore NON allouée n'a aucun frais : il n'y a alors ni dû ni
 * reste à afficher (`disponible === false`), et le reçu retombe sur la seule
 * ligne « Montant ». Afficher « Reste : 0 » sur une avance ferait croire que
 * la scolarité est soldée.
 *
 * ⚠ UNE requête agrégée pour les totaux, jamais `InscriptionFee::montantPaye()`
 * dans une boucle (read-models, CLAUDE.md §17).
 */
final class SituationFraisRecu
{
    private function __construct(
        public readonly bool $disponible,
        public readonly float $totalFrais,
        public readonly float $totalPaye,
        public readonly float $reste,
    ) {}

    /** Situation d'un reçu UNITAIRE — celui d'un frais, ou d'une avance appliquée. */
    public static function pour(Encaissement $encaissement): self
    {
        return self::pourFeeIds(self::feeIdsDe($encaissement));
    }

    /**
     * Faut-il encore imprimer la ligne « Montant » au-dessus du récapitulatif ?
     *
     * Sur un PREMIER versement, « Montant » et « Total payé » portent le même
     * chiffre : le reçu affichait alors 100 DH deux fois de suite, et la
     * répétition se lit comme une erreur de document (signalé le 04/09/2026).
     * On masque donc « Montant » quand il n'apprend rien.
     *
     * ⚠ Il RÉAPPARAÎT dès qu'il diffère — 2ᵉ tranche, avance partiellement
     * appliquée, reçu groupé : c'est alors la seule ligne qui dit ce qui a été
     * remis AUJOURD'HUI, par opposition au cumul. Ne jamais le retirer
     * inconditionnellement : un reçu doit toujours pouvoir énoncer la somme
     * encaissée à sa date.
     *
     * Comparaison au centime (les deux valeurs sont arrondies à 2 décimales)
     * plutôt qu'en float brut.
     */
    public function montantRedondant(float $montantEncaisse): bool
    {
        return $this->disponible
            && abs(round($montantEncaisse, 2) - $this->totalPaye) < 0.005;
    }

    /**
     * Situation d'un reçu GROUPÉ : l'ensemble des frais couverts par le lot.
     *
     * @param  \Illuminate\Support\Collection<int, Encaissement>  $encaissements
     */
    public static function pourLot(\Illuminate\Support\Collection $encaissements): self
    {
        $feeIds = $encaissements
            ->flatMap(fn (Encaissement $e): array => self::feeIdsDe($e))
            ->unique()
            ->values()
            ->all();

        return self::pourFeeIds($feeIds);
    }

    /**
     * Les frais que CET argent a payés — même logique que
     * `Encaissement::libelleFrais()` : le frais direct, sinon ceux des lignes
     * d'application, en suivant la chaîne complète dès qu'une application a
     * été reconvertie (`ResoudreAllocationsAvance`).
     *
     * @return list<int>
     */
    private static function feeIdsDe(Encaissement $encaissement): array
    {
        if ($encaissement->inscription_fee_id !== null) {
            return [(int) $encaissement->inscription_fee_id];
        }

        $applications = $encaissement->applications;

        $ids = $applications->contains(fn (Encaissement $a): bool => $a->inscription_fee_id === null)
            ? collect(ResoudreAllocationsAvance::terminales([$encaissement->id])[$encaissement->id] ?? [])
                ->filter(fn (array $allocation): bool => $allocation['kind'] === ResoudreAllocationsAvance::KIND_FRAIS)
                ->map(fn (array $allocation): ?int => $allocation['row']->inscription_fee_id)
            : $applications->map(fn (Encaissement $a): ?int => $a->inscription_fee_id);

        return $ids->filter()->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }

    /** @param  list<int>  $feeIds */
    private static function pourFeeIds(array $feeIds): self
    {
        if ($feeIds === []) {
            return new self(false, 0.0, 0.0, 0.0);
        }

        $totalFrais = (float) InscriptionFee::query()->whereIn('id', $feeIds)->sum('montant');
        $totalPaye = (float) Encaissement::query()->whereIn('inscription_fee_id', $feeIds)->sum('montant');

        return new self(
            true,
            round($totalFrais, 2),
            round($totalPaye, 2),
            round(max(0.0, $totalFrais - $totalPaye), 2),
        );
    }
}
