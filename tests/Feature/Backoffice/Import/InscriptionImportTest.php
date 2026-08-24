<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class InscriptionImportTest extends TestCase
{
    use RefreshDatabase;

    private const string SAMPLE_FILE = __DIR__.'/../../../../old crm data exemple/liste-inscriptions_31_20260817.xlsx';

    private AnneeScolaire $annee;

    private AnneeScolaire $otherAnnee;

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
        $this->otherAnnee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->otherCentre = Etablissement::factory()->create();
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

    private function sampleUpload(): UploadedFile
    {
        return new UploadedFile(self::SAMPLE_FILE, 'liste-inscriptions.xlsx', null, null, true);
    }

    /**
     * commit() now processes a small chunk per call (progress-bar UX) —
     * loops the JSON endpoint to completion the way the real Preview page's
     * useCommitProgress hook does, and returns the final chunk's response.
     */
    private function commitAllSelected(User $user, ImportBatch $batch, array $rowIds): \Illuminate\Testing\TestResponse
    {
        $guard = 0;

        do {
            $response = $this->actingAs($user)->postJson(route('backoffice.import.inscriptions.commit', $batch), [
                'selected_row_ids' => $rowIds,
            ]);
            $remaining = $response->json('remaining');

            // Without this, a chunk that fails to shrink the eligible set
            // spins forever and the whole suite just stops reporting.
            $this->assertLessThan(
                500,
                ++$guard,
                'commit() stopped making progress — remaining never reached 0.'
            );
        } while ($remaining > 0);

        return $response;
    }

    /** Creates a student whose name matches one of the real sample rows exactly. */
    private function makeStudent(string $prenom, string $nom, ?int $etablissementId = null): Student
    {
        return Student::factory()->create([
            'etablissement_id' => $etablissementId ?? $this->centre->id,
            'prenom' => $prenom,
            'nom' => $nom,
        ]);
    }

    /**
     * Builds a one-off inscriptions xlsx — the shipped sample has no
     * recycled réf., and the recycled-réf. bug can only be reproduced with
     * one.
     *
     * Written with ZipArchive rather than OpenSpout's writer: that writer
     * stages its archive in the Windows temp folder and dies there with
     * "Renaming temporary file failed". A minimal inline-strings workbook is
     * all SheetReader needs.
     *
     * @param  array<int, array<int, string>>  $rows  [réf, étudiant, groupe, statut, date] — the "N°" column is added here
     */
    private function buildUpload(array $rows): UploadedFile
    {
        $dir = storage_path('framework/testing/import');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.uniqid('ins', true).'.xlsx';

        $sheetRows = '';
        // The leading "N°" column is what SheetReader keys the header row
        // off — a sheet without it is not recognised as an export at all.
        $header = ['N°', 'Réf', 'Étudiant', 'Groupe', 'Statut', "Date d'inscription"];
        $numbered = [];
        foreach ($rows as $offset => $row) {
            $numbered[] = [(string) ($offset + 1), ...array_values($row)];
        }

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

        return new UploadedFile($path, 'liste-inscriptions.xlsx', null, null, true);
    }

    /**
     * The old CRM reuses one réf. for genuinely different inscriptions.
     * Those rows are NOT duplicates — dedupe must fall back to
     * étudiant + groupe + date, and the second one is stored under a
     * suffixed legacy_ref so the unique index accepts it.
     */
    public function test_a_recycled_reference_on_a_different_student_is_imported_not_skipped(): void
    {
        $this->makeStudent('CHAYMA', 'AAZRI');
        $this->makeStudent('HASNA', 'BARGAZ');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $group->frais()->sync([Frais::first()->id => ['montant' => 300, 'date_echeance' => '2026-09-01']]);

        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->buildUpload([
                ['337SL126', 'CHAYMA AAZRI', 'Herr Driss 13h', 'Active', '05/01/2026'],
                ['337SL126', 'HASNA BARGAZ', 'Herr Driss 13h', 'Active', '06/01/2026'],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [
                ['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $batch = ImportBatch::query()->firstOrFail();
        $rows = $batch->rows()->orderBy('source_row_number')->get();

        $this->assertCount(2, $rows);
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[0]->status);
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[1]->status, 'A recycled réf. on a different student must not be treated as a doublon.');
        $this->assertSame('337SL126', $rows[0]->legacy_ref);
        $this->assertSame('337SL126#2', $rows[1]->legacy_ref, 'The reused réf. must be disambiguated for the unique index.');

        $this->commitAllSelected($user, $batch, $rows->pluck('id')->all());

        $this->assertSame(2, Inscription::query()->count());
    }

    /** The same row twice — same réf. AND same étudiant/groupe/date — IS a doublon. */
    public function test_an_identical_row_repeated_under_the_same_reference_is_still_skipped(): void
    {
        $this->makeStudent('CHAYMA', 'AAZRI');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $group->frais()->sync([Frais::first()->id => ['montant' => 300, 'date_echeance' => '2026-09-01']]);

        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->buildUpload([
                ['337SL126', 'CHAYMA AAZRI', 'Herr Driss 13h', 'Active', '05/01/2026'],
                ['337SL126', 'CHAYMA AAZRI', 'Herr Driss 13h', 'Active', '05/01/2026'],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [
                ['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id],
            ],
        ])->assertSessionHasNoErrors();

        $rows = ImportBatch::query()->firstOrFail()->rows()->orderBy('source_row_number')->get();

        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[0]->status);
        $this->assertSame(ImportRow::STATUT_DOUBLON, $rows[1]->status);
    }

    public function test_peek_groupes_lists_the_centres_groups_from_every_year_labeled(): void
    {
        $user = $this->userWith('import.view', 'import.create');
        $group = Group::factory()->create([
            'nom' => 'GROUP 19H SEPTEMBRE',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        // A same-named group from another année IS offered, flagged with its
        // year — mapping it re-affects it to the selected année (the fix for
        // data split across two years). Another CENTRE's group never appears.
        $otherYearGroup = Group::factory()->create([
            'nom' => 'GROUP 19H SEPTEMBRE',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->otherAnnee->id,
        ]);
        Group::factory()->create([
            'nom' => 'GROUP 19H SEPTEMBRE',
            'etablissement_id' => $this->otherCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $response = $this->actingAs($user)->post(route('backoffice.import.inscriptions.peek-groupes'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
        ]);

        $response->assertOk();
        $json = $response->json();

        $this->assertContains('Herr Driss 13h', $json['groupeLabels']);
        $this->assertContains('GROUP 19H SEPTEMBRE', $json['groupeLabels']);
        $this->assertCount(2, $json['existingGroups']);

        $byId = collect($json['existingGroups'])->keyBy('id');
        $this->assertFalse($byId[$group->id]['horsAnnee']);
        $this->assertTrue($byId[$otherYearGroup->id]['horsAnnee']);
        $this->assertSame('2026/2027', $byId[$otherYearGroup->id]['anneeNom']);
    }

    public function test_analyze_maps_group_and_resolves_students_by_normalized_full_name(): void
    {
        $this->makeStudent('HASNA', 'TIMOUN');
        $this->makeStudent('AYA', 'IBNOU EDDINE');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $group->frais()->sync([Frais::first()->id => ['montant' => 300, 'date_echeance' => '2026-09-01']]);

        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [
                ['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id],
            ],
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $response->assertRedirect(route('backoffice.import.inscriptions.preview', $batch));

        $row = $batch->rows()->where('legacy_ref', '373SL126')->firstOrFail();
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $row->status);
        $this->assertSame($group->id, $row->resolution['group_id']);
    }

    public function test_unmapped_groupe_label_is_erreur(): void
    {
        $this->makeStudent('HASNA', 'TIMOUN');
        $user = $this->userWith('import.view', 'import.create');
        $otherGroup = Group::factory()->create([
            'nom' => 'GROUP 19H SEPTEMBRE',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        // "Herr Driss 13h" (row 373SL126's group) is deliberately left out
        // of the submitted mapping — only a different label is mapped.
        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [
                ['label' => 'GROUP 19H SEPTEMBRE', 'action' => 'map', 'group_id' => $otherGroup->id],
            ],
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', '373SL126')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('group_not_mapped', $row->errors[0]['code']);
    }

    public function test_create_new_group_syncs_full_active_frais_catalog_not_a_subset(): void
    {
        Frais::create(['nom' => 'Second frais actif', 'statut' => Frais::STATUT_ACTIF]);
        Frais::create(['nom' => 'Frais inactif', 'statut' => Frais::STATUT_INACTIF]);
        $this->makeStudent('HASNA', 'TIMOUN');
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [
                ['label' => 'Herr Driss 13h', 'action' => 'create', 'nom' => 'Herr Driss 13h', 'niveau' => 'A1.1'],
            ],
        ]);

        $group = Group::query()->where('nom', 'Herr Driss 13h')->firstOrFail();

        $this->assertSame($this->centre->id, $group->etablissement_id);
        $this->assertSame($this->annee->id, $group->annee_scolaire_id);
        $this->assertSame(2, $group->frais()->where('frais.statut', Frais::STATUT_ACTIF)->count());
        $this->assertSame(0, $group->frais()->where('frais.statut', Frais::STATUT_INACTIF)->count());
    }

    public function test_commit_creates_fee_lines_from_groups_own_group_frais_never_invented(): void
    {
        $this->makeStudent('HASNA', 'TIMOUN');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $frais = Frais::first();
        $group->frais()->sync([$frais->id => ['montant' => 300, 'date_echeance' => '2026-09-01']]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [
                ['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id],
            ],
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', '373SL126')->firstOrFail();

        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $inscription = Inscription::query()->where('legacy_ref', '373SL126')->firstOrFail();
        $this->assertSame(1, $inscription->fees()->count());
        $fee = $inscription->fees()->firstOrFail();
        $this->assertSame($frais->id, $fee->frais_id);
        $this->assertSame('300.00', (string) $fee->montant);
        $this->assertSame('300.00', (string) $inscription->montant_total);
        // date_debut/date_fin come from the group's own training period, never the xlsx.
        $this->assertSame($group->date_debut_formation?->toDateString(), $inscription->date_debut?->toDateString());
    }

    public function test_a_group_created_by_the_import_inherits_the_catalog_amounts(): void
    {
        // An imported inscription must end up looking exactly like one
        // created through the UI. Pinning importer-created groups to
        // montant 0 meant every imported inscription carried empty fee
        // lines, and "Enregistrer un paiement" then listed nothing at all
        // (its query keeps only fees whose reste is above zero).
        $frais = Frais::query()->create([
            'nom' => 'Frais de Septembre',
            'montant_defaut' => 1300,
            'statut' => Frais::STATUT_ACTIF,
        ]);
        $inscriptionFrais = Frais::query()->create([
            'nom' => "Frais d'inscription A1/A2/B1",
            'montant_defaut' => 300,
            'statut' => Frais::STATUT_ACTIF,
        ]);

        $group = app(\App\Services\Import\InscriptionImporter::class)->createGroupWithFullCatalogSync([
            'nom' => 'GROUPE IMPORTÉ',
            'niveau' => 'A1',
            'statut' => Group::STATUT_EN_FORMATION,
            'date_debut_formation' => '2025-09-23',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $pivots = $group->fresh()->frais->keyBy('id');

        // The catalog's amount, not zero.
        $this->assertSame('1300.00', (string) $pivots[$frais->id]->pivot->montant);
        $this->assertSame('300.00', (string) $pivots[$inscriptionFrais->id]->pivot->montant);

        // Septembre = month 9, day from the group's start, year = today.
        $this->assertSame(
            now()->format('Y').'-09-23',
            $pivots[$frais->id]->pivot->date_echeance,
        );

        // A fee naming no month keeps no derived due date.
        $this->assertNull($pivots[$inscriptionFrais->id]->pivot->date_echeance);
    }

    public function test_legacy_archivee_statut_becomes_changement(): void
    {
        // The old CRM and this app agree on every inscription statut EXCEPT
        // one: their "Archivée" is our "Changement". That single rename is
        // the entire difference between the two vocabularies.
        $map = (new \ReflectionClassConstant(\App\Services\Import\InscriptionImporter::class, 'STATUT_MAP'))->getValue();

        $this->assertSame(Inscription::STATUT_CHANGEMENT, $map['Archivée'] ?? null);

        // Everything else must pass through untouched — mapping any other
        // statut would rewrite history rather than translate it.
        foreach ([Inscription::STATUT_ACTIVE, Inscription::STATUT_ANNULEE, Inscription::STATUT_CHANGEMENT, Inscription::STATUT_EXPIREE] as $statut) {
            $this->assertArrayNotHasKey($statut, $map, "{$statut} must not be translated.");
        }
    }

    public function test_annulee_and_changement_inscriptions_are_accepted_by_the_validator(): void
    {
        // These are legitimate historical statuts — an import must accept
        // them, not reject the row.
        $validator = new \App\Services\Import\ImportValidator();

        foreach ([Inscription::STATUT_ACTIVE, Inscription::STATUT_ANNULEE, Inscription::STATUT_CHANGEMENT] as $statut) {
            $errors = $validator->validateInscription([
                'etudiant' => 'HASNA TIMOUN',
                'groupe' => 'Herr Driss 13h',
                'statut' => $statut,
                'date_inscription' => '2026-07-01',
            ]);

            $statutErrors = array_filter($errors, fn ($e) => $e['field'] === 'statut');
            $this->assertSame([], $statutErrors, "Statut {$statut} must be accepted.");
        }
    }

    public function test_a_skipped_row_records_why_it_was_skipped(): void
    {
        // "Ignorées: 4" with nothing behind it gave the operator no way to
        // tell a harmless re-import from a real row silently dropped.
        $this->makeStudent('HASNA', 'TIMOUN');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $mapping = [['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id]];
        $payload = [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'groupe_mapping' => $mapping,
        ];

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), $payload);
        $first = ImportBatch::query()->firstOrFail();
        $row = $first->rows()->where('legacy_ref', '373SL126')->firstOrFail();
        $this->commitAllSelected($user, $first, [$row->id]);

        // Re-uploading the same file makes that row a duplicate.
        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            ...$payload, 'file' => $this->sampleUpload(),
        ]);
        $second = ImportBatch::query()->where('id', '!=', $first->id)->firstOrFail();
        $skipped = $second->rows()->where('legacy_ref', '373SL126')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_DOUBLON, $skipped->status);
        $this->assertNotEmpty($skipped->errors, 'A skipped row must record why it was skipped.');
        $this->assertSame('already_in_database', $skipped->errors[0]['code']);
        $this->assertStringContainsString('373SL126', $skipped->errors[0]['message']);
        // A réf. alone is unreadable — the operator has to see who it is.
        $this->assertStringContainsString('TIMOUN', $skipped->errors[0]['message']);
        $this->assertNotSame('', (string) ($skipped->raw['etudiant'] ?? ''));
    }

    public function test_result_screen_lists_skipped_rows_with_their_reason(): void
    {
        $this->makeStudent('HASNA', 'TIMOUN');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $payload = [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id]],
        ];

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), $payload);
        $first = ImportBatch::query()->firstOrFail();
        $this->commitAllSelected($user, $first, [$first->rows()->where('legacy_ref', '373SL126')->firstOrFail()->id]);

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            ...$payload, 'file' => $this->sampleUpload(),
        ]);
        $second = ImportBatch::query()->where('id', '!=', $first->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('backoffice.import.inscriptions.result', $second))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Inscriptions/Result')
                ->where('skippedRows.total', fn ($total) => $total > 0)
                ->has('skippedRows.data.0.errors.0.message')
                // The table's Étudiant column reads this off raw.
                ->has('skippedRows.data.0.raw.etudiant')
            );
    }

    public function test_reupload_is_idempotent(): void
    {
        $this->makeStudent('HASNA', 'TIMOUN');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $mapping = [['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id]];

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'groupe_mapping' => $mapping,
        ]);
        $firstBatch = ImportBatch::query()->firstOrFail();
        $row = $firstBatch->rows()->where('legacy_ref', '373SL126')->firstOrFail();
        $this->commitAllSelected($user, $firstBatch, [$row->id]);
        $this->assertSame(1, Inscription::query()->where('legacy_ref', '373SL126')->count());

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'groupe_mapping' => $mapping,
        ]);
        $secondBatch = ImportBatch::query()->where('id', '!=', $firstBatch->id)->firstOrFail();
        $secondRow = $secondBatch->rows()->where('legacy_ref', '373SL126')->firstOrFail();
        $this->assertSame(ImportRow::STATUT_DOUBLON, $secondRow->status);

        $this->commitAllSelected($user, $secondBatch, $secondBatch->rows()->pluck('id')->all());

        $this->assertSame(1, Inscription::query()->where('legacy_ref', '373SL126')->count());
    }

    public function test_same_named_student_in_a_different_centre_never_resolves(): void
    {
        $this->makeStudent('HASNA', 'TIMOUN', $this->otherCentre->id);
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id]],
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', '373SL126')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('student_not_found', $row->errors[0]['code']);
    }

    public function test_analyze_ignores_a_posted_centre_when_the_context_locks_one(): void
    {
        // Non-global single-centre user: the active context IS their centre,
        // so a hostile/stale etablissement_id in the request is ignored — the
        // batch can only ever land in the centre they actually work in.
        $user = User::factory()->create();
        $user->givePermissionTo('import.view', 'import.create');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($user->fresh())->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->otherCentre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [],
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $this->assertSame($this->centre->id, $batch->etablissement_id);
        $this->assertSame($this->annee->id, $batch->annee_scolaire_id);
    }

    public function test_mapping_a_group_from_another_year_reaffects_it_to_the_selected_year(): void
    {
        // The "half in one year, half in the other" split (24/08/2026): data
        // previously imported under the WRONG année. Re-importing under the
        // right one and mapping the file's label onto that existing group
        // must MOVE the group + its inscriptions to the selected year and
        // recognize the rows as already imported — never duplicate them.
        $student = $this->makeStudent('HASNA', 'TIMOUN');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->otherAnnee->id,
        ]);
        $existing = Inscription::create([
            'reference' => 'INS-EXIST-1',
            'legacy_ref' => '111AB1',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->otherAnnee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-01-15',
        ]);

        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->buildUpload([
                ['111AB1', 'HASNA TIMOUN', 'Herr Driss 13h', 'Active', '15/01/2026'],
            ]),
            'etablissement_id' => $this->centre->id,
            'groupe_mapping' => [['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id]],
        ]);

        // The mapped group and its existing inscription now live in the
        // ACTIVE année — nothing left behind under the old one.
        $this->assertSame($this->annee->id, $group->fresh()->annee_scolaire_id);
        $this->assertSame($this->annee->id, $existing->fresh()->annee_scolaire_id);

        // And the file's row is a recognized duplicate, not a re-insert.
        $batch = ImportBatch::query()->firstOrFail();
        $this->assertSame(ImportRow::STATUT_DOUBLON, $batch->rows()->firstOrFail()->status);
        $this->assertSame(1, Inscription::query()->count());
    }

    public function test_mapping_refuses_a_group_from_another_centre(): void
    {
        $foreignGroup = Group::factory()->create([
            'nom' => 'Groupe étranger',
            'etablissement_id' => $this->otherCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->buildUpload([
                ['222CD2', 'HASNA TIMOUN', 'Groupe étranger', 'Active', '15/01/2026'],
            ]),
            'etablissement_id' => $this->centre->id,
            'groupe_mapping' => [['label' => 'Groupe étranger', 'action' => 'map', 'group_id' => $foreignGroup->id]],
        ]);

        $response->assertSessionHasErrors(['groupe_mapping']);
        $this->assertSame(0, ImportBatch::query()->count());
        // The foreign group's year is untouched — no cross-centre move.
        $this->assertSame($this->annee->id, $foreignGroup->fresh()->annee_scolaire_id);
    }

    public function test_preview_and_result_pages_render(): void
    {
        $this->makeStudent('HASNA', 'TIMOUN');
        $group = Group::factory()->create([
            'nom' => 'Herr Driss 13h', 'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [['label' => 'Herr Driss 13h', 'action' => 'map', 'group_id' => $group->id]],
        ]);
        $batch = ImportBatch::query()->firstOrFail();

        $this->actingAs($user)->get(route('backoffice.import.inscriptions.preview', $batch))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Inscriptions/Preview')
                ->where('batch.id', $batch->id));

        $this->actingAs($user)->get(route('backoffice.import.inscriptions.result', $batch))
            ->assertInertia(fn (Assert $page) => $page->component('Backoffice/Import/Inscriptions/Result'));
    }
}
