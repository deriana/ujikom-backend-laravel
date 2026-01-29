<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Allowance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'amount',
        'type',
        'created_by_id',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'position_allowances')
            ->withPivot('amount')
            ->withTimestamps();
    }
}
