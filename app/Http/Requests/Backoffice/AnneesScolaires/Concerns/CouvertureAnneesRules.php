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
        // Repartir d'une ardoise vide : une requête précédente ayant échoué
        // APRÈS avoir calculé un glissement (autre règle en erreur, 403,
        // année clôturée) laisserait son décalage en attente, et la
        // prochaine écriture le jouerait sur des dates qui n'ont plus rien
        // à voir. Le conteneur survit à la requête sous Octane et en test.
        AnneeScolaire::prendreGlissements();

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
     * ⚠ **FRONTIÈRE GLISSANTE** (21/09/2026). Déplacer la frontière entre
     * deux années ADJACENTES était une impasse : les deux ordres possibles
     * sont refusés. Clôturer 2025/2026 au 26-08-2026 alors que 2026/2027
     * ouvre le 01-09-2026 crée un trou (refusé, ci-dessous) ; avancer
     * d'abord 2026/2027 au 27-08-2026 chevauche 2025/2026 qui court encore
     * jusqu'au 31-08 (refusé plus haut). Aucune séquence de deux
     * enregistrements ne mène à l'état voulu, et il n'existe pas d'écran
     * pour éditer les deux bornes ensemble : le garde-fou interdisait
     * exactement le geste légitime qu'il est censé protéger (signalé le
     * 21/09/2026, 66 dépenses / 5 remboursements / 14 chèques dans le trou).
     *
     * Le trou est donc COMBLÉ plutôt que refusé, quand il peut l'être sans
     * rien casser : la voisine qui BORDE le trou voit sa borne proche
     * glisser jusqu'à toucher la fenêtre soumise. C'est sûr par
     * construction — la voisine ne fait que GRANDIR dans un intervalle que
     * personne ne couvre, donc aucun chevauchement ne peut naître (une
     * troisième année dans le trou impliquerait qu'elle chevauche déjà la
     * voisine, ce que la règle interdit). Deux bornes :
     *
     *  - le glissement est **appliqué dans la MÊME transaction** que
     *    l'année soumise (`AnneeScolaire::glissementsEnAttente()`, joué par
     *    `AnneeScolaireController::persist()`). Écrit à part, il laisserait
     *    la base en trou si le second écrit échouait — exactement l'état
     *    que la règle refuse ;
     *  - il est **annoncé à l'utilisateur** (flash nommant l'année et sa
     *    nouvelle borne). Une année qu'on n'a pas éditée change de dates :
     *    le taire ferait découvrir le décalage des mois plus tard, sur un
     *    total qui ne tombe plus juste (§11 « signaler plutôt que masquer »).
     *
     * Reste refusé le trou qu'aucune voisine ne borde — il n'y a alors rien
     * à faire glisser, et l'argent serait bel et bien perdu de vue.
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
                $colonneAGlisser = 'date_debut';
                $nouvelleBorne = $trouDebut;
            }
            // Trou AVANT la fenêtre soumise (la voisine se termine plus tôt).
            elseif ($vFin < $debut) {
                $trouDebut = $vFin->copy()->addDay();
                $trouFin = $debut->copy()->subDay();
                $colonneAGlisser = 'date_fin';
                $nouvelleBorne = $trouFin;
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

            // La voisine borde le trou : elle l'absorbe. Rien n'est écrit
            // ici — un FormRequest valide, il n'écrit pas ; le glissement
            // est mis en attente pour la transaction du contrôleur.
            //
            // ⚠ Pas de `return` : rétrécir une année du MILIEU ouvre un trou
            // de CHAQUE côté, et n'en combler qu'un laisserait l'autre
            // orphelin sans que rien ne le signale — la boucle doit voir
            // toutes les voisines.
            AnneeScolaire::enregistrerGlissement($voisine, $colonneAGlisser, $nouvelleBorne);
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
