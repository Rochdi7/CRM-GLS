<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Depenses\Concerns;

use App\Models\TypeDepense;

/**
 * The « Paiement prof » type has its OWN modal (Dépenses → Paiements prof)
 * with a different contract from an ordinary dépense:
 *
 * - `group_id` is REQUIRED (a teacher is always paid for a given group),
 *   whereas the Dépenses modal doesn't show the field at all;
 * - `periode_debut` / `periode_fin` state the teaching period the payment
 *   covers and are required for it, forbidden otherwise;
 * - `reference_facture` is a supplier-invoice field — meaningless here.
 *
 * The rules below are shared by Store/UpdateDepenseRequest so the two can
 * never drift; the type is read from the request (a client can send any
 * type, so the branch is decided server-side, not by which modal was open).
 */
trait PaiementProfRules
{
    /**
     * True when the submitted type is the seeded « Paiement prof » type.
     *
     * Public because DepenseController::update() reuses the SAME decision to
     * null the prof-only columns when the type is switched away — one source
     * of truth, so the rules and the payload can never disagree.
     */
    public function isPaiementProf(): bool
    {
        $typeId = $this->input('type_depense_id');

        if ($typeId === null || $typeId === '') {
            return false;
        }

        return TypeDepense::query()
            ->whereKey((int) $typeId)
            ->value('nom') === TypeDepense::SYSTEM_PAIEMENT_PROF;
    }

    /**
     * Fields whose rules differ between the two modals.
     *
     * @return array<string, mixed>
     */
    protected function typeDependentRules(): array
    {
        $isProf = $this->isPaiementProf();

        return [
            // Required for a paiement prof, hidden (and refused) on an
            // ordinary dépense so the Dépenses modal can drop the field.
            'group_id' => $isProf
                ? ['required', 'exists:groups,id']
                : ['prohibited'],
            'periode_debut' => $isProf ? ['required', 'date'] : ['prohibited'],
            'periode_fin' => $isProf
                ? ['required', 'date', 'after_or_equal:periode_debut']
                : ['prohibited'],
            // Supplier invoice reference — dépenses only.
            'reference_facture' => $isProf
                ? ['prohibited']
                : ['nullable', 'string', 'max:100'],
            // Required on BOTH modals (26/08/2026): a money outflow with no
            // stated reason is unauditable.
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
