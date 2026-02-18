<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttendanceRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'request_type',
        'shift_template_id',
        'work_schedules_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by_id',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'integer',
    ];

    protected $hidden = [
        'id',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedules_id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::APPROVED->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::PENDING->value);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ApprovalStatus::REJECTED->value);
    }
}
