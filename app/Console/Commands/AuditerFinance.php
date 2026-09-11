<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Finance\Support\VentilationCentre;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\ImportRow;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Audit finance READ-ONLY — les familles de défauts rencontrées en
 * production entre le 07 et le 11/09/2026, chacune nommée par le cas réel
 * qui l'a fait découvrir.
 *
 * Complément de `caisse:verifier-coherence`, qui garde les invariants
 * STRUCTURELS (compte ↔ méthode, tills, solde ↔ journal). Ici : les
 * défauts de VENTILATION et d'IMPUTATION, ceux qui laissent `caisses.solde`
 * juste mais font mentir un écran — une part de centre négative, un frais
 * qui paraît impayé alors que l'argent attend en avance, un agent qui ne
 * devrait pas encaisser.
 *
 * ⚠ Les critères sont ceux qui ont SURVÉCU aux faux positifs du 11/09 :
 *
 *  - un transfert sans centre n'est signalé que si le repli rend la part du
 *    centre NÉGATIVE. « La caisse sert deux centres » (Hafssa) et « elle a
 *    transféré plus qu'elle n'a encaissé » (Maria, qui centralise Ikram)
 *    sont des signaux, pas des preuves — les deux étaient sains ;
 *  - une avance importée n'est signalée que si le fichier source nomme UN
 *    frais (une cellule multi-frais est laissée à l'importeur) ;
 *  - le trop-perçu est INDICATIF et jamais compté : une avance volontaire y
 *    ressemble trait pour trait.
 *
 * Aucune écriture, jamais. Chaque section nomme la commande de réparation
 * qui lui correspond — dry-run d'abord, comme toujours.
 */
final class AuditerFinance extends Command
{
    protected $signature = 'finance:auditer
        {--strict : Échouer (exit 1) dès qu\'un point à traiter est trouvé}
        {--top=25 : Nombre de lignes de la section trop-perçu}';

    protected $description = 'Audit finance en lecture seule : ventilation par centre, transferts, avances importées, frais écrasés, agents.';

    private VentilationCentre $ventilation;

    /** @var Collection<int, Etablissement> */
    private Collection $centres;

    public function handle(VentilationCentre $ventilation): int
    {
        $this->ventilation = $ventilation;
        $this->centres = Etablissement::query()->orderBy('id')->get(['id', 'nom_centre']);

        $especes = Caisse::withoutGlobalScopes()->whereIn('type', Caisse::TYPES_ESPECES)->get();

        $points = 0;
        $points += $this->section('1. Parts négatives — argent sorti d\'un centre qui ne l\'avait pas', $this->partsNegatives($especes),
            'transferts:imputer-centre <REF> --centre=<X> --dry-run  (prouver par les encaissements)');
        $this->section('2. Somme des parts ≠ solde stocké — informatif', $this->ecartsVentilation($especes),
            'un PETIT écart = mouvements antérieurs au 01/09 non ventilables, normal ; un GROS écart = à investiguer');
        $points += $this->section('3. Transferts sans centre dont le repli rend une part négative', $this->transfertsNullFautifs(),
            'transferts:imputer-centre <REF> --centre=<X> --dry-run');
        $points += $this->section('4. Avances importées dont le fichier source nomme un frais — par centre', $this->avancesImporteesAvecFrais(),
            'paiements:reconcilier --centre=<X> --dossier=<data>  (simulation par défaut, --apply pour écrire)');
        $points += $this->section('5. Frais à 0 DH avec un montant initial > 0 — montant écrasé', $this->fraisEcrases(),
            'vérifier au journal QUI l\'a mis à 0 ; remettre le montant SEULEMENT si une avance attend (cas Décembre), sinon demander au centre');
        $this->section('6. Trop-perçu ≥ 500 DH — INDICATIF, non compté', $this->tropPercus((int) $this->option('top')),
            'légitime si avance volontaire ; suspect si double saisie ou inscription annulée jamais remboursée — vérification humaine');
        $points += $this->section('7. Agent non-encaisseur sur des saisies CRM des 30 derniers jours', $this->agentsNonEncaisseurs(),
            'corriger la CATÉGORIE dans la fiche employé (vraie caissière mal classée) ; encaissements:reattribuer-agent pour l\'historique importé');

        $this->newLine();

        if ($points === 0) {
            $this->info('Aucun point à traiter — le réseau est sain sur les familles corrigibles.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d point(s) à traiter (sections 1, 3, 4, 5, 7).', $points));

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function section(string $titre, array $rows, string $remede): int
    {
        $this->newLine();
        $this->line("<options=bold>{$titre}</> — ".count($rows));

        if ($rows !== []) {
            $this->table(array_keys($rows[0]), $rows);
            $this->line("  <comment>→ {$remede}</comment>");
        }

        return count($rows);
    }

    private function nomCentre(int|string|null $id): string
    {
        return $this->centres->firstWhere('id', (int) $id)?->nom_centre ?? ('etab'.$id);
    }

    private function dh(float|int|string $v): string
    {
        return number_format((float) $v, 2, '.', ' ');
    }

    // ------------------------------------------------------------------ //

    /**
     * @param  Collection<int, Caisse>  $especes
     * @return list<array<string, string>>
     */
    private function partsNegatives(Collection $especes): array
    {
        $rows = [];

        foreach ($especes as $caisse) {
            foreach ($this->centres as $centre) {
                $part = $this->ventilation->soldeDuCentre($caisse, $centre->id);

                if ($part < -0.005) {
                    $rows[] = [
                        'caisse' => (string) $caisse->id,
                        'nom' => mb_substr($caisse->nom, 0, 24),
                        'centre' => $centre->nom_centre,
                        'part' => $this->dh($part),
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Caisse>  $especes
     * @return list<array<string, string>>
     */
    private function ecartsVentilation(Collection $especes): array
    {
        $rows = [];

        foreach ($especes as $caisse) {
            $somme = 0.0;

            foreach ($this->centres as $centre) {
                $somme += $this->ventilation->soldeDuCentre($caisse, $centre->id);
            }

            $ecart = round($somme - (float) $caisse->solde, 2);

            if (abs($ecart) > 0.005) {
                $rows[] = [
                    'caisse' => (string) $caisse->id,
                    'nom' => mb_substr($caisse->nom, 0, 24),
                    'parts' => $this->dh($somme),
                    'solde' => $this->dh($caisse->solde),
                    'écart' => $this->dh($ecart),
                ];
            }
        }

        return $rows;
    }

    /**
     * Le SEUL critère qui prouve une mauvaise imputation : le repli rend la
     * part du centre négative. Vérifié le 11/09/2026 contre deux faux
     * positifs — « sert deux centres » (Hafssa, repli exact) et « a transféré
     * plus qu'encaissé » (Maria, centralisatrice d'Ikram, tout se réconcilie).
     *
     * @return list<array<string, string>>
     */
    private function transfertsNullFautifs(): array
    {
        $rows = [];

        foreach (CaisseTransfer::query()
            ->whereNull('etablissement_id')
            ->where('statut', CaisseTransfer::STATUT_VALIDE)
            ->with('caisseSource:id,nom,etablissement_id')
            ->orderBy('id')
            ->get() as $transfert) {
            $caisse = Caisse::withoutGlobalScopes()->find($transfert->caisse_source_id);

            if ($caisse === null || $caisse->etablissement_id === null) {
                continue;
            }

            $part = $this->ventilation->soldeDuCentre($caisse, (int) $caisse->etablissement_id);

            if ($part < -0.005) {
                $rows[] = [
                    'transfert' => $transfert->reference,
                    'montant' => $this->dh($transfert->montant),
                    'source' => mb_substr($transfert->caisseSource->nom ?? '?', 0, 22),
                    'replié sur' => $this->nomCentre($caisse->etablissement_id),
                    'part' => $this->dh($part),
                ];
            }
        }

        return $rows;
    }

    /**
     * Le chantier de fond (cas SOKAYNA, 09/09/2026) : un paiement importé
     * resté en avance alors que son fichier source nomme un frais laisse ce
     * frais PARAÎTRE impayé — et une caissière le re-saisit de bonne foi.
     *
     * @return list<array<string, string>>
     */
    private function avancesImporteesAvecFrais(): array
    {
        $parCentre = [];

        Encaissement::query()
            ->whereNull('inscription_fee_id')
            ->where('legacy_source', 'ancien-crm')
            ->whereNotNull('legacy_ref')
            ->chunkById(500, function (Collection $lot) use (&$parCentre): void {
                $rows = ImportRow::query()
                    ->whereIn('legacy_ref', $lot->pluck('legacy_ref'))
                    ->where('status', ImportRow::STATUT_INSERE)
                    ->get()
                    ->keyBy('legacy_ref');

                foreach ($lot as $e) {
                    $label = trim((string) ($rows[$e->legacy_ref]->raw['frais_label'] ?? ''));

                    // « - » est la valeur manquante de l'export ; une cellule à
                    // virgule est un multi-frais que l'importeur éclate lui-même.
                    if ($label === '' || $label === '-' || str_contains($label, ',')) {
                        continue;
                    }

                    $parCentre[$e->etablissement_id]['n'] = ($parCentre[$e->etablissement_id]['n'] ?? 0) + 1;
                    $parCentre[$e->etablissement_id]['m'] = ($parCentre[$e->etablissement_id]['m'] ?? 0) + (float) $e->montant;
                }
            });

        ksort($parCentre);

        $rows = [];

        foreach ($parCentre as $eid => $x) {
            $rows[] = [
                'centre' => $this->nomCentre($eid),
                'paiements' => (string) $x['n'],
                'montant' => $this->dh($x['m']),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, string>> */
    private function fraisEcrases(): array
    {
        return InscriptionFee::query()
            ->where('montant', 0)
            ->where('montant_initial', '>', 0)
            ->whereNull('masque_le')
            ->with(['inscription.student:id,prenom,nom,etablissement_id', 'frais:id,nom'])
            ->orderBy('id')
            ->get()
            ->map(function (InscriptionFee $f): array {
                $student = $f->inscription?->student;

                return [
                    'fee' => (string) $f->id,
                    'frais' => mb_substr($f->frais->nom ?? $f->nom, 0, 28),
                    'initial' => $this->dh($f->montant_initial),
                    'étudiant' => mb_substr(($student->prenom ?? '').' '.($student->nom ?? ''), 0, 24),
                    'centre' => $this->nomCentre($student->etablissement_id ?? null),
                ];
            })
            ->all();
    }

    /**
     * INDICATIF. Une avance volontaire y apparaît comme un « trop-perçu » —
     * cette section est un point de départ pour une vérification humaine,
     * jamais une liste de réparations. C'est pourquoi elle n'entre pas dans
     * le total.
     *
     * @return list<array<string, string>>
     */
    private function tropPercus(int $top): array
    {
        $rows = DB::select('
            select s.prenom, s.nom, s.etablissement_id,
                   coalesce(p.recu, 0) as recu, coalesce(d.du, 0) as du
            from students s
            join (
                select student_id, sum(montant) as recu
                from encaissements
                where applied_from_encaissement_id is null
                group by student_id
            ) p on p.student_id = s.id
            left join (
                select i.student_id, sum(f.montant) as du
                from inscription_fees f
                join inscriptions i on i.id = f.inscription_id
                where f.masque_le is null
                group by i.student_id
            ) d on d.student_id = s.id
            where coalesce(p.recu, 0) - coalesce(d.du, 0) >= 500
            order by (coalesce(p.recu, 0) - coalesce(d.du, 0)) desc
            limit ?
        ', [$top]);

        return array_map(fn (object $r): array => [
            'étudiant' => mb_substr($r->prenom.' '.$r->nom, 0, 26),
            'centre' => $this->nomCentre($r->etablissement_id),
            'reçu' => $this->dh($r->recu),
            'dû' => $this->dh($r->du),
            'trop-perçu' => $this->dh((float) $r->recu - (float) $r->du),
        ], $rows);
    }

    /** @return list<array<string, string>> */
    private function agentsNonEncaisseurs(): array
    {
        return Encaissement::query()
            ->whereNull('legacy_source')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereHas('agent', fn ($q) => $q
                ->withoutGlobalScopes()
                ->whereIn('categorie', Employee::CATEGORIES_NON_ENCAISSEUSES))
            ->selectRaw('agent_id, count(*) as n, sum(montant) as total')
            ->groupBy('agent_id')
            ->get()
            ->map(function (object $r): array {
                $e = Employee::withoutGlobalScopes()->find($r->agent_id);

                return [
                    'agent' => (string) $r->agent_id,
                    'nom' => mb_substr(($e->prenom ?? '').' '.($e->nom ?? ''), 0, 24),
                    'catégorie' => $e->categorie ?? '?',
                    'saisies' => (string) $r->n,
                    'montant' => $this->dh($r->total),
                ];
            })
            ->all();
    }
}
