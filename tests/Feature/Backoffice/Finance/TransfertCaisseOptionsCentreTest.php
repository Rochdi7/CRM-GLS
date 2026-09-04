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
 * The destination list is deliberately NOT scoped by centre (04/09/2026).
 * A transfer is the one money flow whose real control is the RECIPIENT:
 * nothing moves until the employee owning the destination till accepts it,
 * and a super-admin cannot bypass that (CLAUDE.md §11). Filtering by centre
 * blocked legitimate hand-overs without protecting anything — a cashier in
 * Rabat could not hand cash to a director who works in Rabat because his
 * till is filed in Marrakech, and staff assigned to all seven centres were
 * unreachable from six of them.
 *
 * What still narrows the list, none of it a centre rule:
 *  - cash accounts only (a transfer never targets a TPE/Chèque/Virement
 *    account);
 *  - the hidden maintainer till (HiddenAccount);
 *  - an EMPTY till belonging to a teacher or a departed employee
 *    (DormantTill) — one that still HOLDS money stays offered, or the
 *    transfer that empties it would be impossible.
 *
 * ⚠ The dropdown and StoreCaisseTransferRequest must stay in step: a
 * destination the screen offers must be one the request accepts. They
 * disagreed once — the list was widened while the Form Request still ran
 * AccessibleCaisse — and the user got « La caisse sélectionnée n'est pas
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

    public function test_a_till_is_offered_from_any_centre(): void
    {
        // Filed in Marrakech (his primary centre), but he also works in Rabat.
        $rafik = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $rafik->syncEtablissements([$this->marrakech->id, $this->rabat->id]);
        $till = $this->tillOf($rafik, '5000.00');

        $user = $this->superAdmin();

        // The point of the fix: reachable from Rabat, where he also works.
        $this->assertContains($till->id, $this->optionIdsIn($this->rabat, $user));
        // Still reachable from the centre it is filed in.
        $this->assertContains($till->id, $this->optionIdsIn($this->marrakech, $user));
    }


    /**
     * Hafssa Elkhattabi's exact case (04/09/2026), and the reason the first
     * attempt at this fix did not work: she is a CASHIER, not a super-admin,
     * assigned to Rabat + Salé. Rafik works in all seven centres but his till
     * is filed in Marrakech, which she cannot reach — so
     * scopeAccessibleCenters() dropped the row before the assignment clause
     * could rescue it, and the dropdown still said « Aucun résultat ».
     */
    public function test_a_cashier_reaches_a_colleague_who_works_in_her_centre(): void
    {
        $hafssa = Employee::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $hafssa->syncEtablissements([$this->rabat->id]);
        $user = $hafssa->user ?? User::factory()->create();
        $hafssa->forceFill(['user_id' => $user->id])->save();
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
