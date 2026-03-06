<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Overtime extends Model
{
    use Notifiable, Notificationable;

    public $customNotification = [];

    public $skipDefaultNotification = true;

    protected $fillable = [
        'uuid',
        'attendance_id',
        'employee_id',
        'duration_minutes',
        'reason',
        'status',
        'approved_by_id',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
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

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager()
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
