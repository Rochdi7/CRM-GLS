<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Domain\Payroll\Actions\CalculerPaiementProfHoraire;
use App\Domain\Payroll\Actions\CalculerPaiementProfParPaliers;
use App\Domain\Payroll\DTOs\ResultatPaiementProf;
use App\Domain\Payroll\Support\StatutPresencePaie;
use App\Models\Presence;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Calcul « Paiement prof » — la règle de rémunération elle-même.
 *
 *     < 5 présences → 0 · 5–6 → 1 semaine · 7–10 → 2 semaines · 11+ → complet
 *     une semaine = taux ÷ 4
 *
 * Tests PURS (aucune base) : ils portent sur le calcul, pas sur l'écran.
 */
final class CalculPaiementProfTest extends TestCase
{
    /** @return array<string, mixed> */
    private function etudiant(int $presences, int $absences = 0, int $herites = 0, int $id = 1): array
    {
        return [
            'student_id' => $id,
            'nom' => 'Étudiant '.$id,
            'retenus' => $presences,
            'absents' => $absences,
            'ignores' => $herites,
        ];
    }

    /** @param array<int, array<string, mixed>> $etudiants */
    private function calculer(
        array $etudiants,
        int $seances,
        float $taux = 500.0,
        array $ajustements = [],
    ): ResultatPaiementProf {
        return (new CalculerPaiementProfParPaliers())
            ->handle($etudiants, $seances, $taux, $ajustements);
    }

    /*
    |--------------------------------------------------------------------
    | Les paliers (règle du CEO, 30/09/2026)
    |--------------------------------------------------------------------
    */

    #[Test]
    public function each_attendance_tier_pays_its_weeks(): void
    {
        // taux 400 ⇒ une semaine = 100.
        $attendu = [
            0 => 0.0, 4 => 0.0,
            5 => 100.0, 6 => 100.0,
            7 => 200.0, 10 => 200.0,
            11 => 400.0, 15 => 400.0, 20 => 400.0,
        ];

        foreach ($attendu as $presences => $montant) {
            $this->assertSame(
                $montant,
                $this->calculer([$this->etudiant($presences)], 20, 400.0)->lignes[0]->montantEffectif,
                "$presences présences",
            );
        }
    }

    #[Test]
    public function absences_no_longer_cut_a_full_month(): void
    {
        // Le cas réel qui a fait changer la règle — « Yassmina 10H A1 »,
        // septembre 2026, 20 séances, win-win 400 : 18/2 sortait à 360 DH,
        // 17/3 à 340 DH, et le CEO les remettait à la main à 400.
        $resultat = $this->calculer([
            $this->etudiant(18, 2, 0, 1),
            $this->etudiant(17, 3, 0, 2),
            $this->etudiant(15, 5, 0, 3),
        ], 20, 400.0);

        foreach ($resultat->lignes as $ligne) {
            $this->assertSame(400.0, $ligne->montantEffectif);
            $this->assertSame(4, $ligne->semainesPayees);
        }
        $this->assertSame(1200.0, $resultat->total);
    }

    #[Test]
    public function a_student_who_came_late_or_left_early_is_paid_by_what_he_followed(): void
    {
        // Même groupe : arrivés en fin de mois (3/0, 3/1) ou partis tôt
        // (1/5, 3/10) — le CEO les mettait à 0. 5 présences ⇒ 1 semaine.
        $resultat = $this->calculer([
            $this->etudiant(3, 0, 0, 1),
            $this->etudiant(1, 5, 0, 2),
            $this->etudiant(3, 10, 0, 3),
            $this->etudiant(5, 0, 0, 4),
        ], 20, 400.0);

        $this->assertSame([0.0, 0.0, 0.0, 100.0], array_map(
            static fn ($l) => $l->montantEffectif,
            $resultat->lignes,
        ));
        $this->assertSame(1, $resultat->etudiantsRemunerateurs);
    }

