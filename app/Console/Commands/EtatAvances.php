<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Etablissement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY snapshot of unallocated advances, per centre.
 *
 * An avance is a payment with NO fee attached (`inscription_fee_id IS NULL`) —
 * including a row DETACHED from a fee later, which is an avance again
 * (§11 « Deliberate exceptions »). What is still available is
 * montant − applied to fees − refunded, the very formula the Avances tab uses
 * (`GetEncaissementsList::AVANCE_RESTANT_SQL`), so this command and the screen
 * can never disagree.
 *
 * Usage:
 *   php artisan avances:etat
 *   php artisan avances:etat --detail
 */
final class EtatAvances extends Command
{
    protected $signature = 'avances:etat
        {--detail : Lister les avances encore disponibles, étudiant par étudiant}
        {--centre= : Limiter à un centre (id)}';

    protected $description = 'État des avances non affectées par centre : nombre et montant encore disponible (lecture seule).';

    /** Same expression as the Avances tab: montant − applied − refunded. */
    private const string RESTANT = '(encaissements.montant'
        .' - coalesce((select sum(a.montant) from encaissements a'
        .' where a.applied_from_encaissement_id = encaissements.id), 0)'
        .' - coalesce((select sum(r.montant) from remboursements r'
        .' where r.encaissement_id = encaissements.id), 0))';

    public function handle(): int
    {
        $centres = Etablissement::query()
            ->when($this->option('centre'), fn ($q, $c) => $q->whereKey((int) $c))
            ->orderBy('id')
            ->get();

        $this->line('');
        $this->line(sprintf('  %-16s %8s %14s %8s %14s', 'CENTRE', 'avances', 'reçu', 'restant', 'disponible'));
        $this->line('  '.str_repeat('─', 64));

        $totN = 0;
        $totRecu = 0.0;
        $totRestN = 0;
        $totDispo = 0.0;

        foreach ($centres as $centre) {
            $base = DB::table('encaissements')
                ->where('etablissement_id', $centre->id)
                ->whereNull('inscription_fee_id');

            $n = (clone $base)->count();
            $recu = (float) (clone $base)->sum('montant');

            $restants = (clone $base)->whereRaw(self::RESTANT.' > 0.005');
            $nRest = (clone $restants)->count();
            $dispo = (float) (clone $restants)->selectRaw('coalesce(sum('.self::RESTANT.'), 0) s')->value('s');

            $this->line(sprintf(
                '  %-16s %8d %14s %8d %14s',
                mb_substr(str_replace('GLS ', '', $centre->nom_centre), 0, 16),
                $n, number_format($recu, 0, '.', ' '), $nRest, number_format($dispo, 0, '.', ' ')
            ));

            $totN += $n;
            $totRecu += $recu;
            $totRestN += $nRest;
            $totDispo += $dispo;

            if ($this->option('detail') && $nRest > 0) {
                $lignes = (clone $restants)
                    ->join('students', 'students.id', '=', 'encaissements.student_id')
                    ->orderByDesc(DB::raw(self::RESTANT))
                    ->selectRaw("encaissements.reference, encaissements.date_paiement, students.prenom, students.nom, encaissements.montant, ".self::RESTANT.' as restant')
                    ->get();

                foreach ($lignes as $l) {
                    $this->line(sprintf(
                        '        %-11s %-10s %-30s reçu %9s   dispo %9s',
                        $l->reference, substr((string) $l->date_paiement, 0, 10),
                        mb_substr(trim($l->prenom.' '.$l->nom), 0, 30),
                        number_format((float) $l->montant, 2, '.', ' '),
                        number_format((float) $l->restant, 2, '.', ' ')
                    ));
                }
            }
        }

        $this->line('  '.str_repeat('─', 64));
        $this->line(sprintf(
            '  %-16s %8d %14s %8d %14s',
            'TOTAL', $totN, number_format($totRecu, 0, '.', ' '), $totRestN, number_format($totDispo, 0, '.', ' ')
        ));
        $this->line('');
        $this->comment('« avances » = paiements sans frais rattaché ; « disponible » = reçu − appliqué − remboursé (formule de l\'onglet Avances).');

        return self::SUCCESS;
    }
}
