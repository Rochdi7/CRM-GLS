<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Employees;

use App\Domain\Employees\Queries\GetEmployeesList;
use App\Domain\Settings\Queries\GetAccessibleCenterOptions;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    public function index(
        Request $request,
        GetEmployeesList $getEmployeesList,
        GetAccessibleCenterOptions $accessibleCenters,
    ): Response {
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
            // Centre reach governs this dropdown — never the full table
            // (audit 07/09/2026, C-2). This same list feeds the « Centres
            // affectés » MultiSelect, so an unfiltered query let a manager
            // confined to {Marrakech, Rabat} tick a centre they cannot reach
            // — the input that made the silent substitution in
            // syncEtablissementIds() reachable (C-3). Same funnel as
            // Settings/Salles/Frais, so it stays in sync with §16.
            'etablissements' => Etablissement::query()
                ->whereIn('id', $accessibleCenters->allowedIds($request->user()))
                ->orderBy('nom_centre')
                ->get(['id', 'nom_centre']),
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

        $employee = DB::transaction(function () use ($payload, $centerIds, $data, $request): Employee {
            $employee = new Employee([
                ...$payload,
                // Primary center = the first assigned one (see Employee::syncEtablissements).
                'etablissement_id' => $centerIds[0],
                'reference' => ReferenceGenerator::make('EMP', 'employees'),
            ]);
            $employee->requestedUsername = $data['username'] ?? null;
            // Employee + login + till + role are one unit: a failure in the
            // observer must not leave a role-less employee without a login.
            $employee->save();

            $employee->syncEtablissements($centerIds);
            $this->storePhoto($employee, $request);

            return $employee;
        });

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
        $ancienCentrePrincipal = (int) $employee->etablissement_id;

        DB::transaction(function () use ($employee, $payload, $data, $request): void {
            $employee->update($payload);
            // Pass the record so centres it already holds outside the actor's
            // reach are preserved instead of dropped (C-3).
            $employee->syncEtablissements($this->resolveCenterIds($data, $employee));
            $this->storePhoto($employee, $request);

            // The login's e-mail follows the staff record (they were two
            // independent columns — audit CRUD-F17). Kept only when the
            // employee has a real address; a placeholder login e-mail stays.
            if ($payload['email'] !== null && $employee->user !== null && $employee->user->email !== $payload['email']) {
                $employee->user->update(['email' => $payload['email']]);
            }
        });

        $redirect = redirect()->route('backoffice.employees.index')
            ->with('success', __('Employee updated.'));

        // A profile edit NEVER moves a caisse (production-safety rule,
        // 01/09/2026): when the primary centre changed and the employee's
        // till is still attached to another centre, say so out loud instead
        // of silently relocating money. Re-homing an (empty, movement-free)
        // till stays an explicit super-admin action on Comptes de caisse.
        $employee->refresh();
        $till = $employee->till()->with('etablissement')->first();

        if ((int) $employee->etablissement_id !== $ancienCentrePrincipal
            && $till !== null
            && (int) $till->etablissement_id !== (int) $employee->etablissement_id) {
            $redirect->with('warning', __("The employee's till stays attached to :centre (balance: :solde DH). A profile edit never moves money.", [
                'centre' => $till->etablissement?->nom_centre ?? __('its current centre'),
                'solde' => number_format((float) $till->solde, 2, ',', ' '),
            ]));
        }

        return $redirect;
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        if ($this->employeeHasActivity($employee)) {
            throw ValidationException::withMessages([
                'delete' => __('This employee has activity history and cannot be deleted. Deactivate instead.'),
            ]);
        }

        // Nothing else may point at this employee any more, so the login
        // and the empty tills go with it — an orphan login with a live role
        // was the audit's CRUD-F3 hole.
        DB::transaction(function () use ($employee): void {
            $employee->caisses()->delete();

            if ($employee->user !== null) {
                $employee->user->forceFill([
                    'is_active' => false,
                    'remember_token' => Str::random(60),
                ])->save();
                $employee->user->syncRoles([]);
            }

            $employee->delete();
        });

        return redirect()->route('backoffice.employees.index')
            ->with('success', __('Employee deleted.'));
    }

    /**
     * Every RESTRICT/keep-worthy reference to this employee — the four
     * relations the old guard counted plus cheques, transfers, teaching
     * assignments, import batches and any till that ever held money.
     */
    private function employeeHasActivity(Employee $employee): bool
    {
        $employee->loadCount(['groupes', 'encaissements', 'depenses', 'remboursements']);

        if ($employee->groupes_count || $employee->encaissements_count
            || $employee->depenses_count || $employee->remboursements_count) {
            return true;
        }

        $id = $employee->id;
        $caisseIds = $employee->caisses()->select('id');

        return DB::table('cheques')->where('agent_id', $id)->exists()
            || DB::table('caisse_transfers')->where('requested_by', $id)->orWhere('validated_by', $id)->exists()
            || DB::table('group_enseignants')->where('enseignant_id', $id)->exists()
            || DB::table('import_batches')->where('created_by', $id)->exists()
            || $employee->caisses()->where('solde', '!=', 0)->exists()
            || DB::table('encaissements')->whereIn('caisse_id', $caisseIds)->exists()
            || DB::table('depenses')->whereIn('caisse_id', $caisseIds)->exists()
            || DB::table('remboursements')->whereIn('caisse_id', $caisseIds)->exists()
            || DB::table('caisse_transfers')->whereIn('caisse_source_id', $caisseIds)->orWhereIn('caisse_destination_id', $caisseIds)->exists();
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
     * The centers to assign. The "Centres affectés" multi-select is shown in
     * every context (an employee may work in several centers, and that
     * assignment is what grants their access + builds their own centre
     * switcher), so the submitted list is authoritative — the active top-bar
     * centre no longer overrides it.
     *
     * It is still never trusted blindly: the list is narrowed to the centres
     * the signed-in user may actually assign, so a centre-confined admin can
     * neither assign an employee to a centre it cannot see nor move one out
     * of its own reach. When NOTHING submitted is within reach: an UPDATE is
     * REFUSED rather than silently rewritten (C-3), while a CREATE keeps the
     * long-standing narrowing (no prior assignment exists to destroy). On an
     * edit, `$employee`'s existing out-of-reach centres are preserved.
     *
     * Guaranteed non-empty: the Form Requests require at least one id, and
     * the locked branch always yields the context center.
     *
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function resolveCenterIds(array $data, ?Employee $employee = null): array
    {
        $context = app(CurrentContext::class);

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

                // Nothing submitted is within reach. What happens next
                // depends on whether a real assignment is at stake (audit
                // 07/09/2026, C-3):
                //
                //  - UPDATE ($employee !== null) — REFUSE. The old code read
                //    `$ids = $narrowed === [] ? $allowed : $narrowed`, so the
                //    submission was silently replaced by ALL of the actor's
                //    own centres and syncEtablissements() then OVERWROTE the
                //    employee's real assignment. Since accessibleCenterIds()
                //    reads that pivot, the victim's own centre reach changed
                //    — with a success flash and no error. That is the hole.
                //
                //  - CREATE ($employee === null) — narrow to the actor's own
                //    centres, the deliberate long-standing behaviour ("you
                //    hire into your own centre"), asserted by
                //    EmployeesInertiaCrudTest::test_a_user_cannot_assign_an
                //    _employee_to_a_center_it_does_not_hold. There is no
                //    prior assignment to destroy here, so nothing is lost.
                if ($narrowed === [] && $employee !== null) {
                    throw ValidationException::withMessages([
                        'etablissement_ids' => __('You cannot assign an employee to centers you do not have access to.'),
                    ]);
                }

                if ($narrowed === []) {
                    $narrowed = $allowed;
                }

                // On an EDIT, centres the employee already holds outside the
                // actor's reach are preserved rather than dropped: a manager
                // of {Marrakech} editing a phone number must not silently
                // un-assign that employee from Rabat. Same intent as
                // FraisController::syncPayload(). On a CREATE there is no
                // record yet, so $keep is empty and this is a no-op.
                $keep = $employee !== null
                    ? array_values(array_diff(
                        $employee->etablissements()->pluck('etablissements.id')->map(intval(...))->all(),
                        $allowed,
                    ))
                    : [];

                $ids = array_values(array_unique([...$narrowed, ...$keep]));
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
