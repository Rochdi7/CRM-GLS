<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Caisse;
use App\Models\Remboursement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Répare le remboursement saisi en double le 03/09/2026 (ELWARDI MOHAMMED
 * YASSER, 300 DH espèces).
 *
 * CE QUI S'EST PASSÉ
 * ------------------
 * La liste des remboursements déduisait le centre de la CAISSE débitée. Les
 * deux caissiers ont leur caisse rattachée à GLS Marrakech, l'étudiant est à
 * GLS Kénitra : la ligne n'apparaissait donc sur AUCUN des deux centres.
 * Ne voyant rien enregistré, le remboursement a été saisi une seconde fois
 * presque trois heures plus tard :
 *
 *   RMB-001  19:31  caisse Rafik (id 1)      non lié à un paiement
 *   RMB-002  22:14  caisse Karouali (id 28)  lié à ENC-14782, caisse à 0 → -300
 *
 * 600 DH sont sortis des caisses pour un remboursement de 300 DH réellement
 * remis à l'étudiant.
 *
 * CE QUE FAIT CETTE COMMANDE (une seule transaction)
 * --------------------------------------------------
 *  1. Annule RMB-001 : la caisse Rafik est RECRÉDITÉE de 300 DH.
 *  2. Déplace RMB-002 de la caisse Karouali vers la caisse Rafik : Karouali
 *     est recréditée (elle revient de -300 à 0), Rafik est débitée.
 *  3. Fixe le centre de RMB-002 sur celui du paiement d'origine (Kénitra).
 *
 * RMB-002 est conservé — et non RMB-001 — parce qu'il porte le lien vers
 * ENC-14782. Ce lien est ce qui marque le paiement comme remboursé : sans
 * lui, la même avance de 300 DH resterait « disponible » et pourrait être
 * remboursée une deuxième fois ou appliquée à un frais
 * (Encaissement::montantUtilise()).
 *
 * Aucune ligne n'est supprimée (§11 : les enregistrements monétaires sont
 * append-only) et TOUT mouvement passe par CaisseLedger, donc reste visible
 * au journal d'audit.
 *
 * Simulation par défaut ; --apply pour valider.
 */
final class ReparerRemboursementElwardi extends Command
{
    protected $signature = 'remboursements:reparer-elwardi {--apply : Applique réellement la correction}';

    protected $description = 'Corrige le double remboursement ELWARDI (03/09/2026) : annule RMB-001, déplace RMB-002 sur la caisse Rafik et le rattache à Kénitra';

    private const MARKER = '[ANNULÉ]';