    #[Test]
    public function the_session_count_of_the_month_does_not_change_the_tier(): void
    {
        // Un mois court ou chargé ne déplace pas les seuils.
        foreach ([8, 13, 20, 25] as $seances) {
            $this->assertSame(500.0, $this->calculer([$this->etudiant(11)], $seances)->lignes[0]->montantEffectif);
        }
    }

    #[Test]
    public function a_week_is_a_quarter_of_the_rate_computed_on_the_full_rate(): void
    {
        $resultat = $this->calculer([$this->etudiant(7), $this->etudiant(22, 0, 0, 2)], 22, 450.0);

        $this->assertSame(112.5, $resultat->montantParSemaine);
        $this->assertSame(225.0, $resultat->lignes[0]->montantEffectif);
        // « Complet » vaut EXACTEMENT le taux.
        $this->assertSame(450.0, $resultat->lignes[1]->montantEffectif);
    }

    /*
    |--------------------------------------------------------------------
    | Statuts
    |--------------------------------------------------------------------
    */

    #[Test]
    public function only_present_earns(): void
    {
        // Décision du 22/09/2026 : la saisie d'appel n'offre plus que
        // Présent / Absent. Les rares « Retard » hérités de l'ancien import
        // (14 lignes en base) ne rapportent rien.
        $this->assertTrue(StatutPresencePaie::estRetenu(Presence::STATUT_PRESENT));
        $this->assertFalse(StatutPresencePaie::estRetenu(Presence::STATUT_ABSENT));
        $this->assertFalse(StatutPresencePaie::estRetenu(Presence::STATUT_RETARD));
        $this->assertFalse(StatutPresencePaie::estRetenu(Presence::STATUT_JUSTIFIE));

        $this->assertTrue(StatutPresencePaie::estHerite(Presence::STATUT_RETARD));
        $this->assertTrue(StatutPresencePaie::estHerite(Presence::STATUT_JUSTIFIE));
        $this->assertFalse(StatutPresencePaie::estHerite(Presence::STATUT_ABSENT));
    }

    #[Test]
    public function a_legacy_late_row_pays_nothing(): void
    {
        // 9 présences + 2 « Retard » : seules les 9 présences comptent, donc
        // 2 semaines — les Retard ne font pas franchir le palier des 11.
        $resultat = $this->calculer([$this->etudiant(9, 0, 2)], 22);

        $this->assertSame(250.0, $resultat->lignes[0]->montantEffectif);
    }

    /*
    |--------------------------------------------------------------------
    | Ajustement manuel + total
    |--------------------------------------------------------------------
    */

    #[Test]
    public function a_manual_adjustment_replaces_the_computed_amount(): void
    {
        $resultat = $this->calculer([$this->etudiant(0, 22)], 22, 500.0, [1 => 250.0]);

        $this->assertSame(250.0, $resultat->total);
        $this->assertSame(250.0, $resultat->lignes[0]->montantAjuste);
        // Le montant calculé reste LISIBLE à côté : c'est ce qui permet de
        // voir qu'on a dérogé, et de combien.
        $this->assertSame(0.0, $resultat->lignes[0]->montantAuto);
    }

    #[Test]
    public function the_total_is_the_sum_of_every_student(): void
    {
        $resultat = $this->calculer([
            $this->etudiant(22, 0, 0, 1),
            $this->etudiant(8, 14, 0, 2),
            $this->etudiant(0, 22, 0, 3),
        ], 22);

        // 500 + 250 (2 semaines) + 0
        $this->assertSame(750.0, $resultat->total);
        $this->assertSame(2, $resultat->etudiantsRemunerateurs);
    }

    /*
    |--------------------------------------------------------------------
    | Mode horaire (inchangé)
    |--------------------------------------------------------------------
    */

    #[Test]
    public function the_hourly_mode_multiplies_rate_by_hours(): void
    {
        $this->assertSame(1500.0, (new CalculerPaiementProfHoraire())->handle(100.0, 15.0));
        $this->assertSame(337.5, (new CalculerPaiementProfHoraire())->handle(45.0, 7.5));
    }
}
