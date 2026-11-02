<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\DemandeRemboursement;
use App\Models\Employee;
use App\Models\Encaissement;
use Illuminate\Support\Facades\DB;

/**
 * DEMANDE de remboursement (02/11/2026, « Validation des remboursements »
 * active) — AUCUN argent ne bouge.
 *
 * La demande porte ce que le remboursement portera : étudiant, paiement
 * remboursé (ou aucun), montant, motif, note, et le centre auquel le
 * remboursement appartiendra (EnregistrerRemboursement::centreDuRemboursement,
 * la même règle). Ni caisse ni méthode : elles ne se décident qu'à
 * l'approbation (ApprouverDemandeRemboursement).
 *
 * Un paiement lié est contrôlé SOUS VERROU par la même règle que
 * l'approbation (assertPaiementRemboursable), moins ce que d'autres demandes
 * en attente réclament déjà sur ce paiement — sinon deux demandes pourraient
 * promettre deux fois le même argent, et la seconde approbation tomberait sur
 * un refus que personne n'attendait. L'approbation rejoue tout de toute
 * façon : cette garde évite le mauvais signal, elle n'est pas l'autorité.
 */
final class DemanderRemboursement
{
    public function __construct(
        private readonly EnregistrerRemboursement $enregistrer,
    ) {}

    /**
     * @param array<string, mixed> $data validated StoreRemboursementRequest data
     */
    public function handle(array $data, Employee $demandeur): DemandeRemboursement
    {
        return DB::transaction(function () use ($data, $demandeur): DemandeRemboursement {
            $encaissement = null;
            $montant = round((float) $data['montant'], 2);

            if (! empty($data['encaissement_id'])) {
                $encaissement = Encaissement::query()
                    ->whereKey((int) $data['encaissement_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->enregistrer->assertPaiementRemboursable(
                    $encaissement,
                    (int) $data['beneficiaire_id'],
                    $montant,
                    self::enAttenteSur($encaissement->id),
                );
            }

            $demande = DemandeRemboursement::create([
                'reference' => ReferenceGenerator::make('DRB', 'demandes_remboursement'),
                'beneficiaire_id' => (int) $data['beneficiaire_id'],
                'encaissement_id' => $encaissement?->id,
                'encaissement_reference' => $encaissement?->reference,
                'etablissement_id' => $this->enregistrer->centreDuRemboursement($encaissement, (int) $data['beneficiaire_id'], $demandeur),
                'montant' => $montant,
                'motif' => $data['motif'] ?? null,
                'note' => $data['note'] ?? null,
                'statut' => DemandeRemboursement::STATUT_EN_ATTENTE,
                'demande_par_id' => $demandeur->id,
            ]);

            activity('demande_remboursement')
                ->performedOn($demande)
                ->event('demande_remboursement_creee')
                ->withProperties([
                    'reference' => $demande->reference,
                    'montant' => number_format($montant, 2, '.', ''),
                    'encaissement_reference' => $demande->encaissement_reference,
                    'etablissement_id' => $demande->etablissement_id,
                ])
                ->log("Demande de remboursement {$demande->reference} créée");

            return $demande;
        });
    }

    /** Ce que les demandes EN ATTENTE réclament déjà sur ce paiement. */
    public static function enAttenteSur(int $encaissementId, ?int $saufDemandeId = null): float
    {
        return round((float) DemandeRemboursement::query()
            ->enAttente()
            ->where('encaissement_id', $encaissementId)
            ->when($saufDemandeId !== null, fn ($q) => $q->whereKeyNot($saufDemandeId))
            ->sum('montant'), 2);
    }
}
