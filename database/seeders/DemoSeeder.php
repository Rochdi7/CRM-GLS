<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ⚠ DEMO / FAKE DATA — local development only. NEVER run in production.
 *
 * Split out of DatabaseSeeder so `php artisan db:seed` (and any deploy script
 * that calls it) can only ever produce essential reference data. Demo data is
 * now explicitly opt-in:
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * Everything below invents students, groups, inscriptions, tills and money
 * movements so every CRUD screen has something to click through. All rows it
 * creates are recognizable by their reference prefixes (ETU-DEMO*, ETU-DASH*,
 * ETU-RETARD*, EMP-ROLE*) — that is what makes them removable later.
 *
 * BookStockSeeder is included here rather than in DatabaseSeeder: the titles
 * are real GLS books, but it opens every article at an invented 40 units, so
 * it is stock fiction on a production database. Seed the titles by hand (or
 * import real quantities) instead.
 *
 * Refuses to run outside local/testing unless explicitly forced.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment('local', 'testing')
            && ! (bool) env('ALLOW_DEMO_SEED', false)) {
            $this->command?->error(
                'DemoSeeder refuses to run outside local/testing. '
                .'Set ALLOW_DEMO_SEED=true only if you really mean it.'
            );

            return;
        }

        $this->call([
            // One login per role (EMP-ROLE01…05 + *@gls.test users).
            DemoRoleUsersSeeder::class,
            // Teachers, students, groups, inscriptions + fee lines.
            DemoDataSeeder::class,
            // Tills, encaissements, dépenses, transfers.
            DemoFinanceSeeder::class,
            // Book articles opened at a fictional 40 units each.
            BookStockSeeder::class,
            DemoStockSeeder::class,
            DemoRecouvrementSeeder::class,
            DemoDashboardSeeder::class,
        ]);

        $this->command?->warn('Demo data seeded — do NOT deploy this database.');
    }
}
