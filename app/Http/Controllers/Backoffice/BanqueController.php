<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Banques\StoreBanqueRequest;
use App\Http\Requests\Backoffice\Banques\UpdateBanqueRequest;
use App\Models\Banque;
use App\Models\Encaissement;
use App\Models\Cheque;
use Illuminate\Http\RedirectResponse;

/**
 * Banques (bank catalog) CRUD — same pattern as FraisController: catalog
 * lives as a Paramètres tab (?tab=banques), index/create/edit redirect
 * there, store/update/destroy are the real mutation endpoints. Restricted to
 * super-admin by design — the `banks.*` permissions are deliberately absent
 * from every role in PermissionRegistry::matrix(), so only the Gate::before
 * super-admin bypass grants access.
 */
final class BanqueController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Banque::class, 'banque');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function store(StoreBanqueRequest $request): RedirectResponse
    {
        Banque::create($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'banques'])
            ->with('success', __('Banque créée.'));
    }

    public function edit(Banque $banque): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateBanqueRequest $request, Banque $banque): RedirectResponse
    {
        $data = $request->validated();

        // Payments and cheques store the bank NAME (free text): renaming a
        // bank in use would orphan them (audit CRUD-F9).
        if (($data['nom'] ?? $banque->nom) !== $banque->nom && $this->banqueEnUtilisation($banque)) {
            return back()->withErrors(['nom' => __('This bank is used by existing payments or cheques and cannot be renamed.')]);
        }

        $banque->update($data);

        return redirect()->route('backoffice.settings', ['tab' => 'banques'])
            ->with('success', __('Banque mise à jour.'));
    }

    /**
     * Guarded like Frais: a bank still referenced by a payment's free-text
     * `banque` column cannot be removed. `delete` field error (not a flash)
     * so the React confirm-dialog stays open and shows it inline.
     */
    public function destroy(Banque $banque): RedirectResponse
    {
        if ($this->banqueEnUtilisation($banque)) {
            return back()->withErrors(['delete' => __('This bank is used by existing payments and cannot be deleted.')]);
        }

        $banque->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'banques'])
            ->with('success', __('Banque supprimée.'));
    }

    private function banqueEnUtilisation(Banque $banque): bool
    {
        return Encaissement::where('banque', $banque->nom)->exists()
            || Cheque::where('banque', $banque->nom)->exists();
    }
}
