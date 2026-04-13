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
            PermissionSeeder::class,
            UserSeeder::class,
            HolidaySeeder::class,
            SettingSeeder::class,
            DivisionSeeder::class,
            PositionAllowanceSeeder::class,
            AllowanceSeeder::class,
            EmployeeSeeder::class,
            AttendanceSeeder::class, // Developing
            // AttendanceYearSeeder::class,
            // BiometricSeeder::class,
            BiometricUserSeeder::class,
            WorkModeSeeder::class,
            WorkScheduleSeeder::class,
            EmployeeWorkScheduleSeeder::class,
            ShiftTemplateSeeder::class,
            EmployeeShiftSeeder::class,
            LeaveTypeSeeder::class,
            EmployeeLeaveBalanceSeeder::class,
            LeaveSeeder::class,
            EmployeeLeaveSeeder::class,
            EarlyLeaveSeeder::class,
            AttendanceRequestSeeder::class,
            OvertimeSeeder::class,
            PayrollSeeder::class,
            AssessmentCategorySeeder::class,
            AssessmentSeeder::class,
            AttendanceCorrectionSeeder::class,
            PointSystemSeeder::class,
        ]);
    }
}
