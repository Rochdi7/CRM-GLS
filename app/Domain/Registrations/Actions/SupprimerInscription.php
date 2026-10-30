<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionCorbeille;
use App\Models\Remboursement;
use App\Models\User;
use App\Models\Virement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ⚠ Supprimer une inscription dont TOUT l'argent a été remboursé
 * (04/10/2026).
 *
 * Une inscription qui a reçu un paiement ne se supprimait jamais : la ligne
 * `encaissements.inscription_fee_id` (restrictOnDelete) la retenait, même
 * quand ce paiement avait été rendu en entier à l'étudiant. Le dossier
 * restait alors impossible à retirer alors qu'il ne porte plus un dirham.
 *
 * Désormais, si CHAQUE paiement posé sur ses frais est intégralement
 * remboursé (remboursements non annulés ≥ montant), la suppression passe :
 * ces paiements sont DÉTACHÉS du frais (`inscription_fee_id` → NULL) et
 * restent sur la fiche de l'ÉTUDIANT (`student_id` inchangé), toujours
 * reliés à leur remboursement (`remboursements.encaissement_id` inchangé,
 * `beneficiaire_id` inchangé). Ils deviennent des avances entièrement
 * utilisées (reste 0,00). AUCUN argent ne bouge : ni `montant`, ni
 * `caisse_id`, ni `caisses.solde`, ni aucune ligne `remboursements` — les
 * remboursements déjà saisis ne sont ni lus pour écriture ni réécrits.
 *
 * Toujours refusé (le dossier se clôture alors par « Annuler ») :
 *  - un paiement non remboursé ou remboursé en PARTIE — l'école garde de
 *    l'argent sur ce dossier ;
 *  - une ligne d'APPLICATION d'avance — son argent appartient à l'avance
 *    parente ;
 *  - un paiement qui a lui-même financé des applications ;
 *  - une demande de virement encore « En attente de vérification » (30/10/2026).
 *    Une demande DÉCIDÉE (validée / refusée) est détachée du dossier et reste
 *    lisible sur la page Virements.
 *
 * Le contrôle et le détachement ont lieu dans la MÊME transaction, sur des
 * lignes verrouillées FOR UPDATE (§11) : un paiement saisi entre-temps fait
 * échouer la suppression au lieu de rester accroché à un frais effacé.
 * Chaque détachement passe par `save()` (journalisé par Auditable) et la
 * suppression écrit une entrée qui nomme le dossier, les paiements et leurs
 * frais — une fois l'inscription effacée, c'est la seule trace du lien.
 *
 * Depuis le 19/10/2026 la suppression écrit AUSSI une ligne dans
 * `inscriptions_corbeille` (append-only), dans la même transaction : ce
 * qu'était le dossier et QUI l'a supprimé — c'est ce que l'onglet
 * « Corbeille » de la page Inscriptions liste et compte par personne.
 * `$supprimePar` est le compte qui agit (le contrôleur passe
 * `$request->user()`) ; un appel console sans utilisateur laisse l'auteur
 * vide, jamais deviné.
 */
final class SupprimerInscription
{
    public function __construct(private readonly AssignerLivresInscription $assignerLivres) {}

