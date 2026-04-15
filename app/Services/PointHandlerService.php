<?php

namespace App\Services;

use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Enums\PointCategoryEnum;
use Exception;
use Illuminate\Support\Facades\Log;

class PointHandlerService
{
    public function trigger(int $employeeId, PointCategoryEnum $category, int $value, ?string $note = null)
    {
        try {
            // 1. Cari Periode aktif
            $period = PointPeriode::where('is_active', true)->first();
            if (!$period) {
                Log::error("Sistem Poin: Tidak ada periode aktif.");
                return null;
            }

            // 2. Cari Rule yang cocok menggunakan Engine Logic
            $rule = $this->findMatchingRule($category, $value);

            if (!$rule) {
                Log::warning("Sistem Poin: Tidak ada rule yang cocok untuk kategori {$category->value} dengan nilai {$value}.");
                return null;
            }

            // 3. Catat Transaksi
            return PointTransaction::create([
                'employee_id'     => $employeeId,
                'point_rule_id'   => $rule->id,
                'point_period_id' => $period->id,
                'current_points'  => $rule->points, // Mengambil poin dari rule yang cocok
                'note'            => $note,
            ]);

        } catch (Exception $e) {
            Log::error("Sistem Poin Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Logic inti untuk mencari rule berdasarkan operator
     */
    private function findMatchingRule(PointCategoryEnum $category, int $value)
    {
        $rules = PointRule::where('category', $category)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $isMatch = match ($rule->operator) {
                '<'       => $value < $rule->min_value,
                '<='      => $value <= $rule->min_value,
                '>'       => $value > $rule->min_value,
                '>='      => $value >= $rule->min_value,
                '=='      => $value == $rule->min_value,
                'BETWEEN' => ($value >= $rule->min_value && $value <= $rule->max_value),
                default   => false,
            };

            if ($isMatch) return $rule;
        }

        return null;
    }
}
