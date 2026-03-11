<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssessmentDetail extends Model
{
    protected $fillable = [
        'assessment_id',
        'category_id',
        'score',
        'old_category_name',
        'bonus_salary',
    ];

    protected $casts = [
        'bonus_salary: decimal:2',
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

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }
}
