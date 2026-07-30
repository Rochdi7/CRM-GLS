<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Registrations\Queries\GetInscriptionDetails;
use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inscription detail page (read-only). The list + add/edit CRUD is the
 * Livewire InscriptionsIndex component; this controller serves the show page.
 */
final class InscriptionController extends Controller
{
    public function show(Inscription $inscription, GetInscriptionDetails $getInscriptionDetails): Response
    {
        $this->authorize('view', $inscription);

        return Inertia::render('Backoffice/Inscriptions/Show', [
            'inscription' => $getInscriptionDetails($inscription),
        ]);
    }
}
