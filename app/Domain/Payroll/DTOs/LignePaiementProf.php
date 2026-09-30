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
     * @param  string|null $inscriptionStatut statut du dossier dans le groupe (null = appelé sans y être inscrit)
     * @param  int         $joursRetenus  présences — la SEULE donnée qui paie
     * @param  int         $joursIgnores  « Retard » / « Justifié » hérités, ne rapportent rien
     * @param  int         $semainesPayees palier atteint : 0, 1, 2 ou 4 (= complet)
     * @param  float|null  $montantAjuste ajustement manuel, s'il y en a un
     */
    public function __construct(
        public int $studentId,
        public string $nom,
        public ?string $inscriptionStatut,
        public int $joursRetenus,
        public int $joursAbsents,
        public int $joursIgnores,
        public int $semainesPayees,
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
            'inscriptionStatut' => $this->inscriptionStatut,
            'joursRetenus' => $this->joursRetenus,
            'joursAbsents' => $this->joursAbsents,
            'joursIgnores' => $this->joursIgnores,
            'semainesPayees' => $this->semainesPayees,
            'montantAuto' => $this->montantAuto,
            'montantAjuste' => $this->montantAjuste,
            'montantEffectif' => $this->montantEffectif,
        ];
    }
}
