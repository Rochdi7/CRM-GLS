<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontoffice;

use App\Domain\Payments\Support\RecuPdfRenderer;
use App\Http\Controllers\Controller;
use App\Models\Encaissement;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reçu de paiement en PDF, servi à l'ÉTUDIANT via un lien signé — la cible
 * du lien envoyé par WhatsApp (31/08/2026, EncaissementController::recuLien).
 *
 * ⚠ Publique par nécessité, verrouillée par la signature. Le lien
 * click-to-chat (api.whatsapp.com/send) ne transporte que `phone` et `text` :
 * il ne peut PAS joindre un fichier. Le PDF voyage donc comme URL dans le
 * message, et l'étudiant n'a pas de compte sur le CRM — d'où une route hors
 * `auth`, dans le Frontoffice (§2), et non une route backoffice qui
 * l'enverrait sur l'écran de login.
 *
 * Ce que la signature garantit (middleware `signed`, posé sur la route) :
 *  - l'URL est infalsifiable — changer l'id invalide la signature, donc
 *    personne ne peut énumérer /recu/1, /recu/2… pour lire les reçus des
 *    autres étudiants ;
 *  - elle EXPIRE (7 jours, EncaissementController::RECU_LINK_TTL_DAYS), donc
 *    un message WhatsApp transféré ne reste pas une porte ouverte à vie.
 *
 * Le document ne contient que ce que l'étudiant a déjà reçu au guichet :
 * son propre reçu. Aucune donnée d'un autre étudiant, aucun total de caisse,
 * aucune navigation vers le CRM.
 */
final class RecuController extends Controller
{
    public function __invoke(Encaissement $encaissement, RecuPdfRenderer $renderer): Response
    {
        $encaissement->load(RecuPdfRenderer::RELATIONS);

        // `attachment` : le clic TELECHARGE le fichier au lieu de l'afficher
        // dans la webview de WhatsApp. C'est ce que l'utilisateur attend d'un
        // « reçu en PDF » — un document qu'il garde, pas une page qu'il perd
        // en fermant l'onglet.
        //
        // ⚠ Sur iOS cet en-tête ne fait rien : WebKit affiche quand même le
        // PDF dans sa visionneuse (il ignore `attachment` pour les types
        // qu'il sait rendre), d'où « Partager → Enregistrer dans Fichiers »
        // comme seul chemin d'enregistrement. Un téléchargement forcé
        // n'existe pas sur iPhone — inutile de retenter, une page
        // intermédiaire portant un bouton a été essayée puis retirée le
        // 01/09/2026 : elle ajoutait une étape sans rien résoudre.
        return response($renderer->render($encaissement), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$renderer->filename($encaissement).'"',
            // Un reçu est un document personnel : jamais mis en cache par un
            // proxy partagé.
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
