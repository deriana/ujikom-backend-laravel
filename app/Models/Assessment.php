<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class Assessment
 *
 * Model yang merepresentasikan penilaian kinerja (performance assessment) karyawan
 * yang dilakukan oleh evaluator untuk periode tertentu.
 */
class Assessment extends Model
{
    use Notifiable, Notificationable;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'evaluator_id', /**< ID karyawan yang memberikan penilaian */
        'evaluatee_id', /**< ID karyawan yang dinilai */
        'period', /**< Periode penilaian (format: YYYY-MM) */
        'note', /**< Catatan atau feedback tambahan */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id', /**< ID user pengubah terakhir */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /**
     * Relasi ke model Employee sebagai penilai (evaluator).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    /**
     * Relasi ke model Employee sebagai pihak yang dinilai (evaluatee).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluatee()
    {
        return $this->belongsTo(Employee::class, 'evaluatee_id');
    }

    /**
     * Relasi one-to-many ke detail item penilaian.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assessments_details()
    {
        return $this->hasMany(AssessmentDetail::class);
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
     * Mendapatkan nama kolom kunci untuk routing Laravel.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
