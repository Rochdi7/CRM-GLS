<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
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
    use Auditable;
    use HasFactory;

    protected $table = 'frais';

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    protected $fillable = ['nom', 'montant_defaut', 'statut'];

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

    /**
     * The catalog's default amount is only a starting point: group_frais
     * .montant remains the authority for what a given group charges, so a
     * group can always override it.
     */
    protected function casts(): array
    {
        return ['montant_defaut' => 'decimal:2'];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_frais')
            ->withPivot('montant', 'date_echeance', 'classification')
            ->withTimestamps();
    }

    /**
     * Centers this fee is charged in, each with its OWN amount — the same
     * fee costs 1400 in Rabat/Casablanca, 1300 in Kénitra/Marrakech/Salé
     * and 1200 in Agadir, so the price lives on the pivot, not the fee.
     */
    public function etablissements(): BelongsToMany
    {
        return $this->belongsToMany(Etablissement::class, 'frais_etablissement')
            ->withPivot('montant')
            ->withTimestamps();
    }

    /**
     * What this fee costs in a given center.
     *
     * Falls back to the catalog default when the center has no line of its
     * own (or when no center is known at all, e.g. a group without an
     * établissement), so every caller always gets a usable amount.
     */
    public function montantPourCentre(?int $etablissementId): float
    {
        if ($etablissementId === null) {
            return (float) $this->montant_defaut;
        }

        $ligne = $this->relationLoaded('etablissements')
            ? $this->etablissements->firstWhere('id', $etablissementId)
            : $this->etablissements()->find($etablissementId);

        return $ligne === null
            ? (float) $this->montant_defaut
            : (float) $ligne->pivot->montant;
    }
}
