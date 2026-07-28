<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Room / venue (gls-crm-schema.md §3), physical or virtual, per branch.
 */
class Salle extends Model
{
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

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
