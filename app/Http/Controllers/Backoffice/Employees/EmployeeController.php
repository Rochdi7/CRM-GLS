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
        $perPage = (int) $request->integer('perPage', GetEmployeesList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Employees/Index', [
            'employees' => $getEmployeesList($request->user(), $search, $categorieFilter, $perPage),
            'filters' => [
                'search' => $search,
                'categorieFilter' => $categorieFilter,
                'perPage' => in_array($perPage, GetEmployeesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetEmployeesList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetEmployeesList::PER_PAGE_OPTIONS,
            'categories' => Employee::CATEGORIES,
            'statuts' => Employee::STATUTS,
            'sexes' => Employee::SEXES,
            'countries' => Countries::all(),
            'defaultCountry' => Countries::DEFAULT,
            'etablissements' => Etablissement::query()->orderBy('nom_centre')->get(['id', 'nom_centre']),
            'centerLocked' => ! $context->isAllCenters(),
            'contextCenterId' => $context->etablissementId(),
            'contextCenterName' => $context->etablissement()?->nom_centre,
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
        // see HandleInertiaRequests).
        $employee = Employee::create([
            ...$payload,
            'reference' => ReferenceGenerator::make('EMP', 'employees'),
        ]);

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
     * `etablissement_id` is never trusted from client input when the top-bar
     * context is locked to a specific center — it is forced to
     * CurrentContext::etablissementId() server-side either way (on create,
     * matching EmployeesIndex::create()'s auto-scoping; on update, so a
     * locked admin can't reassign a record to another center by tampering
     * with the request).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, Request $request, ?Employee $employee = null): array
    {
        $context = app(CurrentContext::class);
        $phonePays = $data['phone_pays'] ?? Countries::DEFAULT;

        $etablissementId = $context->isAllCenters()
            ? ($data['etablissement_id'] ?? null)
            : $context->etablissementId();

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
            'etablissement_id' => $etablissementId,
        ];
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
