<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Payment received from a student (gls-crm-schema.md §11).
 * Cheque data is inline (numero_cheque / banque / date_echeance_cheque),
 * populated only when methode = Chèque.
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
        'reference', 'student_id', 'inscription_fee_id', 'montant', 'methode',
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

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'agent_id');
    }
}
