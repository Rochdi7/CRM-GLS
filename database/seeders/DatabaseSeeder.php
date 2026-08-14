<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            // ReferentialDataSeeder supersedes AnneeScolaireSeeder (years 24/25 +
            // 25/26) and adds the 7 GLS branches with their rooms.
            ReferentialDataSeeder::class,
            TypeDepenseSeeder::class,
            StockTypeSeeder::class,
            BookStockSeeder::class,
            FraisSeeder::class,
            BanqueSeeder::class,
            // Demo data (local dev only — every seeder below is idempotent):
            // one login per role, academic records, then finance movements so
            // every CRUD screen has something to click through.
            DemoRoleUsersSeeder::class,
            DemoDataSeeder::class,
            DemoFinanceSeeder::class,
            DemoStockSeeder::class,
            DemoRecouvrementSeeder::class,
            DemoDashboardSeeder::class,
        ]);
    }
}
