<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class LeaveType
 *
 * Model yang merepresentasikan jenis-jenis cuti yang tersedia di perusahaan,
 * mencakup aturan jatah hari, batasan gender, dan persyaratan status keluarga.
 */
class LeaveType extends Model
{
    use Blameable, HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'name', /**< Nama jenis cuti (misal: Cuti Tahunan, Cuti Melahirkan) */
        'is_active', /**< Status apakah jenis cuti ini aktif digunakan */
        'default_days', /**< Jatah hari default per tahun */
        'gender', /**< Batasan gender (null jika untuk semua, 'L' atau 'P') */
        'requires_family_status', /**< Flag jika memerlukan status keluarga tertentu */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id', /**< ID user pengubah terakhir */
        'is_unlimited', /**< Flag jika cuti tidak memiliki batasan kuota hari */
        'description' /**< Deskripsi atau keterangan mengenai jenis cuti */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'default_days' => 'integer', /**< Konversi jatah hari ke integer */
        'is_active' => 'boolean', /**< Konversi status aktif ke boolean */
        'requires_family_status' => 'boolean', /**< Konversi flag status keluarga ke boolean */
        'is_unlimited' => 'boolean' /**< Konversi flag unlimited ke boolean */
    ];

    /**
     * Relasi ke user yang membuat record jenis cuti ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Boot function untuk menangani event model.
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
}
