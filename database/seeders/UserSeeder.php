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
        // 1. ADMIN SYSTEM
        $admin = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Admin System',
            'email' => 'admin@app.com',
            'password' => Hash::make('password'),
            'system_reserve' => true,
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(UserRole::ADMIN->value);

        // 2. OWNER (Pemilik Perusahaan)
        $owner = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Bapak Owner',
            'email' => 'owner@app.com',
            'password' => Hash::make('password'),
            'system_reserve' => true,
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole(UserRole::OWNER->value);

        // 3. DIRECTOR (Pucuk Pimpinan Operasional)
        $director = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Ibu Direktur',
            'email' => 'director@app.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $director->assignRole(UserRole::DIRECTOR->value);

        // 4. HR (Pengelola SDM)
        $hr = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Project HR',
            'email' => 'hr@app.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $hr->assignRole(UserRole::HR->value);

        // 5. FINANCE (Pengelola Gaji/Payroll)
        $finance = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Bagian Keuangan',
            'email' => 'finance@app.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $finance->assignRole(UserRole::FINANCE->value);

        // 6. MANAGER (Kepala Divisi)
        $manager = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Project Manager',
            'email' => 'manager@app.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole(UserRole::MANAGER->value);

        // 6b. MANAGER 2
        $manager2 = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Operations Manager',
            'email' => 'manager2@app.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $manager2->assignRole(UserRole::MANAGER->value);

        // 7. EMPLOYEE (Staff Biasa)
        $employee = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Nikola Tesla',
            'email' => 'employee@app.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        $employee->assignRole(UserRole::EMPLOYEE->value);

        $totalTesting = 100; // Ubah angka ini sesuai kebutuhan testingmu

        for ($i = 1; $i <= $totalTesting; $i++) {
            $user = User::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
            ]);

            $user->assignRole(UserRole::EMPLOYEE->value);
        }
    }
}
