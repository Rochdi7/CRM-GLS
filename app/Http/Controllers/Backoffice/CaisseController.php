<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Queries\GetCaisseDetails;
use App\Domain\Finance\Queries\GetCaisseJournal;
use App\Domain\Finance\Queries\GetCaissesList;
use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Services\Context\CurrentContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Gestion de la caisse" — ONE Inertia page (Phase 10,
 * docs/phase-10-finance-audit.md §2.1/§2.2/§3) hosting Ma caisse + journal
 * des transactions + transferts + comptes de caisse as client-side React
 * tabs, replacing the former Livewire-tab Blade shell
 * (CaisseManagementController, now unreferenced — see
 * docs/legacy-frontend-removal-plan.md §0g). Access = ANY of the two view
 * permissions; each tab's own data/actions are still gated server-side by
 * their own permission.
 *
 * create/store/edit/update/destroy remain intentionally unrouted — a caisse
 * is auto-created by CaisseProvisioner (EmployeeObserver) and never
 * created/edited/deleted by hand anywhere in the live app
 * (docs/phase-10-finance-mapping.md: no manual CRUD added, matching
 * CaissesIndex having zero write actions).
 */
final class CaisseController extends Controller
{
    public function index(
        Request $request,
        GetCaissesList $getCaissesList,
        GetCaisseJournal $getCaisseJournal,
        GetCaisseTransfersList $getCaisseTransfersList,
    ): Response {
        $user = $request->user();
        $canViewCaisses = $user->can('cash-registers.view');
        $canViewTransfers = $user->can('cash-transfers.view');
        abort_unless($canViewCaisses || $canViewTransfers, 403);

        $activeCenterId = app(CurrentContext::class)->etablissementId();

        // Every tab switch is a real Inertia visit (?tab=…, the React page's
        // switchTab), so only the ACTIVE tab's heavy dataset is computed per
        // request — the four panels each null-guard their prop client-side.
        // Before this gating the page ran ~40 queries per load/switch (both
        // journal scopes + tills + transfers at once); now ~15
        // (Phase 12, docs/phase12-performance-report.md).
        $tab = (string) $request->query('tab', $canViewCaisses ? 'ma-caisse' : 'transferts');
        $validTabs = [
            ...($canViewCaisses ? ['ma-caisse', 'journal', 'comptes'] : []),
            ...($canViewTransfers ? ['transferts'] : []),
        ];
        if (! in_array($tab, $validTabs, true)) {
            $tab = $canViewCaisses ? 'ma-caisse' : 'transferts';
        }

        return Inertia::render('Backoffice/Caisses/Index', [
            'canViewCaisses' => $canViewCaisses,
            'canViewTransfers' => $canViewTransfers,
            'journalMine' => $canViewCaisses && $tab === 'ma-caisse'
                ? $getCaisseJournal($user, 'mine', '', '', '', 1)
                : null,
            'journalAll' => $canViewCaisses && $tab === 'journal'
                ? $getCaisseJournal($user, 'all', '', '', '', 1)
                : null,
            'caisses' => $canViewCaisses && $tab === 'comptes'
                ? $getCaissesList($user, $activeCenterId)
                : null,
            // Small option lists stay unconditional — the page reads them at
            // component top level (not inside a tab panel).
            'etablissements' => $canViewCaisses
                ? $getCaissesList->etablissementOptions($user)
                : [],
            'statuts' => Caisse::STATUTS,
            'transfers' => $canViewTransfers && $tab === 'transferts'
                ? $getCaisseTransfersList($user)
                : null,
            'transferStatutCounts' => $canViewTransfers && $tab === 'transferts'
                ? $getCaisseTransfersList->statutCounts($user)
                : [],
            'transferCaisses' => $canViewTransfers
                ? $getCaisseTransfersList->caisseOptions($user)
                : [],
            'transferStatuts' => CaisseTransfer::STATUTS,
            'currentEmployeeId' => $user->employee?->id,
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
