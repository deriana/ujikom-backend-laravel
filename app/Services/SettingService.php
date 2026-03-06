<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingService
{
    /**
     * Get a specific setting by its key or create a default one if it doesn't exist.
     *
     * @param string $key
     * @return Setting
     */
    public function get(string $key): Setting
    {
        // 1. Retrieve the setting record by key or initialize a new one with empty values
        return Setting::firstOrCreate(['key' => $key], ['values' => []]);
    }

    /**
     * Get multiple settings by an array of keys.
     *
     * @param array $keys
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMany(array $keys)
    {
        // 1. Fetch settings matching the provided keys and index them by key for easier access
        return Setting::whereIn('key', $keys)->get()->keyBy('key');
    }

    /**
     * Update or create a setting record with the provided values.
     *
     * @param string $key
     * @param array $values
     */
    public function update(string $key, array $values): Setting
    {
        return DB::transaction(function () use ($key, $values) {
            // 1. Perform an update or create operation within a database transaction
            return Setting::updateOrCreate(
                ['key' => $key],
                ['values' => $values]
            );
        });
    }
}
