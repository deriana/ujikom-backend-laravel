<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class AttendanceRequest
 *
 * Model yang merepresentasikan pengajuan perubahan jadwal kerja atau shift oleh karyawan,
 * yang memerlukan persetujuan dari atasan atau pihak berwenang.
 */
class AttendanceRequest extends Model
{
    use Notifiable, Notificationable;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan yang mengajukan permohonan */
        'request_type', /**< Jenis pengajuan (misal: shift_change, schedule_change) */
        'shift_template_id', /**< ID template shift yang diusulkan (jika ada) */
        'work_schedules_id', /**< ID jadwal kerja yang diusulkan (jika ada) */
        'start_date', /**< Tanggal mulai berlakunya perubahan */
        'end_date', /**< Tanggal berakhirnya perubahan */
        'reason', /**< Alasan pengajuan permohonan */
        'status', /**< Status persetujuan (0: Pending, 1: Approved, 2: Rejected) */
        'approved_by_id', /**< ID karyawan yang menyetujui/menolak pengajuan */
        'note', /**< Catatan tambahan dari penyetuju */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'start_date' => 'date', /**< Konversi tanggal mulai ke objek Carbon */
        'end_date' => 'date', /**< Konversi tanggal akhir ke objek Carbon */
        'status' => 'integer', /**< Konversi status ke integer */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
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
     * Relasi ke model Attendance (jika pengajuan terkait data presensi spesifik).
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
     * Relasi ke model ShiftTemplate yang diusulkan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    /**
     * Relasi ke model WorkSchedule yang diusulkan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedules_id');
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
