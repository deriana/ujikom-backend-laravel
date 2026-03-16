<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Position
 *
 * Model yang merepresentasikan jabatan atau posisi struktural dalam perusahaan,
 * mencakup informasi gaji pokok standar dan tunjangan yang terkait.
 */
class Position extends Model
{
    use SoftDeletes, Blameable, HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'name', /**< Nama jabatan */
        'base_salary', /**< Nominal gaji pokok standar untuk jabatan ini */
        'system_reserve', /**< Flag untuk data yang diproteksi sistem */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id', /**< ID user pengubah terakhir */
        'deleted_by_id', /**< ID user penghapus record */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'base_salary' => 'decimal:2', /**< Konversi gaji ke format desimal */
    ];

    /**
     * Relasi ke user yang membuat record jabatan ini.
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

    /**
     * Relasi many-to-many ke model Allowance (Tunjangan).
     * Menghubungkan jabatan dengan daftar tunjangan yang berhak diterima.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function allowances()
    {
        return $this->belongsToMany(Allowance::class, 'position_allowances')
            ->withPivot('amount')
            ->withTimestamps();
    }

    /**
     * Relasi one-to-many ke model Employee.
     * Mendapatkan daftar karyawan yang menempati jabatan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
