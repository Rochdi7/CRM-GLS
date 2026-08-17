<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private const string SAMPLE_FILE = __DIR__.'/../../../../old crm data exemple/liste-etudiants_55_20260817.xlsx';

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
        return new UploadedFile(self::SAMPLE_FILE, 'liste-etudiants.xlsx', null, null, true);
    }

    public function test_upload_page_renders_with_scoped_etablissement_options(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->get(route('backoffice.import.students.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Students/Upload')
                ->has('etablissements')
                ->has('anneesScolaires'));
    }

    public function test_analyze_requires_etablissement_and_annee_scolaire(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
        ]);

        $response->assertSessionHasErrors(['etablissement_id', 'annee_scolaire_id']);
        $this->assertSame(0, ImportBatch::query()->count());
    }

    public function test_analyze_rejects_a_centre_the_user_cannot_access(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('import.view', 'import.create');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $response = $this->actingAs($user->fresh())->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->otherCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $response->assertForbidden();
        $this->assertSame(0, ImportBatch::query()->count());
    }

    public function test_analyze_parses_all_41_rows_as_nouveau(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $response->assertRedirect(route('backoffice.import.students.preview', $batch));

        $this->assertSame(ImportBatch::MODULE_STUDENTS, $batch->module);
        $this->assertSame($this->centre->id, $batch->etablissement_id);
        $this->assertSame($this->annee->id, $batch->annee_scolaire_id);
        $this->assertSame(41, $batch->total_rows);
        $this->assertSame(41, $batch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->count());

        $row = $batch->rows()->where('source_row_number', 18)->firstOrFail();
        $this->assertNull($row->raw['date_naissance']);

        $this->actingAs($user)->get(route('backoffice.import.students.preview', $batch))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Students/Preview')
                ->where('batch.id', $batch->id)
                ->has('rows', 41));
    }

    public function test_commit_inserts_selected_rows_with_legacy_ref_and_reference(): void
    {
        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $rowIds = $batch->rows()->pluck('id')->all();

        $this->actingAs($user)->post(route('backoffice.import.students.commit', $batch), [
            'selected_row_ids' => $rowIds,
        ])->assertRedirect(route('backoffice.import.students.result', $batch));

        $this->assertSame(41, Student::query()->count());

        $student = Student::query()->where('legacy_ref', 'E931')->firstOrFail();
        $this->assertSame('AYA', $student->prenom);
        $this->assertSame('IBNOU EDDINE', $student->nom);
        $this->assertSame('Femme', $student->sexe);
        $this->assertSame($this->centre->id, $student->etablissement_id);
        $this->assertSame('ancien-crm', $student->legacy_source);
        $this->assertStringStartsWith('ETU-', $student->reference);

        $batch->refresh();
        $this->assertSame(41, $batch->inserted_rows);
        $this->assertSame(ImportBatch::STATUT_COMMITTED, $batch->status);

        $this->actingAs($user)->get(route('backoffice.import.students.result', $batch))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Students/Result')
                ->where('batch.inserted_rows', 41));
    }

    public function test_reupload_of_the_same_file_inserts_zero_rows_idempotent(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $firstBatch = ImportBatch::query()->firstOrFail();
        $this->actingAs($user)->post(route('backoffice.import.students.commit', $firstBatch), [
            'selected_row_ids' => $firstBatch->rows()->pluck('id')->all(),
        ]);

        $this->assertSame(41, Student::query()->count());

        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $secondBatch = ImportBatch::query()->where('id', '!=', $firstBatch->id)->firstOrFail();

        $this->assertSame(41, $secondBatch->rows()->where('status', ImportRow::STATUT_DOUBLON)->count());
        $this->assertSame(0, $secondBatch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->count());

        $this->actingAs($user)->post(route('backoffice.import.students.commit', $secondBatch), [
            'selected_row_ids' => $secondBatch->rows()->pluck('id')->all(),
        ]);

        $this->assertSame(41, Student::query()->count(), 'Re-uploading the same file must insert 0 new rows.');
    }

    public function test_same_named_student_in_a_different_centre_is_not_a_duplicate(): void
    {
        $user = $this->userWith('import.view', 'import.create');
        Student::factory()->create([
            'etablissement_id' => $this->otherCentre->id,
            'prenom' => 'AYA',
            'nom' => 'IBNOU EDDINE',
            'date_naissance' => '2003-11-26',
        ]);

        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $batch = ImportBatch::query()->firstOrFail();

        $this->assertSame(41, $batch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->count());
    }

    public function test_invalid_sexe_value_is_flagged_erreur_not_inserted(): void
    {
        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $batch = ImportBatch::query()->firstOrFail();
        $row = $batch->rows()->where('source_row_number', 11)->firstOrFail();
        $row->update(['raw' => [...$row->raw, 'sexe' => 'H'], 'status' => ImportRow::STATUT_ERREUR, 'errors' => [
            ['field' => 'sexe', 'code' => 'invalid_enum', 'message' => 'bad'],
        ]]);

        $this->actingAs($user)->post(route('backoffice.import.students.commit', $batch), [
            'selected_row_ids' => [$row->id],
        ]);

        $this->assertSame(0, Student::query()->where('legacy_ref', 'E931')->count());
    }
}
