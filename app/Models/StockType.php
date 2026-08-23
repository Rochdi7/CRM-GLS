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

    /**
     * Mirror of the column default, so a freshly created row and the model in
     * memory agree.
     *
     * Without this, a create() that omits `statut` leaves the model holding
     * NULL while the database row holds the default. The next status change
     * then records « avant : (vide) » in the audit journal — a false statement
     * of history, since the record did have a status. See InscriptionFee,
     * where this actually produced wrong entries.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => self::STATUT_ACTIF,
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
