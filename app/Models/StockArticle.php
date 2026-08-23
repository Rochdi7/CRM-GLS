<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Inventory item (Stock module). `quantite` is application-maintained: it
 * only ever changes through EnregistrerMouvementStock (one transaction with
 * the stock_mouvements audit line) — never a raw update.
 *
 * A product with per-center stock (e.g. a book title) is modeled as ONE
 * StockArticle row PER center — same `nom`, different `etablissement_id`/
 * `quantite`/`reference` — not a single row shared across centers.
 */
class StockArticle extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    protected $fillable = [
        'reference', 'nom', 'stock_type_id', 'quantite', 'seuil_alerte',
        'etablissement_id', 'statut', 'note',
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

    public function stockType(): BelongsTo
    {
        return $this->belongsTo(StockType::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(StockMouvement::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(InscriptionLivre::class);
    }

    public function enAlerte(): bool
    {
        return $this->seuil_alerte !== null && $this->quantite <= $this->seuil_alerte;
    }
}
