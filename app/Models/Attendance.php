<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * Class Attendance
 *
 * Model yang merepresentasikan data presensi harian karyawan,
 * mencakup waktu masuk/keluar, durasi kerja, keterlambatan, dan koordinat lokasi.
 */
class Attendance extends Model
{
    use HasFactory, Notifiable, Notificationable;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan pemilik data presensi */
        'date', /**< Tanggal presensi */
        'status', /**< Status kehadiran (misal: present, absent, leave) */
        'clock_in', /**< Waktu jam masuk */
        'clock_out', /**< Waktu jam keluar */
        'late_minutes', /**< Durasi keterlambatan dalam menit */
        'early_leave_minutes', /**< Durasi pulang awal dalam menit */
        'work_minutes', /**< Total durasi kerja efektif dalam menit */
        'overtime_minutes', /**< Durasi lembur dalam menit */
        'clock_in_photo', /**< Path/URL foto saat jam masuk */
        'clock_out_photo', /**< Path/URL foto saat jam keluar */
        'latitude_in', /**< Koordinat lintang saat jam masuk */
        'longitude_in', /**< Koordinat bujur saat jam masuk */
        'latitude_out', /**< Koordinat lintang saat jam keluar */
        'longitude_out', /**< Koordinat bujur saat jam keluar */
        'is_early_leave_approved', /**< Status persetujuan pulang awal */
        'is_corrected', /**< Flag apakah data telah dikoreksi secara manual */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'date' => 'date', /**< Konversi tanggal ke objek Carbon */
        'clock_in' => 'datetime', /**< Konversi waktu masuk ke objek Carbon */
        'clock_out' => 'datetime', /**< Konversi waktu keluar ke objek Carbon */
    ];

    /**
     * Relasi ke model Employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model AttendanceCorrection.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function attendanceCorrection()
    {
        return $this->hasOne(AttendanceCorrection::class);
    }

    /**
     * Relasi ke model AttendanceLog.
     * Menghubungkan lewat employee_id dan difilter berdasarkan tanggal absensi.
     */
    public function logs()
    {
        // Hubungkan employee_id di Attendance ke employee_id di AttendanceLog
        return $this->hasMany(AttendanceLog::class, 'employee_id', 'employee_id')
            ->whereDate('created_at', $this->date);
    }
}
