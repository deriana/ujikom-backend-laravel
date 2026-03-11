<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class WorkSchedule
 *
 * Model yang merepresentasikan template jadwal kerja standar (misal: Jam Kantor Normal),
 * mencakup pengaturan waktu masuk, pulang, istirahat, serta toleransi keterlambatan.
 */
class WorkSchedule extends Model
{
    use Blameable, SoftDeletes;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'name', /**< Nama jadwal kerja */
        'work_mode_id', /**< ID mode kerja yang terkait (WFO/WFH) */
        'work_start_time', /**< Waktu mulai kerja (jam masuk) */
        'work_end_time', /**< Waktu berakhir kerja (jam pulang) */
        'break_start_time', /**< Waktu mulai istirahat */
        'break_end_time', /**< Waktu berakhir istirahat */
        'late_tolerance_minutes', /**< Toleransi keterlambatan dalam menit */
        'requires_office_location', /**< Flag apakah memerlukan validasi lokasi kantor */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id', /**< ID user pengubah terakhir */
        'deleted_by_id' /**< ID user penghapus record */
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
     * Relasi one-to-many ke model EmployeeWorkSchedule.
     * Mendapatkan daftar penugasan karyawan yang menggunakan jadwal ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employeeWorkSchedules(): HasMany
    {
        return $this->hasMany(EmployeeWorkSchedule::class);
    }

    /**
     * Relasi ke model WorkMode (Mode Kerja).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workMode()
    {
        return $this->belongsTo(WorkMode::class);
    }

    /**
     * Relasi ke user yang membuat record jadwal kerja ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
