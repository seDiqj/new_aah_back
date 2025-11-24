<?php

// Note: PLEASE DO NOT SEED THE DATABASE TWICE USING THIS GENERAL SEEDER.

// Note: ONCE YOU SEED THE DATABASE USING THIS GENERAL SEEDER PLEASE COMMINT IT OUT FOR PREVENTING REPETATION.

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
            // SectorsTableSeeder::class,
            // DeveloperUserSeeder::class
        ]);

    }
}
