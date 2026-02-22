<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class EarlyLeave extends Model
{
    use Notifiable, Notificationable;

    public $customNotification = [];

    public $skipDefaultNotification = true;

    protected $fillable = [
        'uuid',
        'attendance_id',
        'employee_id',
        'minutes_early',
        'reason',
        'attachment',
        'status',
        'approved_by_id',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'status' => 'integer',
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
