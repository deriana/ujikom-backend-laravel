<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class PointItemTransaction
 *
 * Model yang merepresentasikan transaksi penukaran poin dengan item (reward) oleh karyawan.
 */
class PointItemTransaction extends Model
{
    use Notifiable, Notificationable, HasFactory;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = [];

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid',
        'employee_id',
        'point_item_id',
        'point_period_id',
        'quantity',
        'total_points',
        'status',
        'note',
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'status' => 'integer',
        'quantity' => 'integer',
        'total_points' => 'integer',
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
     * Relasi ke model PointPeriode.
     */
    public function period()
    {
        return $this->belongsTo(PointPeriode::class, 'point_period_id');
    }
}
