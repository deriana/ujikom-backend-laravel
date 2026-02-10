<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type',
        'date_start', 'date_end',
        'reason', 'attachment',
        'approval_status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'approved_at' => 'datetime',
    ];
}
