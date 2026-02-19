<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Attendance extends Model
{
    use Notifiable, Notificationable;

    public $customNotification = [];

    public $skipDefaultNotification = true;

    protected $fillable = [
        'employee_id', 'date', 'status',
        'clock_in', 'clock_out',
        'late_minutes', 'early_leave_minutes',
        'work_minutes', 'overtime_minutes',
        'clock_in_photo', 'clock_out_photo',
        'latitude_in', 'longitude_in',
        'latitude_out', 'longitude_out',
        'is_early_leave_approved'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