    public function handle(Inscription $inscription, ?Employee $agent, ?User $supprimePar = null): void
    {
        DB::transaction(function () use ($inscription, $agent, $supprimePar): void {
            $encaissements = Encaissement::query()
                ->with('fee')
                ->whereIn('inscription_fee_id', $inscription->fees()->select('id'))
                ->lockForUpdate()
                ->get();

            foreach ($encaissements as $encaissement) {
                if (! $this->estIntegralementRembourse($encaissement)) {
                    throw ValidationException::withMessages([
                        'delete' => __('This registration has payments that were not fully refunded and cannot be deleted. Cancel it instead.'),
                    ]);
                }
            }

            // Demandes de virement (30/10/2026). Une demande EN ATTENTE porte
            // de l'argent que le comptable n'a pas encore vérifié : elle se
            // valide ou se refuse d'abord, jamais elle ne perd son dossier en
            // silence. Une demande DÉCIDÉE n'a plus rien à faire ici — son
            // argent, s'il est entré, est l'encaissement contrôlé ci-dessus —
            // elle est détachée (journalisée) et reste lisible sur la page
            // Virements. Sans ce détachement, la contrainte RESTRICT d'origine
            // bloquait le dossier pour toujours (INS-8146).
            $virements = Virement::query()
                ->where('inscription_id', $inscription->id)
                ->lockForUpdate()
                ->get();

            $enAttente = $virements->filter(fn (Virement $v): bool => $v->isEnAttente());

            if ($enAttente->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'delete' => __('This registration has bank transfer requests awaiting verification (:refs). Validate or refuse them first.', [
                        'refs' => $enAttente->pluck('reference')->implode(', '),
                    ]),
                ]);
            }

            $virementsDetaches = [];

            foreach ($virements as $virement) {
                $virementsDetaches[] = [
                    'reference' => $virement->reference,
                    'statut' => $virement->statut,
                    'montant' => number_format((float) $virement->montant, 2, '.', ''),
                    'encaissement_id' => $virement->encaissement_id,
                ];

                $virement->inscription_id = null;
                $virement->inscription_fee_id = null;
                $virement->save();
            }

            $detaches = [];

            foreach ($encaissements as $encaissement) {
                $remboursements = Remboursement::query()
                    ->where('encaissement_id', $encaissement->id)
                    ->nonAnnules()
                    ->get(['reference', 'montant', 'date_remboursement']);

                $detaches[] = [
                    'reference' => $encaissement->reference,
                    'encaissement_id' => $encaissement->id,
                    'montant' => number_format((float) $encaissement->montant, 2, '.', ''),
                    'date_paiement' => $encaissement->date_paiement?->toDateString(),
                    'frais' => $encaissement->fee?->nom,
                    'rembourse' => number_format((float) $remboursements->sum('montant'), 2, '.', ''),
                    'remboursements' => $remboursements->pluck('reference')->all(),
                ];

                $encaissement->inscription_fee_id = null;
                $encaissement->save();
            }

            // Books handed out with this registration go back to the shelf
            // FIRST (one « Entrée » movement each) — the FK cascade alone
            // deleted the assignment and left the stock one short forever
            // (audit DB-02).
            $this->assignerLivres->handle($inscription, [], $agent);

            // Le dossier disparaît de la table, mais pas de l'HISTORIQUE de
            // l'étudiant : cette entrée (append-only) fige tout ce que la
            // fiche affiche ensuite sous « Inscriptions supprimées » — une
            // fois la ligne effacée, c'est la seule trace du groupe, de
            // l'année et du lien entre ces paiements et ce dossier.
            $inscription->loadMissing(['group:id,nom', 'anneeScolaire:id,nom']);

            activity('inscription')
                ->performedOn($inscription)
                ->event('inscription_deleted')
                ->withProperties([
                    'inscription_reference' => $inscription->reference,
                    'etudiant_id' => (string) $inscription->student_id,
                    'groupe' => $inscription->group?->nom,
                    'annee_scolaire' => $inscription->anneeScolaire?->nom,
                    'date_inscription' => $inscription->date_inscription?->toDateString(),
                    'statut' => $inscription->statut,
                    'montant_total' => $inscription->montant_total !== null
                        ? number_format((float) $inscription->montant_total, 2, '.', '')
                        : null,
                    'paiements_detaches' => $detaches,
                    'virements_detaches' => $virementsDetaches,
                ])
                ->log($detaches === []
                    ? sprintf('Inscription %s supprimée', $inscription->reference)
                    : sprintf(
                        'Inscription %s supprimée : %d paiement(s) intégralement remboursé(s) conservé(s) sur la fiche de l\'étudiant',
                        $inscription->reference,
                        count($detaches),
                    ));

            $this->deposerDansLaCorbeille($inscription, $detaches, $supprimePar);

            $inscription->delete();
        });
    }

    /**
     * La ligne de corbeille : une photographie du dossier + l'auteur, figée
     * (les noms sont recopiés, les FK sont en SET NULL — la ligne doit
     * survivre à la disparition du groupe, de l'année ou du compte).
     *
     * @param  list<array<string, mixed>>  $detaches
     */
    private function deposerDansLaCorbeille(Inscription $inscription, array $detaches, ?User $supprimePar): void
    {
        $inscription->loadMissing(['student:id,reference,nom,prenom']);

        $label = null;
        if ($supprimePar !== null) {
            $name = trim((string) $supprimePar->name);
            $handle = $supprimePar->username ?: $supprimePar->email;
            $label = $name !== '' ? ($handle ? "{$name} ({$handle})" : $name) : ($handle ?: null);
        }

        InscriptionCorbeille::create([
            'inscription_id' => $inscription->id,
            'reference' => $inscription->reference,
            'student_id' => $inscription->student_id,
            'student_reference' => $inscription->student?->reference,
            'student_nom' => $inscription->student?->nomComplet(),
            'etablissement_id' => $inscription->etablissement_id,
            'annee_scolaire_id' => $inscription->annee_scolaire_id,
            'annee_nom' => $inscription->anneeScolaire?->nom,
            'group_id' => $inscription->group_id,
            'groupe_nom' => $inscription->group?->nom,
            'statut' => $inscription->statut,
            'date_inscription' => $inscription->date_inscription?->toDateString(),
            'montant_total' => $inscription->montant_total,
            'paiements_detaches' => $detaches === [] ? null : $detaches,
            'supprime_par_user_id' => $supprimePar?->id,
            'supprime_par_label' => $label !== null ? mb_substr($label, 0, 200) : null,
            'supprime_le' => now(),
        ]);
    }

    private function estIntegralementRembourse(Encaissement $encaissement): bool
    {
        if ($encaissement->applied_from_encaissement_id !== null) {
            return false;
        }

        if ($encaissement->applications()->exists()) {
            return false;
        }

        $rembourse = (float) Remboursement::query()
            ->where('encaissement_id', $encaissement->id)
            ->nonAnnules()
            ->sum('montant');

        return round($rembourse, 2) >= round((float) $encaissement->montant, 2);
    }
}
