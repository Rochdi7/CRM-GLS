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

    /**
     * commit() now processes a small chunk per call (progress-bar UX) —
     * loops the JSON endpoint to completion the way the real Preview page's
     * useCommitProgress hook does, and returns the final chunk's response.
     */
    private function commitAllSelected(User $user, ImportBatch $batch, array $rowIds): \Illuminate\Testing\TestResponse
    {
        $guard = 0;

        do {
            $response = $this->actingAs($user)->postJson(route('backoffice.import.students.commit', $batch), [
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

    public function test_upload_page_renders_with_scoped_etablissement_options(): void
    {
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)->get(route('backoffice.import.students.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Students/Upload')
                ->has('etablissements')
                ->has('centerLocked'));
    }

    public function test_analyze_requires_a_centre_in_all_centers_mode(): void
    {
        // Global user in « Tous les centres »: the année comes from the
        // context, but a specific centre must still be chosen on the form.
        $user = $this->userWith('import.view', 'import.create');

        $response = $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
        ]);

        $response->assertSessionHasErrors(['etablissement_id']);
        $this->assertSame(0, ImportBatch::query()->count());
    }

    public function test_analyze_ignores_a_posted_centre_when_the_context_locks_one(): void
    {
        // Non-global single-centre user: the active context IS their centre,
        // so a hostile/stale etablissement_id in the request is ignored — the
        // batch can only ever land in the centre they actually work in.
        $user = User::factory()->create();
        $user->givePermissionTo('import.view', 'import.create');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($user->fresh())->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->otherCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $this->assertSame($this->centre->id, $batch->etablissement_id);
    }

    public function test_analyze_uses_the_active_context_year_never_client_input(): void
    {
        // The bug this guards against: the Upload form used to carry its own
        // Année dropdown, so a batch could silently land in a different year
        // than the one every list page displays.
        $otherAnnee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $user = $this->userWith('import.view', 'import.create');

        $this->actingAs($user)
            ->withSession(['context.annee_scolaire_id' => $this->annee->id])
            ->post(route('backoffice.import.students.analyze'), [
                'file' => $this->sampleUpload(),
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $otherAnnee->id,
            ]);

        $batch = ImportBatch::query()->firstOrFail();
        $this->assertSame($this->annee->id, $batch->annee_scolaire_id);
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

        // rows is a paginator now (a real export is thousands of rows, so
        // the Preview screen never loads them all at once) — the full row
        // count lives in rows.total, and every selectable id is sent
        // separately for the "select all"/commit loop.
        $this->actingAs($user)->get(route('backoffice.import.students.preview', $batch))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Students/Preview')
                ->where('batch.id', $batch->id)
                ->where('rows.total', 41)
                ->has('rows.data', 41)
                ->has('selectableRowIds', 41)
                ->where('statusCounts.NOUVEAU', 41));
    }

    public function test_preview_paginates_rows_and_filters_by_status(): void
    {
        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $batch = ImportBatch::query()->firstOrFail();

        // Force a mix of statuses so the filter has something to narrow to.
        $batch->rows()->limit(5)->update(['status' => ImportRow::STATUT_ERREUR]);

        $this->actingAs($user)->get(route('backoffice.import.students.preview', $batch).'?status=ERREUR')
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.total', 5)
                ->where('filters.status', ImportRow::STATUT_ERREUR)
                ->where('statusCounts.ERREUR', 5)
                ->where('statusCounts.NOUVEAU', 36));

        // An unknown status is ignored rather than 500ing or filtering to nothing.
        $this->actingAs($user)->get(route('backoffice.import.students.preview', $batch).'?status=BOGUS')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', '')
                ->where('rows.total', 41));
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

        $this->commitAllSelected($user, $batch, $rowIds)->assertOk();

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

    public function test_commit_processes_a_small_chunk_per_call_reporting_incremental_progress(): void
    {
        $user = $this->userWith('import.view', 'import.create');
        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $batch = ImportBatch::query()->firstOrFail();
        $rowIds = $batch->rows()->orderBy('id')->pluck('id')->all();
        $this->assertGreaterThan(5, count($rowIds), 'This test needs more rows than one chunk to prove chunking.');

        $first = $this->actingAs($user)->postJson(route('backoffice.import.students.commit', $batch), [
            'selected_row_ids' => $rowIds,
        ]);

        $first->assertOk();
        $this->assertSame(5, $first->json('inserted'));
        $this->assertSame(count($rowIds) - 5, $first->json('remaining'));
        $this->assertSame(5, Student::query()->count(), 'Only the first chunk should be inserted so far.');
        $this->assertSame(ImportBatch::STATUT_COMMITTING, $batch->fresh()->status);

        $second = $this->actingAs($user)->postJson(route('backoffice.import.students.commit', $batch), [
            'selected_row_ids' => $rowIds,
        ]);

        $this->assertSame(5, $second->json('inserted'));
        $this->assertSame(10, Student::query()->count());
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
        $this->commitAllSelected($user, $firstBatch, $firstBatch->rows()->pluck('id')->all());

        $this->assertSame(41, Student::query()->count());

        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $secondBatch = ImportBatch::query()->where('id', '!=', $firstBatch->id)->firstOrFail();

        $this->assertSame(41, $secondBatch->rows()->where('status', ImportRow::STATUT_DOUBLON)->count());
        $this->assertSame(0, $secondBatch->rows()->where('status', ImportRow::STATUT_NOUVEAU)->count());

        $this->commitAllSelected($user, $secondBatch, $secondBatch->rows()->pluck('id')->all());

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

        $this->commitAllSelected($user, $batch, [$row->id]);

        $this->assertSame(0, Student::query()->where('legacy_ref', 'E931')->count());
    }
}
