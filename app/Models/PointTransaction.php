<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class PointTransaction
 *
 * Model yang merepresentasikan riwayat perolehan poin karyawan,
 * mencatat detail transaksi poin berdasarkan aturan dan periode tertentu.
 */
class PointTransaction extends Model
{
     use HasFactory, Notifiable, Notificationable;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'employee_id', /**< ID karyawan penerima poin */
        'point_rule_id', /**< ID aturan poin yang diterapkan */
        'point_period_id', /**< ID periode pengumpulan poin */
        'current_points', /**< Jumlah poin yang didapat pada transaksi ini */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'current_points' => 'integer',
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id',
    ];

    /**
     * Boot function untuk menangani event model.
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
     * Relasi ke model Employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model PointRule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rule()
    {
        return $this->belongsTo(PointRule::class, 'point_rule_id');
    }

    /**
     * Relasi ke model PointPeriode.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function period()
    {
        return $this->belongsTo(PointPeriode::class, 'point_period_id');
    }
}
