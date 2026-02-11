<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Holiday extends Model
{
    use Blameable;

    protected $fillable = ['uuid', 'name', 'date', 'is_recurring', 'created_by_id', 'updated_by_id'];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    protected $hidden = [
        'id'
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
