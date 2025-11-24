<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Mosa Baregzay',
            "title" => "Developer",
            'email' => 'developer@developer.com',
            "password" => Hash::make("developer123"),
            "status" => "active",
            "department_id" => Department::find(1)->id,
        ]);

        $user = User::where('email', 'developer@developer.com')->first();

        if (! $user) {
            $this->command->warn('⚠️  User with email admin@example.com not found!');
            return;
        }

        $permissions = Permission::pluck('name')->toArray();

        if (empty($permissions)) {
            $this->command->warn('⚠️  No permissions found. Did you run the permission seeder?');
            return;
        }

        $user->syncPermissions($permissions);

        $this->command->info('✅ All permissions assigned successfully to user: ' . $user->email);
    }
}
