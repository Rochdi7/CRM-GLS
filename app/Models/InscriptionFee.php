<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fee line item owed for an enrollment (gls-crm-schema.md §9),
 * e.g. "Frais d'inscription", "Frais de Juillet". Payments allocate per-fee.
 */
class InscriptionFee extends Model
{
    use HasFactory;

    public const STATUT_NON_PAYE = 'Non payé';

    public const STATUT_PAYE_PARTIELLEMENT = 'Payé partiellement';

    public const STATUT_PAYE = 'Payé';

    public const STATUTS = [
        self::STATUT_NON_PAYE,
        self::STATUT_PAYE_PARTIELLEMENT,
        self::STATUT_PAYE,
    ];

    protected $fillable = [
        'inscription_id', 'frais_id', 'nom',
        'montant_initial', 'remise_pct', 'remise_montant', 'montant',
        'date_echeance', 'note', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'montant_initial' => 'decimal:2',
            'remise_pct' => 'decimal:2',
            'remise_montant' => 'decimal:2',
            'date_echeance' => 'date',
        ];
    }

    /**
     * Final amount after discount: initial − (pct% of initial) OR − fixed DH.
     */
    public static function computeMontant(float $initial, ?float $remisePct, ?float $remiseMontant): float
    {
        if ($remisePct !== null && $remisePct > 0) {
            return round($initial * (1 - min($remisePct, 100) / 100), 2);
        }

        return round(max(0, $initial - (float) ($remiseMontant ?? 0)), 2);
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function frais(): BelongsTo
    {
        return $this->belongsTo(Frais::class);
    }

    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    public function montantPaye(): float
    {
        return (float) $this->encaissements()->sum('montant');
    }
}
