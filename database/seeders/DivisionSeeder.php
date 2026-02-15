<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Executive Office',
                'code' => 'EXE',
                'teams' => ['Board of Directors', 'Strategic Planning', 'Owner Relations'],
                'system_reserve' => true,
            ],
            [
                'name' => 'Finance & Accounting',
                'code' => 'FIN',
                'teams' => ['Payroll & Tax', 'Treasury', 'Accounting', 'Audit'],
            ],
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'teams' => ['Backend', 'Frontend', 'Mobile', 'QA & Testing', 'DevOps'],
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'teams' => ['Recruitment', 'People Development', 'Employee Relations', 'General Affairs'],
            ],
            [
                'name' => 'Marketing & Sales',
                'code' => 'MKT',
                'teams' => ['Digital Marketing', 'Brand Activation', 'Sales Canvas', 'Customer Service'],
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'teams' => ['Logistics', 'Procurement', 'Security', 'Facility Management'],
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'teams' => ['IT Support', 'Infrastructure', 'Cyber Security'],
            ],
        ];

        foreach ($divisions as $divData) {
            $division = Division::create([
                'uuid' => Str::uuid(),
                'name' => $divData['name'],
                'code' => $divData['code'],
                'system_reserve' => $divData['system_reserve'] ?? false,
                'created_by_id' => 1, // Admin ID
            ]);

            foreach ($divData['teams'] as $teamName) {
                $division->teams()->create([
                    'uuid' => Str::uuid(),
                    'name' => $teamName,
                    'created_by_id' => 1,
                ]);
            }
        }
    }
}
