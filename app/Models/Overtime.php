<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class Overtime
 *
 * Model yang merepresentasikan pengajuan lembur oleh karyawan, mencatat durasi,
 * alasan, serta status persetujuan oleh manajer atau pihak berwenang.
 */
class Overtime extends Model
{
    use Notifiable, Notificationable, HasFactory;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'attendance_id', /**< ID data presensi yang terkait dengan lembur */
        'employee_id', /**< ID karyawan yang mengajukan lembur */
        'duration_minutes', /**< Durasi lembur dalam satuan menit */
        'reason', /**< Alasan pengambilan lembur */
        'status', /**< Status persetujuan (ApprovalStatus) */
        'approved_by_id', /**< ID karyawan (atasan) yang menyetujui/menolak */
        'approved_at', /**< Waktu saat persetujuan diberikan */
        'note', /**< Catatan tambahan dari penyetuju */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'approved_at' => 'datetime', /**< Konversi waktu persetujuan ke objek Carbon */
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

    /**
     * Relasi ke model Attendance yang terkait dengan lembur ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Relasi ke model Employee pemilik pengajuan lembur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model Employee yang bertindak sebagai penyetuju (Manager).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'approved_by_id');
    }

    /**
     * Scope untuk memfilter pengajuan lembur yang telah disetujui.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::APPROVED->value);
    }

    /**
     * Scope untuk memfilter pengajuan lembur yang masih menunggu persetujuan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::PENDING->value);
    }

    /**
     * Scope untuk memfilter pengajuan lembur yang ditolak.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeRejected($query)
    {
        return $query->where('status', ApprovalStatus::REJECTED->value);
    }
}
