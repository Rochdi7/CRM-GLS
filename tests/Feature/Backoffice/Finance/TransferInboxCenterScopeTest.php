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

    private function listFor(User $user): array
    {
        return app(GetCaisseTransfersList::class)($user)['data']->items();
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
}
