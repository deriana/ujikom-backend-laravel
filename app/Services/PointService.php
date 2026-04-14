<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Enums\UserRole;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class PointTransactionService
 * * Menangani logika transaksi poin karyawan, baik otomatis (system)
 * maupun manual (manager/admin).
 */
class PointService
{
    /**
     * Menampilkan riwayat transaksi poin dengan filter role.
     */
    public function index()
    {
        $user = Auth::user();
        $query = PointTransaction::with(['employee.user', 'rule', 'period']);

        if ($user->hasRole(UserRole::MANAGER->value)) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('manager_id', $user->employee?->id);
            });
        } elseif ($user->hasRole(UserRole::EMPLOYEE->value)) {
            $query->where('employee_id', $user->employee?->id);
        }

        return $query->latest()->get();
    }

    /**
     * Menyimpan transaksi poin baru (Manual Input dari Manager/Admin).
     * * @param array $data (employee_nik, rule_uuid, current_points)
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Cari Employee berdasarkan NIK
            $employeeId = Employee::where('nik', $data['employee_nik'])->value('id');
            if (!$employeeId) {
                throw new Exception('Employee with the provided NIK not found.');
            }

            // 2. Cari Rule berdasarkan UUID
            $rule = PointRule::where('uuid', $data['rule_uuid'])->first();
            if (!$rule) {
                throw new Exception('Point rule not found.');
            }

            // 3. Cari Periode Aktif
            $period = PointPeriode::where('is_active', true)->first();
            if (!$period) {
                throw new Exception('There is no active point period.');
            }

            // 4. Create Transaction
            $transaction = PointTransaction::create([
                'employee_id' => $employeeId,
                'point_rule_id' => $rule->id,
                'point_period_id' => $period->id,
                'current_points' => $data['current_points'] ?? $rule->points, // Bisa custom atau ikut default rule
            ]);

            // 5. Notifikasi (Opsional)
            $type = $transaction->current_points >= 0 ? 'Reward' : 'Penalty';
            $transaction->notifyCustom(
                title: "Point $type Received",
                message: "You have received {$transaction->current_points} points for: {$rule->event_name}"
            );

            return $transaction;
        });
    }

    /**
     * Memperbarui transaksi poin (Hanya disarankan untuk koreksi administratif).
     */
    public function update(PointTransaction $pointTransaction, array $data)
    {
        return DB::transaction(function () use ($pointTransaction, $data) {
            // 1. Cari Rule baru jika uuid-nya dikirim
            if (isset($data['rule_uuid'])) {
                $ruleId = PointRule::where('uuid', $data['rule_uuid'])->value('id');
                $pointTransaction->point_rule_id = $ruleId;
            }

            // 2. Update field lainnya
            $pointTransaction->current_points = $data['current_points'] ?? $pointTransaction->current_points;

            // Simpan alasan kenapa diedit agar ada jejak audit
            $pointTransaction->note = $data['note'] ?? $pointTransaction->note;

            $pointTransaction->save();

            return $pointTransaction->refresh();
        });
    }

    /**
     * Mengambil data Leaderboard berdasarkan periode aktif.
     */
    public function getLeaderboard(int $limit = 10)
    {
        $period = PointPeriode::active()->first();

        if (!$period) {
            return collect([]);
        }

        return Employee::query()
            ->select(
                'employees.id',
                'employees.user_id',
                'users.name as employee_name',
                DB::raw('SUM(point_transactions.current_points) as total_points')
            )
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->join('point_transactions', 'employees.id', '=', 'point_transactions.employee_id')
            ->where('point_transactions.point_period_id', $period->id)
            ->groupBy('employees.id', 'employees.user_id', 'users.name')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get()
            ->map(function ($emp) {
                // Tambahkan URL foto menggunakan Spatie Media Library dari model aslinya
                $employeeModel = Employee::find($emp->id);
                $emp->photo_url = $employeeModel->getFirstMediaUrl('profile_photo');
                return $emp;
            });
    }

    /**
     * Mengambil histori poin per karyawan untuk periode tertentu (Opsional tapi berguna)
     */
    public function getEmployeeHistory(int $employee_nik)
    {
        $employeeId = Employee::where('nik', $employee_nik)->value('id');
        if (!$employeeId) {
            throw new \Exception('Employee with the provided NIK not found.');
        }

        return PointTransaction::with(['rule', 'period'])
            ->where('employee_id', $employeeId)
            ->latest()
            ->get();
    }

    /**
     * Menghapus transaksi poin (jika ada kesalahan input).
     */
    public function delete(PointTransaction $pointTransaction): bool
    {
        return DB::transaction(function () use ($pointTransaction) {
            return $pointTransaction->delete();
        });
    }
}
