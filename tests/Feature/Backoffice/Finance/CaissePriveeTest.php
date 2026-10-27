<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseGlobale;
use App\Domain\Finance\Queries\GetComptesCaisse;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Access\CaissePrivee;
use App\Support\Access\HiddenAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * The CEO's personal till is PRIVATE (App\Support\Access\CaissePrivee,
 * 27/10/2026): on « Caisse globale » and « Comptes de caisse » it is listed
 * for its owner alone, and the totals above the lists drop it for everyone
 * else. Every other till stays listed; nothing about money changes.
 */
final class CaissePriveeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);

        $this->centre = Etablissement::factory()->create();
    }

    private function staff(string $email, string $prenom, string $nom): Employee
    {
        $user = User::factory()->create(['email' => $email, 'must_change_password' => false]);

        $employee = Employee::withoutGlobalScopes()->create([
            'reference' => 'EMP-'.substr(md5($email), 0, 5),
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_id' => $this->centre->id,
            'user_id' => $user->id,
        ]);

        $user->assignRole('super-admin');

        return $employee;
    }

    private function till(Employee $employee, string $solde): Caisse
    {
        $till = Caisse::where('responsable_employee_id', $employee->id)->firstOrFail();
        // A non-zero solde so DormantTill cannot be what hides it.
        $till->forceFill(['solde' => $solde])->save();

        return $till;
    }

    /** @return array{ceo: Employee, other: Employee, ceoTill: Caisse, otherTill: Caisse} */
    private function scenario(): array
    {
        $ceo = $this->staff(CaissePrivee::EMAILS[0], 'Mohammed', 'Rafik');
        $other = $this->staff('directeur@glszentrum.com', 'Amine', 'Rafik');

        return [
            'ceo' => $ceo,
            'other' => $other,
            'ceoTill' => $this->till($ceo, '20000.00'),
            'otherTill' => $this->till($other, '300.00'),
        ];
    }

    private function globaleIds(User $viewer): array
    {
        $this->actingAs($viewer);
        app(CurrentContext::class)->setEtablissement(null);

        $result = app(GetCaisseGlobale::class)($viewer);

        return [
            'ids' => collect($result['comptes'])->flatten(1)->pluck('id')->all(),
            'personnelle' => collect($result['cards'])->firstWhere('type', Caisse::TYPE_CAISSIERE)['total'],
            'total' => $result['total'],
        ];
    }

    private function comptesIds(User $viewer): array
    {
        $this->actingAs($viewer);
        app(CurrentContext::class)->setEtablissement(null);

        return collect(app(GetComptesCaisse::class)()->items())->pluck('id')->all();
    }

    public function test_the_private_till_is_hidden_from_everyone_else_on_caisse_globale(): void
    {
        ['other' => $other, 'ceoTill' => $ceoTill, 'otherTill' => $otherTill] = $this->scenario();

        $seen = $this->globaleIds($other->user);

        $this->assertNotContains($ceoTill->id, $seen['ids'], 'The CEO\'s till must not be listed for another user.');
        $this->assertContains($otherTill->id, $seen['ids'], 'An ordinary till stays listed.');
        $this->assertSame('300.00', $seen['personnelle'], 'The card total must drop the hidden till too.');
        $this->assertSame('300.00', $seen['total']);
    }

    public function test_the_owner_still_sees_his_own_till_on_caisse_globale(): void
    {
        ['ceo' => $ceo, 'ceoTill' => $ceoTill, 'otherTill' => $otherTill] = $this->scenario();

        $seen = $this->globaleIds($ceo->user);

        $this->assertContains($ceoTill->id, $seen['ids']);
        $this->assertContains($otherTill->id, $seen['ids']);
        $this->assertSame('20300.00', $seen['personnelle']);
    }

    public function test_the_private_till_is_hidden_from_everyone_else_on_comptes_de_caisse(): void
    {
        ['ceo' => $ceo, 'other' => $other, 'ceoTill' => $ceoTill, 'otherTill' => $otherTill] = $this->scenario();

        $seenByOther = $this->comptesIds($other->user);
        $this->assertNotContains($ceoTill->id, $seenByOther);
        $this->assertContains($otherTill->id, $seenByOther);

        $seenByCeo = $this->comptesIds($ceo->user);
        $this->assertContains($ceoTill->id, $seenByCeo);
        $this->assertContains($otherTill->id, $seenByCeo);
    }

    public function test_the_technical_account_does_not_see_the_private_till(): void
    {
        // Unlike every other display filter, the maintenance identity is NOT
        // exempt here — the owner's decision (27/10/2026).
        ['ceoTill' => $ceoTill] = $this->scenario();
        $maintainer = $this->staff(HiddenAccount::EMAIL, 'Rochdi', 'Karouali');

        $this->assertNotContains($ceoTill->id, $this->globaleIds($maintainer->user)['ids']);
        $this->assertNotContains($ceoTill->id, $this->comptesIds($maintainer->user));
    }

    public function test_the_gls_domain_staff_account_does_not_see_the_private_till(): void
    {
        ['ceoTill' => $ceoTill] = $this->scenario();
        $staff = $this->staff(HiddenAccount::STAFF_EMAIL, 'Rochdi', 'Karouali');

        $this->assertNotContains($ceoTill->id, $this->globaleIds($staff->user)['ids']);
        $this->assertNotContains($ceoTill->id, $this->comptesIds($staff->user));
    }

    public function test_the_technical_accounts_till_is_listed_for_nobody_not_even_itself(): void
    {
        ['ceo' => $ceo] = $this->scenario();
        $maintainer = $this->staff(HiddenAccount::EMAIL, 'Rochdi', 'Karouali');
        $staff = $this->staff(HiddenAccount::STAFF_EMAIL, 'Rochdi', 'Karouali');
        $devTill = $this->till($maintainer, '10.00');
        $staffTill = $this->till($staff, '20.00');

        foreach ([$maintainer->user, $ceo->user, $staff->user] as $viewer) {
            $this->assertNotContains($devTill->id, $this->globaleIds($viewer)['ids'], $viewer->email);
            $this->assertNotContains($devTill->id, $this->comptesIds($viewer), $viewer->email);
            // The GLS-domain staff account is an ordinary, visible one.
            $this->assertContains($staffTill->id, $this->globaleIds($viewer)['ids'], $viewer->email);
            $this->assertContains($staffTill->id, $this->comptesIds($viewer), $viewer->email);
        }
    }

    public function test_a_namesake_till_stays_visible(): void
    {
        // Keyed on the login address, never on the name: Amine Rafik is a
        // different person and his till is an ordinary one.
        ['other' => $other, 'otherTill' => $otherTill] = $this->scenario();
        $third = $this->staff('tiers@glszentrum.com', 'Sanae', 'Alaoui');

        $this->assertContains($otherTill->id, $this->globaleIds($third->user)['ids']);
        $this->assertContains($otherTill->id, $this->comptesIds($third->user));
    }
}
