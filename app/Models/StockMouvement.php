<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock movement — the audit trail of stock_articles.quantite. Never edited
 * or deleted (no update/destroy routes); corrections use a compensating
 * movement, mirroring the finance rule for money records.
 */
class StockMouvement extends Model
{
    use HasFactory;
    use Auditable;

    public const TYPE_ENTREE = 'Entrée';

    public const TYPE_SORTIE = 'Sortie';

    /** Inventory recount — `quantite` carries the NEW total, not a delta. */
    public const TYPE_AJUSTEMENT = 'Ajustement';

    public const TYPES = [
        self::TYPE_ENTREE,
        self::TYPE_SORTIE,
        self::TYPE_AJUSTEMENT,
    ];

    protected $fillable = [
        'stock_article_id', 'type', 'quantite', 'quantite_avant',
        'quantite_apres', 'note', 'created_by',
    ];


    public function article(): BelongsTo
    {
        return $this->belongsTo(StockArticle::class, 'stock_article_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
