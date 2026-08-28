<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Brings a re-enrolled student's payments onto the group he is ACTIVE in.
 *
 * The legacy import books each payment against whichever inscription resolved
 * at the time — usually the previous année's one, since the payments file
 * carries no group column. A student who moved to a still-running group
 * therefore shows an empty « Détails paiement » row there, while the old CRM
 * displays the money under the running group. Nothing was lost; it is filed
 * under the closed year.
 *
 * This command moves those payments onto the student's ACTIVE inscription in
 * a group « En formation », using the two existing money actions so every
 * invariant of CLAUDE.md §11 holds:
 *   1. ConvertirEncaissementsEnAvance detaches the payment from its old fee
 *      (row never deleted, the old fee recomputed back to Non payé),
 *   2. AppliquerAvance settles the SAME-NAMED fee on the active inscription,
 *      capped at what it still owes and COPYING the original date_paiement.
 *
 * `caisses.solde` never moves — the cash never left the till, only its
 * allocation changes. Money never crosses students: AppliquerAvance refuses a
 * fee belonging to anyone else, and the target is always the payer's own
 * inscription.
 *
 * ⚠ Deliberately narrow:
 *   - only groups « En formation », but EVERY registration of them (Active,
 *     Annulée, Changement) — the old CRM's matrix lists them all, and a
 *     cancelled student's money still belongs on the row where he appears;
 *   - only payments currently attached to ANOTHER group's inscription;
 *   - only where the active inscription carries a fee of the SAME NAME with
 *     something still owed — otherwise the payment stays exactly where it is.
 *
 * Reversible: each allocation is a new row linked to its avance
 * (applied_from_encaissement_id), so « Convertir en avance » on the
 * Encaissements screen undoes it.
 *
 * Usage:
 *   php artisan paiements:rapatrier --centre=marrakech --dry-run
 *   php artisan paiements:rapatrier --centre=marrakech
 */
final class RapatrierPaiementsGroupesActifs extends Command
{
    protected $signature = 'paiements:rapatrier
        {--centre= : Limiter à un centre (id ou partie du nom)}
        {--groupe= : Limiter à un groupe (id ou nom exact)}
        {--dry-run : Afficher ce qui serait rapatrié sans rien modifier}';

    protected $description = "Rapatrie sur son inscription du groupe en formation les paiements d'un étudiant restés sur l'inscription d'un autre groupe.";

