<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Enums\ApprovalStatus;

/**
 * Class LeaveApproval
 *
 * Model yang merepresentasikan data persetujuan berjenjang untuk pengajuan cuti,
 * mencatat status persetujuan dari setiap penyetuju (Manager/HR).
 */
class LeaveApproval extends Model
{
    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'leave_id', /**< ID pengajuan cuti yang terkait */
        'approver_id', /**< ID karyawan yang bertindak sebagai penyetuju */
        'level', /**< Tingkat urutan persetujuan (misal: 0 untuk Manager, 1 untuk HR) */
        'status', /**< Status persetujuan (ApprovalStatus) */
        'approved_at', /**< Waktu saat persetujuan diberikan */
        'note', /**< Catatan atau alasan dari penyetuju */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'status' => 'integer', /**< Konversi status ke integer */
        'approved_at' => 'datetime', /**< Konversi waktu persetujuan ke objek Carbon */
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
     * Relasi ke model Leave (Header Pengajuan).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leave()
    {
        return $this->belongsTo(Leave::class);
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
     * Mengecek apakah status persetujuan masih menunggu (Pending).
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === ApprovalStatus::PENDING->value;
    }

    /**
     * Mengecek apakah status persetujuan telah disetujui (Approved).
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->status === ApprovalStatus::APPROVED->value;
    }

    /**
     * Mengecek apakah status persetujuan telah ditolak (Rejected).
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->status === ApprovalStatus::REJECTED->value;
    }
}
