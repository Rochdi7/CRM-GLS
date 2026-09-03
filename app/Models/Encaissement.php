<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Payments\Support\ResoudreAllocationsAvance;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payment received from a student (gls-crm-schema.md §11).
 * Cheque data is inline (numero_cheque / banque / date_echeance_cheque),
 * populated only when methode = Chèque.
 *
 * inscription_fee_id is nullable: a row with no fee is an "avance" — money
 * received but not yet allocated. Applying an avance to a fee creates a
 * SECOND Encaissement row (via AppliquerAvance) whose
 * applied_from_encaissement_id points back at the avance; the avance row
 * itself is never edited (money records are append-only, CLAUDE.md §11).
 *
 * A fee-attached row can later be DETACHED from its fee (inscription_fee_id
 * set back to null by ConvertirEncaissementsEnAvance or
 * ChangerGroupeInscription) — it then counts as an avance again, whether or
 * not it was itself an "apply" row: a detached apply row keeps its
 * applied_from link (so the parent avance's used amount stays correct) while
 * its own montant becomes re-allocatable through its own applications().
 */
class Encaissement extends Model
{
    use HasFactory;
    use Auditable;

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
        'reference', 'legacy_ref', 'legacy_source', 'etablissement_id', 'student_id', 'inscription_fee_id', 'applied_from_encaissement_id', 'cheque_id', 'montant', 'methode',
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


    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The student's centre at the time of payment — dedupe scope for
     * legacy refs (unique per centre, see migration
     * create_encaissements_table). Money routing never
     * reads it: caisse_id is the account.
     */
    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(InscriptionFee::class, 'inscription_fee_id');
    }

    /** The avance this row applies, when it is itself an "apply" row (not a fresh avance). */
    public function appliedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'applied_from_encaissement_id');
    }

    /** The tracked chèque this payment was made with (Cheques module), when any. */
    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }

    /** Rows that have applied THIS avance to a fee — sum('montant') is the avance's used amount. */
    public function applications(): HasMany
    {
        return $this->hasMany(self::class, 'applied_from_encaissement_id');
    }

    public function isAvance(): bool
    {
        return $this->inscription_fee_id === null;
    }

    /**
     * Money of this avance that is no longer available: applied to fees
     * PLUS refunded to the student. Without the refund term an avance that
     * was paid back in cash would still show its full "restant" and could
     * be applied to a fee a second time — the student would get the money
     * twice.
     */
    public function montantUtilise(): float
    {
        return round(
            (float) $this->applications()->sum('montant') + (float) $this->remboursements()->sum('montant'),
            2,
        );
    }

    /**
     * Le libelle des frais que cet argent a payes — la SEULE definition,
     * partagee par les QUATRE recus : imprime (recu.blade), groupe
     * (recu-groupe.blade), PDF/WhatsApp et email.
     *
     * Un encaissement attache a un frais le nomme directement. Une AVANCE n'a
     * pas de frais au moment de l'encaissement, mais des qu'elle est appliquee
     * l'argent a paye des frais precis : c'est cela que l'etudiant doit lire
     * sur son recu, « Avance » ne lui apprend rien (signale 31/08/2026). On ne
     * retombe sur « Avance » que tant que l'argent reste non alloue.
     *
     * Les relations chargees (`applications.fee`) suffisent tant qu'aucune
     * application n'a ete RECONVERTIE : une application detachee (fee NULL)
     * peut avoir ete re-appliquee via sa propre ligne fille, et seule
     * `ResoudreAllocationsAvance` (la definition unique de la chaine, partagee
     * avec la liste et la page detail) sait ou cet argent est finalement alle.
     * Ce cas rare paie une requete par niveau ; le cas courant n'en fait aucune.
     */
    public function libelleFrais(): string
    {
        if ($this->fee?->nom) {
            return $this->fee->nom;
        }

        $applications = $this->applications->sortBy('id');

        $noms = $applications->contains(fn (self $application): bool => $application->inscription_fee_id === null)
            ? collect(ResoudreAllocationsAvance::terminales([$this->id])[$this->id] ?? [])
                ->filter(fn (array $allocation): bool => $allocation['kind'] === ResoudreAllocationsAvance::KIND_FRAIS)
                ->map(fn (array $allocation): ?string => $allocation['row']->fee?->nom)
            : $applications->map(fn (self $application): ?string => $application->fee?->nom);

        $noms = $noms->filter()->unique()->values();

        return $noms->isEmpty() ? 'Avance' : $noms->implode(' + ');
    }

    /** Rounded to the cent so the exact last cents can always be applied. */
    public function montantRestant(): float
    {
        return round(max(0.0, (float) $this->montant - $this->montantUtilise()), 2);
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'agent_id');
    }

    /** Refunds recorded against this payment (Remboursement.encaissement_id). */
    public function remboursements(): HasMany
    {
        return $this->hasMany(Remboursement::class);
    }
}
