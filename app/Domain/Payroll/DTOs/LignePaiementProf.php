<?php

declare(strict_types=1);

namespace App\Domain\Payroll\DTOs;

/**
 * Une ligne du calcul « Paiement prof » : ce qu'UN étudiant rapporte à
 * l'enseignant sur la période, et le détail qui l'explique.
 *
 * Le détail est transporté jusqu'à l'écran À DESSEIN : un montant de paie que
 * l'on ne peut pas justifier ligne par ligne n'est pas vérifiable par la
 * personne qui le signe.
 */
final readonly class LignePaiementProf
{
    /**
     * @param  array<int, int>    $joursParSemaine    bucket 1..4 => jours retenus
     * @param  array<int, float>  $montantsParSemaine bucket 1..4 => montant calculé
     * @param  float|null         $montantAjuste      ajustement manuel, s'il y en a un
     */
    public function __construct(
        public int $studentId,
        public string $nom,
        public int $joursRetenus,
        public int $joursAbsents,
        public int $joursIgnores,
        public array $joursParSemaine,
        public array $montantsParSemaine,
        public float $montantAuto,
        public ?float $montantAjuste,
        public float $montantEffectif,
        public bool $qualifie,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'studentId' => $this->studentId,
            'nom' => $this->nom,
            'joursRetenus' => $this->joursRetenus,
            'joursAbsents' => $this->joursAbsents,
            'joursIgnores' => $this->joursIgnores,
            'joursParSemaine' => $this->joursParSemaine,
            'montantsParSemaine' => $this->montantsParSemaine,
            'montantAuto' => $this->montantAuto,
            'montantAjuste' => $this->montantAjuste,
            'montantEffectif' => $this->montantEffectif,
            'qualifie' => $this->qualifie,
        ];
    }
}
