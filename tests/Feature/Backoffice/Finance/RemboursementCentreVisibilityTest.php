<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetRemboursementsList;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A refund belongs to the CENTRE OF THE MONEY, not to the centre of the till
 * it was paid out of (03/09/2026).
 *
 * Reported in production: MOHAMMED YASSER ELWARDI, a student of centre 4, was
 * refunded 300 DH by a cashier whose till is homed to centre 1. The list
 * derived the refund's centre from that till, so the row appeared on NEITHER
 * centre — not on 4 (where the student is, and where anyone would look) and
 * not on 1 unless you happened to switch there. Seeing nothing recorded, the
 * cashier entered it again: RMB-001 and RMB-002, 300 DH each, same day, two
 * tills debited for one 300 DH refund.
 *
 * The fix stores etablissement_id on the refund itself, following the rule
 * CaisseLedger already applies (CLAUDE.md §11 « Centre dimension »): the
 * original payment's centre, else the beneficiary's.
 */
final class RemboursementCentreVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centreCaisse;

    private Etablissement $centreEtudiant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centreCaisse = Etablissement::factory()->create();
        $this->centreEtudiant = Etablissement::factory()->create();
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        foreach (['refunds.view', 'refunds.create', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centreCaisse->id]);

        return $user->fresh();
    }

    /**
     * The reported bug, end to end: refund a student of centre B from a till
     * homed to centre A, then look at centre B — the row must be there.
     */
    public function test_a_refund_is_listed_on_the_students_centre_not_the_tills(): void
    {
        $user = $this->cashier();
        $this->actingAs($user);

        $till = $user->employee->till()->first();
        $till->update(['solde' => 1000]);
        $this->assertSame($this->centreCaisse->id, $till->etablissement_id);

        $student = Student::factory()->create(['etablissement_id' => $this->centreEtudiant->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $till->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $remboursement = Remboursement::where('beneficiaire_id', $student->id)->firstOrFail();
        $this->assertSame($this->centreEtudiant->id, $remboursement->etablissement_id);

        // The switcher on the STUDENT's centre — where the cashier looks.
        app(CurrentContext::class)->setEtablissement($this->centreEtudiant->id);

        $rows = app(GetRemboursementsList::class)($user);

        $this->assertCount(1, $rows->items(), 'The refund must be visible on the student centre.');
        $this->assertSame($remboursement->id, $rows->items()[0]['id']);
    }

    /**
     * The other half: it must NOT leak onto an unrelated centre. A NULL
     * etablissement_id would read as "global" and show everywhere, which is
     * why the production backfill matters.
     */
    public function test_a_refund_does_not_appear_on_an_unrelated_centre(): void
    {
        $user = $this->cashier();
        $this->actingAs($user);

        $till = $user->employee->till()->first();
        $till->update(['solde' => 1000]);
        $student = Student::factory()->create(['etablissement_id' => $this->centreEtudiant->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $till->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $autre = Etablissement::factory()->create();
        app(CurrentContext::class)->setEtablissement($autre->id);

        $this->assertCount(0, app(GetRemboursementsList::class)($user)->items());
    }

    /**
     * The cashier now chooses the till, so the money must leave the till they
     * named — not the one derived from their own employee record.
     */
    public function test_the_chosen_till_is_the_one_debited(): void
    {
        $user = $this->cashier();
        $this->actingAs($user);

        $ownTill = $user->employee->till()->first();
        $ownTill->update(['solde' => 1000]);

        // Creating an Employee auto-provisions exactly ONE « Caissière »
        // till (caisses_une_caissiere_par_employe, §11) — take that one
        // rather than making a second, which the constraint refuses.
        $autreEmploye = Employee::factory()->create(['etablissement_id' => $this->centreCaisse->id]);
        $autreTill = $autreEmploye->till()->firstOrFail();
        $autreTill->update(['solde' => 2000]);

        $student = Student::factory()->create(['etablissement_id' => $this->centreEtudiant->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $autreTill->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('1700.00', (string) $autreTill->fresh()->solde, 'The chosen till pays.');
        $this->assertSame('1000.00', (string) $ownTill->fresh()->solde, 'The acting employee till is untouched.');
    }

    /**
     * Centre reach still governs which tills may be named — a tampered
     * caisse_id must not reach a centre the user cannot access.
     */
    public function test_a_till_outside_the_users_centres_is_refused(): void
    {
        $user = User::factory()->create();
        foreach (['refunds.view', 'refunds.create'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centreCaisse->id]);
        $user = $user->fresh();
        $this->actingAs($user);

        $horsPortee = Etablissement::factory()->create();
        $employeHors = Employee::factory()->create(['etablissement_id' => $horsPortee->id]);
        $caisseHors = $employeHors->till()->firstOrFail();
        $caisseHors->update(['solde' => 5000]);

        $student = Student::factory()->create(['etablissement_id' => $this->centreCaisse->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisseHors->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionHasErrors('caisse_id');

        $this->assertSame('5000.00', (string) $caisseHors->fresh()->solde);
    }

    /**
     * The dropdown offers cash tills of the active centre only — never a
     * TPE/Chèque/Virement account (a refund is cash handed back, §11).
     */
    public function test_caisse_options_exclude_method_accounts(): void
    {
        $user = $this->cashier();
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centreCaisse->id);

        $options = app(GetRemboursementsList::class)->caisseOptions($user);
        $ids = $options->pluck('id')->all();

        $comptesMethode = Caisse::query()
            ->whereIn('type', Caisse::TYPES_METHODE)
            ->pluck('id')
            ->all();

        foreach ($comptesMethode as $id) {
            $this->assertNotContains($id, $ids, 'A method account must never be offered as a refund source.');
        }

        $this->assertContains($user->employee->till()->first()->id, $ids);
    }
}
