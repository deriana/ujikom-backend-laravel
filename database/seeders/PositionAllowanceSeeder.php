<?php

namespace Database\Seeders;

use App\Models\Allowance;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionAllowanceSeeder extends Seeder
{
    public function run()
    {
        $creatorId = User::first()->id;

        // =========================
        // 1. Create All Allowances
        // =========================
        $transport = Allowance::create([
            'uuid' => Str::uuid(),
            'name' => 'Transport',
            'type' => 'fixed',
            'amount' => 150000, // Default value
            'created_by_id' => $creatorId,
        ]);

        $meal = Allowance::create([
            'uuid' => Str::uuid(),
            'name' => 'Meal',
            'type' => 'fixed',
            'amount' => 100000,
            'created_by_id' => $creatorId,
        ]);

        $jabatan = Allowance::create([
            'uuid' => Str::uuid(),
            'name' => 'Tunjangan Jabatan',
            'type' => 'percentage',
            'amount' => 10,
            'created_by_id' => $creatorId,
        ]);

        $health = Allowance::create([
            'uuid' => Str::uuid(),
            'name' => 'Kesehatan',
            'type' => 'fixed',
            'amount' => 500000,
            'created_by_id' => $creatorId,
        ]);

        $comm = Allowance::create([
            'uuid' => Str::uuid(),
            'name' => 'Komunikasi',
            'type' => 'fixed',
            'amount' => 200000,
            'created_by_id' => $creatorId,
        ]);

        // =========================
        // 2. Create Positions & Attach Allowances
        // =========================

        // --- OWNER ---
        $owner = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Owner',
            'base_salary' => 0, // Per discussion, owner takes profit
            'created_by_id' => $creatorId,
            'system_reserve' => true
        ]);
        // Owner doesn't need technical allowances, or leave empty

        // --- DIRECTOR ---
        $director = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Director',
            'base_salary' => 50000000,
            'created_by_id' => $creatorId,
        ]);
        $director->allowances()->attach([
            $transport->id => ['amount' => 2000000], // Special luxury car fuel allowance
            $jabatan->id   => ['amount' => 25],      // 25% of Base Salary
            $health->id    => ['amount' => 2000000],
            $comm->id      => ['amount' => 1000000],
        ]);

        // --- MANAGER ---
        $manager = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Manager',
            'base_salary' => 15000000,
            'created_by_id' => $creatorId,
        ]);
        $manager->allowances()->attach([
            $transport->id => ['amount' => 1000000],
            $meal->id      => ['amount' => null], // Use default
            $jabatan->id   => ['amount' => 15],
            $comm->id      => ['amount' => 500000],
        ]);

        // --- HR & FINANCE (Senior Staff Level) ---
        $seniorStaff = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Senior Staff',
            'base_salary' => 10000000,
            'created_by_id' => $creatorId,
        ]);
        $seniorStaff->allowances()->attach([
            $transport->id => ['amount' => null],
            $meal->id      => ['amount' => null],
            $jabatan->id   => ['amount' => 5],
            $comm->id      => ['amount' => 250000],
        ]);

        // --- STAFF (Standard) ---
        $staff = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Staff',
            'base_salary' => 7000000,
            'created_by_id' => $creatorId,
        ]);
        $staff->allowances()->attach([
            $transport->id => ['amount' => null],
            $meal->id      => ['amount' => null],
        ]);

        // --- INTERN ---
        $intern = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Intern',
            'base_salary' => 2500000,
            'created_by_id' => $creatorId,
        ]);
        $intern->allowances()->attach([
            $meal->id => ['amount' => null],
        ]);
    }
}
