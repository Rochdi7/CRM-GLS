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

    /**
     * Approval workflow (Paramètres → Système « Validation des dépenses »).
     * "En attente" holds the money: the till is NOT debited until a
     * super-admin approves. Refusing never moves money at all.
     */
    public const STATUT_EN_ATTENTE = 'En attente';
    public const STATUT_APPROUVEE = 'Approuvée';
    public const STATUT_REFUSEE = 'Refusée';

    /**
     * Cancelled by COMPENSATING ENTRY (Domain\Expenses\Actions\AnnulerDepense,
     * 09/09/2026): an approved dépense whose money came BACK into the till
     * through a journaled CaisseLedger credit — a duplicate keyed twice, for
     * instance. The row is never deleted (§11): it stays listed with this
     * statut, and every read model that sums « money that left the tills »
     * already filters on STATUT_APPROUVEE, so it drops out of the totals
     * without any of them knowing the concept.
     */
    public const STATUT_ANNULEE = 'Annulée';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_APPROUVEE,
        self::STATUT_REFUSEE,
        self::STATUT_ANNULEE,
    ];

    /** Written into `note` by AnnulerDepense — the human-readable half of the trail. */
    public const MARQUEUR_ANNULE = '[ANNULÉE]';

    protected $fillable = [
        'reference', 'type_depense_id', 'caisse_id', 'group_id', 'montant',
        'methode_paiement', 'date_depense', 'periode_debut', 'periode_fin',
        'reference_facture',
        'description', 'mots_cles', 'note', 'agent_id',
        'statut', 'approved_by', 'approved_at', 'motif_refus',
    ];

    /**
     * Mirror of the column default (§11): without it a create() that omits
     * `statut` leaves the model NULL while the row holds 'Approuvée', so the
     * next change is journalled as « avant : vide » — the trail would then
     * state a false previous value, which is worse than a missing one.
     */
    protected $attributes = [
        'statut' => self::STATUT_APPROUVEE,
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_depense' => 'date',
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /** Awaiting a super-admin decision — no money has moved yet. */
    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    /** Approved: the till was debited when the decision was taken. */
    public function isApprouvee(): bool
    {
        return $this->statut === self::STATUT_APPROUVEE;
    }

    public function isRefusee(): bool
    {
        return $this->statut === self::STATUT_REFUSEE;
    }

    /** Reversed by a compensating credit — its money is back in the till. */
    public function isAnnulee(): bool
    {
        return $this->statut === self::STATUT_ANNULEE;
    }

    /**
     * A refused/approved expense is settled — only a pending one may still be
     * edited or decided upon.
     */
    public function isDecided(): bool
    {
        return ! $this->isEnAttente();
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

    /** The super-admin who approved or refused this expense. */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }
}
