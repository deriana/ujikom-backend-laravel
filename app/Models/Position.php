<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Position extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'name',
        'base_salary',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
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

    public function allowances()
    {
        return $this->belongsToMany(Allowance::class, 'position_allowances')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
