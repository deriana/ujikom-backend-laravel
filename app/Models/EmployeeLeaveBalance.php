<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingDaysAttribute()
    {
        return $this->total_days - $this->used_days;
    }

    public function useDays(int $days)
    {
        $this->used_days += $days;
        $this->save();
    }

    public function resetForNewYear(int $defaultDays)
    {
        $this->used_days = 0;
        $this->total_days = $defaultDays;
        $this->year = now()->year;
        $this->save();
    }
}
