<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Audit;

use App\Domain\Audit\Queries\GetActivityLogList;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

/**
 * Journal d'audit (CLAUDE.md §11 "Audit log").
 *
 * These tests pin the guarantees the journal is FOR, not just that a page
 * renders: that every change is captured with its before/after values, that
 * the trail records where an action came from, that a super-admin is logged
 * like everyone else, and that no code path can rewrite history.
 */
final class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user->fresh();
    }

    // ── Access control ──────────────────────────────────────────────────

    public function test_the_page_requires_the_audit_logs_view_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('backoffice.audit-logs.index'))->assertForbidden();
    }

    public function test_a_user_with_the_permission_can_open_the_journal(): void
    {
        $this->actingAs($this->userWith('audit-logs.view'));

        $this->get(route('backoffice.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Backoffice/AuditLogs/Index'));
    }

    public function test_a_super_admin_can_open_the_journal_without_the_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        $this->actingAs($user->fresh());

        $this->get(route('backoffice.audit-logs.index'))->assertOk();
    }

    public function test_guests_are_redirected_to_the_login(): void
    {
        $this->get(route('backoffice.audit-logs.index'))
            ->assertRedirect(route('backoffice.login'));
    }

    // ── Capture depth ───────────────────────────────────────────────────

    public function test_it_records_the_before_and_after_of_every_changed_field(): void
    {
        $this->actingAs($this->userWith('fees.view'));

        $frais = Frais::create(['nom' => 'Frais initial', 'montant_defaut' => '100.00', 'statut' => 'Actif']);

        $frais->update(['nom' => 'Frais renommé', 'montant_defaut' => '250.00']);

        $entry = Activity::query()->where('log_name', 'frais')->where('event', 'updated')->latest('id')->firstOrFail();

        $changes = $entry->attribute_changes->toArray();

        // Both edited columns must be present, with their OLD and NEW values —
        // the whole point of the journal is answering "changed from what?".
        $this->assertSame('Frais renommé', $changes['attributes']['nom']);
        $this->assertSame('Frais initial', $changes['old']['nom']);
        $this->assertSame('250.00', (string) $changes['attributes']['montant_defaut']);
        $this->assertSame('100.00', (string) $changes['old']['montant_defaut']);
    }

    public function test_it_logs_fields_outside_any_allowlist(): void
    {
        // Regression guard: the pre-journal implementation used a narrow
        // logOnly([...]) per model, so edits to unlisted columns vanished.
        $this->actingAs($this->userWith('centers.view'));

        $centre = Etablissement::create(['nom_centre' => 'GLS Test', 'ville' => 'Casablanca']);
        $centre->update(['adresse' => '12 rue Test', 'telephone' => '0522000000']);

        $entry = Activity::query()->where('log_name', 'etablissement')->where('event', 'updated')->latest('id')->firstOrFail();
        $changed = array_keys($entry->attribute_changes->toArray()['attributes']);

        $this->assertContains('adresse', $changed);
        $this->assertContains('telephone', $changed);
    }

    public function test_it_never_records_password_values(): void
    {
        $this->actingAs($this->userWith('users.view'));

        $target = User::factory()->create();
        $target->update(['password' => 'a-brand-new-secret', 'name' => 'Nom modifié']);

        $entry = Activity::query()->where('log_name', 'user')->where('event', 'updated')->latest('id')->firstOrFail();
        $recorded = $entry->attribute_changes->toArray();

        $this->assertArrayNotHasKey('password', $recorded['attributes'] ?? []);
        $this->assertArrayNotHasKey('password', $recorded['old'] ?? []);
        $this->assertStringNotContainsString('a-brand-new-secret', json_encode($recorded));
        // The rest of the edit is still recorded.
        $this->assertSame('Nom modifié', $recorded['attributes']['name']);
    }

    // ── Forensic context ────────────────────────────────────────────────

    public function test_it_stamps_who_when_and_from_where(): void
    {
        $actor = $this->userWith('fees.view', 'fees.create');
        $this->actingAs($actor);

        $this->post(route('backoffice.frais.store'), [
            'nom' => 'Frais audité',
            'montant_defaut' => '400.00',
            'statut' => 'Actif',
        ]);

        $entry = Activity::query()->where('log_name', 'frais')->latest('id')->firstOrFail();

        $this->assertSame($actor->id, $entry->causer_id);
        $this->assertNotNull($entry->ip_address);
        $this->assertSame('POST', $entry->method);
        $this->assertSame('backoffice.frais.store', $entry->route_name);
        $this->assertStringContainsString('/backoffice/frais', (string) $entry->url);
        // Second precision — investigations order events inside one minute.
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $entry->created_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_it_freezes_the_actor_identity_at_write_time(): void
    {
        $actor = $this->userWith('fees.view');
        $this->actingAs($actor);

        Frais::create(['nom' => 'Frais X', 'montant_defaut' => '10.00', 'statut' => 'Actif']);

        $entry = Activity::query()->where('log_name', 'frais')->latest('id')->firstOrFail();
        $labelAtWriteTime = $entry->causer_label;

        $this->assertNotNull($labelAtWriteTime);
        $this->assertStringContainsString($actor->name, $labelAtWriteTime);

        // Renaming the user later must not rewrite what the journal already said.
        $actor->update(['name' => 'Nom totalement différent']);

        $this->assertSame($labelAtWriteTime, $entry->fresh()->causer_label);
    }

    public function test_a_super_admin_is_logged_like_any_other_user(): void
    {
        // The whole point of "even if superadmin does it": bypassing every
        // Gate must not bypass being recorded.
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($admin->fresh());

        $frais = Frais::create(['nom' => 'Frais admin', 'montant_defaut' => '99.00', 'statut' => 'Actif']);
        $frais->update(['montant_defaut' => '1.00']);

        $entry = Activity::query()->where('log_name', 'frais')->where('event', 'updated')->latest('id')->firstOrFail();

        $this->assertSame($admin->id, $entry->causer_id);
        $this->assertSame('1.00', (string) $entry->attribute_changes->toArray()['attributes']['montant_defaut']);
    }

    // ── Immutability ────────────────────────────────────────────────────

    public function test_an_audit_entry_cannot_be_modified(): void
    {
        $this->actingAs($this->userWith('fees.view'));
        Frais::create(['nom' => 'Frais', 'montant_defaut' => '10.00', 'statut' => 'Actif']);

        $entry = Activity::query()->latest('id')->firstOrFail();

        $this->expectException(RuntimeException::class);
        $entry->update(['description' => 'trafiqué']);
    }

    public function test_an_audit_entry_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWith('fees.view'));
        Frais::create(['nom' => 'Frais', 'montant_defaut' => '10.00', 'statut' => 'Actif']);

        $entry = Activity::query()->latest('id')->firstOrFail();

        $this->expectException(RuntimeException::class);
        $entry->delete();
    }

    public function test_the_journal_exposes_no_write_routes(): void
    {
        $this->actingAs($this->userWith('audit-logs.view'));

        // Both URLs answer GET only (index and show), so every write verb is
        // 405 Method Not Allowed — there is no action to route to. Pinning the
        // exact code matters: asserting merely "not 2xx" would still pass if a
        // store/update/destroy route were added later, which is precisely the
        // regression this guards against.
        $this->post('/backoffice/audit-logs')->assertStatus(405);
        $this->put('/backoffice/audit-logs')->assertStatus(405);
        $this->delete('/backoffice/audit-logs')->assertStatus(405);
        $this->post('/backoffice/audit-logs/1')->assertStatus(405);
        $this->put('/backoffice/audit-logs/1')->assertStatus(405);
        $this->delete('/backoffice/audit-logs/1')->assertStatus(405);
    }

    // ── Authentication trail ────────────────────────────────────────────

    public function test_it_records_a_successful_login_with_its_ip(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => true]);

        $this->post(route('backoffice.login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $entry = Activity::query()->where('log_name', 'authentication')->where('event', 'login')->latest('id')->firstOrFail();

        $this->assertSame($user->id, $entry->causer_id);
        $this->assertNotNull($entry->ip_address);
    }

    public function test_it_records_a_failed_login_attempt(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => true]);

        $this->post(route('backoffice.login.store'), [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $entry = Activity::query()->where('log_name', 'authentication')->where('event', 'login_failed')->latest('id')->firstOrFail();

        $this->assertNotNull($entry->ip_address);
        $this->assertSame($user->email, $entry->getProperty('login'));
        // The submitted password must never reach the journal.
        $this->assertStringNotContainsString('wrong-password', json_encode($entry->properties->toArray()));
    }

    public function test_a_failed_login_for_an_unknown_account_is_recorded_without_crashing(): void
    {
        // Regression: performedOn() only accepts a real Model, so passing null
        // for an unknown username threw a TypeError and 500'd the login page.
        $this->post(route('backoffice.login.store'), [
            'login' => 'compte-inexistant',
            'password' => 'peu-importe',
        ])->assertRedirect();

        $entry = Activity::query()->where('event', 'login_failed')->latest('id')->firstOrFail();

        $this->assertNull($entry->causer_id);
        $this->assertNull($entry->subject_id);
        $this->assertSame('compte-inexistant', $entry->getProperty('login'));
    }

    public function test_each_authentication_event_is_recorded_exactly_once(): void
    {
        // Regression: the listener was both auto-discovered AND registered via
        // Event::subscribe(), so every auth event was journalled twice.
        $user = User::factory()->create(['password' => 'password', 'is_active' => true]);

        $this->post(route('backoffice.login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertSame(
            1,
            Activity::query()->where('log_name', 'authentication')->where('event', 'login')->count(),
        );
    }

    // ── Detail page & readability ───────────────────────────────────────

    public function test_the_detail_page_opens_for_one_entry(): void
    {
        $this->actingAs($this->userWith('audit-logs.view', 'fees.view'));

        Frais::create(['nom' => 'Frais détail', 'montant_defaut' => '75.00', 'statut' => 'Actif']);
        $entry = Activity::query()->latest('id')->firstOrFail();

        $this->get(route('backoffice.audit-logs.show', $entry->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/AuditLogs/Show')
                ->where('entry.id', $entry->id));
    }

    public function test_the_detail_page_requires_the_permission(): void
    {
        $this->actingAs($this->userWith('fees.view'));

        Frais::create(['nom' => 'Frais', 'montant_defaut' => '10.00', 'statut' => 'Actif']);
        $entry = Activity::query()->latest('id')->firstOrFail();

        $this->get(route('backoffice.audit-logs.show', $entry->id))->assertForbidden();
    }

    public function test_an_unknown_entry_is_a_404(): void
    {
        $this->actingAs($this->userWith('audit-logs.view'));

        $this->get(route('backoffice.audit-logs.show', 999999))->assertNotFound();
    }

    public function test_foreign_keys_are_shown_as_names_not_raw_ids(): void
    {
        // The reason this page exists: "ENSEIGNANT_ID: 11" means nothing to a
        // director. The id stays (it is what was stored), but the name must be
        // resolved alongside it.
        $viewer = $this->userWith('audit-logs.view');
        $centre = Etablissement::create(['nom_centre' => 'GLS Marrakech', 'ville' => 'Marrakech']);

        $this->actingAs($viewer);
        Salle::create(['nom' => 'Salle 1', 'etablissement_id' => $centre->id, 'capacite' => 20, 'statut' => 'Actif']);

        $entry = Activity::query()->where('log_name', 'salle')->latest('id')->firstOrFail();
        $presented = app(GetActivityLogList::class)->find($entry->id);

        $centreChange = collect($presented['changes'])->firstWhere('field', 'etablissement_id');

        $this->assertNotNull($centreChange);
        $this->assertSame('Centre', $centreChange['label']);
        $this->assertSame((string) $centre->id, $centreChange['new']);
        $this->assertSame('GLS Marrakech', $centreChange['newLabel']);
    }

    public function test_columns_get_french_labels(): void
    {
        $this->actingAs($this->userWith('audit-logs.view', 'fees.view'));

        $frais = Frais::create(['nom' => 'Ancien', 'montant_defaut' => '10.00', 'statut' => 'Actif']);
        $frais->update(['montant_defaut' => '20.00']);

        $entry = Activity::query()->where('event', 'updated')->latest('id')->firstOrFail();
        $presented = app(GetActivityLogList::class)->find($entry->id);

        $change = collect($presented['changes'])->firstWhere('field', 'montant_defaut');

        $this->assertSame('Montant par défaut', $change['label']);
    }

    public function test_plumbing_columns_are_hidden_on_a_creation(): void
    {
        // id/created_at/updated_at on a creation are noise that pushed the
        // meaningful fields out of view.
        $this->actingAs($this->userWith('audit-logs.view', 'fees.view'));

        Frais::create(['nom' => 'Frais propre', 'montant_defaut' => '30.00', 'statut' => 'Actif']);

        $entry = Activity::query()->where('event', 'created')->latest('id')->firstOrFail();
        $fields = collect(app(GetActivityLogList::class)->find($entry->id)['changes'])->pluck('field');

        $this->assertNotContains('id', $fields);
        $this->assertNotContains('created_at', $fields);
        $this->assertNotContains('updated_at', $fields);
        $this->assertContains('nom', $fields);
    }

    public function test_dates_are_shown_in_french_format(): void
    {
        $this->actingAs($this->userWith('audit-logs.view', 'academic-years.view'));

        AnneeScolaire::create([
            'nom' => '2026/2027',
            'date_debut' => '2026-09-01',
            'date_fin' => '2027-06-30',
            'par_defaut' => false,
        ]);

        $entry = Activity::query()->where('log_name', 'annee_scolaire')->latest('id')->firstOrFail();
        $change = collect(app(GetActivityLogList::class)->find($entry->id)['changes'])
            ->firstWhere('field', 'date_debut');

        // Not 2026-09-01T00:00:00.000000Z, and no meaningless midnight.
        $this->assertSame('01/09/2026', $change['new']);
    }

    // ── Filtering ───────────────────────────────────────────────────────

    public function test_the_money_only_scope_returns_finance_entries_only(): void
    {
        $this->actingAs($this->userWith('audit-logs.view', 'fees.view'));

        Frais::create(['nom' => 'Frais catalogue', 'montant_defaut' => '10.00', 'statut' => 'Actif']);

        $this->get(route('backoffice.audit-logs.index', ['financeOnly' => 1]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/AuditLogs/Index')
                // `frais` is the catalog, not a money movement — it must not
                // appear under the finance scope.
                ->where('entries.data', fn ($rows) => collect($rows)->every(
                    fn (array $row): bool => $row['logName'] !== 'frais',
                ))
            );
    }

    public function test_it_can_filter_by_actor(): void
    {
        $viewer = $this->userWith('audit-logs.view');
        $other = $this->userWith('fees.view');

        $this->actingAs($other);
        Frais::create(['nom' => 'Frais de A', 'montant_defaut' => '10.00', 'statut' => 'Actif']);

        $this->actingAs($viewer);

        $this->get(route('backoffice.audit-logs.index', ['causerId' => $other->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('entries.data', fn ($rows) => collect($rows)->every(
                    fn (array $row): bool => $row['causerId'] === $other->id,
                ))
            );
    }
}
