<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A dépense — ordinary or « Paiement prof » — leaves the account of its
 * METHOD (03/11/2026, CaisseResolver::forDepense):
 *  - TPE / Virement → the account of that method of the dépense's centre
 *    (group centre for a paiement prof, else the centre it was keyed in);
 *  - Espèces → the physical till of the employee who keyed the request,
 *    never the approver's.
 * The account is stored at creation, so approval debits exactly that row.
 */
final class DepenseMethodeCompteCentreTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $online;

    private AnneeScolaire $annee;

    private TypeDepense $type;

    private TypeDepense $profType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-03 10:00:00');
        $this->seed(RolesAndPermissionsSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);
        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-08-27', 'date_fin' => '2027-08-26',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->online = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);
        $this->type = TypeDepense::create(['nom' => 'Fournitures', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);
        $this->profType = TypeDepense::create([
            'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF, 'is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
    }

    /** Primary centre Rabat (its till lives there), also assigned to Online. */
    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['expenses.view', 'expenses.create', 'expenses.update']);

        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);
        $employee->syncEtablissements([$this->rabat->id, $this->online->id], $this->rabat->id);

        return $user->fresh();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);

        return $user->fresh();
    }

    private function compte(Etablissement $centre, string $methode): Caisse
    {
        return Caisse::query()->where('etablissement_id', $centre->id)->where('type', $methode)->firstOrFail();
    }

    private function keyIn(User $user, Etablissement $centre, array $overrides = []): Depense
    {
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($centre->id);

        $this->post(route('backoffice.depenses.store'), array_merge([
            'type_depense_id' => $this->type->id,
            'montant' => '300.00',
            'methode_paiement' => Encaissement::METHODE_VIREMENT,
            'date_depense' => '2026-11-03',
            'description' => 'Test',
        ], $overrides))->assertSessionHasNoErrors();

        return Depense::query()->latest('id')->firstOrFail();
    }

    private function approve(Depense $depense): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($this->superAdmin());
        app(CurrentContext::class)->setEtablissement(null);

        return $this->put(route('backoffice.depenses.approve', $depense));
    }

    /** @return list<list<string>> */
    public static function methodesCompteCentre(): array
    {
        return [[Encaissement::METHODE_VIREMENT], [Encaissement::METHODE_TPE]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('methodesCompteCentre')]
    public function test_a_non_cash_depense_is_debited_from_its_centres_method_account_at_approval(string $methode): void
    {
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();
        $till->update(['solde' => '1000.00']);
        $compte = $this->compte($this->online, $methode);
        $compte->update(['solde' => '500.00']);

        $depense = $this->keyIn($user, $this->online, ['methode_paiement' => $methode]);

        // Stored at creation, nothing debited while pending.
        $this->assertSame($compte->id, $depense->caisse_id);
        $this->assertSame('500.00', (string) $compte->fresh()->solde);

        $this->approve($depense)->assertSessionHasNoErrors();

        $this->assertSame('200.00', (string) $compte->fresh()->solde);
        $this->assertSame('1000.00', (string) $till->fresh()->solde);
        // The other centre's account of the same method is never touched.
        $this->assertSame('0.00', (string) $this->compte($this->rabat, $methode)->solde);
    }

    public function test_a_cash_depense_debits_the_requesters_till_never_the_approvers(): void
    {
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();
        $till->update(['solde' => '1000.00']);

        $depense = $this->keyIn($user, $this->rabat, ['methode_paiement' => Encaissement::METHODE_ESPECES]);
        $this->assertSame($till->id, $depense->caisse_id);

        $admin = $this->superAdmin();
        $adminTill = $admin->employee->till()->firstOrFail();
        $adminTill->update(['solde' => '1000.00']);
        $this->actingAs($admin);
        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasNoErrors();

        $this->assertSame('700.00', (string) $till->fresh()->solde);
        $this->assertSame('1000.00', (string) $adminTill->fresh()->solde);
    }

    public function test_a_virement_paiement_prof_leaves_the_groups_centre_account(): void
    {
        $user = $this->cashier();
        $group = Group::factory()->create(['etablissement_id' => $this->online->id, 'annee_scolaire_id' => $this->annee->id]);
        $compte = $this->compte($this->online, Encaissement::METHODE_VIREMENT);
        $compte->update(['solde' => '5000.00']);

        $depense = $this->keyIn($user, $this->online, [
            'type_depense_id' => $this->profType->id,
            'group_id' => $group->id,
            'montant' => '1200.00',
            'periode_debut' => '2026-10-01',
            'periode_fin' => '2026-10-31',
            'description' => 'Heures d\'octobre',
        ]);

        $this->assertSame($compte->id, $depense->caisse_id);
        $this->approve($depense)->assertSessionHasNoErrors();
        $this->assertSame('3800.00', (string) $compte->fresh()->solde);
    }

    /**
     * Signalé 04/11/2026 : un « Paiement prof » Virement de 13 000 DH saisi
     * sur GLS Online le 26/10 — AVANT la règle du 03/11 — pointait encore la
     * caisse physique de l'employé ; l'approbation le bornait par la part
     * d'Online de ce tiroir (5 310 DH) alors que le compte Virement d'Online
     * en contenait plus d'un million. Une ligne EN ATTENTE n'a rien débité :
     * l'approbation la re-pointe sur le compte de la méthode de son centre.
     */
    public function test_approving_a_pending_virement_keyed_before_the_rule_debits_the_centres_account_not_the_till(): void
    {
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();
        $till->update(['solde' => '300.00']);
        $compte = $this->compte($this->online, Encaissement::METHODE_VIREMENT);
        $compte->update(['solde' => '50000.00']);

        $depense = $this->keyIn($user, $this->online, ['montant' => '13000.00']);
        // As a row created before 03/11/2026 was stored: on the till.
        $depense->forceFill(['caisse_id' => $till->id])->saveQuietly();

        $this->approve($depense)->assertSessionHasNoErrors();

        $depense->refresh();
        $this->assertSame($compte->id, $depense->caisse_id);
        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->statut);
        $this->assertSame('37000.00', (string) $compte->fresh()->solde);
        $this->assertSame('300.00', (string) $till->fresh()->solde);
    }

    public function test_approval_is_refused_when_the_method_account_does_not_hold_the_amount(): void
    {
        $user = $this->cashier();
        $user->employee->till()->firstOrFail()->update(['solde' => '10000.00']);
        $compte = $this->compte($this->online, Encaissement::METHODE_TPE);
        $compte->update(['solde' => '100.00']);

        $depense = $this->keyIn($user, $this->online, ['methode_paiement' => Encaissement::METHODE_TPE]);

        $this->approve($depense)->assertSessionHasErrors('statut');
        $this->assertSame('100.00', (string) $compte->fresh()->solde);
        $this->assertTrue($depense->fresh()->isEnAttente());
    }

    public function test_approval_off_debits_the_method_account_immediately(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();
        $till->update(['solde' => '1000.00']);
        $compte = $this->compte($this->rabat, Encaissement::METHODE_VIREMENT);
        $compte->update(['solde' => '400.00']);

        $this->keyIn($user, $this->rabat);

        $this->assertSame('100.00', (string) $compte->fresh()->solde);
        $this->assertSame('1000.00', (string) $till->fresh()->solde);
    }

    public function test_changing_the_method_of_a_pending_depense_repoints_its_account(): void
    {
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();
        $depense = $this->keyIn($user, $this->online, ['methode_paiement' => Encaissement::METHODE_ESPECES]);
        $this->assertSame($till->id, $depense->caisse_id);

        $this->put(route('backoffice.depenses.update', $depense), [
            'type_depense_id' => $this->type->id,
            'montant' => '300.00',
            'methode_paiement' => Encaissement::METHODE_VIREMENT,
            'date_depense' => '2026-11-03',
            'description' => 'Test',
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->compte($this->online, Encaissement::METHODE_VIREMENT)->id, $depense->fresh()->caisse_id);
    }

    public function test_the_method_of_an_approved_depense_can_no_longer_move_its_account(): void
    {
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();
        $till->update(['solde' => '1000.00']);
        $depense = $this->keyIn($user, $this->online, ['methode_paiement' => Encaissement::METHODE_ESPECES]);
        $this->approve($depense)->assertSessionHasNoErrors();

        $this->actingAs($this->superAdmin());
        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->put(route('backoffice.depenses.update', $depense), [
            'type_depense_id' => $this->type->id,
            'montant' => '300.00',
            'methode_paiement' => Encaissement::METHODE_TPE,
            'date_depense' => '2026-11-03',
            'description' => 'Test',
        ])->assertSessionHasErrors('methode_paiement');

        $depense->refresh();
        $this->assertSame($till->id, $depense->caisse_id);
        $this->assertSame(Encaissement::METHODE_ESPECES, $depense->methode_paiement);
        $this->assertSame('700.00', (string) $till->fresh()->solde);
    }

    public function test_the_coherence_auditor_accepts_a_depense_in_its_own_centres_method_account(): void
    {
        $user = $this->cashier();
        $this->compte($this->online, Encaissement::METHODE_VIREMENT)->update(['solde' => '500.00']);
        $depense = $this->keyIn($user, $this->online);

        Artisan::call('caisse:verifier-coherence');
        $this->assertStringNotContainsString($depense->reference, Artisan::output());

        // Moved by hand into the WRONG centre's account: flagged.
        Depense::query()->whereKey($depense->id)->update([
            'caisse_id' => $this->compte($this->rabat, Encaissement::METHODE_VIREMENT)->id,
        ]);
        Artisan::call('caisse:verifier-coherence');
        $this->assertStringContainsString($depense->reference, Artisan::output());
    }

    // ── Preuve de paiement jointe à l'approbation (03/11/2026) ──────────

    private function approveWithProof(Depense $depense, UploadedFile $file): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($this->superAdmin());
        app(CurrentContext::class)->setEtablissement(null);

        return $this->post(route('backoffice.depenses.approve', $depense), [
            '_method' => 'put',
            'preuve_paiement' => $file,
        ]);
    }

    public function test_the_approver_may_attach_the_proof_of_a_virement_payment(): void
    {
        Storage::fake('media');
        $user = $this->cashier();
        $compte = $this->compte($this->online, Encaissement::METHODE_VIREMENT);
        $compte->update(['solde' => '500.00']);
        $depense = $this->keyIn($user, $this->online);

        $this->approveWithProof($depense, UploadedFile::fake()->image('virement.jpg'))->assertSessionHasNoErrors();

        $depense->refresh();
        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->statut);
        $this->assertSame('200.00', (string) $compte->fresh()->solde);
        $this->assertCount(1, $depense->getMedia(Depense::MEDIA_PREUVE_PAIEMENT));
        // Kept apart from the requester's receipts.
        $this->assertCount(0, $depense->getMedia('justificatifs'));
    }

    public function test_the_proof_is_optional(): void
    {
        $user = $this->cashier();
        $this->compte($this->online, Encaissement::METHODE_TPE)->update(['solde' => '500.00']);
        $depense = $this->keyIn($user, $this->online, ['methode_paiement' => Encaissement::METHODE_TPE]);

        $this->approve($depense)->assertSessionHasNoErrors();
        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->fresh()->statut);
    }

    public function test_a_proof_is_refused_on_a_cash_depense_and_nothing_moves(): void
    {
        Storage::fake('media');
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();
        $till->update(['solde' => '1000.00']);
        $depense = $this->keyIn($user, $this->rabat, ['methode_paiement' => Encaissement::METHODE_ESPECES]);

        $this->approveWithProof($depense, UploadedFile::fake()->image('recu.jpg'))->assertSessionHasErrors('preuve_paiement');

        $this->assertTrue($depense->fresh()->isEnAttente());
        $this->assertSame('1000.00', (string) $till->fresh()->solde);
    }

    public function test_a_proof_of_the_wrong_type_is_refused_and_nothing_moves(): void
    {
        Storage::fake('media');
        $user = $this->cashier();
        $compte = $this->compte($this->online, Encaissement::METHODE_VIREMENT);
        $compte->update(['solde' => '500.00']);
        $depense = $this->keyIn($user, $this->online);

        $this->approveWithProof($depense, UploadedFile::fake()->create('script.exe', 10))->assertSessionHasErrors('preuve_paiement');

        $this->assertTrue($depense->fresh()->isEnAttente());
        $this->assertSame('500.00', (string) $compte->fresh()->solde);
    }

    public function test_the_detail_page_serves_the_proof(): void
    {
        Storage::fake('media');
        $user = $this->cashier();
        $this->compte($this->online, Encaissement::METHODE_VIREMENT)->update(['solde' => '500.00']);
        $depense = $this->keyIn($user, $this->online);
        $this->approveWithProof($depense, UploadedFile::fake()->image('virement.png'))->assertSessionHasNoErrors();

        $this->get(route('backoffice.depenses.show', $depense))
            ->assertInertia(fn ($page) => $page->has('depense.preuvesPaiement', 1));
    }
}
