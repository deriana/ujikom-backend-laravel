<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveBalance extends Model
{
    use HasFactory;

    protected $table = 'employee_leave_balances';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
    ];

    /**
     * Relations
     */

    // Relasi ke Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relasi ke LeaveType
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Hitung sisa cuti
     */
    public function getRemainingDaysAttribute()
    {
        return $this->total_days - $this->used_days;
    }

    /**
     * Tambah cuti yang dipakai
     */
    public function useDays(int $days)
    {
        $this->used_days += $days;
        $this->save();
    }

    /**
     * Reset saldo tahunan (opsional)
     */
    public function resetForNewYear(int $defaultDays)
    {
        $this->used_days = 0;
        $this->total_days = $defaultDays;
        $this->year = now()->year;
        $this->save();
    }
}
