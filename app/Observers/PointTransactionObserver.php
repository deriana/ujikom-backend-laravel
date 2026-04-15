<?php

namespace App\Observers;

use App\Models\PointTransaction;
use App\Models\PointWallet;

class PointTransactionObserver
{
    /**
     * Menangani event "created" pada PointTransaction.
     */
    public function created(PointTransaction $pointTransaction): void
    {
        // Cari wallet yang cocok dengan Employee dan Periode dari transaksi tersebut
        // Jika belum ada wallet untuk periode tersebut, otomatis buat baru (firstOrCreate)
        $wallet = PointWallet::firstOrCreate(
            [
                'employee_id'     => $pointTransaction->employee_id,
                'point_period_id' => $pointTransaction->point_period_id,
            ],
            [
                'current_balance' => 0, // Nilai awal jika baru dibuat
            ]
        );

        // Update saldo di wallet tersebut
        $wallet->increment('current_balance', $pointTransaction->current_points);
    }

    /**
     * Jika transaksi poin dihapus, saldo di wallet harus dikurangi.
     */
    public function deleted(PointTransaction $pointTransaction): void
    {
        $wallet = PointWallet::where('employee_id', $pointTransaction->employee_id)
            ->where('point_period_id', $pointTransaction->point_period_id)
            ->first();

        if ($wallet) {
            $wallet->decrement('current_balance', $pointTransaction->current_points);
        }
    }
}
