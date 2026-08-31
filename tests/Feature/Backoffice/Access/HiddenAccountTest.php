<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Access;

use App\Domain\Finance\Queries\GetCaisseGlobale;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Scopes\HiddenAccountScope;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * The maintainer / developer login is invisible to every other user
 * (App\Support\Access\HiddenAccount) — including to the CEO's super-admin
 * account, which bypasses authorization but is NOT exempt from this display
 * filter.
 *
 * Regression under test (reported 30/08/2026): the developer's personal till
 * was still listed on « Caisse globale » and « Comptes de caisse » with an
 * empty Responsable column. `hideCaisses()` filtered on
 * `whereDoesntHave('responsable.user', …)`, but `Employee` carries
 * HiddenAccountScope as a GLOBAL scope, which applies inside that nested
 * subquery too — so the lookup searched for the maintainer's employee row in
 * a set he had already been scoped out of, found nothing, and concluded the
 * caisse had no maintainer responsable. The one row that had to be filtered
 * was the only one the filter could not see.
 */
final class HiddenAccountTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);

        $this->centre = Etablissement::factory()->create();
    }

    /**
     * Create the maintainer: user + employee + the auto-provisioned till.
     */
    private function maintainer(): Employee
    {
        $user = User::factory()->create(['email' => HiddenAccount::EMAIL]);

        $employee = Employee::withoutGlobalScopes()->create([
            'reference' => 'EMP-DEV',
            'nom' => 'Karouali',
            'prenom' => 'Rochdi',
            'email' => HiddenAccount::EMAIL,
            'categorie' => Employee::CATEGORIE_RESPONSABLE_SYSTEME,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_id' => $this->centre->id,
            'user_id' => $user->id,
        ]);

        return $employee;
    }

    private function staff(string $email): Employee
    {
        $user = User::factory()->create(['email' => $email]);

        return Employee::withoutGlobalScopes()->create([
            'reference' => 'EMP-'.substr(md5($email), 0, 5),
            'nom' => 'Rafik',
            'prenom' => 'Mohammed',
            'email' => $email,
            'categorie' => Employee::CATEGORIE_RESPONSABLE_SYSTEME,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_id' => $this->centre->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_the_maintainers_till_is_hidden_from_a_caisse_query(): void
    {
        $dev = $this->maintainer();
        $ceo = $this->staff('rafik@glszentrum.com');

        // The tills auto-provisioned by the EmployeeObserver.
        $devTill = Caisse::where('responsable_employee_id', $dev->id)->firstOrFail();
        $ceoTill = Caisse::where('responsable_employee_id', $ceo->id)->firstOrFail();

        $this->actingAs($ceo->user);

        $query = Caisse::query();
        HiddenAccount::hideCaisses($query);
        $visible = $query->pluck('id');

        $this->assertNotContains(
            $devTill->id,
            $visible,
            'The maintainer\'s till must never be listed for another user.',
        );
        $this->assertContains(
            $ceoTill->id,
            $visible,
            'A real staff till must stay listed.',
        );
    }

    public function test_the_maintainers_till_is_absent_from_caisse_globale(): void
    {
        $dev = $this->maintainer();
        $ceo = $this->staff('rafik@glszentrum.com');

        $devTill = Caisse::where('responsable_employee_id', $dev->id)->firstOrFail();
        // A non-zero solde so DormantTill cannot be what hides it — the row
        // must disappear because of WHO owns it, not because it is empty.
        $devTill->forceFill(['solde' => '1500.00'])->save();

        $this->actingAs($ceo->user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $result = app(GetCaisseGlobale::class)($ceo->user);

        $ids = collect($result['comptes'])->flatten(1)->pluck('id');

        $this->assertNotContains($devTill->id, $ids);
        $this->assertStringNotContainsString(
            '1500',
            $result['total'],
            'The hidden till must not be folded into the totals either.',
        );
    }

    public function test_a_namesake_staff_member_stays_visible(): void
    {
        $this->maintainer();

        // Same person's name, a REAL GLS login — must not be caught by the
        // filter, which keys on the e-mail address and nothing else.
        $namesake = $this->staff('rochdi.karouali@glszentrum.com');
        $ceo = $this->staff('rafik@glszentrum.com');

        $this->actingAs($ceo->user);

        $namesakeTill = Caisse::where('responsable_employee_id', $namesake->id)->firstOrFail();

        $query = Caisse::query();
        HiddenAccount::hideCaisses($query);

        $this->assertContains($namesakeTill->id, $query->pluck('id'));
        $this->assertTrue(Employee::whereKey($namesake->id)->exists());
    }

    public function test_the_maintainer_still_sees_himself(): void
    {
        $dev = $this->maintainer();
        $devTill = Caisse::where('responsable_employee_id', $dev->id)->firstOrFail();

        $this->actingAs($dev->user);

        $query = Caisse::query();
        HiddenAccount::hideCaisses($query);

        $this->assertContains(
            $devTill->id,
            $query->pluck('id'),
            'The maintainer must keep resolving his own records, or his profile 500s.',
        );
        $this->assertTrue(Employee::whereKey($dev->id)->exists());
    }

    public function test_the_employee_row_is_hidden_by_the_global_scope(): void
    {
        $dev = $this->maintainer();
        $ceo = $this->staff('rafik@glszentrum.com');

        $this->actingAs($ceo->user);

        $this->assertFalse(Employee::whereKey($dev->id)->exists());
        $this->assertTrue(
            Employee::withoutGlobalScope(HiddenAccountScope::class)->whereKey($dev->id)->exists(),
            'The row itself is never deleted — only filtered from reads.',
        );
    }

    /**
     * Every read model that offers a caisse to pick, or scopes the journal to
     * a set of caisses, must drop the maintainer's till. Each of these named
     * the hidden account in a user-facing dropdown before 30/08/2026.
     */
    public function test_the_maintainers_till_is_absent_from_every_caisse_dropdown(): void
    {
        $dev = $this->maintainer();
        $ceo = $this->staff('rafik@glszentrum.com');

        $devTill = Caisse::where('responsable_employee_id', $dev->id)->firstOrFail();
        $ceoTill = Caisse::where('responsable_employee_id', $ceo->id)->firstOrFail();

        $this->actingAs($ceo->user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $transferOptions = app(\App\Domain\Finance\Queries\GetCaisseTransfersList::class)
            ->caisseOptions($ceo->user)->pluck('id');
        $this->assertNotContains($devTill->id, $transferOptions);
        $this->assertContains($ceoTill->id, $transferOptions);

        $paymentOptions = app(\App\Domain\Payments\Queries\GetEncaissementsList::class)
            ->caisseOptions($ceo->user)->pluck('id');
        $this->assertNotContains($devTill->id, $paymentOptions);

        $scope = new \ReflectionMethod(\App\Domain\Finance\Queries\GetCaisseJournal::class, 'caisseIds');
        $scope->setAccessible(true);
        $journalIds = $scope->invoke(app(\App\Domain\Finance\Queries\GetCaisseJournal::class), $ceo->user, 'all');
        $this->assertNotContains($devTill->id, $journalIds);
        $this->assertContains($ceoTill->id, $journalIds);
    }

    /**
     * The list queries filter the maintainer out, but a hand-typed URL goes
     * straight to a controller's authorize() with the model already resolved
     * by route-model binding. Before 30/08/2026 the CEO could open
     * /backoffice/caisses/<the maintainer's till> and read his page in full:
     * Gate::before granted it to every super-admin before any policy ran.
     * The deny therefore lives in Gate::before itself, ABOVE the bypass.
     */
    public function test_a_super_admin_cannot_reach_the_maintainers_records_by_url(): void
    {
        $dev = $this->maintainer();
        $ceo = $this->staff('rafik@glszentrum.com');
        $ceo->user->assignRole(\App\Models\Role::SUPER_ADMIN);

        $devTill = Caisse::where('responsable_employee_id', $dev->id)->firstOrFail();
        $ceoTill = Caisse::where('responsable_employee_id', $ceo->id)->firstOrFail();

        $this->assertFalse(Gate::forUser($ceo->user)->allows('view', $devTill));
        $this->assertFalse(Gate::forUser($ceo->user)->allows('view', $dev->user));
        $this->assertFalse(Gate::forUser($ceo->user)->allows('view', $dev));

        // The bypass is otherwise intact — a super-admin still reaches every
        // record that is not the maintainer's own.
        $this->assertTrue(Gate::forUser($ceo->user)->allows('view', $ceoTill));

        $this->actingAs($ceo->user)
            ->get(route('backoffice.caisses.show', $devTill))
            ->assertForbidden();
    }

    public function test_the_maintainer_still_reaches_his_own_records_by_url(): void
    {
        $dev = $this->maintainer();
        $dev->user->assignRole(\App\Models\Role::SUPER_ADMIN);

        $devTill = Caisse::where('responsable_employee_id', $dev->id)->firstOrFail();

        $this->assertTrue(Gate::forUser($dev->user)->allows('view', $devTill));
        $this->assertTrue(Gate::forUser($dev->user)->allows('view', $dev->user));

        $this->actingAs($dev->user)
            ->get(route('backoffice.caisses.show', $devTill))
            ->assertOk();
    }
}
