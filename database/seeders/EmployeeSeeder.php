<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@app.com')->first();
        $managerUser = User::where('email', 'manager@app.com')->first();
        $hrUser = User::where('email', 'hr@app.com')->first();
        $employeeUser = User::where('email', 'employee@app.com')->first();

        /*
        |--------------------------------------------------------------------------
        | MANAGER
        |--------------------------------------------------------------------------
        */
        $managerEmployee = Employee::create([
            'nik' => 'EMP20230001',
            'user_id' => $managerUser->id,
            'team_id' => 1,
            'position_id' => 1,
            'manager_id' => null,
            'employee_status' => EmployeeStatus::PERMANENT,
            'base_salary' => 15000000,
            'phone' => '081234567890',
            'gender' => 'male',
            'date_of_birth' => '1990-05-10',
            'address' => 'Jakarta',
            'join_date' => now()->subYears(5),
            'created_by_id' => $admin->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | HR
        |--------------------------------------------------------------------------
        */
        Employee::create([
            'nik' => 'EMP20230002',
            'user_id' => $hrUser->id,
            'team_id' => 1,
            'position_id' => 2,
            'manager_id' => $managerEmployee->id,
            'employee_status' => EmployeeStatus::PERMANENT,
            'base_salary' => 10000000,
            'phone' => '081234567891',
            'gender' => 'female',
            'date_of_birth' => '1992-08-15',
            'address' => 'Bandung',
            'join_date' => now()->subYears(3),
            'created_by_id' => $admin->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE
        |--------------------------------------------------------------------------
        */
        Employee::create([
            'nik' => 'EMP20230003',
            'user_id' => $employeeUser->id,
            'team_id' => 1,
            'position_id' => 3,
            'manager_id' => $managerEmployee->id,
            'employee_status' => EmployeeStatus::CONTRACT,
            'contract_start' => now()->subMonths(6),
            'contract_end' => now()->addMonths(6),
            'base_salary' => 7000000,
            'phone' => '081234567892',
            'gender' => 'male',
            'date_of_birth' => '1998-01-20',
            'address' => 'Surabaya',
            'join_date' => now()->subMonths(6),
            'created_by_id' => $admin->id,
        ]);
    }
}
