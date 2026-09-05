<?php

declare(strict_types=1);

namespace App\Domain\Payments\Mail;

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
            with: ['encaissement' => $this->encaissement],
        );
    }

    public function attachments(): array
    {
        $inscription = $this->encaissement->fee?->inscription
            ?? $this->encaissement->applications->sortBy('id')->first()?->fee?->inscription;
        $centre = $inscription?->etablissement ?? $this->encaissement->student?->etablissement;

        $html = view('backoffice.encaissements.recu-pdf', [
            'encaissement' => $this->encaissement,
            'centre' => $centre,
            'anneeScolaire' => $inscription?->anneeScolaire?->nom,
            'niveau' => $inscription?->group?->nom ?? $this->encaissement->student?->niveau,
            'fraisNom' => $this->encaissement->libelleFrais(),
        ])->render();

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A5-L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        return [
            Attachment::fromData(fn () => $pdfContent, 'recu-'.$this->encaissement->reference.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
