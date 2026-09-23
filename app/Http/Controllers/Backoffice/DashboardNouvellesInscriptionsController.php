<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Reports\Actions\GetNouvellesInscriptionsChart;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * « Nouvelles inscriptions » — the students behind one clicked bar. Same
 * audience as the chart itself (every signed-in user) and the same scope:
 * the active centre from CurrentContext, which only offers centres the user
 * already reaches. `canViewStudents` only decides whether the modal draws a
 * link to the student page — that page keeps its own permission + policy.
 */
final class DashboardNouvellesInscriptionsController extends Controller
{
    public function __invoke(Request $request, GetNouvellesInscriptionsChart $chart): JsonResponse
    {
        return response()->json([
            'students' => $chart->students(
                (string) $request->string('duree'),
                (string) $request->string('key'),
            ),
            'canViewStudents' => (bool) $request->user()?->can('students.view'),
        ]);
    }
}
