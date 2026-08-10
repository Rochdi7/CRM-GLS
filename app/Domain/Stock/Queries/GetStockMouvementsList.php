<?php

declare(strict_types=1);

namespace App\Domain\Stock\Queries;

use App\Models\StockMouvement;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Mouvements tab — center scope reaches the movement
 * through its article (movements themselves carry no etablissement_id).
 */
final class GetStockMouvementsList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  array{articleFilter?: string, typeFilter?: string, dateFrom?: string, dateTo?: string}  $filters
     */
    public function __invoke(User $user, array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $articleFilter = $filters['articleFilter'] ?? '';
        $typeFilter = $filters['typeFilter'] ?? '';
        $dateFrom = $filters['dateFrom'] ?? '';
        $dateTo = $filters['dateTo'] ?? '';

        $mouvements = StockMouvement::query()
            ->with(['article:id,reference,nom,etablissement_id', 'createdBy:id,nom,prenom'])
            ->whereHas('article', function ($q) use ($user): void {
                $this->centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->when($articleFilter !== '', fn ($q) => $q->where('stock_article_id', (int) $articleFilter))
            ->when($typeFilter !== '', fn ($q) => $q->where('type', $typeFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $mouvements->through(fn (StockMouvement $mouvement): array => [
            'id' => $mouvement->id,
            'date' => $mouvement->created_at?->format('Y-m-d H:i'),
            'articleNom' => $mouvement->article?->nom,
            'articleReference' => $mouvement->article?->reference,
            'type' => $mouvement->type,
            'quantite' => $mouvement->quantite,
            'quantiteAvant' => $mouvement->quantite_avant,
            'quantiteApres' => $mouvement->quantite_apres,
            'note' => $mouvement->note,
            'par' => $mouvement->createdBy?->nomComplet(),
        ]);

        return $mouvements;
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
