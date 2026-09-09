<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Expenses\Actions\AnnulerDepense;
use App\Models\Activity;
use App\Models\Depense;
use Illuminate\Console\Command;

/**
 * Annule une dépense saisie EN DOUBLE par écriture compensatoire — la caisse
 * est recréditée, aucune ligne n'est supprimée (§11).
 *
 * POURQUOI (09/09/2026)
 * ---------------------
 * DEP-012 et DEP-014 : 7 650,00 DH chacune, même date (29/08), même
 * description « * », même caisse (El Mehdi Bakhach, GLS Rabat), saisies à
 * onze minutes d'intervalle le 04/09, approuvées toutes les deux le 08/09.
 * La caisse n'avait reçu que 15 250 DH par transferts et cinq dépenses ont
 * été approuvées pour 22 500 DH : solde -7 250,00 DH. Sans DEP-014 la caisse
 * serait à +400,00 DH — exactement l'écart.
 *
 * CE QUE FAIT LA COMMANDE
 * -----------------------
 * Elle N'invente PAS le doublon : l'opérateur nomme la dépense EN TROP et
 * celle qui est CONSERVÉE, et la commande refuse d'agir tant que les preuves
 * ne concordent pas toutes — même caisse, même montant, même date, même
 * description, toutes deux « Approuvée », le doublon ayant exactement UN
 * débit journalisé et aucune écriture compensatoire. Elle n'a pas de
 * « --force » : si une preuve manque, on s'arrête et on regarde.
 *
 * Idempotente : la référence de correction est STABLE
 * (`CORRECTION-<ref>-DUPLICATE`), vérifiée dans le journal ET par le statut
 * « Annulée » ; une seconde exécution ne recrédite rien.
 *
 * Simulation par défaut ; --apply pour écrire.
 */
final class AnnulerDepenseDoublon extends Command
{
    protected $signature = 'depenses:annuler-doublon
        {reference : Référence de la dépense EN TROP (ex. DEP-014)}
        {conservee : Référence de la dépense CONSERVÉE dont elle est le doublon (ex. DEP-012)}
        {--apply : Applique réellement la correction (sinon simulation)}';

    protected $description = "Annule une dépense saisie en double par une écriture compensatoire (la caisse est recréditée, aucune ligne n'est supprimée)";

    /** Écart maximal entre les deux saisies pour parler d'un doublon. */
    private const FENETRE_MINUTES = 60;

