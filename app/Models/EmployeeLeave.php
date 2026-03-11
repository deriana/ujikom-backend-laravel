<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class EmployeeLeave
 *
 * Model yang merepresentasikan histori pengambilan cuti oleh karyawan yang telah disetujui,
 * mencatat durasi dan periode cuti untuk sinkronisasi dengan saldo cuti.
 */
class EmployeeLeave extends Model
{
    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan yang mengambil cuti */
        'leave_type_id', /**< ID jenis cuti yang diambil */
        'start_date', /**< Tanggal mulai cuti */
        'end_date', /**< Tanggal berakhir cuti */
        'days_taken', /**< Jumlah hari kerja yang digunakan */
        'status', /**< Status record cuti */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id' /**< ID user pengubah terakhir */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id' /**< Identifier internal database */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'days_taken' => 'integer', /**< Konversi jumlah hari ke integer */
        'start_date' => 'date', /**< Konversi tanggal mulai ke objek Carbon */
        'end_date' => 'date', /**< Konversi tanggal akhir ke objek Carbon */
    ];

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
     * Relasi ke model Employee pemilik histori cuti.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee() {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model LeaveType (Jenis Cuti).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leaveType() {
        return $this->belongsTo(LeaveType::class);
    }
}
