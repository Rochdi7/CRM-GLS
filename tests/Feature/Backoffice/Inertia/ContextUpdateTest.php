<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Livewire\Backoffice\Students\StudentsIndex;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 4 (docs/inertia-react-migration-plan.md) — the new
 * backoffice.context.update endpoint (ContextController), replacing the
 * Livewire ContextSwitcher as the write path for the top-bar switcher.
 * CurrentContext's own validation/authorization (already covered by
 * CurrentContextTest) is the real boundary; these tests verify the HTTP
 * layer around it, plus the mixed Inertia/Livewire propagation this phase
 * requires.
 */
final class ContextUpdateTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $y2425;

    private AnneeScolaire $y2526;

    private Etablissement $rabat;

    private Etablissement $casa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->y2425 = AnneeScolaire::create(['nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31', 'par_defaut' => false, 'inscription_ouverte' => false]);
        $this->y2526 = AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
    }

    private function globalUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    private function centerUser(Etablissement $centre): User
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $this->actingAs($user);

        return $user->fresh();
    }

    public function test_guest_is_rejected(): void
    {
        $this->post(route('backoffice.context.update'), ['etablissement_id' => $this->casa->id])
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_global_user_can_switch_to_an_allowed_center(): void
    {
        $this->globalUser();

        $this->post(route('backoffice.context.update'), ['etablissement_id' => $this->casa->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($this->casa->id, app(CurrentContext::class)->etablissementId());
    }

    public function test_global_user_can_select_all_centers(): void
    {
        $this->globalUser();
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        $this->post(route('backoffice.context.update'), ['etablissement_id' => null]);

        $this->assertTrue(app(CurrentContext::class)->isAllCenters());
    }

    public function test_center_scoped_user_cannot_switch_center(): void
    {
        $this->centerUser($this->rabat);

        $this->post(route('backoffice.context.update'), ['etablissement_id' => $this->casa->id]);

        // Silently ignored by CurrentContext — the user stays locked to
        // their own center, exactly like the Livewire switcher's behavior.
        $this->assertSame($this->rabat->id, app(CurrentContext::class)->etablissementId());
    }

    public function test_invalid_center_id_is_rejected_at_validation(): void
    {
        $this->globalUser();

        $this->post(route('backoffice.context.update'), ['etablissement_id' => 'not-a-number'])
            ->assertSessionHasErrors('etablissement_id');
    }

    public function test_valid_academic_year_succeeds(): void
    {
        $this->globalUser();

        $this->post(route('backoffice.context.update'), ['annee_scolaire_id' => $this->y2425->id]);

        $this->assertSame($this->y2425->id, app(CurrentContext::class)->anneeScolaireId());
    }

    public function test_invalid_academic_year_is_ignored(): void
    {
        $this->globalUser();
        $before = app(CurrentContext::class)->anneeScolaireId();

        $this->post(route('backoffice.context.update'), ['annee_scolaire_id' => 999999]);

        // CurrentContext::setAnneeScolaire() only persists an id that
        // actually exists — a nonexistent id leaves the prior selection
        // untouched (matches its own existing behavior/tests).
        $this->assertSame($before, app(CurrentContext::class)->anneeScolaireId());
    }

    public function test_context_persists_across_a_subsequent_request(): void
    {
        $this->globalUser();

        $this->post(route('backoffice.context.update'), ['etablissement_id' => $this->casa->id]);
        $this->get(route('backoffice.dashboard'));

        $this->assertSame($this->casa->id, app(CurrentContext::class)->etablissementId());
    }

    public function test_redirect_back_and_flash_message_work(): void
    {
        $this->globalUser();

        $this->from(route('backoffice.dashboard'))
            ->post(route('backoffice.context.update'), ['etablissement_id' => $this->casa->id])
            ->assertRedirect(route('backoffice.dashboard'))
            ->assertSessionHas('success');
    }

    public function test_stale_invalid_session_center_falls_back_safely(): void
    {
        $user = $this->globalUser();
        session(['context.etablissement_id' => 999999]); // simulate a deleted center id

        $this->get(route('backoffice.dashboard'))->assertOk();
    }

    /**
     * Mixed-stack requirement: changing context through the new Inertia/
     * React endpoint must be observed by legacy Livewire pages too — both
     * read the same CurrentContext/session, there is only one context
     * implementation.
     */
    public function test_context_change_through_the_new_endpoint_is_observed_by_a_legacy_livewire_page(): void
    {
        $this->globalUser();
        Student::factory()->create(['nom' => 'EtudiantRabatMixedStack', 'etablissement_id' => $this->rabat->id]);
        Student::factory()->create(['nom' => 'EtudiantCasaMixedStack', 'etablissement_id' => $this->casa->id]);

        // Both visible under "all centers".
        Livewire::test(StudentsIndex::class)
            ->assertSee('EtudiantRabatMixedStack')
            ->assertSee('EtudiantCasaMixedStack');

        // Change context through the new Inertia/React endpoint.
        $this->post(route('backoffice.context.update'), ['etablissement_id' => $this->rabat->id]);

        // The legacy Livewire component, mounted fresh, reflects the change.
        Livewire::test(StudentsIndex::class)
            ->assertSee('EtudiantRabatMixedStack')
            ->assertDontSee('EtudiantCasaMixedStack');
    }
}
