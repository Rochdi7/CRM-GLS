<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Context;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CurrentContextTest extends TestCase
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
        $user->assignRole(Role::SUPER_ADMIN); // global access via Gate::before
        $this->actingAs($user);

        return $user;
    }

    private function centerUser(Etablissement $centre): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view'); // no centers.access-all
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $this->actingAs($user);

        return $user->fresh();
    }

    public function test_defaults_to_the_default_academic_year(): void
    {
        $this->globalUser();

        $this->assertSame($this->y2526->id, app(CurrentContext::class)->anneeScolaireId());
    }

    public function test_year_can_be_switched_and_persists_in_session(): void
    {
        $this->globalUser();
        $context = app(CurrentContext::class);

        $context->setAnneeScolaire($this->y2425->id);

        $this->assertSame($this->y2425->id, $context->anneeScolaireId());
        $this->assertSame($this->y2425->id, session('context.annee_scolaire_id'));
    }

    public function test_global_user_can_switch_center_and_select_all(): void
    {
        $this->globalUser();
        $context = app(CurrentContext::class);

        $this->assertTrue($context->canSwitchCenter());
        $this->assertTrue($context->isAllCenters());

        $context->setEtablissement($this->casa->id);
        $this->assertSame($this->casa->id, $context->etablissementId());
        $this->assertFalse($context->isAllCenters());

        $context->setEtablissement(null);
        $this->assertTrue($context->isAllCenters());
    }

    public function test_center_scoped_user_is_locked_to_their_center(): void
    {
        $this->centerUser($this->rabat);
        $context = app(CurrentContext::class);

        $this->assertFalse($context->canSwitchCenter());
        $this->assertSame($this->rabat->id, $context->etablissementId());

        // Attempting to switch is ignored.
        $context->setEtablissement($this->casa->id);
        $this->assertSame($this->rabat->id, $context->etablissementId());

        // Their switcher only lists their own center.
        $this->assertEquals([$this->rabat->id], $context->availableCentres()->pluck('id')->all());
    }
}
