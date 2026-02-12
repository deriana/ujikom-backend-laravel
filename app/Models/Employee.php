<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Employee extends Model implements HasMedia
{
    use Blameable, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'nik',
        'user_id',
        'team_id',
        'position_id',
        'manager_id',
        'work_mode_id',
        'employee_status',
        'contract_start',
        'contract_end',
        'base_salary',
        'phone',
        'gender',
        'date_of_birth',
        'address',
        'join_date',
        'resign_date',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'employee_status' => EmployeeStatus::class,
        'join_date' => 'date',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'resign_date' => 'date',
        'date_of_birth' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (empty($employee->nik)) {
                $employee->nik = self::generateNik();
            }
        });
    }

    public static function generateNik(): string
    {
        $year = now()->format('Y');

        $last = self::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $number = $last ? ((int) substr($last->nik, -4)) + 1 : 1;

        return 'EMP'.$year.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photo')->singleFile();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resign_date');
    }

    public function scopePermanent($query)
    {
        return $query->where('employee_status', EmployeeStatus::PERMANENT);
    }

    public function isActive(): bool
    {
        return is_null($this->resign_date);
    }

    public function isContract(): bool
    {
        return $this->employee_status === EmployeeStatus::CONTRACT;
    }

    public function isPermanent(): bool
    {
        return $this->employee_status === EmployeeStatus::PERMANENT;
    }

    public function hasContractEnded(): bool
    {
        return $this->contract_end && now()->gt($this->contract_end);
    }

    public function biometrics()
    {
        return $this->hasMany(BiometricUser::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->employee_status) {
            EmployeeStatus::PERMANENT => 'Permanent',
            EmployeeStatus::CONTRACT => 'Contract',
            EmployeeStatus::INTERN => 'Intern',
            EmployeeStatus::PROBATION => 'Probation',
        };
    }
}
