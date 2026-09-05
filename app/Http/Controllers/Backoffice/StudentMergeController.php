<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Actions\DeplacerEncaissementVersFrais;
use App\Domain\Payments\Queries\GetStudentPaymentPlacement;
use App\Domain\Students\Actions\FusionnerEtudiants;
use App\Domain\Students\Queries\GetFusionCandidates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Students\DeplacerEncaissementRequest;
use App\Http\Requests\Backoffice\Students\FusionnerEtudiantsRequest;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Fusion de fiches & réaffectation des paiements » — l'écran de réparation
 * réservé au super-admin (`students.merge` + `payments.move-fee`, tous deux
 * dans PermissionRegistry::superAdminOnly()).
 *
 * Il répond à deux pannes qui vont presque toujours ensemble :
 *   1. la même personne existe en DEUX fiches (ancien CRM, double saisie) —
 *      ses inscriptions sont d'un côté, ses paiements de l'autre, et
 *      AppliquerAvance refuse alors l'allocation ;
 *   2. un paiement est accroché au mauvais frais / à la mauvaise
 *      inscription, souvent sur un dossier déjà clos.
 *
 * ⚠ Volontairement HORS du sélecteur de contexte et SANS filtre de statut :
 * les lignes à réparer sont exactement celles que les écrans ordinaires
 * masquent (autre centre, autre année, inscription Annulée). C'est la raison
 * d'être de la page, et pourquoi elle est super-admin only.
 *
 * ⚠ Aucun montant ne bouge nulle part ici : ni `montant`, ni `methode`, ni
 * `date_paiement`, ni `caisse_id`, ni `agent_id`, ni `caisses.solde`. Les
 * deux actions ne réécrivent que `student_id` (fusion) et
 * `inscription_fee_id` (déplacement).
 *
 * Pas d'entrée dans la barre latérale (cf. backofficeNavigation.ts) : la
 * page est atteignable par son URL directe et par l'onglet des Étudiants,
 * même convention que « Déplacer des encaissements ».
 */
final class StudentMergeController extends Controller
{
    public function index(Request $request, GetFusionCandidates $candidats, GetStudentPaymentPlacement $dossier): Response
    {
        $this->authorize('merge', Student::class);

        $search = (string) $request->string('search');
        $etudiantId = $request->integer('etudiant_id') ?: null;

        return Inertia::render('Backoffice/Students/Merge', [
            'filters' => ['search' => $search, 'etudiant_id' => $etudiantId],
            'candidats' => $candidats($search),
            // Le dossier complet n'est chargé qu'une fois une fiche choisie —
            // closure, donc un rechargement partiel (only: ['candidats'])
            // pendant la recherche ne le recalcule pas (§ Performance rules).
            'dossier' => fn () => $etudiantId !== null
                ? $dossier(Student::findOrFail($etudiantId))
                : null,
        ]);
    }

    /**
     * Fusionne la fiche doublon dans la fiche gardée. Seul `student_id`
     * est réécrit sur les six tables liées — voir FusionnerEtudiants.
     */
    public function merge(FusionnerEtudiantsRequest $request, FusionnerEtudiants $action): RedirectResponse
    {
        $this->authorize('merge', Student::class);

        $data = $request->validated();

        $resultat = $action->handle(
            Student::findOrFail((int) $data['garde_id']),
            Student::findOrFail((int) $data['doublon_id']),
        );

        $lignes = array_sum($resultat['lignes']);

        return redirect()
            ->route('backoffice.students.merge.index', ['etudiant_id' => $resultat['garde']->getKey()])
            ->with('success', __(':count record(s) moved onto :reference.', [
                'count' => $lignes,
                'reference' => $resultat['garde']->reference,
            ]));
    }

    /**
     * Déplace un encaissement vers le frais d'une autre inscription du même
     * étudiant — ou l'en détache (fee_id vide ⇒ redevient une avance libre).
     */
    public function movePayment(DeplacerEncaissementRequest $request, DeplacerEncaissementVersFrais $action): RedirectResponse
    {
        $this->authorize('movePayment', Encaissement::class);

        $data = $request->validated();
        $encaissement = Encaissement::findOrFail((int) $data['encaissement_id']);

        $cible = isset($data['fee_id']) && $data['fee_id'] !== null
            ? InscriptionFee::findOrFail((int) $data['fee_id'])
            : null;

        $action->handle($encaissement, $cible);

        return redirect()
            ->route('backoffice.students.merge.index', ['etudiant_id' => $encaissement->student_id])
            ->with('success', $cible === null
                ? __('Payment detached — it is an advance again.')
                : __('Payment moved.'));
    }
}
