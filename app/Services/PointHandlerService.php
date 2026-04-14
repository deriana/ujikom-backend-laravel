<?php

namespace App\Services;

use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use Exception;
use Illuminate\Support\Facades\Log;

class PointHandlerService
{
    /**
     * Trigger pemberian poin berdasarkan nama event.
     * Dapat dipanggil dari Controller, Command, atau Observer.
     * * @param int $employeeId
     * @param string $eventName (Contoh: 'Hadir Tepat Waktu', 'Terlambat')
     * @param string|null $note Catatan tambahan
     */
    public function trigger(int $employeeId, string $eventName, ?string $note = null)
    {
        try {
            // 1. Cari Rule yang aktif berdasarkan nama event
            $rule = PointRule::where('event_name', $eventName)
                ->where('is_active', true)
                ->first();

            if (!$rule) {
                Log::warning("Point Rule '{$eventName}' not found or inactive.");
                return null;
            }

            // 2. Cari Periode yang sedang aktif
            $period = PointPeriode::where('is_active', true)->first();
            if (!$period) {
                Log::error("Failed to add points: No active period found.");
                return null;
            }

            // 3. Catat Transaksi
            $transaction = PointTransaction::create([
                'employee_id' => $employeeId,
                'point_rule_id' => $rule->id,
                'point_period_id' => $period->id,
                'current_points' => $rule->points,
                'note' => $note,
            ]);

            return $transaction;

        } catch (Exception $e) {
            Log::error("Point System Error: " . $e->getMessage());
            return null;
        }
    }
}
