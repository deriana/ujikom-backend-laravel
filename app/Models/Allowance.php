<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Allowance
 *
 * Model yang merepresentasikan data tunjangan karyawan, yang dapat dikaitkan dengan berbagai jabatan.
 */
class Allowance extends Model
{
    use SoftDeletes, Blameable, HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'name', /**< Nama tunjangan */
        'amount', /**< Nominal default tunjangan */
        'type', /**< Tipe tunjangan (misal: fixed, variable) */
        'created_by_id', /**< ID user pembuat */
        'updated_by_id', /**< ID user pengubah terakhir */
        'deleted_by_id', /**< ID user penghapus */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'amount' => 'decimal:2', /**< Konversi nominal ke format desimal */
    ];

    /**
     * Relasi ke user yang membuat record tunjangan ini.
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
     * Mendapatkan nama kolom kunci untuk routing Laravel.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Relasi many-to-many ke model Position.
     * Menghubungkan tunjangan dengan jabatan-jabatan yang berhak menerimanya.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function positions()
    {
        return $this->belongsToMany(Position::class, 'position_allowances')
            ->withPivot('amount')
            ->withTimestamps();
    }
}
