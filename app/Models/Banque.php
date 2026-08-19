<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bank catalog entry — the selectable "Banque" list on chèque payments
 * (Encaissement::banque stays a free-text column; the payment form's
 * dropdown is validated against this catalog's Actif entries via
 * StoreEncaissementRequest/UpdateEncaissementRequest).
 */
class Banque extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'banques';

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    protected $fillable = ['nom', 'statut'];
}
