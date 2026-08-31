<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Encaissement;
use Illuminate\Support\Facades\URL;

/**
 * Construit le lien click-to-chat qui envoie le reçu à l'étudiant
 * (31/08/2026) : https://api.whatsapp.com/send?phone=…&text=…
 *
 * ⚠ Ce lien NE PEUT PAS joindre de fichier — l'API click-to-chat n'accepte
 * que `phone` et `text`, il n'existe aucun paramètre d'attachement. Le PDF
 * voyage donc comme URL SIGNÉE dans le corps du message
 * (frontoffice.recu, servie par Frontoffice\RecuController). Ne pas
 * « corriger » en cherchant un paramètre de pièce jointe : il n'y en a pas.
 *
 * Le lien est construit ICI, côté serveur, jamais dans React : la signature
 * de l'URL du PDF est un secret d'application (APP_KEY) et le texte du
 * message est du contenu utilisateur qui doit rester identique quel que soit
 * l'écran qui l'ouvre.
 */
final class RecuWhatsAppLink
{
    /** Durée de validité du lien PDF envoyé à l'étudiant. */
    public const TTL_DAYS = 7;

    /**
     * @return array{url:string, phone:string}|null  null si l'étudiant n'a
     *                                               aucun numéro joignable.
     */
    public function forEncaissement(Encaissement $encaissement): ?array
    {
        $phone = WhatsAppNumber::forStudent($encaissement->student);

        if ($phone === null) {
            return null;
        }

        return [
            'phone' => $phone,
            'url' => 'https://api.whatsapp.com/send/?'.http_build_query([
                'phone' => $phone,
                'text' => $this->message($encaissement),
                'type' => 'phone_number',
                'app_absent' => '0',
            ], '', '&', PHP_QUERY_RFC3986),
        ];
    }

    /** URL signée et expirante du PDF — la seule façon de le publier. */
    public function pdfUrl(Encaissement $encaissement): string
    {
        return URL::temporarySignedRoute(
            'frontoffice.recu',
            now()->addDays(self::TTL_DAYS),
            ['encaissement' => $encaissement->id],
        );
    }

    /**
     * L'URL du PDF est-elle joignable depuis le téléphone de l'étudiant ?
     *
     * `APP_URL` vaut `http://127.0.0.1:8000` en local : sur le mobile du
     * destinataire, 127.0.0.1 DÉSIGNE SON PROPRE TÉLÉPHONE, donc le lien
     * échoue à tous les coups. Envoyer quand même donnerait à l'étudiant un
     * lien mort — et au caissier la conviction que le reçu est parti.
     * Constaté en situation réelle le 31/08/2026 (capture WhatsApp Web).
     *
     * On refuse donc l'envoi tant qu'`APP_URL` pointe sur la machine locale.
     * En production, où `APP_URL` est le vrai domaine, ce garde ne se
     * déclenche jamais.
     */
    public function pdfUrlIsPubliclyReachable(): bool
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        return ! in_array($host, ['127.0.0.1', 'localhost', '::1', '0.0.0.0'], true)
            && ! str_ends_with($host, '.localhost')
            && ! str_ends_with($host, '.test');
    }

    /**
     * Corps du message. Reprend les informations que l'étudiant a déjà sur
     * son reçu papier — rien de plus : un message WhatsApp peut être
     * transféré, il ne doit donc porter aucune donnée d'un autre dossier.
     */
    private function message(Encaissement $encaissement): string
    {
        // Une avance appliquée tire son libellé de ses lignes d'application
        // — même règle que le PDF (Encaissement::libelleFrais).
        $inscription = $encaissement->fee?->inscription
            ?? $encaissement->applications->sortBy('id')->first()?->fee?->inscription;
        $centre = $inscription?->etablissement ?? $encaissement->student?->etablissement;

        $lines = [
            __('Hello :name,', ['name' => $encaissement->student?->nomComplet() ?? '']),
            '',
            __('We confirm receipt of your payment.'),
            '',
            __('Receipt').' : '.$encaissement->reference,
            __('Amount').' : '.number_format((float) $encaissement->montant, 2, ',', ' ').' MAD',
            __('Fee').' : '.$encaissement->libelleFrais(),
            __('Date').' : '.$encaissement->date_paiement?->format('d/m/Y'),
        ];

        if ($centre?->nom_centre !== null) {
            $lines[] = $centre->nom_centre;
        }

        $lines[] = '';
        $lines[] = __('Download your receipt in PDF (link valid for :days days):', ['days' => self::TTL_DAYS]);
        $lines[] = $this->pdfUrl($encaissement);
        $lines[] = '';
        $lines[] = __('Thank you for your trust.');

        return implode("\n", $lines);
    }
}
