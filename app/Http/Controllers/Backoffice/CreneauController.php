<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Attendance\Actions\GenererSeancesDepuisCreneau;
use App\Domain\Attendance\Queries\GetCreneauFormOptions;
use App\Domain\Attendance\Queries\GetCreneauxGrille;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Attendance\StoreCreneauRequest;
use App\Http\Requests\Backoffice\Attendance\UpdateCreneauRequest;
use App\Models\Creneau;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $creneau->update($request->validated());
        $generer->resynchroniser($creneau);

        if ($generer->bloqueParFinFormation) {
            return redirect()->route('backoffice.emploi-du-temps.index')
                ->with('warning', __("Schedule slot updated, but no session was generated: the group's end of training date has passed. Extend the group's end date to generate sessions."));
        }

        return redirect()->route('backoffice.emploi-du-temps.index')
            ->with('success', __('Schedule slot updated.'));
    }

    public function destroy(Creneau $creneau, GenererSeancesDepuisCreneau $generer): RedirectResponse
    {
        $this->authorize('delete', $creneau);

        $generer->supprimerFuturs($creneau);
        $creneau->delete();

        return redirect()->route('backoffice.emploi-du-temps.index')
            ->with('success', __('Schedule slot deleted.'));
    }
}
