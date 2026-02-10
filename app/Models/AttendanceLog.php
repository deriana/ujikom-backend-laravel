<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_nik',
        'status',
        'action',
        'reason',
        'similarity_score',
        'ip_address',
        'user_agent',
        'latitude',
        'longitude',
    ];
}
