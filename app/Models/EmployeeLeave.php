<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeeLeave extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 'days_taken',
        'status', 'created_by_id', 'updated_by_id'
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'days_taken' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function employee() {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType() {
        return $this->belongsTo(LeaveType::class);
    }
}
