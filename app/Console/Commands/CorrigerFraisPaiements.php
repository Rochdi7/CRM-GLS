<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Frais;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Puts an imported payment on the fee its SOURCE ROW names.
 *
 * The import's loose fallback dropped a payment onto "the first unpaid line"
 * when its label matched nothing — so a 2 300 DH « ÖSD B1 » exam landed on
 * a 200 DH inscription fee (MOUNA SAMMOU, 28/08/2026). Later cleanups hid
 * fee lines and detached payments, leaving avances whose fee still exists
 * in `import_rows.raw->frais_label`.
 *
 * Two cases, same source of truth (the import row):
 *   - MIS-ATTACHED: payment on a fee ≠ its label → detach, then apply to the
 *     right fee;
 *   - AVANCE with a label → apply to the right fee.
 * The right fee is looked up on the student's own inscriptions; a hidden
 * line is un-hidden, a missing one is created from the catalogue. A row
 * whose source names no fee is a real avance and is never touched.
 *
 * Existing money actions only — rows never deleted, caisses.solde and
 * date_paiement untouched.
 */
final class CorrigerFraisPaiements extends Command
{
    protected $signature = 'paiements:corriger-frais
        {--centre= : Limiter à un centre (id ou partie du nom)}
        {--dry-run : Afficher sans modifier}';

    protected $description = "Rattache chaque paiement importé au frais que nomme sa ligne d'import (ÖSD, frais masqué, avance).";

    /** Source label (normalized) => catalogue fee (normalized). */
    private const array ALIAS = [
        'osda1' => 'fraisdexamosda1',
        'osdb1' => 'fraisdexamosdb1',
        'osdb2' => 'fraisdexamosdb2',
        'examenosd' => 'fraisdexamosdb1',
        'fraisdinscription' => 'fraisdinscriptiona1a2b1',
        'fraisdinscription1' => 'fraisdinscriptiona1a2b1',
        'fraisdinscriptiona1' => 'fraisdinscriptiona1a2b1',
        'fraisdinscriptiona2' => 'fraisdinscriptiona1a2b1',
        'fraisdinscriptionb1' => 'fraisdinscriptiona1a2b1',
        'fraisdinscription2' => 'fraisdinscriptionb2',
    ];

