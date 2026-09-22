<?php

declare(strict_types=1);

namespace App\Domain\Payroll\DTOs;

/**
 * Résultat complet d'un calcul « Paiement prof » : le total, et TOUT ce qui
 * permet de le refaire à la main.
 *
 *     montant par séance = taux ÷ séances rémunérées (plafond 22)
 *     montant étudiant   = montant par séance × ses présences
 *
 * ⚠ Ce total n'est pas un paiement. Rien n'est écrit, aucune caisse n'est
 * touchée : c'est une proposition de montant que l'utilisateur relit avant
 * d'enregistrer la dépense par le chemin ordinaire (§11).
 */
final readonly class ResultatPaiementProf
{
    /**
     * @param  list<LignePaiementProf>  $lignes
     * @param  int  $nombreSeances      séances réellement effectuées
     * @param  int  $seancesRemunerees  diviseur retenu (le réel, plafonné à 22)
     */
    public function __construct(
        public array $lignes,
        public float $total,
        public float $montantParEtudiant,
        public float $montantParSeance,
        public int $nombreSeances,
        public int $seancesRemunerees,
        public int $etudiantsRemunerateurs,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lignes' => array_map(static fn (LignePaiementProf $l): array => $l->toArray(), $this->lignes),
            'total' => $this->total,
            'montantParEtudiant' => $this->montantParEtudiant,
            'montantParSeance' => $this->montantParSeance,
            'nombreSeances' => $this->nombreSeances,
            'seancesRemunerees' => $this->seancesRemunerees,
            'etudiantsRemunerateurs' => $this->etudiantsRemunerateurs,
        ];
    }
}
