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
        // 1. Buat Super Admin
        $superAdmin = User::create([
            'uuid' => '0',
            'name' => 'Super Admin',
            'email' => 'superadmin@app.com',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole(UserRole::SUPER_ADMIN->value);

        // 2. Buat Admin
        $admin = User::create([
            'uuid' => '1',
            'name' => 'Admin System',
            'email' => 'admin@app.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole(UserRole::ADMIN->value);

        // 3. Buat HR
        $hr = User::create([
            'uuid' => '2',
            'name' => 'HR Manager',
            'email' => 'hr@app.com',
            'password' => Hash::make('password'),
        ]);
        $hr->assignRole(UserRole::HR->value);

        // 4. Buat Manager
        $manager = User::create([
            'uuid' => '3',
            'name' => 'Project Manager',
            'email' => 'manager@app.com',
            'password' => Hash::make('password'),
        ]);
        $manager->assignRole(UserRole::MANAGER->value);

        // 5. Buat Employee (Reguler)
        $employee = User::create([
            'uuid' => '4',
            'name' => 'Nikola Tesla',
            'email' => 'employee@app.com',
            'password' => Hash::make('password'),
        ]);
        $employee->assignRole(UserRole::EMPLOYEE->value);

        // 6. Buat Intern
        $intern = User::create([
            'uuid' => '5',
            'name' => 'Anak Magang',
            'email' => 'intern@app.com',
            'password' => Hash::make('password'),
        ]);
        $intern->assignRole(UserRole::INTERN->value);
    }
}
