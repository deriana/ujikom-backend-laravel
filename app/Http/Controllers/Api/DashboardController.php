<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\EarlyLeave;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Setting;
use App\Services\Attendance\Validators\TimeValidator;
use App\Services\WorkdayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    protected TimeValidator $timeValidator;

    protected WorkdayService $workdayService;

    public function __construct(TimeValidator $timeValidator, WorkdayService $workdayService)
    {
        $this->timeValidator = $timeValidator;
        $this->workdayService = $workdayService;
    }

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

        // Not Done

        // $directureIsLeave = Leave::approved()
        //     ->whereHas('employee.user', function ($query) {
        //         $query->where('role', UserRole::DIRECTOR);
        //     })
        //     ->whereDate('date_start', '<=', $date)
        //     ->whereDate('date_end', '>=', $date)
        //     ->exists();

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

        // Lokasi Office
        $geoSetting = Setting::where('key', 'geo_fencing')->first();
        $officeLocation = [
            'lat' => $geoSetting->values['office_latitude'] ?? -6.200000,
            'lng' => $geoSetting->values['office_longitude'] ?? 106.816666,
            'radius_meters' => $geoSetting->values['office_radius_meters'] ?? 100,
        ];

        return $this->successResponse([
            'employee_stats' => $statsKaryawan,
            'attendance_today' => $rekapKehadiran,
            'office_location' => $officeLocation,
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

        $employee = $user->employee;
        $profile = [
            'name' => $user->name,
            'nik' => $employee->nik,
            'position' => $employee->position?->name ?? '-',
            'team' => $employee->team?->name ?? '-',
            'division' => $employee->team?->division?->name ?? '-',
            'profile_photo' => $employee->getFirstMediaUrl('profile_photo') ?: null,
        ];

        // 1. Sisa Cuti
        $leaveBalance = $this->getLeaveBalance($employeeId, $year);

        // 2. Statistik Kehadiran & Menit Kerja Bulan Ini
        $myAttendance = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->latest()
            ->get();

        $formattedWorkDuration = $this->formatWorkDuration($myAttendance->sum('work_minutes'));

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
        $salaryLogs = $this->getSalaryLogs($employeeId);

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

        // 4. Ambil jadwal Kerja

        $today = Carbon::today();

        $workSchedule = $this->timeValidator->getEmployeeScheduleTimes($employee, $today);

        $isWorkday = $this->workdayService->isWorkday($today);

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
            'today_schedule' => [
                'date' => $today->toDateString(),
                'is_workday' => $isWorkday,
                'label' => $workSchedule['label'] ?? 'Standard Office Hours',
                'work_start' => $workSchedule['work_start_time']->format('H:i'),
                'work_end' => $workSchedule['work_end_time']->format('H:i'),
                'tolerance' => $workSchedule['late_tolerance_minutes'].' min',
                'must_at_office' => (bool) $workSchedule['requires_office_location'],
            ],
        ], 'Employee dashboard data fetched successfully');
    }

    public function mobileHomePage()
    {
        $user = Auth::user();
        $employee = $user->employee;
        $today = Carbon::today();

        // 1. Ambil Status Kehadiran Hari Ini
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        // 2. Ambil Jadwal Kerja Hari Ini
        $workSchedule = $this->timeValidator->getEmployeeScheduleTimes($employee, $today);
        $isWorkday = $this->workdayService->isWorkday($today);

        // 3. Ambil Lokasi Kantor (Geo Fencing)
        $geoSetting = Setting::where('key', 'geo_fencing')->first();
        $officeLocation = [
            'lat' => (float) ($geoSetting->values['office_latitude'] ?? -6.200000),
            'lng' => (float) ($geoSetting->values['office_longitude'] ?? 106.816666),
            'radius' => (int) ($geoSetting->values['office_radius_meters'] ?? 100),
        ];

        // 4. Inisialisasi Koleksi Aktivitas
        $activities = collect();

        // -- Aktivitas dari Tabel Attendance (Clock In/Out) --
        if ($attendance) {
            if ($attendance->clock_in) {
                $activities->push([
                    'type' => 'clock_in',
                    'time' => $attendance->clock_in->format('H:i'),
                    'label' => 'Clock In',
                    'status' => $attendance->late_minutes > 0 ? 'Late' : 'On Time',
                ]);
            }
            if ($attendance->clock_out) {
                $activities->push([
                    'type' => 'clock_out',
                    'time' => $attendance->clock_out->format('H:i'),
                    'label' => 'Clock Out',
                    'status' => 'Finished',
                ]);
            }
        }

        // -- Aktivitas Overtime (Lembur) Approved Hari Ini --
        Overtime::where('employee_id', $employee->id)
            ->whereDate('created_at', $today)
            ->approved()
            ->get()
            ->each(fn ($ot) => $activities->push([
                'type' => 'overtime',
                'time' => $ot->created_at->format('H:i'),
                'label' => 'Overtime Approved',
                'status' => $ot->duration_minutes.' Mins',
            ]));

        // -- Aktivitas Leave (Cuti) Approved Hari Ini --
        Leave::where('employee_id', $employee->id)
            ->whereDate('date_start', '<=', $today)
            ->whereDate('date_end', '>=', $today)
            ->approved()
            ->get()
            ->each(fn ($l) => $activities->push([
                'type' => 'leave',
                'time' => 'All Day',
                'label' => 'Leave: '.$l->leaveType->name,
                'status' => 'Approved',
            ]));

        // -- Aktivitas Early Leave (Pulang Cepat) Approved Hari Ini --
        EarlyLeave::where('employee_id', $employee->id)
            ->whereDate('created_at', $today)
            ->approved()
            ->get()
            ->each(fn ($el) => $activities->push([
                'type' => 'early_leave',
                'time' => $el->created_at->format('H:i'),
                'label' => 'Early Leave',
                'status' => $el->minutes_early.' Mins',
            ]));

        // -- Aktivitas Attendance Request (Koreksi/Manual) Approved Hari Ini --
        AttendanceRequest::where('employee_id', $employee->id)
            ->whereDate('created_at', $today)
            ->approved()
            ->get()
            ->each(fn ($ar) => $activities->push([
                'type' => 'attendance_request',
                'time' => $ar->created_at->format('H:i'),
                'label' => str_replace('_', ' ', ucwords($ar->request_type, '_')),
                'status' => 'Approved',
            ]));

        return $this->successResponse([
            'attendance_status' => [
                'is_checked_in' => (bool) ($attendance->clock_in ?? false),
                'is_checked_out' => (bool) ($attendance->clock_out ?? false),
                'clock_in_time' => $attendance?->clock_in?->format('H:i') ?? '--:--',
                'clock_out_time' => $attendance?->clock_out?->format('H:i') ?? '--:--',
                'attendance_id' => $attendance?->id,
            ],
            'today_schedule' => [
                'date' => $today->toDateString(),
                'is_workday' => $isWorkday,
                'label' => $workSchedule['label'] ?? 'Standard Office Hours',
                'work_start' => $workSchedule['work_start_time']->format('H:i'),
                'work_end' => $workSchedule['work_end_time']->format('H:i'),
                'tolerance' => $workSchedule['late_tolerance_minutes'].' min',
                'must_at_office' => (bool) $workSchedule['requires_office_location'],
            ],
            'office' => $officeLocation,
            'activities' => $activities->sortByDesc('time')->values(),
        ], 'Mobile home data fetched');
    }

    public function mobileStatsPage(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        Log::info($year);
        Log::info($month);

        // 1. Personal Stats (Bulan Ini)
        $myAttendance = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $leaveBalance = $this->getLeaveBalance($employee->id, $year);
        $totalWorkMinutes = $myAttendance->sum('work_minutes');

        $personalStats = [
            'sisa_cuti' => $leaveBalance ? (int) $leaveBalance->remaining_days : 0,
            'total_terlambat' => $myAttendance->where('late_minutes', '>', 0)->count(),
            'total_menit_lembur' => (int) $myAttendance->sum('overtime_minutes'),
            'total_menit_kerja' => (int) $totalWorkMinutes,
            'kehadiran_bulan_ini' => $myAttendance->count(),
        ];

        // 2. Weekly Trend (7 Hari Terakhir)
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weeklyAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->get()
            ->keyBy(fn ($date) => Carbon::parse($date->date)->format('N'));

        $weeklyTrend = [];
        for ($i = 1; $i <= 7; $i++) {
            $dayData = $weeklyAttendance->get($i);
            $weeklyTrend[] = [
                'day' => Carbon::now()->startOfWeek()->addDays($i - 1)->translatedFormat('D'),
                'work_minutes' => $dayData ? (int) $dayData->work_minutes : 0,
                'status' => $dayData ? $dayData->status : 'absent',
            ];
        }

        // 3. Salary Logs (3 Terakhir)
        $salaryLogs = $this->getSalaryLogs($employee->id);

        // 4. Upcoming Holidays (1 Bulan ke Depan)
        $upcomingHolidays = Holiday::where('start_date', '>=', Carbon::today())
            ->where('start_date', '<=', Carbon::today()->addMonth())
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(fn ($h) => [
                'uuid' => $h->uuid,
                'name' => $h->name,
                'date' => $h->start_date->translatedFormat('d M Y'),
                'is_recurring' => $h->is_recurring,
            ]);

        return $this->successResponse([
            'personal_stats' => $personalStats,
            'weekly_trend' => $weeklyTrend,
            'salary_logs' => $salaryLogs,
            'upcoming_holidays' => $upcomingHolidays,
        ], 'Mobile statistics fetched successfully');
    }

    public function mobileDailyTrackerPage(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Request ganti per tanggal, default hari ini
        $dateString = $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateString);
        $currentYear = $date->year;

        // 1. Ambil Data Kehadiran
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        // 2. Ambil Data Early Leave
        $earlyLeave = EarlyLeave::where('employee_id', $employee->id)
            ->whereDate('created_at', $date)
            ->approved()
            ->first();

        // 2.5 Ambil Data Cuti
        $leave = Leave::where('employee_id', $employee->id)
            ->whereDate('date_start', '<=', $date)
            ->whereDate('date_end', '>=', $date)
            ->approved()
            ->first();

        // 2.7 Ambil Data Lembur
        $overtime = Overtime::where('employee_id', $employee->id)
            ->whereDate('created_at', $date)
            ->approved()
            ->first();

        // 3. Ambil Jadwal & Payday
        $workSchedule = $this->timeValidator->getEmployeeScheduleTimes($employee, $date);

        $approvedLeavesThisYear = Leave::where('employee_id', $employee->id)
            ->whereYear('date_start', $currentYear)
            ->approved()
            ->with('leaveType')
            ->get()
            ->map(fn ($l) => [
                'type' => $l->leaveType->name,
                'start' => $l->date_start->translatedFormat('d M Y'),
                'end' => $l->date_end->translatedFormat('d M Y'),
                'status' => 'Approved',
                'is_upcoming' => $l->date_start->isAfter($date),
            ]);

        // Estimasi Payday (Contoh: Tanggal 26 setiap bulan)
        $payday = Carbon::create($date->year, $date->month, 26);
        if ($date->day > 26) {
            $payday->addMonth();
        }

        // 4. Ambil Holiday (Hari Libur)
        $isHoliday = Holiday::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        $allHolidaysThisYear = Holiday::whereYear('start_date', $date->year)
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(fn ($h) => [
                'name' => $h->name,
                'date' => $h->start_date->translatedFormat('d M Y'),
                'full_date' => $h->start_date->format('Y-m-d'),
            ]);

        $tracker = [
            'date' => $date->translatedFormat('d F Y'),
            'clock_in' => [
                'time' => $attendance?->clock_in?->format('H:i') ?? '--:--',
                'is_done' => (bool) $attendance?->clock_in,
                'late_minutes' => $attendance?->late_minutes ?? 0,
                'status' => $attendance ? ($attendance->late_minutes > 0 ? 'Late' : 'On Time') : 'Not Checked In',
            ],
            'clock_out' => [
                'time' => $attendance?->clock_out?->format('H:i') ?? '--:--',
                'is_done' => (bool) $attendance?->clock_out,
                'early_leave_minutes' => $attendance?->early_leave_minutes ?? 0,
                'is_early_leave_approved' => $attendance?->is_early_leave_approved,
                'status' => $attendance?->clock_out ? 'Finished' : 'Not Checked Out',
            ],
            'early_leave' => [
                'is_requested' => (bool) $earlyLeave,
                'minutes' => $earlyLeave?->minutes_early ?? 0,
                'reason' => $earlyLeave?->reason ?? '-',
                'status' => $earlyLeave ? 'Approved' : 'None',
            ],
            'leave' => [
                'is_on_leave' => (bool) $leave,
                'type' => $leave?->leaveType?->name ?? '-',
                'reason' => $leave?->reason ?? '-',
                'status' => $leave ? 'Approved' : 'None',
            ],
            'overtime' => [
                'is_overtime' => (bool) $overtime,
                'minutes' => $overtime?->duration_minutes ?? 0,
                'reason' => $overtime?->reason ?? '-',
                'status' => $overtime ? 'Approved' : 'None',
            ],
            'work_duration' => [
                'work_minutes' => $attendance?->work_minutes ?? 0,
                'overtime_minutes' => $attendance?->overtime_minutes ?? 0,
                'formatted_overtime' => $this->formatWorkDuration($attendance?->overtime_minutes ?? 0),
                'formatted_work' => $this->formatWorkDuration($attendance?->work_minutes ?? 0),
            ],
            'payday_info' => [
                'next_payday' => $payday->translatedFormat('d M Y'),
                'days_remaining' => (int) $date->diffInDays($payday, false),
            ],
            'holiday_info' => [
                'is_holiday' => (bool) $isHoliday,
                'holiday_name' => $isHoliday?->name,
            ],
            'annual_leave_summary' => $approvedLeavesThisYear,
        ];

        $timeline = collect();
        if ($attendance?->clock_in) {
            $timeline->push([
                'time' => $attendance->clock_in->format('H:i'),
                'event' => 'Clock In',
                'desc' => $attendance->late_minutes > 0 ? "Late {$attendance->late_minutes} minute" : 'On Time',
            ]);
        }
        if ($isHoliday) {
            $timeline->push([
                'time' => null,
                'event' => 'Holiday: '.$isHoliday->name,
                'desc' => 'Public Holiday',
            ]);
        }
        if ($leave) {
            $timeline->push([
                'time' => null,
                'event' => 'Leave: '.$leave->leaveType->name,
                'desc' => $leave->reason,
            ]);
        }
        if ($earlyLeave) {
            $timeline->push([
                'time' => $earlyLeave->created_at->format('H:i'),
                'event' => 'Early Leave',
                'desc' => "Duration: {$earlyLeave->minutes_early} minutes",
            ]);
        }
        if ($overtime) {
            $timeline->push([
                'time' => $overtime->created_at->format('H:i'),
                'event' => 'Overtime',
                'desc' => "Duration: {$overtime->duration_minutes} minutes",
            ]);
        }
        if ($attendance?->clock_out) {
            $timeline->push([
                'time' => $attendance->clock_out->format('H:i'),
                'event' => 'Clock Out',
                'desc' => 'Clock out successful',
            ]);
        }

        $upcomingHolidays = Holiday::where('start_date', '>=', $date)
            ->where('start_date', '<=', $date->copy()->addMonths(2))
            ->orderBy('start_date', 'asc')
            ->limit(3)
            ->get()
            ->map(fn ($h) => [
                'name' => $h->name,
                'date' => $h->start_date->translatedFormat('d M Y'),
                'days_away' => (int) $date->diffInDays($h->start_date, false),
            ]);

        return $this->successResponse([
            'tracker' => $tracker,
            'timeline' => $timeline->sortBy('time')->values(),
            'schedule' => [
                'start' => $workSchedule['work_start_time']->format('H:i'),
                'end' => $workSchedule['work_end_time']->format('H:i'),
                'label' => $workSchedule['label'],
            ],
            'yearly_holidays' => $allHolidaysThisYear,
            'my_approved_leaves' => $approvedLeavesThisYear,
            'upcoming_holidays' => $upcomingHolidays->sortBy('days_away')->values(),
        ], 'Daily tracker fetched successfully');
    }

    private function getLeaveBalance($employeeId, $year)
    {
        return EmployeeLeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->first();
    }

    private function formatWorkDuration($totalMinutes)
    {
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return "{$hours}j {$minutes}m";
    }

    private function getSalaryLogs($employeeId)
    {
        return Payroll::where('employee_id', $employeeId)
            ->finalized()
            ->latest('period_start')
            ->take(3)
            ->get()
            ->map(function ($payroll) {
                return [
                    'uuid' => $payroll->uuid,
                    'period' => $payroll->period_start->translatedFormat('F Y'),
                    'payment_date' => $payroll->finalized_at
                        ? 'Paid: '.$payroll->finalized_at->translatedFormat('d M Y')
                        : 'Processing',
                    'net_salary' => (float) $payroll->net_salary,
                    'status' => $payroll->status,
                ];
            });
    }
}
