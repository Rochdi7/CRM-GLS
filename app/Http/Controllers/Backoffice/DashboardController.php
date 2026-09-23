<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payroll\Queries\GetEspaceEnseignant;
use App\Domain\Reports\Actions\GetAnnualFraisSummary;
use App\Domain\Reports\Actions\GetDashboardStats;
use App\Domain\Reports\Actions\GetNouvellesInscriptionsChart;
use App\Domain\Reports\Actions\GetSeancesCalendar;
use App\Http\Controllers\Controller;
use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetDashboardStats $getDashboardStats,
        GetAnnualFraisSummary $getAnnualFraisSummary,
        GetSeancesCalendar $getSeancesCalendar,
        GetNouvellesInscriptionsChart $getNouvellesInscriptions,
        GetEspaceEnseignant $getEspaceEnseignant,
        CurrentContext $context,
    ): Response {
        // "Résumé des séances" calendar month — the chart itself follows the
        // top-bar année scolaire switcher (CurrentContext), no per-widget
        // year parameter any more.
        $calMonth = (string) $request->string('calMonth');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $calMonth) !== 1) {
            $calMonth = now()->format('Y-m');
        }

        // « Nouvelles inscriptions » bar chart — every signed-in user, no
        // permission (23/09/2026, business request). Still scoped to the
        // active centre by CurrentContext, so each user only counts the
        // centres they already reach.
        $duree = (string) $request->string('inscDuree', GetNouvellesInscriptionsChart::DUREE_DEFAUT);

        // Every widget is a closure so Inertia partial reloads
        // (`only: ['seancesCalendar']` when paging the calendar) compute
        // ONLY the requested widget. A plain value here would still be
        // evaluated server-side and then dropped from the response.
        // Espace enseignant (23/09/2026) — servi UNIQUEMENT au prof connecté,
        // pour LUI-MÊME. L'identité est `$user->employee` (jamais un id du
        // client), et il faut à la fois la permission ET une fiche employé de
        // catégorie Enseignant : un compte sans fiche n'a rien à voir ici.
        $user = $request->user();
        $employee = $user?->employee;
        $estEnseignant = $employee !== null
            && $employee->estEnseignant()
            && $user->can('dashboard.espace-enseignant');
        $espaceMois = (string) $request->string('espaceMois');

        return Inertia::render('Backoffice/Dashboard/Index', [
            'stats' => fn () => $getDashboardStats($context)->toArray(),
            'annualFrais' => fn () => $getAnnualFraisSummary(),
            'annualFraisPeriode' => fn () => $getAnnualFraisSummary->periodeLabel(),
            'seancesCalendar' => fn () => $getSeancesCalendar($context, $calMonth),
            'nouvellesInscriptions' => fn () => $getNouvellesInscriptions($duree),
            'espaceEnseignant' => fn () => $estEnseignant
                ? $getEspaceEnseignant($employee, $espaceMois !== '' ? $espaceMois : null)
                : null,
        ]);
    }
}
