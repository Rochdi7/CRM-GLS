<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One book (StockArticle row) assigned to a registration — see the
 * migration's docblock for the append-then-remove design (never a "swap"
 * update; AssignerLivresInscription only ever creates or deletes rows).
 */
class InscriptionLivre extends Model
{
    use HasFactory;

    protected $fillable = [
        'inscription_id', 'stock_article_id', 'assigned_by',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function stockArticle(): BelongsTo
    {
        return $this->belongsTo(StockArticle::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }
}
