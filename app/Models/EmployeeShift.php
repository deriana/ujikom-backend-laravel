<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class EmployeeShift
 *
 * Model yang merepresentasikan penugasan shift kerja spesifik kepada karyawan
 * pada tanggal tertentu berdasarkan template shift yang tersedia.
 */
class EmployeeShift extends Model
{
    use Blameable, Notifiable, Notificationable, HasFactory;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'employee_id', /**< ID karyawan yang ditugaskan */
        'shift_template_id', /**< ID template shift yang digunakan */
        'shift_date', /**< Tanggal pelaksanaan shift */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id', /**< ID user pengubah terakhir */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'shift_date' => 'date' /**< Konversi tanggal shift ke objek Carbon */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /**
     * Relasi ke user yang membuat record penugasan shift ini.
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
     * Relasi ke model Employee pemilik penugasan shift.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model ShiftTemplate yang menjadi acuan waktu kerja.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class);
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
