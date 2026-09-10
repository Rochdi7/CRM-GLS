<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

use App\Models\Group;
use App\Models\Inscription;
use App\Models\MotifAnnulation;
use Illuminate\Support\Facades\DB;

/**
 * Détache les inscriptions d'un groupe qu'on s'apprête à SUPPRIMER
 * définitivement (10/09/2026).
 *
 * ⚠ Le point central : une inscription n'est JAMAIS supprimée avec son
 * groupe. C'est le dossier d'un étudiant — il porte des lignes de frais et,
 * potentiellement, de l'argent. Avant cette action, SupprimerGroupe faisait
 * `Inscription::where('group_id', …)->delete()` : le dossier disparaissait
 * de la fiche de l'étudiant, et plus rien nulle part n'expliquait pourquoi.
 * Des mois plus tard, personne ne pouvait comprendre ce qui s'était passé.
 *
 * Ce que fait cette action, dans la transaction de SupprimerGroupe :
 *
 *  1. chaque inscription encore OUVERTE (`Active`) passe `Annulée`, avec le
 *     motif catalogué « Groupe supprimé » — jamais du texte libre, §11 ;
 *  2. TOUTE inscription du groupe — y compris celles déjà annulées ou
 *     changées — reçoit dans sa NOTE le nom du groupe supprimé et la date.
 *     Une fois la ligne `groups` détruite, cette note est le SEUL endroit où
 *     ce nom survit : `group_id` retombe à NULL (ON DELETE SET NULL) et
 *     l'écran affiche « — ». Sans la note, l'inscription deviendrait un
 *     dossier orphelin sans explication ;
 *  3. la note est AJOUTÉE, jamais écrasée (même règle que
 *     AnnulerInscription / CloturerInscriptionsGroupe) : un motif
 *     d'annulation antérieur doit rester lisible.
 *
 * Le `group_id` lui-même n'est pas écrit ici : PostgreSQL le met à NULL tout
 * seul quand le groupe est supprimé (ON DELETE SET NULL). L'écrire à la main
 * en plus ne ferait que dupliquer — et masquer — ce contrat.
 *
 * ⚠ Aucun argent ne bouge, et aucun frais n'est masqué. C'est délibéré et
 * c'est différent de CloturerInscriptionsGroupe : là-bas le groupe SURVIT et
 * ses créances doivent cesser d'être réclamées. Ici, SupprimerGroupe refuse
 * en amont tout groupe portant le moindre encaissement, donc les frais de
 * ces inscriptions n'ont par définition jamais reçu un dirham. Les laisser
 * visibles garde le dossier lisible tel qu'il a été saisi.
 */
final class DetacherInscriptionsGroupeSupprime
{
    /** Motif système écrit sur les inscriptions d'un groupe supprimé. */
    public const MOTIF = MotifAnnulation::MOTIF_GROUPE_SUPPRIME;

    /**
     * @return array{inscriptionsAnnulees: int, inscriptionsNotees: int}
     */
    public function handle(Group $group): array
    {
        return DB::transaction(function () use ($group): array {
            $this->assurerMotif();

            $nomGroupe = trim((string) $group->nom);
            $note = __('Group ":name" was permanently deleted on :date. This registration was kept and cancelled.', [
                'name' => $nomGroupe,
                'date' => now()->format('d/m/Y'),
            ]);

            $inscriptions = Inscription::query()
                ->where('group_id', $group->id)
                ->lockForUpdate()
                ->get();

            $annulees = 0;

            foreach ($inscriptions as $inscription) {
                $attributs = ['note' => $this->appendNote($inscription->note, $note)];

                // Seul un dossier encore OUVERT est annulé. Une inscription
                // déjà `Annulée` / `Changement` / `Expirée` / `Archivée` garde
                // son statut ET son motif d'origine : elle a été close pour une
                // autre raison, que la suppression du groupe ne doit pas
                // réécrire. Elle reçoit quand même la note, sinon son groupe
                // deviendrait « — » sans explication.
                if ($inscription->statut === Inscription::STATUT_ACTIVE) {
                    $attributs['statut'] = Inscription::STATUT_ANNULEE;
                    $attributs['motif_annulation'] = self::MOTIF;
                    $attributs['date_fin'] = $inscription->date_fin?->format('Y-m-d') ?? now()->format('Y-m-d');
                    $annulees++;
                }

                // save() sur le modèle, jamais un update() de masse : sinon
                // `Auditable` ne journalise rien (§16).
                $inscription->fill($attributs)->save();
            }

            return [
                'inscriptionsAnnulees' => $annulees,
                'inscriptionsNotees' => $inscriptions->count(),
            ];
        });
    }

    /**
     * Le motif est catalogué et `is_system` : il est posé par le code, donc
     * il doit exister et rester actif — même contrat que « Clôture du
     * groupe » et « Changement de groupe ».
     */
    private function assurerMotif(): void
    {
        MotifAnnulation::query()->updateOrCreate(
            ['nom' => self::MOTIF],
            [
                'statut' => MotifAnnulation::STATUT_ACTIF,
                'is_system' => true,
                'portee' => MotifAnnulation::PORTEE_INSCRIPTION,
            ],
        );
    }

    private function appendNote(?string $existing, string $added): string
    {
        return trim(($existing ?? '') === '' ? $added : $existing."\n".$added);
    }
}
