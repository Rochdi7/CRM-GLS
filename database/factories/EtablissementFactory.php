<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Etablissement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etablissement>
 */
final class EtablissementFactory extends Factory
{
    protected $model = Etablissement::class;

    public function definition(): array
    {
        return [
            'nom_centre' => 'GLS '.fake()->unique()->city(),
            'ville' => fake()->city(),
            'siege_social' => false,
        ];
    }
}
