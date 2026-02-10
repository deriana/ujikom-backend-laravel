<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricUser extends Model
{
    protected $fillable = [
        'employee_id',
        'view',
        'descriptor',
    ];

    protected $casts = [
        'descriptor' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
