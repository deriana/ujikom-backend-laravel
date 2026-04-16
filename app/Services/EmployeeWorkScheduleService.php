<?php

namespace App\Services;

use App\Enums\PriorityEnum;
use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Class EmployeeWorkScheduleService
 *
 * Menangani logika bisnis untuk penugasan jadwal kerja karyawan,
 * termasuk manajemen prioritas jadwal (permanen vs sementara) dan validasi konflik tanggal.
 */
class EmployeeWorkScheduleService
{
    /**
     * Mengambil semua data penugasan jadwal kerja karyawan beserta relasi terkait.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve all assignments with eager loaded relationships
        return EmployeeWorkSchedule::with(['employee', 'workSchedule'])
            ->latest()
            ->get();
    }

    /**
     * Menugaskan jadwal kerja baru kepada karyawan.
     *
     * @param array $data Data penugasan (employee_nik, work_schedule_uuid, start_date, end_date).
     * @return EmployeeWorkSchedule Objek penugasan yang berhasil dibuat.
     * @throws DomainException Jika terjadi konflik jadwal pada tingkat prioritas yang sama.
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Find employee and work schedule by identifiers
            $employee = Employee::where('nik', $data['employee_nik'])->firstOrFail();
            $workSchedule = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->firstOrFail();

            // 2. Determine priority level based on whether it's a temporary (Level 2) or permanent (Level 1) schedule
            $priority = isset($data['end_date']) ? PriorityEnum::LEVEL_2->value : PriorityEnum::LEVEL_1->value;

            // 3. If Level 1, close the previous permanent schedule to prevent overlap
            if ($priority === PriorityEnum::LEVEL_1->value) {
                EmployeeWorkSchedule::where('employee_id', $employee->id)
                    ->level1()
                    ->whereNull('end_date')
                    ->update([
                        'end_date' => Carbon::parse($data['start_date'])->subDay()->toDateString(),
                    ]);
            }

            // 4. Validate that the new schedule doesn't conflict with existing ones of the same priority
            $this->validateDateConflict(
                $employee->id,
                $data['start_date'],
                $data['end_date'] ?? null,
                $priority
            );

            // 5. Create the assignment record
            $assignment = new EmployeeWorkSchedule([
                'employee_id' => $employee->id,
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'priority' => $priority,
            ]);

            // 6. Set custom notification data
            $assignment->customNotification = [
                'title' => 'Work Schedule Assigned',
                'message' => "Work schedule '{$workSchedule->name}' for {$employee->user->name} (NIK: {$employee->nik}) has been assigned from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            $assignment->save();

            return $assignment->load(['employee', 'workSchedule']);
        });
    }

    /**
     * Memperbarui data penugasan jadwal kerja karyawan yang sudah ada.
     *
     * @param EmployeeWorkSchedule $assignment Objek penugasan yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return EmployeeWorkSchedule Objek penugasan setelah diperbarui.
     * @throws DomainException Jika terjadi konflik jadwal pada tingkat prioritas yang sama.
     */
    public function update(EmployeeWorkSchedule $assignment, array $data)
    {
        return DB::transaction(function () use ($assignment, $data) {
            // 1. Find the requested work schedule
            $workSchedule = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->firstOrFail();

            // 2. Prepare data and determine priority
            $startDate = $data['start_date'];
            $endDate = $data['end_date'] ?? null;
            $priority = $endDate ? PriorityEnum::LEVEL_2->value : PriorityEnum::LEVEL_1->value;

            // 3. Validate date conflicts excluding the current record
            $this->validateDateConflict(
                $assignment->employee_id,
                $startDate,
                $endDate,
                $priority,
                $assignment->id
            );

            // 4. Update the assignment record
            $assignment->update([
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'priority' => $priority,
            ]);

            // 5. Set custom notification data
            $assignment->customNotification = [
                'title' => 'Work Schedule Updated',
                'message' => "Work schedule '{$workSchedule->name}' for {$assignment->employee->user->name} (NIK: {$assignment->employee->nik}) has been updated from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            return $assignment->load(['employee', 'workSchedule']);
        });
    }

    /**
     * Menghapus data penugasan jadwal kerja.
     *
     * @param EmployeeWorkSchedule $assignment Objek penugasan yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     */
    public function delete(EmployeeWorkSchedule $assignment): bool
    {
        return DB::transaction(function () use ($assignment) {

            // 1. Set custom notification data before deletion
            $assignment->customNotification = [
                'title' => 'Work Schedule Removed',
                'message' => "Work schedule '{$assignment->workSchedule->name}' for {$assignment->employee->user->name} (NIK: {$assignment->employee->nik}) has been removed from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            $assignment->delete();

            return true;
        });
    }

    /**
     * Mencegah tumpang tindih jadwal untuk tingkat prioritas yang sama.
     *
     * @param int $employeeId ID karyawan.
     * @param string $startDate Tanggal mulai jadwal baru.
     * @param string|null $endDate Tanggal berakhir jadwal baru.
     * @param int $priority Tingkat prioritas jadwal.
     * @param int|null $ignoreId ID penugasan yang diabaikan (untuk proses update).
     * @throws DomainException Jika ditemukan konflik jadwal.
     */
    private function validateDateConflict(
        int $employeeId,
        string $startDate,
        ?string $endDate,
        int $priority,
        ?int $ignoreId = null
    ): void {
        // 1. Initialize query for the specific employee and priority
        $query = EmployeeWorkSchedule::where('employee_id', $employeeId)
            ->where('priority', $priority);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        // 2. Check for overlapping date ranges
        $query->where(function ($q) use ($startDate, $endDate) {
            // Use a far-future date to represent null end_date for comparison
            $actualEnd = $endDate ?? '9999-12-31';

            $q->where(function ($query) use ($startDate, $actualEnd) {
                $query->where('start_date', '<=', $actualEnd)
                    ->where(function ($sub) use ($startDate) {
                        $sub->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    });
            });
        });

        // 3. Throw domainException if a conflict is found
        if ($query->exists()) {
            throw new \DomainException("Schedule conflict detected for Priority Level $priority");
        }
    }
}