    public function handle(AnnulerDepense $annuler): int
    {
        $reference = strtoupper(trim((string) $this->argument('reference')));
        $refConservee = strtoupper(trim((string) $this->argument('conservee')));
        $apply = (bool) $this->option('apply');
        $correction = self::referenceCorrection($reference);

        if ($reference === $refConservee) {
            $this->error('La dépense en trop et la dépense conservée doivent être différentes.');

            return self::FAILURE;
        }

        $doublon = Depense::query()->with(['caisse.etablissement', 'agent', 'approvedBy'])->where('reference', $reference)->first();
        $conservee = Depense::query()->with(['caisse'])->where('reference', $refConservee)->first();

        if ($doublon === null || $conservee === null) {
            $this->error('Dépense introuvable : '.($doublon === null ? $reference : $refConservee).'.');

            return self::FAILURE;
        }

        // ── Idempotence ────────────────────────────────────────────────
        if ($doublon->isAnnulee() || AnnulerDepense::correctionExiste($doublon, $correction)) {
            $this->warn("  {$reference} est déjà annulée ({$correction}) — aucune action.");
            $this->line('  Solde de la caisse : '.$this->money((float) $doublon->caisse?->solde).' DH');

            return self::SUCCESS;
        }

        // ── Preuves ────────────────────────────────────────────────────
        $sorties = $this->mouvements($doublon, 'Sortie');
        $entrees = $this->mouvements($doublon, 'Entrée');
        $ecartMinutes = $doublon->created_at !== null && $conservee->created_at !== null
            ? abs($doublon->created_at->diffInMinutes($conservee->created_at))
            : null;

        $preuves = [
            ['Toutes deux « Approuvée »', $doublon->isApprouvee() && $conservee->isApprouvee(),
                "{$reference} : {$doublon->statut} · {$refConservee} : {$conservee->statut}"],
            ['Même caisse', $doublon->caisse_id !== null && $doublon->caisse_id === $conservee->caisse_id,
                ($doublon->caisse?->nom ?? '—').' / '.($conservee->caisse?->nom ?? '—')],
            ['Même montant', (string) $doublon->montant === (string) $conservee->montant,
                $this->money((float) $doublon->montant).' / '.$this->money((float) $conservee->montant)],
            ['Même date de dépense', $doublon->date_depense?->toDateString() === $conservee->date_depense?->toDateString(),
                ($doublon->date_depense?->format('d/m/Y') ?? '—').' / '.($conservee->date_depense?->format('d/m/Y') ?? '—')],
            ['Même description', trim((string) $doublon->description) === trim((string) $conservee->description),
                '« '.trim((string) $doublon->description).' » / « '.trim((string) $conservee->description).' »'],
            ['Saisies à moins de '.self::FENETRE_MINUTES.' min', $ecartMinutes !== null && $ecartMinutes <= self::FENETRE_MINUTES,
                $ecartMinutes === null ? '—' : $ecartMinutes.' min'],
            ['Exactement UN débit journalisé pour '.$reference, $sorties === 1, $sorties.' débit(s)'],
            ['Aucune écriture compensatoire pour '.$reference, $entrees === 0, $entrees.' crédit(s)'],
        ];

        $this->line('');
        $this->line("  Dépense en trop : <info>{$reference}</info>  ·  conservée : <info>{$refConservee}</info>");
        $this->line('  Caisse          : '.($doublon->caisse?->nom ?? '—').' ('.($doublon->caisse?->etablissement?->nom_centre ?? '—').')');
        $this->line('  Saisie par      : '.($doublon->agent?->nomComplet() ?? '—').' le '.($doublon->created_at?->format('d/m/Y H:i') ?? '—'));
        $this->line('  Approuvée par   : '.($doublon->approvedBy?->nomComplet() ?? '—').' le '.($doublon->approved_at?->format('d/m/Y H:i') ?? '—'));
        $this->line('');
        $this->line('  Preuves du doublon :');

        $ok = true;
        foreach ($preuves as [$libelle, $vrai, $detail]) {
            $ok = $ok && $vrai;
            $this->line(sprintf('    %s %-48s %s', $vrai ? '<info>✔</info>' : '<error>✘</error>', $libelle, $detail));
        }

        $this->line('');

        if (! $ok) {
            $this->error("  Les preuves ne concordent pas : {$reference} n'est pas traitée comme un doublon. Rien n'a été écrit.");

            return self::FAILURE;
        }

        $montant = (float) $doublon->montant;
        $soldeAvant = (float) $doublon->caisse->solde;

        $this->line('  Écriture prévue :');
        $this->line("    Référence       : {$correction}");
        $this->line('    Caisse créditée : '.$doublon->caisse->nom.'  +'.$this->money($montant).' DH');
        $this->line('    Solde           : '.$this->money($soldeAvant).' DH  →  '.$this->money($soldeAvant + $montant).' DH');
        $this->line("    Statut          : {$doublon->statut}  →  ".Depense::STATUT_ANNULEE);
        $this->line('');

        if (! $apply) {
            $this->warn('  SIMULATION — relancez avec --apply pour appliquer.');

            return self::SUCCESS;
        }

        $annuler->handle(
            $doublon,
            $correction,
            "dépense saisie en double (doublon de {$refConservee})",
        );

        $this->info('  ✔ Correction appliquée.');
        $this->line('  Nouveau solde : '.$this->money((float) $doublon->caisse->fresh()->solde).' DH');

        return self::SUCCESS;
    }

    /** Stable, human-readable, unique per duplicate: CORRECTION-DEP-014-DUPLICATE. */
    public static function referenceCorrection(string $reference): string
    {
        return 'CORRECTION-'.strtoupper($reference).'-DUPLICATE';
    }

    /** Voir AnnulerDepense::mouvements() pour la comparaison de `origine_id`. */
    private function mouvements(Depense $depense, string $sens): int
    {
        return Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->where('properties->origine_type', Depense::class)
            ->where('properties->origine_id', (string) $depense->getKey())
            ->where('properties->sens', $sens)
            ->count();
    }

    private function money(float $v): string
    {
        return number_format($v, 2, ',', ' ');
    }
}
