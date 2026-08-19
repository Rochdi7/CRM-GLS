<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Expense type catalog (gls-crm-schema.md §12).
 * is_system = true rows (e.g. "Paiement prof") are seeded once and locked:
 * payroll automation and till transfers depend on their existence. The admin
 * "add expense type" form must only ever create is_system = false rows.
 */
class TypeDepense extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'types_depenses';

    // Seeded system types — names are contracts with future code (payroll, transfers)
    public const SYSTEM_PAIEMENT_PROF = 'Paiement prof';
    public const SYSTEM_SALAIRE = 'Salaire';
    public const SYSTEM_TRANSFERT_CAISSE = 'Transfert à une autre caisse';

    public const STATUT_ACTIF = 'Actif';
    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    protected $fillable = [
        'nom', 'is_system', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class, 'type_depense_id');
    }
}
