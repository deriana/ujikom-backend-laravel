<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class EmployeeShiftService
 *
 * Menangani logika bisnis untuk penugasan shift kerja karyawan,
 * termasuk validasi hari kerja dan sinkronisasi dengan template shift.
 */
class EmployeeShiftService
{
    protected WorkdayService $workdayService; /**< Layanan untuk validasi hari kerja dan hari libur */

    /**
     * Membuat instance layanan shift karyawan baru.
     *
     * @param WorkdayService $workdayService
     */
    public function __construct(WorkdayService $workdayService)
    {
        $this->workdayService = $workdayService;
    }

    /**
     * Mengambil semua data shift karyawan beserta relasi karyawan dan template shift-nya.
     *
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data shift karyawan.
     */
    public function index()
    {
        // 1. Retrieve all shifts with eager loaded relationships
        return EmployeeShift::with(['employee', 'shiftTemplate'])
            ->latest()
            ->get();
    }

    /**
     * Menugaskan shift baru kepada karyawan.
     *
     * @param array $data Data penugasan (employee_nik, shift_template_uuid, shift_date).
     * @return EmployeeShift Objek shift yang berhasil dibuat atau diperbarui.
     * @throws \DomainException Jika tanggal yang dipilih adalah hari libur atau akhir pekan.
     */
    public function store(array $data): EmployeeShift
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate if the date is a valid workday (not a holiday or weekend)
            if (! $this->workdayService->isWorkday(Carbon::parse($data['shift_date']))) {
                throw new \DomainException("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            // 2. Retrieve employee and shift template (Select only needed columns)
            $employee = Employee::select('id', 'nik', 'user_id')
                ->with('user:id,name')
                ->where('nik', $data['employee_nik'])
                ->firstOrFail();

            $template = ShiftTemplate::select('id', 'uuid', 'name')
                ->where('uuid', $data['shift_template_uuid'])
                ->firstOrFail();

            // 3. Use updateOrCreate to prevent duplicates and reduce queries
            $shift = EmployeeShift::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'shift_date' => $data['shift_date'],
                ],
                ['shift_template_id' => $template->id]
            );

            // 4. Set custom notification data
            $shift->customNotification = [
                'title' => 'Shift Assigned',
                'message' => "A new shift has been assigned to {$employee->user->name} on {$data['shift_date']} with template {$template->name}.",
            ];
            $shift->save();

            return $shift->load(['employee', 'shiftTemplate']);
        });
    }

    /**
     * Memperbarui penugasan shift karyawan yang sudah ada.
     *
     * @param EmployeeShift $shift Objek shift yang akan diperbarui.
     * @param array $data Data pembaruan (shift_date, shift_template_uuid).
     * @return EmployeeShift Objek shift setelah diperbarui.
     * @throws \DomainException Jika tanggal baru adalah hari libur atau akhir pekan.
     */
    public function update(EmployeeShift $shift, array $data): EmployeeShift
    {
        return DB::transaction(function () use ($shift, $data) {
            // 1. Validate the new shift date
            if (! $this->workdayService->isWorkday(Carbon::parse($data['shift_date']))) {
                throw new \DomainException("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            // 2. Find the requested shift template
            $template = ShiftTemplate::select('id', 'name')->where('uuid', $data['shift_template_uuid'])->firstOrFail();

            // 3. Set custom notification data
            $shift->customNotification = [
                'title' => 'Shift Updated',
                'message' => "Shift schedule for {$shift->employee->user->name} has been updated to {$data['shift_date']} with template {$template->name}.",
            ];

            // 4. Update the shift record
            $shift->update([
                'shift_template_id' => $template->id,
                'shift_date' => $data['shift_date'],
            ]);

            return $shift->load(['employee', 'shiftTemplate']);
        });
    }

    /**
     * Menghapus penugasan shift karyawan.
     *
     * @param EmployeeShift $shift Objek shift yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     */
    public function delete(EmployeeShift $shift): bool
    {
        return DB::transaction(function () use ($shift) {
            // 1. Set custom notification data before deletion
            $shift->customNotification = [
                'title' => 'Shift Deleted',
                'message' => "Shift schedule for {$shift->employee->user->name} on {$shift->shift_date->format('Y-m-d')} has been removed.",
            ];

            // 2. Delete the shift record
            return $shift->delete();
        });
    }

    /**
     * Menampilkan detail lengkap dari satu penugasan shift tertentu.
     *
     * @param EmployeeShift $shift Objek shift.
     * @return EmployeeShift Objek shift dengan relasi yang dimuat.
     */
    public function show(EmployeeShift $shift): EmployeeShift
    {
        // 1. Load related employee and shift template
        return $shift->load(['employee', 'shiftTemplate']);
    }
}
