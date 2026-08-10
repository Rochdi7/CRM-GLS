<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
final class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'nom' => 'Groupe '.fake()->unique()->numerify('###'),
            'niveau' => 'A1.1',
            'statut' => Group::STATUT_EN_INSCRIPTION,
        ];
    }
}
