<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LeaveType extends Model
{
    use Blameable;

    protected $fillable = [
        'name', 'is_active', 'default_days', 'gender', 'requires_family_status',
        'created_by_id', 'updated_by_id', 'is_unlimited', 'description'
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'is_active' => 'boolean',
        'requires_family_status' => 'boolean',
        'is_unlimited' => 'boolean'
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
}
