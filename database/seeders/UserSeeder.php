<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'uuid' => '1',
            'name' => 'Admin System',
            'email' => 'admin@app.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole(UserRole::ADMIN->value);

        $manager = User::create([
            'uuid' => '2',
            'name' => 'Project Manager',
            'email' => 'manager@app.com',
            'password' => Hash::make('password'),
        ]);
        $manager->assignRole(UserRole::MANAGER->value);

        $employee = User::create([
            'uuid' => '3',
            'name' => 'Nikola Tesla',
            'email' => 'employee@app.com',
            'password' => Hash::make('password'),
        ]);
        $employee->assignRole(UserRole::EMPLOYEE->value);
    }
}
