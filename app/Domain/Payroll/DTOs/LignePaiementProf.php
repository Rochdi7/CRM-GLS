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
     * @param  int         $joursRetenus  présences — la SEULE donnée qui paie
     * @param  int         $joursIgnores  « Retard » / « Justifié » hérités, ne rapportent rien
     * @param  float|null  $montantAjuste ajustement manuel, s'il y en a un
     */
    public function __construct(
        public int $studentId,
        public string $nom,
        public int $joursRetenus,
        public int $joursAbsents,
        public int $joursIgnores,
        public float $montantAuto,
        public ?float $montantAjuste,
        public float $montantEffectif,
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
            'montantAuto' => $this->montantAuto,
            'montantAjuste' => $this->montantAjuste,
            'montantEffectif' => $this->montantEffectif,
        ];
    }
}
