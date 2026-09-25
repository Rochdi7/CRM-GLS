<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Fee line item owed for an enrollment (gls-crm-schema.md §9),
 * e.g. "Frais d'inscription", "Frais de Juillet". Payments allocate per-fee.
 */
class InscriptionFee extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUT_NON_PAYE = 'Non payé';

    public const STATUT_PAYE_PARTIELLEMENT = 'Payé partiellement';

    public const STATUT_PAYE = 'Payé';

    public const STATUTS = [
        self::STATUT_NON_PAYE,
        self::STATUT_PAYE_PARTIELLEMENT,
        self::STATUT_PAYE,
    ];

    protected $fillable = [
        'inscription_id', 'frais_id', 'nom',
        'montant_initial', 'remise_pct', 'remise_montant', 'montant',
        'date_echeance', 'note', 'statut', 'masque_le', 'masque_origine',
    ];

    /**
     * Mirror of the column default, so a freshly created row and the model in
     * memory agree.
     *
     * The database defaults `statut` to 'Non payé', but a create() that omits
     * the key left the PHP model holding NULL while the row held 'Non payé'.
     * A later status change then recorded « avant : (vide) » in the audit
     * journal — the trail claimed the fee came from nothing when it actually
     * came from « Non payé », which is a false statement of history, not just
     * a display quirk. Declaring the default here fixes every creation path at
     * once rather than each caller remembering to pass it.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => self::STATUT_NON_PAYE,
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'montant_initial' => 'decimal:2',
            'remise_pct' => 'decimal:2',
            'remise_montant' => 'decimal:2',
            'date_echeance' => 'date',
            'masque_le' => 'datetime',
        ];
    }

    /** Who hid the line: the group (RetirerFraisGroupe) or a person (hideFee). */
    public const MASQUE_ORIGINE_GROUPE = 'groupe';

    public const MASQUE_ORIGINE_MANUEL = 'manuel';

    /**
     * Masquée par le transfert de l'étudiant vers un autre centre
     * (ValiderTransfertEtudiant) : la créance impayée n'est plus due ici,
     * le dossier étant clos « Transférée ».
     */
    public const MASQUE_ORIGINE_TRANSFERT = 'transfert';

    public function estMasque(): bool
    {
        return $this->masque_le !== null;
    }

    /**
     * Final amount after discount: initial − (pct% of initial) OR − fixed DH.
     */
    public static function computeMontant(float $initial, ?float $remisePct, ?float $remiseMontant): float
    {
        if ($remisePct !== null && $remisePct > 0) {
            return round($initial * (1 - min($remisePct, 100) / 100), 2);
        }

        return round(max(0, $initial - (float) ($remiseMontant ?? 0)), 2);
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function frais(): BelongsTo
    {
        return $this->belongsTo(Frais::class);
    }

    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    /**
     * Les remboursements NON annulés des paiements posés sur ce frais.
     *
     * @return HasManyThrough<Remboursement, Encaissement, $this>
     */
    public function remboursements(): HasManyThrough
    {
        return $this->hasManyThrough(Remboursement::class, Encaissement::class, 'inscription_fee_id', 'encaissement_id')
            ->nonAnnules();
    }

    /**
     * ⚠ PAYÉ = ENCAISSÉ − REMBOURSÉ (24/09/2026). La SEULE définition de
     * « combien de ce frais est réglé », partagée par les actions (ligne
     * verrouillée) et, via `scopeAvecPayeNet()` / `payeNet()`, par toutes
     * les listes.
     *
     * Avant, seul l'encaissé comptait : un paiement remboursé en entier
     * laissait son frais « Payé » alors que l'argent était reparti (ENC-26191,
     * 1 200 DH, remboursé par RMB-003 le 08/09/2026 — le frais de Septembre
     * de LOUBNA SOUILH restait « Payé » sur la fiche, la matrice et le
     * recouvrement). Un remboursement ANNULÉ ne compte pas : sa caisse a été
     * recréditée, l'argent est revenu.
     *
     * Jamais négatif — un remboursement ne dépasse jamais son paiement
     * (EnregistrerRemboursement le borne), le plancher n'est qu'une ceinture.
     */
    public function montantPaye(): float
    {
        return max(0.0, round(
            (float) $this->encaissements()->sum('montant') - (float) $this->remboursements()->sum('remboursements.montant'),
            2,
        ));
    }

    /**
     * Charge, EN LOT, les deux sommes que `payeNet()` lit — pour les listes,
     * qui ne doivent jamais appeler `montantPaye()` ligne par ligne (§17).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    public function scopeAvecPayeNet($query): void
    {
        $query->withSum('encaissements', 'montant')
            ->withSum('remboursements', 'montant');
    }

    /**
     * Le payé NET d'une ligne chargée par `scopeAvecPayeNet()` ; retombe sur
     * `montantPaye()` (une requête) si les sommes n'ont pas été chargées.
     */
    public function payeNet(): float
    {
        if (! array_key_exists('encaissements_sum_montant', $this->attributes)) {
            return $this->montantPaye();
        }

        return max(0.0, round(
            (float) ($this->attributes['encaissements_sum_montant'] ?? 0)
            - (float) ($this->attributes['remboursements_sum_montant'] ?? 0),
            2,
        ));
    }

    /**
     * Recalcule le statut stocké depuis le payé net — la définition des
     * `recalculerStatutFee()` des actions, appelée ici par les remboursements
     * (création et annulation), qui changent le payé sans toucher au frais.
     */
    public function rafraichirStatut(): void
    {
        $paye = $this->montantPaye();

        $this->update([
            'statut' => match (true) {
                $paye >= (float) $this->montant => self::STATUT_PAYE,
                $paye > 0 => self::STATUT_PAYE_PARTIELLEMENT,
                default => self::STATUT_NON_PAYE,
            },
        ]);
    }
}
