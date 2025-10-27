<?php

// Note: please do not seed the database twice using this general seeding class.

namespace Database\Seeders;

use App\Models\IndicatorType;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            // DepartmentsTableSeeder::class,
            // UsersTableSeeder::class,
            // RolePermissionSeeder::class,
            // DistrictsTableSeeder::class,
            // ProvincesTableSeeder::class,
            // KitsTableSeeder::class,
            // DatabasesTableSeeder::class,
            // IndicatorsTypeTableSeeder::class,
            // Isp3TableSeeder::class,
            // QuestionsTableSeeder::class,
            // DeveloperUserSeeder::class
        ]);

    }
}
