<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    // protected $guarded = [];
    protected $fillable = [
        'key', 'values',
    ];

    protected $casts = [
        'values' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
    }

    // public function getId($request)
    // {
    //     return ($request->id) ? $request->id : $request->route('settings');
    // }

    // public function setValuesAttribute($value)
    // {
    //     $this->attributes['values'] = json_encode($value);
    // }

}
