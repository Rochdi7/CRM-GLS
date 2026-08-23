<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Physical chèque in hand — OFF-LEDGER inventory (never touches
 * caisses.solde by itself). Money only enters a till when the chèque is
 * used to pay: an Encaissement row with cheque_id pointing back here,
 * created through EnregistrerEncaissement as usual. "Reste" is derived
 * from those linked rows, mirroring Encaissement's own avance
 * applications() pattern.
 *
 * statut lifecycle: En possession -> Déposé (Remise à la banque) ->
 * Encaissé | Rejeté. Type "Garantie (À encaisser)" chèques are held as
 * security and may never be deposited at all.
 */
class Cheque extends Model
{
    use HasFactory;
    use Auditable;

    public const SOURCE_ETUDIANT = 'Étudiant';
    public const SOURCE_PARENTS = 'Parents';

    public const SOURCES = [
        self::SOURCE_ETUDIANT,
        self::SOURCE_PARENTS,
    ];

    public const TYPE_GARANTIE = 'Garantie (À encaisser)';
    public const TYPE_A_DEPOSER = 'À déposer';

    public const TYPES = [
        self::TYPE_GARANTIE,
        self::TYPE_A_DEPOSER,
    ];

    public const STATUT_EN_POSSESSION = 'En possession';
    public const STATUT_DEPOSE = 'Déposé';
    public const STATUT_ENCAISSE = 'Encaissé';
    public const STATUT_REJETE = 'Rejeté';

    public const STATUTS = [
        self::STATUT_EN_POSSESSION,
        self::STATUT_DEPOSE,
        self::STATUT_ENCAISSE,
        self::STATUT_REJETE,
    ];

    protected $fillable = [
        'reference', 'source', 'student_id', 'proprietaire_nom',
        'numero_cheque', 'montant', 'banque', 'date_reception',
        'type', 'date_echeance', 'statut', 'note',
        'etablissement_id', 'agent_id',
        'retourne_le', 'retourne_par_id',
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
        'statut' => self::STATUT_EN_POSSESSION,
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_reception' => 'date',
            'date_echeance' => 'date',
            'retourne_le' => 'datetime',
        ];
    }


    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'agent_id');
    }

    /** Employee who physically handed a rejected chèque back to its owner. */
    public function retournePar(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'retourne_par_id');
    }

    /** Payments made using this chèque — their montant sum is the used amount. */
    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    /** Display name of whoever wrote the chèque, regardless of source. */
    public function proprietaireLabel(): ?string
    {
        return $this->source === self::SOURCE_ETUDIANT
            ? $this->student?->nomComplet()
            : $this->proprietaire_nom;
    }

    public function montantUtilise(): float
    {
        return round((float) $this->encaissements()->sum('montant'), 2);
    }

    /** Rounded to the cent so paying the exact remaining balance is never refused. */
    public function montantRestant(): float
    {
        return round(max(0.0, (float) $this->montant - $this->montantUtilise()), 2);
    }

    public function estRetourne(): bool
    {
        return $this->retourne_le !== null;
    }
}
