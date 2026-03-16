<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class Setting
 *
 * Model yang merepresentasikan pengaturan konfigurasi sistem, menyimpan data
 * dalam format key-value serta mendukung penyimpanan media seperti logo dan favicon.
 */
class Setting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'key', /**< Kunci unik untuk identifikasi pengaturan */
        'values', /**< Nilai pengaturan dalam format array/JSON */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'values' => 'array', /**< Konversi kolom values ke format array */
    ];

    /**
     * Mendaftarkan koleksi media untuk Spatie Media Library.
     * Digunakan untuk mengelola file logo dan favicon sistem.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
    }
}
