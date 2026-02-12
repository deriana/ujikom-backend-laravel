<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSchedule extends Model
{
    use Blameable, SoftDeletes;

    protected $fillable = [
        'name',
        'work_mode_id',
    ];

    public function employeeWorkSchedules(): HasMany
    {
        return $this->hasMany(EmployeeWorkSchedule::class);
    }

    public function workMode()
    {
        return $this->belongsTo(WorkMode::class);
    }
}
