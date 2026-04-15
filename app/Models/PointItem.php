<?php

namespace App\Models;

use App\Enums\PowerUpTypeEnum;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PointItem extends Model implements HasMedia
{
    use Notifiable, Notificationable, HasFactory, InteractsWithMedia;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'required_points',
        'stock',
        'power_up_type',
        'category',
        'is_active',
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'power_up_type' => PowerUpTypeEnum::class,
        'required_points' => 'integer',
        'stock' => 'integer',
        'is_active' => 'boolean',
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

            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name) . '-' . Str::random(5);
            }
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
     * Registrasi koleksi media untuk Spatie Media Library.
     *
     * @param Media|null $media
     * @return void
     */
    public function registerMediaCollections(?Media $media = null): void
    {
        $this->addMediaCollection('point_item_images')
            ->singleFile();
    }

    /**
     * Relasi ke model EmployeeInventories.
     * Mendapatkan daftar inventaris karyawan yang terkait dengan item ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employeeInventories()
    {
        return $this->hasMany(EmployeeInventories::class);
    }
}
