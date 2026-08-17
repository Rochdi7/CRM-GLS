<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Services\Import\Exceptions\ImportHeaderNotFoundException;
use Generator;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Reads a legacy-CRM XLSX export: detects the real header row among a
 * handful of metadata rows (title, centre name/address/phone, "Date :",
 * "Période :", ...), then streams data rows until the export's footer
 * (stray totals / "Total par méthode" / "Total par frais" blocks — paiements
 * exports only). Never loads the whole sheet into memory.
 */
final class SheetReader
{
    private const int HEADER_SCAN_ROWS = 20;

    private const array HEADER_FIRST_CELL_TOKENS = ['N°', "N° d'ordre"];

    private const array HEADER_REF_TOKENS = ['Réf', 'Réf.'];

    /** Pathological-file guard — real exports are a few dozen/hundred rows. */
    private const int FOOTER_CUTOFF_ROW_CAP = 5000;

    /**
     * @param  array<int, string>  $expectedColumns  column names that must be present in the detected header row
     * @return array{headerRowNumber: int, headerMap: array<string, int>}
     */
    public function detectHeader(string $filePath, array $expectedColumns): array
    {
        $reader = $this->makeReader();
        $reader->open($filePath);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $rowNumber = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;

                    if ($rowNumber > self::HEADER_SCAN_ROWS) {
                        break;
                    }

                    $cells = $row->toArray();
                    $texts = array_map(static fn (mixed $v): string => Support\CellNormalizer::text($v), $cells);

                    if (! $this->rowLooksLikeHeader($texts)) {
                        continue;
                    }

                    $headerMap = $this->buildHeaderMap($texts);
                    $this->assertExpectedColumnsPresent($headerMap, $expectedColumns);

                    return ['headerRowNumber' => $rowNumber, 'headerMap' => $headerMap];
                }

                break;
            }
        } finally {
            $reader->close();
        }

        throw new ImportHeaderNotFoundException(
            sprintf("Aucune ligne d'en-tête trouvée dans les %d premières lignes du fichier.", self::HEADER_SCAN_ROWS)
        );
    }

    /**
     * Streams data rows starting right after the header row, stopping at the
     * first row whose first cell isn't a positive integer (the footer
     * cutoff rule — correctly excludes every real footer shape observed:
     * stray totals, blank rows, "Total par méthode"/"Total par frais"
     * blocks — with no special-casing).
     *
     * @param  array<string, int>  $headerMap
     * @return Generator<int, array<string, mixed>>
     */
    public function readDataRows(string $filePath, int $headerRowNumber, array $headerMap): Generator
    {
        $reader = $this->makeReader();
        $reader->open($filePath);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $rowNumber = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;

                    if ($rowNumber <= $headerRowNumber) {
                        continue;
                    }

                    if ($rowNumber > $headerRowNumber + self::FOOTER_CUTOFF_ROW_CAP) {
                        break 2;
                    }

                    $cells = $row->toArray();

                    if (! $this->isDataRow($cells)) {
                        break 2;
                    }

                    yield $rowNumber => $this->mapRow($cells, $headerMap);
                }

                break;
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Distinct, trimmed, non-empty values of one named column across every
     * data row — powers the pre-analyze Groupe/Opérateur mapping screens.
     *
     * @return array<int, string>
     */
    public function distinctColumnValues(string $filePath, string $columnName): array
    {
        [$headerRowNumber, $headerMap] = array_values($this->detectHeader($filePath, [$columnName]));

        $values = [];

        foreach ($this->readDataRows($filePath, $headerRowNumber, $headerMap) as $row) {
            $value = Support\CellNormalizer::text($row[$columnName] ?? '');

            if ($value !== '') {
                $values[$value] = true;
            }
        }

        return array_keys($values);
    }

    /**
     * Row numbers must line up with real XLSX row numbers (used for
     * source_row_number and the header-row math) — openspout skips
     * genuinely empty rows by default, which would drift the count.
     */
    private function makeReader(): Reader
    {
        return new Reader(new Options(SHOULD_PRESERVE_EMPTY_ROWS: true));
    }

    /** @param array<int, string> $texts */
    private function rowLooksLikeHeader(array $texts): bool
    {
        $first = $texts[0] ?? '';

        if (! in_array($first, self::HEADER_FIRST_CELL_TOKENS, true)) {
            return false;
        }

        foreach ($texts as $text) {
            if (in_array($text, self::HEADER_REF_TOKENS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<string, int>
     */
    private function buildHeaderMap(array $texts): array
    {
        $map = [];

        foreach ($texts as $index => $text) {
            if ($text !== '') {
                $map[$text] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $headerMap
     * @param  array<int, string>  $expectedColumns
     */
    private function assertExpectedColumnsPresent(array $headerMap, array $expectedColumns): void
    {
        $missing = array_values(array_diff($expectedColumns, array_keys($headerMap)));

        if ($missing !== []) {
            throw new ImportHeaderNotFoundException(sprintf(
                'Colonnes attendues introuvables dans le fichier : %s.',
                implode(', ', $missing)
            ));
        }
    }

    /** @param array<int, mixed> $cells */
    private function isDataRow(array $cells): bool
    {
        $first = Support\CellNormalizer::text($cells[0] ?? '');

        return $first !== '' && ctype_digit($first) && (int) $first > 0;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @param  array<string, int>  $headerMap
     * @return array<string, mixed>
     */
    private function mapRow(array $cells, array $headerMap): array
    {
        $mapped = [];

        foreach ($headerMap as $columnName => $index) {
            $mapped[$columnName] = $cells[$index] ?? null;
        }

        return $mapped;
    }
}
