<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Finance\Support\CaisseResolver;
use App\Domain\Reports\Actions\GetAnnualFraisSummary;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\CaisseProvisioner;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression suite for the 24/08/2026 financial audit (see
 * docs/financial-audit-2026-08-24.md). Each test pins one bug that was found
 * in the live code or one invariant the fixes must keep true:
 *
 *  - the physical till is the Caissière row ONLY — never an Externe safe the
 *    employee happens to be responsable of (CaisseResolver::tillOf);
 *  - one physical till per employee, enforced by PostgreSQL;
 *  - a centre's method account is provisioned, never edited by hand;
 *  - centre isolation on avances and refunds (server-side, not the UI);
 *  - `caisse:recalculer-soldes` re-homes payments ONLY — never a dépense;
 *  - the annual chart attributes a payment to the student's centre like
 *    every other screen;
 *  - every balance equals the sum of its journaled movements.
 */
final class FinancialInvariantsAuditTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function userWith(array $permissions, ?Etablissement $centre = null, bool $global = true): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, ...($global ? ['centers.access-all'] : [])] as $p) {
            $user->givePermissionTo($p);
        }
        $centre ??= $this->centre;
        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $employee->syncEtablissements([$centre->id]);

        return $user->fresh();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolledStudentWithFee(float $montant = 1000, ?Etablissement $centre = null): array
    {
        $centre ??= $this->centre;
        $student = Student::factory()->create(['etablissement_id' => $centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    private function compte(Etablissement $centre, string $methode): Caisse
    {
        return Caisse::query()->where('etablissement_id', $centre->id)->where('type', $methode)->firstOrFail();
    }

    private function payLine(User $user, Student $student, Inscription $inscription, InscriptionFee $fee, string $montant, string $methode): void
    {
        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [[
                'fee_id' => $fee->id, 'montant' => $montant, 'methode' => $methode, 'date_paiement' => '2025-09-20',
            ]],
        ])->assertSessionHasNoErrors();
    }

    /** An « Externe » safe the employee is made responsable of (Comptes de caisse allows this). */
    private function externeSafeFor(Employee $employee): Caisse
    {
        return Caisse::create([
            'nom' => 'Coffre', 'type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id,
            'responsable_employee_id' => $employee->id, 'solde' => 0, 'statut' => Caisse::STATUT_ACTIVE,
        ]);
    }

    // ── The physical till is the Caissière row only ─────────────────────

    public function test_an_externe_safe_assigned_to_the_cashier_never_receives_or_pays_their_cash(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $user = $this->userWith(['payments.view', 'payments.create', 'expenses.view', 'expenses.create', 'refunds.create', 'cash-transfers.view', 'cash-transfers.create']);
        $employee = $user->employee;
        $till = $employee->till()->firstOrFail();
        $safe = $this->externeSafeFor($employee);
        // Make the safe the row `caisses()->first()` would have picked.
        $this->assertSame($till->id, $employee->fresh()->till()->firstOrFail()->id);

        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        // Cash payment → the till.
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_ESPECES);
        $this->assertSame('1000.00', (string) $till->fresh()->solde);
        $this->assertSame('0.00', (string) $safe->fresh()->solde);

        // Dépense → the till.
        $type = TypeDepense::create(['nom' => 'Fournitures', 'is_system' => false, 'statut' => 'Actif']);
        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $type->id, 'montant' => '100', 'methode_paiement' => Encaissement::METHODE_ESPECES,
            'date_depense' => '2025-09-22', 'description' => 'Fournitures',
        ])->assertSessionHasNoErrors();
        $this->assertSame($till->id, Depense::query()->sole()->caisse_id);

        // Refund → the till.
        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id, 'montant' => '50', 'date_remboursement' => '2025-09-23',
        ])->assertSessionHasNoErrors();
        $this->assertSame($till->id, Remboursement::query()->sole()->caisse_id);

        // Transfer request → source is the till.
        $other = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $other->till()->firstOrFail()->id, 'montant' => '10',
        ])->assertSessionHasNoErrors();
        $this->assertSame($till->id, CaisseTransfer::query()->sole()->caisse_source_id);

        $this->assertSame('850.00', (string) $till->fresh()->solde);
        $this->assertSame('0.00', (string) $safe->fresh()->solde);

        // And the screens show the till, not the safe.
        $this->get(route('backoffice.caisses.index', ['tab' => 'transferts']))
            ->assertInertia(fn ($page) => $page->where('myCaisse.id', $till->id));
        $this->get(route('backoffice.depenses.index'))
            ->assertInertia(fn ($page) => $page->where('soldeActuel', '850.00'));
    }

    public function test_the_provisioner_still_creates_the_till_of_an_employee_who_only_holds_an_externe_safe(): void
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $employee->till()->firstOrFail()->delete(); // simulate a pre-provisioner account
        $this->externeSafeFor($employee);
        $this->assertTrue($employee->caisses()->exists());

        $till = app(CaisseResolver::class)->tillOf($employee);

        $this->assertSame(Caisse::TYPE_CAISSIERE, $till->type);
        $this->assertSame($employee->id, $till->responsable_employee_id);
        // Idempotent: a second call finds it.
        $this->assertSame($till->id, app(CaisseResolver::class)->tillOf($employee->fresh())->id);
        $this->assertNull(app(CaisseProvisioner::class)->provisionFor($employee->fresh()));
    }

    public function test_the_database_refuses_a_second_physical_till_for_the_same_employee(): void
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->assertSame(1, $employee->till()->count());

        $this->expectException(QueryException::class);

        Caisse::create([
            'nom' => 'Doublon', 'type' => Caisse::TYPE_CAISSIERE, 'etablissement_id' => $this->centre->id,
            'responsable_employee_id' => $employee->id, 'solde' => 0,
        ]);
    }

    public function test_a_balance_can_no_longer_be_null(): void
    {
        $this->expectException(QueryException::class);

        DB::table('caisses')->insert([
            'nom' => 'Nul', 'type' => Caisse::TYPE_EXTERNE, 'solde' => null, 'statut' => 'Active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_method_account_provisioning_survives_a_lost_race_inside_an_outer_transaction(): void
    {
        $rabat = Etablissement::factory()->create();
        $tpe = $this->compte($rabat, Encaissement::METHODE_TPE);
        $tpe->delete(); // the observer hasn't run (restored dump, old centre)

        $competitorId = null;
        // Simulate the race: right after the provisioner's "does it exist?"
        // SELECT has answered "no", another request inserts the account.
        // Hooked on the query event (fires after the SELECT ran, on the OUTER
        // transaction — not inside the provisioner's savepoint), so the
        // provisioner's own INSERT then hits the partial unique index.
        DB::listen(function ($query) use (&$competitorId, $rabat): void {
            if ($competitorId === null
                && str_contains($query->sql, 'from "caisses"')
                && in_array(Encaissement::METHODE_TPE, $query->bindings, true)) {
                $competitorId = -1; // re-entrancy guard: the insert below fires this listener too
                $competitorId = DB::table('caisses')->insertGetId([
                    'nom' => 'TPE — gagnant', 'type' => Encaissement::METHODE_TPE, 'etablissement_id' => $rabat->id,
                    'solde' => 0, 'statut' => 'Active', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $resolved = DB::transaction(function () use ($rabat): Caisse {
            $compte = app(CaisseProvisioner::class)->compteMethodeFor($rabat->id, Encaissement::METHODE_TPE);
            // The outer transaction is still usable after the caught violation.
            $this->assertSame(1, Caisse::query()->where('etablissement_id', $rabat->id)->where('type', Encaissement::METHODE_TPE)->count());

            return $compte;
        });

        $this->assertSame($competitorId, $resolved->id);
    }

    // ── Comptes de caisse: what may be edited by hand ────────────────────

    public function test_a_method_account_cannot_be_edited_and_an_employee_till_cannot_change_hands(): void
    {
        $admin = $this->superAdmin();
        $tpe = $this->compte($this->centre, Encaissement::METHODE_TPE);
        $other = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        // Renaming / re-homing / assigning a responsable to a TPE account.
        $this->actingAs($admin)->put(route('backoffice.caisses.update', $tpe), [
            'nom' => 'Renommé', 'statut' => Caisse::STATUT_INACTIVE, 'responsable_employee_id' => $other->id,
        ])->assertSessionHasErrors('nom');
        $this->assertSame($tpe->nom, $tpe->fresh()->nom);
        $this->assertSame(Caisse::STATUT_ACTIVE, $tpe->fresh()->statut);
        $this->assertNull($tpe->fresh()->responsable_employee_id);

        // Re-assigning an employee's till to someone else.
        $till = $admin->employee->till()->firstOrFail();
        $this->put(route('backoffice.caisses.update', $till), [
            'nom' => $till->nom, 'statut' => Caisse::STATUT_ACTIVE, 'responsable_employee_id' => $other->id,
        ])->assertSessionHasErrors('responsable_employee_id');
        $this->assertSame($admin->employee->id, $till->fresh()->responsable_employee_id);

        // Echoing the current owner back is fine; an Externe safe may change hands.
        $this->put(route('backoffice.caisses.update', $till), [
            'nom' => 'Ma caisse', 'statut' => Caisse::STATUT_ACTIVE, 'responsable_employee_id' => $admin->employee->id,
        ])->assertSessionHasNoErrors();
        $safe = Caisse::create(['nom' => 'Coffre', 'type' => Caisse::TYPE_EXTERNE, 'etablissement_id' => $this->centre->id, 'solde' => 0]);
        $this->put(route('backoffice.caisses.update', $safe), [
            'nom' => 'Coffre', 'statut' => Caisse::STATUT_ACTIVE, 'responsable_employee_id' => $other->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($other->id, $safe->fresh()->responsable_employee_id);
    }

    // ── Centre isolation, server-side ───────────────────────────────────

    public function test_an_avance_for_a_student_of_another_centre_is_refused(): void
    {
        $rabat = Etablissement::factory()->create();
        $user = $this->userWith(['payments.view', 'payments.create'], global: false);
        [$foreignStudent] = $this->enrolledStudentWithFee(500, $rabat);

        $this->actingAs($user)->post(route('backoffice.avances.store'), [
            'student_id' => $foreignStudent->id, 'montant' => '500', 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ])->assertForbidden();

        $this->assertSame(0, Encaissement::count());
        $this->assertSame('0.00', (string) $user->employee->till()->firstOrFail()->solde);
    }

    public function test_a_refund_to_a_student_of_another_centre_is_refused(): void
    {
        $rabat = Etablissement::factory()->create();
        $user = $this->userWith(['refunds.view', 'refunds.create'], global: false);
        [$foreignStudent] = $this->enrolledStudentWithFee(500, $rabat);

        $this->actingAs($user)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $foreignStudent->id, 'montant' => '200', 'date_remboursement' => '2025-09-23',
        ])->assertForbidden();

        $this->assertSame(0, Remboursement::count());
        $this->assertSame('0.00', (string) $user->employee->till()->firstOrFail()->solde);
    }

    public function test_a_single_centre_cashier_cannot_credit_another_centres_method_account_through_the_session(): void
    {
        $rabat = Etablissement::factory()->create();
        $user = $this->userWith(['payments.view', 'payments.create'], global: false);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        // A tampered session value pointing at a centre the cashier is not
        // assigned to is ignored by CurrentContext.
        $this->actingAs($user);
        session(['context.etablissement_id' => $rabat->id]);
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_TPE);

        $this->assertSame('1000.00', (string) $this->compte($this->centre, Encaissement::METHODE_TPE)->solde);
        $this->assertSame('0.00', (string) $this->compte($rabat, Encaissement::METHODE_TPE)->solde);
    }

    // ── Historical re-homing: payments only ─────────────────────────────

    public function test_recalculer_soldes_never_moves_a_depense_out_of_the_till(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $user = $this->userWith(['expenses.view', 'expenses.create']);
        $till = $user->employee->till()->firstOrFail();
        app(CaisseLedger::class)->credit($till->id, 1000.0, 'ouverture');
        $type = TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => 'Actif']);

        $this->actingAs($user)->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $type->id, 'montant' => '300', 'methode_paiement' => Encaissement::METHODE_VIREMENT,
            'date_depense' => '2025-09-22', 'description' => 'Loyer',
        ])->assertSessionHasNoErrors();
        $depense = Depense::query()->sole();
        $this->assertSame('700.00', (string) $till->fresh()->solde);
        $movementsBefore = Activity::query()->where('event', 'solde_movement')->count();

        $this->assertSame(0, Artisan::call('caisse:recalculer-soldes', ['--apply' => true]));

        // Nothing moved: the dépense stays in the till, the Virement account is untouched.
        $this->assertSame($till->id, $depense->fresh()->caisse_id);
        $this->assertSame('700.00', (string) $till->fresh()->solde);
        $this->assertSame('0.00', (string) $this->compte($this->centre, Encaissement::METHODE_VIREMENT)->solde);
        $this->assertSame($movementsBefore, Activity::query()->where('event', 'solde_movement')->count());
        $this->assertStringContainsString('Rows to re-home: 0', Artisan::output());
    }

    // ── Reporting: one definition of "a payment's centre" ───────────────

    public function test_the_annual_chart_attributes_a_payment_to_the_students_centre_like_the_dashboard(): void
    {
        $rabat = Etablissement::factory()->create();
        // Operator based in Rabat (till in Rabat), collecting cash for a
        // student of $this->centre while working there.
        $user = $this->userWith(['payments.view', 'payments.create'], $rabat);
        $user->employee->syncEtablissements([$rabat->id, $this->centre->id]);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->payLine($user, $student, $inscription, $fee, '1000', Encaissement::METHODE_ESPECES);
        $this->assertSame($rabat->id, Encaissement::query()->sole()->caisse->etablissement_id);

        // The chart window is the ACTIVE ACADEMIC YEAR (2025/2026 =
        // 01/09/2025 → 31/08/2026), not a calendar year, so September is
        // index 0 — and __invoke() takes no argument. Both were left over
        // from the calendar-year version replaced in fd6451b.
        $summary = app(GetAnnualFraisSummary::class)();
        $this->assertSame('1000.00', $summary['encaissements'][0]); // September

        app(CurrentContext::class)->setEtablissement($rabat->id);
        $summary = app(GetAnnualFraisSummary::class)();
        $this->assertSame('0.00', $summary['encaissements'][0]);
    }

    public function test_the_annual_chart_excludes_cancelled_inscriptions_from_chiffre_daffaire(): void
    {
        // WimSchool reference rule (REGISTRATION_STATUS_ID <> 10): a fee kept
        // on an « Annulée » inscription counts neither as chiffre d'affaire
        // nor as collecté — but the money actually received stays in the
        // « Encaissements » series.
        $user = $this->userWith(['payments.view', 'payments.create']);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->payLine($user, $student, $inscription, $fee, '400', Encaissement::METHODE_ESPECES);

        // Fee due 2025-10-31 → October = index 1; payment 2025-09-20 → index 0.
        $summary = app(GetAnnualFraisSummary::class)();
        $this->assertSame('1000.00', $summary['chiffreAffaire'][1]);
        $this->assertSame('400.00', $summary['collecte'][1]);
        $this->assertSame('600.00', $summary['resteAPayer'][1]);
        $this->assertSame('400.00', $summary['encaissements'][0]);

        // Cancel WITHOUT removing the fee line — the row survives, its
        // inscription's statut alone must pull it out of the billed series.
        $inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        $summary = app(GetAnnualFraisSummary::class)();
        $this->assertSame('0.00', $summary['chiffreAffaire'][1]);
        $this->assertSame('0.00', $summary['collecte'][1]);
        $this->assertSame('0.00', $summary['resteAPayer'][1]);
        $this->assertSame('400.00', $summary['encaissements'][0]);
    }

    // ── Ledger invariant: solde = Σ credits − Σ debits, entries chain ───

    public function test_every_balance_equals_the_sum_of_its_journaled_movements(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $cashier = $this->userWith(['payments.view', 'payments.create', 'expenses.create', 'refunds.create', 'cash-transfers.create']);
        $recipient = $this->userWith(['cash-transfers.validate']);
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->actingAs($cashier);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $inscription->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20'],
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors();
        $type = TypeDepense::create(['nom' => 'Fournitures', 'is_system' => false, 'statut' => 'Actif']);
        $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $type->id, 'montant' => '120', 'methode_paiement' => Encaissement::METHODE_TPE, 'date_depense' => '2025-09-22',
            'description' => 'Fournitures',
        ])->assertSessionHasNoErrors();
        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id, 'montant' => '80', 'date_remboursement' => '2025-09-23',
        ])->assertSessionHasNoErrors();
        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $recipient->employee->till()->firstOrFail()->id, 'montant' => '100',
        ])->assertSessionHasNoErrors();
        app(ValiderTransfertCaisse::class)->handle(CaisseTransfer::query()->sole(), $recipient->employee);

        $this->assertSame('200.00', (string) $cashier->employee->till()->firstOrFail()->fresh()->solde);
        $this->assertSame('100.00', (string) $recipient->employee->till()->firstOrFail()->fresh()->solde);
        $this->assertSame('1000.00', (string) $this->compte($this->centre, Encaissement::METHODE_TPE)->solde);

        foreach (Caisse::all() as $caisse) {
            $entries = Activity::query()->where('log_name', 'caisse')->where('event', 'solde_movement')
                ->where('subject_id', $caisse->id)->orderBy('id')->get();
            $net = 0.0;
            $previousApres = 0.0;

            foreach ($entries as $entry) {
                $p = $entry->properties;
                $this->assertSame($previousApres, (float) $p['solde_avant'], "Journal de {$caisse->nom} ne s'enchaîne pas.");
                $delta = $p['sens'] === 'Entrée' ? (float) $p['montant'] : -(float) $p['montant'];
                $this->assertSame(round((float) $p['solde_avant'] + $delta, 2), (float) $p['solde_apres']);
                $net = round($net + $delta, 2);
                $previousApres = (float) $p['solde_apres'];
            }

            $this->assertSame($net, (float) $caisse->solde, "Solde de {$caisse->nom} ≠ Σ des mouvements journalisés.");
        }

        // The read-only auditor agrees.
        $this->assertSame(0, Artisan::call('caisse:verifier-coherence', ['--strict' => true]));
    }

    public function test_the_coherence_auditor_flags_a_mis_routed_payment_and_a_depense_in_a_method_account(): void
    {
        $user = $this->userWith(['payments.view']);
        $till = $user->employee->till()->firstOrFail();
        $tpe = $this->compte($this->centre, Encaissement::METHODE_TPE);
        [$student, , $fee] = $this->enrolledStudentWithFee(700);

        $this->assertSame(0, Artisan::call('caisse:verifier-coherence'));

        // A pre-refactor TPE row still in the physical till.
        Encaissement::create([
            'reference' => 'ENC-LEGACY', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $till->id, 'agent_id' => $user->employee->id,
            'montant' => 700, 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-10',
        ]);
        // A dépense someone parked in the TPE account (the old command did this).
        $type = TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => 'Actif']);
        Depense::create([
            'reference' => 'DEP-BAD', 'type_depense_id' => $type->id, 'caisse_id' => $tpe->id, 'montant' => 50,
            'methode_paiement' => Encaissement::METHODE_TPE, 'date_depense' => '2025-09-11', 'agent_id' => $user->employee->id,
            'statut' => Depense::STATUT_APPROUVEE,
        ]);

        $this->assertSame(1, Artisan::call('caisse:verifier-coherence'));
        $output = Artisan::output();
        $this->assertStringContainsString('ENC-LEGACY', $output);
        $this->assertStringContainsString('DEP-BAD', $output);
        // Read-only: nothing moved.
        $this->assertSame('0.00', (string) $till->fresh()->solde);
        $this->assertSame('0.00', (string) $tpe->fresh()->solde);
    }
}
