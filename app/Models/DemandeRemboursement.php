<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de remboursement (02/11/2026) — OFF-LEDGER tant qu'elle n'est pas
 * approuvée.
 *
 * Une ligne ici ne touche JAMAIS `caisses.solde` : l'argent ne sort que par
 * le remboursement créé à l'approbation (ApprouverDemandeRemboursement →
 * EnregistrerRemboursement), lié par `remboursement_id`. Refusée, la demande
 * reste listée avec son motif — jamais supprimée.
 */
class DemandeRemboursement extends Model
{
    use Auditable;

    protected $table = 'demandes_remboursement';

    public const STATUT_EN_ATTENTE = 'En attente';
    public const STATUT_APPROUVEE = 'Approuvée';
    public const STATUT_REFUSEE = 'Refusée';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_APPROUVEE,
        self::STATUT_REFUSEE,
    ];

    /**
     * Les méthodes qu'une approbation peut choisir. Espèces = la caisse
     * physique du demandeur ; TPE / Virement = le compte de cette méthode du
     * centre de la demande. Jamais « Chèque » : la seule sortie du compte
     * Chèque est la reprise d'un chèque rejeté, que CaisseResolver décide
     * seul (forRemboursement), sous la méthode Espèces.
     */
    public const METHODES = [
        Encaissement::METHODE_ESPECES,
        Encaissement::METHODE_TPE,
        Encaissement::METHODE_VIREMENT,
    ];

    protected $fillable = [
        'reference', 'beneficiaire_id', 'encaissement_id', 'encaissement_reference',
        'etablissement_id', 'montant', 'motif', 'note', 'statut', 'methode',
        'remboursement_id', 'motif_refus', 'demande_par_id', 'decide_par_id', 'decide_le',
    ];

    /**
     * Mirror of the column default (CLAUDE.md §11 « Audit log »): without it
     * the first decision is journalled as « avant : vide ».
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
            'decide_le' => 'datetime',
        ];
    }

    public function beneficiaire(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'beneficiaire_id');
    }

    public function encaissement(): BelongsTo
    {
        return $this->belongsTo(Encaissement::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function remboursement(): BelongsTo
    {
        return $this->belongsTo(Remboursement::class);
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

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('demandes_remboursement.statut', self::STATUT_EN_ATTENTE);
    }
}
