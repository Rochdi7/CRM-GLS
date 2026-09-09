<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Till-to-till transfer (gls-crm-schema.md §15) — the highest fraud-risk
 * operation in the system.
 *
 * Two-step flow (structure doc §7):
 * 1. Request  → row created "En attente", solde snapshots *_avant captured,
 *               caisses.solde NOT touched.
 * 2. Validate → a DIFFERENT employee approves; balances move and *_apres
 *               snapshots are captured in one transaction.
 */
class CaisseTransfer extends Model
{
    use HasFactory;
    use Auditable;

    public const STATUT_EN_ATTENTE = 'En attente';
    public const STATUT_VALIDE = 'Validé';
    public const STATUT_ANNULE = 'Annulé';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDE,
        self::STATUT_ANNULE,
    ];

    protected $fillable = [
        'reference', 'caisse_source_id', 'caisse_destination_id', 'montant',
        'date_transfert', 'solde_source_avant', 'solde_source_apres',
        'solde_dest_avant', 'solde_dest_apres', 'statut', 'note',
        'requested_by', 'validated_by', 'etablissement_id',
    ];

    /**
     * Mirror of the column default (§11): without it a create() that omits
     * `statut` leaves the model NULL while the row holds 'En attente', so the
     * next change is journalled as « avant : vide » — the trail would then
     * state a false previous value, which is worse than a missing one.
     */
    protected $attributes = [
        'statut' => self::STATUT_EN_ATTENTE,
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_transfert' => 'datetime',
            'solde_source_avant' => 'decimal:2',
            'solde_source_apres' => 'decimal:2',
            'solde_dest_avant' => 'decimal:2',
            'solde_dest_apres' => 'decimal:2',
        ];
    }


    /**
     * Centre ACTIF du caissier au moment de la demande — le centre dont
     * l'argent SORT (09/09/2026). Null sur les transferts antérieurs :
     * la lecture retombe alors sur le centre de la caisse (jamais de
     * backfill, §11).
     */
    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function caisseSource(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_source_id');
    }

    public function caisseDestination(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_destination_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'validated_by');
    }
}
