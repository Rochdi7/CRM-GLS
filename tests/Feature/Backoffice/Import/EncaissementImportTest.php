<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class EncaissementImportTest extends TestCase
{
    use RefreshDatabase;

    private const string SAMPLE_FILE = __DIR__.'/../../../../old crm data exemple/liste-paiements_19_20260817.xlsx';

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Employee $operatorEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        // EmployeeObserver already provisions exactly one caisse per employee
        // (CaisseProvisioner). Creating a second one here gave the operator
        // TWO tills, so the assertions below — which read the balance back
        // with firstOrFail() — picked whichever row Postgres happened to
        // return first and failed non-deterministically depending on test
        // order. Use the auto-provisioned till instead of making another.
        $this->operatorEmployee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
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
        return new UploadedFile(self::SAMPLE_FILE, 'liste-paiements.xlsx', null, null, true);
    }

    /**
     * Sets up a student with an active inscription carrying a fee line
     * whose name matches the real sample row's Frais label.
     */
    private function studentWithActiveFee(string $prenom, string $nom, string $feeNom, float $montant = 1000): Student
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id, 'prenom' => $prenom, 'nom' => $nom]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-07-01',
        ]);
        InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => $feeNom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return $student;
    }

    /**
     * Minimal inline-strings payments workbook (same ZipArchive approach as
     * InscriptionImportTest::buildUpload — OpenSpout's writer dies on the
     * Windows temp folder). The leading "N°" column keys header detection.
     *
     * @param  array<int, array<int, string>>  $rows  [réf, payeur, type, montant, méthode, frais, date, opérateur]
     */
    private function buildUpload(array $rows): UploadedFile
    {
        $dir = storage_path('framework/testing/import');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.uniqid('enc', true).'.xlsx';
        $header = ['N°', 'Réf.', 'Élève / Payeur', 'Type', 'Montant', 'Méthode', 'Frais', 'Date', 'Opérateur'];

        $numbered = [];
        foreach ($rows as $offset => $row) {
            $numbered[] = [(string) ($offset + 1), ...array_values($row)];
        }

        $sheetRows = '';
        foreach ([$header, ...$numbered] as $index => $row) {
            $cells = '';
            foreach (array_values($row) as $column => $value) {
                $cells .= sprintf(
                    '<c r="%s" t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
                    chr(ord('A') + $column).($index + 1),
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

    public function test_a_messy_payer_name_resolves_when_exactly_one_student_matches(): void
    {
        // Real Marrakech file, 24/08/2026: 58 of 76 conflicts were payer
        // cells that were not a clean doubled name. Written once, tripled,
        // halves swapped, or a typo in the copy — one real student matches
        // ⇒ resolved. A same-name twin or no match ⇒ still a conflict.
        $this->studentWithActiveFee('AHMED', 'AMIMI', 'Frais test');
        $this->studentWithActiveFee('AYA', 'ZAHIR', 'Frais test');
        $this->studentWithActiveFee('JENNATE', 'FIRDAOUS', 'Frais test');
        $this->studentWithActiveFee('AMMAR', 'OUACHOUCH', 'Frais test');
        // Twins: two students named SALWA EBBOUATTI — never auto-picked.
        $this->studentWithActiveFee('SALWA', 'EBBOUATTI', 'Frais test');
        $this->studentWithActiveFee('SALWA', 'EBBOUATTI', 'Frais test');

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->buildUpload([
                ['P1', 'AHMED AMIMI', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'mustapha'],
                ['P2', 'AYA ZAHIR ZAHIR ZAHIR', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'mustapha'],
                ['P3', 'JENNATE FIRDAOUS FIRDAOUS JENNATE', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'mustapha'],
                ['P4', 'AMMAR OUACHOCH AMMAR OUACHOUCH', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'mustapha'],
                ['P5', 'SALWA EBBOUATTI EBBOUATTI', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'mustapha'],
                ['P6', 'PERSONNE INCONNUE INCONNU', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'mustapha'],
            ]),
            'etablissement_id' => $this->centre->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ])->assertSessionHasNoErrors();

        $rows = ImportBatch::query()->firstOrFail()->rows()->orderBy('source_row_number')->get()->keyBy('legacy_ref');

        foreach (['P1', 'P2', 'P3', 'P4'] as $ref) {
            $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[$ref]->status, $ref.': '.json_encode($rows[$ref]->errors));
        }
        $this->assertSame('aya zahir', $rows['P2']->raw['payeur']);

        $this->assertSame(ImportRow::STATUT_CONFLIT, $rows['P5']->status);
        $this->assertSame('payer_name_ambiguous', $rows['P5']->errors[0]['code']);
        $this->assertSame(ImportRow::STATUT_CONFLIT, $rows['P6']->status);
    }

    private function defaultOperateurMapping(): array
    {
        return [
            ['label' => 'mustapha', 'employee_id' => $this->operatorEmployee->id],
            ['label' => 'latifa', 'employee_id' => $this->operatorEmployee->id],
        ];
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
            $response = $this->actingAs($user)->postJson(route('backoffice.import.encaissements.commit', $batch), [
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

    public function test_peek_operateurs_lists_distinct_labels_and_employees_scoped_to_centre(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.encaissements.peek-operateurs'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['mustapha', 'latifa'], $response->json('operateurLabels'));
    }

    public function test_peek_operateurs_includes_employees_of_other_centres_when_they_have_global_access(): void
    {
        $otherCentre = Etablissement::factory()->create();

        // A direction account: attached to ANOTHER centre, but allowed to
        // work on every one. Such an account signs payments in every branch,
        // so it must be offered in this centre's Opérateur -> Employé mapping.
        $directionUser = User::factory()->create();
        $directionUser->assignRole(Role::SUPER_ADMIN);
        $direction = Employee::factory()->create([
            'user_id' => $directionUser->id,
            'etablissement_id' => $otherCentre->id,
            'prenom' => 'Mohammed',
            'nom' => 'Rafik',
        ]);

        // A plain employee of another centre stays out of the list.
        $outsider = Employee::factory()->create(['etablissement_id' => $otherCentre->id]);

        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.encaissements.peek-operateurs'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $response->assertOk();
        $ids = array_column($response->json('employees'), 'id');

        $this->assertContains($direction->id, $ids);
        $this->assertContains($this->operatorEmployee->id, $ids);
        $this->assertNotContains($outsider->id, $ids);
    }

    public function test_footer_rows_excluded_analyzes_exactly_64_rows(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $this->assertSame(64, $batch->total_rows);
    }

    public function test_doubled_payer_name_is_collapsed_and_student_resolved(): void
    {
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame('ABDERRAHMANE BOUGMA', $row->raw['payeur']);
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $row->status);
        // Real sample row P4255 (ABDERRAHMANE BOUGMA) is "50 Dh".
        $this->assertSame('50.00', $row->raw['montant']);
    }

    public function test_mismatched_doubled_name_row_p4226_is_conflit_not_crashed(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4226')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('younes TALIB HACHIM TALIBi', $row->raw['payeur']);
        $this->assertSame('younes TALIB', $row->resolution['payer_guess']);
    }

    public function test_virement_bancaire_maps_to_virement_not_stored_literally(): void
    {
        $this->studentWithActiveFee('WIJDANE', 'IDRISSI JOUICHA', 'Frais de Septembre', 1300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4247')->firstOrFail();

        $this->assertSame(Encaissement::METHODE_VIREMENT, $row->raw['methode']);
        $this->assertNotSame('Virement bancaire', $row->raw['methode']);
    }

    public function test_unresolved_frais_is_conflit_never_creates_a_frais_row(): void
    {
        // A student with NO inscription at all: nothing to attach the
        // payment to. (A student WITH an inscription now attaches loosely
        // to its first unpaid line — covered by
        // test_a_payment_attaches_to_the_active_inscription_....)
        Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $fraisCountBefore = Frais::query()->count();
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('no_inscription', $row->errors[0]['code']);
        $this->assertSame($fraisCountBefore, Frais::query()->count());

        $this->commitAllSelected($user, $batch, [$row->id]);

        $this->assertSame($fraisCountBefore, Frais::query()->count());
        $this->assertSame(0, Encaissement::query()->count());
    }

    public function test_committing_an_unresolved_conflit_row_fails_cleanly_with_a_french_reason(): void
    {
        // Regression: CONFLIT rows carry candidates, not decisions, so their
        // resolution has no agent_id/caisse_id/inscription_fee_id. commit()
        // used to dereference those keys and blow up with a raw PHP
        // "Undefined array key" on every single unresolved row.
        // A student with NO inscription at all: nothing to attach the
        // payment to. (A student WITH an inscription now attaches loosely
        // to its first unpaid line — covered by
        // test_a_payment_attaches_to_the_active_inscription_....)
        Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);

        $this->commitAllSelected($user, $batch, [$row->id]);

        $row->refresh();
        $this->assertSame(ImportRow::STATUT_ECHEC_COMMIT, $row->status);

        $message = $row->errors[0]['message'];
        $this->assertStringNotContainsString('Undefined array key', $message);
        $this->assertStringContainsString('aucune inscription', $message);
        $this->assertSame(0, Encaissement::query()->count());
    }

    public function test_preview_pre_checks_only_nouveau_rows_and_lists_conflicts_separately(): void
    {
        // The default selection must never include CONFLIT rows: on a real
        // export that meant thousands of guaranteed commit failures in one
        // click.
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', 'Un autre nom de frais', 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);
        $batch = ImportBatch::query()->firstOrFail();

        $conflictIds = $batch->rows()->where('status', ImportRow::STATUT_CONFLIT)->pluck('id')->all();
        $nouveauIds = $batch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->pluck('id')->all();
        $this->assertNotEmpty($conflictIds, 'This fixture is expected to produce CONFLIT rows.');

        $response = $this->actingAs($user)->get(route('backoffice.import.encaissements.preview', $batch));
        $props = $response->viewData('page')['props'];

        $this->assertEqualsCanonicalizing($nouveauIds, $props['selectableRowIds']);
        $this->assertEqualsCanonicalizing($conflictIds, $props['conflictRowIds']);

        foreach ($conflictIds as $conflictId) {
            $this->assertNotContains($conflictId, $props['selectableRowIds']);
        }
    }

    public function test_a_payment_attaches_to_the_students_inscription_in_another_annee_of_the_same_centre(): void
    {
        // A legacy payments export is ONE file per centre covering every
        // année, while inscriptions arrive as separate Active / Annulé /
        // Archive files that land in DIFFERENT années. Scoping the lookup
        // to the batch's année refused every payment whose inscription sat
        // in the other one — 414 Marrakech rows, 2 755 across the seven
        // centres (2,1 M DH) on the 25/08/2026 import.
        $otherAnnee = AnneeScolaire::create([
            'nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31', 'par_defaut' => false, 'inscription_ouverte' => false,
        ]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA']);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $otherAnnee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $otherAnnee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2024-09-15',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2024-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_NOUVEAU, $row->status);
        $this->assertSame($fee->id, $row->resolution['inscription_fee_id']);
    }

    public function test_a_payment_never_attaches_to_an_inscription_of_another_centre(): void
    {
        // Année scoping was relaxed so a payments file can span years;
        // CENTRE scoping never is. One centre's money must never land on
        // another centre's enrolment.
        $autreCentre = Etablissement::factory()->create();
        $student = Student::factory()->create(['etablissement_id' => $autreCentre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA']);
        $group = Group::factory()->create(['etablissement_id' => $autreCentre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $autreCentre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
        InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('student_not_found', $row->errors[0]['code']);
    }

    public function test_by_default_a_cancelled_inscription_does_not_receive_payments(): void
    {
        // The opt-in must really be opt-in: without the checkbox the
        // previous behaviour stands, and money never lands on a cancelled
        // enrolment by accident.
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ANNULEE, 'date_inscription' => '2026-07-01',
        ]);
        InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
            // no include_inactive_inscriptions -> default behaviour
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->assertSame('no_inscription', $row->errors[0]['code']);
        // The message must point at the checkbox, not leave the operator guessing.
        $this->assertStringContainsString('annulées', $row->errors[0]['message']);
    }

    public function test_a_payment_attaches_to_a_cancelled_or_changed_inscription_rather_than_being_refused(): void
    {
        // A student whose inscription was annulée / changement (the old
        // CRM's "archivée") still really paid during the year. Refusing
        // those payments silently dropped historical revenue and left the
        // caisse short — they must attach to the inscription that exists.
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            // NOT Active — this is the whole point of the test.
            'statut' => Inscription::STATUT_CHANGEMENT, 'date_inscription' => '2026-07-01',
        ]);
        InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
            // The operator has to ask for this explicitly.
            'include_inactive_inscriptions' => true,
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame(
            ImportRow::STATUT_NOUVEAU,
            $row->status,
            'Row should resolve against the non-active inscription: '.json_encode($row->errors),
        );

        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $encaissement = Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame($student->id, $encaissement->student_id);
        $this->assertNotNull($encaissement->inscription_fee_id);
        $this->assertSame('50.00', (string) Caisse::query()
            ->where('responsable_employee_id', $this->operatorEmployee->id)->sole()->solde);
    }

    public function test_an_active_inscription_still_wins_over_a_cancelled_one(): void
    {
        // The fallback must not demote Active — where both exist, the
        // payment belongs to the live inscription.
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $cancelled = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ANNULEE, 'date_inscription' => '2026-06-01',
        ]);
        InscriptionFee::create([
            'inscription_id' => $cancelled->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $active = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-07-01',
        ]);
        $activeFee = InscriptionFee::create([
            'inscription_id' => $active->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
            'include_inactive_inscriptions' => true,
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $this->assertSame(
            $activeFee->id,
            Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail()->inscription_fee_id,
        );
    }

    public function test_a_cancelled_inscription_wins_over_a_changed_one(): void
    {
        // The old CRM exports Annulée and Archivée (→ Changement) as two
        // separate files, so an old-year student often holds BOTH. Decision
        // 24/08/2026: his historical payments belong on the ANNULÉE record —
        // the Changement copy carries no money — even when the Changement
        // row is the more recent one (date is only a tie-breaker).
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);

        $cancelled = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ANNULEE, 'date_inscription' => '2026-06-01',
        ]);
        $cancelledFee = InscriptionFee::create([
            'inscription_id' => $cancelled->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $changed = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            // More recent than the Annulée one — must still lose.
            'statut' => Inscription::STATUT_CHANGEMENT, 'date_inscription' => '2026-07-01',
        ]);
        InscriptionFee::create([
            'inscription_id' => $changed->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300, 'montant' => 300,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
            'include_inactive_inscriptions' => true,
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $this->assertSame(
            $cancelledFee->id,
            Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail()->inscription_fee_id,
        );
    }

    public function test_an_imported_fee_priced_at_zero_is_backfilled_from_the_payment(): void
    {
        // The legacy inscriptions export has no amount columns, so imported
        // fee lines are created at 0.00. Without back-filling, the
        // inscription page read "Montant 0.00 / Payé 1300.00" and the total
        // dû stayed at zero however much had really been paid.
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            // Marks this as import-created — the guard the back-fill checks.
            'legacy_ref' => '373SL126', 'legacy_source' => 'ancien-crm',
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-07-01',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription A1/A2/B1",
            // Exactly the state the inscriptions import leaves behind.
            'montant_initial' => 0, 'montant' => 0, 'masque_le' => now(),
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $fee->refresh();
        // P4255 is a 50.00 payment in the sample file.
        $this->assertSame('50.00', (string) $fee->montant);
        $this->assertSame('50.00', (string) $fee->montant_initial);
        // A fee that received money is really charged — it must be visible.
        $this->assertNull($fee->masque_le);
        // ...and the inscription's total dû must follow.
        $this->assertSame('50.00', (string) $inscription->fresh()->montant_total);
    }

    public function test_backfill_never_touches_a_fee_from_normal_crud(): void
    {
        // Import-only, as asked: a fee created through the UI keeps whatever
        // amount the user set, even when it is deliberately 0.00.
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            // No legacy_source -> not an import -> untouchable.
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-07-01',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 0, 'montant' => 0,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $this->assertSame('0.00', (string) $fee->fresh()->montant, 'A CRUD fee must keep its own amount.');
    }

    public function test_commit_uses_enregistrer_encaissement_action_updates_caisse_solde_and_fee_statut(): void
    {
        $student = $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $row->status);

        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $encaissement = Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame('50.00', (string) $encaissement->montant);
        $this->assertSame($student->id, $encaissement->student_id);
        $this->assertSame('ancien-crm', $encaissement->legacy_source);

        $caisse = Caisse::query()->where('responsable_employee_id', $this->operatorEmployee->id)->sole();
        $this->assertSame('50.00', (string) $caisse->solde);

        $fee = InscriptionFee::query()->where('nom', "Frais d'inscription A1/A2/B1")->firstOrFail();
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->statut);
    }

    /**
     * Payment-method accounts (24/08/2026): an imported row lands in the
     * account of ITS method — Espèces in the mapped opérateur's own till,
     * TPE / Virement / Chèque in the method account of the centre BEING
     * IMPORTED (the batch's centre), even when the opérateur is based in
     * another branch. The physical till never receives non-cash money.
     */
    public function test_commit_routes_each_imported_row_to_the_account_of_its_method(): void
    {
        $this->studentWithActiveFee('AHMED', 'AMIMI', 'Frais test', 1000);
        $this->studentWithActiveFee('AYA', 'ZAHIR', 'Frais test', 1000);
        $this->studentWithActiveFee('JENNATE', 'FIRDAOUS', 'Frais test', 1000);
        $this->studentWithActiveFee('AMMAR', 'OUACHOUCH', 'Frais test', 1000);

        // Opérateur based in ANOTHER centre, importing for $this->centre.
        $autreCentre = Etablissement::factory()->create();
        $operateur = Employee::factory()->create(['etablissement_id' => $autreCentre->id]);
        $operateur->etablissements()->syncWithoutDetaching([$this->centre->id]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->buildUpload([
                ['P1', 'AHMED AMIMI', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'op'],
                ['P2', 'AYA ZAHIR', 'Réglement', '200', 'TPE', 'Frais test', '10/01/2026', 'op'],
                ['P3', 'JENNATE FIRDAOUS', 'Réglement', '300', 'Virement bancaire', 'Frais test', '10/01/2026', 'op'],
                // Legacy spelling (e-acute), as in the real export.
                ['P4', 'AMMAR OUACHOUCH', 'Réglement', '400', 'Chéque', 'Frais test', '10/01/2026', 'op'],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => [['label' => 'op', 'employee_id' => $operateur->id]],
        ])->assertSessionHasNoErrors();

        $batch = ImportBatch::query()->firstOrFail();
        $rows = $batch->rows()->get();
        foreach ($rows as $row) {
            $this->assertSame(ImportRow::STATUT_NOUVEAU, $row->status, $row->legacy_ref.': '.json_encode($row->errors));
        }

        $this->commitAllSelected($user, $batch, $rows->pluck('id')->all())->assertOk();

        $compte = fn (Etablissement $c, string $type) => Caisse::query()
            ->where('etablissement_id', $c->id)->where('type', $type)->sole();

        // Cash → the opérateur's own till (wherever it is based).
        $till = Caisse::query()->where('responsable_employee_id', $operateur->id)->sole();
        $this->assertSame('100.00', (string) $till->solde);

        // Non-cash → the IMPORTED centre's accounts, never the operator's centre.
        $this->assertSame('200.00', (string) $compte($this->centre, Encaissement::METHODE_TPE)->solde);
        $this->assertSame('300.00', (string) $compte($this->centre, Encaissement::METHODE_VIREMENT)->solde);
        $this->assertSame('400.00', (string) $compte($this->centre, Encaissement::METHODE_CHEQUE)->solde);
        foreach (Caisse::TYPES_METHODE as $type) {
            $this->assertSame('0.00', (string) $compte($autreCentre, $type)->solde);
        }

        // The cheque row is ALSO tracked in the Chèques module: a Cheque
        // record linked to the payment, in the imported centre, already
        // Encaissé (the money was received), and the payment shows on the
        // Chèques tab of Encaissements.
        $chequePayment = Encaissement::query()->where('legacy_ref', 'P4')->sole();
        $this->assertSame(Encaissement::METHODE_CHEQUE, $chequePayment->methode);
        $this->assertNotNull($chequePayment->cheque_id);
        $chequeRow = \App\Models\Cheque::query()->findOrFail($chequePayment->cheque_id);
        $this->assertSame('400.00', (string) $chequeRow->montant);
        $this->assertSame($this->centre->id, (int) $chequeRow->etablissement_id);
        $this->assertSame(\App\Models\Cheque::STATUT_ENCAISSE, $chequeRow->statut);
        $this->assertSame($chequePayment->student_id, (int) $chequeRow->student_id);
        $this->assertSame($compte($this->centre, Encaissement::METHODE_CHEQUE)->id, $chequePayment->caisse_id);

        $viewer = $this->userWith('payments.view');
        $this->actingAs($viewer);
        app(\App\Services\Context\CurrentContext::class)->setEtablissement($this->centre->id);
        $this->get(route('backoffice.encaissements.index', ['view' => 'cheque', 'dateFrom' => '', 'dateTo' => '']))
            ->assertOk()
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->has('encaissements.data', 1)
                ->where('encaissements.data.0.reference', $chequePayment->reference));

        // Each row points at the account that was credited.
        $this->assertSame($till->id, Encaissement::query()->where('legacy_ref', 'P1')->sole()->caisse_id);
        $this->assertSame(
            $compte($this->centre, Encaissement::METHODE_TPE)->id,
            Encaissement::query()->where('legacy_ref', 'P2')->sole()->caisse_id,
        );
    }

    /**
     * Every centre's old CRM numbers its payments from P1 — the same « P3 »
     * is a DIFFERENT payment in Rabat and in Marrakech. The 24/08/2026
     * Rabat import skipped 4 297 of 5 000 rows as « déjà importé » because
     * the ref check was global. The scope is the batch centre; a re-upload
     * within the same centre still dedupes.
     */
    public function test_same_legacy_ref_in_another_centre_is_a_different_payment(): void
    {
        $this->studentWithActiveFee('AHMED', 'AMIMI', 'Frais test', 1000);
        $user = $this->userWith('import.view', 'import.create');
        $mapping = [['label' => 'op', 'employee_id' => $this->operatorEmployee->id]];
        $upload = fn () => $this->buildUpload([
            ['P3', 'AHMED AMIMI', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'op'],
        ]);

        // Centre A: imported.
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $upload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'operateur_mapping' => $mapping,
        ])->assertSessionHasNoErrors();
        $batchA = ImportBatch::query()->firstOrFail();
        $this->commitAllSelected($user, $batchA, $batchA->rows()->pluck('id')->all())->assertOk();
        $first = Encaissement::query()->where('legacy_ref', 'P3')->sole();
        $this->assertSame($this->centre->id, (int) $first->etablissement_id);

        // Centre B, same P3 for a homonymous student there: NOT a duplicate.
        $centreB = Etablissement::factory()->create();
        $studentB = Student::factory()->create(['etablissement_id' => $centreB->id, 'prenom' => 'AHMED', 'nom' => 'AMIMI']);
        $groupB = Group::factory()->create(['etablissement_id' => $centreB->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscriptionB = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $studentB->id, 'group_id' => $groupB->id,
            'etablissement_id' => $centreB->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-07-01',
        ]);
        InscriptionFee::create([
            'inscription_id' => $inscriptionB->id, 'nom' => 'Frais test',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        $operateurB = Employee::factory()->create(['etablissement_id' => $centreB->id]);

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $upload(), 'etablissement_id' => $centreB->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => [['label' => 'op', 'employee_id' => $operateurB->id]],
        ])->assertSessionHasNoErrors();
        $batchB = ImportBatch::query()->where('etablissement_id', $centreB->id)->firstOrFail();
        $rowB = $batchB->rows()->firstOrFail();
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rowB->status, json_encode($rowB->errors));

        $this->commitAllSelected($user, $batchB, [$rowB->id])->assertOk();
        $this->assertSame(2, Encaissement::query()->where('legacy_ref', 'P3')->count());
        $this->assertSame(1, Encaissement::query()->where('legacy_ref', 'P3')->where('etablissement_id', $centreB->id)->count());

        // Same centre again: still a duplicate.
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $upload(), 'etablissement_id' => $centreB->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => [['label' => 'op', 'employee_id' => $operateurB->id]],
        ]);
        $batchC = ImportBatch::query()->where('etablissement_id', $centreB->id)->where('id', '!=', $batchB->id)->firstOrFail();
        $this->assertSame(ImportRow::STATUT_DOUBLON, $batchC->rows()->firstOrFail()->status);
        $this->commitAllSelected($user, $batchC, $batchC->rows()->pluck('id')->all());
        $this->assertSame(2, Encaissement::query()->where('legacy_ref', 'P3')->count());
    }

    /**
     * Homonyms: the payments export carries only a name. When exactly one of
     * the same-name students holds an inscription in the batch's
     * centre+année, the money is his (it can attach nowhere else); when
     * both are enrolled it stays a CONFLIT for a human.
     */
    public function test_homonym_payer_is_resolved_to_the_only_enrolled_twin(): void
    {
        $enrolled = $this->studentWithActiveFee('SOUMIA', 'LABHIRI', 'Frais test', 1000);
        Student::factory()->create(['etablissement_id' => $this->centre->id, 'prenom' => 'SOUMIA', 'nom' => 'LABHIRI']);
        $this->studentWithActiveFee('AYA', 'HAKIM', 'Frais test', 1000);
        $this->studentWithActiveFee('AYA', 'HAKIM', 'Frais test', 1000);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->buildUpload([
                ['P1', 'SOUMIA LABHIRI SOUMIA LABHIRI', 'Réglement', '100', 'Espèces', 'Frais test', '10/01/2026', 'op'],
                ['P2', 'AYA HAKIM AYA HAKIM', 'Réglement', '200', 'Espèces', 'Frais test', '10/01/2026', 'op'],
            ]),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => [['label' => 'op', 'employee_id' => $this->operatorEmployee->id]],
        ])->assertSessionHasNoErrors();

        $rows = ImportBatch::query()->firstOrFail()->rows()->orderBy('source_row_number')->get();
        $this->assertSame(ImportRow::STATUT_NOUVEAU, $rows[0]->status, json_encode($rows[0]->resolution));
        $this->assertSame($enrolled->id, (int) $rows[0]->resolution['student_id']);
        $this->assertSame(ImportRow::STATUT_CONFLIT, $rows[1]->status, 'both twins enrolled ⇒ a human decides');
        $this->assertSame('ambiguous_student', $rows[1]->errors[0]['code']);
    }

    public function test_committing_the_same_batch_twice_never_double_writes_a_payment(): void
    {
        // analyze() dedupes against a snapshot taken at upload time. Committing
        // the SAME batch again (double-click, refresh, retry) must still not
        // duplicate the payment — the write path re-checks live data.
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->commitAllSelected($user, $batch, [$row->id]);
        $this->assertSame(1, Encaissement::query()->where('legacy_ref', 'P4255')->count());

        $caisse = Caisse::query()->where('responsable_employee_id', $this->operatorEmployee->id)->sole();
        $soldeAfterFirstCommit = (string) $caisse->fresh()->solde;

        // Force the row back to a committable state, exactly as a retry would
        // (write straight through the query builder — the in-memory $row is
        // stale, the app updated the DB copy during the first commit).
        ImportRow::query()->whereKey($row->id)->update([
            'status' => ImportRow::STATUT_NOUVEAU,
            'created_model_id' => null,
            'created_model_type' => null,
        ]);
        ImportBatch::query()->whereKey($batch->id)->update(['status' => ImportBatch::STATUT_ANALYZED]);

        $this->commitAllSelected($user, $batch->fresh(), [$row->id]);

        $this->assertSame(1, Encaissement::query()->where('legacy_ref', 'P4255')->count());
        $this->assertSame($soldeAfterFirstCommit, (string) $caisse->fresh()->solde, 'A skipped duplicate must not move the till.');
        $this->assertSame(ImportRow::STATUT_DOUBLON, $row->fresh()->status);
    }

    public function test_legacy_cheque_spelling_maps_to_the_real_methode_constant(): void
    {
        // The legacy CRM writes "Chéque" (e-acute) where the app's constant
        // is "Chèque" (e-grave). Treating them as different values sent the
        // entire cheque column to ERREUR as an unknown méthode.
        $acute = 'Chéque';
        $grave = Encaissement::METHODE_CHEQUE;

        $this->assertNotSame($acute, $grave, 'Fixture guard: the two spellings must really differ.');

        $map = (new \ReflectionClassConstant(\App\Services\Import\EncaissementImporter::class, 'METHODE_MAP'))->getValue();

        $this->assertSame($grave, $map[$acute] ?? null, 'The misspelt legacy label must map to Chèque.');
        $this->assertSame($grave, $map[$grave] ?? null, 'The correct spelling must keep working.');
    }

    public function test_a_cheque_payment_also_lands_in_the_cheques_table(): void
    {
        // The legacy paiements export mixes cheques in with every other
        // méthode. Importing one as a bare Encaissement left the Chèques
        // module blind to money the school is still waiting to clear.
        $student = $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        // The small sample carries no cheque row, so one is made here rather
        // than depending on a fixture that may or may not contain one.
        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $row->update(['raw' => [...$row->raw, 'methode' => Encaissement::METHODE_CHEQUE]]);

        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $encaissement = Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertNotNull($encaissement->cheque_id, 'A Chèque payment must point at a Chèques-module record.');

        $cheque = \App\Models\Cheque::query()->findOrFail($encaissement->cheque_id);
        $this->assertSame($student->id, $cheque->student_id);
        $this->assertSame('50.00', (string) $cheque->montant);
        $this->assertSame($this->centre->id, $cheque->etablissement_id);
        $this->assertSame($this->operatorEmployee->id, $cheque->agent_id);
        // The money was received in the old CRM — the cheque is not pending.
        $this->assertSame(\App\Models\Cheque::STATUT_ENCAISSE, $cheque->statut);
        // No number exists in the export, so none is invented — the
        // placeholder traces back to the legacy réf instead.
        $this->assertStringContainsString('P4255', (string) $cheque->numero_cheque);
    }

    public function test_a_non_cheque_payment_creates_no_cheque_record(): void
    {
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame(Encaissement::METHODE_ESPECES, $row->raw['methode']);

        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $this->assertNull(Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail()->cheque_id);
        $this->assertSame(0, \App\Models\Cheque::query()->count());
    }

    public function test_a_payment_with_no_frais_label_is_imported_as_an_avance(): void
    {
        // In the legacy export an empty Frais cell means the student paid
        // ahead of any fee line. Requiring a fee there sent every avance to
        // ERREUR; it must import with inscription_fee_id left null instead.
        $student = $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $row->update([
            'raw' => [...$row->raw, 'is_avance' => true],
            'resolution' => [...($row->resolution ?? []), 'inscription_fee_id' => null],
        ]);

        $this->commitAllSelected($user, $batch, [$row->id])->assertOk();

        $encaissement = Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertNull($encaissement->inscription_fee_id);
        $this->assertTrue($encaissement->isAvance());
        $this->assertSame($student->id, $encaissement->student_id);

        // The money still reaches the till — an avance is a real payment.
        $caisse = Caisse::query()->where('responsable_employee_id', $this->operatorEmployee->id)->sole();
        $this->assertSame('50.00', (string) $caisse->solde);
    }

    public function test_conflict_rows_keep_the_operator_and_caisse_that_did_resolve(): void
    {
        // A row conflicting on the student/frais still has a perfectly valid
        // opérateur -> caisse. Dropping them made the commit guard report
        // "opérateur, caisse" as missing on rows where they were mapped fine.
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $conflict = $batch->rows()->where('status', ImportRow::STATUT_CONFLIT)->firstOrFail();

        $this->assertSame($this->operatorEmployee->id, $conflict->resolution['agent_id'] ?? null);
        $this->assertNotNull($conflict->resolution['caisse_id'] ?? null);
    }

    public function test_a_failed_row_can_be_retried_once_its_cause_is_fixed(): void
    {
        // A commit failure used to be a dead end: ECHEC_COMMIT wasn't
        // selectable, so fixing the underlying data (creating the missing
        // inscription, say) still meant re-uploading the whole file.
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        // No student/inscription exists yet, so this row cannot resolve.
        $this->assertSame(ImportRow::STATUT_CONFLIT, $row->status);
        $this->commitAllSelected($user, $batch, [$row->id]);
        $this->assertSame(ImportRow::STATUT_ECHEC_COMMIT, $row->fresh()->status);
        $this->assertSame(0, Encaissement::query()->count());

        // Fix the cause, then re-analyze so the row picks up the now-
        // resolvable student/fee, and retry it.
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $retryBatch = ImportBatch::query()->where('id', '!=', $batch->id)->firstOrFail();
        $retryRow = $retryBatch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_NOUVEAU, $retryRow->status);
        $this->commitAllSelected($user, $retryBatch, [$retryRow->id]);

        $this->assertSame(1, Encaissement::query()->where('legacy_ref', 'P4255')->count());
    }

    public function test_failed_rows_are_offered_for_retry_but_never_pre_selected(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $conflict = $batch->rows()->where('status', ImportRow::STATUT_CONFLIT)->firstOrFail();
        $this->commitAllSelected($user, $batch, [$conflict->id]);

        $response = $this->actingAs($user)->get(route('backoffice.import.encaissements.preview', $batch));
        $props = $response->viewData('page')['props'];

        $this->assertContains($conflict->id, $props['failedRowIds'], 'A failed row must be offered for retry.');
        $this->assertNotContains($conflict->id, $props['selectableRowIds'], 'It must never be pre-selected.');
    }

    public function test_a_payment_attaches_to_the_active_inscription_even_when_the_fee_label_does_not_match(): void
    {
        // Legacy labels drift, and some rows pay several fees at once
        // ("Frais A, Frais B"). When the student HAS an active inscription
        // the payment still belongs to it, so it attaches to the first
        // unpaid line rather than blocking.
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA',
        ]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-07-01',
        ]);
        // Deliberately NOT the label the file uses.
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais annuel',
            'montant_initial' => 5000, 'montant' => 5000,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_NOUVEAU, $row->status);
        $this->assertSame($fee->id, $row->resolution['inscription_fee_id']);
        $this->assertSame('Frais annuel', $row->resolution['fee_matched_loosely']['attached_to'] ?? null);

        $this->commitAllSelected($user, $batch, [$row->id]);

        $encaissement = Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame($fee->id, $encaissement->inscription_fee_id);
        $this->assertStringContainsString('rattaché à "Frais annuel"', $encaissement->note);
    }

    public function test_batch_counters_are_derived_from_rows_not_accumulated_across_retries(): void
    {
        // Counters used to be written as `$batch->x + $n` per chunk, so a
        // retried batch inflated them — 5 real failures were reported as 675.
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $conflict = $batch->rows()->where('status', ImportRow::STATUT_CONFLIT)->firstOrFail();

        // Commit the same unresolvable row three times over. Each pass uses a
        // single POST rather than commitAllSelected(): resetting the row to
        // CONFLIT inside that helper's "loop until remaining === 0" would
        // never terminate, since the row becomes eligible again every time.
        foreach (range(1, 3) as $ignored) {
            ImportRow::query()->whereKey($conflict->id)->update(['status' => ImportRow::STATUT_CONFLIT]);

            $this->actingAs($user)->postJson(
                route('backoffice.import.encaissements.commit', $batch),
                ['selected_row_ids' => [$conflict->id]]
            );
        }

        $batch->refresh();

        $this->assertSame(
            $batch->rows()->where('status', ImportRow::STATUT_ECHEC_COMMIT)->count(),
            $batch->error_rows,
            'error_rows must equal the real number of failed rows, however many retries happened.'
        );
        $this->assertSame(
            $batch->rows()->where('status', ImportRow::STATUT_DOUBLON)->count(),
            $batch->skipped_rows,
        );
    }

    public function test_reupload_is_idempotent(): void
    {
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');
        $mapping = $this->defaultOperateurMapping();

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'operateur_mapping' => $mapping,
        ]);
        $firstBatch = ImportBatch::query()->firstOrFail();
        $row = $firstBatch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->commitAllSelected($user, $firstBatch, [$row->id]);
        $this->assertSame(1, Encaissement::query()->where('legacy_ref', 'P4255')->count());

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'operateur_mapping' => $mapping,
        ]);
        $secondBatch = ImportBatch::query()->where('id', '!=', $firstBatch->id)->firstOrFail();
        $secondRow = $secondBatch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame(ImportRow::STATUT_DOUBLON, $secondRow->status);

        $this->commitAllSelected($user, $secondBatch, $secondBatch->rows()->pluck('id')->all());

        $this->assertSame(1, Encaissement::query()->where('legacy_ref', 'P4255')->count());
    }

    public function test_unmapped_operateur_is_erreur(): void
    {
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'operateur_mapping' => [],
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('legacy_ref', 'P4255')->firstOrFail();

        $this->assertSame(ImportRow::STATUT_ERREUR, $row->status);
        $this->assertSame('operateur_not_mapped', $row->errors[0]['code']);
    }

    public function test_preview_and_result_pages_render(): void
    {
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', "Frais d'inscription A1/A2/B1", 300);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'operateur_mapping' => $this->defaultOperateurMapping(),
        ]);
        $batch = ImportBatch::query()->firstOrFail();

        $this->actingAs($user)->get(route('backoffice.import.encaissements.preview', $batch))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Encaissements/Preview')
                ->where('batch.id', $batch->id));

        $this->actingAs($user)->get(route('backoffice.import.encaissements.result', $batch))
            ->assertInertia(fn (Assert $page) => $page->component('Backoffice/Import/Encaissements/Result'));
    }
}
