<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Seance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ⚠ L'EXCEPTION à « un groupe ne se supprime jamais » (CLAUDE.md §11).
 *
 * Détruit DÉFINITIVEMENT un groupe. Réservé au
 * super-admin (`groups.delete` ∈ PermissionRegistry::superAdminOnly(),
 * GroupPolicy@delete) pour les groupes créés par erreur : import raté,
 * doublon, groupe de test. Ce n'est PAS le chemin normal de clôture — un
 * groupe qui a réellement tourné se termine par
 * Group::archiverCommeTermine() ou Group::annuler().
 *
 * Invariant monétaire : la suppression est REFUSÉE dès qu'un encaissement
 * est rattaché au groupe, quel qu'en soit le montant. Aucun argent n'est
 * déplacé ni reconverti au passage — un groupe qui a encaissé n'est pas un
 * groupe créé par erreur, et ses paiements doivent être traités à la main
 * avant (remboursement, ou changement de groupe qui les convertit en avances
 * de façon tracée et auditable).
 *
 * Refusé aussi si le groupe porte des séances : l'historique de présence
 * n'est pas destructible par ce chemin (seances.group_id est ON DELETE
 * RESTRICT).
 *
 * Les dépenses « Paiement prof » du groupe ne sont pas supprimées non plus
 * (depenses.group_id est ON DELETE SET NULL) : ce sont des enregistrements
 * monétaires, elles se détachent simplement du groupe.
 *
 * ⚠ Les INSCRIPTIONS ne sont plus supprimées non plus (10/09/2026). Une
 * inscription est le dossier d'un étudiant : elle porte des lignes de frais
 * et, potentiellement, de l'argent. La détruire en dommage collatéral la
 * faisait disparaître de la fiche de l'étudiant sans laisser la moindre
 * explication. Désormais
 * DetacherInscriptionsGroupeSupprime l'annule (motif « Groupe supprimé ») et
 * lui écrit le NOM du groupe dans sa note — le seul endroit où ce nom
 * survit — puis `inscriptions.group_id` retombe à NULL tout seul
 * (ON DELETE SET NULL). Les frais et livres de l'inscription restent
 * attachés à elle, donc rien n'est perdu.
 */
final class SupprimerGroupe
{
    public function __construct(
        private readonly DetacherInscriptionsGroupeSupprime $detacher,
    ) {}

    /**
     * @return array{inscriptions:int, inscriptionsAnnulees:int}
     */
    public function handle(Group $group): array
    {
        return DB::transaction(function () use ($group): array {
            /** @var Group $group */
            $group = Group::query()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();

            if (Seance::query()->where('group_id', $group->id)->exists()) {
                throw ValidationException::withMessages([
                    'group' => __('This group has séances and cannot be deleted. Cancel or close it instead.'),
                ]);
            }

            $inscriptions = Inscription::query()
                ->where('group_id', $group->id)
                ->lockForUpdate()
                ->get();

            // ⚠ Le REFUS monétaire : un seul dirham encaissé sur ce groupe et
            // la suppression est rejetée. Ce chemin ne sert qu'aux groupes
            // créés par erreur, qui n'ont par définition reçu aucun paiement.
            // Décision 31/08/2026 : plutôt que de reconvertir l'argent en
            // avances au passage — une réallocation massive et silencieuse,
            // impossible à relire ensuite — on exige que les paiements soient
            // traités À LA MAIN d'abord (remboursement, ou changement de
            // groupe qui les transforme en avances de façon tracée). Un refus
            // se rattrape toujours ; une suppression, jamais.
            $feeIds = InscriptionFee::query()
                ->whereIn('inscription_id', $inscriptions->modelKeys())
                ->pluck('id');

            $encaissementsCount = $feeIds->isEmpty() ? 0 : Encaissement::query()
                ->whereIn('inscription_fee_id', $feeIds)
                ->count();

            if ($encaissementsCount > 0) {
                throw ValidationException::withMessages([
                    'group' => __('This group has :count payment(s) attached and cannot be deleted. Handle the payments first.', [
                        'count' => (string) $encaissementsCount,
                    ]),
                ]);
            }

            // ⚠ Les inscriptions SURVIVENT. Elles sont annulées et reçoivent
            // dans leur note le nom du groupe supprimé ; leurs frais, livres
            // et historique restent attachés à elles. C'est le seul moyen
            // pour qu'un étudiant garde son dossier — et qu'on comprenne,
            // des mois plus tard, ce qui est arrivé à son groupe.
            $detache = $this->detacher->handle($group);

            // creneaux / group_frais / group_enseignants / groups_historique
            // sont en ON DELETE CASCADE.
            // `inscriptions.group_id` retombe à NULL ici (ON DELETE SET NULL),
            // sans que rien n'ait à l'écrire à la main.
            $group->delete();

            return [
                'inscriptions' => $inscriptions->count(),
                'inscriptionsAnnulees' => $detache['inscriptionsAnnulees'],
            ];
        });
    }
}
