<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Demande de virement bancaire (07/10/2026) — OFF-LEDGER tant qu'elle n'est
 * pas validée.
 *
 * Un virement n'est plus un encaissement saisi directement : l'étudiant se
 * présente avec la preuve de son virement, le guichet DÉCLARE la demande
 * (payeur, référence bancaire, justificatif, frais réglé), et le comptable
 * la VÉRIFIE. Une ligne ici ne touche JAMAIS `caisses.solde` par elle-même :
 * l'argent n'entre que par l'encaissement créé à la validation
 * (ValiderVirement → EnregistrerEncaissement), lié par `encaissement_id`.
 *
 * `date_operation` est le jour où l'étudiant s'est présenté — c'est la date
 * de paiement de l'encaissement, jamais le jour de la décision.
 *
 * Media : `justificatif` (capture / scan de l'ordre de virement, obligatoire).
 */
class Virement extends Model implements HasMedia
{
    use Auditable;
    use InteractsWithMedia;

    public const MEDIA_JUSTIFICATIF = 'justificatif';

    /** Accepted upload extensions — mirrors registerMediaCollections(). */
    public const MEDIA_MIMES = ['jpeg', 'jpg', 'png', 'webp', 'pdf'];

    public const MEDIA_MAX_KB = 5120;

    public const STATUT_EN_ATTENTE = 'En attente de vérification';
    public const STATUT_VALIDE = 'Validé';
    public const STATUT_REFUSE = 'Refusé';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDE,
        self::STATUT_REFUSE,
    ];

    protected $fillable = [
        'reference', 'student_id', 'inscription_id', 'inscription_fee_id',
        'encaissement_id', 'etablissement_id', 'montant', 'nom_payeur',
        'reference_virement', 'date_operation', 'statut', 'note',
        'motif_refus', 'demande_par_id', 'decide_par_id', 'decide_le',
    ];

    /**
     * Mirror of the column default, so a freshly created row and the model in
     * memory agree (otherwise the first decision is journalled as
     * « avant : vide » — CLAUDE.md §11 « Audit log »).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => self::STATUT_EN_ATTENTE,
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_operation' => 'date',
            'decide_le' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_JUSTIFICATIF)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(InscriptionFee::class, 'inscription_fee_id');
    }

    public function encaissement(): BelongsTo
    {
        return $this->belongsTo(Encaissement::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function demandePar(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'demande_par_id');
    }

    public function decidePar(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'decide_par_id');
    }

    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isDecided(): bool
    {
        return ! $this->isEnAttente();
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }
}
