<?php

declare(strict_types=1);

namespace App\Domain\Stock\Queries;

use App\Models\StockArticle;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Stock articles list — same center scoping recipe as
 * every other list (accessible centers ∩ active context center; NULL-center
 * articles are global). Stock has no academic-year dimension.
 */
final class GetStockArticlesList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  array{search?: string, categorieFilter?: string, statutFilter?: string, alerteFilter?: string}  $filters
     */
    public function __invoke(User $user, array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $search = $filters['search'] ?? '';
        $categorieFilter = $filters['categorieFilter'] ?? '';
        $statutFilter = $filters['statutFilter'] ?? '';
        $alerteFilter = $filters['alerteFilter'] ?? '';

        $articles = StockArticle::query()
            ->with('etablissement:id,nom_centre')
            ->withCount('mouvements')
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($categorieFilter !== '', fn ($q) => $q->where('categorie', $categorieFilter))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($alerteFilter === '1', fn ($q) => $q
                ->whereNotNull('seuil_alerte')
                ->whereColumn('quantite', '<=', 'seuil_alerte'))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nom', 'ilike', "%{$search}%")
                ->orWhere('reference', 'ilike', "%{$search}%")))
            ->orderBy('nom')
            ->paginate($perPage)
            ->withQueryString();

        $articles->through(fn (StockArticle $article): array => [
            'id' => $article->id,
            'reference' => $article->reference,
            'nom' => $article->nom,
            'categorie' => $article->categorie,
            'quantite' => $article->quantite,
            'seuilAlerte' => $article->seuil_alerte,
            'enAlerte' => $article->enAlerte(),
            'etablissement' => $article->etablissement?->nom_centre,
            'statut' => $article->statut,
            'note' => $article->note,
            'mouvementsCount' => $article->mouvements_count,
        ]);

        return $articles;
    }

    private function scopeToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
