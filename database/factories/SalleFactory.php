<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Etablissement;
use App\Models\Salle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Salle>
 */
final class SalleFactory extends Factory
{
    protected $model = Salle::class;

    public function definition(): array
    {
        return [
            'nom' => 'Salle '.fake()->unique()->numerify('##'),
            'etablissement_id' => Etablissement::factory(),
            'capacite' => fake()->numberBetween(10, 30),
            'statut' => Salle::STATUT_ACTIVE,
        ];
    }
}
