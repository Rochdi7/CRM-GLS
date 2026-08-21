<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Queries\GetCaisseDetails;
use App\Domain\Finance\Queries\GetCaisseJournal;
use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Domain\Finance\Queries\GetComptesCaisse;
use App\Http\Requests\Backoffice\Caisses\StoreCaisseRequest;
use App\Http\Requests\Backoffice\Caisses\UpdateCaisseRequest;
use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Etablissement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Gestion de la caisse" — ONE Inertia page hosting Ma caisse, Validation
 * de transfert and Comptes de caisse as tabs (each tab switch is a real
 * Inertia visit, so only the active tab's dataset is computed). Access = ANY
 * of the three view permissions; each tab's own data/actions are still gated
 * server-side by their own permission.
 *
 * ⚠ « Comptes de caisse » is super-admin only: `cash-accounts.*` is absent
 * from every role in PermissionRegistry::matrix(), so only the Gate::before
 * bypass reaches it. "Externe" is the ONLY type creatable there — employee
 * tills stay owned by CaisseProvisioner (EmployeeObserver), and TPE / Chèque
 * / Virement are payment methods aggregated live by GetComptesCaisse, not
 * rows to create (see that class and StoreCaisseRequest).
 */
final class CaisseController extends Controller
{
    public function index(
        Request $request,
        GetCaisseJournal $getCaisseJournal,
        GetCaisseTransfersList $getCaisseTransfersList,
        GetComptesCaisse $getComptesCaisse,
    ): Response {
        $user = $request->user();
        $canViewCaisses = $user->can('cash-registers.view');
        $canViewTransfers = $user->can('cash-transfers.view');
        $canViewComptes = $user->can('cash-accounts.view');
        abort_unless($canViewCaisses || $canViewTransfers || $canViewComptes, 403);

        // Every tab switch is a real Inertia visit (?tab=…, the React page's
        // switchTab), so only the ACTIVE tab's heavy dataset is computed per
        // request.
        $validTabs = [
            ...($canViewCaisses ? ['ma-caisse'] : []),
            ...($canViewTransfers ? ['transferts'] : []),
            ...($canViewComptes ? ['comptes'] : []),
        ];
        $tab = (string) $request->query('tab', $validTabs[0] ?? 'ma-caisse');
        if (! in_array($tab, $validTabs, true)) {
            $tab = $validTabs[0] ?? 'ma-caisse';
        }

        $search = (string) $request->string('search');
        $statutFilter = (string) $request->string('statutFilter');
        $typeFilter = (string) $request->string('typeFilter');
        $compteSearch = (string) $request->string('compteSearch');
        $compteTypeFilter = (string) $request->string('compteTypeFilter');

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
            'canViewComptes' => $canViewComptes,
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
            'comptes' => $canViewComptes && $tab === 'comptes'
                ? $getComptesCaisse($compteSearch, $compteTypeFilter, GetComptesCaisse::DEFAULT_PER_PAGE, (int) $request->integer('page', 1))
                : null,
            // The form offers "Externe" only — everything else is either
            // provisioned with its employee or derived from a payment method
            // (StoreCaisseRequest refuses the rest server-side too).
            'compteTypes' => GetComptesCaisse::CREATABLE_TYPES,
            // The FILTER offers every kind the tab can show: employee tills,
            // the derived TPE/Chèque/Virement rows, and Externe accounts.
            'compteTypeFilters' => GetComptesCaisse::allTypes(),
            'compteEtablissements' => $canViewComptes
                ? Etablissement::query()->orderBy('nom_centre')->get()
                    ->map(fn (Etablissement $e): array => ['id' => $e->id, 'nom' => $e->nom_centre])
                    ->all()
                : [],
            'comptePermissions' => [
                'create' => $user->can('cash-accounts.create'),
                'update' => $user->can('cash-accounts.update'),
                'delete' => $user->can('cash-accounts.delete'),
            ],
            'compteFilters' => [
                'compteSearch' => $compteSearch,
                'compteTypeFilter' => $compteTypeFilter,
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

    /**
     * Create an "Externe" account (a safe, an outside holder…) — the only
     * type this screen creates.
     *
     * It opens at 0,00 DH and Active: a balance is never typed by hand
     * anywhere in the app, it only moves through CaisseLedger.
     */
    public function store(StoreCaisseRequest $request): RedirectResponse
    {
        abort_unless($request->user()->can('cash-accounts.create'), 403);

        Caisse::create([
            ...$request->validated(),
            'solde' => 0,
            'statut' => Caisse::STATUT_ACTIVE,
        ]);

        return redirect()->route('backoffice.caisses.index', ['tab' => 'comptes'])
            ->with('success', __('Cash account created.'));
    }

    /**
     * Neither `solde` nor `type` is editable — see UpdateCaisseRequest.
     */
    public function update(UpdateCaisseRequest $request, Caisse $caisse): RedirectResponse
    {
        abort_unless($request->user()->can('cash-accounts.update'), 403);

        $caisse->update($request->validated());

        return redirect()->route('backoffice.caisses.index', ['tab' => 'comptes'])
            ->with('success', __('Cash account updated.'));
    }

    /**
     * An account is removable only while it is still empty. Money records are
     * never deleted (CLAUDE.md §11), so an account that carries any movement
     * — or a non-zero balance — must stay too; deactivate it instead. The
     * refusal is a `delete` field error, not a flash, so the React
     * confirm-dialog keeps it inline (same contract as BanqueController).
     */
    public function destroy(Request $request, Caisse $caisse, GetComptesCaisse $getComptesCaisse): RedirectResponse
    {
        abort_unless($request->user()->can('cash-accounts.delete'), 403);

        // Only an Externe account is ever removable: an employee's till is
        // owned by CaisseProvisioner, and the derived TPE/Chèque/Virement
        // rows have no id to reach this route with in the first place.
        if ($caisse->type !== Caisse::TYPE_EXTERNE) {
            return back()->withErrors(['delete' => __("An employee's own till cannot be deleted.")]);
        }

        if ($getComptesCaisse->hasMovements($caisse)) {
            return back()->withErrors(['delete' => __('This cash account carries movements and cannot be deleted. Deactivate it instead.')]);
        }

        if ((float) $caisse->solde !== 0.0) {
            return back()->withErrors(['delete' => __('This cash account still holds a balance and cannot be deleted.')]);
        }

        $caisse->delete();

        return redirect()->route('backoffice.caisses.index', ['tab' => 'comptes'])
            ->with('success', __('Cash account deleted.'));
    }

    public function show(Caisse $caisse, GetCaisseDetails $getCaisseDetails): Response
    {
        $this->authorize('view', $caisse);

        return Inertia::render('Backoffice/Caisses/Show', [
            'caisse' => $getCaisseDetails($caisse),
        ]);
    }
}
