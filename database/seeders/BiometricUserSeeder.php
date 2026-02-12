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

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Seeder skipped.');
            return;
        }

        $views = ['front', 'side', 'back'];

        foreach ($employees as $employee) {
            foreach ($views as $view) {
                BiometricUser::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'view' => $view, // supaya tidak dobel
                    ],
                    [
                        'descriptor' => $this->fakeDescriptor(),
                    ]
                );
            }
        }
    }

    private function fakeDescriptor(): array
    {
        return collect(range(1, 128))
            ->map(fn () => fake()->randomFloat(6, -1, 1))
            ->toArray();
    }
}
