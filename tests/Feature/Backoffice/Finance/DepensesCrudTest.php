<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Livewire\Backoffice\Depenses\DepensesIndex;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class DepensesCrudTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Caisse $caisse;

    private TypeDepense $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
        $this->caisse = Caisse::factory()->create([
            'etablissement_id' => $this->centre->id,
            'solde' => 5000,
        ]);
        $this->type = TypeDepense::create([
            'nom' => 'Fournitures de bureau',
            'is_system' => false,
            'statut' => TypeDepense::STATUT_ACTIF,
        ]);
    }

    /** Super-admin WITH a linked employee — money operations need an agent. */
    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($user->fresh());

        return $user;
    }

    private function makeDepense(array $attrs = []): Depense
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        return Depense::create([
            'reference' => 'DEP-'.fake()->unique()->numerify('9#####'),
            'type_depense_id' => $this->type->id,
            'caisse_id' => $this->caisse->id,
            'montant' => 250,
            'date_depense' => '2026-01-15',
            'description' => 'Achat papier',
            'agent_id' => $agent->id,
            ...$attrs,
        ]);
    }

    // --- Access ------------------------------------------------------------

    public function test_the_page_renders_for_a_user_with_expenses_view(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('expenses.view');
        $this->actingAs($user);

        Livewire::test(DepensesIndex::class)->assertOk();
    }

    public function test_the_page_is_forbidden_without_expenses_view(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');
        $this->actingAs($user);

        Livewire::test(DepensesIndex::class)->assertForbidden();
    }

    public function test_creating_is_forbidden_without_expenses_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('expenses.view');
        $this->actingAs($user);

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->assertForbidden();
    }

    // --- Billing fields (legacy-app parity) ---------------------------------

    public function test_an_expense_stores_invoice_reference_payment_method_and_group(): void
    {
        $this->admin();
        $annee = \App\Models\AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $group = \App\Models\Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id,
        ]);

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('group_id', $group->id)
            ->set('montant', '300')
            ->set('methode_paiement', 'Virement')
            ->set('date_depense', '2026-02-15')
            ->set('reference_facture', 'FA-2026-0154')
            ->call('save')
            ->assertHasNoErrors();

        $depense = Depense::where('reference_facture', 'FA-2026-0154')->firstOrFail();
        $this->assertSame('Virement', $depense->methode_paiement);
        $this->assertSame($group->id, $depense->group_id);
        // The invariant still holds: the till balance moved in the action.
        $this->assertEquals(4700.0, (float) $this->caisse->fresh()->solde);
    }

    public function test_the_payment_method_is_required_when_creating(): void
    {
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('montant', '100')
            ->set('date_depense', '2026-02-15')
            ->call('save')
            ->assertHasErrors(['methode_paiement' => 'required']);
    }

    public function test_an_unknown_payment_method_is_rejected(): void
    {
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('montant', '100')
            ->set('methode_paiement', 'Bitcoin')
            ->set('date_depense', '2026-02-15')
            ->call('save')
            ->assertHasErrors('methode_paiement');
    }

    // --- Validation --------------------------------------------------------

    public function test_required_fields_are_validated(): void
    {
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', null)
            ->set('caisse_id', null)
            ->set('montant', '')
            ->set('date_depense', '')
            ->call('save')
            ->assertHasErrors(['type_depense_id', 'caisse_id', 'montant', 'date_depense']);
    }

    public function test_the_amount_must_be_strictly_positive(): void
    {
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('montant', '0')
            ->set('date_depense', '2026-02-01')
            ->call('save')
            ->assertHasErrors('montant');
    }

    // --- Creation goes through the Domain action ---------------------------

    public function test_recording_an_expense_decreases_the_till_balance_and_generates_a_reference(): void
    {
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('montant', '750.50')
            ->set('methode_paiement', 'Espèces')
            ->set('date_depense', '2026-02-10')
            ->set('description', 'Achat imprimante')
            ->set('mots_cles', 'bureau,materiel')
            ->call('save')
            ->assertHasNoErrors();

        $depense = Depense::where('description', 'Achat imprimante')->first();
        $this->assertNotNull($depense);
        // System-generated reference (ReferenceGenerator inside EnregistrerDepense).
        $this->assertStringStartsWith('DEP-', (string) $depense->reference);
        // The acting employee is stamped as agent.
        $this->assertNotNull($depense->agent_id);

        // caisses.solde decremented by the amount, in the same transaction.
        $this->assertEquals(5000 - 750.50, (float) $this->caisse->fresh()->solde);
    }

    // --- Receipts (justificatifs) ------------------------------------------

    public function test_a_receipt_is_attached_to_the_justificatifs_collection(): void
    {
        Storage::fake('media');
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('montant', '120')
            ->set('methode_paiement', 'Espèces')
            ->set('date_depense', '2026-02-11')
            ->set('description', 'Avec justificatif')
            ->set('justificatifs', [UploadedFile::fake()->image('facture.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $depense = Depense::where('description', 'Avec justificatif')->first();
        $media = $depense->getFirstMedia('justificatifs');
        $this->assertNotNull($media);
        $this->assertSame(1, $depense->getMedia('justificatifs')->count());
        // Stored on the dedicated `media` disk, under the 8-char-uuid folder
        // built by ShortUuidPathGenerator.
        $this->assertSame('media', $media->disk);
        Storage::disk('media')->assertExists(substr((string) $media->uuid, 0, 8).'/'.$media->file_name);
    }

    public function test_receipt_urls_are_served_from_media_not_storage(): void
    {
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('montant', '90')
            ->set('methode_paiement', 'TPE')
            ->set('date_depense', '2026-02-12')
            ->set('description', 'URL justificatif')
            ->set('justificatifs', [UploadedFile::fake()->image('recu.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        // Public URLs are /media/<8-char-uuid>/… — never /storage/…
        $url = Depense::where('description', 'URL justificatif')->first()->getFirstMediaUrl('justificatifs');
        $this->assertStringContainsString('/media/', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_a_receipt_with_a_forbidden_mime_type_is_rejected(): void
    {
        Storage::fake('media');
        $this->admin();

        Livewire::test(DepensesIndex::class)
            ->call('create')
            ->set('type_depense_id', $this->type->id)
            ->set('caisse_id', $this->caisse->id)
            ->set('montant', '120')
            ->set('date_depense', '2026-02-11')
            ->set('justificatifs', [UploadedFile::fake()->create('virus.exe', 10)])
            ->call('save')
            ->assertHasErrors('justificatifs.0');
    }

    public function test_a_stored_receipt_can_be_removed_while_editing(): void
    {
        Storage::fake('media');
        $this->admin();

        $depense = $this->makeDepense();
        $depense->addMedia(UploadedFile::fake()->image('recu.png'))->toMediaCollection('justificatifs');
        $mediaId = (int) $depense->getFirstMedia('justificatifs')->id;

        Livewire::test(DepensesIndex::class)
            ->call('edit', $depense->id)
            ->assertSet("existingJustificatifs.{$mediaId}.name", 'recu.png')
            ->call('removeJustificatif', $mediaId)
            ->assertHasNoErrors();

        $this->assertSame(0, $depense->fresh()->getMedia('justificatifs')->count());
        // The expense itself is untouched.
        $this->assertDatabaseHas('depenses', ['id' => $depense->id]);
    }

    // --- Immutability ------------------------------------------------------

    public function test_amount_and_cash_register_are_not_editable_after_creation(): void
    {
        $this->admin();
        $depense = $this->makeDepense();
        $otherCaisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 100]);

        Livewire::test(DepensesIndex::class)
            ->call('edit', $depense->id)
            ->set('montant', '99999')
            ->set('caisse_id', $otherCaisse->id)
            ->set('description', 'Description corrigée')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $depense->fresh();
        // Editable field changed…
        $this->assertSame('Description corrigée', $fresh->description);
        // …but the money fields did not move.
        $this->assertEquals(250.0, (float) $fresh->montant);
        $this->assertSame($this->caisse->id, $fresh->caisse_id);
        // No balance was touched by the update.
        $this->assertEquals(5000.0, (float) $this->caisse->fresh()->solde);
        $this->assertEquals(100.0, (float) $otherCaisse->fresh()->solde);
    }

    public function test_expenses_have_no_destroy_route_and_no_delete_action(): void
    {
        // An expense is never deleted (audit trail, schema §13).
        $this->assertFalse(Route::has('backoffice.depenses.destroy'));
        $this->assertFalse(method_exists(DepensesIndex::class, 'delete'));
    }

    // --- List: search, filters, scoping -------------------------------------

    public function test_the_list_can_be_searched_and_filtered(): void
    {
        $this->admin();
        $autreType = TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->makeDepense(['description' => 'Achat papier', 'date_depense' => '2026-01-15']);
        $this->makeDepense([
            'description' => 'Loyer janvier',
            'type_depense_id' => $autreType->id,
            'date_depense' => '2026-03-20',
        ]);

        Livewire::test(DepensesIndex::class)
            ->assertSee('Achat papier')
            ->assertSee('Loyer janvier')
            // Search on the description.
            ->set('search', 'papier')
            ->assertSee('Achat papier')
            ->assertDontSee('Loyer janvier')
            ->set('search', '')
            // Filter on the expense type.
            ->set('typeFilter', (string) $autreType->id)
            ->assertSee('Loyer janvier')
            ->assertDontSee('Achat papier')
            ->set('typeFilter', '')
            // Date range filter.
            ->set('dateFrom', '2026-03-01')
            ->assertSee('Loyer janvier')
            ->assertDontSee('Achat papier');
    }

    public function test_the_list_is_scoped_to_the_users_center(): void
    {
        $autreCentre = Etablissement::factory()->create();
        $autreCaisse = Caisse::factory()->create(['etablissement_id' => $autreCentre->id, 'solde' => 900]);

        $this->makeDepense(['description' => 'Depense mon centre']);
        $this->makeDepense(['description' => 'Depense autre centre', 'caisse_id' => $autreCaisse->id]);

        // Center-scoped user (no centers.access-all) attached to $this->centre.
        $user = User::factory()->create();
        $user->givePermissionTo('expenses.view');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($user->fresh());

        Livewire::test(DepensesIndex::class)
            ->assertSee('Depense mon centre')
            ->assertDontSee('Depense autre centre');
    }
}
