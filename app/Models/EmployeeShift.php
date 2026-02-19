<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class EmployeeShift extends Model
{
    use Blameable, Notifiable, Notificationable;

    public $customNotification = [];

    protected $fillable = [
        'uuid',
        'name',
        'employee_id',
        'shift_template_id',
        'shift_date',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'shift_date' => 'date'
    ];

    protected $hidden = [
        'id',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
