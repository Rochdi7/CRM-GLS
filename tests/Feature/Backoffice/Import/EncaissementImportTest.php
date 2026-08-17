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
        $this->operatorEmployee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        Caisse::create([
            'nom' => 'Caisse test', 'etablissement_id' => $this->centre->id,
            'responsable_employee_id' => $this->operatorEmployee->id, 'solde' => 0, 'statut' => Caisse::STATUT_ACTIVE,
        ]);
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

    private function defaultOperateurMapping(): array
    {
        return [
            ['label' => 'mustapha', 'employee_id' => $this->operatorEmployee->id],
            ['label' => 'latifa', 'employee_id' => $this->operatorEmployee->id],
        ];
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
        $this->studentWithActiveFee('ABDERRAHMANE', 'BOUGMA', 'Un autre nom de frais', 300);
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
        $this->assertSame('fee_not_matched', $row->errors[0]['code']);
        $this->assertSame($fraisCountBefore, Frais::query()->count());

        $this->actingAs($user)->post(route('backoffice.import.encaissements.commit', $batch), [
            'selected_row_ids' => [$row->id],
        ]);

        $this->assertSame($fraisCountBefore, Frais::query()->count());
        $this->assertSame(0, Encaissement::query()->count());
    }

    public function test_no_active_inscription_in_selected_annee_is_erreur_never_falls_back_to_another_year(): void
    {
        $otherAnnee = AnneeScolaire::create([
            'nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31', 'par_defaut' => false, 'inscription_ouverte' => false,
        ]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id, 'prenom' => 'ABDERRAHMANE', 'nom' => 'BOUGMA']);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $otherAnnee->id]);
        Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $otherAnnee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2024-09-15',
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
        $this->assertSame('no_active_inscription', $row->errors[0]['code']);
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

        $this->actingAs($user)->post(route('backoffice.import.encaissements.commit', $batch), [
            'selected_row_ids' => [$row->id],
        ])->assertRedirect(route('backoffice.import.encaissements.result', $batch));

        $encaissement = Encaissement::query()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame('50.00', (string) $encaissement->montant);
        $this->assertSame($student->id, $encaissement->student_id);
        $this->assertSame('ancien-crm', $encaissement->legacy_source);

        $caisse = Caisse::query()->where('responsable_employee_id', $this->operatorEmployee->id)->firstOrFail();
        $this->assertSame('50.00', (string) $caisse->solde);

        $fee = InscriptionFee::query()->where('nom', "Frais d'inscription A1/A2/B1")->firstOrFail();
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->statut);
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
        $this->actingAs($user)->post(route('backoffice.import.encaissements.commit', $firstBatch), [
            'selected_row_ids' => [$row->id],
        ]);
        $this->assertSame(1, Encaissement::query()->where('legacy_ref', 'P4255')->count());

        $this->actingAs($user)->post(route('backoffice.import.encaissements.analyze'), [
            'file' => $this->sampleUpload(), 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'operateur_mapping' => $mapping,
        ]);
        $secondBatch = ImportBatch::query()->where('id', '!=', $firstBatch->id)->firstOrFail();
        $secondRow = $secondBatch->rows()->where('legacy_ref', 'P4255')->firstOrFail();
        $this->assertSame(ImportRow::STATUT_DOUBLON, $secondRow->status);

        $this->actingAs($user)->post(route('backoffice.import.encaissements.commit', $secondBatch), [
            'selected_row_ids' => $secondBatch->rows()->pluck('id')->all(),
        ]);

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
