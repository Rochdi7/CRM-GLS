<?php

declare(strict_types=1);

namespace App\Domain\Payroll\DTOs;

/**
 * Résultat complet d'un calcul « Paiement prof » : le total, et TOUT ce qui
 * permet de le refaire à la main.
 *
 *     < 5 présences → 0 · 5–6 → 1 semaine · 7–10 → 2 semaines · 11+ → complet
 *     une semaine = taux ÷ 4 (voir `CalculerPaiementProfParPaliers`)
 *
 * ⚠ Ce total n'est pas un paiement. Rien n'est écrit, aucune caisse n'est
 * touchée : c'est une proposition de montant que l'utilisateur relit avant
 * d'enregistrer la dépense par le chemin ordinaire (§11).
 */
final readonly class ResultatPaiementProf
{
    /**
     * @param  list<LignePaiementProf>  $lignes
     * @param  float  $montantParSemaine  taux ÷ 4 — ce que vaut UNE semaine payée
     * @param  int    $nombreSeances      séances réellement effectuées (affichage)
     */
    public function __construct(
        public array $lignes,
        public float $total,
        public float $montantParEtudiant,
        public float $montantParSemaine,
        public int $nombreSeances,
        public int $etudiantsRemunerateurs,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lignes' => array_map(static fn (LignePaiementProf $l): array => $l->toArray(), $this->lignes),
            'total' => $this->total,
            'montantParEtudiant' => $this->montantParEtudiant,
            'montantParSemaine' => $this->montantParSemaine,
            'nombreSeances' => $this->nombreSeances,
            'etudiantsRemunerateurs' => $this->etudiantsRemunerateurs,
        ];
    }
}
