<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
final class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'reference' => 'ETU-'.fake()->unique()->numerify('9#####'),
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'niveau' => 'A1.1',
        ];
    }
}
