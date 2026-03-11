<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class Holiday
 *
 * Model yang merepresentasikan data hari libur nasional atau kebijakan perusahaan,
 * digunakan untuk perhitungan durasi cuti dan validasi presensi.
 */
class Holiday extends Model
{
    use Blameable, HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'name', /**< Nama hari libur */
        'start_date', /**< Tanggal mulai libur */
        'end_date', /**< Tanggal berakhir libur */
        'is_recurring', /**< Flag apakah libur berulang setiap tahun */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id' /**< ID user pengubah terakhir */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id' /**< Identifier internal database */
    ];

    /**
     * Relasi ke user yang membuat record hari libur ini.
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
}
