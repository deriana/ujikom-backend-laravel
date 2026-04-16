<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\PointWallet;
use DomainException;
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
     *
     * * @param array $data (employee_nik, rule_uuid, current_points)
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Cari Employee berdasarkan NIK
            $employeeId = Employee::where('nik', $data['employee_nik'])->value('id');
            if (! $employeeId) {
                throw new \DomainException('Employee with the provided NIK not found.');
            }

            // 2. Cari Rule berdasarkan UUID
            $rule = PointRule::where('uuid', $data['rule_uuid'])->first();
            if (! $rule) {
                throw new \DomainException('Point rule not found.');
            }

            // 3. Cari Periode Aktif
            $period = PointPeriode::where('is_active', true)->first();
            if (! $period) {
                throw new \DomainException('There is no active point period.');
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
        $activePeriod = PointPeriode::active()->first();
        $user = Auth::user();
        $myRank = null;
        $myPoints = 0;

        // Ambil semua wallet untuk periode aktif, hitung total poin yang dikumpulkan (incoming)
        $allWallets = PointWallet::with(['employee.user', 'employee.position'])
            ->where('point_period_id', $activePeriod?->id)
            ->get()
            ->map(function ($wallet) use ($activePeriod) {
                $wallet->total_earned = PointTransaction::where('employee_id', $wallet->employee_id)
                    ->where('point_period_id', $activePeriod?->id)
                    ->where('current_points', '>', 0)
                    ->sum('current_points');
                return $wallet;
            })->sortByDesc('total_earned')->values();

        if ($user && $user->employee) {
            $myWallet = $allWallets->where('employee_id', $user->employee->id)->first();
            if ($myWallet) {
                $myPoints = $myWallet->total_earned;
                $myRank = $allWallets->search(fn($w) => $w->employee_id === $user->employee->id) + 1;
            }
        }

        $highest = $allWallets->take($limit)->map(function ($item, $key) {
            $item->rank = $key + 1;
            $item->current_balance = $item->total_earned; // Override balance dengan total yang dikumpulkan
            return $item;
        });

        $lowest = $allWallets->slice(-$limit)->map(function ($item, $key) use ($allWallets, $limit) {
            $originalIndex = $allWallets->count() - ($limit - $key);
            $item->rank = $originalIndex + 1;
            $item->current_balance = $item->total_earned;
            return $item;
        })->values();

        return [
            'period' => $activePeriod ? $activePeriod->name : 'No Active Period',
            'my_rank' => $myRank,
            'my_points' => $myPoints,
            'highest' => $highest,
            'lowest' => $lowest
        ];
    }

    /**
     * Mengambil detail poin satu karyawan berdasarkan NIK untuk periode aktif.
     */
    public function getLeaderboardDetail(string $nik)
    {
        $period = PointPeriode::active()->first();

        if (! $period) {
            throw new \DomainException('No active point period found.');
        }

        // Ambil Employee beserta Wallet-nya untuk periode aktif
        $employee = Employee::where('nik', $nik)
            ->with(['user', 'position', 'wallets' => function ($q) use ($period) {
                $q->where('point_period_id', $period->id);
            }])
            ->first();

        if (! $employee) {
            throw new \DomainException('Employee not found.');
        }

        // Ambil history transaksi untuk list di UI
        $transactions = PointTransaction::with('rule')
            ->where('employee_id', $employee->id)
            ->where('point_period_id', $period->id)
            ->latest()
            ->get();

        // Ambil saldo dari wallet (fallback ke 0 jika belum ada transaksi)
        $currentWallet = $employee->wallets->first();

        return [
            'employee' => [
                'nik' => $employee->nik,
                'name' => $employee->user->name,
                'position' => $employee->position?->name,
                'photo_url' => $employee->getFirstMediaUrl('profile_photo'),
                'total_points' => $currentWallet ? $currentWallet->current_balance : 0,
            ],
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'event' => $transaction->rule->event_name,
                    'points' => $transaction->current_points,
                    'date' => $transaction->created_at->format('Y-m-d H:i'),
                    'note' => $transaction->note,
                ];
            }),
        ];
    }

    /**
     * Mengambil histori poin per karyawan untuk periode tertentu (Opsional tapi berguna)
     */
    public function getEmployeeHistory(int $employee_nik)
    {
        $employeeId = Employee::where('nik', $employee_nik)->value('id');
        if (! $employeeId) {
            throw new \DomainException('Employee with the provided NIK not found.');
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
