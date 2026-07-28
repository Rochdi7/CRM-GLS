<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Explicit, auditable way to grant the super-admin role:
 *
 *   php artisan auth:assign-super-admin admin@gls.test
 *
 * Never creates users, never touches passwords, never hardcodes credentials.
 */
final class AssignSuperAdmin extends Command
{
    protected $signature = 'auth:assign-super-admin {email : Email of the existing user}';

    protected $description = 'Assign the super-admin role to an existing user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        if ($user->hasRole(Role::SUPER_ADMIN)) {
            $this->info("[{$email}] already has the super-admin role.");

            return self::SUCCESS;
        }

        if ($this->laravel->isProduction()
            && ! $this->confirm("Assign SUPER-ADMIN to [{$email}] in PRODUCTION?")) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $user->assignRole(Role::SUPER_ADMIN);

        activity('authorization')
            ->performedOn($user)
            ->withProperties(['role' => Role::SUPER_ADMIN, 'via' => 'console'])
            ->log('super-admin assigned');

        $this->info("super-admin assigned to [{$email}].");

        return self::SUCCESS;
    }
}
