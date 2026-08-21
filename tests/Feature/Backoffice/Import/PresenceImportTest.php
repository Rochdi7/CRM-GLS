<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class PresenceImportTest extends TestCase
{
    use RefreshDatabase;

    private const string SAMPLE_FILE = __DIR__.'/../../../../old crm data exemple/registre-presences_45_20260821.xlsx';

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Etablissement $otherCentre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->otherCentre = Etablissement::factory()->create();
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

    private function sampleUpload(): UploadedFile
    {
        return new UploadedFile(self::SAMPLE_FILE, 'registre-presences.xlsx', null, null, true);
    }

    private function makeStudent(string $prenom, string $nom, ?int $etablissementId = null): Student
    {
        return Student::factory()->create([
            'etablissement_id' => $etablissementId ?? $this->centre->id,
            'prenom' => $prenom,
            'nom' => $nom,
        ]);
    }

    private function makeGroup(string $nom): Group
    {
        return Group::factory()->create([
            'nom' => $nom,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    /** Loops the chunked commit endpoint the way the Preview page's useCommitProgress hook does. */
    private function commitAllSelected(User $user, ImportBatch $batch, array $rowIds): \Illuminate\Testing\TestResponse
    {
        $guard = 0;

        do {
            $response = $this->actingAs($user)->postJson(route('backoffice.import.presences.commit', $batch), [
                'selected_row_ids' => $rowIds,
            ]);
            $remaining = $response->json('remaining');

            $this->assertLessThan(
                500,
                ++$guard,
                'commit() stopped making progress — remaining never reached 0.'
            );
        } while ($remaining > 0);

        return $response;
    }

    /**
     * Builds a one-off "Registre des présences" xlsx.
     *
     * Written with ZipArchive rather than OpenSpout's writer for the same
     * reason as the other import tests: that writer stages its archive in
     * the Windows temp folder and dies there with "Renaming temporary file
     * failed". A minimal inline-strings workbook is all SheetReader needs.
     *
     * @param  array<int, array<int, string>>  $rows  [élève, groupe, matière, date, horaire, statut, enseignant] — the "N°" column is added here
     */
    private function buildUpload(array $rows): UploadedFile
    {
        $dir = storage_path('framework/testing/import');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.uniqid('pres', true).'.xlsx';

        // The leading "N°" column is what SheetReader keys the header row
        // off; "Élève" is what distinguishes this export's header from the
        // réf-bearing ones.
        $header = ['N°', 'Élève', 'Groupe', 'Matière', 'Date', 'Horaire', 'Statut', 'Enseignant'];
        $numbered = [];
        foreach ($rows as $offset => $row) {
            $numbered[] = [(string) ($offset + 1), ...array_values($row)];
        }

        $sheetRows = '';
        foreach ([$header, ...$numbered] as $index => $row) {
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
            .'<sheets><sheet name="Registre des présences" sheetId="1" r:id="rId1"/></sheets></workbook>');
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

        return new UploadedFile($path, 'registre-presences.xlsx', null, null, true);
    }

    /**
     * The core promise of this module: one séance per (groupe, date,
     * horaire) whatever the number of lines, and one présence per line.
     */
    public function test_it_derives_one_seance_per_group_date_and_time_and_one_presence_per_row(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $this->makeStudent('IMANE', 'AGOUNI');
        $group = $this->makeGroup('Herr Driss 10H');
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', 'MoulayDriss Kadiri'],
                ['IMANE AGOUNI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Absent', 'MoulayDriss Kadiri'],
                // Same group, next day => a SECOND séance.
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '23/07/2026', '10:00 - 12:30', 'Présent', 'MoulayDriss Kadiri'],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $batch = ImportBatch::query()->firstOrFail();
        $rows = $batch->rows()->orderBy('source_row_number')->get();
        $this->assertCount(3, $rows);
        $rows->each(fn (ImportRow $row) => $this->assertSame(ImportRow::STATUT_NOUVEAU, $row->status));

        $this->commitAllSelected($user, $batch, $rows->pluck('id')->all());

        $this->assertSame(2, Seance::query()->count(), 'Two distinct dates must yield exactly two séances.');
        $this->assertSame(3, Presence::query()->count());

        $seance = Seance::query()->where('date_seance', '2026-07-22')->firstOrFail();
        $this->assertSame($group->id, $seance->group_id);
        $this->assertSame('10:00:00', substr((string) $seance->heure_debut, 0, 8));
        $this->assertSame('12:30:00', substr((string) $seance->heure_fin, 0, 8));
        $this->assertSame($this->centre->id, $seance->etablissement_id);
        $this->assertSame($this->annee->id, $seance->annee_scolaire_id);
        // A register only ever lists sessions that actually happened.
        $this->assertSame(Seance::STATUT_EFFECTUEE, $seance->statut);
        $this->assertSame(2, $seance->presences()->count());
    }

    /** Two different sessions of the SAME day are distinct séances, not one merged séance. */
    public function test_two_time_slots_on_the_same_day_are_two_seances(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $group = $this->makeGroup('Herr Driss 10H');
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', ''],
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '13:00 - 15:00', 'Absent', ''],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors();

        $batch = ImportBatch::query()->firstOrFail();
        $this->commitAllSelected($user, $batch, $batch->rows()->pluck('id')->all());

        $this->assertSame(2, Seance::query()->count());
        $this->assertSame(2, Presence::query()->count());
    }

    /**
     * A séance the app already knows about (created by hand or generated
     * from a créneau) is filled in, never duplicated — and never rewritten.
     */
    public function test_an_existing_seance_is_reused_and_left_untouched(): void
    {
        $student = $this->makeStudent('HAMZA', 'AITHAMMANI');
        $group = $this->makeGroup('Herr Driss 10H');
        $user = $this->userWith('import.view', 'import.create');

        $existing = Seance::create([
            'group_id' => $group->id,
            'date_seance' => '2026-07-22',
            'heure_debut' => '10:00:00',
            'heure_fin' => '12:30:00',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
            'note' => 'Note saisie à la main',
        ]);

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', ''],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors();

        $batch = ImportBatch::query()->firstOrFail();
        $this->commitAllSelected($user, $batch, $batch->rows()->pluck('id')->all());

        $this->assertSame(1, Seance::query()->count(), 'The existing séance must be reused, not duplicated.');

        $existing->refresh();
        $this->assertSame(Seance::STATUT_PREVUE, $existing->statut, "The import must never rewrite an existing séance's statut.");
        $this->assertSame('Note saisie à la main', $existing->note);

        $this->assertDatabaseHas('presences', [
            'seance_id' => $existing->id,
            'student_id' => $student->id,
            'statut' => Presence::STATUT_PRESENT,
        ]);
    }

    /** Re-importing the same file must insert nothing the second time. */
    public function test_reupload_is_idempotent(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $group = $this->makeGroup('Herr Driss 10H');
        $user = $this->userWith('import.view', 'import.create');
        $mapping = [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]];
        $rows = [['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', '']];

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload($rows), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'groupe_mapping' => $mapping,
        ]);
        $firstBatch = ImportBatch::query()->firstOrFail();
        $this->commitAllSelected($user, $firstBatch, $firstBatch->rows()->pluck('id')->all());
        $this->assertSame(1, Presence::query()->count());

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload($rows), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'groupe_mapping' => $mapping,
        ]);
        $secondBatch = ImportBatch::query()->where('id', '!=', $firstBatch->id)->firstOrFail();
        $secondRow = $secondBatch->rows()->firstOrFail();

        $this->assertSame(ImportRow::STATUT_DOUBLON, $secondRow->status);
        $this->assertNotEmpty($secondRow->errors, 'A skipped row must carry the reason it was skipped.');

        $this->commitAllSelected($user, $secondBatch, $secondBatch->rows()->pluck('id')->all());

        $this->assertSame(1, Presence::query()->count(), 'A re-import must insert nothing.');
        $this->assertSame(1, Seance::query()->count(), 'A re-import must not create a second séance either.');
    }

    /** The same line twice inside ONE file is a doublon too. */
    public function test_a_repeated_line_within_the_file_is_a_doublon(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $group = $this->makeGroup('Herr Driss 10H');
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', ''],
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Absent', ''],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors();

        $batch = ImportBatch::query()->firstOrFail();
        $rows = $batch->rows()->orderBy('source_row_number')->get();

        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[0]->status);
        $this->assertSame(ImportRow::STATUT_DOUBLON, $rows[1]->status);

        $this->commitAllSelected($user, $batch, $rows->pluck('id')->all());
        $this->assertSame(1, Presence::query()->count());
    }

    /**
     * The export writes a literal "-" when the roll call was never filled
     * in. That line must surface as a visible ERREUR, never as a silently
     * defaulted "Présent".
     */
    public function test_a_row_without_a_status_is_an_error_not_a_default(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $group = $this->makeGroup('Herr Driss 10H');
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', '-', ''],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors();

        $row = ImportBatch::query()->firstOrFail()->rows()->firstOrFail();

        $this->assertSame(ImportRow::STATUT_ERREUR, $row->status);
        $this->assertSame('statut', $row->errors[0]['field']);
        $this->assertSame(0, Presence::query()->count());
    }

    /** An élève who does not exist in this centre is a CONFLIT, never an invented student. */
    public function test_an_unknown_student_is_a_conflict_and_is_never_created(): void
    {
        $group = $this->makeGroup('Herr Driss 10H');
        // Same name, WRONG centre — must not resolve.
        $this->makeStudent('HAMZA', 'AITHAMMANI', $this->otherCentre->id);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', ''],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors();

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('student_not_found', $row->errors[0]['code']);

        // Committing it anyway must fail with a readable reason, not create anything.
        $this->commitAllSelected($user, $batch, [$row->id]);

        $this->assertSame(0, Presence::query()->count());
        $this->assertSame(0, Seance::query()->count());
        $this->assertSame(1, Student::query()->count(), 'The import must never create a student.');
        $this->assertSame(ImportRow::STATUT_ECHEC_COMMIT, $row->fresh()->status);
    }

    /** A group mapped to another centre's group is refused — the batch's scope is immutable. */
    public function test_a_group_outside_the_batch_scope_is_refused(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $foreignGroup = Group::factory()->create([
            'nom' => 'Herr Driss 10H',
            'etablissement_id' => $this->otherCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', ''],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $foreignGroup->id]],
        ])->assertSessionHasNoErrors();

        $row = ImportBatch::query()->firstOrFail()->rows()->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('group_out_of_scope', $row->errors[0]['code']);
        $this->assertSame(0, Seance::query()->count());
    }

    /**
     * The teacher column is advisory: an unmatched name must not block the
     * line, and a matched one lands on the séance.
     */
    public function test_the_teacher_column_is_advisory(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $this->makeStudent('IMANE', 'AGOUNI');
        $group = $this->makeGroup('Herr Driss 10H');
        $enseignant = Employee::factory()->create([
            'prenom' => 'MoulayDriss',
            'nom' => 'Kadiri',
            'etablissement_id' => $this->centre->id,
        ]);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->buildUpload([
                ['HAMZA AITHAMMANI', 'Herr Driss 10H', 'allemand', '22/07/2026', '10:00 - 12:30', 'Présent', 'MoulayDriss Kadiri'],
                ['IMANE AGOUNI', 'Herr Driss 10H', 'allemand', '23/07/2026', '10:00 - 12:30', 'Présent', 'Prof Inconnu'],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors();

        $batch = ImportBatch::query()->firstOrFail();
        $rows = $batch->rows()->orderBy('source_row_number')->get();
        $rows->each(fn (ImportRow $row) => $this->assertSame(
            ImportRow::STATUT_NOUVEAU,
            $row->status,
            'An unknown teacher must never block a roll-call line.'
        ));

        $this->commitAllSelected($user, $batch, $rows->pluck('id')->all());

        $matched = Seance::query()->where('date_seance', '2026-07-22')->firstOrFail();
        $this->assertSame($enseignant->id, $matched->enseignant_id);

        // Unknown teacher => falls back to the group's own teacher rather
        // than inventing an employee.
        $unmatched = Seance::query()->where('date_seance', '2026-07-23')->firstOrFail();
        $this->assertSame($group->enseignant_id, $unmatched->enseignant_id);
    }

    /** The real 960-line export parses end to end without a header or footer surprise. */
    public function test_it_reads_the_real_sample_export(): void
    {
        $this->makeStudent('HAMZA', 'AITHAMMANI');
        $group = $this->makeGroup('Herr Driss 10H');
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.presences.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 10H', 'action' => 'map', 'group_id' => $group->id]],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $batch = ImportBatch::query()->firstOrFail();

        // 967 sheet rows minus the 6 metadata rows and the header row.
        $this->assertSame(960, $batch->total_rows);

        // The one mapped group's lines resolve for the one student that exists.
        $this->assertGreaterThan(0, $batch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->count());
        // Every other group was left unmapped => CONFLIT, never a silent drop.
        $this->assertGreaterThan(0, $batch->rows()->where('status', ImportRow::STATUT_CONFLIT)->count());
        $this->assertSame(960, $batch->rows()->count());
    }

    public function test_it_requires_the_import_permission(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($user->fresh())
            ->get(route('backoffice.import.presences.create'))
            ->assertForbidden();
    }
}
