<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Queries\GetCaisseDetails;
use App\Domain\Finance\Queries\GetCaisseJournal;
use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Gestion de la caisse" — ONE Inertia page hosting Ma caisse + Validation
 * de transfert as client-side React tabs (Journal des transactions and
 * Comptes de caisse were dropped as visible tabs — GetCaissesList/
 * Caisse::STATUTS/the etablissement-scoped "all" journal are no longer
 * read here; GetCaisseJournal's 'all' scope and GetCaissesList stay used
 * elsewhere, e.g. CaisseController::show()). Access = ANY of the two view
 * permissions; each tab's own data/actions are still gated server-side by
 * their own permission.
 *
 * create/store/edit/update/destroy remain intentionally unrouted — a caisse
 * is auto-created by CaisseProvisioner (EmployeeObserver) and never
 * created/edited/deleted by hand anywhere in the live app.
 */
final class CaisseController extends Controller
{
    public function index(
        Request $request,
        GetCaisseJournal $getCaisseJournal,
        GetCaisseTransfersList $getCaisseTransfersList,
    ): Response {
        $user = $request->user();
        $canViewCaisses = $user->can('cash-registers.view');
        $canViewTransfers = $user->can('cash-transfers.view');
        abort_unless($canViewCaisses || $canViewTransfers, 403);

        // Every tab switch is a real Inertia visit (?tab=…, the React page's
        // switchTab), so only the ACTIVE tab's heavy dataset is computed per
        // request.
        $tab = (string) $request->query('tab', $canViewCaisses ? 'ma-caisse' : 'transferts');
        $validTabs = [
            ...($canViewCaisses ? ['ma-caisse'] : []),
            ...($canViewTransfers ? ['transferts'] : []),
        ];
        if (! in_array($tab, $validTabs, true)) {
            $tab = $canViewCaisses ? 'ma-caisse' : 'transferts';
        }

        $search = (string) $request->string('search');
        $statutFilter = (string) $request->string('statutFilter');
        $typeFilter = (string) $request->string('typeFilter');

        // The transfer modal's fixed source: the acting employee's OWN till
        // (even super-admins transfer from their own till — the source is
        // never chosen client-side, matching CaisseTransferController::store).
        $myCaisse = $user->employee?->caisses()->first();

        return Inertia::render('Backoffice/Caisses/Index', [
            'myCaisse' => $myCaisse !== null ? [
                'id' => $myCaisse->id,
                'nom' => $myCaisse->nom,
                'solde' => number_format((float) $myCaisse->solde, 2, '.', ''),
            ] : null,
            'canViewCaisses' => $canViewCaisses,
            'canViewTransfers' => $canViewTransfers,
            'journalMine' => $canViewCaisses && $tab === 'ma-caisse'
                ? $getCaisseJournal($user, 'mine', '', '', '', 1)
                : null,
            'transfers' => $canViewTransfers && $tab === 'transferts'
                ? $getCaisseTransfersList($user, $search, $statutFilter, $typeFilter)
                : null,
            'transferStatutCounts' => $canViewTransfers && $tab === 'transferts'
                ? $getCaisseTransfersList->statutCounts($user)
                : [],
            'transferCaisses' => $canViewTransfers
                ? $getCaisseTransfersList->caisseOptions($user)
                : [],
            'transferStatuts' => CaisseTransfer::STATUTS,
            'currentEmployeeId' => $user->employee?->id,
            'transferFilters' => [
                'search' => $search,
                'statutFilter' => $statutFilter,
                'typeFilter' => $typeFilter,
            ],
        ]);
    }

    /**
     * AJAX refresh for the journal tab (filter/date/pagination changes)
     * without reloading the whole tabbed page — mirrors
     * CaisseJournal::updated*()'s live re-render.
     */
    public function journal(Request $request, string $scope, GetCaisseJournal $getCaisseJournal): JsonResponse
    {
        abort_unless($request->user()->can('cash-registers.view'), 403);

        $journal = $getCaisseJournal(
            $request->user(),
            $scope,
            (string) $request->string('typeFilter'),
            (string) $request->string('dateFrom'),
            (string) $request->string('dateTo'),
            (int) $request->integer('page', 1),
        );

        return response()->json($journal);
    }

    public function show(Caisse $caisse, GetCaisseDetails $getCaisseDetails): Response
    {
        $this->authorize('view', $caisse);

        return Inertia::render('Backoffice/Caisses/Show', [
            'caisse' => $getCaisseDetails($caisse),
        ]);
    }
}
