<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function getAdminDashboard(Request $request)
    {
        // Filter Tanggal (Default hari ini)
        $date = $request->query('date', Carbon::today()->toDateString());
        $month = Carbon::parse($date)->month;
        $year = Carbon::parse($date)->year;

        // 1. Statistik Karyawan
        $statsKaryawan = [
            'total' => Employee::count(),
            'aktif' => Employee::active()->count(),
            'resign_bulan_ini' => Employee::whereMonth('resign_date', $month)->count(),
            'baru_bulan_ini' => Employee::whereMonth('join_date', $month)->count(),
        ];

        // 2. Kehadiran Hari Ini
        $attendanceToday = Attendance::with(['employee', 'employee.user'])->whereDate('date', $date)->get();

        $leavesTodayCount = Leave::approved()
            ->where(function ($query) use ($date) {
                $query->whereDate('date_start', '<=', $date)
                    ->whereDate('date_end', '>=', $date);
            })
            ->count();

        $rekapKehadiran = [
            'hadir' => $attendanceToday->where('status', 'present')->count(),
            'cuti' => $leavesTodayCount,
            'terlambat' => $attendanceToday->where('late_minutes', '>', 0)->count(),
            'tanpa_keterangan' => Employee::count() - $attendanceToday->count(), // Estimasi
        ];

        // 3. Ringkasan Cuti Bulan Ini

        $rataRataSisaCuti = EmployeeLeaveBalance::where('year', $year)
            ->selectRaw('AVG(total_days - used_days) as average_remaining')
            ->first()
            ->average_remaining;

        $ringkasanCuti = [
            'disetujui' => Leave::whereMonth('date_start', $month)->approved()->count(),
            'ditolak' => Leave::whereMonth('date_start', $month)->rejected()->count(),
            'pending' => Leave::pending()->count(),
            'sisa_cuti' => round($rataRataSisaCuti),
        ];

        // 4. Need Approval (Action Center)
        $pendingApprovals = [
            'cuti' => Leave::pending()->count(),
            'lembur' => Overtime::pending()->count(),
            'attendance_request' => AttendanceRequest::pending()->count(),
        ];

        // 5. Data untuk Map (GPS)
        $mapLocations = $attendanceToday->map(fn ($q) => [
            'name' => $q->employee->user->name ?? 'Unknown',
            'lat' => $q->latitude_in,
            'lng' => $q->longitude_in,
            'time' => $q->clock_in?->format('H:i'),
        ]);

        // 6. Data Chart Kehadiran (12 Bulan)
        $monthlyAttendance = Attendance::selectRaw('
        MONTH(date) as month,
        COUNT(CASE WHEN status = "present" THEN 1 END) as hadir,
        COUNT(CASE WHEN status = "absent" THEN 1 END) as absent
        ')
            ->whereYear('date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $chartData = [
            'hadir' => [],
            'absent' => [], // Ganti izin_sakit menjadi absent
        ];

        for ($m = 1; $m <= 12; $m++) {
            $chartData['hadir'][] = $monthlyAttendance->get($m)->hadir ?? 0;
            $chartData['absent'][] = $monthlyAttendance->get($m)->absent ?? 0;
        }

        return $this->successResponse([
            'employee_stats' => $statsKaryawan,
            'attendance_today' => $rekapKehadiran,
            'leave_summary' => $ringkasanCuti,
            'pending_tasks' => $pendingApprovals,
            'map_locations' => $mapLocations,
            'monthly_chart' => $chartData,
        ], 'Admin dashboard data fetched successfully');
    }

    public function getEmployeeDashboard(Request $request)
    {
        $user = $request->user();
        $employeeId = $user->employee->id;
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $employee = Employee::with([
            'position',
            'team.division',
            'user',
        ])
            ->where('user_id', $user->id)
            ->first();

        $profile = [
            'name' => $user->name,
            'nik' => $employee->nik,
            'position' => $employee->position?->name ?? '-',
            'team' => $employee->team?->name ?? '-',
            'division' => $employee->team?->division?->name ?? '-',
            'profile_photo' => $employee->getFirstMediaUrl('profile_photo') ?: null,
        ];

        // 1. Sisa Cuti
        $leaveBalance = EmployeeLeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->first();

        // 2. Statistik Kehadiran & Menit Kerja Bulan Ini
        $myAttendance = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->latest()
            ->get();

        $totalWorkMinutes = $myAttendance->sum('work_minutes');
        $workHours = floor($totalWorkMinutes / 60);
        $workMinutes = $totalWorkMinutes % 60;
        $formattedWorkDuration = "{$workHours}j {$workMinutes}m";

        // 3. Statistik Kehadiran Tahunan (Chart)
        $attendanceSetting = Setting::where('key', 'attendance')->first();
        $lateTolerance = $attendanceSetting->values['late_tolerance_minutes'] ?? 0;
        $yearlyAttendance = Attendance::selectRaw('MONTH(date) as month, COUNT(*) as total_hadir')
            ->where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->where('status', 'present')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $yearlyData = Attendance::selectRaw('
            MONTH(date) as month,
            COUNT(*) as total_hadir,
            SUM(CASE WHEN late_minutes > ? THEN 1 ELSE 0 END) as total_terlambat
        ', [$lateTolerance])
            ->where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->where('status', 'present')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $yearlyChart = [];
        $lateChart = []; // Array baru untuk chart keterlambatan

        for ($m = 1; $m <= 12; $m++) {
            if ($yearlyData->has($m)) {
                $yearlyChart[] = (int) $yearlyData->get($m)->total_hadir;
                $lateChart[] = (int) $yearlyData->get($m)->total_terlambat;
            } else {
                $yearlyChart[] = 0;
                $lateChart[] = 0;
            }
        }

        // 4. Log Lembur Terbaru (Approved & Pending)
        $overtimeLogs = Overtime::where('employee_id', $employeeId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($ot) {
                return [
                    'date' => $ot->attendance?->date?->format('d M Y') ?? '-',
                    'duration' => $ot->duration_minutes.' Menit',
                    'reason' => $ot->reason,
                    'status' => $ot->status, // Menggunakan Enum value
                ];
            });

        // 5. Log Cuti Terbaru
        $leaveLogs = Leave::with('leaveType')
            ->where('employee_id', $employeeId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($leave) {
                return [
                    'type' => $leave->leaveType->name,
                    'date_range' => $leave->date_start->format('d M').' - '.$leave->date_end->format('d M Y'),
                    'status' => $leave->approval_status,
                    'reason' => $leave->reason,
                ];
            });

        // 6. Riwayat Gaji (Payroll)
        $salaryLogs = Payroll::where('employee_id', $employeeId)
            ->finalized() // Hanya ambil yang sudah selesai/dibayar
            ->latest('period_start')
            ->take(3)
            ->get()
            ->map(function ($payroll) {
                return [
                    'uuid' => $payroll->uuid,
                    'period' => $payroll->period_start->translatedFormat('F Y'), // Contoh: Januari 2026
                    'payment_date' => $payroll->finalized_at
                        ? 'Dibayar: '.$payroll->finalized_at->translatedFormat('d M Y')
                        : 'Proses Pencairan',
                    'net_salary' => (float) $payroll->net_salary,
                    'status' => $payroll->status,
                ];
            });

        $pendingLeaves = Leave::with('leaveType')
            ->where('employee_id', $employeeId)
            ->pending()
            ->latest()
            ->get()
            ->map(fn ($l) => [
                'id' => 'leave-'.$l->id,
                'title' => $l->leaveType->name,
                'subtitle' => $l->date_start->format('d M Y'),
                'type' => 'leave',
                'status' => 'Pending',
            ]);

        // 2. Ambil Pending Overtime
        $pendingOvertimes = Overtime::where('employee_id', $employeeId)
            ->pending()
            ->latest()
            ->get()
            ->map(fn ($ot) => [
                'id' => 'ot-'.$ot->id,
                'title' => 'Lembur ('.$ot->duration_minutes.'m)',
                'subtitle' => Str::limit($ot->reason, 20),
                'type' => 'overtime',
                'status' => 'Review',
            ]);

        // 3. Ambil Pending Attendance Request (Koreksi Absen / Shift)
        $pendingAttendance = AttendanceRequest::where('employee_id', $employeeId)
            ->pending()
            ->latest()
            ->get()
            ->map(fn ($ar) => [
                'id' => 'ar-'.$ar->uuid,
                'title' => 'Koreksi: '.str_replace('_', ' ', ucwords($ar->request_type, '_')),
                'subtitle' => $ar->start_date->format('d M'),
                'type' => 'attendance',
                'status' => 'Pending',
            ]);

        $allPending = $pendingLeaves
            ->concat($pendingOvertimes)
            ->concat($pendingAttendance)
            ->sortByDesc('id')
            ->take(5)
            ->values();

        return $this->successResponse([
            'profile' => $profile,
            'personal_stats' => [
                'sisa_cuti' => $leaveBalance ? (int) $leaveBalance->remaining_days : 0,
                'total_terlambat' => $myAttendance->where('late_minutes', '>', 0)->count(),
                'total_menit_lembur' => (int) $myAttendance->sum('overtime_minutes'),
                'total_menit_kerja' => (int) $myAttendance->sum('work_minutes'), // Ini yang kamu minta
                'total_durasi_kerja' => $formattedWorkDuration,
                'kehadiran_bulan_ini' => $myAttendance->count(),
            ],
            'recent_attendance' => $myAttendance->take(5),
            'pending_requests' => [
                'leave' => $pendingLeaves->count(),
                'overtime' => $pendingOvertimes->count(),
                'attendance' => $pendingAttendance->count(),
                'total' => $allPending->count(),
            ],
            'yearly_attendance_chart' => [
                'presence' => $yearlyChart,
                'late' => $lateChart,
            ],
            'logs' => [
                'overtime' => $overtimeLogs,
                'leave' => $leaveLogs,
                'salary' => $salaryLogs,
            ],
        ], 'Employee dashboard data fetched successfully');
    }
}
