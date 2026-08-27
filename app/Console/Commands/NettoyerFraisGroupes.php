<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Groups\Actions\RetirerFraisGroupe;
use App\Models\Encaissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Trims each group's fee catalogue down to what that group actually charges.
 *
 * The legacy import gave EVERY group the whole catalogue (17 fees), so a group
 * running Nov→Apr still carried « Frais de Juillet », « Frais de Août » and the
 * three ÖSD exam fees — 3 053 group_frais rows across 180 groups, and an equally
 * inflated « Détails paiement » matrix. Two rules clean that up:
 *
 *   1. the three « Frais dexam ÖSD » lines are removed from every group
 *      (they are billed case by case, never as part of a group's plan);
 *   2. a MONTHLY fee is removed when its month falls outside the group's
 *      date_debut_formation → date_fin_formation window.
 *
 * ⚠ THE MONEY RULE OVERRIDES BOTH: a fee on which even ONE payment exists in
 * that group is NEVER removed, whatever its month. Removing it would push the
 * collected money back out as an avance and make a settled fee look unpaid —
 * on the real data 282 of 819 out-of-range monthly lines carry payments
 * (27/08/2026). Those are reported, not touched.
 *
 * Inscription fees are never DELETED: RetirerFraisGroupe hides them
 * (`masque_le`) exactly as the trash icon on « Modifier le groupe » does, so
 * the operation is fully reversible — re-adding the fee to the group un-hides
 * every line and restores the amounts (RetirerFraisGroupe::restore).
 * `caisses.solde` never moves.
 *
 * Usage:
 *   php artisan groupes:nettoyer-frais --dry-run
 *   php artisan groupes:nettoyer-frais
 *   php artisan groupes:nettoyer-frais --centre=marrakech --sans-osd
 */
final class NettoyerFraisGroupes extends Command
{
    protected $signature = 'groupes:nettoyer-frais
        {--centre= : Limiter à un centre (id ou partie du nom)}
        {--groupe= : Limiter à un groupe (id ou nom exact)}
        {--sans-osd : Ne pas retirer les frais d\'examen ÖSD}
        {--sans-mois : Ne pas retirer les frais mensuels hors période}
        {--dry-run : Afficher ce qui serait retiré sans rien modifier}';

    protected $description = "Retire des groupes les frais d'examen ÖSD et les frais mensuels hors de leur période de formation (jamais ceux qui portent un paiement).";

    /** Catalogue fee name => calendar month it bills. */
    private const array MOIS = [
        'frais de septembre' => 9,
        "frais d'octobre" => 10,
        'frais de novembre' => 11,
        'frais de décembre' => 12,
        'frais de janvier' => 1,
        'frais de février' => 2,
        'frais de mars' => 3,
        "frais d'avril" => 4,
        'frais de mai' => 5,
        'frais de juin' => 6,
        'frais de juillet' => 7,
        'frais de août' => 8,
    ];

    public function handle(RetirerFraisGroupe $retirer): int
    {
        $osdIds = $this->option('sans-osd')
            ? []
            : Frais::query()->where('nom', 'ilike', '%ÖSD%')->pluck('id', 'nom')->all();

        $groupes = Group::query()
            ->when($this->option('centre'), function ($q, $centre): void {
                $q->whereHas('etablissement', fn ($e) => is_numeric($centre)
                    ? $e->whereKey((int) $centre)
                    : $e->where('nom_centre', 'ilike', '%'.$centre.'%'));
            })
            ->when($this->option('groupe'), fn ($q, $g) => is_numeric($g)
                ? $q->whereKey((int) $g)
                : $q->where('nom', $g))
            ->with('frais')
            ->orderBy('nom')
            ->get();

        $this->info(sprintf(
            '%d groupe(s)%s.',
            $groupes->count(),
            $this->option('dry-run') ? '   [DRY-RUN]' : ''
        ));

        $retires = 0;
        $proteges = 0;
        $sansDates = [];

        foreach ($groupes as $groupe) {
            $aRetirer = [];

            foreach ($groupe->frais as $frais) {
                $raison = $this->raisonDeRetirer($groupe, $frais, $osdIds, $sansDates);

                if ($raison === null) {
                    continue;
                }

                // ⚠ The money rule: one payment is enough to keep the fee.
                if ($this->porteUnPaiement($groupe, $frais)) {
                    $proteges++;

                    continue;
                }

                $aRetirer[] = [$frais, $raison];
            }

            if ($aRetirer === []) {
                continue;
            }

            $this->line(sprintf(
                '  %-30s %s',
                mb_substr($groupe->nom, 0, 30),
                implode(', ', array_map(
                    static fn (array $p): string => $p[0]->nom.' ('.$p[1].')',
                    $aRetirer
                ))
            ));

            if (! $this->option('dry-run')) {
                foreach ($aRetirer as [$frais]) {
                    $retirer->handle($groupe, $frais->id);
                }
            }

            $retires += count($aRetirer);
        }

        $this->line('');
        $this->info(sprintf(
            '%s%d ligne(s) de frais retirée(s), %d protégée(s) par un paiement.',
            $this->option('dry-run') ? '[DRY-RUN] ' : '',
            $retires,
            $proteges
        ));

        if ($sansDates !== []) {
            $this->warn('Sans dates de formation (frais mensuels non touchés) : '
                .implode(', ', array_slice($sansDates, 0, 10))
                .(count($sansDates) > 10 ? ' …' : ''));
        }

        if (! $this->option('dry-run') && $retires > 0) {
            $this->line('');
            $this->comment('Réversible : ré-ajouter le frais dans « Modifier le groupe » ré-affiche les lignes masquées et restaure les montants.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $osdIds
     * @param  list<string>  $sansDates
     */
    private function raisonDeRetirer(Group $groupe, Frais $frais, array $osdIds, array &$sansDates): ?string
    {
        if (in_array($frais->id, $osdIds, true)) {
            return 'examen ÖSD';
        }

        if ($this->option('sans-mois')) {
            return null;
        }

        $mois = self::MOIS[mb_strtolower(trim($frais->nom))] ?? null;

        if ($mois === null) {
            return null; // inscription fees and anything unknown are left alone
        }

        if ($groupe->date_debut_formation === null || $groupe->date_fin_formation === null) {
            if (! in_array($groupe->nom, $sansDates, true)) {
                $sansDates[] = $groupe->nom;
            }

            return null;
        }

        return $this->moisDeLaPeriode($groupe)[$mois] ?? false
            ? null
            : 'hors période';
    }

    /**
     * Every calendar month the group's formation window touches. A window is
     * walked month by month rather than compared numerically: a Nov→Apr group
     * spans the year boundary, so "month >= 11 || month <= 4" is the only
     * correct reading and a plain range test would keep nothing.
     *
     * @return array<int, true>
     */
    private function moisDeLaPeriode(Group $groupe): array
    {
        $mois = [];
        $curseur = $groupe->date_debut_formation->copy()->startOfMonth();
        $fin = $groupe->date_fin_formation->copy()->startOfMonth();

        // A window longer than a year covers everything; the guard also stops
        // a reversed pair (fin < début) from looping forever.
        for ($i = 0; $curseur <= $fin && $i < 24; $i++) {
            $mois[(int) $curseur->month] = true;
            $curseur->addMonth();
        }

        return $mois;
    }

    /** True as soon as ONE payment exists on this fee inside this group. */
    private function porteUnPaiement(Group $groupe, Frais $frais): bool
    {
        return Encaissement::query()
            ->whereIn('inscription_fee_id', InscriptionFee::query()
                ->select('inscription_fees.id')
                ->join('inscriptions', 'inscriptions.id', '=', 'inscription_fees.inscription_id')
                ->where('inscriptions.group_id', $groupe->id)
                ->where(fn ($q) => $q
                    ->where('inscription_fees.frais_id', $frais->id)
                    ->orWhereRaw('lower(trim(inscription_fees.nom)) = ?', [mb_strtolower(trim($frais->nom))]))
                ->pluck('inscription_fees.id'))
            ->exists();
    }
}
