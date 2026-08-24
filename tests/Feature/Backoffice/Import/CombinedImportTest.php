<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Combined « Étudiants + Inscriptions » import: students are imported and
 * auto-committed first, then the inscriptions of the SAME run resolve
 * against them — the whole point is that no "étudiant introuvable"
 * conflict can come from running the two modules separately.
 */
final class CombinedImportTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        Frais::create(['nom' => 'Frais catalogue test', 'statut' => Frais::STATUT_ACTIF]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /**
     * Minimal inline-strings workbook, same ZipArchive approach as
     * InscriptionImportTest::buildUpload (OpenSpout's writer dies on the
     * Windows temp folder). The leading "N°" column keys header detection.
     *
     * @param  list<string>  $header  WITHOUT the leading "N°"
     * @param  array<int, array<int, string>>  $rows
     */
    private function buildXlsx(array $header, array $rows): UploadedFile
    {
        $dir = storage_path('framework/testing/import');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.uniqid('cmb', true).'.xlsx';

        $numbered = [];
        foreach ($rows as $offset => $row) {
            $numbered[] = [(string) ($offset + 1), ...array_values($row)];
        }

        $sheetRows = '';
        foreach ([['N°', ...$header], ...$numbered] as $index => $row) {
            $cells = '';
            foreach (array_values($row) as $column => $value) {
                $ref = chr(ord('A') + $column).($index + 1);
                $cells .= sprintf(
                    '<c r="%s" t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
                    $ref,
                    htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                );
            }
            $sheetRows .= sprintf('<row r="%d">%s</row>', $index + 1, $cells);
        }

        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Feuille1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();

        return new UploadedFile($path, basename($path), null, null, true);
    }

    private function studentsUpload(): UploadedFile
    {
        return $this->buildXlsx(
            ['Réf', 'Prénom', 'Nom', 'Téléphone', 'Sexe', 'Date de naissance'],
            [
                ['E001', 'HASNA', 'TIMOUN', '0612345678', 'Femme', '01/01/2000'],
                ['E002', 'AYA', 'IBNOU EDDINE', '0698765432', 'Femme', '26/11/2003'],
            ],
        );
    }

    /** @param array<int, array<int, string>> $rows [réf, étudiant, groupe, statut, date] */
    private function inscriptionsUpload(?array $rows = null): UploadedFile
    {
        return $this->buildXlsx(
            ['Réf', 'Étudiant', 'Groupe', 'Statut', "Date d'inscription"],
            $rows ?? [
                ['I001', 'HASNA TIMOUN', 'Herr Driss 13h', 'Active', '05/01/2026'],
                ['I002', 'AYA IBNOU EDDINE', 'Herr Driss 13h', 'Active', '06/01/2026'],
            ],
        );
    }

    public function test_students_are_imported_then_inscriptions_resolve_against_them(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $this->assertSame(0, Student::query()->count());

        $response = $this->actingAs($user)->post(route('backoffice.import.combine.analyze'), [
            'students_file' => $this->studentsUpload(),
            'inscriptions_files' => ['Active' => $this->inscriptionsUpload()],
            'etablissement_id' => $this->centre->id,
            'statuts' => [],
            'groupe_mapping' => [
                ['label' => 'Herr Driss 13h', 'action' => 'create', 'nom' => 'Herr Driss 13h', 'niveau' => 'A1.1'],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        // Both students were inserted automatically, in the batch's centre.
        $this->assertSame(2, Student::query()->count());
        $this->assertSame(2, Student::query()->where('etablissement_id', $this->centre->id)->count());

        // Two batches: the auto-committed students one, then inscriptions.
        $studentBatch = ImportBatch::query()->where('module', ImportBatch::MODULE_STUDENTS)->firstOrFail();
        $this->assertSame(2, $studentBatch->rows()->where('status', ImportRow::STATUT_INSERE)->count());

        $inscriptionBatch = ImportBatch::query()->where('module', ImportBatch::MODULE_INSCRIPTIONS)->firstOrFail();
        $response->assertRedirect(route('backoffice.import.inscriptions.preview', $inscriptionBatch));

        // The whole point: every inscription row resolved a student from the
        // SAME run — no "étudiant introuvable" conflicts.
        $this->assertSame(2, $inscriptionBatch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->count());
        $this->assertSame(0, $inscriptionBatch->rows()->where('status', ImportRow::STATUT_CONFLIT)->count());
    }

    public function test_one_inscriptions_file_per_statut_is_analyzed_into_a_single_batch(): void
    {
        // The old CRM exports Annulée and Archivée as SEPARATE lists — both
        // are posted (one per checked statut) and land in ONE batch, with
        // the legacy statuts translated and only the checked ones kept.
        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.combine.analyze'), [
            'students_file' => $this->studentsUpload(),
            'inscriptions_files' => [
                'Annulée' => $this->inscriptionsUpload([
                    ['A001', 'HASNA TIMOUN', 'Herr Driss 13h', 'Annulé', '05/01/2026'],
                ]),
                'Changement' => $this->inscriptionsUpload([
                    ['R001', 'AYA IBNOU EDDINE', 'Herr Driss 13h', 'Archive', '06/01/2026'],
                    // An Active row hiding in the "archived" export is ignored.
                    ['R002', 'HASNA TIMOUN', 'Herr Driss 13h', 'Active', '07/01/2026'],
                ]),
            ],
            'etablissement_id' => $this->centre->id,
            'statuts' => ['Annulée', 'Changement'],
            'groupe_mapping' => [
                ['label' => 'Herr Driss 13h', 'action' => 'create', 'nom' => 'Herr Driss 13h', 'niveau' => 'A1.1'],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $batch = ImportBatch::query()->where('module', ImportBatch::MODULE_INSCRIPTIONS)->sole();
        $this->assertStringContainsString(' + ', $batch->original_filename);
        $this->assertSame(3, $batch->total_rows);

        $rows = $batch->rows()->orderBy('source_row_number')->get();
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[0]->status);
        $this->assertSame('Annulée', $rows[0]->raw['statut']);
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[1]->status);
        $this->assertSame('Changement', $rows[1]->raw['statut']);
        $this->assertSame(ImportRow::STATUT_DOUBLON, $rows[2]->status);
        $this->assertSame('statut_filtre', $rows[2]->errors[0]['code']);
    }

    public function test_it_requires_the_inscriptions_files(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.combine.analyze'), [
            'students_file' => $this->studentsUpload(),
            'etablissement_id' => $this->centre->id,
            'groupe_mapping' => [],
        ]);

        $response->assertSessionHasErrors(['inscriptions_files']);
        $this->assertSame(0, ImportBatch::query()->count());
        $this->assertSame(0, Student::query()->count());
    }
}
