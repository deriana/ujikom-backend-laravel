<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssessmentCategory extends Model
{
    use Blameable;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function assessmentsDetails()
    {
        return $this->hasMany(AssessmentDetail::class, 'category_id');
    }
}
