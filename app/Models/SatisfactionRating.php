<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class SatisfactionRating
 *
 * Model yang merepresentasikan penilaian kepuasan (rating) dan umpan balik (feedback)
 * dari karyawan setelah tiket mereka ditutup.
 */
class SatisfactionRating extends Model
{
    use HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'ticket_id', /**< ID tiket yang dinilai */
        'employee_id', /**< ID karyawan yang memberikan penilaian */
        'rating', /**< Nilai kepuasan (1-5) */
        'feedback', /**< Umpan balik tambahan berupa teks */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'rating' => 'integer',
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
     * Relasi ke model Ticket yang dinilai.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relasi ke model Employee yang memberikan penilaian.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
