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
        // The year comes from "today", so it has to be pinned.
        Carbon::setTestNow('2026-08-19');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_month_comes_from_the_fee_name_day_from_the_group_start_year_from_today(): void
    {
        // Septembre = 9, the group starts on the 23rd, this year is 2026.
        $this->assertSame('2026-09-23', FraisEcheanceResolver::defaultFor('Frais de Septembre', '2025-09-23'));
        $this->assertSame('2026-01-23', FraisEcheanceResolver::defaultFor('Frais de Janvier', '2025-09-23'));
        $this->assertSame('2026-10-05', FraisEcheanceResolver::defaultFor("Frais d'Octobre", '2025-09-05'));
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
        $this->assertSame('2026-02-28', FraisEcheanceResolver::defaultFor('Frais de Février', '2025-01-31'));
    }

    public function test_missing_or_unparseable_group_start_falls_back_to_the_first(): void
    {
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
}