    public function handle(ConvertirEncaissementsEnAvance $convertir, AppliquerAvance $appliquer): int
    {
        $groupes = Group::query()
            ->where('statut', Group::STATUT_EN_FORMATION)
            ->when($this->option('centre'), function ($q, $centre): void {
                $q->whereHas('etablissement', fn ($e) => is_numeric($centre)
                    ? $e->whereKey((int) $centre)
                    : $e->where('nom_centre', 'ilike', '%'.$centre.'%'));
            })
            ->when($this->option('groupe'), fn ($q, $g) => is_numeric($g)
                ? $q->whereKey((int) $g)
                : $q->where('nom', $g))
            ->orderBy('nom')
            ->get();

        $this->info(sprintf(
            '%d groupe(s) « En formation »%s.',
            $groupes->count(),
            $this->option('dry-run') ? '   [DRY-RUN]' : ''
        ));

        $rapatries = 0;
        $montantTotal = 0.0;
        $sansFrais = 0;

        // ⚠ ONE target per payment, decided once for all groups. 23 Marrakech
        // students hold a registration in SEVERAL running groups, so a
        // per-group loop planned the same payment once per group — 1 080
        // moves for 482 real payments, more money than the centre holds
        // (28/08/2026). The target is resolved per STUDENT first, preferring
        // an Active registration, then Changement, then Annulée.
        $cibleParStudent = [];

        foreach ($groupes as $groupe) {
            foreach (Inscription::query()->where('group_id', $groupe->id)->get() as $inscription) {
                $rang = match ($inscription->statut) {
                    Inscription::STATUT_ACTIVE => 0,
                    Inscription::STATUT_CHANGEMENT => 1,
                    default => 2,
                };

                $courant = $cibleParStudent[$inscription->student_id] ?? null;

                if ($courant === null || $rang < $courant['rang']) {
                    $cibleParStudent[$inscription->student_id] = [
                        'rang' => $rang,
                        'inscription' => $inscription,
                        'groupe' => $groupe,
                    ];
                }
            }
        }

        $parGroupe = [];

        foreach ($cibleParStudent as $studentId => $cible) {
            $feesCible = InscriptionFee::query()
                ->where('inscription_id', $cible['inscription']->id)
                ->whereNull('masque_le')
                ->get();

            if ($feesCible->isEmpty()) {
                continue;
            }

            // This student's payments sitting on ANOTHER inscription.
            $ailleurs = Encaissement::query()
                ->where('student_id', $studentId)
                ->whereNotNull('inscription_fee_id')
                ->whereHas('fee.inscription', fn ($i) => $i->where('id', '!=', $cible['inscription']->id))
                ->with('fee.inscription')
                ->orderBy('date_paiement')
                ->get();

            // ⚠ The remaining due is tracked AS THE PLAN IS BUILT, not read
            // once per fee: several payments often target the same line (a
            // 300 + 1 000 pair on a 1 300 fee), and checking each against the
            // untouched balance let both through — AppliquerAvance then
            // refused the second with « Le montant ne peut pas dépasser le
            // reste à payer » and aborted the whole run (28/08/2026).
            $restant = [];

            foreach ($ailleurs as $paiement) {
                $nom = $this->cle((string) ($paiement->fee?->nom ?? ''));
                $fee = $feesCible->first(fn (InscriptionFee $f): bool => $this->cle($f->nom) === $nom);

                if ($fee === null) {
                    $sansFrais++;

                    continue;
                }

                $restant[$fee->id] ??= round((float) $fee->montant - $fee->montantPaye(), 2);
                $part = min($restant[$fee->id], (float) $paiement->montant);

                if ($part <= 0.0) {
                    $sansFrais++;

                    continue;
                }

                $restant[$fee->id] = round($restant[$fee->id] - $part, 2);
                $parGroupe[$cible['groupe']->nom][] = [$paiement, $fee, $part];
            }
        }

        ksort($parGroupe);

        foreach ($parGroupe as $nomGroupe => $lignes) {
            $this->line(sprintf('  %s', $nomGroupe));

            foreach ($lignes as [$paiement, $fee, $part]) {
                $this->line(sprintf(
                    '      %-11s %9s  %-28s %s',
                    $paiement->reference,
                    number_format($part, 2, '.', ''),
                    mb_substr((string) $fee->nom, 0, 28),
                    substr((string) $paiement->date_paiement, 0, 10)
                ));

                if (! $this->option('dry-run')) {
                    DB::transaction(function () use ($convertir, $appliquer, $paiement, $fee, $part): void {
                        $source = $paiement->fee?->inscription;

                        if ($source === null) {
                            return;
                        }

                        $convertir->handle($source, [$paiement->id]);

                        $avance = $paiement->fresh();

                        $cible = $fee->fresh();

                        if ($avance === null || $cible === null) {
                            return;
                        }

                        // Cap against the LIVE balance too: the plan was built
                        // before any move was applied.
                        $part = min(
                            $part,
                            (float) $avance->montantRestant(),
                            round((float) $cible->montant - $cible->montantPaye(), 2),
                        );

                        if ($part > 0.0) {
                            $appliquer->handle($avance, $cible, $part);
                        }
                    });
                }

                $rapatries++;
                $montantTotal += $part;
            }
        }

        $this->line('');
        $this->info(sprintf(
            '%s%d paiement(s) rapatrié(s) pour %s MAD.',
            $this->option('dry-run') ? '[DRY-RUN] ' : '',
            $rapatries,
            number_format($montantTotal, 2, '.', '')
        ));

        if ($sansFrais > 0) {
            $this->warn(sprintf(
                '%d paiement(s) laissé(s) en place : aucun frais du même nom (ou déjà soldé) sur son inscription active.',
                $sansFrais
            ));
        }

        if (! $this->option('dry-run') && $rapatries > 0) {
            $this->line('');
            $this->comment('Réversible : « Convertir en avance » sur l\'écran Encaissements remet le paiement en avance.');
        }

        return self::SUCCESS;
    }

    private function cle(string $valeur): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim(mb_strtolower($valeur)));
    }
}
