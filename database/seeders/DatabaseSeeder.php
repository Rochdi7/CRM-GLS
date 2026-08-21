<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Production seeder — ESSENTIAL reference data only.
 *
 * Everything called here is idempotent and safe to re-run on a live database:
 * roles/permissions, the GLS centers + rooms + academic years, the locked
 * catalogs (expense types, stock types, frais, banks, cancellation reasons)
 * and the real GLS staff. No student, group, inscription or money record is
 * ever created here.
 *
 * ⚠ There is NO demo/fake data seeder any more. The whole Demo* family
 * (DemoSeeder, DemoData, DemoFinance, DemoRoleUsers, DemoStock,
 * DemoDashboard, DemoRecouvrement, DemoLongueDuree) and BookStockSeeder
 * were deleted on purpose: this seeder set is production-only. Never add a
 * seeder that invents business records — real quantities and real students
 * come from the Import screen.
 *
 * AdminUserSeeder still runs: a database with no super-admin is unusable
 * (every permission-protected route answers 403). It is guarded to local
 * environments — see its docblock for the production provisioning note.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            // ReferentialDataSeeder supersedes AnneeScolaireSeeder (years 24/25 +
            // 25/26) and adds the 7 GLS branches with their rooms.
            ReferentialDataSeeder::class,
            // AFTER the two above on purpose: AdminUserSeeder assigns the
            // super-admin role (needs the role) and attaches the admin
            // employee to every center (needs the établissements).
            AdminUserSeeder::class,
            TypeDepenseSeeder::class,
            StockTypeSeeder::class,
            FraisSeeder::class,
            BanqueSeeder::class,
            MotifAnnulationSeeder::class,
            GlsStaffSeeder::class,
        ]);
    }
}
