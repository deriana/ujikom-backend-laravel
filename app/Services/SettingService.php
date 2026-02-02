<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingService
{
    public function get(string $key): Setting
    {
        return Setting::firstOrCreate(['key' => $key], ['values' => []]);
    }

    public function getMany(array $keys)
    {
        return Setting::whereIn('key', $keys)->get()->keyBy('key');
    }

    public function update(string $key, array $values): Setting
    {
        return DB::transaction(function () use ($key, $values) {
            return Setting::updateOrCreate(
                ['key' => $key],
                ['values' => $values]
            );
        });
    }
}
