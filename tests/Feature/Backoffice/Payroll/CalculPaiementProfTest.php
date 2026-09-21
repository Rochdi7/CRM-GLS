<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Domain\Payroll\Actions\CalculerPaiementProfHebdomadaire;
use App\Domain\Payroll\Actions\CalculerPaiementProfHoraire;
use App\Domain\Payroll\Support\DecoupageSemaines;
use App\Domain\Payroll\Support\StatutPresencePaie;
use App\Models\Presence;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Calcul « Paiement prof » — la règle de rémunération elle-même.
 *
 * Tests PURS (aucune base) : ils portent sur le calcul, pas sur l'écran.
 * Le calcul est porté du portail GLS ; ces tests figent à la fois ce qui est
 * FIDÈLE au portail et la seule divergence ASSUMÉE (la semaine vide, voir
 * plus bas).
 */
final class CalculPaiementProfTest extends TestCase
{
    /** @param array<string, int> $semainesParLundi */
    private function datesDeCours(array $semainesParLundi): array
    {
        $dates = [];

        foreach ($semainesParLundi as $lundi => $nombre) {
            for ($i = 0; $i < $nombre; $i++) {
                $dates[] = Carbon::parse($lundi)->addDays($i)->toDateString();
            }
        }

        return $dates;
    }

    /** @param array<string, int> $semainesParLundi */
    private function semaines(array $semainesParLundi): array
    {
        $semaines = [];

        foreach ($semainesParLundi as $lundi => $nombre) {
            $semaines[Carbon::parse($lundi)->isoFormat('GGGG-WW')] = $nombre;
        }

        return $semaines;
    }

    /** @param array<string, int> $presencesParLundi */
    private function etudiant(array $presencesParLundi, int $id = 1): array
    {
        return [
            'student_id' => $id,
            'nom' => 'Étudiant '.$id,
            'semaines' => $this->semaines($presencesParLundi),
            'retenus' => array_sum($presencesParLundi),
            'absents' => 0,
            'ignores' => 0,
        ];
    }

    private const MOIS_PLEIN = [
        '2026-09-07' => 5,
        '2026-09-14' => 5,
        '2026-09-21' => 5,
        '2026-09-28' => 5,
    ];

    /*
    |--------------------------------------------------------------------
    | Fidélité au portail GLS
    |--------------------------------------------------------------------
    */

    #[Test]
    public function it_reproduces_the_documented_portail_example(): void
    {
        // PROF_PAYMENT_LOGIC.md §6 : base 500, 25 %, seuil 3,
        // présences [4, 3, 2, 5] → 3 semaines qualifiées → 375 DH.
        $resultat = (new CalculerPaiementProfHebdomadaire())->handle(
            etudiants: [$this->etudiant([
                '2026-09-07' => 4,
                '2026-09-14' => 3,
                '2026-09-21' => 2,
                '2026-09-28' => 5,
            ])],
            datesDeCours: $this->datesDeCours(self::MOIS_PLEIN),
            montantParEtudiant: 500.0,
        );

        $this->assertSame(375.0, $resultat->total);
        $this->assertSame(125.0, $resultat->montantSemaine);
        // La 3e semaine (2 jours < seuil 3) ne rapporte rien.
        $this->assertSame(0.0, $resultat->lignes[0]->montantsParSemaine[3]);
    }

    #[Test]
    public function a_student_present_every_week_is_worth_the_full_amount(): void
    {
        $resultat = (new CalculerPaiementProfHebdomadaire())->handle(
            etudiants: [$this->etudiant(self::MOIS_PLEIN)],
            datesDeCours: $this->datesDeCours(self::MOIS_PLEIN),
            montantParEtudiant: 500.0,
        );

        $this->assertSame(500.0, $resultat->total);
    }

    #[Test]
    public function a_week_below_the_threshold_earns_nothing(): void
    {
        $resultat = (new CalculerPaiementProfHebdomadaire())->handle(
            etudiants: [$this->etudiant([
                '2026-09-07' => 2, // < seuil
                '2026-09-14' => 2, // < seuil
                '2026-09-21' => 2, // < seuil
                '2026-09-28' => 2, // < seuil
            ])],
            datesDeCours: $this->datesDeCours(self::MOIS_PLEIN),
            montantParEtudiant: 500.0,
        );

        $this->assertSame(0.0, $resultat->total);
        $this->assertFalse($resultat->lignes[0]->qualifie);
    }

