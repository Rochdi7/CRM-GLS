<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Actions\DeplacerPaiementMaintenance;
use App\Domain\Payments\Queries\DiagnostiquerDeplacementPaiement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Maintenance\MovePaymentRequest;
use App\Models\Encaissement;
use App\Models\Inscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Déplacer un paiement » — /backoffice/move-payment (21–23/09/2026).
 *
 * L'écran de ce qui se faisait en PuTTY trois fois en une semaine : un
 * paiement encaissé au nom du MAUVAIS étudiant (dossier saisi sur le
 * mauvais nom au guichet, appels « Absent » sur une personne jamais venue)
 * est réaffecté au frais de la bonne personne. Deux gestes qu'aucun écran
 * d'exploitation n'autorise, et qui ne doivent pas s'y ajouter :
 *   1. effacer les appels FANTÔMES du dossier source — sinon la garde
 *      « zéro présence » refuse, à raison ;
 *   2. affecter une AVANCE au frais d'un autre étudiant.
 *
 * ⚠ RÉSERVÉ AU COMPTE DE MAINTENANCE — une IDENTITÉ, pas une permission
 * (`AppServiceProvider::MAINTAINER_ONLY_ABILITIES`, décidée AU-DESSUS du
 * bypass super-admin, même mécanique que « Gestion de la base de
 * données »). Le CEO reçoit 403. Hors de la barre latérale, atteint par
 * son lien — et ce n'est pas ce qui le protège (§16).
 *
 * AUCUN ARGENT NE BOUGE : montant, date, agent, caisse et `caisses.solde`
 * sont inchangés, seule l'affectation change ; les actions Domain le
 * garantissent, et le message de succès le rappelle.
 */
final class MovePaymentController extends Controller
{
    /** Ability d'identité — voir AppServiceProvider::MAINTAINER_ONLY_ABILITIES. */
    public const ABILITY = 'payments.move-any';

    public function index(Request $request, DiagnostiquerDeplacementPaiement $diagnostiquer): Response
    {
        abort_unless($request->user()->can(self::ABILITY), 403);

        $encaissement = strtoupper(trim((string) $request->query('encaissement', '')));
        $inscription = strtoupper(trim((string) $request->query('inscription', '')));

        return Inertia::render('Backoffice/MovePayment/Index', [
            'filters' => [
                'encaissement' => $encaissement,
                'inscription' => $inscription,
            ],
            // Closure : le diagnostic ne se calcule que quand les deux
            // références sont là, et pas sur un rechargement partiel.
            'diagnostic' => fn () => ($encaissement !== '' && $inscription !== '')
                ? $diagnostiquer($encaissement, $inscription)
                : null,
        ]);
    }

    public function store(MovePaymentRequest $request, DeplacerPaiementMaintenance $action): RedirectResponse
    {
        abort_unless($request->user()->can(self::ABILITY), 403);

        $data = $request->validated();

        $encaissement = Encaissement::query()->where('reference', $data['encaissement'])->firstOrFail();
        $inscription = Inscription::query()->where('reference', $data['inscription'])->firstOrFail();

        $result = $action->handle(
            $encaissement,
            $inscription,
            (string) $data['motif'],
            (bool) ($data['purger_presences'] ?? false),
            isset($data['inscription_fee_id']) ? (int) $data['inscription_fee_id'] : null,
        );

        $row = $result['encaissement']->load(['student', 'fee']);

        $message = __(':reference (:montant MAD) now belongs to :student on « :frais ». Amount, date, agent and till are unchanged.', [
            'reference' => $row->reference,
            'montant' => number_format((float) $row->montant, 2, '.', ' '),
            'student' => $row->student?->nomComplet() ?? '?',
            'frais' => $row->fee?->nom ?? '?',
        ]);

        if ($result['presencesSupprimees'] > 0) {
            $message .= ' '.__(':count ghost « Absent » line(s) erased and journaled.', ['count' => $result['presencesSupprimees']]);
        }

        // Retour sur le MÊME diagnostic : l'opérateur voit la ligne à son
        // nouveau nom, et le bouton se grise (même étudiant désormais).
        return redirect()
            ->route('backoffice.move-payment.index', [
                'encaissement' => $data['encaissement'],
                'inscription' => $data['inscription'],
            ])
            ->with('success', $message);
    }
}
