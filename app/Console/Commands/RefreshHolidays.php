<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RefreshHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:refresh {year?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh holidays from external API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Determine the year to fetch, default to current year
        $year = $this->argument('year') ?? now()->year;

        // Fetch holiday data from external API
        $response = Http::get("https://libur.deno.dev/api", ['year' => $year]);

        if ($response->failed()) {
            $this->error('Failed to fetch data from holiday API');
            return self::FAILURE;
        }

        $holidays = $response->json();

        // Sync API data with local database
        foreach ($holidays as $item) {
            Holiday::updateOrCreate(
                [
                    'start_date' => $item['date'],
                    'end_date'   => null,
                ],
                [
                    'name' => $item['name'],
                    'is_recurring' => false,
                ]
            );
        }

        $this->info("Holidays for {$year} successfully refreshed.");
        return self::SUCCESS;
    }
}
