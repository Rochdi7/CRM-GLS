<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Employees\StoreEmployeeRequest;
use App\Http\Requests\Backoffice\Employees\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Employee::class, 'employee');
    }

    public function index(): View
    {
        return view('backoffice.employees.index', [
            'employees' => Employee::query()->with('etablissement')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('backoffice.employees.create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        // EmployeeObserver auto-creates the login (username + one-time
        // password, flashed to session) — structure doc §5.
        Employee::create([
            ...$request->validated(),
            'reference' => ReferenceGenerator::make('EMP', 'employees'),
        ]);

        return redirect()->route('backoffice.employees.index')
            ->with('status', __('Employé créé. Ses identifiants de connexion ont été générés.'));
    }

    public function show(Employee $employee): View
    {
        return view('backoffice.employees.show', [
            'employee' => $employee->load(['etablissement', 'user', 'groupes']),
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('backoffice.employees.edit', ['employee' => $employee]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('backoffice.employees.index')
            ->with('status', __('Employé mis à jour.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        // Restrict FKs (agent_id on money tables) block deleting an employee
        // with financial history — deactivate (statut Inactif) instead.
        $employee->delete();

        return redirect()->route('backoffice.employees.index')
            ->with('status', __('Employé supprimé.'));
    }
}
