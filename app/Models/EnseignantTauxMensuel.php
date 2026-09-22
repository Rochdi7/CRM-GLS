<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Montant par étudiant d'un enseignant pour UN mois civil — mode « win-win »
 * (`Employee::MODE_PAIEMENT_WIN_WIN`).
 *
 * Le principe du win-win : le montant par étudiant PROGRESSE d'un mois à
 * l'autre (400 → 420 → 450 → … → 600), saisi à la main sur la fiche de
 * l'enseignant. Le calcul de paie lit la ligne du mois demandé ; si elle
 * n'existe pas, il REFUSE et le dit — il ne prend jamais le mois voisin ni
 * le taux GLS à la place, sinon l'enseignant est payé sur un chiffre que
 * personne n'a décidé.
 *
 * `mois` est TOUJOURS le 1er du mois (normalisé à l'écriture) : une date
 * plutôt qu'un couple (mois, année), pour trier et borner en SQL.
 *
 * Audité (`Auditable`) : un taux de paie qui change est exactement ce que le
 * journal doit pouvoir expliquer.
 */
class EnseignantTauxMensuel extends Model
{
    use Auditable;

    protected $table = 'enseignant_taux_mensuels';

    protected $fillable = ['employee_id', 'mois', 'montant_par_etudiant'];

    protected function casts(): array
    {
        return [
            'mois' => 'date',
            'montant_par_etudiant' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
