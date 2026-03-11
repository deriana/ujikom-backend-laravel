<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\Employee;
use App\Models\AssessmentCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all necessary IDs for relations
        $employeeIds = Employee::pluck('id')->toArray();
        $categories = AssessmentCategory::all();

        // Ensure employee and category data exists before seeding
        if (empty($employeeIds) || $categories->isEmpty()) {
            $this->command->warn("Employee or AssessmentCategory data is empty. Skipping seeder.");
            return;
        }

        // Create 10 Assessment records
        for ($i = 0; $i < 10; $i++) {
            $evaluatorId = Arr::random($employeeIds);
            $evaluateeId = Arr::random($employeeIds);

            // Ensure evaluator and evaluatee are not the same person
            while ($evaluatorId === $evaluateeId) {
                $evaluateeId = Arr::random($employeeIds);
            }

            $assessment = Assessment::create([
                'uuid' => (string) Str::uuid(),
                'evaluator_id'  => $evaluatorId,
                'evaluatee_id'  => $evaluateeId,
                'period'        => '2026-01-01',
                'note'          => 'Routine performance assessment for January 2026.',
            ]);

            // For each Assessment, create details for each category
            foreach ($categories as $category) {
                AssessmentDetail::create([
                    'uuid' => (string) Str::uuid(),
                    'assessment_id' => $assessment->id,
                    'category_id'   => $category->id,
                    'old_category_name' => $category->name,
                    'score'         => rand(1, 5),
                    'bonus_salary'  => rand(100000, 500000),
                ]);
            }
        }

        $this->command->info("Assessment and Details seeder executed successfully!");
    }
}
