<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Refund to a student (gls-crm-schema.md §14) — separate audit trail from
 * generic depenses even though both drain a caisse.
 */
class Remboursement extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'reference', 'beneficiaire_id', 'encaissement_id', 'caisse_id', 'montant',
        'date_remboursement', 'motif', 'note', 'agent_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_remboursement' => 'date',
        ];
    }


    public function beneficiaire(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'beneficiaire_id');
    }

    /** The payment this refund reverses — nullable (a refund isn't always tied to a tracked payment). */
    public function encaissement(): BelongsTo
    {
        return $this->belongsTo(Encaissement::class);
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
