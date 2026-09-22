<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Domain\Payroll\Actions\CalculerPaiementProfHoraire;
use App\Domain\Payroll\Actions\CalculerPaiementProfParSeance;
use App\Domain\Payroll\DTOs\ResultatPaiementProf;
use App\Domain\Payroll\Support\StatutPresencePaie;
use App\Models\Presence;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Calcul « Paiement prof » — la règle de rémunération elle-même.
 *
 *     montant par séance = taux ÷ séances du mois (plafond 22)
 *     montant étudiant   = montant par séance × ses présences
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
        return (new CalculerPaiementProfParSeance())
            ->handle($etudiants, $seances, $taux, $ajustements);
    }

    /*
    |--------------------------------------------------------------------
    | La règle
    |--------------------------------------------------------------------
    */

    #[Test]
    public function it_pays_the_rate_divided_by_sessions_times_attendance(): void
    {
        // Exemple de référence (22/09/2026) : taux 500 sur 22 séances ⇒
        // 22,73 DH la séance ; 18 présences ⇒ 409,09 DH.
        $resultat = $this->calculer([$this->etudiant(18, 4)], 22);

        $this->assertSame(22.73, $resultat->montantParSeance);
        $this->assertSame(409.09, $resultat->lignes[0]->montantEffectif);
        $this->assertSame(409.09, $resultat->total);
    }

    #[Test]
    public function a_student_present_at_every_session_is_worth_exactly_the_rate(): void
    {
        // ⚠ Le produit se calcule sur le taux ENTIER, jamais sur la part
        // arrondie : 22,73 × 22 rendrait 500,06 et « présent partout » ne
        // vaudrait plus le prix plein.
        $this->assertSame(500.0, $this->calculer([$this->etudiant(22)], 22)->lignes[0]->montantEffectif);
        $this->assertSame(500.0, $this->calculer([$this->etudiant(13)], 13)->lignes[0]->montantEffectif);
        $this->assertSame(500.0, $this->calculer([$this->etudiant(7)], 7)->lignes[0]->montantEffectif);
    }

    #[Test]
    public function a_student_never_present_earns_nothing(): void
    {
        $resultat = $this->calculer([$this->etudiant(0, 22)], 22);

        $this->assertSame(0.0, $resultat->lignes[0]->montantEffectif);
        $this->assertSame(0, $resultat->etudiantsRemunerateurs);
    }

    #[Test]
    public function there_is_no_threshold_anymore(): void
    {
        // UNE seule présence paie : le modèle hebdomadaire exigeait 3 jours
        // dans la semaine, celui-ci est strictement proportionnel.
        $resultat = $this->calculer([$this->etudiant(1, 21)], 22);

        $this->assertSame(22.73, $resultat->lignes[0]->montantEffectif);
        $this->assertSame(1, $resultat->etudiantsRemunerateurs);
    }

    /*
    |--------------------------------------------------------------------
    | Le plafond de 22 séances
    |--------------------------------------------------------------------
    */

    #[Test]
    public function a_short_month_divides_by_its_real_session_count(): void
    {
        // « 22 séances AU MAXIMUM » est un plafond, jamais un plancher : un
        // mois creux ne doit pas faire perdre d'argent à l'enseignant.
        $resultat = $this->calculer([$this->etudiant(13)], 13);

        $this->assertSame(13, $resultat->seancesRemunerees);
        $this->assertSame(38.46, $resultat->montantParSeance);
    }

    #[Test]
    public function a_busy_month_is_capped_at_twenty_two_sessions(): void
    {
        // 25 séances : on divise par 22, pas par 25. Les séances au-delà du
        // plafond rapportent EN PLUS — l'étudiant a suivi plus de cours que
        // le mois n'en compte normalement.
        $resultat = $this->calculer([$this->etudiant(25)], 25);

        $this->assertSame(CalculerPaiementProfParSeance::SEANCES_MAX_PAR_MOIS, $resultat->seancesRemunerees);
        $this->assertSame(25, $resultat->nombreSeances);
        $this->assertSame(22.73, $resultat->montantParSeance);
        $this->assertGreaterThan(500.0, $resultat->lignes[0]->montantEffectif);
    }

    #[Test]
    public function a_month_without_any_session_never_divides_by_zero(): void
    {
        $resultat = $this->calculer([$this->etudiant(0)], 0);

        $this->assertSame(0.0, $resultat->montantParSeance);
        $this->assertSame(0.0, $resultat->total);
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
        // 20 présences + 2 « Retard » sur 22 séances : seules les 20
        // présences paient.
        $resultat = $this->calculer([$this->etudiant(20, 0, 2)], 22);

        $this->assertSame(round(500 * 20 / 22, 2), $resultat->lignes[0]->montantEffectif);
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
            $this->etudiant(11, 11, 0, 2),
            $this->etudiant(0, 22, 0, 3),
        ], 22);

        // 500 + 250 + 0
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
