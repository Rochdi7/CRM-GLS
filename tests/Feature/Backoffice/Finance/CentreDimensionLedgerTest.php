<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Expenses\Actions\ApprouverDepense;
use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Payments\Actions\SupprimerEncaissement;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Centre dimension of ledger movements (01/09/2026 fix).
 *
 * One employee keeps exactly ONE physical till, whatever centres they work
 * in — but every ledger entry now stamps the CENTRE of the operation, so a
 * multi-centre cashier's till can be broken down per centre from the ledger
 * alone, without ever splitting/moving/recomputing `caisses.solde`.
 *
 * Also under test: an employee profile edit NEVER moves the caisse — it only
 * warns when the primary centre and the till's centre diverge.
 */
final class CentreDimensionLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $online;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->online = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);
    }

    private function superAdmin(int $centreId): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centreId]);

        return $user->fresh();
    }

    /** The centre stamped on the latest `caisse` journal entry. */
    private function lastEntryCentre(): ?int
    {
        $props = Activity::query()->where('log_name', 'caisse')
            ->where('event', 'solde_movement')->latest('id')->firstOrFail()->properties;

        return $props['etablissement_id'] ?? null;
    }

    private function cash(Employee $agent, Student $student, float $montant): Encaissement
    {
        return app(EnregistrerEncaissement::class)->handle([
            'student_id' => $student->id,
            'inscription_fee_id' => null,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-09-01',
            'caisse_id' => $agent->till()->firstOrFail()->id,
        ], $agent);
    }

    // ---------------------------------------------------------------
    // Encaissements
    // ---------------------------------------------------------------

    public function test_cash_from_two_centres_lands_in_the_same_till_with_each_entry_stamped(): void
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $till = $agent->till()->firstOrFail();

        $this->cash($agent, Student::factory()->create(['etablissement_id' => $this->rabat->id]), 500.0);
        $this->assertSame($this->rabat->id, $this->lastEntryCentre());

        $this->cash($agent, Student::factory()->create(['etablissement_id' => $this->online->id]), 300.0);
        $this->assertSame($this->online->id, $this->lastEntryCentre());

        // ONE till, global balance authoritative, nothing split.
        $this->assertSame(1, Caisse::query()->where('responsable_employee_id', $agent->id)->count());
        $this->assertSame('800.00', (string) $till->fresh()->solde);
        // The till row itself was not re-homed by any of this.
        $this->assertSame($this->online->id, $till->fresh()->etablissement_id);
    }

    public function test_ledger_breakdown_per_centre_reconciles_with_the_till_balance(): void
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $till = $agent->till()->firstOrFail();

        $this->cash($agent, Student::factory()->create(['etablissement_id' => $this->rabat->id]), 500.0);
        $this->cash($agent, Student::factory()->create(['etablissement_id' => $this->rabat->id]), 2500.0);
        $this->cash($agent, Student::factory()->create(['etablissement_id' => $this->online->id]), 700.0);

        $rows = Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->where('subject_type', Caisse::class)
            ->where('subject_id', $till->id)
            ->get()
            ->groupBy(fn (Activity $a) => $a->properties['etablissement_id'] ?? 0)
            ->map(fn ($entries) => $entries->sum(fn (Activity $a) => ($a->properties['sens'] === 'Entrée' ? 1 : -1) * (float) $a->properties['montant']));

        $this->assertSame(3000.0, $rows[$this->rabat->id]);
        $this->assertSame(700.0, $rows[$this->online->id]);
        // Per-centre sums reconcile exactly with the authoritative balance.
        $this->assertSame(3700.0, (float) $till->fresh()->solde);
    }

    public function test_deleting_a_payment_reverses_with_the_original_centre(): void
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $enc = $this->cash($agent, Student::factory()->create(['etablissement_id' => $this->rabat->id]), 400.0);

        app(SupprimerEncaissement::class)->handle($enc);

        $this->assertSame($this->rabat->id, $this->lastEntryCentre());
        $this->assertSame('0.00', (string) $agent->till()->firstOrFail()->solde);
    }

    // ---------------------------------------------------------------
    // Remboursements
    // ---------------------------------------------------------------

    public function test_linked_refund_carries_the_original_payment_centre_even_after_the_student_moved(): void
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $student = Student::factory()->create(['etablissement_id' => $this->rabat->id]);
        $enc = $this->cash($agent, $student, 500.0);

        // The student is transferred to Online AFTER paying in Rabat.
        $student->update(['etablissement_id' => $this->online->id]);

        app(EnregistrerRemboursement::class)->handle([
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $enc->id,
            'caisse_id' => $agent->till()->firstOrFail()->id,
            'montant' => 200.0,
            'date_remboursement' => '2026-09-01',
            'motif' => 'Test',
        ], $agent);

        // Reverses the ORIGINAL financial context (Rabat), never the
        // student's current centre.
        $this->assertSame($this->rabat->id, $this->lastEntryCentre());
        // Historical payment untouched.
        $this->assertSame($this->rabat->id, $enc->fresh()->etablissement_id);
    }

    public function test_unlinked_refund_carries_the_active_context_centre(): void
    {
        $this->actingAs($this->superAdmin($this->online->id));
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        $agent = Employee::factory()->create(['etablissement_id' => $this->online->id]);

        app(EnregistrerRemboursement::class)->handle([
            'beneficiaire_id' => Student::factory()->create(['etablissement_id' => $this->rabat->id])->id,
            'encaissement_id' => null,
            'caisse_id' => $agent->till()->firstOrFail()->id,
            'montant' => 50.0,
            'date_remboursement' => '2026-09-01',
        ], $agent);

        $this->assertSame($this->rabat->id, $this->lastEntryCentre());
    }

    // ---------------------------------------------------------------
    // Dépenses
    // ---------------------------------------------------------------

    public function test_ordinary_expense_stamps_the_context_centre_when_approval_is_off(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $this->actingAs($this->superAdmin($this->online->id));
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        $agent = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $type = TypeDepense::create(['nom' => 'Fournitures', 'statut' => 'Actif']);

        app(EnregistrerDepense::class)->handle([
            'type_depense_id' => $type->id,
            'caisse_id' => $agent->till()->firstOrFail()->id,
            'montant' => 100.0,
            'date_depense' => '2026-09-01',
            'description' => 'Test',
        ], $agent);

        $this->assertSame($this->rabat->id, $this->lastEntryCentre());
    }

    public function test_paiement_prof_stamps_the_group_centre_at_approval(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);

        $agent = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $approver = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $type = TypeDepense::create(['nom' => 'Paiement prof', 'is_system' => true, 'statut' => 'Actif']);
        $group = Group::factory()->create(['etablissement_id' => $this->rabat->id]);

        $depense = app(EnregistrerDepense::class)->handle([
            'type_depense_id' => $type->id,
            'caisse_id' => $agent->till()->firstOrFail()->id,
            'group_id' => $group->id,
            'montant' => 900.0,
            'date_depense' => '2026-09-01',
            'periode_debut' => '2026-08-01',
            'periode_fin' => '2026-08-31',
            'description' => 'Août',
        ], $agent);

        // Approval ON: nothing journaled, nothing debited yet.
        $this->assertSame('0.00', (string) $agent->till()->firstOrFail()->solde);

        app(ApprouverDepense::class)->handle($depense, $approver);

        // The GROUP's centre — not the approver's, not the agent's.
        $this->assertSame($this->rabat->id, $this->lastEntryCentre());
        $this->assertSame('-900.00', (string) $agent->till()->firstOrFail()->solde);
    }

    // ---------------------------------------------------------------
    // Transfers
    // ---------------------------------------------------------------

    public function test_cross_centre_transfer_stamps_each_leg_with_its_own_caisse_centre(): void
    {
        $source = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $dest = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $sourceTill = $source->till()->firstOrFail();
        $destTill = $dest->till()->firstOrFail();

        $this->cash($source, Student::factory()->create(['etablissement_id' => $this->rabat->id]), 1000.0);

        $transfer = CaisseTransfer::create([
            'reference' => 'TRF-TEST-1',
            'caisse_source_id' => $sourceTill->id,
            'caisse_destination_id' => $destTill->id,
            'montant' => 400.0,
            'date_transfert' => now(),
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'requested_by' => $source->id,
        ]);

        app(ValiderTransfertCaisse::class)->handle($transfer, $dest);

        $legs = Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->whereIn('subject_id', [$sourceTill->id, $destTill->id])
            ->latest('id')->limit(2)->get();
        $byCaisse = $legs->keyBy('subject_id');

        $this->assertSame($this->rabat->id, $byCaisse[$sourceTill->id]->properties['etablissement_id']);
        $this->assertSame($this->online->id, $byCaisse[$destTill->id]->properties['etablissement_id']);
        $this->assertSame('600.00', (string) $sourceTill->fresh()->solde);
        $this->assertSame('400.00', (string) $destTill->fresh()->solde);
    }

    // ---------------------------------------------------------------
    // Employee profile edits never move the caisse
    // ---------------------------------------------------------------

    public function test_changing_the_primary_centre_leaves_the_till_untouched_and_warns(): void
    {
        $this->actingAs($admin = $this->superAdmin($this->online->id));

        $employee = Employee::factory()->create([
            'etablissement_id' => $this->online->id,
            'nom' => 'Barnicha', 'prenom' => 'Fatine',
        ]);
        $employee->syncEtablissements([$this->online->id]);
        $till = $employee->till()->firstOrFail();
        $this->cash($employee, Student::factory()->create(['etablissement_id' => $this->rabat->id]), 2200.0);

        $response = $this->put(route('backoffice.employees.update', $employee), [
            'nom' => 'Barnicha', 'prenom' => 'Fatine', 'sexe' => 'Femme',
            'categorie' => 'Assistante administrative',
            'statut' => 'Actif',
            'etablissement_ids' => [$this->rabat->id],
        ]);

        $response->assertSessionHas('warning');

        // Primary moved, caisse did NOT: same row, same centre, same solde.
        $this->assertSame($this->rabat->id, $employee->fresh()->etablissement_id);
        $fresh = $till->fresh();
        $this->assertSame($till->id, $fresh->id);
        $this->assertSame($this->online->id, $fresh->etablissement_id);
        $this->assertSame('2200.00', (string) $fresh->solde);
    }

    public function test_no_warning_when_the_till_centre_matches_the_new_primary(): void
    {
        $this->actingAs($this->superAdmin($this->online->id));

        $employee = Employee::factory()->create(['etablissement_id' => $this->online->id]);
        $employee->syncEtablissements([$this->online->id]);

        $response = $this->put(route('backoffice.employees.update', $employee), [
            'nom' => $employee->nom, 'prenom' => $employee->prenom, 'sexe' => $employee->sexe ?? 'Femme',
            'categorie' => $employee->categorie,
            'statut' => 'Actif',
            'etablissement_ids' => [$this->online->id],
        ]);

        $response->assertSessionMissing('warning');
        $this->assertSame($this->online->id, $employee->fresh()->etablissement_id);
    }
}
