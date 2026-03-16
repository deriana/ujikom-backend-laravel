<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeeLeaveBalance
 *
 * Model yang merepresentasikan saldo cuti tahunan karyawan untuk jenis cuti tertentu,
 * melacak jumlah hari yang tersedia, yang telah digunakan, dan periode tahunnya.
 */
class EmployeeLeaveBalance extends Model
{
    use HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan pemilik saldo cuti */
        'leave_type_id', /**< ID jenis cuti yang terkait */
        'year', /**< Tahun berlakunya saldo cuti */
        'total_days', /**< Total jatah hari cuti yang diberikan */
        'used_days', /**< Jumlah hari cuti yang telah digunakan */
    ];

    protected $appends = ['remaining_days'];

    /**
     * Relasi ke model Employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model LeaveType (Jenis Cuti).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Accessor untuk menghitung sisa hari cuti yang tersedia.
     *
     * @return int
     */
    public function getRemainingDaysAttribute()
    {
        // Pastikan total_days dan used_days punya nilai default 0 jika null
        return ($this->total_days ?? 0) - ($this->used_days ?? 0);
    }

    /**
     * Menambahkan jumlah hari yang digunakan ke dalam saldo.
     *
     * @param int $days Jumlah hari yang akan dikurangi dari saldo
     * @return void
     */
    public function useDays(int $days)
    {
        $this->used_days += $days;
        $this->save();
    }

    /**
     * Mereset saldo cuti untuk tahun baru dengan jatah hari default.
     *
     * @param int $defaultDays Jumlah jatah hari default untuk tahun baru
     * @return void
     */
    public function resetForNewYear(int $defaultDays)
    {
        $this->used_days = 0;
        $this->total_days = $defaultDays;
        $this->year = now()->year;
        $this->save();
    }
}
