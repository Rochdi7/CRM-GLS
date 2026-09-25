<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Students\Actions\AnnulerTransfertEtudiant;
use App\Domain\Students\Actions\DemanderTransfertEtudiant;
use App\Domain\Students\Actions\RefuserTransfertEtudiant;
use App\Domain\Students\Actions\ValiderTransfertEtudiant;
use App\Domain\Students\Queries\GetGroupesCiblesTransfert;
use App\Domain\Students\Queries\GetStudentTransfersList;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Backoffice\Concerns\RedirectsPreservingFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\StudentTransfers\DecideStudentTransferRequest;
use App\Http\Requests\Backoffice\StudentTransfers\StoreStudentTransferRequest;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\StudentTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Transferts d'étudiants entre centres (25/09/2026) — flux en deux temps :
 * store() = DEMANDE depuis la page Étudiants (rien ne bouge) ·
 * validateAction() / refuse() = DÉCISION du super-admin · cancel() = retrait
 * d'une demande en attente par son demandeur. Aucun destroy() : la trace
 * reste. Toute la logique est dans Domain\Students\Actions.
 */
final class StudentTransferController extends Controller
{
    use AssertsContextScope, RedirectsPreservingFilters;

    public function index(Request $request, GetStudentTransfersList $getList): Response
    {
        $this->authorize('viewAny', StudentTransfer::class);

        $search = (string) $request->string('search');
        $statutFilter = (string) $request->string('statutFilter');
        $perPage = (int) $request->integer('perPage', GetStudentTransfersList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/StudentTransfers/Index', [
            'transfers' => $getList($request->user(), $search, $statutFilter, $perPage),
            'filters' => [
                'search' => $search,
                'statutFilter' => $statutFilter,
                'perPage' => in_array($perPage, GetStudentTransfersList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetStudentTransfersList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetStudentTransfersList::PER_PAGE_OPTIONS,
            'statuts' => StudentTransfer::STATUTS,
            'tabCounts' => fn () => $getList->tabCounts($request->user()),
            'permissions' => [
                'validate' => $request->user()->can('student-transfers.validate'),
            ],
        ]);
    }

    /**
     * Groupes ouverts du centre CIBLE — alimente le second menu du modal de
     * demande. Gardé par `student-transfers.create` et non par la portée
     * de centre : le demandeur désigne un groupe d'un centre qu'il n'ouvre
     * pas par ailleurs (GetGroupesCiblesTransfert).
     */
    public function groupesCibles(Request $request, Etablissement $etablissement, GetGroupesCiblesTransfert $getGroupes): JsonResponse
    {
        $this->authorize('create', StudentTransfer::class);

        return response()->json($getGroupes($etablissement->id)->all());
    }

    public function store(StoreStudentTransferRequest $request, DemanderTransfertEtudiant $action): RedirectResponse
    {
        $this->authorize('create', StudentTransfer::class);

        $data = $request->validated();
        $student = Student::query()->findOrFail((int) $data['student_id']);

        // La fiche qui part doit être dans la portée ET le centre actif du
        // demandeur : on ne transfère pas un étudiant d'un centre qu'on ne
        // voit pas (§11 « writes are guarded »).
        $this->assertStudentInContext($request, $student);

        $requester = $request->user()->employee;

        if ($requester === null) {
            throw ValidationException::withMessages([
                'student_id' => __('Your account is not linked to any employee record.'),
            ]);
        }

        $action->handle(
            $student,
            (int) $data['etablissement_cible_id'],
            (int) $data['group_cible_id'],
            (string) $data['motif'],
            $requester,
        );

        return $this->backToListPreservingFilters($request, 'backoffice.students.index')
            ->with('success', __('Student transfer requested - awaiting backoffice validation.'));
    }

    public function validateAction(Request $request, StudentTransfer $student_transfer, ValiderTransfertEtudiant $action): RedirectResponse
    {
        $this->authorize('validate', $student_transfer);

        try {
            $transfert = $action->handle($student_transfer, $request->user());
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? __('This transfer cannot be validated.');

            throw ValidationException::withMessages(['validate' => $message]);
        }

        $transfert->loadMissing(['nouveauStudent', 'etablissementCible']);

        return $this->backToListPreservingFilters($request, 'backoffice.student-transfers.index')
            ->with('success', __('Student transfer validated: :nom is now enrolled at :centre (:reference).', [
                'nom' => $transfert->nouveauStudent?->nomComplet() ?? '—',
                'centre' => $transfert->etablissementCible?->nom_centre ?? '—',
                'reference' => $transfert->nouveauStudent?->reference ?? '—',
            ]));
    }

    public function refuse(DecideStudentTransferRequest $request, StudentTransfer $student_transfer, RefuserTransfertEtudiant $action): RedirectResponse
    {
        $this->authorize('refuse', $student_transfer);

        $action->handle($student_transfer, (string) ($request->validated()['motif_decision'] ?? ''), $request->user());

        return $this->backToListPreservingFilters($request, 'backoffice.student-transfers.index')
            ->with('success', __('Student transfer refused.'));
    }

    public function cancel(DecideStudentTransferRequest $request, StudentTransfer $student_transfer, AnnulerTransfertEtudiant $action): RedirectResponse
    {
        $this->authorize('cancel', $student_transfer);

        $action->handle($student_transfer, $request->user(), $request->validated()['motif_decision'] ?? null);

        return $this->backToListPreservingFilters($request, 'backoffice.student-transfers.index')
            ->with('success', __('Student transfer request cancelled.'));
    }
}
