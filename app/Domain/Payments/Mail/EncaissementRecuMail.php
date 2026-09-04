<?php

declare(strict_types=1);

namespace App\Domain\Payments\Mail;

use App\Domain\Payments\Support\RecuPdfRenderer;
use App\Domain\Payments\Support\SituationFraisRecu;
use App\Models\Encaissement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Emailed payment receipt (reçu) — same design as the printable "recu" page
 * (backoffice.encaissements.recu) via the table-based recu-pdf variant,
 * rendered A5 landscape by mPDF and attached as a PDF. mPDF (not dompdf):
 * the receipt's Arabic labels need real RTL glyph shaping, which mPDF does
 * natively (autoScriptToLang/autoLangToFont) and dompdf cannot do at all.
 * Sent on demand from the Encaissements list "Envoyer le reçu par email"
 * row action (EncaissementController::sendRecuEmail).
 *
 * Always QUEUED (`Mail::queue`): the PDF render + SMTP round-trip run in the
 * queue worker, not in the cashier's request. `SerializesModels` stores only
 * the encaissement id (+ loaded relation names) and re-fetches on the worker,
 * so the property must be public/protected (not private) for the trait to
 * restore it.
 */
final class EncaissementRecuMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Retry a transient SMTP failure a few times before giving up. */
    public int $tries = 3;

    public function __construct(public readonly Encaissement $encaissement)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reçu de paiement '.$this->encaissement->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'backoffice.encaissements.recu-email',
            with: [
                'encaissement' => $this->encaissement,
                'situation' => SituationFraisRecu::pour($this->encaissement),
            ],
        );
    }

    /**
     * ⚠ Le PDF est fabriqué par RecuPdfRenderer, jamais ici : c'est la
     * fabrique unique du document (config mPDF, gabarit, situation du frais),
     * pour que le reçu reçu par email soit exactement celui du lien WhatsApp
     * et du guichet. La copie locale de cette configuration qui vivait dans
     * ce mailable a divergé au premier ajout au gabarit (les lignes
     * « Total payé / Reste à payer » du 04/09/2026 lui manquaient) — c'est
     * précisément le risque annoncé par le docblock du renderer.
     */
    public function attachments(): array
    {
        $renderer = app(RecuPdfRenderer::class);
        $pdfContent = $renderer->render($this->encaissement);

        return [
            Attachment::fromData(fn () => $pdfContent, $renderer->filename($this->encaissement))
                ->withMime('application/pdf'),
        ];
    }
}
