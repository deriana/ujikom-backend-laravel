<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShiftTemplate extends Model
{
    use Blameable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'start_time',
        'end_time',
        'cross_day',
        'late_tolerance_minutes',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'cross_day' => 'boolean',
        'late_tolerance_minutes' => 'integer',
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

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
