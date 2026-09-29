<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\People;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renaming an employee re-copies the name onto its till (`caisses.nom`) and
 * its login (`users.name`) — SynchroniserNomEmploye, fired by
 * EmployeeObserver — and moves NO money.
 */
final class EmployeeNameSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function employee(): Employee
    {
        $employee = Employee::factory()->create([
            'prenom' => 'Hafssa',
            'nom' => 'Elkhattabi',
            'etablissement_id' => Etablissement::factory()->create()->id,
        ]);

        return $employee->fresh(['till', 'user']);
    }

    public function test_renaming_an_employee_renames_its_till_and_login_without_moving_money(): void
    {
        $employee = $this->employee();
        $till = $employee->till;
        $this->assertSame('Hafssa Elkhattabi', $till->nom);

        app(CaisseLedger::class)->credit($till, 500, 'Test');
        $solde = $till->fresh()->solde;

        $employee->update(['prenom' => 'Hafsa', 'nom' => 'El Khattabi']);

        $this->assertSame('Hafsa El Khattabi', $till->fresh()->nom);
        $this->assertSame('Hafsa El Khattabi', $employee->user->fresh()->name);
        $this->assertSame((string) $solde, (string) $till->fresh()->solde);
    }

    public function test_an_external_safe_the_employee_is_responsable_of_keeps_its_own_name(): void
    {
        $employee = $this->employee();
        $coffre = Caisse::create([
            'nom' => 'Coffre central',
            'type' => Caisse::TYPE_EXTERNE,
            'etablissement_id' => $employee->etablissement_id,
            'responsable_employee_id' => $employee->id,
            'solde' => 0,
            'statut' => Caisse::STATUT_ACTIVE,
        ]);

        $employee->update(['nom' => 'Autre']);

        $this->assertSame('Coffre central', $coffre->fresh()->nom);
        $this->assertSame('Hafssa Autre', $employee->till->fresh()->nom);
    }

    public function test_renaming_yourself_in_profil_renames_the_employee_and_the_caisse(): void
    {
        $employee = $this->employee();
        $user = $employee->user;

        $this->actingAs($user)->post(route('backoffice.profile.update'), [
            'prenom' => 'Hafsa',
            'nom' => 'El Khattabi',
            'email' => $user->email ?? 'hafsa@example.com',
            'phone_pays' => 'MA',
            'telephone' => '',
            'whatsapp' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Hafsa El Khattabi', $employee->fresh()->nomComplet());
        $this->assertSame('Hafsa El Khattabi', $user->fresh()->name);
        $this->assertSame('Hafsa El Khattabi', $employee->till->fresh()->nom);
    }

    public function test_the_users_screen_cannot_rename_a_login_away_from_its_employee(): void
    {
        $employee = $this->employee();
        $target = $employee->user;
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->put(route('backoffice.users.update', $target), [
            'name' => 'Nom Forgé',
            'email' => $target->email ?? 'x@example.com',
            'username' => $target->username,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Hafssa Elkhattabi', $target->fresh()->name);
        $this->assertSame('Hafssa Elkhattabi', $employee->till->fresh()->nom);
    }

    public function test_the_catch_up_command_is_a_dry_run_by_default(): void
    {
        $employee = $this->employee();
        // Simulate pre-fix drift: the till kept the old name.
        Caisse::query()->whereKey($employee->till->id)->update(['nom' => 'Ancien Nom']);

        $this->artisan('caisses:synchroniser-noms')->assertSuccessful();
        $this->assertSame('Ancien Nom', $employee->till->fresh()->nom);

        $this->artisan('caisses:synchroniser-noms', ['--apply' => true])->assertSuccessful();
        $this->assertSame('Hafssa Elkhattabi', $employee->till->fresh()->nom);
    }
}
