<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Depenses endpoints (DepenseController) built
 * alongside the unchanged Livewire DepensesIndex fallback — see
 * DepensesCrudTest for the Livewire-side coverage of the same business
 * rules (docs/phase-10-finance-audit.md §2.5).
 */
final class DepensesInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
        $this->type = TypeDepense::create(['nom' => 'Fournitures', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);
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

    public function test_index_requires_expenses_or_refunds_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.depenses.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('expenses.view'))
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Depenses/Index', false)
                ->where('canViewDepenses', true)
                ->where('canViewRemboursements', false)
                ->has('depenses')
                ->where('remboursements', null)
            );
    }

    public function test_a_depense_can_be_created_and_decrements_the_caisse(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 5000]);

        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $this->type->id,
            'caisse_id' => $caisse->id,
            'montant' => '120',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-15',
            'description' => 'Fournitures de bureau',
        ])->assertRedirect(route('backoffice.depenses.index'));

        $depense = Depense::where('description', 'Fournitures de bureau')->first();
        $this->assertNotNull($depense);
        $this->assertStringStartsWith('DEP-', $depense->reference);
        $this->assertSame('4880.00', (string) $caisse->fresh()->solde);
    }

    /**
     * Regression: the create form never has a caisse field (the till is
     * always the acting employee's own — see StoreDepenseRequest's
     * docblock), so the payload legitimately arrives with NO caisse_id at
     * all. Before this fix, caisse_id was still `required` server-side,
     * which silently failed every real submission.
     */
    public function test_a_depense_can_be_created_with_no_caisse_id_in_the_payload(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 5000]);

        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $this->type->id,
            'montant' => '120',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-15',
            'description' => 'Fournitures de bureau',
        ])->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('backoffice.depenses.index'));

        $depense = Depense::where('description', 'Fournitures de bureau')->first();
        $this->assertNotNull($depense);
        $this->assertSame($caisse->id, $depense->caisse_id);
        $this->assertSame('4880.00', (string) $caisse->fresh()->solde);
    }

    public function test_methode_paiement_is_required_on_create(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $this->type->id,
            'caisse_id' => $caisse->id,
            'montant' => '120',
            'date_depense' => '2025-09-15',
        ])->assertSessionHasErrors('methode_paiement');
    }

    public function test_a_receipt_attaches_to_the_justificatifs_collection(): void
    {
        // Deliberately NOT faking the `media` disk here — Storage::fake()
        // discards the disk's configured custom `url` (rewriting it to the
        // generic /storage/{disk} testing convention), which would make the
        // /media/ URL assertion below meaningless. Matches
        // DepensesCrudTest::test_receipt_urls_are_served_from_media_not_storage,
        // which uses the same real-disk approach for the same reason.
        $user = $this->userWith('expenses.view', 'expenses.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $this->type->id,
            'caisse_id' => $caisse->id,
            'montant' => '120',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-15',
            'description' => 'Avec justificatif',
            'justificatifs' => [UploadedFile::fake()->image('facture.jpg')],
        ])->assertSessionDoesntHaveErrors();

        $depense = Depense::where('description', 'Avec justificatif')->first();
        $this->assertSame(1, $depense->getMedia('justificatifs')->count());
        $url = $depense->getFirstMediaUrl('justificatifs');
        $this->assertStringContainsString('/media/', $url);
        $this->assertStringNotContainsString('/storage/', $url);

        // Clean up the real file this test wrote to storage/app/media.
        $depense->getFirstMedia('justificatifs')->delete();
    }

    public function test_a_forbidden_mime_type_is_rejected(): void
    {
        Storage::fake('media');
        $user = $this->userWith('expenses.view', 'expenses.create');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $this->type->id,
            'caisse_id' => $caisse->id,
            'montant' => '120',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-15',
            'justificatifs' => [UploadedFile::fake()->create('virus.exe', 10)],
        ])->assertSessionHasErrors('justificatifs.0');
    }

    public function test_montant_and_caisse_are_frozen_on_update_even_when_tampered(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create', 'expenses.update');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();
        $caisse->update(['solde' => 5000]);
        $otherCaisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $depense = Depense::create([
            'reference' => 'DEP-EDIT', 'type_depense_id' => $this->type->id, 'caisse_id' => $caisse->id,
            'montant' => 120, 'methode_paiement' => 'Espèces', 'date_depense' => '2025-09-15',
            'agent_id' => $user->employee->id,
        ]);

        $this->put(route('backoffice.depenses.update', $depense), [
            'type_depense_id' => $this->type->id,
            'date_depense' => '2025-09-16',
            'description' => 'Updated',
            // Tampered — must have zero effect.
            'montant' => '9999',
            'caisse_id' => $otherCaisse->id,
        ])->assertSessionDoesntHaveErrors();

        $fresh = $depense->fresh();
        $this->assertSame('120.00', (string) $fresh->montant);
        $this->assertSame($caisse->id, $fresh->caisse_id);
        $this->assertSame('Updated', $fresh->description);
    }

    public function test_a_stored_receipt_can_be_removed(): void
    {
        Storage::fake('media');
        $user = $this->userWith('expenses.view', 'expenses.create', 'expenses.update');
        $this->actingAs($user);
        $caisse = $user->employee->caisses()->first();

        $depense = Depense::create([
            'reference' => 'DEP-RM', 'type_depense_id' => $this->type->id, 'caisse_id' => $caisse->id,
            'montant' => 100, 'date_depense' => '2025-09-15', 'agent_id' => $user->employee->id,
        ]);
        $depense->addMedia(UploadedFile::fake()->image('recu.png'))->toMediaCollection('justificatifs');
        $mediaId = (int) $depense->getFirstMedia('justificatifs')->id;

        $this->delete(route('backoffice.depenses.justificatifs.destroy', ['depense' => $depense->id, 'media' => $mediaId]))
            ->assertRedirect(route('backoffice.depenses.index'));

        $this->assertSame(0, $depense->fresh()->getMedia('justificatifs')->count());
        $this->assertNotNull(Depense::find($depense->id));
    }

    public function test_no_delete_route_exists_for_expenses(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.depenses.destroy'));
    }
}
