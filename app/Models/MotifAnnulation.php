<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cancellation/archival reason catalog ("Raisons d'annulation ou archivage")
 * — the managed list of reasons an inscription can be cancelled or archived
 * with. Starter list seeded by MotifAnnulationSeeder; managed by super-admin
 * only under Paramètres → Raisons d'annulation (same restriction pattern as
 * Banque: `cancellation-reasons.*` permissions are absent from every role in
 * PermissionRegistry::matrix()).
 *
 * is_system = true rows (e.g. "Changement de groupe") belong to application
 * flows and are locked from edit/delete — same rule as TypeDepense/StockType.
 */
class MotifAnnulation extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'motifs_annulation';

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    /** The system reason written by the "Changement de groupe" flow. */
    public const MOTIF_CHANGEMENT_GROUPE = 'Changement de groupe';

    protected $fillable = ['nom', 'is_system', 'statut'];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
