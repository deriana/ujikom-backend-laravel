<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BiometricUser;
use App\Models\Employee;

class BiometricUserSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {

            // 5 descriptor per employee
            for ($i = 0; $i < 5; $i++) {

                BiometricUser::create([
                    'employee_id' => $employee->id,
                    'descriptor' => $this->generateNormalizedDescriptor(),
                ]);
            }
        }
    }

    private function generateNormalizedDescriptor(int $dimension = 128): array
    {
        $vector = [];
        $sum = 0;

        for ($i = 0; $i < $dimension; $i++) {
            $value = mt_rand() / mt_getrandmax();
            $vector[] = $value;
            $sum += $value * $value;
        }

        $norm = sqrt($sum);

        return array_map(fn($v) => $v / $norm, $vector);
    }
}
