<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Caisse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Caisse>
 */
final class CaisseFactory extends Factory
{
    protected $model = Caisse::class;

    public function definition(): array
    {
        return [
            'nom' => 'Caisse '.fake()->unique()->numerify('###'),
            'solde' => 0,
            'statut' => Caisse::STATUT_ACTIVE,
        ];
    }
}
