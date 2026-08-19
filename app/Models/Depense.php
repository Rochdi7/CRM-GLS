<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Expense — cash outflow from a till (gls-crm-schema.md §13).
 * mots_cles = comma-separated free-text tags by design (no tags table).
 */
class Depense extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Auditable;

    /** Same fixed list as Encaissement::METHODES (validated, no lookup table). */
    public const METHODES = Encaissement::METHODES;

    protected $fillable = [
        'reference', 'type_depense_id', 'caisse_id', 'group_id', 'montant',
        'methode_paiement', 'date_depense', 'reference_facture',
        'description', 'mots_cles', 'note', 'agent_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_depense' => 'date',
        ];
    }

    /**
     * Media: "justificatifs" — receipts/invoices backing this expense
     * (fraud traceability). URLs served from /media/<8-char-uuid>/….
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('justificatifs')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }


    public function typeDepense(): BelongsTo
    {
        return $this->belongsTo(TypeDepense::class, 'type_depense_id');
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    /** Optional link to the class/group this expense belongs to. */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'agent_id');
    }
}
