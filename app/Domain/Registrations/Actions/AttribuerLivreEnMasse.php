<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Employee;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionLivre;
use App\Models\StockArticle;
use App\Models\StockType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Remet UN livre à plusieurs inscriptions d'un groupe (onglet « Livres » des
 * Étudiants, 12/10/2026). Chaque remise passe par AssignerLivresInscription —
 * la même action que le modal : une ligne `inscription_livres` + une Sortie
 * de stock −1 tracée. Aucun argent ne bouge.
 *
 * Trois règles, comme tout écran « en masse » (cf. Échéances en masse) :
 *   1. la portée est revérifiée À L'ÉCRITURE — les ids arrivent cochés depuis
 *      le navigateur, donc forgeables : un id hors du groupe refuse le LOT
 *      ENTIER au lieu d'être filtré en silence ;
 *   2. le stock est contrôlé AVANT la première sortie, sous verrou : refuser
 *      au 31e étudiant laisserait croire que les 30 premiers ont échoué ;
 *   3. un dossier qui a DÉJÀ ce livre est ignoré (jamais deux sorties), et le
 *      nombre ignoré est rendu pour que l'écran le dise.
 */
final class AttribuerLivreEnMasse
{
    public function __construct(private readonly AssignerLivresInscription $assigner) {}

    /**
     * @param  list<int>  $inscriptionIds
     * @return array{attribues: int, dejaServis: int, livre: string}
     */
    public function handle(Group $group, int $stockArticleId, array $inscriptionIds, ?Employee $par = null): array
    {
        $inscriptionIds = array_values(array_unique(array_map('intval', $inscriptionIds)));

        return DB::transaction(function () use ($group, $stockArticleId, $inscriptionIds, $par): array {
            /** @var StockArticle|null $article */
            $article = StockArticle::query()->lockForUpdate()->find($stockArticleId);

            $estUnLivreDuCentre = $article !== null
                && (int) $article->etablissement_id === (int) $group->etablissement_id
                && $article->statut === StockArticle::STATUT_ACTIF
                && $article->stockType()->where('nom', StockType::SYSTEM_LIVRE)->exists();

            if (! $estUnLivreDuCentre) {
                throw ValidationException::withMessages([
                    'stock_article_id' => __("This book is not in the stock of the group's centre."),
                ]);
            }

            $inscriptions = Inscription::query()
                ->where('group_id', $group->id)
                ->whereIn('id', $inscriptionIds)
                ->lockForUpdate()
                ->get();

            if ($inscriptions->count() !== count($inscriptionIds)) {
                throw ValidationException::withMessages([
                    'inscription_ids' => __('Some selected registrations do not belong to this group. Nothing was assigned.'),
                ]);
            }

            $deja = InscriptionLivre::query()
                ->whereIn('inscription_id', $inscriptions->pluck('id'))
                ->where('stock_article_id', $article->id)
                ->pluck('inscription_id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $aServir = $inscriptions->reject(fn (Inscription $i): bool => in_array((int) $i->id, $deja, true));

            if ((int) $article->quantite < $aServir->count()) {
                throw ValidationException::withMessages([
                    'inscription_ids' => __('Not enough stock for :book: :needed needed, :stock in stock. Nothing was assigned.', [
                        'book' => $article->nom,
                        'needed' => $aServir->count(),
                        'stock' => (int) $article->quantite,
                    ]),
                ]);
            }

            foreach ($aServir as $inscription) {
                $existants = $inscription->livres()->pluck('stock_article_id')->map(fn ($id): int => (int) $id)->all();
                $this->assigner->handle($inscription, array_values(array_unique([...$existants, (int) $article->id])), $par);
            }

            return ['attribues' => $aServir->count(), 'dejaServis' => count($deja), 'livre' => $article->nom];
        });
    }
}
