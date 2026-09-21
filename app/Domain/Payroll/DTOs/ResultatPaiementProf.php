<?php

declare(strict_types=1);

namespace App\Domain\Payroll\DTOs;

/**
 * Résultat complet d'un calcul « Paiement prof » : le total, et TOUT ce qui
 * permet de le refaire à la main.
 *
 * ⚠ Ce total n'est pas un paiement. Rien n'est écrit, aucune caisse n'est
 * touchée : c'est une proposition de montant que l'utilisateur relit avant
 * d'enregistrer la dépense par le chemin ordinaire (§11).
 */
final readonly class ResultatPaiementProf
{
    /**
     * @param  list<LignePaiementProf>  $lignes
     * @param  array<string, int>       $decoupageSemaines  semaine ISO => bucket 1..4
     * @param  list<int>                $bucketsOccupes     semaines ayant reçu des cours
     */
    public function __construct(
        public array $lignes,
        public float $total,
        public float $montantParEtudiant,
        public float $montantSemaine,
        public int $seuil,
        public int $nombreJoursDeCours,
        public int $semainesQualifiees,
        public int $etudiantsRemunerateurs,
        public array $decoupageSemaines,
        public array $bucketsOccupes = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lignes' => array_map(static fn (LignePaiementProf $l): array => $l->toArray(), $this->lignes),
            'total' => $this->total,
            'montantParEtudiant' => $this->montantParEtudiant,
            'montantSemaine' => $this->montantSemaine,
            'seuil' => $this->seuil,
            'nombreJoursDeCours' => $this->nombreJoursDeCours,
            'semainesQualifiees' => $this->semainesQualifiees,
            'etudiantsRemunerateurs' => $this->etudiantsRemunerateurs,
            'decoupageSemaines' => $this->decoupageSemaines,
            'bucketsOccupes' => $this->bucketsOccupes,
        ];
    }
}