    #[Test]
    public function present_days_are_capped_at_five_per_iso_week(): void
    {
        // Une semaine ne peut pas porter plus de 5 présences (l'école
        // n'ouvre pas le week-end) : un 6e jour ne crée pas de crédit.
        $comptes = DecoupageSemaines::compter(
            ['2026-37' => 1],
            ['2026-37' => 8],
        );

        $this->assertSame(DecoupageSemaines::JOURS_MAX_PAR_SEMAINE, $comptes[1]);
    }

    /*
    |--------------------------------------------------------------------
    | Semaine écourtée par un férié
    |--------------------------------------------------------------------
    */

    #[Test]
    public function a_short_holiday_week_joins_the_current_bucket_instead_of_wasting_a_slot(): void
    {
        // Une semaine à 1 seul jour de cours ne peut jamais qualifier seule
        // (1 < 3). Elle doit REJOINDRE le bucket courant, sinon elle
        // consomme un créneau de paie définitivement ingagnable.
        $map = DecoupageSemaines::construire(
            array_merge(['2026-09-07'], $this->datesDeCours([
                '2026-09-14' => 5,
                '2026-09-21' => 5,
                '2026-09-28' => 5,
            ])),
            CalculerPaiementProfHebdomadaire::SEUIL_PAR_DEFAUT,
        );

        $semaineFeriee = Carbon::parse('2026-09-07')->isoFormat('GGGG-WW');
        $semaineSuivante = Carbon::parse('2026-09-14')->isoFormat('GGGG-WW');

        $this->assertSame($map[$semaineFeriee], $map[$semaineSuivante]);
    }

    #[Test]
    public function an_empty_week_is_never_counted_as_a_missed_one(): void
    {
        // ⚠ DIVERGENCE ASSUMÉE d'avec le portail GLS (21/09/2026).
        //
        // Quand un férié réduit le mois à 3 semaines enseignées, le portail
        // comptait quand même le 4e bucket — resté VIDE — comme une semaine
        // non qualifiée : un étudiant présent à TOUS les cours du mois ne
        // rapportait que 375 DH sur 500, l'enseignant perdant un quart de sa
        // paie à cause d'un férié dont il n'est pas responsable.
        //
        // Une semaine sans aucun jour de cours n'a pas été enseignée : elle
        // ne peut être ni gagnée, ni perdue.
        $datesDeCours = array_merge(['2026-09-07'], $this->datesDeCours([
            '2026-09-14' => 5,
            '2026-09-21' => 5,
            '2026-09-28' => 5,
        ]));

        $resultat = (new CalculerPaiementProfHebdomadaire())->handle(
            etudiants: [$this->etudiant([
                '2026-09-07' => 1,
                '2026-09-14' => 5,
                '2026-09-21' => 5,
                '2026-09-28' => 5,
            ])],
            datesDeCours: $datesDeCours,
            montantParEtudiant: 500.0,
        );

        $this->assertSame([1, 2, 3], $resultat->bucketsOccupes);
        $this->assertSame(500.0, $resultat->total);
        // La semaine jamais enseignée est NULL — ni gagnée, ni perdue —
        // pour que l'écran ne la peigne pas comme une semaine ratée.
        $this->assertNull($resultat->lignes[0]->montantsParSemaine[4]);
    }

    #[Test]
    public function a_holiday_month_still_costs_a_student_who_misses_a_week(): void
    {
        // Le correctif ci-dessus ne doit pas rendre l'absence gratuite.
        $datesDeCours = array_merge(['2026-09-07'], $this->datesDeCours([
            '2026-09-14' => 5,
            '2026-09-21' => 5,
            '2026-09-28' => 5,
        ]));

        $resultat = (new CalculerPaiementProfHebdomadaire())->handle(
            etudiants: [$this->etudiant([
                '2026-09-07' => 1,
                '2026-09-14' => 5,
                '2026-09-21' => 0, // semaine entièrement manquée
                '2026-09-28' => 5,
            ])],
            datesDeCours: $datesDeCours,
            montantParEtudiant: 500.0,
        );

        $this->assertLessThan(500.0, $resultat->total);
        $this->assertGreaterThan(0.0, $resultat->total);
    }

