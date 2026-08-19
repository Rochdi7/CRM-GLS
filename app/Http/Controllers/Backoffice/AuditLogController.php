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
        $perPage = (int) $request->integer('perPage', GetActivityLogList::DEFAULT_PER_PAGE);

        $list = $getActivityLogList(
            $search, $logName, $event, $causerId, $subjectType,
            $dateFrom, $dateTo, $ip, $financeOnly, $perPage,
        );

        return Inertia::render('Backoffice/AuditLogs/Index', [
            'entries' => $list['data'],
            'logNames' => $getActivityLogList->logNameOptions(),
            'events' => $getActivityLogList->eventOptions(),
            'causers' => $getActivityLogList->causerOptions(),
            'subjectTypes' => AuditLogRegistry::subjectTypeOptions(),
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
                'perPage' => $perPage,
            ],
        ]);
    }
}
