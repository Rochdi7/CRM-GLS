<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cash register / till (gls-crm-schema.md §10).
 *
 * ⚠ `solde` is a stored, application-updated number — NOT computed from a
 * ledger (deliberate trade-off). Every encaissement, depense, remboursement
 * and validated caisse_transfer MUST adjust it inside the same DB transaction.
 */
class Caisse extends Model
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
        'nom', 'etablissement_id', 'responsable_employee_id', 'solde', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
        ];
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsable_employee_id');
    }

    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(Remboursement::class);
    }

    public function transfersSortants(): HasMany
    {
        return $this->hasMany(CaisseTransfer::class, 'caisse_source_id');
    }

    public function transfersEntrants(): HasMany
    {
        return $this->hasMany(CaisseTransfer::class, 'caisse_destination_id');
    }
}
