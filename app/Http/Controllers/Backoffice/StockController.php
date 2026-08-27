<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Domain\Stock\Actions\EnregistrerMouvementStock;
use App\Domain\Stock\Queries\GetStockArticlesList;
use App\Domain\Stock\Queries\GetStockMouvementsList;
use App\Domain\Stock\Queries\GetStockTypesList;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Requests\Backoffice\Stock\StoreStockArticleRequest;
use App\Http\Requests\Backoffice\Stock\StoreStockMouvementRequest;
use App\Http\Requests\Backoffice\Stock\UpdateStockArticleRequest;
use App\Models\Etablissement;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion du stock — ONE Inertia page (Gestion des dépenses pattern):
 * Articles + Mouvements as client-side tabs. Article quantities only change
 * through movements (EnregistrerMouvementStock — caisses.solde pattern);
 * movements are never edited or deleted (compensating entries only).
 */
final class StockController extends Controller
{
    use AssertsContextScope;

    public function index(
        Request $request,
        GetStockArticlesList $getArticles,
        GetStockMouvementsList $getMouvements,
        GetStockTypesList $getStockTypes,
        CenterAccessService $centerAccess,
        CurrentContext $context,
    ): Response {
        $this->authorize('viewAny', StockArticle::class);

        $tab = (string) $request->string('tab', 'articles');
        $user = $request->user();

        $articleFilters = [
            'search' => (string) $request->string('search'),
            'stockTypeFilter' => (string) $request->string('stockTypeFilter'),
            'etablissementFilter' => (string) $request->string('etablissementFilter'),
            'statutFilter' => (string) $request->string('statutFilter'),
            'alerteFilter' => (string) $request->string('alerteFilter'),
        ];

        $mouvementFilters = [
            'articleFilter' => (string) $request->string('articleFilter'),
            'typeFilter' => (string) $request->string('typeFilter'),
            'dateFrom' => (string) $request->string('dateFrom'),
            'dateTo' => (string) $request->string('dateTo'),
        ];

        $typeSearch = (string) $request->string('typeSearch');

        $perPage = (int) $request->integer('perPage', GetStockArticlesList::DEFAULT_PER_PAGE);
        $typePerPage = (int) $request->integer('typePerPage', GetStockArticlesList::DEFAULT_PER_PAGE);

        // Options for the movement modal's article select + the Mouvements
        // filter — every accessible article, active first.
        $articleOptions = StockArticle::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->when($context->etablissementId(), fn ($q, $id) => $q
                ->where(fn ($w) => $w->whereNull('etablissement_id')->orWhere('etablissement_id', $id)))
            ->orderByDesc('statut')
            ->orderBy('nom')
            ->get(['id', 'nom', 'reference', 'quantite', 'statut'])
            ->map(fn (StockArticle $article): array => [
                'value' => $article->id,
                'label' => "{$article->nom} ({$article->reference}) — {$article->quantite} en stock",
            ])
            ->all();

        return Inertia::render('Backoffice/Stock/Index', [
            'tab' => in_array($tab, ['articles', 'mouvements', 'types'], true) ? $tab : 'articles',
            'articles' => $getArticles($user, $articleFilters, $perPage),
            'mouvements' => $getMouvements($user, $mouvementFilters, $perPage),
            'stockTypesList' => $getStockTypes(
                $typeSearch,
                in_array($typePerPage, [10, 25, 50, 100], true) ? $typePerPage : 10,
            ),
            'filters' => $articleFilters + $mouvementFilters + [
                'perPage' => in_array($perPage, GetStockArticlesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetStockArticlesList::DEFAULT_PER_PAGE,
            ],
            'typeFilters' => [
                'typeSearch' => $typeSearch,
                'typePerPage' => in_array($typePerPage, [10, 25, 50, 100], true) ? $typePerPage : 10,
            ],
            'perPageOptions' => GetStockArticlesList::PER_PAGE_OPTIONS,
            'articleOptions' => $articleOptions,
            'stockTypes' => StockType::query()
                ->where('statut', StockType::STATUT_ACTIF)
                ->orderBy('nom')
                ->get(['id', 'nom']),
            'statuts' => StockArticle::STATUTS,
            'mouvementTypes' => StockMouvement::TYPES,
            // Stock is per center: under « Tous les centres » the article form
            // and the list expose the center (centerLocked rule, CLAUDE.md §5).
            'etablissements' => Etablissement::query()
                ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user, 'id'))
                ->orderBy('nom_centre')
                ->get(['id', 'nom_centre']),
            'centerLocked' => ! $context->isAllCenters(),
            'permissions' => [
                'create' => $user->can('create', StockArticle::class),
                'update' => $user->can('stock.update'),
                'delete' => $user->can('stock.delete'),
                'move' => $user->can('stock.move'),
            ],
            'typePermissions' => [
                'create' => $user->can('create', StockType::class),
                'update' => $user->can('stock-types.update'),
                'delete' => $user->can('stock-types.delete'),
            ],
        ]);
    }

    public function storeArticle(
        StoreStockArticleRequest $request,
        CurrentContext $context,
        CenterAccessService $centerAccess,
    ): RedirectResponse {
        $this->authorize('create', StockArticle::class);

        $data = $request->validated();

        StockArticle::create([
            'reference' => ReferenceGenerator::make('ART', 'stock_articles'),
            'nom' => $data['nom'],
            'stock_type_id' => $data['stock_type_id'],
            'quantite' => 0,
            'seuil_alerte' => $data['seuil_alerte'] ?? null,
            'etablissement_id' => $this->resolveCenter($request, $context, $centerAccess, $data),
            'statut' => $data['statut'],
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('backoffice.stock.index')
            ->with('success', __('Stock item created.'));
    }

    public function updateArticle(
        UpdateStockArticleRequest $request,
        StockArticle $article,
        CurrentContext $context,
        CenterAccessService $centerAccess,
    ): RedirectResponse {
        $this->authorize('update', $article);

        $data = $request->validated();

        // quantite is untouched here on purpose — movements only.
        $attributes = [
            'nom' => $data['nom'],
            'stock_type_id' => $data['stock_type_id'],
            'seuil_alerte' => $data['seuil_alerte'] ?? null,
            'statut' => $data['statut'],
            'note' => $data['note'] ?? null,
        ];

        // The center can only be corrected while the row has no movement
        // history: once quantities moved, re-homing the row would silently
        // move that stock to another center without any movement line.
        if ($context->isAllCenters() && ! $article->mouvements()->exists()) {
            $attributes['etablissement_id'] = $this->resolveCenter($request, $context, $centerAccess, $data);
        }

        $article->update($attributes);

        return redirect()->back()
            ->with('success', __('Stock item updated.'));
    }

    /**
     * Stock is always per center. With a center selected in the top bar that
     * center wins (the form never shows a select); under « Tous les centres »
     * the form's choice is required and must be a center the user can access.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveCenter(
        Request $request,
        CurrentContext $context,
        CenterAccessService $centerAccess,
        array $data,
    ): int {
        $contextId = $context->etablissementId();

        if ($contextId !== null) {
            return $contextId;
        }

        $chosen = isset($data['etablissement_id']) ? (int) $data['etablissement_id'] : null;

        if ($chosen === null) {
            throw ValidationException::withMessages([
                'etablissement_id' => __('Please select the center this stock belongs to.'),
            ]);
        }

        if (! $centerAccess->canAccessCenter($request->user(), $chosen)) {
            throw ValidationException::withMessages([
                'etablissement_id' => __('You do not have access to this center.'),
            ]);
        }

        return $chosen;
    }

    public function destroyArticle(Request $request, StockArticle $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        // An article with movement history is an audit trail — never
        // deletable (the FK is restrictOnDelete as a backstop).
        if ($article->mouvements()->exists()) {
            throw ValidationException::withMessages([
                'delete' => __('This item has stock movements and cannot be deleted. Set it to Inactif instead.'),
            ]);
        }

        $article->delete();

        return redirect()->route('backoffice.stock.index')
            ->with('success', __('Stock item deleted.'));
    }

    public function storeMouvement(
        StoreStockMouvementRequest $request,
        EnregistrerMouvementStock $enregistrerMouvement,
    ): RedirectResponse {
        $data = $request->validated();
        $article = StockArticle::findOrFail((int) $data['stock_article_id']);

        $this->authorize('move', $article);

        // Stock has no year, but it is per centre: a movement must hit an
        // article of the ACTIVE centre, not one reachable through a stale
        // dropdown loaded before the top-bar switch (AssertsContextScope).
        $this->assertRecordInContext(
            $request,
            'stock_article_id',
            $article->etablissement_id,
            null,
            __('This stock item belongs to another centre than the active one.'),
            '',
        );

        $enregistrerMouvement(
            $article,
            $data['type'],
            (int) $data['quantite'],
            $data['note'] ?? null,
            $request->user()?->employee,
        );

        return redirect()->back()
            ->with('success', __('Stock movement recorded.'));
    }
}
