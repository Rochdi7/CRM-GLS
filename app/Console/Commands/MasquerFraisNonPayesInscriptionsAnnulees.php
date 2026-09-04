<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattrapage ponctuel : le dû fantôme des inscriptions DÉJÀ annulées.
 *
 * « Annuler l'inscription » propose depuis toujours de retirer les frais que
 * l'étudiant ne devra jamais (AnnulerInscription::$unpaidFeesScope), mais
 * l'option est facultative et n'existait pas au moment de l'import legacy.
 * Résultat : 2 644 dossiers Annulée traînent 18 537 lignes de frais jamais
 * payées — plus de 20 M DH de créance qui n'existe pas, comptée dans le dû
 * de l'étudiant, dans les retards et dans les récapitulatifs.
 *
 * La commande fait, rétroactivement, ce que fait la corbeille du modal
 * (BasculerVisibiliteFraisInscription::hide) : elle pose `masque_le`. Elle ne
 * SUPPRIME rien — la ligne et son historique restent, et un « Restaurer » la
 * ramène si l'annulation était une erreur. montant_total est recalculé sur
 * les frais encore visibles, comme partout ailleurs.
 *
 * ⚠ Une ligne qui a reçu le moindre dirham n'est JAMAIS touchée — ni un frais
 * « Payé », ni un « Payé partiellement ». Décidé par l'utilisateur le
 * 04/09/2026 : le retrait ne doit concerner que ce qui n'a jamais rien reçu.
 * La commande ne détache donc aucun encaissement et n'appelle pas
 * ConvertirEncaissementsEnAvance — il n'y a, par construction, pas un dirham
 * à libérer (§11 : les trois chemins de retrait libèrent l'argent ; ici on
 * ne retire aucun frais qui en porte). Un frais partiellement payé reste dû
 * pour son reste, ce qui est le comportement voulu : l'étudiant a commencé à
 * payer cette prestation.
 *
 * Dry-run par défaut, comme caisse:recalculer-soldes — lire la sortie avant
 * --apply. Idempotent : une ligne masquée n'est plus vue par la requête.
 */
final class MasquerFraisNonPayesInscriptionsAnnulees extends Command
{
    protected $signature = 'inscriptions:masquer-frais-non-payes-annulees
        {--apply : Écrire les changements (sans ce drapeau, simple simulation)}
        {--centre= : Limiter à un établissement (id)}
        {--details : Lister chaque ligne de frais au lieu du résumé par inscription}';

    protected $description = "Masque les frais jamais payés des inscriptions déjà annulées (dû fantôme). Ne touche aucun frais ayant reçu de l'argent.";

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $centre = $this->option('centre');

        $inscriptions = Inscription::query()
            ->where('statut', Inscription::STATUT_ANNULEE)
            ->when($centre !== null, fn ($q) => $q->where('etablissement_id', (int) $centre))
            ->whereHas('fees', fn ($q) => $this->scopeRetirables($q))
            ->with([
                'student:id,nom,prenom',
                'fees' => fn ($q) => $this->scopeRetirables($q),
            ])
            ->orderBy('id')
            ->get();

        if ($inscriptions->isEmpty()) {
            $this->info('Aucun frais non payé sur une inscription annulée. Rien à faire.');

            return self::SUCCESS;
        }

        $totalLignes = 0;
        $totalMontant = 0.0;

        foreach ($inscriptions as $inscription) {
            $lignes = $inscription->fees;
            $montant = round((float) $lignes->sum('montant'), 2);
            $etudiant = trim(($inscription->student?->prenom ?? '').' '.($inscription->student?->nom ?? ''));

            $this->line(sprintf(
                '  %s — %s : %d frais, %s DH',
                $inscription->reference,
                $etudiant !== '' ? $etudiant : 'étudiant inconnu',
                $lignes->count(),
                number_format($montant, 2, '.', ' '),
            ));

            if ($this->option('details')) {
                foreach ($lignes as $fee) {
                    $this->line(sprintf(
                        '      · %s (%s) — %s DH',
                        $fee->nom,
                        $fee->statut,
                        number_format((float) $fee->montant, 2, '.', ' '),
                    ));
                }
            }

            if ($apply) {
                DB::transaction(function () use ($inscription, $lignes): void {
                    foreach ($lignes as $fee) {
                        // Un par un : un update de query builder ne déclenche
                        // aucun événement Eloquent, donc rien dans le journal
                        // d'audit (§11).
                        $fee->update([
                            'masque_le' => now(),
                            'masque_origine' => InscriptionFee::MASQUE_ORIGINE_MANUEL,
                        ]);
                    }

                    $inscription->update([
                        'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
                    ]);
                });
            }

            $totalLignes += $lignes->count();
            $totalMontant += $montant;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d inscription(s) annulée(s), %d ligne(s) de frais masquée(s), %s DH de dû fantôme retiré.',
            $apply ? 'Appliqué' : 'Simulation (aucune écriture)',
            $inscriptions->count(),
            $totalLignes,
            number_format($totalMontant, 2, '.', ' '),
        ));

        $this->comment("Les frais ayant reçu de l'argent (payés ou partiellement payés) n'ont pas été touchés.");

        if (! $apply) {
            $this->comment('Relancer avec --apply pour écrire.');
        }

        return self::SUCCESS;
    }

    /**
     * Les lignes retirables : visibles, jamais entièrement payées, et surtout
     * n'ayant reçu AUCUN encaissement. Le statut ne suffit pas — un « Payé
     * partiellement » porte de l'argent réel, et un statut peut avoir dérivé ;
     * l'absence d'encaissement est le seul critère sûr.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<InscriptionFee>  $query
     */
    private function scopeRetirables($query): void
    {
        $query->whereNull('masque_le')
            ->where('statut', '!=', InscriptionFee::STATUT_PAYE)
            ->whereDoesntHave('encaissements');
    }
}
