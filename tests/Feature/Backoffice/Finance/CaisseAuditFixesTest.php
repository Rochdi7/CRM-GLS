<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseDetails;
use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TypeDepenseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for the confirmed findings of the 27/08/2026 Caisse audit.
 *
 * Each test pins a rule that was NOT enforced before the fix, so a later
 * refactor that quietly restores the old behaviour fails here rather than in
 * production accounting.
 */
final class CaisseAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $marrakech;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->marrakech = Etablissement::factory()->create();
        $this->rabat = Etablissement::factory()->create();
    }

    /** @param  list<string>  $permissions */
    private function userIn(Etablissement $centre, array $permissions, bool $allCenters = false): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }

        if ($allCenters) {
            $user->givePermissionTo('centers.access-all');
        }

        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    private function context(?int $etablissementId): void
    {
        app(CurrentContext::class)->setEtablissement($etablissementId);
    }

    // ── P0-1 · the transfer inbox must ignore the active centre ──────────

    /**
     * The recipient is the ONLY person who can validate, so a pending
     * transfer that hides behind a centre switch can never be cleared by
     * anyone — the money stays « En attente » forever. CLAUDE.md §11 lists
     * the inbox as a deliberate exception to context scoping.
     */
    public function test_a_pending_transfer_stays_visible_after_the_recipient_switches_centre(): void
    {
        $recipient = $this->userIn($this->marrakech, ['cash-transfers.view', 'cash-transfers.validate'], allCenters: true);
        $recipient->employee->syncEtablissements([$this->marrakech->id, $this->rabat->id]);

        $destination = $recipient->employee->till()->first();
        $source = Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->marrakech->id,
            'solde' => 1000,
        ]);

        $transfer = CaisseTransfer::create([
            'reference' => 'TRF-'.fake()->unique()->numerify('#####'),
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => 300,
            'date_transfert' => now(),
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'requested_by' => Employee::factory()->create(['etablissement_id' => $this->marrakech->id])->id,
        ]);

        $this->actingAs($recipient);
        $list = app(GetCaisseTransfersList::class);

        // Working in the till's own centre — visible, as before.
        $this->context($this->marrakech->id);
        $this->assertTrue(
            $list($recipient)['data']->pluck('id')->contains($transfer->id),
            'The pending transfer must be listed in its own centre.',
        );

        // Switched to another assigned centre — it must NOT disappear.
        $this->context($this->rabat->id);
        $this->assertTrue(
            $list($recipient)['data']->pluck('id')->contains($transfer->id),
            'A pending transfer must survive a centre switch, or nobody can ever validate it.',
        );

        // The status tab counters follow the same rule.
        $this->assertSame(
            1,
            (int) ($list->statutCounts($recipient)[CaisseTransfer::STATUT_EN_ATTENTE] ?? 0),
        );
    }

    /** Widening the inbox must not widen who may ACT on the row. */
    public function test_a_colleague_in_the_same_centre_still_cannot_validate(): void
    {
        $recipient = $this->userIn($this->marrakech, ['cash-transfers.view'], allCenters: true);
        $bystander = $this->userIn($this->marrakech, ['cash-transfers.view', 'cash-transfers.validate'], allCenters: true);

        $source = Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->marrakech->id,
            'solde' => 1000,
        ]);

        $transfer = CaisseTransfer::create([
            'reference' => 'TRF-'.fake()->unique()->numerify('#####'),
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $recipient->employee->till()->first()->id,
            'montant' => 300,
            'date_transfert' => now(),
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'requested_by' => Employee::factory()->create(['etablissement_id' => $this->marrakech->id])->id,
        ]);

        $this->actingAs($bystander);
        $this->context($this->marrakech->id);

        // CaisseTransferPolicy@validate refuses outright (403) — holding
        // `cash-transfers.validate` is not enough, the validator must OWN the
        // destination till.
        $this->put(route('backoffice.caisse-transfers.validate', $transfer))
            ->assertForbidden();

        $this->assertSame(CaisseTransfer::STATUT_EN_ATTENTE, $transfer->fresh()->statut);
        $this->assertSame('1000.00', (string) $source->fresh()->solde);
    }

    /** The destination dropdown stays centre-scoped — only the inbox widened. */
    public function test_caisse_options_remain_scoped_to_the_active_centre(): void
    {
        $user = $this->userIn($this->marrakech, ['cash-transfers.view', 'cash-transfers.create'], allCenters: true);
        $user->employee->syncEtablissements([$this->marrakech->id, $this->rabat->id]);

        $rabatCaisse = Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $this->rabat->id,
            'solde' => 0,
        ]);

        $this->actingAs($user);
        $this->context($this->marrakech->id);

        $this->assertFalse(
            app(GetCaisseTransfersList::class)->caisseOptions($user)->pluck('id')->contains($rabatCaisse->id),
            "A transfer's destination list must stay narrowed to the active centre.",
        );
    }

    // ── P0-3 · dépense approve/refuse/update honour the active centre ────

    private function activeType(): TypeDepense
    {
        return TypeDepense::firstOrCreate(
            ['nom' => 'Fournitures'],
            ['is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF],
        );
    }

    private function pendingDepenseIn(Etablissement $centre): Depense
    {
        $owner = $this->userIn($centre, ['expenses.view']);

        return Depense::create([
            'reference' => 'DEP-'.fake()->unique()->numerify('#####'),
            'type_depense_id' => $this->activeType()->id,
            'caisse_id' => $owner->employee->till()->first()->id,
            'agent_id' => $owner->employee->id,
            'montant' => 200,
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-20',
            'statut' => Depense::STATUT_EN_ATTENTE,
            'description' => 'En attente',
        ]);
    }

    public function test_approving_an_expense_of_another_centre_is_refused(): void
    {
        $this->seed(TypeDepenseSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);

        $depense = $this->pendingDepenseIn($this->rabat);
        $soldeBefore = $depense->caisse->solde;

        $approver = $this->userIn($this->marrakech, ['expenses.view', 'expenses.approve'], allCenters: true);
        $approver->employee->syncEtablissements([$this->marrakech->id, $this->rabat->id]);

        $this->actingAs($approver);
        $this->context($this->marrakech->id); // working in Marrakech…

        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasErrors();

        // …so the Rabat till must not have been debited.
        $this->assertSame(Depense::STATUT_EN_ATTENTE, $depense->fresh()->statut);
        $this->assertSame((string) $soldeBefore, (string) $depense->caisse->fresh()->solde);
    }

    public function test_refusing_an_expense_of_another_centre_is_refused(): void
    {
        $this->seed(TypeDepenseSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);

        $depense = $this->pendingDepenseIn($this->rabat);

        $approver = $this->userIn($this->marrakech, ['expenses.view', 'expenses.approve'], allCenters: true);
        $approver->employee->syncEtablissements([$this->marrakech->id, $this->rabat->id]);

        $this->actingAs($approver);
        $this->context($this->marrakech->id);

        $this->put(route('backoffice.depenses.refuse', $depense), ['motif_refus' => 'non'])
            ->assertSessionHasErrors();

        $this->assertSame(Depense::STATUT_EN_ATTENTE, $depense->fresh()->statut);
    }

    public function test_approving_an_expense_of_the_active_centre_still_works(): void
    {
        $this->seed(TypeDepenseSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);

        $depense = $this->pendingDepenseIn($this->marrakech);

        $approver = $this->userIn($this->marrakech, ['expenses.view', 'expenses.approve'], allCenters: true);

        $this->actingAs($approver);
        $this->context($this->marrakech->id);

        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasNoErrors();

        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->fresh()->statut);
        // Approval is the moment the money leaves: 0 - 200.
        $this->assertSame('-200.00', (string) $depense->caisse->fresh()->solde);
    }

    // ── P1-7 · an inactive expense type can no longer be chosen ──────────

    public function test_an_inactive_expense_type_is_refused_on_creation(): void
    {
        $this->seed(TypeDepenseSeeder::class);

        $type = TypeDepense::create([
            'nom' => 'Retiré',
            'is_system' => false,
            'statut' => TypeDepense::STATUT_INACTIF,
        ]);

        $user = $this->userIn($this->marrakech, ['expenses.view', 'expenses.create'], allCenters: true);

        $this->actingAs($user);
        $this->context($this->marrakech->id);

        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $type->id,
            'montant' => '100',
            'date_depense' => now()->toDateString(),
            'description' => 'Test',
        ])->assertSessionHasErrors('type_depense_id');

        $this->assertSame(0, Depense::query()->count());
    }

    // ── P1-5 · the caisse detail page agrees with the journal ────────────

    /**
     * A pending dépense debited nothing, so listing it as a till movement
     * made the page contradict the balance printed above it.
     */
    public function test_caisse_details_lists_only_movements_that_moved_the_till(): void
    {
        $this->seed(TypeDepenseSeeder::class);

        $owner = $this->userIn($this->marrakech, ['expenses.view']);
        $till = $owner->employee->till()->first();

        $approuvee = Depense::create([
            'reference' => 'DEP-OK',
            'type_depense_id' => $this->activeType()->id,
            'caisse_id' => $till->id,
            'agent_id' => $owner->employee->id,
            'montant' => 100,
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-20',
            'statut' => Depense::STATUT_APPROUVEE,
            'description' => 'Approuvée',
        ]);

        $enAttente = Depense::create([
            'reference' => 'DEP-WAIT',
            'type_depense_id' => $this->activeType()->id,
            'caisse_id' => $till->id,
            'agent_id' => $owner->employee->id,
            'montant' => 250,
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-20',
            'statut' => Depense::STATUT_EN_ATTENTE,
            'description' => 'En attente',
        ]);

        $references = collect(app(GetCaisseDetails::class)($till)['depenses'])->pluck('reference');

        $this->assertTrue($references->contains($approuvee->reference));
        $this->assertFalse(
            $references->contains($enAttente->reference),
            'A pending expense never moved the till and must not be listed as a movement.',
        );
    }
}
