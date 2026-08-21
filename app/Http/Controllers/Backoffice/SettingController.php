<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Centers\Queries\GetEtablissementsList;
use App\Domain\Settings\Queries\GetAccessibleCenterOptions;
use App\Domain\Settings\Queries\GetAnneesScolairesList;
use App\Domain\Settings\Queries\GetBanquesList;
use App\Domain\Settings\Queries\GetFraisList;
use App\Domain\Settings\Queries\GetMotifsAnnulationList;
use App\Domain\Settings\Queries\GetSallesList;
use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Banque;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\MotifAnnulation;
use App\Models\Salle;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paramètres — Inertia/React (Phase 6). One page, four tabs
 * (établissements, années scolaires, salles, frais), URL-driven via
 * ?tab=<name> — React owns visual tab state but the URL stays authoritative
 * (browser back/forward and refresh both work). Only the ACTIVE tab's
 * dataset is fetched/sent — no full-table payload for every tab on every
 * request (CLAUDE.md § Performance rules; data volume here is tiny — max a
 * few hundred rows per table — but still scoped per-tab on principle).
 *
 * Access = ANY of the four view permissions; each tab is included in
 * `availableTabs` (and its data fetched) only if its own permission is
 * held — same per-tab gating the Livewire version had, now enforced
 * server-side instead of in Blade @can blocks.
 */
final class SettingController extends Controller
{
    private const TAB_PERMISSIONS = [
        'etablissements' => 'centers.view',
        'annees-scolaires' => 'academic-years.view',
        'salles' => 'rooms.view',
        'frais' => 'fees.view',
        'banques' => 'banks.view',
        'motifs-annulation' => 'cancellation-reasons.view',
        // Application-wide switches (dépense approval...). No role preset
        // holds system-settings.view, so in practice super-admins only.
        'systeme' => 'system-settings.view',
    ];

    public function __invoke(
        Request $request,
        GetEtablissementsList $getEtablissementsList,
        GetAnneesScolairesList $getAnneesScolairesList,
        GetSallesList $getSallesList,
        GetFraisList $getFraisList,
        GetBanquesList $getBanquesList,
        GetMotifsAnnulationList $getMotifsAnnulationList,
        GetAccessibleCenterOptions $getAccessibleCenterOptions,
        CurrentContext $context,
    ): Response {
        $user = $request->user();

        $availableTabs = collect(self::TAB_PERMISSIONS)
            ->filter(fn (string $permission) => $user->can($permission))
            ->keys()
            ->values()
            ->all();

        abort_if($availableTabs === [], 403);

        $requested = (string) $request->query('tab', '');
        $activeTab = in_array($requested, $availableTabs, true) ? $requested : $availableTabs[0];

        $permissions = collect($availableTabs)->mapWithKeys(fn (string $tab) => [
            $tab => $this->tabPermissions($user, $tab),
        ])->all();

        $props = [
            'activeTab' => $activeTab,
            'availableTabs' => $availableTabs,
            'permissions' => $permissions,
        ];

        // Only the active tab's dataset is fetched — never every tab on
        // every request.
        match ($activeTab) {
            'etablissements' => $props['etablissements'] = $getEtablissementsList(),
            'annees-scolaires' => $props['anneesScolaires'] = $getAnneesScolairesList(),
            'salles' => $props += [
                'salles' => $getSallesList(),
                'centerOptions' => $getAccessibleCenterOptions($user)->values()->all(),
                // Hides the redundant Centre column once the context switcher
                // is on a single center (CLAUDE.md §5 centre-filter rule).
                'centerLocked' => ! $context->isAllCenters(),
            ],
            // The catalog is national: a fee is attached to the centers
            // that charge it, each with its own amount. The picker offers
            // only centers the acting user may act on (same restriction as
            // Salles); prices for other centers are preserved untouched on
            // save, never silently dropped.
            'frais' => $props += [
                'frais' => $getFraisList(),
                'centerOptions' => $getAccessibleCenterOptions($user)->values()->all(),
            ],
            'banques' => $props['banques'] = $getBanquesList(),
            'motifs-annulation' => $props['motifsAnnulation'] = $getMotifsAnnulationList(),
            'systeme' => $props['systeme'] = [
                'expenseApproval' => AppSettings::expenseApprovalEnabled(),
            ],
            default => null,
        };

        return Inertia::render('Backoffice/Settings/Index', $props);
    }

    /**
     * @return array{create: bool, update: bool, delete: bool}
     */
    private function tabPermissions(User $user, string $tab): array
    {
        [$model, $prefix] = match ($tab) {
            'etablissements' => [Etablissement::class, 'centers'],
            'annees-scolaires' => [AnneeScolaire::class, 'academic-years'],
            'salles' => [Salle::class, 'rooms'],
            'frais' => [Frais::class, 'fees'],
            'banques' => [Banque::class, 'banks'],
            'motifs-annulation' => [MotifAnnulation::class, 'cancellation-reasons'],
            // Système has no CRUD model behind it - only an update switch.
            'systeme' => [null, 'system-settings'],
        };

        return [
            'create' => $model !== null && $user->can('create', $model),
            'update' => $user->can("{$prefix}.update"),
            'delete' => $model !== null && $user->can("{$prefix}.delete"),
        ];
    }
}
