<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use Illuminate\Database\Seeder;

/**
 * Demo dataset for Gestion du stock — articles across the real categories
 * and centers, each with a coherent movement history (Entrée → Sortie →
 * Ajustement chains whose quantite_avant/apres always balance to the
 * article's current quantite). A few articles sit below their seuil_alerte
 * so the low-stock warning has something to show. Idempotent: skips
 * entirely when any stock article already exists.
 */
final class DemoStockSeeder extends Seeder
{
    public function run(): void
    {
        if (StockArticle::query()->exists()) {
            return;
        }

        $centres = Etablissement::query()->orderBy('id')->pluck('id')->all();
        $agentId = Employee::query()->orderBy('id')->value('id');

        // [nom, catégorie, quantité finale, seuil, mouvements [type, qté]]
        $articles = [
            ['Ramettes papier A4', 'Fournitures de bureau', 42, 20, [['Entrée', 50], ['Sortie', 8]]],
            ['Stylos bleus (boîte de 50)', 'Fournitures de bureau', 6, 10, [['Entrée', 12], ['Sortie', 6]]],
            ['Marqueurs tableau blanc', 'Fournitures de bureau', 18, 15, [['Entrée', 24], ['Sortie', 6]]],
            ['Manuel Menschen A1.1', 'Livres et manuels', 35, 15, [['Entrée', 40], ['Sortie', 5]]],
            ['Manuel Menschen A2.1', 'Livres et manuels', 8, 12, [['Entrée', 30], ['Sortie', 22]]],
            ['Manuel Sicher! B1.1', 'Livres et manuels', 22, 10, [['Entrée', 25], ['Sortie', 3]]],
            ['Cahiers d\'exercices B2', 'Matériel pédagogique', 14, 8, [['Entrée', 20], ['Sortie', 6]]],
            ['Cartes de vocabulaire (jeu)', 'Matériel pédagogique', 9, 5, [['Entrée', 10], ['Sortie', 1]]],
            ['Cartouches d\'encre HP 305', 'Consommables', 4, 6, [['Entrée', 10], ['Sortie', 6]]],
            ['Feutres correction (lot)', 'Consommables', 25, 10, [['Entrée', 25]]],
            ['Vidéoprojecteur Epson', 'Équipement', 3, 2, [['Entrée', 4], ['Ajustement', 3]]],
            ['Enceintes de classe', 'Équipement', 5, 2, [['Entrée', 5]]],
            ['Rallonges électriques', 'Autre', 7, 3, [['Entrée', 8], ['Sortie', 1]]],
        ];

        foreach ($articles as $i => [$nom, $categorie, $quantiteFinale, $seuil, $mouvements]) {
            $article = StockArticle::create([
                'reference' => ReferenceGenerator::make('ART', 'stock_articles'),
                'nom' => $nom,
                'categorie' => $categorie,
                'quantite' => 0, // rebuilt below through the movement chain
                'seuil_alerte' => $seuil,
                'etablissement_id' => $centres === [] ? null : $centres[$i % count($centres)],
                'statut' => StockArticle::STATUT_ACTIF,
                'note' => null,
            ]);

            $courante = 0;

            foreach ($mouvements as $j => [$type, $quantite]) {
                $avant = $courante;
                $courante = match ($type) {
                    StockMouvement::TYPE_ENTREE => $avant + $quantite,
                    StockMouvement::TYPE_SORTIE => $avant - $quantite,
                    StockMouvement::TYPE_AJUSTEMENT => $quantite, // carries the NEW total
                    default => $avant,
                };

                StockMouvement::create([
                    'stock_article_id' => $article->id,
                    'type' => $type,
                    'quantite' => $quantite,
                    'quantite_avant' => $avant,
                    'quantite_apres' => $courante,
                    'note' => $type === StockMouvement::TYPE_AJUSTEMENT ? 'Inventaire de rentrée' : null,
                    'created_by' => $agentId,
                    'created_at' => now()->subDays(count($mouvements) - $j)->setTime(10, 0),
                    'updated_at' => now()->subDays(count($mouvements) - $j)->setTime(10, 0),
                ]);
            }

            // The chains above are authored to land exactly on the intended
            // final quantity — assert it rather than silently drifting.
            if ($courante !== $quantiteFinale) {
                throw new \LogicException("DemoStockSeeder: chain for '{$nom}' ends at {$courante}, expected {$quantiteFinale}.");
            }

            $article->update(['quantite' => $courante]);
        }
    }
}
