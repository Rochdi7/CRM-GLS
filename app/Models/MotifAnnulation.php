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

    /**
     * What a reason may be used to cancel.
     *
     * A séance and an inscription are cancelled for different reasons:
     * « Malade » / « jour férié » explain why a class did not take place,
     * « Non-paiement » / « Transfert d’établissement » why a student left.
     * PORTEE_TOUS keeps a reason valid on both forms (« Autre »).
     */
    public const PORTEE_TOUS = 'tous';

    public const PORTEE_INSCRIPTION = 'inscription';

    public const PORTEE_SEANCE = 'seance';

    public const PORTEES = [
        self::PORTEE_TOUS,
        self::PORTEE_INSCRIPTION,
        self::PORTEE_SEANCE,
    ];

    /** The system reason written by the "Changement de groupe" flow. */
    public const MOTIF_CHANGEMENT_GROUPE = 'Changement de groupe';

    protected $fillable = ['nom', 'is_system', 'portee', 'statut'];

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
        'portee' => self::PORTEE_TOUS,
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
