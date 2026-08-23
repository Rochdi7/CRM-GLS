<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Audit\Queries\GetActivityLogList;
use App\Http\Controllers\Controller;
use App\Support\Audit\AuditLogRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Journal d'audit" — the read-only forensic trail (CLAUDE.md §11).
 *
 * Read-only by design and by construction: there is no store/update/destroy
 * here and never should be. The journal is the evidence of what happened in
 * the CRM, so the application offers no way to edit or delete an entry — not
 * through this controller, and not through Eloquent either (App\Models\Activity
 * refuses updates and deletes outright, which is what makes the trail hold
 * even against a super-admin who bypasses every Gate).
 *
 * Gated on `audit-logs.view` via route middleware AND re-checked here, per
 * CLAUDE.md §16 (authorize in the controller as well as the route).
 */
final class AuditLogController extends Controller
{
    public function index(Request $request, GetActivityLogList $getActivityLogList): Response
    {
        $user = $request->user();
        abort_unless($user->can('audit-logs.view'), 403);

        $search = (string) $request->string('search');
        $logName = (string) $request->string('logName');
        $event = (string) $request->string('event');
        $causerId = (string) $request->string('causerId');
        $subjectType = (string) $request->string('subjectType');
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $ip = (string) $request->string('ip');
        $financeOnly = $request->boolean('financeOnly');
        $caisseId = (string) $request->string('caisseId');
        $includeDeveloper = $request->boolean('includeDeveloper');
        $perPage = (int) $request->integer('perPage', GetActivityLogList::DEFAULT_PER_PAGE);

        $list = $getActivityLogList(
            $search, $logName, $event, $causerId, $subjectType,
            $dateFrom, $dateTo, $ip, $financeOnly, $caisseId, $includeDeveloper, $perPage,
        );

        return Inertia::render('Backoffice/AuditLogs/Index', [
            'entries' => $list['data'],
            'logNames' => $getActivityLogList->logNameOptions(),
            'events' => $getActivityLogList->eventOptions(),
            'causers' => $getActivityLogList->causerOptions($includeDeveloper),
            'subjectTypes' => AuditLogRegistry::subjectTypeOptions(),
            'caisses' => $getActivityLogList->caisseOptions(),
            // Only offer the toggle when that login actually exists, so the
            // filter bar stays clean on installations without it.
            'hasDeveloperAccount' => $getActivityLogList->developerAccountExists(),
            'filters' => [
                'search' => $search,
                'logName' => $logName,
                'event' => $event,
                'causerId' => $causerId,
                'subjectType' => $subjectType,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'ip' => $ip,
                'financeOnly' => $financeOnly,
                'caisseId' => $caisseId,
                'includeDeveloper' => $includeDeveloper,
                'perPage' => $perPage,
            ],
        ]);
    }

    /**
     * One entry, in full, on its own page.
     *
     * A dedicated page (rather than only the inline expand) because reading a
     * single change is the common case for a non-technical reader: it gets a
     * shareable URL, the browser back button, and room to show every field
     * with its resolved value instead of a cramped drawer.
     */
    public function show(Request $request, GetActivityLogList $getActivityLogList, int $activity): Response
    {
        abort_unless($request->user()->can('audit-logs.view'), 403);

        $entry = $getActivityLogList->find($activity, $request->boolean('includeDeveloper'));

        abort_if($entry === null, 404);

        return Inertia::render('Backoffice/AuditLogs/Show', [
            'entry' => $entry,
        ]);
    }
}
