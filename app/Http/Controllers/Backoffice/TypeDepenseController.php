<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\TypesDepenses\StoreTypeDepenseRequest;
use App\Http\Requests\Backoffice\TypesDepenses\UpdateTypeDepenseRequest;
use App\Models\TypeDepense;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class TypeDepenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(TypeDepense::class, 'types_depense');
    }

    public function index(): View
    {
        return view('backoffice.types-depenses.index', [
            'types' => TypeDepense::query()->orderBy('nom')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('backoffice.types-depenses.create');
    }

    public function store(StoreTypeDepenseRequest $request): RedirectResponse
    {
        // The admin form only ever creates custom types (schema §12).
        TypeDepense::create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        return redirect()->route('backoffice.types-depenses.index')
            ->with('status', __('Type de dépense créé.'));
    }

    public function edit(TypeDepense $types_depense): View
    {
        abort_if($types_depense->is_system, 403, __('Les types système ne sont pas modifiables.'));

        return view('backoffice.types-depenses.edit', ['type' => $types_depense]);
    }

    public function update(UpdateTypeDepenseRequest $request, TypeDepense $types_depense): RedirectResponse
    {
        // System types are protected — payroll/transfer code depends on them.
        abort_if($types_depense->is_system, 403, __('Les types système ne sont pas modifiables.'));

        $types_depense->update($request->validated());

        return redirect()->route('backoffice.types-depenses.index')
            ->with('status', __('Type de dépense mis à jour.'));
    }

    public function destroy(TypeDepense $types_depense): RedirectResponse
    {
        abort_if($types_depense->is_system, 403, __('Les types système ne sont pas supprimables.'));

        // Restrict FK on depenses blocks deleting a type in use.
        $types_depense->delete();

        return redirect()->route('backoffice.types-depenses.index')
            ->with('status', __('Type de dépense supprimé.'));
    }
}
