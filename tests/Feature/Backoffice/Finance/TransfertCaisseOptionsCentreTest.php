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
 * Two rules, both about the DESTINATION dropdown only — neither changes
 * where a caisse is filed or how its money is counted:
 *
 *  1. A till is offered in every centre its responsable is ASSIGNED to, not
 *     only the one it is filed under. « Centres affectés » is the single
 *     authority on where somebody works (CLAUDE.md §16), while
 *     `employees.etablissement_id` is merely their PRIMARY centre.
 *     Reported 04/09/2026: Mohammed Rafik's till is filed in GLS Marrakech
 *     while he also works in Rabat, so a cashier in Rabat searching
 *     « mohammed » got « Aucun résultat ».
 *
 *  2. An EMPTY till belonging to a teacher is noise and is hidden
 *     (DormantTill) — but one that still HOLDS money stays offered, or the
 *     transfer that empties it would be impossible.
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

    public function test_a_till_is_offered_in_every_centre_its_owner_is_assigned_to(): void
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

    public function test_a_till_stays_out_of_a_centre_its_owner_does_not_work_in(): void
    {
        $marrakechOnly = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $marrakechOnly->syncEtablissements([$this->marrakech->id]);
        $till = $this->tillOf($marrakechOnly, '5000.00');

        $user = $this->superAdmin();

        // Widening the dropdown must not turn it into "every till everywhere".
        $this->assertNotContains($till->id, $this->optionIdsIn($this->rabat, $user));
        $this->assertContains($till->id, $this->optionIdsIn($this->marrakech, $user));
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
