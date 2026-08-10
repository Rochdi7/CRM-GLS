<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Attendance\Actions\EnregistrerPresences;
use App\Domain\Attendance\Queries\GetSeanceDetails;
use App\Domain\Attendance\Queries\GetSeanceFormOptions;
use App\Domain\Attendance\Queries\GetSeancesList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Attendance\SavePresencesRequest;
use App\Http\Requests\Backoffice\Attendance\StoreSeanceRequest;
use App\Http\Requests\Backoffice\Attendance\UpdateSeanceRequest;
use App\Models\Group;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Attendance (Présences) — séances list + modal add/edit, and the per-séance
 * fiche de présence (Show) where the roll call is saved. Center + academic
 * year are always inherited from the séance's group, never form inputs.
 */
final class SeanceController extends Controller
{
    public function index(
        Request $request,
        GetSeancesList $getSeancesList,
        GetSeanceFormOptions $formOptions,
    ): Response {
        $this->authorize('viewAny', Seance::class);

        $filters = [
            'search' => (string) $request->string('search'),
            'groupFilter' => (string) $request->string('groupFilter'),
            'statutFilter' => (string) $request->string('statutFilter'),
            'dateFrom' => (string) $request->string('dateFrom'),
            'dateTo' => (string) $request->string('dateTo'),
        ];

        if (! in_array($filters['statutFilter'], Seance::STATUTS, true)) {
            $filters['statutFilter'] = '';
        }

        $perPage = (int) $request->integer('perPage', GetSeancesList::DEFAULT_PER_PAGE);
        $user = $request->user();

        return Inertia::render('Backoffice/Seances/Index', [
            'seances' => $getSeancesList($user, $filters, $perPage),
            'filters' => $filters + [
                'perPage' => in_array($perPage, GetSeancesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetSeancesList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetSeancesList::PER_PAGE_OPTIONS,
            'groupOptions' => $formOptions->groups($user),
            'enseignants' => $formOptions->enseignants(),
            'statuts' => Seance::STATUTS,
            'permissions' => [
                'create' => $user->can('create', Seance::class),
                'update' => $user->can('attendance.update'),
                'delete' => $user->can('attendance.delete'),
                'mark' => $user->can('attendance.mark'),
            ],
        ]);
    }

    public function show(
        Request $request,
        Seance $seance,
        GetSeanceDetails $getSeanceDetails,
        GetSeanceFormOptions $formOptions,
    ): Response {
        $this->authorize('view', $seance);

        $user = $request->user();

        // The Date / Employé selectors above the roll call drive the séance
        // picker; they default to the open séance's own date and teacher.
        $filterDate = (string) $request->string('date');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate) !== 1) {
            $filterDate = $seance->date_seance->toDateString();
        }

        $filterEnseignant = $request->has('enseignant')
            ? ($request->integer('enseignant') ?: null)
            : $seance->enseignant_id;

        return Inertia::render('Backoffice/Seances/Show', [
            'seance' => $getSeanceDetails($seance),
            'presenceStatuts' => Presence::STATUTS,
            'canMark' => $user->can('mark', $seance),
            'filters' => [
                'date' => $filterDate,
                'enseignant' => $filterEnseignant,
            ],
            'enseignantOptions' => $formOptions->enseignants(),
            'seanceOptions' => $formOptions->seancesFor($user, $filterDate, $filterEnseignant),
        ]);
    }

    public function store(StoreSeanceRequest $request): RedirectResponse
    {
        $this->authorize('create', Seance::class);

        $data = $request->validated();
        $group = Group::findOrFail((int) $data['group_id']);

        // The group defines where (center) and when (academic year) the
        // séance belongs; its teacher is the default when none is picked.
        Seance::create([
            'group_id' => $group->id,
            'date_seance' => $data['date_seance'],
            'heure_debut' => $data['heure_debut'] ?? null,
            'heure_fin' => $data['heure_fin'] ?? null,
            'enseignant_id' => $data['enseignant_id'] ?? $group->enseignant_id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $group->annee_scolaire_id,
            'statut' => $data['statut'],
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()?->employee?->id,
        ]);

        return redirect()->route('backoffice.seances.index')
            ->with('success', __('Session created.'));
    }

    public function update(UpdateSeanceRequest $request, Seance $seance): RedirectResponse
    {
        $this->authorize('update', $seance);

        $data = $request->validated();

        $seance->update([
            'date_seance' => $data['date_seance'],
            'heure_debut' => $data['heure_debut'] ?? null,
            'heure_fin' => $data['heure_fin'] ?? null,
            'enseignant_id' => $data['enseignant_id'] ?? null,
            'statut' => $data['statut'],
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->back()
            ->with('success', __('Session updated.'));
    }

    public function destroy(Request $request, Seance $seance): RedirectResponse
    {
        $this->authorize('delete', $seance);

        // Roll-call lines go with the séance (FK cascade) — attendance is
        // operational data, not money; deletion is allowed by permission.
        $seance->delete();

        return redirect()->route('backoffice.seances.index')
            ->with('success', __('Session deleted.'));
    }

    public function savePresences(
        SavePresencesRequest $request,
        Seance $seance,
        EnregistrerPresences $enregistrerPresences,
    ): RedirectResponse {
        $this->authorize('mark', $seance);

        $enregistrerPresences($seance, $request->validated('presences'));

        return redirect()->route('backoffice.seances.show', $seance)
            ->with('success', __('Attendance saved.'));
    }
}
