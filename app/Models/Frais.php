<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Fee catalog entry (predefined "frais"). Assigned to groups via the
 * group_frais pivot; a group's assigned fees become the selectable
 * "Frais disponibles" on an inscription.
 */
class Frais extends Model
{
    use HasFactory;

    protected $table = 'frais';

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    protected $fillable = ['nom', 'statut'];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_frais')
            ->withPivot('montant', 'date_echeance', 'classification')
            ->withTimestamps();
    }
}
