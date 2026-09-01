<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontoffice;

use App\Domain\Payments\Support\RecuPdfRenderer;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontoffice\Concerns\ServesRecuDownload;
use App\Models\Encaissement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reçu GROUPÉ en PDF, servi à l'ÉTUDIANT via un lien signé — la cible du
 * lien envoyé par l'action WhatsApp groupée du menu « Action »
 * (EncaissementController::recuGroupeWhatsApp).
 *
 * Même contrat que RecuController, avec une nuance décisive : la liste des
 * ids voyage DANS la query string, donc elle est couverte par la signature
 * (`Illuminate\Routing\Middleware\ValidateSignature` signe l'URL complète,
 * paramètres compris). Ajouter, retirer ou remplacer un id casse la
 * signature : personne ne peut transformer le lien reçu par un étudiant en
 * un reçu portant les paiements d'un autre. Ne JAMAIS relâcher cette
 * garantie en acceptant des ids hors signature (POST, cookie, session) —
 * l'étudiant n'est pas authentifié, la signature est le seul contrôle.
 *
 * Aucune re-vérification d'appartenance n'est possible ici (pas d'utilisateur
 * connecté) : le périmètre a été fixé côté backoffice, au moment où le
 * caissier autorisé a fabriqué le lien, ligne par ligne.
 */
final class RecuGroupeController extends Controller
{
    use ServesRecuDownload;

    public function __invoke(Request $request, RecuPdfRenderer $renderer): Response
    {
        $ids = collect(explode(',', (string) $request->string('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404);

        $encaissements = Encaissement::query()
            ->whereIn('id', $ids)
            ->with(RecuPdfRenderer::RELATIONS)
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get();

        abort_if($encaissements->count() !== $ids->count(), 404);

        // Une seule inscription — la même règle que le reçu groupé imprimé.
        // Redondant avec la signature (le lot signé était déjà homogène),
        // gardé parce qu'un reçu qui mélangerait deux dossiers serait un
        // document faux, pas seulement une URL inattendue.
        $inscriptionIds = $encaissements->map(fn ($e) => $e->fee?->inscription_id)->unique();
        abort_if($inscriptionIds->count() !== 1 || $inscriptionIds->first() === null, 404);

        // Par défaut : la page qui porte le bouton de téléchargement, jamais
        // le PDF brut (voir ServesRecuDownload — sur mobile il n'y a aucun
        // moyen visible de l'enregistrer). Rendu AVANT l'agrégat et mPDF :
        // ni l'un ni l'autre ne sert à dessiner la page.
        if (! $this->wantsDownload($request)) {
            return $this->landingResponse($request, $encaissements);
        }

        // Reste par frais en UNE requête agrégée — jamais un accesseur money
        // par ligne dans la boucle du reçu (CLAUDE.md §17).
        $feeIds = $encaissements->pluck('inscription_fee_id')->filter()->unique();
        $payeParFee = Encaissement::query()
            ->whereIn('inscription_fee_id', $feeIds)
            ->groupBy('inscription_fee_id')
            ->selectRaw('inscription_fee_id, sum(montant) as paye')
            ->pluck('paye', 'inscription_fee_id');

        return $this->pdfResponse(
            $renderer->renderGroupe($encaissements, $payeParFee),
            $renderer->filenameGroupe($encaissements),
        );
    }
}
