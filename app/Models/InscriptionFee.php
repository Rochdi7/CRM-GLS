<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
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
    use Auditable;
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
        'date_echeance', 'note', 'statut', 'masque_le', 'masque_origine',
    ];

    /**
     * Mirror of the column default, so a freshly created row and the model in
     * memory agree.
     *
     * The database defaults `statut` to 'Non payé', but a create() that omits
     * the key left the PHP model holding NULL while the row held 'Non payé'.
     * A later status change then recorded « avant : (vide) » in the audit
     * journal — the trail claimed the fee came from nothing when it actually
     * came from « Non payé », which is a false statement of history, not just
     * a display quirk. Declaring the default here fixes every creation path at
     * once rather than each caller remembering to pass it.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => self::STATUT_NON_PAYE,
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'montant_initial' => 'decimal:2',
            'remise_pct' => 'decimal:2',
            'remise_montant' => 'decimal:2',
            'date_echeance' => 'date',
            'masque_le' => 'datetime',
        ];
    }

    /** Who hid the line: the group (RetirerFraisGroupe) or a person (hideFee). */
    public const MASQUE_ORIGINE_GROUPE = 'groupe';

    public const MASQUE_ORIGINE_MANUEL = 'manuel';

    public function estMasque(): bool
    {
        return $this->masque_le !== null;
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
