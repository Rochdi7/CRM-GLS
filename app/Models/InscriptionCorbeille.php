<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une inscription SUPPRIMÉE, telle qu'elle était au moment de la
 * suppression, et QUI l'a supprimée (19/10/2026, onglet « Corbeille » de
 * la page Inscriptions).
 *
 * Append-only comme le journal d'audit : une ligne de corbeille est une
 * preuve — elle ne se modifie ni ne s'efface par l'application (même pour
 * un super-admin : la garde est au niveau du modèle, sous toute Gate).
 * Écrite UNIQUEMENT par Domain\Registrations\Actions\SupprimerInscription,
 * dans la même transaction que la suppression.
 */
class InscriptionCorbeille extends Model
{
    public const IMMUTABLE_MESSAGE = 'La corbeille des inscriptions est en lecture seule.';

    protected $table = 'inscriptions_corbeille';

    protected $fillable = [
        'inscription_id', 'reference', 'student_id', 'student_reference', 'student_nom',
        'etablissement_id', 'annee_scolaire_id', 'annee_nom', 'group_id', 'groupe_nom',
        'statut', 'date_inscription', 'montant_total', 'paiements_detaches',
        'supprime_par_user_id', 'supprime_par_label', 'supprime_le',
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'date',
            'supprime_le' => 'datetime',
            'paiements_detaches' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): bool {
            throw new \RuntimeException(self::IMMUTABLE_MESSAGE);
        });

        static::deleting(function (): bool {
            throw new \RuntimeException(self::IMMUTABLE_MESSAGE);
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function supprimePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supprime_par_user_id');
    }
}
