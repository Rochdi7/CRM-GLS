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
 * and their DAY-BY-DAY generation/sync of séances
 * (GenererSeancesDepuisCreneau): a run only ever creates the current day's
 * séance; the daily 08:00 job walks the group's period one morning at a
 * time from date_debut_formation to date_fin_formation.
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
            // Generation now REQUIRES a start date (day-by-day rule): a
            // group without date_debut_formation generates nothing.
            'date_debut_formation' => Carbon::today()->subDay()->toDateString(),
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

    public function test_creating_a_creneau_generates_only_the_current_day_seance(): void
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
        // Day-by-day: exactly ONE séance — today's. The rest of the period is
        // produced one morning at a time by the 08:00 job, never in bulk.
        $this->assertSame(1, Seance::where('creneau_id', $creneau->id)->count());

        $seance = Seance::where('creneau_id', $creneau->id)->firstOrFail();
        $this->assertSame(Carbon::today()->toDateString(), $seance->date_seance->toDateString());
        $this->assertSame($this->centre->id, $seance->etablissement_id);
        $this->assertSame($this->annee->id, $seance->annee_scolaire_id);
        $this->assertSame(Seance::STATUT_PREVUE, $seance->statut);
        $this->assertSame($jour, $seance->date_seance->isoWeekday());
    }

    public function test_selecting_multiple_days_creates_one_creneau_per_day(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create'));

        // Three consecutive weekdays starting today, so exactly one of the
        // créneaux falls on the current day.
        $jours = collect([0, 1, 2])
            ->map(fn (int $offset) => Carbon::today()->addDays($offset)->isoWeekday())
            ->sort()->values()->all();

        $this->post(route('backoffice.creneaux.store'), [
            'group_id' => $this->group->id,
            'jours_semaine' => $jours,
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ])->assertRedirect(route('backoffice.emploi-du-temps.index'));

        $this->assertSame(3, Creneau::count());
        $this->assertSame($jours, Creneau::orderBy('jour_semaine')->pluck('jour_semaine')->all());
        // Day-by-day: only TODAY's créneau has its séance; the other days'
        // séances are created by the 08:00 job on their own mornings.
        foreach (Creneau::all() as $creneau) {
            $attendu = $creneau->jour_semaine === Carbon::today()->isoWeekday() ? 1 : 0;
            $this->assertSame($attendu, Seance::where('creneau_id', $creneau->id)->count());
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

        // Mark today's séance as already done — it must survive untouched,
        // and the edit must NOT regenerate a duplicate for the same day.
        $doneSeance = Seance::where('creneau_id', $creneau->id)->firstOrFail();
        $doneSeance->update(['statut' => Seance::STATUT_EFFECTUEE]);

        // A future Prévue séance from an earlier morning's run (simulated) —
        // the resync must carry the new time onto it.
        $future = Seance::create([
            'group_id' => $this->group->id,
            'creneau_id' => $creneau->id,
            'date_seance' => Carbon::today()->addDays(7)->toDateString(),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

        $this->put(route('backoffice.creneaux.update', $creneau), [
            'jour_semaine' => $jour,
            'heure_debut' => '14:00',
            'heure_fin' => '16:00',
        ])->assertRedirect(route('backoffice.emploi-du-temps.index'));

        $doneSeance->refresh();
        $this->assertSame('10:00:00', $doneSeance->heure_debut);
        $this->assertSame(Seance::STATUT_EFFECTUEE, $doneSeance->statut);

        $future->refresh();
        $this->assertSame('14:00:00', $future->heure_debut);
        $this->assertSame(Seance::STATUT_PREVUE, $future->statut);

        // No duplicate Prévue was minted for today alongside the done one.
        $this->assertSame(2, Seance::where('creneau_id', $creneau->id)->count());
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

        // A future Prévue séance from an earlier morning's run (simulated) —
        // deleting the créneau must remove it.
        Seance::create([
            'group_id' => $this->group->id,
            'creneau_id' => $creneau->id,
            'date_seance' => Carbon::today()->addDays(7)->toDateString(),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

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

        $this->assertSame(1, Seance::count(), "Today's séance (inside the period) must still be generated.");
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

        $creneau = Creneau::firstOrFail();

        // A future Prévue séance beyond the soon-to-be-shortened period —
        // as if generated on its own morning before the period changed.
        Seance::create([
            'group_id' => $this->group->id,
            'creneau_id' => $creneau->id,
            'date_seance' => Carbon::today()->addDays(21)->toDateString(),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

        $avant = Seance::count();
        $this->assertSame(2, $avant);

        $nouvelleFin = Carbon::today()->addDays(14);
        $this->group->update(['date_fin_formation' => $nouvelleFin->toDateString()]);

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
        // Today's séance, inside the shortened period, survives.
        $this->assertSame(1, Seance::count());
    }

    /**
     * The 08:00 job (`seances:generate`) creates séances DAY BY DAY: nothing
     * before the group's date_debut_formation, exactly the current day's
     * séance on each matching morning, nothing once date_fin_formation is
     * past — and re-running within the same day never duplicates.
     */
    public function test_daily_job_generates_day_by_day_from_date_debut_to_date_fin(): void
    {
        $premierJour = Carbon::today()->addDays(2);

        $this->group->update([
            'date_debut_formation' => $premierJour->toDateString(),
            'date_fin_formation' => $premierJour->copy()->addDays(7)->toDateString(),
        ]);

        Creneau::create([
            'group_id' => $this->group->id,
            'jour_semaine' => $premierJour->isoWeekday(),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);

        // Before date_debut_formation: the job produces nothing.
        $this->artisan('seances:generate')->assertSuccessful();
        $this->assertSame(0, Seance::count());

        // The group's first day: exactly that day's séance.
        $this->travelTo($premierJour);
        $this->artisan('seances:generate')->assertSuccessful();
        $this->assertSame(1, Seance::count());
        $this->assertSame($premierJour->toDateString(), Seance::firstOrFail()->date_seance->toDateString());

        // Re-run the same day: idempotent, no duplicate.
        $this->artisan('seances:generate')->assertSuccessful();
        $this->assertSame(1, Seance::count());

        // Next weekly occurrence (still inside the period): one more.
        $this->travelTo($premierJour->copy()->addDays(7));
        $this->artisan('seances:generate')->assertSuccessful();
        $this->assertSame(2, Seance::count());

        // Past date_fin_formation: the job stops producing for this group.
        $this->travelTo($premierJour->copy()->addDays(14));
        $this->artisan('seances:generate')->assertSuccessful();
        $this->assertSame(2, Seance::count());
    }

    /**
     * A finished or cancelled group generates NOTHING — neither through the
     * 08:00 job (statut filter) nor through a créneau save (guard inside
     * GenererSeancesDepuisCreneau itself).
     */
    public function test_a_terminated_or_cancelled_group_generates_no_seance(): void
    {
        $user = $this->userWith('attendance.view', 'attendance.create');

        foreach ([Group::STATUT_FIN_FORMATION, Group::STATUT_ANNULEE] as $statut) {
            $groupe = Group::factory()->create([
                'statut' => $statut,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->annee->id,
                'date_debut_formation' => Carbon::today()->subDays(30)->toDateString(),
                'date_fin_formation' => Carbon::today()->addDays(30)->toDateString(),
            ]);

            // Saving a créneau on such a group mints no séance.
            $this->actingAs($user)->post(route('backoffice.creneaux.store'), [
                'group_id' => $groupe->id,
                'jours_semaine' => [Carbon::today()->isoWeekday()],
                'heure_debut' => '10:00',
                'heure_fin' => '12:00',
            ])->assertRedirect();

            $this->assertSame(0, Seance::where('group_id', $groupe->id)->count());
        }

        // The daily job skips them too.
        $this->artisan('seances:generate')->assertSuccessful();
        $this->assertSame(
            0,
            Seance::whereIn('group_id', Group::whereIn('statut', Group::STATUTS_HISTORIQUE)->pluck('id'))->count(),
        );
    }

    /**
     * A group with NO date_debut_formation generates nothing — there is no
     * start date to anchor generation on — and the user is warned instead of
     * getting a silent success.
     */
    public function test_a_group_without_start_date_generates_nothing_and_warns(): void
    {
        $this->group->update(['date_debut_formation' => null]);

        $this->actingAs($this->userWith('attendance.view', 'attendance.create'))
            ->post(route('backoffice.creneaux.store'), [
                'group_id' => $this->group->id,
                'jours_semaine' => [Carbon::today()->isoWeekday()],
                'heure_debut' => '10:00',
                'heure_fin' => '12:00',
            ])->assertRedirect()->assertSessionHas('warning');

        $this->assertSame(0, Seance::count());
        $this->assertSame(1, Creneau::count());

        // The daily job skips it too.
        $this->artisan('seances:generate')->assertSuccessful();
        $this->assertSame(0, Seance::count());
    }

    /**
     * `seances:purger-futures` removes the future « Prévue » séances the OLD
     * bulk generator left behind — but never today's séance of an active
     * group, never a séance with a roll call, never a detached or
     * Effectuée/Annulée one.
     */
    public function test_purge_command_removes_only_bulk_pregenerated_future_seances(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.create'));

        $this->post(route('backoffice.creneaux.store'), [
            'group_id' => $this->group->id,
            'jours_semaine' => [Carbon::today()->isoWeekday()],
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);
        $creneau = Creneau::firstOrFail();
        $aujourdhui = Seance::firstOrFail(); // today's, active group — must survive

        $seance = fn (int $jours, string $statut, ?int $creneauId = null) => Seance::create([
            'group_id' => $this->group->id,
            'creneau_id' => $creneauId ?? $creneau->id,
            'date_seance' => Carbon::today()->addDays($jours)->toDateString(),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
        ]);

        $futurePrevue = $seance(7, Seance::STATUT_PREVUE);       // bulk leftover → deleted
        $futureEffectuee = $seance(14, Seance::STATUT_EFFECTUEE); // real activity → kept

        // Detached (created by hand, creneau_id NULL) → kept.
        $detachee = $seance(21, Seance::STATUT_PREVUE);
        $detachee->update(['creneau_id' => null]);

        // Dry-run deletes nothing.
        $this->artisan('seances:purger-futures')->assertSuccessful();
        $this->assertSame(4, Seance::count());

        $this->artisan('seances:purger-futures', ['--apply' => true])->assertSuccessful();

        $this->assertDatabaseMissing('seances', ['id' => $futurePrevue->id]);
        $this->assertDatabaseHas('seances', ['id' => $aujourdhui->id]);
        $this->assertDatabaseHas('seances', ['id' => $futureEffectuee->id]);
        $this->assertDatabaseHas('seances', ['id' => $detachee->id]);
    }
}
