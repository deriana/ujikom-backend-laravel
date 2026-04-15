<?php

namespace App\Services;

use App\Models\PointItem;
use App\Models\PointItemTransaction;
use App\Models\EmployeeInventories;
use App\Models\PointWallet;
use App\Models\PointPeriode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class PointItemService
 *
 * Menangani logika bisnis untuk manajemen item poin (Point Item),
 * termasuk operasi CRUD, manajemen stok, dan penanganan media (gambar).
 */
class PointItemService
{
    /**
     * Mengambil semua daftar item poin.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return PointItem::latest()->get();
    }

    /**
     * Menyimpan item poin baru beserta gambarnya.
     *
     * @param array $data Data item (name, required_points, stock, dll).
     * @return PointItem
     */
    public function store(array $data): PointItem
    {
        return DB::transaction(function () use ($data) {
            $pointItem = PointItem::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::random(5),
                'description' => $data['description'] ?? null,
                'required_points' => $data['required_points'],
                'stock' => $data['stock'] ?? 0,
                'power_up_type' => $data['power_up_type'] ?? null,
                'category' => $data['category'] ?? 'general',
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (isset($data['image'])) {
                $pointItem->addMedia($data['image'])
                    ->toMediaCollection('point_item_images');
            }

            return $pointItem;
        });
    }

    /**
     * Menampilkan detail item poin.
     *
     * @param PointItem $pointItem
     * @return PointItem
     */
    public function show(PointItem $pointItem): PointItem
    {
        return $pointItem;
    }

    /**
     * Memperbarui data item poin.
     *
     * @param PointItem $pointItem Objek item yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return PointItem
     */
    public function update(PointItem $pointItem, array $data): PointItem
    {
        return DB::transaction(function () use ($pointItem, $data) {
            if ($pointItem->system_reserve) {
                throw new \Exception('Cannot update a system reserved item.');
            }

            $pointItem->update([
                'name' => $data['name'] ?? $pointItem->name,
                'description' => $data['description'] ?? $pointItem->description,
                'required_points' => $data['required_points'] ?? $pointItem->required_points,
                'stock' => $data['stock'] ?? $pointItem->stock,
                'category' => $data['category'] ?? $pointItem->category,
                'is_active' => $data['is_active'] ?? $pointItem->is_active,
                // power_up_type is excluded from update for safety
            ]);

            if (isset($data['image'])) {
                $pointItem->clearMediaCollection('point_item_images');
                $pointItem->addMedia($data['image'])
                    ->toMediaCollection('point_item_images');
            }

            return $pointItem;
        });
    }

    /**
     * Menyesuaikan stok item secara manual.
     *
     * @param PointItem $pointItem
     * @param int $adjustment Jumlah penyesuaian (bisa negatif)
     * @return PointItem
     */
    public function adjustStock(PointItem $pointItem, int $adjustment): PointItem
    {
        return DB::transaction(function () use ($pointItem, $adjustment) {
            $pointItem->increment('stock', $adjustment);

            if ($pointItem->stock < 0) {
                $pointItem->update(['stock' => 0]);
            }

            return $pointItem;
        });
    }

    /**
     * Menghapus item poin.
     *
     * @param PointItem $pointItem
     * @return bool
     * @throws \Exception
     */
    public function delete(PointItem $pointItem): bool
    {
        return DB::transaction(function () use ($pointItem) {
            if ($pointItem->system_reserve) {
                throw new \Exception('Cannot delete a system reserved item.');
            }

            if ($pointItem->employeeInventories()->exists()) {
                throw new \Exception('Cannot delete item because it is already owned by employees in their inventory.');
            }

            return (bool) $pointItem->delete();
        });
    }

    /**
     * Mengubah status aktif/non-aktif item poin secara cepat.
     *
     * @param PointItem $pointItem
     * @return PointItem
     */
    public function toggleStatus(PointItem $pointItem): PointItem
    {
        return DB::transaction(function () use ($pointItem) {
            $pointItem->update([
                'is_active' => ! $pointItem->is_active
            ]);
            return $pointItem;
        });
    }

    /**
     * Menangani proses penukaran poin (redeem) oleh karyawan.
     *
     * @param \App\Models\Employee $employee
     * @param PointItem $pointItem
     * @param int $quantity
     * @return PointItemTransaction
     * @throws \Exception
     */
    public function redeem(array $data): PointItemTransaction
    {
        return DB::transaction(function () use ($data) {
            $employee = $data['employee'];
            $pointItem = $data['point_item'];
            $quantity = $data['quantity'] ?? 1;

            // 1. Validasi Status Item dan Stok
            if (!$pointItem->is_active) {
                throw new \Exception('This item is currently not available for redemption.');
            }

            if ($pointItem->stock < $quantity) {
                throw new \Exception('Insufficient stock for this item.');
            }

            // 2. Cari Periode Poin Aktif
            $period = PointPeriode::where('is_active', true)->first();
            if (!$period) {
                throw new \Exception('No active point period found.');
            }

            // 3. Cek Saldo Poin Karyawan
            $wallet = PointWallet::where('employee_id', $employee->id)
                ->where('point_period_id', $period->id)
                ->first();

            $totalRequired = $pointItem->required_points * $quantity;

            if (!$wallet || $wallet->current_balance < $totalRequired) {
                throw new \Exception('Insufficient point balance.');
            }

            // 4. Kurangi Saldo Poin dan Stok Item
            $wallet->decrement('current_balance', $totalRequired);
            $pointItem->decrement('stock', $quantity);

            // 5. Buat Record Transaksi
            $transaction = PointItemTransaction::create([
                'employee_id' => $employee->id,
                'point_item_id' => $pointItem->id,
                'point_period_id' => $period->id,
                'quantity' => $quantity,
                'total_points' => $totalRequired,
                'status' => 1, // Misal 1 = Success/Completed
            ]);

            // 6. Tambahkan ke Inventaris Karyawan (Loop berdasarkan quantity)
            for ($i = 0; $i < $quantity; $i++) {
                EmployeeInventories::create([
                    'employee_id' => $employee->id,
                    'point_item_id' => $pointItem->id,
                    'point_item_transaction_id' => $transaction->id,
                    'is_used' => false,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Mengambil daftar inventaris item milik karyawan tertentu.
     *
     * @param \App\Models\Employee $employee
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInventories($employee)
    {
        return EmployeeInventories::with(['pointItem', 'transaction'])
            ->where('employee_id', $employee->id)
            ->latest()
            ->get();
    }

    /**
     * Menggunakan item dari inventaris karyawan.
     *
     * @param EmployeeInventories $inventory
     * @return EmployeeInventories
     * @throws \Exception
     */
    public function useItem(EmployeeInventories $inventory): EmployeeInventories
    {
        return DB::transaction(function () use ($inventory) {
            if ($inventory->is_used) {
                throw new \Exception('This item has already been used.');
            }

            if ($inventory->expired_at && $inventory->expired_at->isPast()) {
                throw new \Exception('This item has expired.');
            }

            $inventory->update([
                'is_used' => true,
                'used_at' => now(),
            ]);

            return $inventory;
        });
    }
}
