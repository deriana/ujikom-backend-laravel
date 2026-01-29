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
        // Contoh data divisions
        $divisions = [
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'teams' => ['Backend', 'Frontend', 'Mobile'],
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'teams' => ['Recruitment', 'Payroll', 'Employee Relations'],
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'teams' => ['Digital', 'Brand', 'Events'],
            ],
        ];

        foreach ($divisions as $divData) {
            $division = Division::create([
                'uuid' => Str::uuid(),
                'name' => $divData['name'],
                'code' => $divData['code'],
                'created_by_id' => 1,
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
