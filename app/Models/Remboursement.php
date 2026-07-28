<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Refund to a student (gls-crm-schema.md §14) — separate audit trail from
 * generic depenses even though both drain a caisse.
 */
class Remboursement extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'reference', 'beneficiaire_id', 'caisse_id', 'montant',
        'date_remboursement', 'motif', 'note', 'agent_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_remboursement' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['montant', 'beneficiaire_id', 'caisse_id'])
            ->logOnlyDirty()
            ->useLogName('remboursement');
    }

    public function beneficiaire(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'beneficiaire_id');
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
