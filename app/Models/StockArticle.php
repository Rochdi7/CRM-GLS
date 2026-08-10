<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Inventory item (Stock module). `quantite` is application-maintained: it
 * only ever changes through EnregistrerMouvementStock (one transaction with
 * the stock_mouvements audit line) — never a raw update.
 */
class StockArticle extends Model
{
    use HasFactory;

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    /** Plain VARCHAR validated against constants — deliberate (schema doc). */
    public const CATEGORIES = [
        'Fournitures de bureau',
        'Matériel pédagogique',
        'Livres et manuels',
        'Consommables',
        'Équipement',
        'Autre',
    ];

    protected $fillable = [
        'reference', 'nom', 'categorie', 'quantite', 'seuil_alerte',
        'etablissement_id', 'statut', 'note',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(StockMouvement::class);
    }

    public function enAlerte(): bool
    {
        return $this->seuil_alerte !== null && $this->quantite <= $this->seuil_alerte;
    }
}
