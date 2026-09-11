<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\AnneesScolaires\Concerns;

use App\Models\AnneeScolaire;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ⚠ Une année scolaire est une FENÊTRE DE LECTURE, pas une étiquette.
 *
 * Les enregistrements datés sans FK d'année — dépenses, remboursements,
 * chèques, lignes du journal de caisse — sont rattachés à l'année dont
 * l'intervalle [date_debut, date_fin] CONTIENT leur date (§11 « Context
 * scoping », `CurrentContext::anneeDateRange()`). Rétrécir ou déplacer une
 * année ne déplace donc pas ces lignes : elle décide si un écran les voit
 * encore.
 *
 * Deux défauts sont refusés ici, parce qu'aucun des deux ne se voit à
 * l'écran au moment où on le crée :
 *
 * 1. **Un TROU entre deux années** — une date qui n'appartient à aucune
 *    fenêtre. La ligne devient invisible dans TOUS les sélecteurs d'année :
 *    elle existe en base, la caisse a bougé, mais aucun écran ne la liste.
 *    C'est le cas signalé le 11/09/2026 : clôturer 2025/2026 au 26-08-2026
 *    alors que 2026/2027 ouvre le 01-09-2026 laissait 6 jours orphelins,
 *    et la dépense DEP-040 du 31-08-2026 (140,00 DH « En attente ») ne
 *    pouvait plus être ni approuvée ni refusée depuis aucun contexte.
 *
 * 2. **Un CHEVAUCHEMENT** — une date appartenant à deux fenêtres. La même
 *    dépense est alors comptée dans le total de DEUX années : deux écrans
 *    du même argent qui se contredisent (§11).
 *
 * Le refus NOMME ce qui est en jeu (l'année voisine, les lignes orphelines
 * et leur montant) plutôt que d'afficher « dates invalides » : sans cela
 * l'utilisateur ne peut pas savoir quelle date choisir. §11 « signaler
 * plutôt que masquer ».
 */
trait CouvertureAnneesRules
{
    /**
     * Tables datées sans `annee_scolaire_id`, rattachées à une année par la
     * seule fenêtre de dates. Ce sont exactement celles que
     * `anneeDateRange()` filtre (GetDepensesList, GetRemboursementsList,
     * GetChequesList, GetCaisseJournal, GetDashboardStats).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private array $tablesDatees = [
        'depenses' => ['date_depense', 'dépense'],
        'remboursements' => ['date_remboursement', 'remboursement'],
        'cheques' => ['date_echeance', 'chèque'],
    ];

    protected function validerCouvertureAnnees(Validator $validator, ?AnneeScolaire $annee = null): void
    {
        if ($validator->errors()->hasAny(['date_debut', 'date_fin'])) {
            return;
        }

        $debut = Carbon::parse($this->input('date_debut'))->startOfDay();
        $fin = Carbon::parse($this->input('date_fin'))->startOfDay();

        $autres = AnneeScolaire::query()
            ->when($annee !== null, fn ($q) => $q->whereKeyNot($annee->getKey()))
            ->orderBy('date_debut')
            ->get();

        foreach ($autres as $voisine) {
            $vDebut = $voisine->date_debut->startOfDay();
            $vFin = $voisine->date_fin->startOfDay();

            // Chevauchement : une date comptée deux fois.
            if ($debut <= $vFin && $fin >= $vDebut) {
                $validator->errors()->add('date_fin', __(
                    'These dates overlap :name (:start to :end). A date inside two academic years is counted twice in both years\' totals.',
                    [
                        'name' => $voisine->nom,
                        'start' => $vDebut->format('d/m/Y'),
                        'end' => $vFin->format('d/m/Y'),
                    ],
                ));

                return;
            }
        }

        $this->refuserTrou($validator, $autres, $debut, $fin);
    }

    /**
     * Un trou n'est refusé que s'il ORPHELINE réellement des lignes : une
     * année isolée dans le temps (la toute première, ou une année future
     * volontairement détachée) reste légitime tant qu'aucun argent ne tombe
     * dans l'intervalle découvert.
     *
     * @param  \Illuminate\Support\Collection<int, AnneeScolaire>  $autres
     */
    private function refuserTrou(Validator $validator, $autres, Carbon $debut, Carbon $fin): void
    {
        foreach ($autres as $voisine) {
            $vDebut = $voisine->date_debut->startOfDay();
            $vFin = $voisine->date_fin->startOfDay();

            // Trou APRÈS la fenêtre soumise (la voisine commence plus tard).
            if ($vDebut > $fin) {
                $trouDebut = $fin->copy()->addDay();
                $trouFin = $vDebut->copy()->subDay();
            }
            // Trou AVANT la fenêtre soumise (la voisine se termine plus tôt).
            elseif ($vFin < $debut) {
                $trouDebut = $vFin->copy()->addDay();
                $trouFin = $debut->copy()->subDay();
            } else {
                continue;
            }

            if ($trouDebut > $trouFin) {
                continue; // Années contiguës : aucun jour découvert.
            }

            $orphelines = $this->lignesOrphelines($trouDebut, $trouFin);

            if ($orphelines === []) {
                continue;
            }

            $validator->errors()->add('date_fin', __(
                'This leaves :from to :to covered by no academic year, and :details would become invisible on every screen. Extend this year or :name to cover those dates.',
                [
                    'from' => $trouDebut->format('d/m/Y'),
                    'to' => $trouFin->format('d/m/Y'),
                    'details' => implode(', ', $orphelines),
                    'name' => $voisine->nom,
                ],
            ));

            return;
        }
    }

    /**
     * @return array<int, string>
     */
    private function lignesOrphelines(Carbon $debut, Carbon $fin): array
    {
        $details = [];

        foreach ($this->tablesDatees as $table => [$colonne, $libelle]) {
            $ligne = DB::table($table)
                ->whereBetween($colonne, [$debut->toDateString(), $fin->toDateString()])
                ->selectRaw('count(*) as total, coalesce(sum(montant), 0) as montant')
                ->first();

            if ($ligne === null || (int) $ligne->total === 0) {
                continue;
            }

            $details[] = trans_choice(
                '{1} :count :label (:amount MAD)|[2,*] :count fois :label (:amount MAD)',
                (int) $ligne->total,
                [
                    'count' => (int) $ligne->total,
                    'label' => $libelle,
                    'amount' => number_format((float) $ligne->montant, 2, ',', ' '),
                ],
            );
        }

        return $details;
    }
}
