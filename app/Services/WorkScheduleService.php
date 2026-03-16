<?php

namespace App\Services;

use App\Models\WorkSchedule;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class WorkScheduleService
 *
 * Menangani logika bisnis untuk manajemen jadwal kerja (work schedule),
 * termasuk pengaturan waktu kerja, toleransi keterlambatan, dan mode kerja.
 */
class WorkScheduleService
{
    /**
     * Mengambil semua jadwal kerja beserta mode kerja dan penugasan terkait.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve schedules with eager loaded relationships
        return WorkSchedule::with('workMode', 'employeeWorkSchedules')->latest()->get();
    }

    /**
     * Menyimpan data jadwal kerja baru ke dalam database.
     *
     * @param array $data Data jadwal kerja.
     * @return WorkSchedule
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {

            // 1. Create the work schedule record in the database
            return WorkSchedule::create([
                'name' => $data['name'],
                'work_mode_id' => $data['work_mode_id'],
                'work_start_time' => $data['work_start_time'],
                'work_end_time' => $data['work_end_time'],
                'break_start_time' => $data['break_start_time'],
                'break_end_time' => $data['break_end_time'],
                'late_tolerance_minutes' => $data['late_tolerance_minutes'],
                'requires_office_location' => $data['requires_office_location'],
            ]);
        });
    }

    /**
     * Memperbarui data jadwal kerja yang sudah ada.
     *
     * @param WorkSchedule $schedule Objek jadwal kerja yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return WorkSchedule
     * @throws Exception Jika jadwal sudah dihapus.
     */
    public function update(WorkSchedule $schedule, array $data)
    {
        // 1. Prevent updating soft-deleted records
        if ($schedule->trashed()) {
            throw new Exception('Cannot update a deleted work schedule');
        }

        return DB::transaction(function () use ($schedule, $data) {
            // 2. Update the schedule attributes with provided data or keep existing values
            $schedule->update([
                'name' => $data['name'] ?? $schedule->name,
                'work_mode_id' => $data['work_mode_id'] ?? $schedule->work_mode_id,
                'work_start_time' => $data['work_start_time'] ?? $schedule->work_start_time,
                'work_end_time' => $data['work_end_time'] ?? $schedule->work_end_time,
                'break_start_time' => $data['break_start_time'] ?? $schedule->break_start_time,
                'break_end_time' => $data['break_end_time'] ?? $schedule->break_end_time,
                'late_tolerance_minutes' => $data['late_tolerance_minutes'] ?? $schedule->late_tolerance_minutes,
                'requires_office_location' => $data['requires_office_location'] ?? $schedule->requires_office_location,
            ]);

            return $schedule->load('workMode');
        });
    }

    /**
     * Menghapus jadwal kerja secara lunak (soft delete).
     *
     * @param WorkSchedule $schedule Objek jadwal kerja yang akan dihapus.
     * @return bool
     * @throws Exception Jika jadwal sudah dihapus atau masih digunakan oleh karyawan.
     */
    public function delete(WorkSchedule $schedule): bool
    {
        // 1. Validate current state
        if ($schedule->trashed()) {
            throw new Exception('Work schedule already deleted');
        }

        return DB::transaction(function () use ($schedule) {
            // 2. Prevent deletion if the schedule is currently assigned to employees
            if ($schedule->employeeWorkSchedules()->exists()) {
                throw new Exception('Cannot delete schedule that is assigned to employees');
            }

            $schedule->delete();

            return true;
        });
    }

    /**
     * Memulihkan data jadwal kerja yang telah dihapus lunak.
     *
     * @param string $uuid UUID jadwal kerja.
     * @return WorkSchedule
     * @throws Exception Jika jadwal tidak dalam status terhapus.
     */
    public function restore(string $uuid): WorkSchedule
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the trashed record
            $schedule = WorkSchedule::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            // 2. Ensure it is actually deleted before restoring
            if (! $schedule->trashed()) {
                throw new Exception('Work schedule is not deleted');
            }

            // 3. Perform the restoration
            $schedule->restore();

            return $schedule;
        });
    }

    /**
     * Menghapus data jadwal kerja secara permanen dari database.
     *
     * @param string $uuid UUID jadwal kerja.
     * @return bool
     * @throws Exception Jika jadwal memiliki riwayat penugasan.
     */
    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the record including trashed ones
            $schedule = WorkSchedule::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            if ($schedule->employeeWorkSchedules()->exists()) {
                throw new Exception('Cannot force delete schedule that has assignment history');
            }

            // 3. Perform permanent deletion
            $schedule->forceDelete();

            return true;
        });
    }

    /**
     * Mengambil semua daftar jadwal kerja yang telah dihapus lunak.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrashed()
    {
        // 1. Retrieve only trashed schedules with work mode info
        return WorkSchedule::onlyTrashed()
            ->with('workMode')
            ->latest()
            ->get();
    }
}
