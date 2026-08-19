<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Employees;

use App\Domain\Employees\Queries\GetEmployeesList;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Employees\StoreEmployeeRequest;
use App\Http\Requests\Backoffice\Employees\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Phone\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Real HTTP endpoints mirroring App\Livewire\Backoffice\Employees\EmployeesIndex
 * one-for-one (docs/inertia-react-migration-plan.md Phase 7) for the new
 * React list+modal page. The Livewire component and its view are left
 * completely untouched as unreferenced fallback code — only this route's
 * controller changed, per the migration's retained-legacy pattern.
 *
 * Behavior-tightening vs. the Livewire version (flag for review): update()
 * and destroy() now authorize via EmployeePolicy (center-scoped — an admin
 * confined to center A can no longer edit/delete an employee of center B),
 * whereas EmployeesIndex only ever checks the flat `employees.update`/
 * `employees.delete` permission with no per-record center check. This is a
 * deliberate safety improvement for these brand-new routes, not a preserved
 * legacy behavior — see the class docblock in EmployeesIndex for context.
 */
final class EmployeeController extends Controller
{
    public function index(Request $request, GetEmployeesList $getEmployeesList): Response
    {
        $this->authorize('viewAny', Employee::class);

        $context = app(CurrentContext::class);

        $search = (string) $request->string('search');
        $categorieFilter = (string) $request->string('categorieFilter');
        $statutFilter = (string) $request->string('statutFilter');
        $etablissementFilter = (string) $request->string('etablissementFilter');
        $perPage = (int) $request->integer('perPage', GetEmployeesList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Employees/Index', [
            'employees' => $getEmployeesList($request->user(), $search, $categorieFilter, $statutFilter, $etablissementFilter, $perPage),
            'filters' => [
                'search' => $search,
                'categorieFilter' => $categorieFilter,
                'statutFilter' => $statutFilter,
                'etablissementFilter' => $etablissementFilter,
                'perPage' => in_array($perPage, GetEmployeesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetEmployeesList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetEmployeesList::PER_PAGE_OPTIONS,
            'categories' => Employee::CATEGORIES,
            'statuts' => Employee::STATUTS,
            'sexes' => Employee::SEXES,
            'defaultCountry' => Countries::DEFAULT,
            'etablissements' => Etablissement::query()->orderBy('nom_centre')->get(['id', 'nom_centre']),
            'centerLocked' => ! $context->isAllCenters(),
            'contextCenterId' => $context->etablissementId(),
            'contextCenterName' => $context->etablissement()?->nom_centre,
            'canManageUsers' => $request->user()->can('users.assign-roles'),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $data = $request->validated();
        $payload = $this->buildPayload($data, $request);

        // Create → EmployeeObserver auto-creates the User and flashes the
        // one-time username + password to the session (surfaced to the
        // React page via the "flash.newEmployeeCredentials" shared prop —
        // see HandleInertiaRequests). Built unsaved first so the optional
        // requested username (read by EmployeeCredentialService, not a real
        // column) is present on the instance the "created" event receives.
        $centerIds = $this->resolveCenterIds($data);

        $employee = new Employee([
            ...$payload,
            // Primary center = the first assigned one (see Employee::syncEtablissements).
            'etablissement_id' => $centerIds[0],
            'reference' => ReferenceGenerator::make('EMP', 'employees'),
        ]);
        $employee->requestedUsername = $data['username'] ?? null;
        $employee->save();

        $employee->syncEtablissements($centerIds);
        $this->storePhoto($employee, $request);

        return redirect()->route('backoffice.employees.index')
            ->with('success', __('Employee created. Its login credentials have been generated.'))
            ->with('new_employee_username', session('new_employee_username'))
            ->with('new_employee_password', session('new_employee_password'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validated();
        $payload = $this->buildPayload($data, $request, $employee);

        $employee->update($payload);
        $employee->syncEtablissements($this->resolveCenterIds($data));
        $this->storePhoto($employee, $request);

        return redirect()->route('backoffice.employees.index')
            ->with('success', __('Employee updated.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->loadCount(['groupes', 'encaissements', 'depenses', 'remboursements']);

        if ($employee->groupes_count || $employee->encaissements_count
            || $employee->depenses_count || $employee->remboursements_count) {
            throw ValidationException::withMessages([
                'delete' => __('This employee has activity history and cannot be deleted. Deactivate instead.'),
            ]);
        }

        $employee->delete();

        return redirect()->route('backoffice.employees.index')
            ->with('success', __('Employee deleted.'));
    }

    /**
     * Builds the mass-assignment payload shared by store/update: combines
     * the phone country + national parts into the stored "+212…" form and
     * normalizes empty strings to null exactly like EmployeesIndex::save().
     *
     * Center assignment is NOT handled here — it lives in the
     * employee_etablissement pivot and goes through resolveCenterIds() +
     * Employee::syncEtablissements(), which also keeps the primary
     * `etablissement_id` column in sync.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, Request $request, ?Employee $employee = null): array
    {
        $phonePays = $data['phone_pays'] ?? Countries::DEFAULT;

        return [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'sexe' => $data['sexe'],
            'categorie' => $data['categorie'],
            'statut' => $data['statut'],
            'telephone' => Countries::join($phonePays, $data['telephone'] ?? null),
            'whatsapp' => Countries::join($phonePays, $data['whatsapp'] ?? null),
            'email' => $data['email'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'note' => $data['note'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
            'date_embauche' => $data['date_embauche'] ?? null,
            'salaire' => $data['salaire'] ?? null,
        ];
    }

    /**
     * The centers to assign, never trusted blindly from client input: when
     * the top-bar context is locked to a specific center, the employee is
     * forced into exactly that center (a locked admin cannot assign an
     * employee to — or move one into — a center it cannot itself see).
     * Only an "all centers" context may submit a real multi-center list.
     *
     * Guaranteed non-empty: the Form Requests require at least one id, and
     * the locked branch always yields the context center.
     *
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function resolveCenterIds(array $data): array
    {
        $context = app(CurrentContext::class);

        if (! $context->isAllCenters() && $context->etablissementId() !== null) {
            return [$context->etablissementId()];
        }

        /** @var list<int> $ids */
        $ids = array_values(array_filter(
            array_unique(array_map('intval', $data['etablissement_ids'] ?? [])),
            static fn (int $id): bool => $id > 0,
        ));

        // "All centers" also covers a multi-center employee viewing all of
        // THEIRS — narrow the submitted list to what they may actually
        // access, so they can never assign an employee to a center they
        // don't hold themselves.
        //
        // Only applies to users who ARE confined to specific centers: a user
        // with no employee profile has no assignment to narrow against, and
        // intersecting with its empty list would wrongly strip every id.
        $centerAccess = app(CenterAccessService::class);
        $user = request()->user();

        if ($user !== null && ! $centerAccess->hasGlobalAccess($user)) {
            $allowed = $centerAccess->accessibleCenterIds($user);

            if ($allowed !== []) {
                $narrowed = array_values(array_intersect($ids, $allowed));
                $ids = $narrowed === [] ? $allowed : $narrowed;
            }
        }

        // Defensive: an employee is never left unaffected. The Form Requests
        // already require a non-empty list, so this only guards against a
        // caller that bypasses them.
        if ($ids === []) {
            if ($context->etablissementId() !== null) {
                return [$context->etablissementId()];
            }

            throw ValidationException::withMessages([
                'etablissement_ids' => __('Select at least one center.'),
            ]);
        }

        return $ids;
    }

    /**
     * Attach the uploaded picture to the single-file "photo" collection (a
     * new upload replaces the previous one) — same as
     * EmployeesIndex::storePhoto(). No-op when nothing was uploaded.
     */
    private function storePhoto(Employee $employee, Request $request): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        $photo = $request->file('photo');

        $employee->addMedia($photo->getRealPath())
            ->usingFileName('photo-'.$employee->id.'.'.$photo->getClientOriginalExtension())
            ->toMediaCollection('photo');
    }
}
