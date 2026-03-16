<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

/**
 * Class Module
 *
 * Model yang merepresentasikan modul sistem, digunakan untuk mengelompokkan
 * berbagai izin (permissions) berdasarkan fungsionalitas aplikasi.
 */
class Module extends Model
{
    use HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'name', /**< Nama unik modul */
        'actions', /**< Daftar aksi yang tersedia dalam modul (array) */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'actions' => 'array', /**< Konversi kolom actions ke format array */
    ];

    /**
     * Relasi one-to-many ke model Permission milik Spatie.
     * Menghubungkan modul dengan daftar izin yang terkait berdasarkan nama modul.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'module_name', 'name');
    }
}
