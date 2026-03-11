<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AttendanceLog
 *
 * Model yang merepresentasikan log aktivitas presensi, mencakup detail teknis
 * seperti skor kemiripan wajah, alamat IP, dan koordinat lokasi saat melakukan aksi.
 */
class AttendanceLog extends Model
{
    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan yang melakukan aksi */
        'employee_nik', /**< NIK karyawan untuk referensi cepat */
        'status', /**< Status hasil aksi (misal: success, failed) */
        'action', /**< Jenis aksi yang dilakukan (misal: clock_in, clock_out) */
        'reason', /**< Keterangan atau alasan jika terjadi kegagalan */
        'similarity_score', /**< Skor kemiripan wajah hasil pemindaian biometrik */
        'ip_address', /**< Alamat IP perangkat yang digunakan */
        'user_agent', /**< Informasi browser/perangkat yang digunakan */
        'latitude', /**< Koordinat lintang saat aksi dilakukan */
        'longitude', /**< Koordinat bujur saat aksi dilakukan */
    ];
}
