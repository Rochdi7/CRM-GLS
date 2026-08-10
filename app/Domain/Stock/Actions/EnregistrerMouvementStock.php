<?php

declare(strict_types=1);

namespace App\Domain\Stock\Actions;

use App\Models\Employee;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The ONLY way stock_articles.quantite changes (caisses.solde pattern):
 * writes the stock_mouvements audit line and adjusts the quantity in ONE
 * transaction, with the article row locked against concurrent movements.
 *
 * - Entrée      : quantite += n
 * - Sortie      : quantite -= n (refused if it would go negative)
 * - Ajustement  : quantite  = n (inventory recount — n is the new total)
 */
final class EnregistrerMouvementStock
{
    public function __invoke(
        StockArticle $article,
        string $type,
        int $quantite,
        ?string $note = null,
        ?Employee $createdBy = null,
    ): StockMouvement {
        return DB::transaction(function () use ($article, $type, $quantite, $note, $createdBy): StockMouvement {
            /** @var StockArticle $locked */
            $locked = StockArticle::query()->lockForUpdate()->findOrFail($article->id);

            $avant = (int) $locked->quantite;

            $apres = match ($type) {
                StockMouvement::TYPE_ENTREE => $avant + $quantite,
                StockMouvement::TYPE_SORTIE => $avant - $quantite,
                StockMouvement::TYPE_AJUSTEMENT => $quantite,
                default => throw ValidationException::withMessages([
                    'type' => __('Invalid stock movement type.'),
                ]),
            };

            if ($apres < 0) {
                throw ValidationException::withMessages([
                    'quantite' => __('Insufficient stock for this removal.'),
                ]);
            }

            $mouvement = $locked->mouvements()->create([
                'type' => $type,
                'quantite' => $quantite,
                'quantite_avant' => $avant,
                'quantite_apres' => $apres,
                'note' => ($note ?? '') !== '' ? $note : null,
                'created_by' => $createdBy?->id,
            ]);

            $locked->update(['quantite' => $apres]);

            return $mouvement;
        });
    }
}
