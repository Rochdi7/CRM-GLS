<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Queries;

use App\Domain\Payroll\Support\MoisDeGroupe;
use App\Domain\Settings\Support\FraisEcheanceResolver;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;

/**
 * Vérification « par les paiements » du calcul Paiement prof — Système GLS
 * SEULEMENT (demande du 29/09/2026).
 *
 * Le calcul par défaut reste celui des SÉANCES (`taux ÷ séances × présences`).
 * Celui-ci en est un contrôle : il relit les « Détails paiement » du groupe
 * (la même donnée que la matrice `GetGroupPaymentMatrix`) sur le frais du
 * MOIS choisi — « Juillet 2026 » ⇒ « Frais de Juillet » — et paie le taux par
 * étudiant au prorata de ce que l'étudiant a réellement réglé sur ce frais :
 *
 *     montant étudiant = taux × min(1, payé net ÷ dû)
 *
 * Soldé ⇒ le taux entier ; partiel ⇒ la part ; rien payé ⇒ 0.
 *
 * Quatre bornes :
 *  1. le frais du mois est reconnu par son NOM (`FraisEcheanceResolver::
 *     moisFromNom`, la même règle qui en dérive l'échéance) parmi les frais
 *     du GROUPE ; aucun frais trouvé ⇒ `frais` vide, et l'écran le dit
 *     plutôt que d'afficher un total à 0 qui se lirait comme « personne n'a
 *     payé » ;
 *  2. payé = encaissé − remboursé (`avecPayeNet()` / `payeNet()`, §11) ;
 *  3. une ligne MASQUÉE n'est pas due et ne compte pas, comme dans la
 *     matrice ; un frais à 0 DH ne rapporte rien (aucun argent derrière) ;
 *  4. deux requêtes quel que soit le nombre d'étudiants (§17).
 *
 * N'écrit RIEN, comme tout l'écran : c'est un chiffre de contrôle.
 */
final class GetVerificationParPaiements
{
    /**
     * @return array{
     *     frais: list<string>,
     *     lignes: list<array{studentId: int, nom: string, statut: string, du: float, paye: float, montant: float}>,
     *     total: float,
     *     etudiantsPayants: int,
     * }
     */
    public function __invoke(Group $group, MoisDeGroupe $fenetre, float $taux): array
    {
        $moisNumero = (int) $fenetre->moisCivil->format('n');

        $fraisDuMois = $group->frais
            ->filter(fn ($frais): bool => FraisEcheanceResolver::moisFromNom((string) $frais->nom) === $moisNumero)
            ->values();

        if ($fraisDuMois->isEmpty()) {
            return ['frais' => [], 'lignes' => [], 'total' => 0.0, 'etudiantsPayants' => 0];
        }

        $inscriptions = Inscription::query()
            ->with('student')
            ->where('group_id', $group->id)
            ->get()
            ->keyBy('id');

        $lignesFrais = InscriptionFee::query()
            ->whereIn('inscription_id', $inscriptions->keys())
            ->whereIn('frais_id', $fraisDuMois->pluck('id'))
            ->whereNull('masque_le')
            ->avecPayeNet()
            ->get();

        /** @var array<int, array{studentId: int, nom: string, statut: string, du: float, paye: float}> $parInscription */
        $parInscription = [];

        foreach ($lignesFrais as $fee) {
            $inscription = $inscriptions->get($fee->inscription_id);

            if ($inscription === null || $inscription->student === null) {
                continue;
            }

            // Deux lignes du même frais sur un dossier : fusionnées, comme
            // dans la matrice — jamais l'une perdue.
            $parInscription[$inscription->id] ??= [
                'studentId' => (int) $inscription->student_id,
                'nom' => $inscription->student->nomComplet(),
                'statut' => (string) $inscription->statut,
                'du' => 0.0,
                'paye' => 0.0,
            ];

            $parInscription[$inscription->id]['du'] += (float) $fee->montant;
            $parInscription[$inscription->id]['paye'] += $fee->payeNet();
        }

        $lignes = [];
        $total = 0.0;
        $payants = 0;

        foreach ($parInscription as $ligne) {
            $part = $ligne['du'] > 0.004 ? min(1.0, $ligne['paye'] / $ligne['du']) : 0.0;
            $montant = round($taux * $part, 2);

            if ($montant > 0) {
                $payants++;
            }

            $total += $montant;
            $lignes[] = [
                ...$ligne,
                'du' => round($ligne['du'], 2),
                'paye' => round($ligne['paye'], 2),
                'montant' => $montant,
            ];
        }

        usort($lignes, fn (array $a, array $b): int => strcoll($a['nom'], $b['nom']));

        return [
            'frais' => $fraisDuMois->pluck('nom')->map(fn ($n): string => (string) $n)->all(),
            'lignes' => $lignes,
            'total' => round($total, 2),
            'etudiantsPayants' => $payants,
        ];
    }
}
