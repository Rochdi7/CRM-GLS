<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Access;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Support\Access\HiddenAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every backoffice screen, swept for the hidden accounts by NAME.
 *
 * The per-query tests in HiddenAccountTest prove each funnel works; this one
 * proves no screen forgot to call one. CLAUDE.md §11 makes a new module
 * visible-by-default — the maintainer reappears the moment somebody ships a
 * list, dropdown, stat card or export nobody filtered — so the guard has to
 * be a sweep over ROUTES, not a list of read models somebody remembers to
 * extend.
 *
 * It walks every parameterless backoffice GET route, renders it as a
 * super-admin who is NOT the maintainer (the CEO), and fails on the hidden
 * surname appearing anywhere in the response. A route added next month is
 * covered the day it is added, with no edit here.
 *
 * ⚠ Asserts on a name that exists ONLY as the hidden accounts, so a real
 * GLS record can never make it pass or fail by coincidence.
 */
final class HiddenAccountPageSweepTest extends TestCase
{
    use RefreshDatabase;

    /** The surname is unique to the hidden accounts in this fixture. */
    private const NEEDLE = 'Zzhiddenmaintainer';

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);

        $this->centre = Etablissement::factory()->create();
    }

    private function makeStaff(string $email, string $nom): Employee
    {
        $user = User::factory()->create(['email' => $email]);

        return Employee::withoutGlobalScopes()->create([
            'reference' => 'EMP-'.substr(md5($email), 0, 5),
            'nom' => $nom,
            'prenom' => 'Test',
            'email' => $email,
            'categorie' => Employee::CATEGORIE_RESPONSABLE_SYSTEME,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_id' => $this->centre->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @return list<string>
     */
    private function sweepableRoutes(): array
    {
        $names = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! str_starts_with($name, 'backoffice.')) {
                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            // Parameterless only — a {model} route needs a fixture per screen,
            // and the record pages are covered by the Gate::before deny test.
            if (str_contains($route->uri(), '{')) {
                continue;
            }

            // Auth screens render no data.
            if (in_array($name, ['backoffice.login', 'backoffice.password.request'], true)) {
                continue;
            }

            $names[] = $name;
        }

        sort($names);

        return array_values(array_unique($names));
    }

    public function test_no_backoffice_screen_names_the_hidden_accounts(): void
    {
        // Both hidden logins, each with the auto-provisioned till, plus a
        // real namesake-free control so an empty page cannot pass vacuously.
        $this->makeStaff(HiddenAccount::EMAIL, self::NEEDLE);
        $this->makeStaff(HiddenAccount::STAFF_EMAIL, self::NEEDLE);

        $ceo = $this->makeStaff('rafik@glszentrum.com', 'Rafik');
        $ceo->user->assignRole(Role::SUPER_ADMIN);

        // Money in the hidden tills, so a screen that sums or lists balances
        // cannot look clean merely because the rows are empty.
        foreach (Employee::withoutGlobalScopes()->whereIn('email', HiddenAccount::emails())->pluck('id') as $id) {
            Caisse::where('responsable_employee_id', $id)
                ->each(fn (Caisse $c) => $c->forceFill(['solde' => '4242.00'])->save());
        }

        $this->actingAs($ceo->user);

        $leaks = [];
        $checked = 0;

        foreach ($this->sweepableRoutes() as $name) {
            $response = $this->get(route($name));

            // 200 or a redirect are both fine; only the CONTENT matters.
            $body = $response->getContent();
            $checked++;

            if (stripos((string) $body, self::NEEDLE) !== false) {
                $leaks[] = $name;
            }
        }

        $this->assertGreaterThan(20, $checked, 'The sweep must actually cover the backoffice.');
        $this->assertSame(
            [],
            $leaks,
            "These screens name a hidden account — route the query through HiddenAccount (CLAUDE.md §11):\n"
                .implode("\n", $leaks),
        );
    }

    /**
     * The control: the sweep is capable of FAILING. A visible staff member
     * with the same shape as the hidden ones must be found by the same walk,
     * or a green run above would prove nothing.
     */
    public function test_the_sweep_detects_a_visible_employee(): void
    {
        $visible = $this->makeStaff('visible.person@glszentrum.com', self::NEEDLE);
        $ceo = $this->makeStaff('rafik@glszentrum.com', 'Rafik');
        $ceo->user->assignRole(Role::SUPER_ADMIN);

        $this->actingAs($ceo->user);

        $found = false;

        foreach ($this->sweepableRoutes() as $name) {
            if (stripos((string) $this->get(route($name))->getContent(), self::NEEDLE) !== false) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'The sweep found nobody at all — it would pass even if the maintainer leaked.',
        );
        $this->assertNotNull($visible->id);
    }

    /**
     * The subject-side filter must stay a READ filter.
     *
     * The entries about the hidden accounts' own records are still WRITTEN in
     * full, and « Inclure le compte technique » brings them back — otherwise
     * the fix above would have created exactly the permanent blind spot on a
     * privileged account that the journal exists to prevent (CLAUDE.md §11).
     */
    public function test_the_journal_still_records_hidden_subjects_and_the_toggle_shows_them(): void
    {
        $dev = $this->makeStaff(HiddenAccount::EMAIL, self::NEEDLE);
        $ceo = $this->makeStaff('rafik@glszentrum.com', 'Rafik');
        $ceo->user->assignRole(Role::SUPER_ADMIN);

        $till = Caisse::where('responsable_employee_id', $dev->id)->firstOrFail();

        // The rows exist in the table — nothing was skipped at write time.
        $this->assertTrue(
            \App\Models\Activity::query()
                ->where('subject_type', $till->getMorphClass())
                ->where('subject_id', $till->id)
                ->exists(),
            'The provisioning of the hidden till must still be journalled.',
        );

        $list = app(\App\Domain\Audit\Queries\GetActivityLogList::class);

        $hiddenFromDefault = json_encode($list(includeDeveloper: false, perPage: 200), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString(self::NEEDLE, (string) $hiddenFromDefault);

        $withToggle = json_encode($list(includeDeveloper: true, perPage: 200), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString(
            self::NEEDLE,
            (string) $withToggle,
            '« Inclure le compte technique » must bring the hidden entries back.',
        );
    }
}
