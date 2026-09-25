<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-student roll-call line of a séance — unique per (seance, student).
 * statut is a plain VARCHAR validated against the constants below (same
 * deliberate pattern as every other statut field, gls-crm-schema.md).
 */
class Presence extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUT_PRESENT = 'Présent';

    public const STATUT_ABSENT = 'Absent';

    public const STATUT_RETARD = 'Retard';

    public const STATUT_JUSTIFIE = 'Justifié';

    public const STATUTS = [
        self::STATUT_PRESENT,
        self::STATUT_ABSENT,
        self::STATUT_RETARD,
        self::STATUT_JUSTIFIE,
    ];

    /**
     * Les statuts qu'un appel peut ENCORE écrire (25/09/2026) : « Retard » est
     * retiré du système. La constante et `STATUTS` restent — des lignes
     * héritées de l'ancien import la portent en base, et on n'efface pas
     * l'historique — mais plus aucune saisie ni import n'en produit.
     */
    public const STATUTS_SAISISSABLES = [
        self::STATUT_PRESENT,
        self::STATUT_ABSENT,
        self::STATUT_JUSTIFIE,
    ];

    /**
     * Compteurs par statut d'une liste d'appels : les statuts saisissables
     * toujours, un statut retiré (« Retard ») seulement s'il reste des lignes
     * héritées — sinon les pastilles ne s'additionneraient plus au total.
     *
     * @param  iterable<object{statut: string}>  $rows
     * @return array<string, int>
     */
    public static function compteurs(iterable $rows): array
    {
        $compteurs = array_fill_keys(self::STATUTS_SAISISSABLES, 0);

        foreach ($rows as $row) {
            $statut = (string) $row->statut;
            $compteurs[$statut] = ($compteurs[$statut] ?? 0) + 1;
        }

        return $compteurs;
    }

    protected $fillable = [
        'seance_id', 'student_id', 'statut', 'note',
    ];

    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
