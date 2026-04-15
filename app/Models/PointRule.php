<?php

namespace App\Models;

use App\Enums\PointCategoryEnum;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class PointRule
 *
 * Model yang merepresentasikan aturan perolehan poin karyawan,
 * menentukan jumlah poin yang didapat untuk setiap jenis aktivitas atau pencapaian.
 */
class PointRule extends Model
{
     use HasFactory, Notifiable, Notificationable;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'category', /**< Kategori poin (Enum PointCategoryEnum) */
        'event_name', /**< Nama aktivitas/kejadian (misal: Tepat Waktu) */
        'points', /**< Jumlah poin yang diberikan */
        'operator', /**< Operator perbandingan (misal: <, >, BETWEEN) */
        'min_value', /**< Nilai ambang batas minimal */
        'max_value', /**< Nilai ambang batas maksimal (untuk BETWEEN) */
        'is_active', /**< Status apakah aturan ini sedang berlaku */
        'description', /**< Penjelasan mengenai aturan poin */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'category' => PointCategoryEnum::class,
        'points' => 'integer',
        'is_active' => 'boolean',
        'min_value' => 'integer',
        'max_value' => 'integer',
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
     * Relasi ke model PointTransaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(PointTransaction::class, 'point_rule_id');
    }

    /**
     * Scope untuk memfilter aturan yang sedang aktif.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
