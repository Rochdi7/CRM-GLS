<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Registrations\Queries\GetInscriptionsCorbeille;
use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onglet « Corbeille » de la page Inscriptions (19/10/2026) : les dossiers
 * supprimés, par qui, quand, et combien par personne. Lecture seule — une
 * ligne de corbeille ne se restaure ni ne s'efface (InscriptionCorbeille
 * est append-only) ; elle est écrite par SupprimerInscription, jamais ici.
 * Même permission que la liste (`registrations.view`) : lire ce qui a été
 * supprimé est lire des inscriptions.
 */
final class InscriptionCorbeilleController extends Controller
{
    public function index(Request $request, GetInscriptionsCorbeille $corbeille): Response
    {
        $this->authorize('viewAny', Inscription::class);

        $user = $request->user();
        $search = (string) $request->string('search');
        $auteurFilter = (string) $request->string('auteurFilter');
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $perPage = (int) $request->integer('perPage', GetInscriptionsCorbeille::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Inscriptions/Corbeille', [
            'rows' => $corbeille($user, $search, $auteurFilter, $dateFrom, $dateTo, $perPage),
            // Total et répartition par auteur : sur TOUT l'ensemble filtré,
            // jamais réadditionnés par la page.
            'total' => $corbeille->total($user, $search, $auteurFilter, $dateFrom, $dateTo),
            'parAuteur' => $corbeille->parAuteur($user, $search, $auteurFilter, $dateFrom, $dateTo),
            'auteurOptions' => fn () => $corbeille->auteurOptions($user),
            'tabCounts' => fn () => ['/backoffice/inscriptions/corbeille' => $corbeille->tabCount($user)],
            'filters' => [
                'search' => $search,
                'auteurFilter' => $auteurFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'perPage' => in_array($perPage, GetInscriptionsCorbeille::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetInscriptionsCorbeille::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetInscriptionsCorbeille::PER_PAGE_OPTIONS,
        ]);
    }
}
