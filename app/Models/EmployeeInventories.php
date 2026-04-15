<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class EmployeeInventories
 *
 * Model yang merepresentasikan inventaris item (reward) yang dimiliki oleh karyawan
 * setelah melakukan penukaran poin.
 */
class EmployeeInventories extends Model
{
    use HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid',
        'employee_id',
        'point_item_id',
        'point_item_transaction_id',
        'serial_number',
        'is_used',
        'used_at',
        'expired_at',
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id',
    ];

    /**
     * Boot function untuk menangani event model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->serial_number)) {
                $model->serial_number = 'INV-' . strtoupper(Str::random(10));
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Relasi ke model Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model PointItem (Master Item).
     */
    public function pointItem()
    {
        return $this->belongsTo(PointItem::class);
    }

    /**
     * Relasi ke model PointItemTransaction.
     */
    public function transaction()
    {
        return $this->belongsTo(PointItemTransaction::class, 'point_item_transaction_id');
    }
}
