<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ImportBatchIndexTest extends TestCase
{
    use RefreshDatabase;

    private const string SAMPLE_FILE = __DIR__.'/../../../../old crm data exemple/liste-etudiants_55_20260817.xlsx';

    public function test_index_lists_recent_batches_across_modules(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $centre = Etablissement::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('import.view', 'import.create', 'centers.access-all');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $user = $user->fresh();

        $this->actingAs($user)->post(route('backoffice.import.students.analyze'), [
            'file' => new UploadedFile(self::SAMPLE_FILE, 'liste-etudiants.xlsx', null, null, true),
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);

        $this->actingAs($user)->get(route('backoffice.import.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Import/Index')
                ->has('recentBatches', 1)
                ->where('recentBatches.0.module', 'students'));
    }

    public function test_index_requires_import_view_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('backoffice.import.index'))->assertForbidden();
    }
}
