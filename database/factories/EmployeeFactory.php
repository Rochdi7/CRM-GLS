<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 *
 * NOTE: creating an Employee with a NULL user_id triggers EmployeeObserver,
 * which auto-creates a linked User (structure doc §5). Pass user_id
 * explicitly in tests when you need to control the login account.
 */
final class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'reference' => 'EMP-'.fake()->unique()->numerify('9#####'),
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'sexe' => fake()->randomElement(Employee::SEXES),
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
        ];
    }
}
