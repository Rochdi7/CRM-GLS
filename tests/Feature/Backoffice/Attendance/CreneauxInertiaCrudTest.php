<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Models\AnneeScolaire;
use App\Models\Creneau;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Seance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Emploi du temps module — créneaux (weekly recurring schedule slots) CRUD
 * and their generation/sync of future séances (GenererSeancesDepuisCreneau).
 */
final class CreneauxInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        // A wide year window so "today" always falls inside it in CI.
        $this->annee = AnneeScolaire::create([
            'nom' => 'Test', 'date_debut' => '2000-09-01', 'date_fin' => '2100-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'date_debut_formation' => null,
        ]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    public function test_index_requires_attendance_view_and_renders_the_react_page(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.emploi-du-temps.index'))
            ->assertForbidden();

        Creneau::create([
            'group_id' => $this->group->id,
            'jour_semaine' => 1,
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);

        $this->actingAs($this->userWith('attendance.view'))
            ->get(route('backoffice.emploi-du-temps.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/EmploiDuTemps/Index', false)
                ->has('creneaux', 1)
                ->has('groupOptions')
                ->has('enseignantOptions')
                ->has('salleOptions')
                ->has('jours')
                ->where('permissions.create', false)
            );
    }

    public function test_creating_a_creneau_generates_future_seances_for_its_weekday(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create'));

        $jour = Carbon::today()->isoWeekday();

        $this->post(route('backoffice.creneaux.store'), [
            'group_id' => $this->group->id,
            'jours_semaine' => [$jour],
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ])->assertRedirect(route('backoffice.emploi-du-temps.index'));

        $creneau = Creneau::firstOrFail();
        $this->assertSame($jour, $creneau->jour_semaine);
        $this->assertGreaterThan(0, Seance::where('creneau_id', $creneau->id)->count());

        $seance = Seance::where('creneau_id', $creneau->id)->firstOrFail();
        $this->assertSame($this->centre->id, $seance->etablissement_id);
        $this->assertSame($this->annee->id, $seance->annee_scolaire_id);
        $this->assertSame(Seance::STATUT_PREVUE, $seance->statut);
        $this->assertSame($jour, $seance->date_seance->isoWeekday());
    }

    public function test_selecting_multiple_days_creates_one_creneau_per_day(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create'));

        $this->post(route('backoffice.creneaux.store'), [
            'group_id' => $this->group->id,
            'jours_semaine' => [1, 2, 3],
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ])->assertRedirect(route('backoffice.emploi-du-temps.index'));

        $this->assertSame(3, Creneau::count());
        $this->assertSame([1, 2, 3], Creneau::orderBy('jour_semaine')->pluck('jour_semaine')->all());
        // Every créneau shares the same time — none is missing its séances.
        foreach (Creneau::all() as $creneau) {
            $this->assertGreaterThan(0, Seance::where('creneau_id', $creneau->id)->count());
        }
    }

    public function test_store_requires_attendance_create(): void
    {
        $this->actingAs($this->userWith('attendance.view'))
            ->post(route('backoffice.creneaux.store'), [
                'group_id' => $this->group->id,
                'jours_semaine' => [1],
                'heure_debut' => '10:00',
                'heure_fin' => '12:00',
            ])->assertForbidden();
    }

    public function test_store_rejects_an_empty_or_invalid_jours_semaine(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create'))
            ->post(route('backoffice.creneaux.store'), [
                'group_id' => $this->group->id,
                'jours_semaine' => [],
                'heure_debut' => '10:00',
                'heure_fin' => '12:00',
            ])->assertSessionHasErrors('jours_semaine');
    }

    public function test_updating_a_creneau_resyncs_future_prevue_seances_but_not_effectuee_ones(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create', 'attendance.update'));

        $jour = Carbon::today()->isoWeekday();

        $this->post(route('backoffice.creneaux.store'), [
            'group_id' => $this->group->id,
            'jours_semaine' => [$jour],
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);
        $creneau = Creneau::firstOrFail();

        // Mark one future séance as already done — it must survive untouched.
        $doneSeance = Seance::where('creneau_id', $creneau->id)->firstOrFail();
        $doneSeance->update(['statut' => Seance::STATUT_EFFECTUEE]);

        $this->put(route('backoffice.creneaux.update', $creneau), [
            'jour_semaine' => $jour,
            'heure_debut' => '14:00',
            'heure_fin' => '16:00',
        ])->assertRedirect(route('backoffice.emploi-du-temps.index'));

        $doneSeance->refresh();
        $this->assertSame('10:00:00', $doneSeance->heure_debut);
        $this->assertSame(Seance::STATUT_EFFECTUEE, $doneSeance->statut);

        $stillPrevue = Seance::where('creneau_id', $creneau->id)
            ->where('statut', Seance::STATUT_PREVUE)
            ->first();
        $this->assertNotNull($stillPrevue);
        $this->assertSame('14:00:00', $stillPrevue->heure_debut);
    }

    public function test_deleting_a_creneau_removes_only_its_future_prevue_seances(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create', 'attendance.delete'));

        $jour = Carbon::today()->isoWeekday();

        $this->post(route('backoffice.creneaux.store'), [
            'group_id' => $this->group->id,
            'jours_semaine' => [$jour],
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);
        $creneau = Creneau::firstOrFail();

        $doneSeance = Seance::where('creneau_id', $creneau->id)->firstOrFail();
        $doneSeance->update(['statut' => Seance::STATUT_EFFECTUEE]);

        $this->delete(route('backoffice.creneaux.destroy', $creneau))
            ->assertRedirect(route('backoffice.emploi-du-temps.index'));

        $this->assertDatabaseMissing('creneaux', ['id' => $creneau->id]);
        // The done séance survives (its creneau_id merely goes null via the FK).
        $this->assertDatabaseHas('seances', ['id' => $doneSeance->id, 'statut' => Seance::STATUT_EFFECTUEE]);
        $this->assertSame(0, Seance::where('statut', Seance::STATUT_PREVUE)->count());
    }

    /**
     * A group's date_fin_formation caps generation: no séance may be dated
     * after the group has finished, even though the academic year runs on.
     */
    public function test_generation_stops_at_the_group_end_of_formation(): void
    {
        $this->group->update([
            'date_debut_formation' => Carbon::today()->toDateString(),
            'date_fin_formation' => Carbon::today()->addDays(20)->toDateString(),
        ]);

        $this->actingAs($this->userWith('attendance.view', 'attendance.create'))
            ->post(route('backoffice.creneaux.store'), [
                'group_id' => $this->group->id,
                'jours_semaine' => [Carbon::today()->isoWeekday()],
                'heure_debut' => '10:00',
                'heure_fin' => '12:00',
            ])->assertRedirect();

        $this->assertGreaterThan(0, Seance::count(), 'Séances inside the period must still be generated.');
        $this->assertSame(
            0,
            Seance::whereDate('date_seance', '>', $this->group->date_fin_formation->toDateString())->count(),
            'No séance may be dated after the group end of formation.'
        );
    }

    /**
     * A group whose formation is already over generates nothing, and the user
     * is told to extend the end date rather than getting a silent success.
     */
    public function test_no_seance_is_generated_when_end_of_formation_has_passed(): void
    {
        $this->group->update([
            'date_debut_formation' => Carbon::today()->subDays(60)->toDateString(),
            'date_fin_formation' => Carbon::today()->subDay()->toDateString(),
        ]);

        $this->actingAs($this->userWith('attendance.view', 'attendance.create'))
            ->post(route('backoffice.creneaux.store'), [
                'group_id' => $this->group->id,
                'jours_semaine' => [Carbon::today()->isoWeekday()],
                'heure_debut' => '10:00',
                'heure_fin' => '12:00',
            ])->assertRedirect()->assertSessionHas('warning');

        $this->assertSame(0, Seance::count());
        // The créneau itself is still saved — only generation is withheld.
        $this->assertSame(1, Creneau::count());
    }

    /**
     * Shortening a group's date_fin_formation removes the untouched future
     * séances that now fall outside the period.
     */
    public function test_shortening_the_group_period_purges_out_of_period_seances(): void
    {
        $this->group->update([
            'date_debut_formation' => Carbon::today()->toDateString(),
            'date_fin_formation' => Carbon::today()->addDays(60)->toDateString(),
        ]);

        $user = $this->userWith('attendance.view', 'attendance.create', 'attendance.update');

        $this->actingAs($user)->post(route('backoffice.creneaux.store'), [
            'group_id' => $this->group->id,
            'jours_semaine' => [Carbon::today()->isoWeekday()],
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ])->assertRedirect();

        $avant = Seance::count();
        $this->assertGreaterThan(1, $avant);

        $nouvelleFin = Carbon::today()->addDays(14);
        $this->group->update(['date_fin_formation' => $nouvelleFin->toDateString()]);

        $creneau = Creneau::firstOrFail();
        $this->actingAs($user)->put(route('backoffice.creneaux.update', $creneau), [
            'group_id' => $this->group->id,
            'jour_semaine' => $creneau->jour_semaine,
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ])->assertRedirect();

        $this->assertSame(
            0,
            Seance::whereDate('date_seance', '>', $nouvelleFin->toDateString())->count(),
            'Séances beyond the shortened period must be removed.'
        );
        $this->assertLessThan($avant, Seance::count());
    }
}
