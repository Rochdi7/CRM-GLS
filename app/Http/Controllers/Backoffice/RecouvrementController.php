<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Queries\GetRetardsList;
use App\Http\Controllers\Controller;
use App\Models\InscriptionFee;
use Illuminate\Http\RedirectResponse;
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
    public function index(Request $request, GetRetardsList $getRetardsList): Response|RedirectResponse
    {
        // Same rule as EncaissementController: the default date window is
        // applied ONLY to a bare first visit, as ONE redirect to the
        // canonical URL carrying it explicitly. Every later request reads
        // the literal values it sends — otherwise clearing the dates then
        // paginating re-applied the default (26/08/2026).
        //
        // ⚠ Only a TRULY bare visit redirects, never a partial reload driven
        // by the page itself. `useFilterReset` sends every key back at '',
        // and `router.get` omits empty strings, so « effacer tous les
        // filtres » arrived here with NO query string at all —
        // indistinguable d'une première visite. Le contrôleur redirigeait
        // donc en réinjectant `subMonth()`/`now()` : effacer un filtre
        // RÉTRÉCISSAIT le résultat, ce que §5 interdit.
        //
        // C'est la régression déjà corrigée sur Encaissements le 27/08/2026 ;
        // cet écran-là s'en protège AUSSI avec le marqueur « - », que
        // Recouvrement n'a pas — l'en-tête partiel est donc sa seule borne.
        // `X-Inertia-Partial-Data` marque un rechargement piloté par la page,
        // qui envoie toujours le jeu de filtres COMPLET : une clé absente y
        // signifie « effacée », jamais « jamais posée ».
        // Tests : tests/Feature/Backoffice/Finance/RecouvrementDateWindowTest.php
        if ($request->query() === [] && ! $request->hasHeader('X-Inertia-Partial-Data')) {
            return redirect()->route('backoffice.recouvrement.index', [
                'dateFrom' => now()->subMonth()->toDateString(),
                'dateTo' => now()->toDateString(),
            ]);
        }

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

        $retardsList = $getRetardsList(
            $request->user(),
            $groupFilter,
            $fraisFilter,
            $statutFilter,
            $dateFrom,
            $dateTo,
            $dureeBucket,
            $perPage,
        );

        return Inertia::render('Backoffice/Recouvrement/Index', [
            'retards' => $retardsList['data'],
            // Reste-à-payer over the WHOLE filtered set, not the visible page
            // — same separate-prop shape as the other finance lists
            // (ChequeController, EncaissementController).
            'montantTotal' => $retardsList['montantTotal'],
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
