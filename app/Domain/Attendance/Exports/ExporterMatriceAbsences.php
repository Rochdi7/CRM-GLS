<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Exports;

use App\Domain\Attendance\Queries\GetAbsencesParGroupe;
use App\Models\Group;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * « Absence par groupe » → .xlsx. Writes the SAME matrix the page renders
 * (one row per student, one column per séance, P/A cells) so the file can
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
     * Width of the « Étudiant » column, in Excel's character unit — 34.06 is
     * the 554 px of the reference workbook. Changing it changes how the file
     * opens for everyone, so it is a constant rather than a magic number.
     */
    private const float LARGEUR_NOM = 34.06;

    /** A séance column holds one letter; the reference keeps them narrow. */
    private const float LARGEUR_SEANCE = 11.0;

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
        // The fills are the reference workbook's, which are also the screen's
        // (app.css « Absence par groupe »): the sheet and the page must never
        // read differently.
        $header = (new Style())
            ->withFontBold(true)
            ->withBackgroundColor('D9E1F2')
            ->withBorder($this->border());

        $present = (new Style())
            ->withFontBold(true)
            ->withBackgroundColor('4CD964')
            ->withBorder($this->border());

        $absent = (new Style())
            ->withFontBold(true)
            ->withBackgroundColor('E74C3C')
            ->withBorder($this->border());

        // An empty cell is FILLED grey, never left blank: on the reference
        // sheet a grey box means there is nothing at that spot (séance not
        // marked, or student not enrolled yet), which a white gap would not
        // say.
        $vide = (new Style())
            ->withBackgroundColor('A6A6A6')
            ->withBorder($this->border());

        // Name cells carry the row's statut colour, same split as the page
        // and « Détails paiement »: grey once the enrollment moved on, red
        // for a cancellation.
        $nomActif = (new Style())
            ->withBackgroundColor('BFBFBF')
            ->withBorder($this->border());

        $nomClos = (new Style())
            ->withBackgroundColor('AAAAAA')
            ->withBorder($this->border());

        $nomAnnule = (new Style())
            ->withFontBold(true)
            ->withBackgroundColor('F62D51')
            ->withBorder($this->border());

        $options = new Options(tempFolder: $temp);

        // Column widths of the reference workbook: the name column is 34.06
        // (554 px in Excel), wide enough for a full « PRENOM NOM » without
        // wrapping, and every séance column is narrow — they hold a single
        // letter. Columns are 1-indexed here (A = 1), so the séance columns
        // are 2 … n+1.
        $options->setColumnWidth(self::LARGEUR_NOM, 1);

        if ($matrice['seances'] !== []) {
            $options->setColumnWidthForRange(
                self::LARGEUR_SEANCE,
                2,
                count($matrice['seances']) + 1,
            );
        }

        $writer = new Writer($options);
        $writer->openToFile($path);

        // Two header rows exactly like the reference workbook: the séance
        // NUMBER on top, its DATE directly underneath, and « Étudiant » as
        // the only label of the name column (the second row's first cell is
        // left empty rather than repeating a « Date » label the reference
        // does not have).
        //
        // No « Référence » / « Présences » / « Absences » columns: the
        // reference sheet is the matrix and nothing else, and the counters
        // are already on screen.
        $numeros = ['Étudiant'];
        $dates = [''];

        foreach ($matrice['seances'] as $seance) {
            $numeros[] = (string) $seance['numero'];
            $dates[] = $this->formatDate((string) $seance['date']);
        }

        $writer->addRow(Row::fromValuesWithStyle($numeros, $header));
        $writer->addRow(Row::fromValuesWithStyle($dates, $header));

        foreach ($matrice['students'] as $student) {
            $marks = (array) $student['cells'];
            $statut = (string) $student['inscriptionStatut'];
            $actif = (bool) $student['actif'];

            $cells = [
                Cell::fromValue(
                    mb_strtoupper("{$student['prenom']} {$student['nom']}"),
                    $actif ? $nomActif : ($statut === 'Annulée' ? $nomAnnule : $nomClos),
                ),
            ];

            // Each P/A carries its own green/red fill, so the sheet reads
            // like the screen rather than as a wall of letters; a séance
            // nobody pointed is grey for EVERY student, like its greyed
            // column on the page.
            foreach ($matrice['seances'] as $seance) {
                $mark = ($seance['saisie'] ?? true) ? ($marks[(string) $seance['id']] ?? null) : null;

                $cells[] = $mark === null
                    ? Cell::fromValue('', $vide)
                    : Cell::fromValue(
                        (string) $mark['lettre'],
                        $mark['lettre'] === GetAbsencesParGroupe::CELL_PRESENT ? $present : $absent,
                    );
            }

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

    /** Thin grey grid around every cell, like the reference workbook. */
    private function border(): Border
    {
        return new Border(
            new BorderPart(BorderName::TOP, '808080', BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::RIGHT, '808080', BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::BOTTOM, '808080', BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::LEFT, '808080', BorderWidth::THIN, BorderStyle::SOLID),
        );
    }

    private function formatDate(string $date): string
    {
        $parts = explode('-', $date);

        return count($parts) === 3 ? "{$parts[2]}/{$parts[1]}/{$parts[0]}" : $date;
    }
}
