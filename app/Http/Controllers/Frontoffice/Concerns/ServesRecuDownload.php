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

    /** Réponse PDF — pièce jointe, jamais mise en cache par un proxy partagé. */
    private function pdfResponse(string $pdf, string $filename): Response
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
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
            'downloadUrl' => $request->fullUrlWithQuery(['download' => 1]),
            'ttlJours' => RecuWhatsAppLink::TTL_DAYS,
        ], 200, [
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
