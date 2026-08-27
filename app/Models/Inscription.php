<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Enrollment — Student × Group (gls-crm-schema.md §8).
 */
class Inscription extends Model
{
    use HasFactory;
    use Auditable;

    public const STATUT_ACTIVE = 'Active';

    public const STATUT_ANNULEE = 'Annulée';

    public const STATUT_CHANGEMENT = 'Changement';

    // Deprecated values kept for backward compatibility with existing rows;
    // not offered in the UI anymore.
    public const STATUT_EXPIREE = 'Expirée';

    public const STATUT_ARCHIVEE = 'Archivée';

    /** The only statuses selectable in the UI. */
    public const STATUTS = [
        self::STATUT_ACTIVE,
        self::STATUT_ANNULEE,
        self::STATUT_CHANGEMENT,
    ];

    protected $fillable = [
        'reference', 'legacy_ref', 'legacy_source', 'student_id', 'group_id', 'etablissement_id',
        'annee_scolaire_id', 'statut', 'motif_annulation', 'date_inscription',
        'date_debut', 'date_fin', 'montant_total', 'note', 'created_by',
    ];

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
        'statut' => self::STATUT_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'date',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'montant_total' => 'decimal:2',
        ];
    }


    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(InscriptionFee::class);
    }

    /** Books (StockArticle rows) assigned to this registration — see AssignerLivresInscription. */
    public function livres(): HasMany
    {
        return $this->hasMany(InscriptionLivre::class);
    }

    /** Set only when this inscription was archived via a group change. */
    public function historique(): HasOne
    {
        return $this->hasOne(InscriptionHistorique::class);
    }
}
