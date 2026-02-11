<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RefreshHolidays extends Command
{
    protected $signature = 'holidays:refresh {year?}';
    protected $description = 'Refresh holidays from external API';

    public function handle(): int
    {
        $year = $this->argument('year') ?? now()->year;

        $response = Http::get("https://libur.deno.dev/api?year={$year}");

        if ($response->failed()) {
            $this->error('Gagal mengambil data holiday API');
            return self::FAILURE;
        }

        $holidays = $response->json();

        foreach ($holidays as $item) {
            Holiday::updateOrCreate(
                ['date' => $item['date']],
                ['name' => $item['name'], 'is_recurring' => false]
            );
        }

        $this->info("Holidays {$year} berhasil di-refresh.");
        return self::SUCCESS;
    }
}
