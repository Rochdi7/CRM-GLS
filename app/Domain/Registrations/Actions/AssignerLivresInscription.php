<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Domain\Stock\Actions\EnregistrerMouvementStock;
use App\Models\Employee;
use App\Models\Inscription;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Syncs a registration's assigned books (inscription_livres) to the
 * submitted set of stock_article_ids — additive/subtractive, never a
 * destroy-and-recreate: a newly selected book gets a fresh row + a Sortie
 * movement (-1 stock); a deselected book has its row deleted + an Entrée
 * movement (+1 stock, restoring it). A book already assigned and still
 * present in the submitted set is left untouched (no double-decrement).
 * This is how re-editing an inscription can add a further book later (e.g.
 * after a level change) without re-charging stock for books already given.
 */
final class AssignerLivresInscription
{
    public function __construct(private readonly EnregistrerMouvementStock $enregistrerMouvement) {}

    /**
     * @param  list<int>  $stockArticleIds
     */
    public function handle(Inscription $inscription, array $stockArticleIds, ?Employee $assignedBy = null): void
    {
        DB::transaction(function () use ($inscription, $stockArticleIds, $assignedBy): void {
            $existing = $inscription->livres()->pluck('stock_article_id')->all();

            $toAdd = array_diff($stockArticleIds, $existing);
            $toRemove = array_diff($existing, $stockArticleIds);

            foreach ($toAdd as $stockArticleId) {
                $article = StockArticle::query()->findOrFail($stockArticleId);

                $this->enregistrerMouvement->__invoke(
                    $article,
                    StockMouvement::TYPE_SORTIE,
                    1,
                    __('Assigned to registration :reference', ['reference' => $inscription->reference]),
                    $assignedBy,
                );

                $inscription->livres()->create([
                    'stock_article_id' => $article->id,
                    'assigned_by' => $assignedBy?->id,
                ]);
            }

            if ($toRemove !== []) {
                $removedRows = $inscription->livres()->whereIn('stock_article_id', $toRemove)->get();

                foreach ($removedRows as $row) {
                    $article = StockArticle::query()->findOrFail($row->stock_article_id);

                    $this->enregistrerMouvement->__invoke(
                        $article,
                        StockMouvement::TYPE_ENTREE,
                        1,
                        __('Unassigned from registration :reference', ['reference' => $inscription->reference]),
                        $assignedBy,
                    );

                    $row->delete();
                }
            }
        });
    }

    /**
     * @param  list<int>  $stockArticleIds
     */
    public function validateAvailability(array $stockArticleIds, array $alreadyAssignedIds): void
    {
        $toCheck = array_diff($stockArticleIds, $alreadyAssignedIds);

        if ($toCheck === []) {
            return;
        }

        $articles = StockArticle::query()->whereIn('id', $toCheck)->get(['id', 'nom', 'quantite']);
        $outOfStock = $articles->filter(fn (StockArticle $a): bool => $a->quantite < 1);

        if ($outOfStock->isNotEmpty()) {
            throw ValidationException::withMessages([
                'livre_ids' => __('Out of stock: :books', ['books' => $outOfStock->pluck('nom')->implode(', ')]),
            ]);
        }
    }
}
