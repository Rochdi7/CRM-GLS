<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\InscriptionFees\StoreInscriptionFeeRequest;
use App\Http\Requests\Backoffice\InscriptionFees\UpdateInscriptionFeeRequest;
use App\Models\InscriptionFee;
use Illuminate\Http\RedirectResponse;

/**
 * Fee lines are managed from the parent inscription's page — no standalone
 * index/show screens (store/update/destroy only).
 */
final class InscriptionFeeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(InscriptionFee::class, 'inscription_fee');
    }

    public function store(StoreInscriptionFeeRequest $request): RedirectResponse
    {
        $fee = InscriptionFee::create($request->validated());

        return redirect()->route('backoffice.inscriptions.show', $fee->inscription_id)
            ->with('status', __('Frais ajouté.'));
    }

    public function update(UpdateInscriptionFeeRequest $request, InscriptionFee $inscription_fee): RedirectResponse
    {
        $inscription_fee->update($request->validated());

        return redirect()->route('backoffice.inscriptions.show', $inscription_fee->inscription_id)
            ->with('status', __('Frais mis à jour.'));
    }

    public function destroy(InscriptionFee $inscription_fee): RedirectResponse
    {
        // Restrict FK on encaissements blocks deleting a fee with payments.
        $inscriptionId = $inscription_fee->inscription_id;
        $inscription_fee->delete();

        return redirect()->route('backoffice.inscriptions.show', $inscriptionId)
            ->with('status', __('Frais supprimé.'));
    }
}
