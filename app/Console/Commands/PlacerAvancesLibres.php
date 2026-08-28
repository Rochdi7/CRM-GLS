<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Settles the IMPORTED avances that name no fee at all — the old CRM's
 * payments with « - » in the Frais column (116 rows / 58 520 DH on
 * 29/08/2026, after every matrix pass had placed what could be placed by
 * evidence).
 *
 * There is no source of truth for these: wimschool's own cashier never
 * picked a month. So this applies the rule a cashier follows at the till —
 * on the student's inscription (Active first, then a running group, then
 * the most recent), pay the UNPAID fees in due-date order, oldest first,
 * splitting the avance across several fees when it covers more than one.
 *
 * Imported money only (legacy_ref chain); an avance received in the CRM
 * itself is the cashier's to allocate. Existing money actions only —
 * caisses.solde and date_paiement untouched, reversible with « Convertir
 * en avance ».
 *
 * Usage:
 *   php artisan avances:placer-libres --dry-run
 *   php artisan avances:placer-libres --centre=5
 */
final class PlacerAvancesLibres extends Command
{
    protected $signature = 'avances:placer-libres
        {--centre= : Limiter à un centre (id)}
        {--dry-run : Afficher sans modifier}';

    protected $description = "Règle les avances importées sans frais nommé sur les frais impayés de l'étudiant, par ordre d'échéance.";

    public function handle(AppliquerAvance $appliquer): int
    {
        $dry = (bool) $this->option('dry-run');

        $avances = Encaissement::query()
            ->whereNull('inscription_fee_id')
            ->when($this->option('centre'), fn ($q, $c) => $q->where('etablissement_id', (int) $c))
            ->with('student')
            ->orderBy('etablissement_id')
            ->orderBy('date_paiement')
            ->get()
            ->filter(fn (Encaissement $e): bool => $this->estImporte($e) && $e->montantRestant() > 0.0);

        $places = 0;
        $montant = 0.0;
        $sansInscription = [];
        $sansFrais = [];
        // Remaining due per fee, tracked AS THE PLAN IS BUILT: two avances
        // of one student target the same fees, and checking each against
        // the untouched balance would plan the same 300 DH twice.
        $restantParFee = [];

        foreach ($avances as $avance) {
            $inscription = Inscription::query()
                ->where('student_id', $avance->student_id)
                ->with('group')
                ->get()
                ->sortBy([
                    fn (Inscription $a, Inscription $b): int => ($a->statut === Inscription::STATUT_ACTIVE ? 0 : 1) <=> ($b->statut === Inscription::STATUT_ACTIVE ? 0 : 1),
                    fn (Inscription $a, Inscription $b): int => (($a->group?->statut ?? '') === Group::STATUT_EN_FORMATION ? 0 : 1) <=> (($b->group?->statut ?? '') === Group::STATUT_EN_FORMATION ? 0 : 1),
                    fn (Inscription $a, Inscription $b): int => strcmp((string) $b->date_inscription, (string) $a->date_inscription),
                ])
                ->first();

            if ($inscription === null) {
                $sansInscription[] = $avance;

                continue;
            }

            $reste = $avance->montantRestant();
            $lignes = [];

            $fees = InscriptionFee::query()
                ->where('inscription_id', $inscription->id)
                ->whereNull('masque_le')
                ->orderByRaw('date_echeance asc nulls last')
                ->orderBy('id')
                ->get();

            foreach ($fees as $fee) {
                if ($reste <= 0.0) {
                    break;
                }

                $restantParFee[$fee->id] ??= round((float) $fee->montant - $fee->montantPaye(), 2);

                if ($restantParFee[$fee->id] <= 0.0) {
                    continue;
                }

                $part = min($restantParFee[$fee->id], $reste);
                $lignes[] = [$fee, $part];
                $reste = round($reste - $part, 2);
                $restantParFee[$fee->id] = round($restantParFee[$fee->id] - $part, 2);
            }

            if ($lignes === []) {
                $sansFrais[] = $avance;

                continue;
            }

            foreach ($lignes as [$fee, $part]) {
                $this->line(sprintf(
                    '  %-10s %-26s %8s -> %-24s %-26s %s',
                    $avance->reference,
                    mb_substr(trim($avance->student->prenom.' '.$avance->student->nom), 0, 26),
                    number_format($part, 2, '.', ''),
                    mb_substr($fee->nom, 0, 24),
                    mb_substr((string) ($inscription->group?->nom ?? '?'), 0, 26),
                    $inscription->statut
                ));

                if (! $dry) {
                    DB::transaction(function () use ($appliquer, $avance, $fee, $part): void {
                        $avance = $avance->fresh();
                        $cible = $fee->fresh();

                        if ($avance === null || $cible === null || ! $avance->isAvance()) {
                            return;
                        }

                        $part = min($part, $avance->montantRestant(), round((float) $cible->montant - $cible->montantPaye(), 2));

                        if ($part > 0.0) {
                            $appliquer->handle($avance, $cible, $part);
                        }
                    });
                }

                $places++;
                $montant += $part;
            }
        }

        $this->line('');
        $this->info(sprintf('%s%d ligne(s) placée(s) pour %s MAD sur %d avance(s).', $dry ? '[DRY-RUN] ' : '', $places, number_format($montant, 2, '.', ''), $avances->count()));

        foreach (['sans inscription' => $sansInscription, 'aucun frais impayé sur son inscription' => $sansFrais] as $motif => $liste) {
            if ($liste === []) {
                continue;
            }

            $this->warn(sprintf('  %d avance(s) laissée(s) — %s :', count($liste), $motif));

            foreach ($liste as $a) {
                $this->line(sprintf('      %-10s %-26s %8s', $a->reference, mb_substr(trim($a->student->prenom.' '.$a->student->nom), 0, 26), number_format($a->montantRestant(), 2, '.', '')));
            }
        }

        return self::SUCCESS;
    }

    private function estImporte(Encaissement $e): bool
    {
        for ($i = 0; $i < 10 && $e !== null; $i++) {
            if ($e->legacy_ref !== null) {
                return true;
            }

            if ($e->applied_from_encaissement_id === null) {
                return false;
            }

            $e = Encaissement::find($e->applied_from_encaissement_id);
        }

        return false;
    }
}
