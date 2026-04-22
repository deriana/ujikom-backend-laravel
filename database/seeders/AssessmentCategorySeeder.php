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
            [
                'name' => 'Quality of Work',
                'description' => 'Assessment of the quality of work produced.',
                'is_active' => true,
            ],
            [
                'name' => 'Teamwork',
                'description' => 'Assessment of teamwork skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Communication Skills',
                'description' => 'Assessment of communication skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Leadership Skills',
                'description' => 'Assessment of leadership skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Problem Solving Skills',
                'description' => 'Assessment of problem-solving skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Time Management Skills',
                'description' => 'Assessment of time management skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Adaptability Skills',
                'description' => 'Assessment of adaptability skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Responsibility Skills',
                'description' => 'Assessment of responsibility skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Creativity Skills',
                'description' => 'Assessment of creativity skills.',
                'is_active' => true,
            ],
            [
                'name' => 'Work Ethic Skills',
                'description' => 'Assessment of work ethic skills.',
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
