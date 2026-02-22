<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Payroll extends Model
{
    use Blameable, Notifiable, Notificationable;

    public $customNotification = [];

    public $skipDefaultNotification = true;

    const STATUS_DRAFT = 0;

    const STATUS_FINALIZED = 1;

    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'base_salary',
        'allowance_total',
        'overtime_pay',
        'manual_adjustment',
        'adjustment_note',
        'gross_salary',
        'status',
        'finalized_at',
        'late_deduction',
        'early_leave_deduction',
        'total_deduction',
        'net_salary',
        'tax_amount',
        'taxable_income',
        'tax_rate',
        'ptkp',
        'slip_path',
        'is_void',
        'void_note',
        'slip_generated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'is_void' => 'boolean',
        'base_salary' => 'decimal:2',
        'allowance_total' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'manual_adjustment' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'early_leave_deduction' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'ptkp' => 'decimal:2',
        'status' => 'integer',
        'finalized_at' => 'datetime',
        'slip_generated_at' => 'datetime',
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    public function scopeForPeriod(Builder $query, $start, $end): Builder
    {
        return $query->where('period_start', $start)
            ->where('period_end', $end);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isFinalized(): bool
    {
        return $this->status == self::STATUS_FINALIZED;
    }

    public function finalize(): void
    {
        $this->update([
            'status' => self::STATUS_FINALIZED,
            'finalized_at' => now(),
        ]);
    }

    public function isVoided(): bool
    {
        return (bool) $this->is_void;
    }
}
