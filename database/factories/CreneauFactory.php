<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Creneau;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Creneau>
 */
final class CreneauFactory extends Factory
{
    protected $model = Creneau::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'jour_semaine' => fake()->numberBetween(1, 7),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ];
    }
}
