<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import\Concerns;

use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The batch's Centre + Année scolaire come from the ACTIVE working context
 * (the top-bar switcher) — the same §11 rule every CRUD create follows —
 * never from client input. The one exception: a global user working in
 * « Tous les centres » has no active centre, so the Upload form still shows
 * a Centre select and the posted id is honored after the usual access check
 * (its existence is validated by the Form Request).
 *
 * Before this, the Upload screens carried their own free Année dropdown:
 * a batch could silently land in a different year than the one every list
 * page displays, which read as "my imported data disappeared".
 */
trait ResolvesImportScope
{
    /**
     * @return array{int, int} [etablissementId, anneeScolaireId]
     */
    private function importScope(Request $request, CenterAccessService $centerAccess): array
    {
        $context = app(CurrentContext::class);

        $anneeScolaireId = $context->anneeScolaireId();

        if ($anneeScolaireId === null) {
            throw ValidationException::withMessages([
                'annee_scolaire_id' => __('No academic year is available — create one in Settings first.'),
            ]);
        }

        $etablissementId = $context->etablissementId();

        if ($etablissementId === null) {
            $etablissementId = (int) $request->input('etablissement_id');

            if ($etablissementId <= 0) {
                throw ValidationException::withMessages([
                    'etablissement_id' => __('Select a centre before importing.'),
                ]);
            }
        }

        abort_unless($centerAccess->canAccessCenter($request->user(), $etablissementId), 403);

        return [$etablissementId, $anneeScolaireId];
    }
}
