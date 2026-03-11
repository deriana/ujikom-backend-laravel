<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BiometricUser
 *
 * Model yang menyimpan data deskriptor biometrik wajah untuk keperluan
 * verifikasi presensi karyawan.
 */
class BiometricUser extends Model
{
    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan pemilik data biometrik */
        'descriptor', /**< Data array representasi fitur wajah (face descriptor) */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'descriptor' => 'array', /**< Konversi descriptor ke format array */
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
}
