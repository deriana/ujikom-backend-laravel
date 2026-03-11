<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Assessment extends Model
{
    use Notifiable, Notificationable;

    public $customNotification = [];

    public $skipDefaultNotification = true;

    protected $fillable = [
        'evaluator_id',
        'evaluatee_id',
        'period',
        'note',
        'created_by_id',
        'updated_by_id',
    ];

    protected $hidden = [
        'id',
    ];

    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    public function evaluatee()
    {
        return $this->belongsTo(Employee::class, 'evaluatee_id');
    }

    public function assessments_details()
    {
        return $this->hasMany(AssessmentDetail::class);
    }

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
}
