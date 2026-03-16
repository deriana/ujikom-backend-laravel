<?php

namespace Database\Seeders;

use App\Models\AssessmentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssessmentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $creatorId = $admin ? $admin->id : 1;

        $categories = [
            [
                'name' => 'Technical Skills',
                'description' => 'Assessment of technical abilities and job-specific expertise.',
                'is_active' => true,
            ],
            [
                'name' => 'Soft Skills',
                'description' => 'Assessment of communication skills, teamwork, and leadership.',
                'is_active' => true,
            ],
            [
                'name' => 'Discipline & Attitude',
                'description' => 'Assessment of discipline, attendance, and work behavior.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            AssessmentCategory::create([
                'uuid' => (string) Str::uuid(),
                'name' => $category['name'],
                'description' => $category['description'],
                'is_active' => $category['is_active'],
                'created_by_id' => $creatorId,
            ]);
        }
    }
}
