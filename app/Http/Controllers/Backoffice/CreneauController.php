<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Attendance\Actions\GenererSeancesDepuisCreneau;
use App\Domain\Attendance\Queries\GetCreneauFormOptions;
use App\Domain\Attendance\Queries\GetCreneauxGrille;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Attendance\StoreCreneauRequest;
use App\Http\Requests\Backoffice\Attendance\UpdateCreneauRequest;
use App\Models\Creneau;
use App\Models\Group;
use App\Models\Salle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Emploi du temps — the weekly recurring schedule grid (créneaux), distinct
 * from Séances (dated occurrences, used for attendance — SeanceController).
 * Creating/editing/deleting a créneau generates or syncs its future
 * "Prévue" séances via GenererSeancesDepuisCreneau; a créneau is never
 * deleted through a raw destroy without also cleaning up the séances it
 * still owns (see that action's supprimerFuturs()).
 */
final class CreneauController extends Controller
{
    use AssertsContextScope;

    public function index(
        Request $request,
        GetCreneauxGrille $getCreneauxGrille,
        GetCreneauFormOptions $formOptions,
    ): Response {
        $this->authorize('viewAny', Creneau::class);

        $user = $request->user();
        $filters = [
            'groupFilter' => (string) $request->string('groupFilter'),
            'enseignantFilter' => (string) $request->string('enseignantFilter'),
            'salleFilter' => (string) $request->string('salleFilter'),
            'jourFilter' => (string) $request->string('jourFilter'),
        ];

        return Inertia::render('Backoffice/EmploiDuTemps/Index', [
            'creneaux' => $getCreneauxGrille($user, $filters),
            'filters' => $filters,
            'jours' => Creneau::JOURS,
            'groupOptions' => $formOptions->groups($user),
            'enseignantOptions' => $formOptions->enseignants(),
            'salleOptions' => $formOptions->salles(),
            'permissions' => [
                'create' => $user->can('create', Creneau::class),
                'update' => $user->can('attendance.update'),
                'delete' => $user->can('attendance.delete'),
            ],
        ]);
    }

    /**
     * One créneau (and its own generated séances) is created per selected
     * day — same group/time/teacher/room on each — letting a single submit
     * cover e.g. "Lundi, Mardi, Mercredi 10h-12h" instead of one request per day.
     */
    public function store(StoreCreneauRequest $request, GenererSeancesDepuisCreneau $generer): RedirectResponse
    {
        $this->authorize('create', Creneau::class);

        $data = $request->validated();

        // The créneau's group defines the centre + année every generated
        // séance inherits — one forged group_id would mass-write a whole
        // year of séances into a foreign centre (AssertsContextScope).
        $group = Group::findOrFail((int) $data['group_id']);
        $this->assertGroupInContext($request, $group);
        $this->assertSalleDuCentre($data['salle_id'] ?? null, $group);

        $jours = $data['jours_semaine'];
        unset($data['jours_semaine']);

        $bloque = false;

        DB::transaction(function () use ($data, $jours, $generer, &$bloque): void {
            foreach ($jours as $jour) {
                $creneau = Creneau::create([...$data, 'jour_semaine' => $jour]);
                $generer->generer($creneau);
                $bloque = $bloque || $generer->bloqueParFinFormation;
            }
        });

        // The slot is saved either way — but with the group's formation already
        // over, no séance can be dated inside it, so say so instead of
        // reporting a silent success.
        if ($bloque) {
            return redirect()->route('backoffice.emploi-du-temps.index')
                ->with('warning', __("Schedule slot created, but no session was generated: the group's end of training date has passed. Extend the group's end date to generate sessions."));
        }

        return redirect()->route('backoffice.emploi-du-temps.index')
            ->with('success', __('Schedule slot created.'));
    }

    public function update(UpdateCreneauRequest $request, Creneau $creneau, GenererSeancesDepuisCreneau $generer): RedirectResponse
    {
        $this->authorize('update', $creneau);
        $this->assertGroupInContext($request, $creneau->group, 'salle_id');

        $data = $request->validated();
        $this->assertSalleDuCentre($data['salle_id'] ?? null, $creneau->group);

        $creneau->update($data);
        $generer->resynchroniser($creneau);

        if ($generer->bloqueParFinFormation) {
            return redirect()->route('backoffice.emploi-du-temps.index')
                ->with('warning', __("Schedule slot updated, but no session was generated: the group's end of training date has passed. Extend the group's end date to generate sessions."));
        }

        return redirect()->route('backoffice.emploi-du-temps.index')
            ->with('success', __('Schedule slot updated.'));
    }

    /**
     * A booked room must belong to the group's own centre — the form's room
     * options are already centre-scoped (GetCreneauFormOptions::salles), so
     * only a forged/stale id reaches this, but without it another centre's
     * room could be silently double-booked.
     */
    private function assertSalleDuCentre(mixed $salleId, Group $group): void
    {
        if ($salleId === null || $salleId === '') {
            return;
        }

        $salle = Salle::findOrFail((int) $salleId);

        if ((int) $salle->etablissement_id !== (int) $group->etablissement_id) {
            throw ValidationException::withMessages([
                'salle_id' => __("This room belongs to another centre than the group's."),
            ]);
        }
    }

    public function destroy(Request $request, Creneau $creneau, GenererSeancesDepuisCreneau $generer): RedirectResponse
    {
        $this->authorize('delete', $creneau);
        $this->assertGroupInContext($request, $creneau->group, 'group_id');

        $generer->supprimerFuturs($creneau);
        $creneau->delete();

        return redirect()->route('backoffice.emploi-du-temps.index')
            ->with('success', __('Schedule slot deleted.'));
    }
}
