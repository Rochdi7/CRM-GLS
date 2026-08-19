<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stock product-type catalog — replaces StockArticle's old hardcoded
 * CATEGORIES array. is_system = true rows are the original 6 categories,
 * seeded once (StockTypeSeeder) and locked from admin editing/deletion,
 * same pattern as TypeDepense.
 */
class StockType extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'stock_types';

    // Seeded system types — kept for any code that needs to reference "Livre" by name.
    public const SYSTEM_LIVRE = 'Livre';

    public const STATUT_ACTIF = 'Actif';
    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    protected $fillable = [
        'nom', 'is_system', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(StockArticle::class);
    }
}
