<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HolidaySeeder extends Seeder
{
    // FIX IS_RECURRING TO BE TRUE LATER
    public function run(): void
    {
        $year = now()->year;
        $userId = 1;

        $response = Http::get('https://libur.deno.dev/api', [
            'year' => $year
        ]);

        if ($response->failed()) {
            $this->command->error('Failed to fetch holiday data from API');
            return;
        }

        $holidays = collect($response->json());

        foreach ($holidays as $item) {
            Holiday::updateOrCreate(
                [
                    'start_date' => $item['date'],
                    'end_date'   => null,
                ],
                [
                    // Only used during INSERT
                    'uuid' => (string) Str::uuid(),

                    'name' => $item['name'],
                    'is_recurring' => false,
                    'created_by_id' => $userId,
                    'updated_by_id' => $userId,
                ]
            );
        }
    }
}
