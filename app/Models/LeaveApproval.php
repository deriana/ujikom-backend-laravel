<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Enums\ApprovalStatus;

class LeaveApproval extends Model
{
    protected $fillable = [
        'leave_id',
        'approver_id',
        'level',
        'status',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'status' => 'integer',
        'approved_at' => 'datetime',
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

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function isPending()
    {
        return $this->status === ApprovalStatus::PENDING->value;
    }

    public function isApproved()
    {
        return $this->status === ApprovalStatus::APPROVED->value;
    }

    public function isRejected()
    {
        return $this->status === ApprovalStatus::REJECTED->value;
    }
}