    public function handle(ConvertirEncaissementsEnAvance $convertir, AppliquerAvance $appliquer): int
    {
        $dry = (bool) $this->option('dry-run');
        $catalogue = Frais::all()->keyBy(fn (Frais $f): string => $this->cle($f->nom));

        $rows = ImportRow::query()
            ->whereHas('batch', function ($q): void {
                $q->where('module', ImportBatch::MODULE_ENCAISSEMENTS)
                    ->when($this->option('centre'), fn ($b, $c) => $b->whereHas('etablissement', fn ($e) => is_numeric($c)
                        ? $e->whereKey((int) $c)
                        : $e->where('nom_centre', 'ilike', '%'.$c.'%')));
            })
            ->where('status', ImportRow::STATUT_INSERE)
            ->whereNotNull('created_model_id')
            ->get(['raw', 'created_model_id']);

        $corriges = 0;
        $montant = 0.0;
        $demasques = 0;
        $crees = 0;
        $sansCatalogue = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row->raw['frais_label'] ?? ''));

            if ($label === '' || $label === '-' || str_contains($label, ',')) {
                continue;
            }

            $cible = self::ALIAS[$this->cle($label)] ?? $this->cle($label);
            $frais = $catalogue->get($cible);

            if ($frais === null) {
                $sansCatalogue[$label] = ($sansCatalogue[$label] ?? 0) + 1;

                continue;
            }

            $paiement = Encaissement::with('fee.inscription')->find($row->created_model_id);

            if ($paiement === null) {
                continue;
            }

            $dejaBon = $paiement->fee !== null && $this->cle($paiement->fee->nom) === $cible;
            // An application row is never a fresh avance — its money is
            // already used on its parent. See AppliquerMatriceGroupe.
            $estAvance = $paiement->fee === null
                && $paiement->applied_from_encaissement_id === null
                && $paiement->montantRestant() > 0;

            if ($dejaBon || (! $estAvance && $paiement->fee === null)) {
                continue;
            }

            // Where the fee should be: the inscription the payment sits on,
            // else the student's active one, else any.
            $inscription = $paiement->fee?->inscription
                ?? Inscription::where('student_id', $paiement->student_id)
                    ->orderByRaw('case statut when ? then 0 else 1 end', [Inscription::STATUT_ACTIVE])
                    ->first();

            if ($inscription === null) {
                continue;
            }

            [$fee, $action] = $this->ligneCible($inscription, $frais, $dry);

            if ($action === 'demasque') {
                $demasques++;
            } elseif ($action === 'cree') {
                $crees++;
            }

            $this->line(sprintf(
                '  %-11s %9s  %-26s -> %-28s %s',
                $paiement->reference,
                number_format((float) $paiement->montant, 2, '.', ''),
                mb_substr($paiement->fee?->nom ?? 'AVANCE', 0, 26),
                mb_substr($frais->nom, 0, 28),
                $action
            ));

            if (! $dry) {
                DB::transaction(function () use ($convertir, $appliquer, $paiement, $fee): void {
                    if ($paiement->fee !== null) {
                        $convertir->handle($paiement->fee->inscription, [$paiement->id]);
                    }

                    $avance = $paiement->fresh();
                    $part = min($avance->montantRestant(), round((float) $fee->montant - $fee->montantPaye(), 2));

                    // An exam fee is billed at whatever was paid — no cap.
                    if ($part <= 0.0) {
                        $part = $avance->montantRestant();
                    }

                    if ($part > 0.0) {
                        $appliquer->handle($avance, $fee->fresh(), $part);
                    }
                });
            }

            $corriges++;
            $montant += (float) $paiement->montant;
        }

        $this->line('');
        $this->info(sprintf(
            '%s%d paiement(s) corrigé(s) pour %s MAD — %d ligne(s) démasquée(s), %d créée(s).',
            $dry ? '[DRY-RUN] ' : '',
            $corriges, number_format($montant, 2, '.', ''), $demasques, $crees
        ));

        foreach ($sansCatalogue as $l => $n) {
            $this->warn(sprintf('  %d × « %s » : aucun frais du catalogue — laissé tel quel.', $n, $l));
        }

        return self::SUCCESS;
    }

    /**
     * The fee line for $frais on $inscription: visible one, else un-hide
     * the hidden one, else create it from the catalogue.
     *
     * @return array{0: InscriptionFee, 1: string}
     */
    private function ligneCible(Inscription $inscription, Frais $frais, bool $dry): array
    {
        $lignes = InscriptionFee::where('inscription_id', $inscription->id)->get()
            ->filter(fn (InscriptionFee $f): bool => $this->cle($f->nom) === $this->cle($frais->nom));

        $visible = $lignes->first(fn (InscriptionFee $f): bool => $f->masque_le === null);

        if ($visible !== null) {
            return [$visible, 'ok'];
        }

        $masquee = $lignes->first();

        if ($masquee !== null) {
            if (! $dry) {
                $masquee->update(['masque_le' => null]);
            }

            return [$masquee, 'demasque'];
        }

        if ($dry) {
            return [new InscriptionFee(['nom' => $frais->nom, 'montant' => $frais->montant_defaut]), 'cree'];
        }

        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $frais->id,
            'nom' => $frais->nom,
            'montant_initial' => $frais->montant_defaut,
            'montant' => $frais->montant_defaut,
            'date_echeance' => $inscription->date_inscription,
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$fee, 'cree'];
    }

    private function cle(string $v): string
    {
        $v = strtr(mb_strtolower($v), ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ô' => 'o', 'ö' => 'o', 'û' => 'u', 'ç' => 'c', 'â' => 'a']);

        return (string) preg_replace('/[^a-z0-9]/', '', $v);
    }
}
