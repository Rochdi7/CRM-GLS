<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which tills the « Transfert à une autre caisse » modal offers.
 *
 * The rule is centre of SERVICE, not centre of filing: a till is listed when
 * the employee HOLDING it is assigned to the active centre — « Centres
 * affectés » being the one authority on where somebody works (CLAUDE.md
 * §16). Neither `caisses.etablissement_id` (where the till is filed) nor the
 * holder's PRIMARY centre decides it. Mohammed Rafik's primary centre is
 * Rabat and his till is filed in Marrakech while he is assigned to all
 * seven, so a cashier standing in Marrakech may hand him cash there.
 *
 * Two alternatives were tried and rejected on 04/09/2026: matching the
 * filing column hid every colleague whose till sits elsewhere, and dropping
 * the filter entirely listed the whole network, offering people who never
 * set foot in the centre.
 *
 * Also narrowing the list, none of it a centre rule: cash accounts only, the
 * hidden maintainer till, and EMPTY dormant tills — one that still HOLDS
 * money stays offered, or the transfer that empties it would be impossible.
 *
 * ⚠ The dropdown and StoreCaisseTransferRequest must ask the same question.
 * They disagreed once — the list was widened while the Form Request still
 * ran AccessibleCaisse — and the user got « La caisse sélectionnée n'est pas
 * accessible depuis votre centre. » on a till the page had just offered.
 */
final class TransfertCaisseOptionsCentreTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $marrakech;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);

        return $user->fresh();
    }

    /** The employee's auto-provisioned till, re-filed and funded as needed. */
    private function tillOf(Employee $employee, string $solde = '0.00'): Caisse
    {
        $caisse = $employee->till()->first();
        $caisse->update(['solde' => $solde]);

        return $caisse->fresh();
    }

    /**
     * @return list<int> ids offered while the switcher names $centre
     *
     * ⚠ actingAs FIRST: CurrentContext::setEtablissement() reads
     * auth()->user() and silently does nothing when nobody is signed in —
     * the context then stays null, every centre filter early-returns, and
     * the assertion passes against an UNFILTERED list.
     */
    private function optionIdsIn(Etablissement $centre, User $user): array
    {
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($centre->id);

        $this->assertSame(
            $centre->id,
            app(CurrentContext::class)->etablissementId(),
            'The active centre was not applied — the assertion below would test nothing.',
        );

        return app(GetCaisseTransfersList::class)
            ->caisseOptions($user)
            ->pluck('id')
            ->all();
    }

    public function test_assignment_decides_not_where_the_till_is_filed(): void
    {
        // Filed in Marrakech, primary centre Marrakech, but assigned to both.
        $rafik = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $rafik->syncEtablissements([$this->marrakech->id, $this->rabat->id]);
        $till = $this->tillOf($rafik, '5000.00');

        $user = $this->superAdmin();

        // Offered in BOTH centres he works in — the filing column decides
        // neither of them.
        $this->assertContains($till->id, $this->optionIdsIn($this->rabat, $user));
        $this->assertContains($till->id, $this->optionIdsIn($this->marrakech, $user));
    }

    /** The list is not « everybody »: a colleague who never works here is out. */
    public function test_a_colleague_not_assigned_to_the_centre_is_not_offered(): void
    {
        $marrakechOnly = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $marrakechOnly->syncEtablissements([$this->marrakech->id]);
        $till = $this->tillOf($marrakechOnly, '5000.00');

        $user = $this->superAdmin();

        $this->assertNotContains($till->id, $this->optionIdsIn($this->rabat, $user));
        $this->assertContains($till->id, $this->optionIdsIn($this->marrakech, $user));
    }

    public function test_a_cashier_reaches_a_colleague_who_works_in_her_centre(): void
    {
        $hafssa = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $hafssa->syncEtablissements([$this->rabat->id]);
        $user = $hafssa->user ?? User::factory()->create();
        $hafssa->forceFill(['user_id' => $user->id])->save();
        // Otherwise the must-change-password middleware bounces every POST
        // to /backoffice/profile and the controller never runs.
        $user->forceFill(['must_change_password' => false])->save();
        $user->givePermissionTo('cash-transfers.view');

        // Filed in Marrakech — a centre Hafssa cannot reach — but its owner
        // works in Rabat, where she does.
        $rafik = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $rafik->syncEtablissements([$this->marrakech->id, $this->rabat->id]);
        $till = $this->tillOf($rafik, '17000.00');

        $this->assertContains($till->id, $this->optionIdsIn($this->rabat, $user->fresh()));
    }


    /**
     * The other half of the same rule: a dropdown that OFFERS a till must
     * lead to a request that ACCEPTS it. Widening only the query left
     * Hafssa able to pick Rafik and refused on submit with « La caisse
     * sélectionnée n'est pas accessible depuis votre centre. »
     */
    public function test_a_cashier_may_actually_submit_a_transfer_to_that_colleague(): void
    {
        $hafssa = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $hafssa->syncEtablissements([$this->rabat->id]);
        $user = $hafssa->user ?? User::factory()->create();
        $hafssa->forceFill(['user_id' => $user->id])->save();
        // Otherwise the must-change-password middleware bounces every POST
        // to /backoffice/profile and the controller never runs.
        $user->forceFill(['must_change_password' => false])->save();
        $user->givePermissionTo('cash-transfers.view');
        $user->givePermissionTo('cash-transfers.create');
        $this->tillOf($hafssa, '20000.00');

        $rafik = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $rafik->syncEtablissements([$this->marrakech->id, $this->rabat->id]);
        $destination = $this->tillOf($rafik, '0.00');

        $this->actingAs($user->fresh());
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id,
            'montant' => '500',
            'date_transfert' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertGreaterThan(
            0,
            \App\Models\CaisseTransfer::query()->count(),
            'No transfer row was created — the POST was accepted but nothing was filed.',
        );

        // Filed as a REQUEST — the two-person control is what protects this
        // flow, so nothing moves until the destination's owner accepts.
        $transfer = \App\Models\CaisseTransfer::query()->firstOrFail();
        $this->assertSame($destination->id, $transfer->caisse_destination_id);
        $this->assertSame('20000.00', (string) $hafssa->till()->first()->solde);
        $this->assertSame('0.00', (string) $destination->fresh()->solde);
    }

    /**
     * The server must refuse what the dropdown does not offer — otherwise a
     * crafted request could still file a transfer to a colleague who works
     * nowhere near the cashier.
     */
    public function test_a_transfer_to_an_unassigned_colleague_is_refused(): void
    {
        $hafssa = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $hafssa->syncEtablissements([$this->rabat->id]);
        $user = $hafssa->user ?? User::factory()->create();
        $hafssa->forceFill(['user_id' => $user->id])->save();
        $user->forceFill(['must_change_password' => false])->save();
        $user->givePermissionTo('cash-transfers.view');
        $user->givePermissionTo('cash-transfers.create');
        $this->tillOf($hafssa, '20000.00');

        $etranger = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $etranger->syncEtablissements([$this->marrakech->id]);
        $destination = $this->tillOf($etranger, '0.00');

        $this->actingAs($user->fresh());
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        $this->post(route('backoffice.caisse-transfers.store'), [
            'caisse_destination_id' => $destination->id,
            'montant' => '500',
            'date_transfert' => now()->toDateString(),
        ])->assertSessionHasErrors('caisse_destination_id');

        $this->assertSame(0, \App\Models\CaisseTransfer::query()->count());
    }

    public function test_an_empty_teacher_till_is_hidden(): void
    {
        $enseignant = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
        ]);
        $enseignant->syncEtablissements([$this->rabat->id]);
        $till = $this->tillOf($enseignant, '0.00');

        $this->assertNotContains($till->id, $this->optionIdsIn($this->rabat, $this->superAdmin()));
    }

    /**
     * ⚠ The zero-balance half of DormantTill is what makes hiding safe: a
     * teacher's till holding money must stay offered, or the transfer that
     * empties it could never be made.
     */
    public function test_a_teacher_till_holding_money_stays_offered(): void
    {
        $enseignant = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
        ]);
        $enseignant->syncEtablissements([$this->rabat->id]);
        $till = $this->tillOf($enseignant, '1200.00');

        $this->assertContains($till->id, $this->optionIdsIn($this->rabat, $this->superAdmin()));
    }
}
