<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkSchedule extends Model
{
    use Blameable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'work_mode_id',
        'work_start_time',
        'work_end_time',
        'break_start_time',
        'break_end_time',
        'requires_office_location',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id'
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

    public function employeeWorkSchedules(): HasMany
    {
        return $this->hasMany(EmployeeWorkSchedule::class);
    }

    public function workMode()
    {
        return $this->belongsTo(WorkMode::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
