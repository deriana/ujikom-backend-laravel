<?php

namespace App\Models;

use App\Enums\PriorityEnum;
use App\Traits\Blameable;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class EmployeeWorkSchedule extends Model
{
    use Blameable, Notifiable, Notificationable;

    public $customNotification = [];

    protected $fillable = [
        'uuid',
        'employee_id',
        'work_schedule_id',
        'start_date',
        'end_date',
        'priority',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'priority' => PriorityEnum::class,

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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    /**
     * Scope: aktif pada tanggal tertentu
     */
    public function scopeActiveOn($query, $date)
    {
        return $query->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeLevel1($query)
    {
        return $query->where('priority', PriorityEnum::LEVEL_1->value);
    }

    public function scopeLevel2($query)
    {
        return $query->where('priority', PriorityEnum::LEVEL_2->value);
    }

    public function scopePriority($query, int $level)
    {
        return $query->where('priority', $level);
    }

    public static function getActiveSchedule($employeeId, $date = null)
    {
        $date = $date ?? now()->toDateString();

        return self::where('employee_id', $employeeId)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
