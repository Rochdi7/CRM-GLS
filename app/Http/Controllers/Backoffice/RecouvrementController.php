<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Queries\GetRetardsList;
use App\Http\Controllers\Controller;
use App\Models\InscriptionFee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des recouvrements — read-only overdue-fees report, two client-side
 * tabs sharing one query (GetRetardsList): "Retards selon la durée" (the
 * same list pre-filtered to a duration bucket) and "Retards selon les
 * critères" (the full filterable table). "Suivi du recouvrement" and
 * "Prévisions de paiement" are not implemented yet — no data source exists
 * for them (no reminder/recovery-attempt tracking, no projection model).
 */
final class RecouvrementController extends Controller
{
    public function index(Request $request, GetRetardsList $getRetardsList): Response
    {
        $groupFilter = (string) $request->string('groupFilter');
        $fraisFilter = (string) $request->string('fraisFilter');
        $statutFilter = (string) $request->string('statutFilter');
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $dureeBucket = (string) $request->string('dureeBucket');
        $perPage = (int) $request->integer('perPage', GetRetardsList::DEFAULT_PER_PAGE);

        if (! in_array($statutFilter, InscriptionFee::STATUTS, true)) {
            $statutFilter = '';
        }

        if (! in_array($dureeBucket, GetRetardsList::BUCKETS, true)) {
            $dureeBucket = '';
        }

        return Inertia::render('Backoffice/Recouvrement/Index', [
            'retards' => $getRetardsList(
                $request->user(),
                $groupFilter,
                $fraisFilter,
                $statutFilter,
                $dateFrom,
                $dateTo,
                $dureeBucket,
                $perPage,
            ),
            'bucketCounts' => $getRetardsList->bucketCounts(
                $request->user(),
                $groupFilter,
                $fraisFilter,
                $statutFilter,
                $dateFrom,
                $dateTo,
            ),
            'filters' => [
                'groupFilter' => $groupFilter,
                'fraisFilter' => $fraisFilter,
                'statutFilter' => $statutFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'dureeBucket' => $dureeBucket,
                'perPage' => in_array($perPage, GetRetardsList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetRetardsList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetRetardsList::PER_PAGE_OPTIONS,
            'groupOptions' => $getRetardsList->groupOptions($request->user()),
            'fraisOptions' => $getRetardsList->fraisOptions(),
            'statuts' => [InscriptionFee::STATUT_NON_PAYE, InscriptionFee::STATUT_PAYE_PARTIELLEMENT],
        ]);
    }
}
