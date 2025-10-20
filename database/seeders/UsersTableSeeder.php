<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            "title" => "Menager",
            'email' => 'test@example.com',
            "password" => Hash::make("12345678"),
            "status" => "active",
            "department_id" => Department::find(1)->id,
        ]);
    }
}
