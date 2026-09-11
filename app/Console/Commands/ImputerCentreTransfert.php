<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CaisseTransfer;
use App\Models\Encaissement;
use App\Models\Etablissement;
use Illuminate\Console\Command;

/**
 * Écrit le CENTRE d'un transfert de caisse resté sans imputation.
 *
 * `caisse_transfers.etablissement_id` n'existe que depuis le 09/09/2026 :
 * 37 transferts validés avant cette date sont NULL, et
 * `VentilationCentre::transfertsDuCentre()` les replie alors sur le centre
 * de RATTACHEMENT de la caisse source. Ce repli tombe juste tant qu'une
 * caissière ne sert qu'un centre — 35 cas sur 37.
 *
 * Il se trompe dès qu'un même tiroir encaisse pour deux centres. Cas réel
 * (11/09/2026) : la caisse d'Ahmed Khadimerrahman, rattachée à GLS Online,
 * a encaissé 500 DH pour Online (04/09) et 1 800 DH pour Kénitra (05/09),
 * puis tout transféré. TRF-028 (1 800 DH) sortait donc comptablement
 * d'Online — qui affichait **-1 800,00 DH** — pendant que Kénitra gardait
 * +1 800 DH d'argent qui n'y était plus. Le solde stocké, lui, était juste :
 * la somme des parts retombait bien sur 0,00 DH.
 *
 * ⚠ Pourquoi une commande CIBLÉE et non un backfill des 37 :
 *
 * §11 interdit de rétro-remplir la dimension centre, et pour une raison
 * solide — deviner l'imputation d'un mouvement déjà journalisé fabrique une
 * donnée que personne n'a saisie, sur des lignes où rien ne cloche. Cette
 * commande ne devine RIEN : elle exige `--centre` et exige que le centre
 * nommé ait réellement alimenté la caisse source (la preuve est faite sur
 * les encaissements espèces du tiroir), sinon elle refuse. C'est une
 * correction PROUVÉE, transfert par transfert, pas une reprise en masse.
 *
 * Elle ne touche QUE `etablissement_id` : ni montant, ni caisses, ni dates,
 * ni statut, ni `caisses.solde` — aucun dirham ne bouge, seule l'imputation
 * analytique change. Le `save()` passe par Eloquent, donc `Auditable`
 * journalise « avant → après ».
 *
 * Usage :
 *   php artisan transferts:imputer-centre TRF-028 --centre=Kénitra --dry-run
 *   php artisan transferts:imputer-centre TRF-028 --centre=Kénitra
 */
final class ImputerCentreTransfert extends Command
{
    protected $signature = 'transferts:imputer-centre
        {reference : Référence du transfert (ex. TRF-028)}
        {--centre= : Centre à imputer (id ou partie du nom)}
        {--dry-run : Afficher sans modifier}';

    protected $description = 'Impute un transfert de caisse à son centre, lorsque la colonne est restée vide (transferts antérieurs au 09/09/2026).';

    public function handle(): int
    {
        $transfert = CaisseTransfer::query()
            ->with(['caisseSource:id,nom,etablissement_id', 'caisseDestination:id,nom'])
            ->where('reference', (string) $this->argument('reference'))
            ->first();

        if ($transfert === null) {
            $this->error('Transfert introuvable.');

            return self::FAILURE;
        }

        $centre = $this->resolveCentre();

        if ($centre === null) {
            return self::FAILURE;
        }

        $this->line('');
        $this->line(sprintf('  %s | %s DH | %s',
            $transfert->reference,
            number_format((float) $transfert->montant, 2, '.', ' '),
            $transfert->date_transfert?->format('d/m/Y') ?? '-'));
        $this->line(sprintf('  %s  ->  %s',
            $transfert->caisseSource->nom ?? '?',
            $transfert->caisseDestination->nom ?? '?'));
        $this->line('');

        if ($transfert->etablissement_id !== null) {
            $actuel = Etablissement::find($transfert->etablissement_id);
            $this->warn(sprintf(
                '  Ce transfert porte déjà un centre : %s. Rien à faire.',
                $actuel->nom_centre ?? $transfert->etablissement_id
            ));

            return self::SUCCESS;
        }

        // La PREUVE : le centre nommé doit avoir alimenté ce tiroir. Sans
        // cela on écrirait une imputation que rien ne soutient — exactement
        // ce que le backfill en masse ferait, et que cette commande refuse.
        $encaisse = Encaissement::query()
            ->where('caisse_id', $transfert->caisse_source_id)
            ->where('etablissement_id', $centre->id)
            ->whereNull('applied_from_encaissement_id')
            ->sum('montant');

        $this->line(sprintf('  Encaissé pour %s dans ce tiroir : %s DH',
            $centre->nom_centre, number_format((float) $encaisse, 2, '.', ' ')));

        if ((float) $encaisse <= 0.0) {
            $this->error(sprintf(
                '  %s n\'a jamais alimenté cette caisse — imputation refusée.',
                $centre->nom_centre
            ));

            return self::FAILURE;
        }

        if ((float) $encaisse < (float) $transfert->montant) {
            $this->warn(sprintf(
                '  ⚠ Le transfert (%s DH) dépasse ce que %s a mis dans ce tiroir (%s DH).',
                number_format((float) $transfert->montant, 2, '.', ' '),
                $centre->nom_centre,
                number_format((float) $encaisse, 2, '.', ' ')
            ));
        }

        $this->line('');
        $this->info(sprintf('  etablissement_id : NULL  ->  %d (%s)', $centre->id, $centre->nom_centre));

        if ($this->option('dry-run')) {
            $this->line('');
            $this->comment('  DRY-RUN — rien écrit. Relancer sans --dry-run pour appliquer.');

            return self::SUCCESS;
        }

        // save() sur le modèle, jamais un update() de masse : Auditable doit
        // enregistrer « avant → après » (§11).
        $transfert->etablissement_id = $centre->id;
        $transfert->save();

        $this->line('');
        $this->info('  Imputé. Aucun montant, aucune caisse, aucun solde n\'a été modifié.');

        return self::SUCCESS;
    }

    private function resolveCentre(): ?Etablissement
    {
        $valeur = trim((string) $this->option('centre'));

        if ($valeur === '') {
            $this->error('--centre est obligatoire.');

            return null;
        }

        $centre = is_numeric($valeur)
            ? Etablissement::find((int) $valeur)
            : Etablissement::where('nom_centre', 'ilike', '%'.$valeur.'%')->first();

        if ($centre === null) {
            $this->error(sprintf('Centre « %s » introuvable.', $valeur));
        }

        return $centre;
    }
}
