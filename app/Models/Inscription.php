<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Enrollment — Student × Group (gls-crm-schema.md §8).
 */
class Inscription extends Model
{
    use HasFactory;
    use LogsActivity;

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
        'reference', 'student_id', 'group_id', 'etablissement_id',
        'annee_scolaire_id', 'statut', 'date_inscription',
        'date_debut', 'date_fin', 'montant_total', 'note', 'created_by',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['student_id', 'group_id', 'statut', 'montant_total'])
            ->logOnlyDirty()
            ->useLogName('inscription');
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
