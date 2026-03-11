<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class WorkMode
 *
 * Model yang merepresentasikan mode kerja dalam sistem (misal: WFO, WFH, Hybrid),
 * digunakan untuk mengkategorikan jadwal kerja karyawan.
 */
class WorkMode extends Model
{
    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'name', /**< Nama mode kerja */
    ];

    /**
     * Relasi one-to-many ke model WorkSchedule.
     * Mendapatkan daftar jadwal kerja yang menggunakan mode kerja ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class);
    }
}
