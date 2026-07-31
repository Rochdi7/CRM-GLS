<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Domain\Expenses\Queries\GetDepenseDetails;
use App\Domain\Expenses\Queries\GetDepensesList;
use App\Domain\Finance\Queries\GetRemboursementsList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Depenses\StoreDepenseRequest;
use App\Http\Requests\Backoffice\Depenses\UpdateDepenseRequest;
use App\Models\Depense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Gestion des dépenses" — ONE Inertia page (Phase 10,
 * docs/phase-10-finance-audit.md §2.5/§3) hosting dépenses + remboursements
 * as client-side React tabs, replacing the former Livewire-tab Blade shell
 * (DepenseManagementController, now unreferenced — see
 * docs/legacy-frontend-removal-plan.md §0g). Access = ANY of the two
 * remaining view permissions (Types de dépenses moved OUT to its own
 * Inertia page in Phase 6); each tab's own data/actions are still gated
 * server-side by their own permission. No destroy(): a recorded expense is
 * never deleted (audit trail); corrections need a compensating entry.
 */
final class DepenseController extends Controller
{
    /** Mirrors Depense::registerMediaCollections()'s mime allowlist. */
    private const JUSTIFICATIF_MIMES = ['jpeg', 'jpg', 'png', 'webp', 'pdf'];

    private const JUSTIFICATIF_MAX_KB = 5120;

    public function index(
        Request $request,
        GetDepensesList $getDepensesList,
        GetRemboursementsList $getRemboursementsList,
    ): Response {
        $user = $request->user();
        abort_unless($user->canAny(['expenses.view', 'refunds.view']), 403);

        $search = (string) $request->string('search');
        $typeFilter = (string) $request->string('typeFilter');
        $caisseFilter = (string) $request->string('caisseFilter');
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $perPage = (int) $request->integer('perPage', GetDepensesList::DEFAULT_PER_PAGE);

        $depensesList = $user->can('expenses.view')
            ? $getDepensesList($user, $search, $typeFilter, $caisseFilter, $dateFrom, $dateTo, $perPage)
            : null;

        return Inertia::render('Backoffice/Depenses/Index', [
            'canViewDepenses' => $user->can('expenses.view'),
            'canViewRemboursements' => $user->can('refunds.view'),
            'depenses' => $depensesList['data'] ?? null,
            'montantTotal' => $depensesList['montantTotal'] ?? null,
            'typesDepenses' => $user->can('expenses.view') ? $getDepensesList->typeDepenseOptions() : [],
            'groups' => $user->can('expenses.view') ? $getDepensesList->groupOptions($user) : [],
            'methodes' => Depense::METHODES,
            'justificatifMimes' => self::JUSTIFICATIF_MIMES,
            'justificatifMaxKb' => self::JUSTIFICATIF_MAX_KB,
            'remboursements' => $user->can('refunds.view') ? $getRemboursementsList($user) : null,
            'students' => $user->can('refunds.view') ? $getRemboursementsList->studentOptions($user) : [],
            'filters' => [
                'search' => $search,
                'typeFilter' => $typeFilter,
                'caisseFilter' => $caisseFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function store(StoreDepenseRequest $request, EnregistrerDepense $action): RedirectResponse
    {
        $this->authorize('create', Depense::class);

        $employee = $request->user()->employee;

        if ($employee === null) {
            throw ValidationException::withMessages([
                'caisse_id' => __('Your account is not linked to any employee record.'),
            ]);
        }

        $payload = collect($request->validated())->except(['justificatifs'])->all();

        // Domain action: creates the expense, generates the DEP- reference,
        // and decrements caisses.solde in ONE transaction.
        $depense = $action->handle($payload, $employee);

        $this->storeJustificatifs($request, $depense);

        return redirect()->route('backoffice.depenses.index')
            ->with('success', __('Expense recorded.'));
    }

    public function show(Depense $depense, GetDepenseDetails $getDepenseDetails): Response
    {
        $this->authorize('view', $depense);

        return Inertia::render('Backoffice/Depenses/Show', [
            'depense' => $getDepenseDetails($depense),
        ]);
    }

    public function update(UpdateDepenseRequest $request, Depense $depense): RedirectResponse
    {
        $this->authorize('update', $depense);

        $payload = collect($request->validated())->except(['justificatifs'])->all();

        // montant / caisse_id are absent from $payload by construction — the
        // till balance already moved (UpdateDepenseRequest excludes them).
        $depense->update($payload);

        // New receipts can be attached during an edit too, not only at
        // creation — matches DepensesIndex::save() calling
        // storeJustificatifs() regardless of the create/edit branch.
        $this->storeJustificatifs($request, $depense);

        return redirect()->route('backoffice.depenses.index')
            ->with('success', __('Expense updated.'));
    }

    /** Detach one stored receipt while editing (the expense itself stays). */
    public function removeJustificatif(Depense $depense, int $media): RedirectResponse
    {
        $this->authorize('update', $depense);

        $item = $depense->getMedia('justificatifs')->firstWhere('id', $media);

        $item?->delete();

        return redirect()->route('backoffice.depenses.index')
            ->with('success', __('Receipt removed.'));
    }

    private function storeJustificatifs(Request $request, Depense $depense): void
    {
        foreach ($request->file('justificatifs', []) as $file) {
            if ($file === null) {
                continue;
            }

            $depense->addMedia($file->getRealPath())
                ->usingFileName($depense->id.'-'.$file->getClientOriginalName())
                ->toMediaCollection('justificatifs');
        }
    }
}
