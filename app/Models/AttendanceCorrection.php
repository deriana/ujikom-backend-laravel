<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class AttendanceCorrection
 *
 * Model yang merepresentasikan pengajuan koreksi data presensi oleh karyawan,
 * biasanya digunakan jika terjadi kesalahan pencatatan waktu masuk atau keluar.
 */
class AttendanceCorrection extends Model
{
    use HasFactory, Notifiable, Notificationable;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'attendance_id', /**< ID data presensi yang dikoreksi */
        'employee_id', /**< ID karyawan yang mengajukan koreksi */
        'clock_in_requested', /**< Waktu jam masuk yang diusulkan */
        'clock_out_requested', /**< Waktu jam keluar yang diusulkan */
        'reason', /**< Alasan pengajuan koreksi */
        'attachment', /**< Path/URL lampiran bukti pendukung */
        'status', /**< Status persetujuan (0: Pending, 1: Approved, 2: Rejected) */
        'approver_id', /**< ID karyawan (atasan/HR) yang menyetujui/menolak */
        'approved_at', /**< Waktu saat pengajuan diproses */
        'note', /**< Catatan tambahan dari penyetuju */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'status' => 'integer', /**< Konversi status ke integer */
        'approved_at' => 'datetime', /**< Konversi waktu persetujuan ke objek Carbon */
        'clock_in_requested' => 'datetime', /**< Konversi waktu jam masuk ke objek Carbon */
        'clock_out_requested' => 'datetime', /**< Konversi waktu jam keluar ke objek Carbon */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /**
     * Scope untuk memfilter pengajuan yang masih menunggu persetujuan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope untuk memfilter pengajuan yang telah disetujui.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope untuk memfilter pengajuan yang ditolak.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Relasi ke model Attendance yang akan dikoreksi.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Relasi ke model Employee pemilik pengajuan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model Employee yang bertindak sebagai penyetuju.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    /**
     * Boot function untuk menangani event model.
     * Digunakan untuk mengotomatisasi pengisian UUID saat pembuatan data.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    /**
     * Mendapatkan nama kolom kunci untuk routing Laravel.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
