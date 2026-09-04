<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Encaissement;
use Illuminate\Support\Collection;

/**
 * Rendu PDF du reçu — la SEULE fabrique du document, partagée par l'envoi
 * par email (EncaissementRecuMail) et par le lien WhatsApp
 * (PublicRecuController). Extrait du mailable le 31/08/2026 : deux copies
 * de la configuration mPDF auraient dérivé, et l'étudiant qui reçoit le
 * reçu par WhatsApp doit tenir en main exactement le même document que
 * celui qui le reçoit par email.
 *
 * mPDF et non dompdf : les libellés arabes du reçu exigent un vrai façonnage
 * des glyphes RTL, que mPDF fait nativement (autoScriptToLang/autoLangToFont)
 * et que dompdf ne sait pas faire du tout.
 *
 * ⚠ Coûteux (quelques secondes) : à n'appeler que dans un worker de queue ou
 * dans une requête qui sert le fichier, jamais dans une boucle de liste.
 */
final class RecuPdfRenderer
{
    /** Les relations dont le rendu a besoin — chargées une fois, par l'appelant. */
    public const RELATIONS = [
        'student.etablissement',
        'fee.inscription.anneeScolaire',
        'fee.inscription.group',
        'fee.inscription.etablissement',
        // Une avance appliquee ne porte PAS de fee : ce sont ses lignes
        // d'application qui savent quels frais l'argent a finalement payes.
        'applications.fee',
        'applications.fee.inscription.anneeScolaire',
        'applications.fee.inscription.group',
        'applications.fee.inscription.etablissement',
    ];

    /** Contenu binaire du PDF (A5 paysage), prêt à attacher ou à streamer. */
    public function render(Encaissement $encaissement): string
    {
        // Pour une avance appliquee, l'annee/le groupe viennent de la
        // premiere ligne d'application : c'est la que l'argent a atterri.
        $inscription = $encaissement->fee?->inscription
            ?? $encaissement->applications->sortBy('id')->first()?->fee?->inscription;
        $centre = $inscription?->etablissement ?? $encaissement->student?->etablissement;

        $html = view('backoffice.encaissements.recu-pdf', [
            'encaissement' => $encaissement,
            'centre' => $centre,
            'anneeScolaire' => $inscription?->anneeScolaire?->nom,
            'niveau' => $inscription?->group?->nom ?? $encaissement->student?->niveau,
            'fraisNom' => $encaissement->libelleFrais(),
            'situation' => SituationFraisRecu::pour($encaissement),
        ])->render();

        $mpdf = $this->mpdf();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Même document, version GROUPÉE : plusieurs encaissements de la MÊME
     * inscription sur un seul reçu (une ligne par frais + total). C'est le
     * PDF que porte le lien WhatsApp d'un envoi groupé.
     *
     * ⚠ L'appelant a déjà vérifié l'appartenance à une seule inscription et
     * autorisé chaque ligne : ce renderer ne fait que dessiner. Il ne
     * recalcule NI le reste par frais (agrégat passé en paramètre, jamais
     * InscriptionFee::montantPaye() dans la boucle — CLAUDE.md §17) ni le
     * périmètre du lot.
     *
     * @param  \Illuminate\Support\Collection<int, Encaissement>  $encaissements
     * @param  \Illuminate\Support\Collection<int, mixed>|array<int, mixed>  $payeParFee
     */
    public function renderGroupe(Collection $encaissements, $payeParFee): string
    {
        $first = $encaissements->first();
        $inscription = $first?->fee?->inscription;
        $centre = $inscription?->etablissement ?? $first?->student?->etablissement;

        $html = view('backoffice.encaissements.recu-groupe-pdf', [
            'encaissements' => $encaissements,
            'student' => $first?->student,
            'centre' => $centre,
            'anneeScolaire' => $inscription?->anneeScolaire?->nom,
            'niveau' => $inscription?->group?->nom ?? $first?->student?->niveau,
            'montantTotal' => (float) $encaissements->sum('montant'),
            'reference' => $first?->reference,
            'payeParFee' => $payeParFee,
            'situation' => SituationFraisRecu::pourLot($encaissements),
        ])->render();

        $mpdf = $this->mpdf();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /** Nom de fichier stable, réutilisé par la pièce jointe et le téléchargement. */
    public function filename(Encaissement $encaissement): string
    {
        return 'recu-'.$encaissement->reference.'.pdf';
    }

    /** Nom de fichier du reçu groupé — la référence de la première ligne suffit à l'identifier. */
    public function filenameGroupe(Collection $encaissements): string
    {
        return 'recu-'.($encaissements->first()?->reference ?? 'groupe').'.pdf';
    }

    /** UNE seule configuration mPDF, partagée par les deux rendus. */
    private function mpdf(): \Mpdf\Mpdf
    {
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return new \Mpdf\Mpdf([
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
    }
}
