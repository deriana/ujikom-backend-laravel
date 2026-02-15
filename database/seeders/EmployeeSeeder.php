<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use App\Models\Team;
use App\Models\Position; // Tambahkan ini
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil User
        $admin = User::where('email', 'admin@app.com')->first();
        $ownerUser = User::where('email', 'owner@app.com')->first();
        $directorUser = User::where('email', 'director@app.com')->first();
        $financeUser = User::where('email', 'finance@app.com')->first();
        $managerUser = User::where('email', 'manager@app.com')->first();
        $hrUser = User::where('email', 'hr@app.com')->first();
        $employeeUser = User::where('email', 'employee@app.com')->first();

        // 2. Ambil Team ID berdasarkan Nama
        $teamBOD = Team::where('name', 'Board of Directors')->first()->id;
        $teamOwner = Team::where('name', 'Owner Relations')->first()->id;
        $teamPayroll = Team::where('name', 'Payroll & Tax')->first()->id;
        $teamBackend = Team::where('name', 'Backend')->first()->id;
        $teamRecruitment = Team::where('name', 'Recruitment')->first()->id;

        // 3. Ambil Position ID berdasarkan Nama (Agar sinkron dengan PositionAllowanceSeeder)
        $posOwner = Position::where('name', 'Owner')->first()->id;
        $posDirector = Position::where('name', 'Director')->first()->id;
        $posManager = Position::where('name', 'Manager')->first()->id;
        $posSenior = Position::where('name', 'Senior Staff')->first()->id;
        $posStaff = Position::where('name', 'Staff')->first()->id;

        /*
        |--------------------------------------------------------------------------
        | OWNER
        |--------------------------------------------------------------------------
        */
        $ownerEmployee = Employee::create([
            'nik' => 'OWNER001',
            'user_id' => $ownerUser->id,
            'team_id' => $teamOwner,
            'position_id' => $posOwner, // Pakai ID dari pencarian nama
            'manager_id' => null,
            'employee_status' => EmployeeStatus::PERMANENT,
            'base_salary' => 0,
            'phone' => '08110000001',
            'gender' => 'male',
            'date_of_birth' => '1980-01-01',
            'address' => 'Penthouse Jakarta',
            'join_date' => now()->subYears(10),
            'created_by_id' => $admin->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | DIRECTOR
        |--------------------------------------------------------------------------
        */
        $directorEmployee = Employee::create([
            'nik' => 'DIR001',
            'user_id' => $directorUser->id,
            'team_id' => $teamBOD,
            'position_id' => $posDirector,
            'manager_id' => null,
            'employee_status' => EmployeeStatus::PERMANENT,
            'base_salary' => 50000000,
            'phone' => '08110000002',
            'gender' => 'female',
            'date_of_birth' => '1985-03-20',
            'address' => 'Kemang, Jakarta',
            'join_date' => now()->subYears(7),
            'created_by_id' => $admin->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | FINANCE
        |--------------------------------------------------------------------------
        */
        Employee::create([
            'nik' => 'FIN001',
            'user_id' => $financeUser->id,
            'team_id' => $teamPayroll,
            'position_id' => $posSenior, // Finance masuk level Senior Staff
            'manager_id' => $directorEmployee->id,
            'employee_status' => EmployeeStatus::PERMANENT,
            'base_salary' => 12000000,
            'phone' => '08110000003',
            'gender' => 'female',
            'date_of_birth' => '1993-11-12',
            'address' => 'Depok',
            'join_date' => now()->subYears(2),
            'created_by_id' => $admin->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | MANAGER
        |--------------------------------------------------------------------------
        */
        $managerEmployee = Employee::create([
            'nik' => 'EMP20230001',
            'user_id' => $managerUser->id,
            'team_id' => $teamBackend,
            'position_id' => $posManager,
            'manager_id' => $directorEmployee->id,
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
            'team_id' => $teamRecruitment,
            'position_id' => $posSenior, // HR masuk level Senior Staff
            'manager_id' => $directorEmployee->id,
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
        | EMPLOYEE (Nikola Tesla)
        |--------------------------------------------------------------------------
        */
        Employee::create([
            'nik' => 'EMP20230003',
            'user_id' => $employeeUser->id,
            'team_id' => $teamBackend,
            'position_id' => $posStaff, // Nikola level Staff
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
