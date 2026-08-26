<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Expenses\Actions\ApprouverDepense;
use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Domain\Finance\Support\CaisseResolver;
use App\Domain\Expenses\Actions\RefuserDepense;
use App\Domain\Expenses\Queries\GetDepenseDetails;
use App\Domain\Expenses\Queries\GetDepensesList;
use App\Domain\Finance\Queries\GetRemboursementsList;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Depenses\StoreDepenseRequest;
use App\Http\Requests\Backoffice\Depenses\UpdateDepenseRequest;
use App\Models\Depense;
use App\Models\Group;
use App\Support\Settings\AppSettings;
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
    use AssertsContextScope;

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

        // The operation trail (créée le / modifiée le) and the « Validation
        // des dépenses » tab are for auditing who keyed what and when — they
        // are super-admin only, keyed on `expenses.approve`, which is
        // deliberately in NO role preset in PermissionRegistry::matrix().
        // ⚠ This is UI shaping only: the timestamps below are simply not sent
        // to anyone else, so there is nothing for a crafted request to reveal.
        $canAudit = $user->can('expenses.approve');

        $search = (string) $request->string('search');
        $typeFilter = (string) $request->string('typeFilter');
        $caisseFilter = (string) $request->string('caisseFilter');
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $statutFilter = (string) $request->string('statutFilter');
        $perPage = (int) $request->integer('perPage', GetDepensesList::DEFAULT_PER_PAGE);

        $depensesList = $user->can('expenses.view')
            ? $getDepensesList($user, $search, $typeFilter, $caisseFilter, $dateFrom, $dateTo, $perPage, GetDepensesList::SCOPE_HORS_PAIEMENT_PROF, $statutFilter)
            : null;

        // "Paiement prof" dépenses live in their own tab — same records,
        // same money rules, just listed apart to keep the Dépenses table
        // readable (they are excluded from $depensesList above).
        $paiementsProfList = $user->can('expenses.view')
            ? $getDepensesList($user, $search, $typeFilter, $caisseFilter, $dateFrom, $dateTo, $perPage, GetDepensesList::SCOPE_PAIEMENT_PROF, $statutFilter)
            : null;

        // The acting employee's own till balance — shown read-only in the
        // Dépense create modal so staff can see what they're spending
        // against (same till StoreDepenseRequest silently derives on save).
        // The physical till only (Employee::till()) — the same account
        // CaisseResolver::tillOf() debits on save, never an Externe safe.
        $employee = $user->employee;
        $soldeActuel = $employee !== null
            ? (string) ($employee->till()->first()?->solde ?? '0.00')
            : null;

        return Inertia::render('Backoffice/Depenses/Index', [
            'canViewDepenses' => $user->can('expenses.view'),
            'canViewRemboursements' => $user->can('refunds.view'),
            'soldeActuel' => $soldeActuel,
            'depenses' => $this->scrubOperationDates($depensesList['data'] ?? null, $canAudit),
            'montantTotal' => $depensesList['montantTotal'] ?? null,
            'montantEnAttente' => $depensesList['montantEnAttente'] ?? null,
            'enAttenteCount' => $depensesList['enAttenteCount'] ?? 0,
            'paiementsProf' => $this->scrubOperationDates($paiementsProfList['data'] ?? null, $canAudit),
            'paiementsProfTotal' => $paiementsProfList['montantTotal'] ?? null,
            // Drives the UI: when approval is OFF the statut column, the
            // filter and the approve/refuse actions are all pointless.
            'approvalEnabled' => AppSettings::expenseApprovalEnabled(),
            'canApprove' => $canAudit,
            // Drives BOTH the « Date d'opération » column and the
            // « Validation des dépenses » tab — see $canAudit above.
            'canAudit' => $canAudit,
            'depenseStatuts' => Depense::STATUTS,
            'typesDepenses' => $user->can('expenses.view') ? $getDepensesList->typeDepenseOptions() : [],
            // The Dépenses tab's Type filter must not offer "Paiement prof"
            // (that type now has its own tab and is excluded there); the
            // create/edit modal still offers every type.
            'paiementProfTypeId' => $user->can('expenses.view') ? $getDepensesList->paiementProfTypeId() : null,
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
                'statutFilter' => $statutFilter,
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
                'type_depense_id' => __('Your account is not linked to any employee record.'),
            ]);
        }

        // A "Paiement prof" attribution must point at a group the user can
        // reach inside the active context — otherwise per-group expense
        // reporting silently crosses centres (AssertsContextScope). It is
        // the ONLY type that carries a group at all: PaiementProfRules makes
        // group_id required for it and prohibited for every other type.
        if (($request->validated('group_id') ?? null) !== null) {
            $this->assertGroupInContext($request, Group::findOrFail((int) $request->validated('group_id')));
        }

        // A dépense is ALWAYS debited from the acting employee's own physical
        // till — never chosen client-side, and NOT routed by
        // `methode_paiement` (that field is descriptive only: the till is
        // what settles an expense, accounting rule confirmed 24/08/2026).
        // Same self-heal as EncaissementController for pre-provisioner
        // accounts.
        $caisse = app(CaisseResolver::class)->tillOf($employee);

        $payload = collect($request->validated())
            ->except(['justificatifs'])
            ->merge(['caisse_id' => $caisse->id])
            ->all();

        // Domain action: creates the expense, generates the DEP- reference,
        // and decrements caisses.solde in ONE transaction.
        $depense = $action->handle($payload, $employee);

        $this->storeJustificatifs($request, $depense);

        // When approval is ON the money has NOT left the till yet — say so,
        // otherwise staff assume the expense is already settled.
        return redirect()->route('backoffice.depenses.index')
            ->with('success', $depense->isEnAttente()
                ? __('Expense submitted — awaiting approval.')
                : __('Expense recorded.'));
    }

    /**
     * Approve a pending expense — THIS is where the till is debited
     * (Domain\Expenses\Actions\ApprouverDepense).
     */
    public function approve(Request $request, Depense $depense, ApprouverDepense $action): RedirectResponse
    {
        $this->authorize('approve', $depense);

        $action->handle($depense, $this->actingEmployee($request));

        return redirect()->route('backoffice.depenses.index')
            ->with('success', __('Expense approved — the till has been debited.'));
    }

    /** Refuse a pending expense — no money ever moves; the row is kept. */
    public function refuse(Request $request, Depense $depense, RefuserDepense $action): RedirectResponse
    {
        $this->authorize('approve', $depense);

        $validated = $request->validate([
            'motif_refus' => ['nullable', 'string', 'max:255'],
        ]);

        $action->handle($depense, $this->actingEmployee($request), $validated['motif_refus'] ?? null);

        return redirect()->route('backoffice.depenses.index')
            ->with('success', __('Expense refused — no money was moved.'));
    }

    /**
     * The Employee record behind the acting user — every approval decision is
     * attributed to a person, so a user with no employee row cannot decide.
     */
    private function actingEmployee(Request $request): \App\Models\Employee
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            throw ValidationException::withMessages([
                'statut' => __('Your account is not linked to any employee record.'),
            ]);
        }

        return $employee;
    }

    public function show(Request $request, Depense $depense, GetDepenseDetails $getDepenseDetails): Response
    {
        $this->authorize('view', $depense);

        $details = $getDepenseDetails($depense);
        $canAudit = $request->user()->can('expenses.approve');

        if (! $canAudit) {
            // Same rule as index(): the operation trail never leaves the
            // server for a non-super-admin.
            unset($details['createdAt'], $details['updatedAt'], $details['wasEdited']);
        }

        return Inertia::render('Backoffice/Depenses/Show', [
            'depense' => $details,
            'canAudit' => $canAudit,
        ]);
    }

    /**
     * Drop the operation timestamps from a paginated list unless the viewer
     * may audit. Done server-side on purpose: a client-side `{canAudit && …}`
     * would still ship every "keyed in at" to the browser, which is precisely
     * what this trail is meant to keep to super-admins.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator|null  $paginator
     */
    private function scrubOperationDates($paginator, bool $canAudit)
    {
        if ($paginator === null || $canAudit) {
            return $paginator;
        }

        return $paginator->through(function (array $row): array {
            unset($row['createdAt'], $row['updatedAt'], $row['wasEdited']);

            return $row;
        });
    }

    public function update(UpdateDepenseRequest $request, Depense $depense): RedirectResponse
    {
        $this->authorize('update', $depense);

        if (($request->validated('group_id') ?? null) !== null) {
            $this->assertGroupInContext($request, Group::findOrFail((int) $request->validated('group_id')));
        }

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
