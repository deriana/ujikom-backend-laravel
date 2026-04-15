<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PointWallet
 *
 * Model yang merepresentasikan saldo poin karyawan untuk periode tertentu.
 */
class PointWallet extends Model
{
    use HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id',
        'point_period_id',
        'current_balance',
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'current_balance' => 'integer',
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id',
    ];

    /**
     * Relasi ke model Employee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model PointPeriode.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PointPeriode::class, 'point_period_id');
    }
}
