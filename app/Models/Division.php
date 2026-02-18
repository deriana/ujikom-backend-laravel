<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Division extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'system_reserve',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
    ];

    protected $hidden = [
        'id'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function teams()
    {
        return $this->hasMany(Team::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
