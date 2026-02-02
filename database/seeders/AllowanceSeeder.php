<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class AllowanceSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::first();

        if (!$creator) {
            $this->command->warn('No users found. Seed users first.');
            return;
        }

        $allowances = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Transport Allowance',
                'amount' => 500000,
                'type' => 'fixed',
                'created_by_id' => $creator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Meal Allowance',
                'amount' => 300000,
                'type' => 'fixed',
                'created_by_id' => $creator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Performance Bonus',
                'amount' => 10, // 10%
                'type' => 'percentage',
                'created_by_id' => $creator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Attendance Bonus',
                'amount' => 5, // 5%
                'type' => 'percentage',
                'created_by_id' => $creator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Internet Allowance',
                'amount' => 400000,
                'type' => 'fixed',
                'created_by_id' => $creator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('allowances')->insert($allowances);
    }
}
