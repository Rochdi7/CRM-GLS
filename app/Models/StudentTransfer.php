<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de transfert d'un étudiant d'un centre vers un autre (25/09/2026).
 *
 * Deux temps, comme un transfert de caisse : la DEMANDE (front office du
 * centre source, avec le groupe d'affectation dans le centre cible) ne
 * change rien ; la DÉCISION appartient au super-admin. À la validation,
 * `ValiderTransfertEtudiant` copie la fiche dans le centre cible, clôture
 * « Transférée » les dossiers Actifs du centre source, ouvre le nouveau
 * dossier Actif dans le groupe cible et y emporte tout l'argent de
 * l'étudiant. Les présences restent sur la fiche d'origine.
 */
class StudentTransfer extends Model
{
    use Auditable;

    public const STATUT_EN_ATTENTE = 'En attente';

    public const STATUT_VALIDE = 'Validé';

    public const STATUT_REFUSE = 'Refusé';

    public const STATUT_ANNULE = 'Annulé';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDE,
        self::STATUT_REFUSE,
        self::STATUT_ANNULE,
    ];

    protected $fillable = [
        'reference', 'student_id', 'nouveau_student_id',
        'etablissement_source_id', 'etablissement_cible_id',
        'group_cible_id', 'nouvelle_inscription_id',
        'statut', 'motif', 'motif_decision', 'montant_transfere',
        'requested_by', 'decided_by', 'decided_at',
    ];

    /** Mirror of the column default (§11) so the journal never records « avant : vide ». */
    protected $attributes = [
        'statut' => self::STATUT_EN_ATTENTE,
    ];

    protected function casts(): array
    {
        return [
            'montant_transfere' => 'decimal:2',
            'decided_at' => 'datetime',
        ];
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    /** La fiche d'origine (centre source). */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** La copie créée dans le centre cible à la validation. */
    public function nouveauStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'nouveau_student_id');
    }

    public function etablissementSource(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class, 'etablissement_source_id');
    }

    public function etablissementCible(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class, 'etablissement_cible_id');
    }

    public function groupeCible(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_cible_id');
    }

    public function nouvelleInscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'nouvelle_inscription_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
