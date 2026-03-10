<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Leave extends Model
{
    use Notifiable, Notificationable;

    public $customNotification = [];

    public $skipDefaultNotification = true;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'date_start',
        'date_end',
        'reason',
        'attachment',
        'approval_status',
        'is_half_day',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'is_half_day' => 'boolean',
        'approval_status' => 'integer',
    ];

    protected $appends = ['duration', 'duration_text'];

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

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeLeave()
    {
        return $this->hasOne(EmployeeLeave::class, 'leave_type_id', 'leave_type_id')
            ->where('employee_id', $this->employee_id);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    public function nextApprover()
    {
        return $this->approvals()
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('level')
            ->first()?->approver;
    }

    public function isApprovedByManager()
    {
        return $this->approvals()
            ->where('level', 0)
            ->where('status', ApprovalStatus::APPROVED->value)
            ->exists();
    }

    public function isApprovedByHR()
    {
        return $this->approvals()
            ->where('level', 1)
            ->where('status', ApprovalStatus::APPROVED->value)
            ->exists();
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', ApprovalStatus::APPROVED->value);
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::PENDING->value);
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', ApprovalStatus::REJECTED->value);
    }

    public function getDurationAttribute()
    {
        if ($this->is_half_day) {
            return 0.5;
        }
        if (! $this->date_start || ! $this->date_end) {
            return 0;
        }

        $holidayDates = Holiday::where(function ($q) {
            $q->whereBetween('start_date', [$this->date_start, $this->date_end])
                ->orWhereBetween('end_date', [$this->date_start, $this->date_end]);
        })
            ->get()
            ->flatMap(function ($holiday) {
                if (! $holiday->end_date || $holiday->start_date->equalTo($holiday->end_date)) {
                    return [$holiday->start_date->format('Y-m-d')];
                }

                return CarbonPeriod::create($holiday->start_date, $holiday->end_date)
                    ->toArray();
            })
            ->map(function ($date) {
                return is_string($date) ? $date : $date->format('Y-m-d');
            })
            ->unique()
            ->toArray();

        $duration = $this->date_start->diffInDaysFiltered(function ($date) use ($holidayDates) {
            return ! $date->isWeekend() && ! in_array($date->format('Y-m-d'), $holidayDates);
        }, $this->date_end) + 1;

        return $duration > 0 ? $duration : 0;
    }

    public function getDurationTextAttribute()
    {
        $duration = $this->getDurationAttribute();

        return $duration.($duration > 1 ? ' Days' : ' Day');
    }
}
