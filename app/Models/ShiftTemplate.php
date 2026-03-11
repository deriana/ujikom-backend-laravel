<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class ShiftTemplate
 *
 * Model yang merepresentasikan template shift kerja, digunakan untuk menentukan
 * jam masuk dan jam pulang yang bersifat dinamis atau tidak tetap bagi karyawan.
 */
class ShiftTemplate extends Model
{
    use Blameable, SoftDeletes;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'name', /**< Nama template shift */
        'start_time', /**< Waktu mulai shift (jam masuk) */
        'end_time', /**< Waktu berakhir shift (jam pulang) */
        'cross_day', /**< Flag apakah shift melewati pergantian hari (lintas hari) */
        'late_tolerance_minutes', /**< Toleransi keterlambatan dalam satuan menit */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id', /**< ID user pengubah terakhir */
        'deleted_by_id', /**< ID user penghapus record */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'start_time' => 'datetime:H:i:s', /**< Konversi jam masuk ke format waktu */
        'end_time' => 'datetime:H:i:s', /**< Konversi jam pulang ke format waktu */
        'cross_day' => 'boolean', /**< Konversi flag lintas hari ke boolean */
        'late_tolerance_minutes' => 'integer', /**< Konversi toleransi ke integer */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /**
     * Relasi ke user yang membuat record template shift ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Boot function untuk menangani event model.
     * Digunakan untuk mengotomatisasi pengisian UUID saat pembuatan data.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid(); /**< Generate UUID otomatis */
        });
    }

    /**
     * Relasi one-to-many ke model EmployeeShift.
     * Mendapatkan daftar penugasan shift yang menggunakan template ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
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
