<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Setting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'key', 'values',
    ];

    protected $casts = [
        'values' => 'array',
    ];

    // public function getId($request)
    // {
    //     return ($request->id) ? $request->id : $request->route('settings');
    // }

    // public function setValuesAttribute($value)
    // {
    //     $this->attributes['values'] = json_encode($value);
    // }

}
