<?php

namespace Database\Seeders;

use App\Models\User;
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
            PermissionSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            DivisionSeeder::class,
            PositionAllowanceSeeder::class,
            AllowanceSeeder::class,
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            // BiometricSeeder::class,
            BiometricUserSeeder::class,
            HolidaySeeder::class,
        ]);
    }
}
