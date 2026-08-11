<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Payment received from a student (gls-crm-schema.md §11).
 * Cheque data is inline (numero_cheque / banque / date_echeance_cheque),
 * populated only when methode = Chèque.
 *
 * inscription_fee_id is nullable: a row with no fee is an "avance" — money
 * received but not yet allocated. Applying an avance to a fee creates a
 * SECOND Encaissement row (via AppliquerAvance) whose
 * applied_from_encaissement_id points back at the avance; the avance row
 * itself is never edited (money records are append-only, CLAUDE.md §11).
 */
class Encaissement extends Model
{
    use HasFactory;
    use LogsActivity;

    public const METHODE_ESPECES = 'Espèces';
    public const METHODE_TPE = 'TPE';
    public const METHODE_CHEQUE = 'Chèque';
    public const METHODE_VIREMENT = 'Virement';

    public const METHODES = [
        self::METHODE_ESPECES,
        self::METHODE_TPE,
        self::METHODE_CHEQUE,
        self::METHODE_VIREMENT,
    ];

    protected $fillable = [
        'reference', 'student_id', 'inscription_fee_id', 'applied_from_encaissement_id', 'montant', 'methode',
        'date_paiement', 'caisse_id', 'agent_id',
        'numero_cheque', 'banque', 'date_echeance_cheque', 'note',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_paiement' => 'date',
            'date_echeance_cheque' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['montant', 'methode', 'caisse_id', 'inscription_fee_id'])
            ->logOnlyDirty()
            ->useLogName('encaissement');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(InscriptionFee::class, 'inscription_fee_id');
    }

    /** The avance this row applies, when it is itself an "apply" row (not a fresh avance). */
    public function appliedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'applied_from_encaissement_id');
    }

    /** Rows that have applied THIS avance to a fee — sum('montant') is the avance's used amount. */
    public function applications(): HasMany
    {
        return $this->hasMany(self::class, 'applied_from_encaissement_id');
    }

    public function isAvance(): bool
    {
        return $this->inscription_fee_id === null && $this->applied_from_encaissement_id === null;
    }

    public function montantUtilise(): float
    {
        return (float) $this->applications()->sum('montant');
    }

    public function montantRestant(): float
    {
        return max(0.0, (float) $this->montant - $this->montantUtilise());
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'agent_id');
    }

    /** Refunds recorded against this payment (Remboursement.encaissement_id). */
    public function remboursements(): HasMany
    {
        return $this->hasMany(Remboursement::class);
    }
}
