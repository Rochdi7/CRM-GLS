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

    /**
     * « Archivée » (19/10/2026) : dossier mis en PAUSE — l'étudiant
     * interrompt ce cours et compte revenir. Porte un motif et une date de
     * retour (`archive_*`), s'écrit UNIQUEMENT par
     * Domain\Registrations\Actions\ArchiverInscription (depuis `Active`) et
     * revient `Active` par ReactiverInscription. Rien d'autre ne bouge :
     * aucun frais masqué, aucun paiement libéré. Valeur historique de
     * l'import legacy aussi (lignes sans motif ni date).
     */
    public const STATUT_ARCHIVEE = 'Archivée';

    /**
     * Dossier clos par un transfert de l'étudiant vers un AUTRE centre
     * (25/09/2026, ValiderTransfertEtudiant) : ses frais payés et leur
     * argent sont partis sur le nouveau dossier du centre d'arrivée, ses
     * frais impayés sont masqués. Écrit par le code seulement — jamais
     * sélectionnable à la main — et clos comme « Changement » pour tout
     * ce qui lit un statut (recouvrement, encaissement, tableau de bord).
     */
    public const STATUT_TRANSFEREE = 'Transférée';

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
        'archive_motif', 'archive_date_retour', 'archive_le', 'archive_par_user_id',
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
            'archive_date_retour' => 'date',
            'archive_le' => 'datetime',
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

    /** Dossier en pause (STATUT_ARCHIVEE) — retour prévu à `archive_date_retour`. */
    public function estArchivee(): bool
    {
        return $this->statut === self::STATUT_ARCHIVEE;
    }

    /** Qui a archivé le dossier (NULL = compte supprimé depuis, ou ligne legacy). */
    public function archivePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archive_par_user_id');
    }
}
