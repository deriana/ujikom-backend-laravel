<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Cuti Tahunan',
                'is_active' => true,
                'default_days' => 12,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cuti Sakit',
                'is_active' => true,
                'default_days' => null,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cuti Hamil',
                'is_active' => true,
                'default_days' => 90,
                'gender' => 'female',
                'requires_family_status' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cuti Menikah',
                'is_active' => true,
                'default_days' => 3,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cuti Karena Alasan Penting',
                'is_active' => true,
                'default_days' => 3,
                'gender' => 'all',
                'requires_family_status' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cuti Tanpa Gaji',
                'is_active' => true,
                'default_days' => null,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::create($type);
        }
    }
}
