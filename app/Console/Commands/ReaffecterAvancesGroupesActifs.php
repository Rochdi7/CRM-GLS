<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-attaches unallocated avances to the fee they were ORIGINALLY paid for,
 * on the student's inscription in a still-running group.
 *
 * Why they became avances in the first place: the legacy import could not
 * always resolve which fee a payment settled (label drift, a fee line that
 * did not exist yet, a payment detached during a group change), so the money
 * landed as an unallocated advance. « Détails paiement » then showed an empty
 * cell even though the student had paid — the matrix only reads fee-attached
 * rows.
 *
 * The fee is recovered from the IMPORT ROW: `import_rows.raw->frais_label`
 * still holds exactly what the old CRM wrote for that payment (« Frais de
 * Janvier », « Frais d'inscription A1/A2/B1 »…). Nothing is guessed — an
 * avance whose source row had no label, or whose label matches no fee line
 * on the target inscription, is left untouched and reported.
 *
 * ⚠ Scope is deliberately narrow:
 *   - only groups whose statut is « En formation » (the running ones);
 *   - only the student's OWN inscription in that group — money never moves
 *     between students (AppliquerAvance refuses it anyway);
 *   - the payment DATE is preserved (AppliquerAvance copies date_paiement);
 *   - `caisses.solde` never moves — the cash never left the till, only its
 *     allocation changes.
 *
 * Reversible: the allocation is a NEW row linked to its avance
 * (applied_from_encaissement_id), so « Convertir en avance » on the
 * Encaissements screen puts the money back exactly where it was.
 *
 * Usage:
 *   php artisan avances:reaffecter --centre=marrakech --dry-run
 *   php artisan avances:reaffecter --centre=marrakech
 */
final class ReaffecterAvancesGroupesActifs extends Command
{
    protected $signature = 'avances:reaffecter
        {--centre= : Limiter à un centre (id ou partie du nom)}
        {--groupe= : Limiter à un groupe (id ou nom exact)}
        {--tous-statuts : Traiter tous les groupes, pas seulement « En formation »}
        {--dry-run : Afficher ce qui serait rattaché sans rien modifier}';

    protected $description = "Rattache les avances au frais qu'elles réglaient à l'origine, sur les inscriptions des groupes en formation.";

    public function handle(AppliquerAvance $appliquer): int
    {
        $groupes = Group::query()
            ->when(! $this->option('tous-statuts'), fn ($q) => $q->where('statut', Group::STATUT_EN_FORMATION))
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
            '%d groupe(s)%s%s.',
            $groupes->count(),
            $this->option('tous-statuts') ? '' : ' « En formation »',
            $this->option('dry-run') ? '   [DRY-RUN]' : ''
        ));

        $rattaches = 0;
        $montant = 0.0;
        $sansLabel = 0;
        $sansFrais = 0;

        foreach ($groupes as $groupe) {
            $inscriptions = Inscription::query()
                ->where('group_id', $groupe->id)
                ->get()
                ->keyBy('student_id');

            if ($inscriptions->isEmpty()) {
                continue;
            }

            $avances = Encaissement::query()
                ->whereNull('inscription_fee_id')
                // Skip application rows: their money is USED on the parent.
                ->whereNull('applied_from_encaissement_id')
                ->whereIn('student_id', $inscriptions->keys())
                ->orderBy('date_paiement')
                ->get();

            if ($avances->isEmpty()) {
                continue;
            }

            $lignes = [];

            foreach ($avances as $avance) {
                $label = $this->fraisDOrigine($avance);

                if ($label === null) {
                    $sansLabel++;

                    continue;
                }

                /** @var Inscription $inscription */
                $inscription = $inscriptions->get($avance->student_id);

                $fee = InscriptionFee::query()
                    ->where('inscription_id', $inscription->id)
                    ->whereNull('masque_le')
                    ->get()
                    ->first(fn (InscriptionFee $f): bool => $this->cle($f->nom) === $this->cle($label));

                if ($fee === null) {
                    $sansFrais++;

                    continue;
                }

                $du = round((float) $fee->montant - $fee->montantPaye(), 2);
                $part = min($du, (float) $avance->montantRestant());

                if ($part <= 0.0) {
                    $sansFrais++;

                    continue;
                }

                $lignes[] = [$avance, $fee, $part, $label];
            }

            if ($lignes === []) {
                continue;
            }

            $this->line(sprintf('  %s', $groupe->nom));

            foreach ($lignes as [$avance, $fee, $part, $label]) {
                $this->line(sprintf(
                    '      %-11s %9s  %-28s %s',
                    $avance->reference,
                    number_format($part, 2, '.', ''),
                    mb_substr($label, 0, 28),
                    substr((string) $avance->date_paiement, 0, 10)
                ));

                if (! $this->option('dry-run')) {
                    DB::transaction(fn () => $appliquer->handle($avance, $fee, $part));
                }

                $rattaches++;
                $montant += $part;
            }
        }

        $this->line('');
        $this->info(sprintf(
            '%s%d avance(s) rattachée(s) pour %s MAD.',
            $this->option('dry-run') ? '[DRY-RUN] ' : '',
            $rattaches,
            number_format($montant, 2, '.', '')
        ));

        if ($sansLabel > 0) {
            $this->warn(sprintf(
                '%d avance(s) laissée(s) telle(s) quelle(s) : le fichier source ne nommait aucun frais.',
                $sansLabel
            ));
        }

        if ($sansFrais > 0) {
            $this->warn(sprintf(
                '%d avance(s) laissée(s) telle(s) quelle(s) : aucun frais correspondant (ou déjà soldé) sur son inscription.',
                $sansFrais
            ));
        }

        if (! $this->option('dry-run') && $rattaches > 0) {
            $this->line('');
            $this->comment('Réversible : « Convertir en avance » sur l\'écran Encaissements remet l\'argent en avance.');
        }

        return self::SUCCESS;
    }

    /**
     * The fee label the old CRM wrote for this payment, read back from the
     * import row its note references. Returns null when the payment was not
     * imported, or when the source really carried no fee (a genuine avance:
     * money received now, to be allocated later — those must stay avances).
     */
    private function fraisDOrigine(Encaissement $avance): ?string
    {
        if ($avance->legacy_ref === null && preg_match('/Réf\. (\S+?)\)/u', (string) $avance->note, $m) !== 1) {
            return null;
        }

        $ref = $avance->legacy_ref ?? $m[1];

        $row = ImportRow::query()
            ->whereHas('batch', fn ($q) => $q
                ->where('module', ImportBatch::MODULE_ENCAISSEMENTS)
                ->where('etablissement_id', $avance->etablissement_id))
            ->where('legacy_ref', $ref)
            ->first(['raw']);

        $label = trim((string) ($row?->raw['frais_label'] ?? ''));

        return $label === '' || $label === '-' ? null : $label;
    }

    private function cle(string $valeur): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim(mb_strtolower($valeur)));
    }
}