    public function handle(CaisseLedger $ledger): int
    {
        $apply = (bool) $this->option('apply');

        $doublon = Remboursement::with(['caisse', 'beneficiaire'])->where('reference', 'RMB-001')->first();
        $garde = Remboursement::with(['caisse', 'beneficiaire', 'encaissement'])->where('reference', 'RMB-002')->first();

        if ($doublon === null || $garde === null) {
            $this->error('RMB-001 et/ou RMB-002 introuvable — rien à faire.');

            return self::FAILURE;
        }

        if (str_contains((string) $doublon->note, self::MARKER)) {
            $this->warn('RMB-001 est déjà annulé — la correction a déjà été appliquée.');

            return self::SUCCESS;
        }

        // La caisse qui doit porter le remboursement, et le centre qu'il doit
        // afficher : celui du paiement d'origine (Kénitra), jamais celui de
        // la caisse (Marrakech) — c'est tout l'objet du correctif.
        $caisseRafik = $doublon->caisse;
        $caisseKarouali = $garde->caisse;
        $centreCible = $garde->encaissement?->etablissement_id
            ?? $garde->beneficiaire?->etablissement_id;

        if ($caisseRafik === null || $caisseKarouali === null) {
            $this->error('Caisse manquante sur une des deux lignes.');

            return self::FAILURE;
        }

        if ($centreCible === null) {
            $this->error('Impossible de déterminer le centre cible.');

            return self::FAILURE;
        }

        $montant = (float) $garde->montant;

        $this->line('');
        $this->line('  <comment>Étudiant</comment>        : '.($garde->beneficiaire?->nomComplet() ?? '—'));
        $this->line('  <comment>Conservé</comment>        : '.$garde->reference.' (lié à '.($garde->encaissement?->reference ?? '—').')');
        $this->line('  <comment>Annulé</comment>          : '.$doublon->reference.' (non lié)');
        $this->line('');
        $this->line('  Mouvements de caisse :');
        $this->line(sprintf(
            '    %-22s %14s  →  %14s   (annulation %s)',
            $caisseRafik->nom,
            number_format((float) $caisseRafik->solde, 2, ',', ' '),
            number_format((float) $caisseRafik->solde + $montant, 2, ',', ' '),
            $doublon->reference,
        ));
        $this->line(sprintf(
            '    %-22s %14s  →  %14s   (%s déplacé)',
            $caisseKarouali->nom,
            number_format((float) $caisseKarouali->solde, 2, ',', ' '),
            number_format((float) $caisseKarouali->solde + $montant, 2, ',', ' '),
            $garde->reference,
        ));
        $this->line(sprintf(
            '    %-22s %14s  →  %14s   (%s débité ici)',
            $caisseRafik->nom,
            number_format((float) $caisseRafik->solde + $montant, 2, ',', ' '),
            number_format((float) $caisseRafik->solde, 2, ',', ' '),
            $garde->reference,
        ));
        $this->line('');
        $this->line('  Résultat : UN seul remboursement de '.number_format($montant, 2, ',', ' ').' DH,');
        $this->line('             débité de « '.$caisseRafik->nom.' », visible sur le centre du paiement.');
        $this->line('');

        if (! $apply) {
            $this->warn('  SIMULATION — relancez avec --apply pour appliquer.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ledger, $doublon, $garde, $caisseRafik, $caisseKarouali, $montant, $centreCible): void {
            $props = fn (Remboursement $r): array => [
                'beneficiaire_id' => $r->beneficiaire_id,
                'etablissement_id' => $centreCible,
                'correction' => 'doublon-elwardi-03092026',
            ];

            // 1. Annulation de RMB-001 — la caisse Rafik récupère ses 300 DH.
            $ledger->credit(
                (int) $caisseRafik->id,
                (float) $doublon->montant,
                "Annulation du doublon {$doublon->reference}",
                $doublon,
                $props($doublon),
            );

            $note = trim((string) $doublon->note);
            $suffix = self::MARKER.' le '.now()->format('d/m/Y')
                .' — saisi en double (doublon de '.$garde->reference.'), caisse recréditée de '
                .number_format((float) $doublon->montant, 2, ',', ' ').' DH.';
            $doublon->update(['note' => $note === '' ? $suffix : $note."\n".$suffix]);

            // 2. Déplacement de RMB-002 : Karouali revient à zéro, Rafik paie.
            $ledger->credit(
                (int) $caisseKarouali->id,
                $montant,
                "Transfert du remboursement {$garde->reference} vers « {$caisseRafik->nom} »",
                $garde,
                $props($garde),
            );

            $ledger->debit(
                (int) $caisseRafik->id,
                $montant,
                "Remboursement {$garde->reference} (déplacé depuis « {$caisseKarouali->nom} »)",
                $garde,
                $props($garde),
            );

            // 3. La ligne porte désormais la bonne caisse ET le bon centre.
            $garde->update([
                'caisse_id' => $caisseRafik->id,
                'etablissement_id' => $centreCible,
            ]);

            // RMB-001 garde sa caisse d'origine (son mouvement a été annulé,
            // pas réécrit) mais reçoit le centre correct, sinon il resterait
            // invisible sur Kénitra comme avant.
            $doublon->update(['etablissement_id' => $centreCible]);
        });

        $this->info('  ✔ Correction appliquée.');
        $this->line('');
        $this->line('    '.$caisseRafik->nom.' : '.number_format((float) $caisseRafik->fresh()->solde, 2, ',', ' ').' DH');
        $this->line('    '.$caisseKarouali->nom.' : '.number_format((float) $caisseKarouali->fresh()->solde, 2, ',', ' ').' DH');

        return self::SUCCESS;
    }
}
