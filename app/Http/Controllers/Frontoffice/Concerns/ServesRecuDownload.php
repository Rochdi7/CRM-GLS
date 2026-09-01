<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontoffice\Concerns;

use App\Domain\Payments\Support\RecuWhatsAppLink;
use App\Models\Encaissement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le lien reçu envoyé à l'étudiant sert DEUX choses à la même URL signée :
 * une page d'atterrissage portant un bouton de téléchargement, et — sur
 * `?download=1` — les octets du PDF.
 *
 * ⚠ POURQUOI la page existe. Servir directement le PDF en `attachment`
 * fonctionne sur un ordinateur mais PAS sur un téléphone, le seul appareil
 * qui reçoive vraiment ces liens : iOS Safari affiche le PDF dans une
 * visionneuse en lecture seule, sans bouton d'enregistrement visible.
 * L'étudiant voyait son reçu sans pouvoir le garder (01/09/2026). Ne pas
 * « simplifier » en revenant au PDF direct.
 *
 * `download` est le SEUL paramètre exclu de la signature
 * (ValidateSignature::absolute(['download']) sur les routes) : ajouter ou
 * retirer ce drapeau ne change pas QUEL reçu est servi, seulement sous quelle
 * forme. Tout le reste — l'id, les ids du lot, l'expiration — reste signé,
 * donc infalsifiable. Ne jamais élargir cette liste.
 */
trait ServesRecuDownload
{
    private function wantsDownload(Request $request): bool
    {
        return $request->boolean('download');
    }

    /**
     * Réponse PDF — jamais mise en cache par un proxy partagé.
     *
     * ⚠ La disposition dépend de l'appareil, et ce n'est pas du zèle :
     * `attachment` déclenche un vrai téléchargement sur ordinateur et sur
     * Android, mais DANS la webview WhatsApp d'iOS le tap est avalé sans
     * rien afficher — pas de gestionnaire de téléchargements là-dedans
     * (constaté le 01/09/2026, « even click not download started »). Sur
     * iOS on sert donc `inline` : le PDF s'ouvre dans la visionneuse
     * native, dont la feuille de partage (« Enregistrer dans Fichiers »)
     * est le SEUL chemin d'enregistrement qui fonctionne. Un téléchargement
     * forcé n'existe pas sur iOS — ne pas re-tenter `attachment` partout.
     */
    private function pdfResponse(Request $request, string $pdf, string $filename): Response
    {
        $disposition = $this->isIos($request) ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * iPhone/iPad — y compris la webview WhatsApp, qui garde « iPhone OS »
     * dans son User-Agent. Un iPad récent se présente comme macOS mais
     * reste tactile ; on s'en tient aux marqueurs sûrs, le pire cas étant
     * un iPad qui télécharge comme un Mac — ce qui fonctionne.
     */
    private function isIos(Request $request): bool
    {
        return (bool) preg_match('/iPhone|iPad|iPod/i', (string) $request->userAgent());
    }

    /**
     * Page d'atterrissage. Ne montre que ce que l'étudiant a déjà sur son
     * reçu papier — un message WhatsApp se transfère, donc aucune donnée
     * d'un autre dossier, aucun total de caisse, aucun lien vers le CRM.
     *
     * @param  Collection<int, Encaissement>  $encaissements
     */
    private function landingResponse(Request $request, Collection $encaissements): Response
    {
        $first = $encaissements->first();

        $inscription = $first?->fee?->inscription
            ?? $first?->applications->sortBy('id')->first()?->fee?->inscription;
        $centre = $inscription?->etablissement ?? $first?->student?->etablissement;

        $dates = $encaissements->map(fn (Encaissement $e) => $e->date_paiement?->format('d/m/Y'))->filter()->unique();

        // Le logo voyage en data URI : la page est ouverte depuis la webview
        // WhatsApp, où une requête d'image supplémentaire est un aller-retour
        // de plus sur une connexion mobile — et le fichier fait 32 Ko.
        $logoPath = public_path('assets/images/logo/gls-noir.png');
        $logoUrl = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
            : null;

        return response()->view('frontoffice.recu-telechargement', [
            'titre' => 'Votre reçu de paiement',
            'logoUrl' => $logoUrl,
            'centre' => $centre,
            'student' => $first?->student,
            'reference' => $encaissements->pluck('reference')->implode(' / '),
            'date' => $dates->count() === 1 ? $dates->first() : $dates->first().' — '.$dates->last(),
            'lignes' => $encaissements->map(fn (Encaissement $e) => [
                'libelle' => $e->libelleFrais(),
                'montant' => number_format((float) $e->montant, 2, ',', ' ').' MAD',
            ])->all(),
            'montantTotal' => number_format((float) $encaissements->sum('montant'), 2, ',', ' ').' MAD',
            'downloadUrl' => $this->downloadUrl($request),
            'isIos' => $this->isIos($request),
            'ttlJours' => RecuWhatsAppLink::TTL_DAYS,
        ], 200, [
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * URL du bouton = le chemin courant + la query reçue + `download=1`,
     * en RELATIF.
     *
     * ⚠ Relatif volontairement, et surtout pas `URL::current()` ni
     * `$request->fullUrlWithQuery()`. Les deux reconstruisent une URL
     * ABSOLUE dont le schéma vient de la requête entrante : derrière le
     * reverse proxy du VPS, Laravel voit `http` là où l'étudiant a reçu un
     * lien `https`. Or la signature est calculée sur l'URL complète, schéma
     * compris (UrlGenerator::hasCorrectSignature) — un bouton reconstruit en
     * `http` porterait donc une signature invalide. Un lien relatif est
     * résolu par le navigateur contre l'URL réellement visitée, celle-là
     * même dont la signature vient d'être validée. Ne pas « corriger » en
     * URL absolue.
     */
    private function downloadUrl(Request $request): string
    {
        $query = $request->query();
        $query['download'] = 1;

        return '/'.ltrim($request->path(), '/').'?'.http_build_query($query);
    }
}
