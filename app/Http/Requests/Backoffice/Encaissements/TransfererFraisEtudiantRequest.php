<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use Illuminate\Foundation\Http\FormRequest;

/**
 * « Transfert d'un frais payé vers l'inscription d'un autre étudiant »
 * (TransfererFraisVersAutreEtudiant, `payments.transfer-student`).
 *
 * ⚠ `motif` est OBLIGATOIRE, et c'est le point de la règle : ce geste fait
 * qu'une somme encaissée au nom d'une personne solde le dossier d'une
 * autre. Sans motif, le journal montre QUE l'argent a changé de main mais
 * jamais POURQUOI — et c'est exactement la question qu'on posera dans six
 * mois. Même exigence que l'annulation d'une dépense (CLAUDE.md §11).
 *
 * Ce que ce formulaire NE contient PAS, délibérément : aucune date, aucun
 * montant, aucune caisse. Le paiement n'est jamais réécrit — seule son
 * affectation change (l'argent est en caisse depuis le jour où il a été
 * reçu). Aucune règle ici ne vérifie les présences, le centre ni le reste
 * dû : ces garde-fous LISENT des données puis écrivent, donc ils vivent
 * dans la transaction verrouillée de l'action, pas dans une validation
 * évaluée avant (CLAUDE.md §11).
 */
final class TransfererFraisEtudiantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'encaissement_id' => ['required', 'integer', 'exists:encaissements,id'],
            // L'INSCRIPTION cible, jamais un frais : le frais est détecté
            // par l'action (même entrée du catalogue que la source). Faire
            // choisir la ligne à la main ouvrait la porte à « Frais
            // d'inscription » posé sur « Frais de Mars » (10/09/2026).
            'inscription_id' => ['required', 'integer', 'exists:inscriptions,id'],
            'motif' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motif.required' => __('A reason is required: it is what explains, later, why this money paid for someone else.'),
            'motif.min' => __('Please state the reason in a few words.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inscription_id' => __('target registration'),
            'motif' => __('reason'),
        ];
    }
}
