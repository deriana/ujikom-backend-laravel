<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Allowance;
use App\Models\User;
use Illuminate\Support\Str;

class PositionAllowanceSeeder extends Seeder
{
    public function run()
    {
        $creatorId = User::first()->id;

        $manager = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Manager',
            'base_salary' => 5000000,
            'created_by_id' => $creatorId,
        ]);

        $staff = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Staff',
            'base_salary' => 3000000,
            'created_by_id' => $creatorId,
        ]);

        $intern = Position::create([
            'uuid' => Str::uuid(),
            'name' => 'Intern',
            'base_salary' => 1500000,
            'created_by_id' => $creatorId,
        ]);

        $transport = Allowance::create([
            'uuid' => Str::uuid(),
            'name' => 'Transport',
            'type' => 'fixed',
            'amount' => 150000,
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
            'name' => 'Jabatan',
            'type' => 'percentage',
            'amount' => 10,
            'created_by_id' => $creatorId,
        ]);

        // =========================
        // 3. Attach Allowances to Positions
        // =========================
        $manager->allowances()->attach([
            $transport->id => ['amount' => 200000],
            $meal->id      => ['amount' => null],
            $jabatan->id   => ['amount' => 15],
        ]);

        $staff->allowances()->attach([
            $transport->id => ['amount' => null],
            $meal->id      => ['amount' => null],
            $jabatan->id   => ['amount' => 5],
        ]);

        $intern->allowances()->attach([
            $transport->id => ['amount' => 50000],
            $meal->id      => ['amount' => null],
        ]);
    }
}
