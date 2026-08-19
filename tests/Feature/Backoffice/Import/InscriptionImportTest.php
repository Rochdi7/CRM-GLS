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

    public function test_peek_groupes_lists_distinct_labels_and_existing_groups_scoped_to_centre_annee(): void
    {
        $user = $this->userWith('import.view', 'import.create');
        $group = Group::factory()->create([
            'nom' => 'GROUP 19H SEPTEMBRE',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        // A same-named group in a different année must never be offered.
        Group::factory()->create([
            'nom' => 'GROUP 19H SEPTEMBRE',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->otherAnnee->id,
        ]);

        $response = $this->actingAs($user)->post(route('backoffice.import.inscriptions.peek-groupes'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $response->assertOk();
        $json = $response->json();

        $this->assertContains('Herr Driss 13h', $json['groupeLabels']);
        $this->assertContains('GROUP 19H SEPTEMBRE', $json['groupeLabels']);
        $this->assertCount(1, $json['existingGroups']);
        $this->assertSame($group->id, $json['existingGroups'][0]['id']);
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

    public function test_analyze_rejects_a_centre_the_user_cannot_access(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('import.view', 'import.create');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $response = $this->actingAs($user->fresh())->post(route('backoffice.import.inscriptions.analyze'), [
            'file' => $this->sampleUpload(),
            'etablissement_id' => $this->otherCentre->id,
            'annee_scolaire_id' => $this->annee->id,
            'groupe_mapping' => [],
        ]);

        $response->assertForbidden();
        $this->assertSame(0, ImportBatch::query()->count());
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
