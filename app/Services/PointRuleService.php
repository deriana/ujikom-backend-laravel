<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class PointRuleService
 *
 * Menangani logika bisnis untuk manajemen aturan poin (point rules),
 * termasuk penentuan poin untuk berbagai aktivitas karyawan.
 */
class PointRuleService
{
    /**
     * Mengambil semua daftar aturan poin.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return PointRule::latest()->get();
    }

    /**
     * Menyimpan aturan poin baru ke dalam database.
     *
     * @param array $data Data aturan (event_name, points, description, is_active).
     * @return PointRule
     */
    public function store(array $data): PointRule
    {
        return DB::transaction(function () use ($data) {
            return PointRule::create([
                'uuid' => (string) Str::uuid(),
                'event_name' => $data['event_name'],
                'points' => $data['points'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Memperbarui data aturan poin yang sudah ada.
     *
     * @param PointRule $pointRule Objek aturan yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return PointRule
     */
    public function update(PointRule $pointRule, array $data): PointRule
    {
        return DB::transaction(function () use ($pointRule, $data) {
            $pointRule->update([
                'event_name' => $data['event_name'] ?? $pointRule->event_name,
                'points' => $data['points'] ?? $pointRule->points,
                'description' => $data['description'] ?? $pointRule->description,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : $pointRule->is_active,
            ]);

            return $pointRule;
        });
    }

    /**
     * Menghapus data aturan poin.
     *
     * @param PointRule $pointRule Objek aturan yang akan dihapus.
     * @return bool
     */
    public function delete(PointRule $pointRule): bool
    {
        return DB::transaction(fn () => (bool) $pointRule->delete());
    }

    /**
     * Mengubah status aktif/non-aktif aturan poin secara cepat.
     *
     * @param PointRule $pointRule
     * @return PointRule
     */
    public function toggleStatus(PointRule $pointRule): PointRule
    {
        return DB::transaction(function () use ($pointRule) {
            $pointRule->update([
                'is_active' => ! $pointRule->is_active
            ]);
            return $pointRule;
        });
    }

    public function addPoint(int $employeeId, string $ruleUuid, ?string $customPoints = null)
    {
        return DB::transaction(function () use ($employeeId, $ruleUuid, $customPoints) {
            // 1. Ambil Rule berdasarkan UUID
            $rule = PointRule::where('uuid', $ruleUuid)->firstOrFail();

            // 2. Ambil Periode yang sedang aktif
            $period = PointPeriode::active()->first();

            if (!$period) {
                throw new \Exception("Tidak ada periode poin yang aktif saat ini.");
            }

            // 3. Simpan Transaksi
            return PointTransaction::create([
                'employee_id' => $employeeId,
                'point_rule_id' => $rule->id,
                'point_period_id' => $period->id,
                'current_points' => $customPoints ?? $rule->points, // Gunakan poin dari rule jika tidak ada custom
            ]);
        });
    }

    /**
     * Mengambil data Leaderboard berdasarkan periode aktif.
     */
   public function getLeaderboard(int $limit = 10)
    {
        // 1. Ambil periode yang aktif
        $period = PointPeriode::active()->first();

        if (!$period) {
            return collect([]);
        }

        // 2. Query Leaderboard
        $leaderboard = Employee::query()
            // Kita ambil ID employee dan nama dari tabel users (via user_id)
            ->select(
                'employees.id as employee_id',
                'employees.user_id',
                'users.name as employee_name',
                DB::raw('SUM(point_transactions.current_points) as total_points')
            )
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->join('point_transactions', 'employees.id', '=', 'point_transactions.employee_id')
            ->where('point_transactions.point_period_id', $period->id)
            ->groupBy('employees.id', 'employees.user_id', 'users.name')
            ->orderByDesc('total_points')
            // ->limit($limit)
            ->get();

        // 3. Tambahkan URL Foto Profil (Spatie Media Library)
        $leaderboard->transform(function ($employee) {
            // Kita ambil objek model employee asli untuk akses media
            $empModel = Employee::find($employee->employee_id);
            $employee->photo_url = $empModel->getFirstMediaUrl('profile_photo') ?: 'https://ui-avatars.com/api/?name=' . urlencode($employee->employee_name);
            return $employee;
        });

        return $leaderboard;
    }

    /**
     * Mengambil histori poin per karyawan untuk periode tertentu (Opsional tapi berguna)
     */
    public function getEmployeeHistory(int $employeeId)
    {
        return PointTransaction::with(['rule', 'period'])
            ->where('employee_id', $employeeId)
            ->latest()
            ->get();
    }
}
