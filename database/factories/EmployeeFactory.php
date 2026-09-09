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
            // Un employé générique de test est un poste ADMINISTRATIF, pas un
            // enseignant : la plupart des tests s'en servent comme agent
            // encaisseur ou opérateur d'import, or « Enseignant » est
            // justement une catégorie qui n'encaisse pas
            // (Employee::CATEGORIES_NON_ENCAISSEUSES, audit 09/09/2026).
            // Un test qui veut vraiment un enseignant le dit :
            // `Employee::factory()->enseignant()`.
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
            'statut' => Employee::STATUT_ACTIF,
        ];
    }

    /** Un enseignant — pour les groupes, créneaux et séances qui en exigent un. */
    public function enseignant(): self
    {
        return $this->state(fn (): array => ['categorie' => Employee::CATEGORIE_ENSEIGNANT]);
    }

    /**
     * Keeps the employee_etablissement pivot in sync with whatever
     * `etablissement_id` a test passed, so factory-made employees behave
     * like real ones (CenterAccessService reads the pivot).
     */
    public function configure(): self
    {
        return $this->afterCreating(function (Employee $employee): void {
            if ($employee->etablissement_id !== null) {
                $employee->etablissements()->syncWithoutDetaching([$employee->etablissement_id]);
            }
        });
    }
}
