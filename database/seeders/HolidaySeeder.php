<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        // Ubah tahun sesuai kebutuhan
        $year = now()->year;

        $employeeId = 2;

        $response = Http::get("https://libur.deno.dev/api?year={$year}");

        if ($response->failed()) {
            $this->command->error('Gagal mengambil data holiday API');
            return;
        }

        $holidays = $response->json();

        foreach ($holidays as $item) {
            Holiday::updateOrCreate(
                ['date' => $item['date']],
                [
                    'name' => $item['name'],
                    'is_recurring' => false, // diasumsikan tidak tahunan
                    'created_by_id' => $employeeId,
                    'updated_by_id' => $employeeId,
                ]
            );
        }

        $this->command->info('Holiday import selesai.');
    }
}
