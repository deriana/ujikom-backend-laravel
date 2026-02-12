<?php

namespace Database\Seeders;

use App\Models\WorkMode;
use Illuminate\Database\Seeder;

class WorkModeSeeder extends Seeder
{
    public function run(): void
    {
        $modes = [
            ['name' => 'WFO'],
            ['name' => 'WFH'],
            ['name' => 'HYBRID'],
        ];

        foreach ($modes as $mode) {
            WorkMode::updateOrCreate(
                ['name' => $mode['name']],
                $mode
            );
        }
    }
}
