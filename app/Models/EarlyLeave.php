<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class EarlyLeave
 *
 * Model yang merepresentasikan pengajuan izin pulang awal oleh karyawan,
 * yang memerlukan persetujuan dari atasan atau pihak berwenang.
 */
class EarlyLeave extends Model
{
    use Notifiable, Notificationable;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'attendance_id', /**< ID data presensi yang terkait */
        'employee_id', /**< ID karyawan yang mengajukan izin */
        'minutes_early', /**< Durasi pulang lebih awal dalam menit */
        'reason', /**< Alasan pengajuan izin pulang awal */
        'attachment', /**< Path/URL lampiran bukti pendukung */
        'status', /**< Status persetujuan (0: Pending, 1: Approved, 2: Rejected) */
        'approved_by_id', /**< ID karyawan yang menyetujui/menolak pengajuan */
        'approved_at', /**< Waktu saat pengajuan diproses */
        'note', /**< Catatan tambahan dari penyetuju */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'approved_at' => 'datetime', /**< Konversi waktu persetujuan ke objek Carbon */
        'status' => 'integer', /**< Konversi status ke integer */
    ];

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

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi ke model Attendance yang terkait dengan izin ini.
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
        return $this->belongsTo(Employee::class, 'approved_by_id');
    }

    /**
     * Scope untuk memfilter pengajuan yang telah disetujui.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::APPROVED->value);
    }

    /**
     * Scope untuk memfilter pengajuan yang masih menunggu persetujuan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::PENDING->value);
    }

    /**
     * Scope untuk memfilter pengajuan yang ditolak.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', ApprovalStatus::REJECTED->value);
    }
}