    /*
    |--------------------------------------------------------------------
    | Retard / Justifié — la règle propre au CRM
    |--------------------------------------------------------------------
    */

    #[Test]
    public function only_present_earns_and_retard_or_justifie_are_ignored(): void
    {
        // Le CRM enregistre 4 statuts là où le portail n'en avait que 2.
        // Décision du 21/09/2026 : seul « Présent » rémunère ; « Retard » et
        // « Justifié » sont ÉCARTÉS du calcul — ni pour, ni contre.
        $this->assertTrue(StatutPresencePaie::estRetenu(Presence::STATUT_PRESENT));
        $this->assertFalse(StatutPresencePaie::estRetenu(Presence::STATUT_ABSENT));
        $this->assertFalse(StatutPresencePaie::estRetenu(Presence::STATUT_RETARD));
        $this->assertFalse(StatutPresencePaie::estRetenu(Presence::STATUT_JUSTIFIE));

        $this->assertTrue(StatutPresencePaie::estIgnore(Presence::STATUT_RETARD));
        $this->assertTrue(StatutPresencePaie::estIgnore(Presence::STATUT_JUSTIFIE));
        $this->assertFalse(StatutPresencePaie::estIgnore(Presence::STATUT_ABSENT));

        // « Ignoré » ne veut pas dire « absent » : une semaine de 3 Présent
        // + 2 Retard qualifie exactement comme 3 Présent seuls.
        $this->assertNotContains(Presence::STATUT_RETARD, StatutPresencePaie::statutsComptes());
        $this->assertNotContains(Presence::STATUT_JUSTIFIE, StatutPresencePaie::statutsComptes());
    }

    /*
    |--------------------------------------------------------------------
    | Ajustement manuel
    |--------------------------------------------------------------------
    */

    #[Test]
    public function a_manual_adjustment_replaces_the_computed_amount(): void
    {
        $resultat = (new CalculerPaiementProfHebdomadaire())->handle(
            etudiants: [$this->etudiant([
                '2026-09-07' => 0,
                '2026-09-14' => 0,
                '2026-09-21' => 0,
                '2026-09-28' => 0,
            ])],
            datesDeCours: $this->datesDeCours(self::MOIS_PLEIN),
            montantParEtudiant: 500.0,
            ajustements: [1 => 250.0],
        );

        $this->assertSame(250.0, $resultat->total);
        $this->assertSame(250.0, $resultat->lignes[0]->montantAjuste);
        // Le montant calculé reste LISIBLE à côté de l'ajustement : c'est ce
        // qui permet de voir qu'on a dérogé, et de combien.
        $this->assertSame(0.0, $resultat->lignes[0]->montantAuto);
    }

    /*
    |--------------------------------------------------------------------
    | Mode horaire
    |--------------------------------------------------------------------
    */

    #[Test]
    public function the_hourly_mode_multiplies_rate_by_hours(): void
    {
        $this->assertSame(1500.0, (new CalculerPaiementProfHoraire())->handle(100.0, 15.0));
        $this->assertSame(0.0, (new CalculerPaiementProfHoraire())->handle(100.0, 0.0));
        $this->assertSame(337.5, (new CalculerPaiementProfHoraire())->handle(45.0, 7.5));
    }

    #[Test]
    public function the_total_is_the_sum_of_every_student(): void
    {
        $resultat = (new CalculerPaiementProfHebdomadaire())->handle(
            etudiants: [
                $this->etudiant(self::MOIS_PLEIN, 1),
                $this->etudiant(self::MOIS_PLEIN, 2),
                $this->etudiant([
                    '2026-09-07' => 0,
                    '2026-09-14' => 0,
                    '2026-09-21' => 0,
                    '2026-09-28' => 0,
                ], 3),
            ],
            datesDeCours: $this->datesDeCours(self::MOIS_PLEIN),
            montantParEtudiant: 500.0,
        );

        // Deux étudiants pleins + un qui ne rapporte rien.
        $this->assertSame(1000.0, $resultat->total);
        $this->assertSame(2, $resultat->etudiantsRemunerateurs);
    }
}
