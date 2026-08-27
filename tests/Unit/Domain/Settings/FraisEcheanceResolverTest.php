<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Settings;

use App\Domain\Settings\Support\FraisEcheanceResolver;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class FraisEcheanceResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Only the no-start-date fallback still reads "today", but pin it
        // anyway so that case is deterministic.
        Carbon::setTestNow('2026-08-19');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_month_comes_from_the_fee_name_day_and_year_from_the_group_start(): void
    {
        // A group starting 23/09/2025 runs Septembre–Décembre in 2025 and
        // rolls into 2026 for Janvier onwards — the school year, not the
        // calendar year.
        $this->assertSame('2025-09-23', FraisEcheanceResolver::defaultFor('Frais de Septembre', '2025-09-23'));
        $this->assertSame('2025-10-23', FraisEcheanceResolver::defaultFor("Frais d'Octobre", '2025-09-23'));
        $this->assertSame('2025-12-23', FraisEcheanceResolver::defaultFor('Frais de Décembre', '2025-09-23'));
        $this->assertSame('2026-01-23', FraisEcheanceResolver::defaultFor('Frais de Janvier', '2025-09-23'));
        $this->assertSame('2026-08-23', FraisEcheanceResolver::defaultFor('Frais de Août', '2025-09-23'));
    }

    public function test_the_due_dates_of_a_group_run_forward_from_its_first_month(): void
    {
        // The ordering guarantee every fee-by-due-date screen depends on:
        // a group's twelve monthly fees must come out strictly increasing,
        // starting at its own first month. Anchoring the year on "today"
        // instead put Janvier ahead of Septembre.
        $mois = [
            'Frais de Septembre', "Frais d'Octobre", 'Frais de Novembre', 'Frais de Décembre',
            'Frais de Janvier', 'Frais de Février', 'Frais de Mars', "Frais d'Avril",
            'Frais de Mai', 'Frais de Juin', 'Frais de Juillet', 'Frais de Août',
        ];

        $dates = array_map(
            fn (string $nom): string => (string) FraisEcheanceResolver::defaultFor($nom, '2025-09-01'),
            $mois,
        );

        $trie = $dates;
        sort($trie);

        $this->assertSame($trie, $dates, 'Monthly due dates must increase from the group start month.');
        $this->assertSame('2025-09-01', $dates[0]);
        $this->assertSame('2026-08-01', $dates[11]);
    }

    public function test_every_catalog_month_resolves(): void
    {
        $expected = [
            'Frais de Janvier' => 1, 'Frais de Février' => 2, 'Frais de Mars' => 3,
            "Frais d'Avril" => 4, 'Frais de Mai' => 5, 'Frais de Juin' => 6,
            'Frais de Juillet' => 7, 'Frais de Août' => 8, 'Frais de Septembre' => 9,
            "Frais d'Octobre" => 10, 'Frais de Novembre' => 11, 'Frais de Décembre' => 12,
        ];

        foreach ($expected as $nom => $mois) {
            $this->assertSame($mois, FraisEcheanceResolver::moisFromNom($nom), "{$nom} should map to month {$mois}.");
        }
    }

    public function test_a_fee_that_names_no_month_has_no_derivable_due_date(): void
    {
        // These are set by hand — guessing a date for them would be wrong.
        $this->assertNull(FraisEcheanceResolver::defaultFor("Frais d'inscription A1/A2/B1", '2025-09-23'));
        $this->assertNull(FraisEcheanceResolver::defaultFor('Frais annuel', '2025-09-23'));
        $this->assertNull(FraisEcheanceResolver::defaultFor('Frais dexam ÖSD A1', '2025-09-23'));
    }

    public function test_a_day_that_does_not_exist_in_the_target_month_is_clamped(): void
    {
        // A group starting the 31st must not push February into March.
        $this->assertSame('2025-02-28', FraisEcheanceResolver::defaultFor('Frais de Février', '2025-01-31'));
        // Leap year, reached by rolling into the next calendar year.
        $this->assertSame('2024-02-29', FraisEcheanceResolver::defaultFor('Frais de Février', '2023-10-31'));
    }

    public function test_missing_or_unparseable_group_start_falls_back_to_the_first(): void
    {
        // With nothing to anchor on, the year falls back to today's.
        $this->assertSame('2026-05-01', FraisEcheanceResolver::defaultFor('Frais de Mai', null));
        $this->assertSame('2026-05-01', FraisEcheanceResolver::defaultFor('Frais de Mai', ''));
        $this->assertSame('2026-05-01', FraisEcheanceResolver::defaultFor('Frais de Mai', 'not-a-date'));
    }

    public function test_accents_and_casing_do_not_break_matching(): void
    {
        $this->assertSame(12, FraisEcheanceResolver::moisFromNom('Frais de DÉCEMBRE'));
        $this->assertSame(12, FraisEcheanceResolver::moisFromNom('frais de decembre'));
        $this->assertSame(2, FraisEcheanceResolver::moisFromNom('FRAIS DE FEVRIER'));
    }

    public function test_exam_fees_sort_after_every_month(): void
    {
        $this->assertSame(0, FraisEcheanceResolver::ordreFromNom("Frais d'inscription A1/A2/B1"));
        $this->assertSame(12, FraisEcheanceResolver::ordreFromNom('Frais de Décembre'));
        $this->assertGreaterThan(12, FraisEcheanceResolver::ordreFromNom("Frais d'exam ÖSD A1"));
        $this->assertGreaterThan(12, FraisEcheanceResolver::ordreFromNom('Frais dexam ÖSD B2'));
        $this->assertGreaterThan(12, FraisEcheanceResolver::ordreFromNom("Frais d'examen"));
    }
}
