<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
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

    // RELASI

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

    // PENDING approval berikutnya
    public function nextApprover()
    {
        return $this->approvals()
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('level')
            ->first()?->approver;
    }

    // CHECK level approval
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

    // SCOPES
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
}
