<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Services\Import\Exceptions\ImportCellParseException;
use App\Services\Import\Exceptions\ImportHeaderNotFoundException;
use App\Services\Import\SheetReader;
use App\Services\Import\Support\CellNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * SheetReader/CellNormalizer are pure PHP with no Laravel dependency —
 * exercised directly against the real sample exports at
 * `old crm data exemple/`, no DB/app boot needed.
 */
final class SheetReaderTest extends TestCase
{
    private const string ETUDIANTS_FILE = __DIR__.'/../../../../old crm data exemple/liste-etudiants_55_20260817.xlsx';

    private const string INSCRIPTIONS_FILE = __DIR__.'/../../../../old crm data exemple/liste-inscriptions_31_20260817.xlsx';

    private const string PAIEMENTS_FILE = __DIR__.'/../../../../old crm data exemple/liste-paiements_19_20260817.xlsx';

    public function test_detects_header_row_for_students_file_at_row_10(): void
    {
        $reader = new SheetReader();

        $result = $reader->detectHeader(self::ETUDIANTS_FILE, ['Réf', 'Prénom', 'Nom', 'Téléphone', 'Sexe']);

        $this->assertSame(10, $result['headerRowNumber']);
        $this->assertArrayHasKey('Prénom', $result['headerMap']);
        $this->assertArrayHasKey('Nom', $result['headerMap']);
    }

    public function test_detects_header_row_for_inscriptions_file_at_row_10(): void
    {
        $reader = new SheetReader();

        $result = $reader->detectHeader(self::INSCRIPTIONS_FILE, ['Réf', 'Étudiant', 'Groupe', 'Statut']);

        $this->assertSame(10, $result['headerRowNumber']);
    }

    public function test_detects_header_row_for_paiements_file_at_row_9_one_fewer_metadata_row(): void
    {
        $reader = new SheetReader();

        $result = $reader->detectHeader(self::PAIEMENTS_FILE, ['Réf.', 'Élève / Payeur', 'Montant', 'Méthode', 'Frais']);

        $this->assertSame(9, $result['headerRowNumber']);
    }

    public function test_throws_when_expected_column_missing(): void
    {
        $reader = new SheetReader();

        $this->expectException(ImportHeaderNotFoundException::class);

        $reader->detectHeader(self::ETUDIANTS_FILE, ['Colonne Inexistante']);
    }

    public function test_reads_exactly_41_student_rows(): void
    {
        $reader = new SheetReader();
        ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $reader->detectHeader(
            self::ETUDIANTS_FILE,
            ['Réf', 'Prénom', 'Nom']
        );

        $rows = iterator_to_array($reader->readDataRows(self::ETUDIANTS_FILE, $headerRow, $headerMap));

        $this->assertCount(41, $rows);
    }

    public function test_reads_exactly_47_inscription_rows(): void
    {
        $reader = new SheetReader();
        ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $reader->detectHeader(
            self::INSCRIPTIONS_FILE,
            ['Réf', 'Étudiant']
        );

        $rows = iterator_to_array($reader->readDataRows(self::INSCRIPTIONS_FILE, $headerRow, $headerMap));

        $this->assertCount(47, $rows);
    }

    public function test_footer_rows_excluded_reads_exactly_64_paiement_rows(): void
    {
        // Rows 10-73 in the raw XML (P4255 down to P4201) = 64 data rows;
        // row 74 is the stray-total row, followed by the "Total par
        // méthode"/"Total par frais" blocks — all correctly excluded.
        $reader = new SheetReader();
        ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $reader->detectHeader(
            self::PAIEMENTS_FILE,
            ['Réf.', 'Élève / Payeur']
        );

        $rows = iterator_to_array($reader->readDataRows(self::PAIEMENTS_FILE, $headerRow, $headerMap));

        $this->assertCount(64, $rows);

        foreach ($rows as $row) {
            $this->assertNotSame('Total par méthode', $row['Réf.'] ?? null);
        }
    }

    public function test_distinct_groupe_values_from_inscriptions_file(): void
    {
        $reader = new SheetReader();

        $values = $reader->distinctColumnValues(self::INSCRIPTIONS_FILE, 'Groupe');

        $this->assertContains('Herr Driss 13h', $values);
        $this->assertContains('GROUP 19H SEPTEMBRE', $values);
        $this->assertContains('NV GROUP 16h SEPTEMBRE', $values);
    }

    public function test_distinct_operateur_values_from_paiements_file(): void
    {
        $reader = new SheetReader();

        $values = $reader->distinctColumnValues(self::PAIEMENTS_FILE, 'Opérateur');

        $this->assertEqualsCanonicalizing(['mustapha', 'latifa'], $values);
    }

    public function test_row_18_date_naissance_dash_parses_to_null(): void
    {
        $this->assertNull(CellNormalizer::parseDate('-'));
        $this->assertNull(CellNormalizer::parseDate(''));
    }

    public function test_parses_dd_mm_yyyy_date(): void
    {
        $date = CellNormalizer::parseDate('26/11/2003');

        $this->assertSame('2003-11-26', $date->toDateString());
    }

    public function test_parses_dd_mm_yyyy_hi_datetime(): void
    {
        $date = CellNormalizer::parseDate('17/08/2026 13:49');

        $this->assertSame('2026-08-17', $date->toDateString());
    }

    public function test_throws_on_unparseable_date(): void
    {
        $this->expectException(ImportCellParseException::class);

        CellNormalizer::parseDate('not a date');
    }

    public function test_parses_money_strips_dh_suffix(): void
    {
        $this->assertSame('1300.00', CellNormalizer::parseMoney('1300 Dh'));
        $this->assertSame('50.00', CellNormalizer::parseMoney('50 Dh'));
        $this->assertSame('650.00', CellNormalizer::parseMoney('650 Dh'));
    }

    public function test_normalizes_text_collapses_double_spaces_and_trims(): void
    {
        $this->assertSame('HASNA MACH', CellNormalizer::text('HASNA  MACH'));
        $this->assertSame('HASNA', CellNormalizer::text('HASNA '));
        $this->assertSame('ANAS', CellNormalizer::text('ANAS '));
    }

    public function test_normalizes_phone_to_e164_morocco(): void
    {
        $this->assertSame('+212716202559', CellNormalizer::normalizePhone('212716202559'));
    }

    public function test_collapses_cleanly_doubled_payer_name(): void
    {
        $result = CellNormalizer::collapseDoubledName('ABDERRAHMANE  BOUGMA ABDERRAHMANE  BOUGMA');

        $this->assertTrue($result['collapsed']);
        $this->assertSame('ABDERRAHMANE BOUGMA', $result['value']);
    }

    public function test_does_not_collapse_the_real_mismatched_row_p4226(): void
    {
        $result = CellNormalizer::collapseDoubledName('younes TALIB HACHIM TALIBi');

        $this->assertFalse($result['collapsed']);
        $this->assertSame('younes TALIB HACHIM TALIBi', $result['value']);
    }

    public function test_best_effort_guess_for_uncollapsed_name(): void
    {
        // 4 tokens -> ceil(4/2) = first 2 tokens, the natural-midpoint guess.
        $guess = CellNormalizer::bestEffortNameGuess('younes TALIB HACHIM TALIBi');

        $this->assertSame('younes TALIB', $guess);
    }
}
