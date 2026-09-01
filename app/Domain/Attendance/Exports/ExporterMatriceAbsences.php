<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Exports;

use App\Domain\Attendance\Queries\GetAbsencesParGroupe;
use App\Models\Group;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * « Absence par groupe » → .xlsx. Writes the SAME matrix the page renders
 * (one row per student, one column per séance, P/Q cells) so the file can
 * never disagree with the screen.
 *
 * OpenSpout is already a dependency (used read-side by the legacy importer);
 * it streams, so a long period with many séances never loads a full
 * spreadsheet into memory. The file is built in a temp file and streamed
 * back, rather than written straight to php://output, so an exception
 * mid-write still yields a clean error page instead of a corrupt download.
 */
final class ExporterMatriceAbsences
{
    /**
     * @param  array{seances: list<array<string, mixed>>, students: list<array<string, mixed>>, totals: array<string, int>}  $matrice
     * @param  array{dateFrom: string, dateTo: string, statutFilter: string}  $filters
     */
    public function __invoke(array $matrice, Group $group, array $filters): StreamedResponse
    {
        // OpenSpout assembles the .xlsx as a zip inside its temp folder, and
        // ZipArchive::close() finishes with a rename there. On Windows that
        // rename is refused in the user's TEMP root (the default), so the
        // scratch folder is one we own under storage/. Both the working
        // folder and the finished file live there and are cleaned up below.
        $temp = storage_path('app/exports');

        if (! is_dir($temp)) {
            mkdir($temp, 0775, true);
        }

        $path = $temp.DIRECTORY_SEPARATOR.'absences_'.bin2hex(random_bytes(8)).'.xlsx';

        // OpenSpout styles are immutable — every with*() returns a new Style.
        $header = (new Style())
            ->withFontBold(true)
            ->withBackgroundColor('E9ECEF');

        $present = (new Style())
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('4CD964');

        $absent = (new Style())
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('E74C3C');

        $writer = new Writer(new Options(tempFolder: $temp));
        $writer->openToFile($path);

        // Column header: Étudiant + one numbered column per séance, then the
        // per-student counters the page also shows.
        $headings = ['Étudiant', 'Référence'];

        foreach ($matrice['seances'] as $seance) {
            $headings[] = (string) $seance['numero'];
        }

        $headings[] = 'Présences';
        $headings[] = 'Absences';

        $writer->addRow(Row::fromValuesWithStyle($headings, $header));

        // Second line spells out each column's date, so the numbers above are
        // readable outside the app.
        $dates = ['Date', ''];

        foreach ($matrice['seances'] as $seance) {
            $dates[] = $this->formatDate((string) $seance['date']);
        }

        $dates[] = '';
        $dates[] = '';

        $writer->addRow(Row::fromValuesWithStyle($dates, $header));

        foreach ($matrice['students'] as $student) {
            $marks = (array) $student['cells'];
            $cells = [
                Cell::fromValue(mb_strtoupper("{$student['prenom']} {$student['nom']}")),
                Cell::fromValue((string) ($student['reference'] ?? '')),
            ];

            // Each P/Q carries its own green/red fill, so the sheet reads
            // like the screen rather than as a wall of letters.
            foreach ($matrice['seances'] as $seance) {
                $mark = $marks[(string) $seance['id']] ?? null;

                $cells[] = $mark === null
                    ? Cell::fromValue('')
                    : Cell::fromValue(
                        (string) $mark['lettre'],
                        $mark['lettre'] === GetAbsencesParGroupe::CELL_PRESENT ? $present : $absent,
                    );
            }

            $cells[] = Cell::fromValue((int) $student['presents']);
            $cells[] = Cell::fromValue((int) $student['absents']);

            $writer->addRow(new Row($cells));
        }

        $writer->close();

        $filename = $this->filename($group, $filters);

        return response()->streamDownload(
            function () use ($path): void {
                $handle = fopen($path, 'rb');

                if ($handle !== false) {
                    fpassthru($handle);
                    fclose($handle);
                }

                @unlink($path);
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    /**
     * @param  array{dateFrom: string, dateTo: string, statutFilter: string}  $filters
     */
    private function filename(Group $group, array $filters): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $group->nom) ?? 'groupe';
        $window = trim(($filters['dateFrom'] ?: '').'_'.($filters['dateTo'] ?: ''), '_');

        return trim("absences_{$slug}".($window !== '' ? "_{$window}" : ''), '-_').'.xlsx';
    }

    private function formatDate(string $date): string
    {
        $parts = explode('-', $date);

        return count($parts) === 3 ? "{$parts[2]}/{$parts[1]}/{$parts[0]}" : $date;
    }
}
