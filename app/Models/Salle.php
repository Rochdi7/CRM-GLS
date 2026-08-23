<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Room / venue (gls-crm-schema.md §3), physical or virtual, per branch.
 */
class Salle extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUT_ACTIVE = 'Active';
    public const STATUT_INACTIVE = 'Inactive';

    public const STATUTS = [
        self::STATUT_ACTIVE,
        self::STATUT_INACTIVE,
    ];

    protected $fillable = [
        'nom', 'etablissement_id', 'capacite', 'statut',
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
        'statut' => self::STATUT_ACTIVE,
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
