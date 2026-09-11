<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Validation de transfert » follows the top-bar centre switcher.
 *
 * Reported 02/09/2026: a super-admin working in « GLS Salé » saw the whole
 * network's transfers (Marrakech ↔ Rabat tills among them). The inbox keyed
 * its scope on CenterAccessService::scopeAccessibleCenters() alone, which is
 * a NO-OP for anyone holding centers.access-all — so "reach" meant every
 * centre and the switcher was ignored.
 *
 * The deliberate exception of CLAUDE.md §11 is preserved and asserted here:
 * a transfer touching one of the viewer's OWN tills stays listed whatever
 * the active centre, otherwise a pending row could hide behind a switch and
 * never be validated by the only person allowed to validate it.
 */
final class TransferInboxCenterScopeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $sale;

    private Etablissement $marrakech;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->sale = Etablissement::factory()->create();
        $this->marrakech = Etablissement::factory()->create();
    }

    private function cashAccount(Etablissement $centre): Caisse
    {
        return Caisse::factory()->create([
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $centre->id,
            'solde' => 1000,
        ]);
    }

    private function transferBetween(Caisse $from, Caisse $to): CaisseTransfer
    {
        // Requested by SOMEONE ELSE: the recipient is the only valid
        // validator, so the requester must never be the viewer under test.
        $requester = Employee::factory()->create(['etablissement_id' => $this->marrakech->id]);

        return CaisseTransfer::create([
            'requested_by' => $requester->id,
            'reference' => 'TRF-'.str_pad((string) (CaisseTransfer::count() + 1), 4, '0', STR_PAD_LEFT),
            'caisse_source_id' => $from->id,
            'caisse_destination_id' => $to->id,
            'montant' => 500,
            'date_transfert' => now(),
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
        ]);
    }

    /** A super-admin: centre reach is unlimited, so only the switcher can scope them. */
    private function superAdmin(Etablissement $primary): User
    {
        $user = User::factory()->create();
        $user->assignRole(\App\Models\Role::SUPER_ADMIN);
        Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $primary->id,
        ]);

        return $user->fresh();
    }

    /** @param  string  $statut  '' = every status (the query defaults to « En attente »). */
    private function listFor(User $user, string $statut = CaisseTransfer::STATUT_EN_ATTENTE): array
    {
        return app(GetCaisseTransfersList::class)($user, '', $statut)['data']->items();
    }

    private function useCenter(?int $etablissementId): void
    {
        app(CurrentContext::class)->setEtablissement($etablissementId);
    }

    public function test_a_transfer_between_two_other_centres_tills_is_hidden_while_a_single_centre_is_active(): void
    {
        $user = $this->superAdmin($this->sale);

        $foreign = $this->transferBetween(
            $this->cashAccount($this->marrakech),
            $this->cashAccount($this->marrakech),
        );

        $this->actingAs($user);
        $this->useCenter($this->sale->id);

        $references = array_column($this->listFor($user), 'reference');

        $this->assertNotContains(
            $foreign->reference,
            $references,
            'A Marrakech-to-Marrakech transfer must not appear while the switcher reads GLS Salé.',
        );
    }

    public function test_a_transfer_in_the_active_centre_is_listed(): void
    {
        $user = $this->superAdmin($this->sale);

        $local = $this->transferBetween(
            $this->cashAccount($this->sale),
            $this->cashAccount($this->sale),
        );

        $this->actingAs($user);
        $this->useCenter($this->sale->id);

        $this->assertContains($local->reference, array_column($this->listFor($user), 'reference'));
    }

    public function test_all_centres_still_shows_every_reachable_transfer(): void
    {
        $user = $this->superAdmin($this->sale);

        $foreign = $this->transferBetween(
            $this->cashAccount($this->marrakech),
            $this->cashAccount($this->marrakech),
        );

        $this->actingAs($user);
        $this->useCenter(null);

        $this->assertContains($foreign->reference, array_column($this->listFor($user), 'reference'));
    }

    /**
     * The §11 exception: my own till at either end wins over the switcher,
     * or a pending transfer could never be validated by its only validator.
     */
    public function test_a_transfer_into_my_own_till_survives_a_centre_switch(): void
    {
        $user = $this->superAdmin($this->sale);

        $myTill = $this->cashAccount($this->marrakech);
        $myTill->update(['responsable_employee_id' => $user->employee->id]);

        $mine = $this->transferBetween($this->cashAccount($this->marrakech), $myTill);

        $this->actingAs($user);
        $this->useCenter($this->sale->id);

        $this->assertContains(
            $mine->reference,
            array_column($this->listFor($user), 'reference'),
            'A transfer into my own till must stay visible whatever the active centre.',
        );
    }

    /**
     * The exception is bounded to PENDING rows (11/09/2026). A validated
     * transfer asks nothing of anyone, so it follows the switcher like every
     * other finance record — otherwise the CEO's till, one end of nearly
     * every transfer, put the whole network's history on every centre's
     * screen (Kénitra's and Rabat's transfers listed under « GLS Online »).
     */
    public function test_a_validated_transfer_into_my_own_till_follows_the_switcher(): void
    {
        $user = $this->superAdmin($this->sale);

        $myTill = $this->cashAccount($this->marrakech);
        $myTill->update(['responsable_employee_id' => $user->employee->id]);

        $done = $this->transferBetween($this->cashAccount($this->marrakech), $myTill);
        $done->update(['statut' => CaisseTransfer::STATUT_VALIDE, 'validated_by' => $user->employee->id]);

        $this->actingAs($user);

        $this->useCenter($this->sale->id);
        $this->assertNotContains(
            $done->reference,
            array_column($this->listFor($user, ''), 'reference'),
            'A VALIDATED Marrakech transfer must not appear while the switcher reads GLS Salé, even into my own till.',
        );

        $this->useCenter($this->marrakech->id);
        $this->assertContains($done->reference, array_column($this->listFor($user, ''), 'reference'));

        $this->useCenter(null);
        $this->assertContains($done->reference, array_column($this->listFor($user, ''), 'reference'));
    }

    /**
     * « Validés » / « En attente » beside « Total des transferts » are per
     * ACTIVE CENTRE and ignore the statut dropdown: selecting « Validé » must
     * not turn the pending figure into 0.00 DH.
     */
    public function test_montants_par_statut_follow_the_switcher_not_the_statut_filter(): void
    {
        $user = $this->superAdmin($this->sale);

        $this->transferBetween($this->cashAccount($this->sale), $this->cashAccount($this->sale));
        $this->transferBetween($this->cashAccount($this->sale), $this->cashAccount($this->sale))
            ->update(['statut' => CaisseTransfer::STATUT_VALIDE]);
        $this->transferBetween($this->cashAccount($this->marrakech), $this->cashAccount($this->marrakech))
            ->update(['statut' => CaisseTransfer::STATUT_VALIDE]);

        $this->actingAs($user);
        $this->useCenter($this->sale->id);

        // Statut filter set to « Validé » — the pending figure must survive it.
        $montants = app(GetCaisseTransfersList::class)($user, '', CaisseTransfer::STATUT_VALIDE)['montantsParStatut'];
        $this->assertSame('500.00', $montants[CaisseTransfer::STATUT_VALIDE], 'Only the Salé validated transfer, not the Marrakech one.');
        $this->assertSame('500.00', $montants[CaisseTransfer::STATUT_EN_ATTENTE]);

        $this->useCenter(null);
        $this->assertSame('1000.00', app(GetCaisseTransfersList::class)($user)['montantsParStatut'][CaisseTransfer::STATUT_VALIDE]);
    }
}
