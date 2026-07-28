<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Contracts\View\View;

/**
 * Inscription detail page (read-only). The list + add/edit CRUD is the
 * Livewire InscriptionsIndex component; this controller serves the show page.
 */
final class InscriptionController extends Controller
{
    public function show(Inscription $inscription): View
    {
        $this->authorize('view', $inscription);

        return view('backoffice.inscriptions.show', [
            'inscription' => $inscription->load([
                'student', 'group.enseignant', 'anneeScolaire', 'createdBy',
                'fees.encaissements',
            ]),
        ]);
    }
}
