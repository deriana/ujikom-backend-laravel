<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class AssessmentDetail
 *
 * Model yang merepresentasikan detail item penilaian kinerja,
 * mencakup skor per kategori dan bonus gaji yang dihasilkan.
 */
class AssessmentDetail extends Model
{
    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'assessment_id', /**< ID header penilaian */
        'category_id', /**< ID kategori penilaian */
        'score', /**< Nilai/skor yang diberikan */
        'old_category_name', /**< Nama kategori saat penilaian dibuat (untuk histori) */
        'bonus_salary', /**< Nominal bonus yang didapat dari item ini */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'bonus_salary' => 'decimal:2', /**< Konversi bonus ke format desimal */
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

    /**
     * Relasi ke model Assessment (Header).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Relasi ke model AssessmentCategory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }
}
